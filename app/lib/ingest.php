<?php
declare(strict_types=1);
/*
 * Dépôt de données par les tâches automatiques de Claude (mails importants, guides de voyage).
 * Authentification : en-tête X-Ingest-Key = HMAC(APP_KEY). Seules des données sont écrites,
 * toujours affichées échappées : aucun code n'est accepté.
 */

function ingest_key(): string { return hash_hmac('sha256', 'chemini-ingest-v1', APP_KEY); }

function ingest_handle(string $kind): never {
    $send = function (array $d, int $c = 200): never { http_response_code($c); header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($method !== 'POST' && !($method === 'GET' && in_array($kind, ['honoraires', 'consignes'], true))) $send(['error' => 'POST attendu'], 405);
    if (ip_blocked()) $send(['error' => 'IP bloquée'], 429);
    if (!hash_equals(ingest_key(), (string)($_SERVER['HTTP_X_INGEST_KEY'] ?? ''))) { ip_fail(null, 'ingest'); $send(['error' => 'clé invalide'], 403); }
    if ($kind === 'consignes' && $method === 'GET') { // consignes des Tâches IA à traiter (à faire / en cours), par priorité
        $rows = q("SELECT c.id, c.body, c.priority, c.status, c.space, c.result, c.created_at, u.email AS user_email, u.name AS user_name
                   FROM consignes c JOIN users u ON u.id = c.user_id WHERE c.status IN ('todo','doing')
                   ORDER BY CASE c.priority WHEN 'haute' THEN 0 WHEN 'normale' THEN 1 ELSE 2 END, c.created_at ASC LIMIT 100")->fetchAll();
        $send(['ok' => true, 'items' => $rows]);
    }
    if ($kind === 'honoraires') { // lecture des données actuelles du tableau des honoraires (pour l'audit mensuel)
        $st = q('SELECT json, updated_at FROM doc_state WHERE slug=?', ['honoraires'])->fetch();
        $html = (string)q('SELECT html FROM docs WHERE slug=?', ['honoraires'])->fetchColumn();
        $data = $st ? json_decode($st['json'], true) : null;
        if (!$data && preg_match('#<script id="seed" type="application/json">(.*?)</script>#s', $html, $m)) $data = json_decode($m[1], true);
        $prev = audit_latest('honoraires');
        $send(['ok' => true, 'modified_at' => $st ? (int)$st['updated_at'] : null, 'data' => $data, 'previous_audit' => $prev['d'] ?? null]);
    }
    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > 2_000_000) $send(['error' => 'trop volumineux'], 413);
    $d = json_decode($raw, true);
    if (!is_array($d)) $send(['error' => 'JSON invalide'], 400);
    $str = fn($v, int $max) => mb_substr(trim((string)($v ?? '')), 0, $max);

    if ($kind === 'mails') {
        $u = q('SELECT id FROM users WHERE email=?', [strtolower($str($d['user_email'] ?? '', 190))])->fetchColumn();
        if (!$u) $send(['error' => 'utilisateur inconnu'], 404);
        $added = 0;
        foreach ((array)($d['items'] ?? []) as $it) {
            if (!is_array($it) || $str($it['id'] ?? '', 400) === '') continue;
            $prio = in_array($it['priority'] ?? '', ['crit', 'warn', 'ok'], true) ? $it['priority'] : 'warn';
            $link = $str($it['link'] ?? '', 1000);
            if ($link !== '' && !preg_match('#^https://(outlook\.office365\.com|outlook\.office\.com|outlook\.live\.com)/#', $link)) $link = '';
            $st = q('INSERT INTO mail_items(user_id,msg_id,from_name,from_email,subject,summary,reason,priority,received_at,link,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)
                     ON CONFLICT(user_id,msg_id) DO UPDATE SET summary=excluded.summary, reason=excluded.reason, priority=excluded.priority',
                [$u, $str($it['id'], 400), $str($it['from'] ?? '', 160), $str($it['from_email'] ?? '', 190), $str($it['subject'] ?? '(sans objet)', 300),
                 $str($it['summary'] ?? '', 600), $str($it['reason'] ?? '', 300), $prio, strtotime((string)($it['received'] ?? '')) ?: time(), $link ?: null, time()]);
            $added += $st->rowCount();
        }
        $send(['ok' => true, 'saved' => $added]);
    }

    if ($kind === 'trip') {
        $dest = $str($d['destination'] ?? '', 120);
        if ($dest === '') $send(['error' => 'destination manquante'], 400);
        $space = in_array($d['space'] ?? 'famille', ['famille', 'direction', 'gid'], true) ? ($d['space'] ?? 'famille') : 'famille';
        $json = json_encode($d, JSON_UNESCAPED_UNICODE);
        $id = (int)($d['id'] ?? 0);
        if ($id && q('SELECT 1 FROM trips WHERE id=?', [$id])->fetchColumn()) {
            q('UPDATE trips SET destination=?, duration=?, data=?, space=?, updated_at=? WHERE id=?', [$dest, $str($d['duration'] ?? '', 60), $json, $space, time(), $id]);
        } else {
            q('INSERT INTO trips(space,destination,duration,data,created_at,updated_at) VALUES(?,?,?,?,?,?)', [$space, $dest, $str($d['duration'] ?? '', 60), $json, time(), time()]);
            $id = (int)db()->lastInsertId();
        }
        $send(['ok' => true, 'id' => $id, 'url' => BASE_URL . '/voyages/' . $id]);
    }
    if ($kind === 'consignes') { // mise à jour du statut (todo / doing / done) et du compte rendu d'une consigne
        $id = (int)($d['id'] ?? 0);
        $status = (string)($d['status'] ?? '');
        if (!$id || !isset(CONSIGNE_STATUS[$status])) $send(['error' => 'id ou statut invalide'], 400);
        $cur = q('SELECT status FROM consignes WHERE id=?', [$id])->fetchColumn();
        if ($cur === false) $send(['error' => 'consigne introuvable (supprimée ?)'], 404);
        $result = array_key_exists('result', $d) ? $str($d['result'], 8000) : null;
        q('UPDATE consignes SET status=?, result=COALESCE(?, result), updated_at=?,
             started_at=CASE WHEN ?=\'doing\' AND started_at IS NULL THEN ? ELSE started_at END,
             done_at=CASE WHEN ?=\'done\' THEN ? ELSE NULL END WHERE id=?',
          [$status, $result, time(), $status, time(), $status, time(), $id]);
        $send(['ok' => true, 'id' => $id, 'status' => $status]);
    }
    if ($kind === 'ag') { // planning des AG relevé dans ImmOpen : {date:"AAAA-MM-JJ", rows:[{code, nom, lots, gest, compta, last, nomination, fin_mandat, debut_ex, fin_ex, ag_date, ag_heure, delai_max, conseil, limite_convoc, convoc, notification, pv}]}
        if (empty($d['rows']) || !is_array($d['rows'])) $send(['error' => 'rows manquant'], 400);
        q("INSERT INTO kv(k,v,updated_at) VALUES('ag_planning',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode(['date' => (string)($d['date'] ?? date('Y-m-d')), 'rows' => array_values($d['rows'])], JSON_UNESCAPED_UNICODE), time()]);
        $send(['ok' => true, 'rows' => count($d['rows'])]);
    }
    if ($kind === 'annexes') { // relevé ImmOpen des honoraires annexes : {at, source, cats:[{acc,k,l}], years:{"2026-2027":{end, partial, b:{code:{catIndex:montant}}}}}
        $d['id'] = 'ingest-' . date('YmdHis');
        if (!annexes_store($d)) $send(['error' => 'cats / years manquants'], 400);
        $send(['ok' => true, 'years' => array_keys($d['years'])]);
    }
    if ($kind === 'veille') {
        $week = $str($d['week'] ?? date('o-\\WW'), 12);
        $n = 0;
        foreach ((array)($d['items'] ?? []) as $it) {
            if (!is_array($it)) continue;
            $url = $str($it['url'] ?? '', 1000);
            if (!preg_match('#^https://[^\s"<>]+$#', $url) || $str($it['title'] ?? '', 300) === '' || $str($it['summary'] ?? '', 2000) === '') continue;
            q('INSERT INTO veille(url,title,source,published_at,summary,impact,important,week,created_at) VALUES(?,?,?,?,?,?,?,?,?)
               ON CONFLICT(url) DO UPDATE SET title=excluded.title, summary=excluded.summary, impact=excluded.impact, important=excluded.important, week=excluded.week',
              [$url, $str($it['title'], 300), $str($it['source'] ?? '', 120), strtotime((string)($it['date'] ?? '')) ?: null,
               $str($it['summary'], 2000), $str($it['impact'] ?? '', 600), !empty($it['important']) ? 1 : 0, $week, time()]);
            $n++;
        }
        $send(['ok' => true, 'saved' => $n]);
    }
    if ($kind === 'buildings') {
        $n = ['buildings' => 0, 'info' => 0, 'events' => 0];
        if (!empty($d['buildings']) && is_array($d['buildings'])) {
            foreach ($d['buildings'] as $r) {
                if (!is_array($r) || !preg_match('/^[0-9A-Za-z]{1,10}$/', (string)($r['code'] ?? '')) || $str($r['label'] ?? '', 200) === '') continue;
                q('INSERT INTO buildings(code,kind,label,addr2,cp,ville,gest,compta,assist,exercice) VALUES(?,?,?,?,?,?,?,?,?,?)
                   ON CONFLICT(code) DO UPDATE SET kind=excluded.kind,label=excluded.label,addr2=excluded.addr2,cp=excluded.cp,ville=excluded.ville,gest=excluded.gest,compta=excluded.compta,assist=excluded.assist,exercice=excluded.exercice',
                  [$r['code'], ($r['kind'] ?? 'syndic') === 'autre' ? 'autre' : 'syndic', $str($r['label'], 200), $str($r['addr2'] ?? '', 200) ?: null, $str($r['cp'] ?? '', 10), $str($r['ville'] ?? '', 80),
                   $str($r['gest'] ?? '', 80) ?: null, $str($r['compta'] ?? '', 80) ?: null, $str($r['assist'] ?? '', 80) ?: null, $str($r['exercice'] ?? '', 80) ?: null]);
                $n['buildings']++;
            }
            q("INSERT INTO kv(k,v,updated_at) VALUES('immopen_at',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [$str($d['immopen_date'] ?? date('Y-m-d'), 10), time()]);
        }
        // Informations de contact : ne complètent que les champs vides (les saisies de l'équipe priment)
        foreach ((array)($d['info'] ?? []) as $r) {
            if (!is_array($r) || !q('SELECT 1 FROM buildings WHERE code=?', [(string)($r['code'] ?? '')])->fetchColumn()) continue;
            $cur = q('SELECT * FROM building_info WHERE code=?', [$r['code']])->fetch() ?: [];
            $vals = [];
            foreach (['president' => 120, 'president_phone' => 40, 'president_email' => 190, 'cs_members' => 2000, 'gardien' => 2000, 'acces' => 2000] as $f => $max)
                $vals[$f] = !empty($cur[$f]) ? $cur[$f] : ($str($r[$f] ?? '', $max) ?: null);
            if (!empty($vals['president_email']) && !filter_var($vals['president_email'], FILTER_VALIDATE_EMAIL)) $vals['president_email'] = $cur['president_email'] ?? null;
            q('INSERT INTO building_info(code,president,president_phone,president_email,cs_members,gardien,acces,sources,updated_at) VALUES(?,?,?,?,?,?,?,?,?)
               ON CONFLICT(code) DO UPDATE SET president=excluded.president,president_phone=excluded.president_phone,president_email=excluded.president_email,cs_members=excluded.cs_members,gardien=excluded.gardien,acces=excluded.acces,sources=excluded.sources,updated_at=excluded.updated_at',
              [$r['code'], $vals['president'], $vals['president_phone'], $vals['president_email'], $vals['cs_members'], $vals['gardien'], $vals['acces'], $str($r['source'] ?? 'auto', 300), time()]);
            $n['info']++;
        }
        foreach ((array)($d['events'] ?? []) as $r) {
            if (!is_array($r) || $str($r['title'] ?? '', 300) === '' || !q('SELECT 1 FROM buildings WHERE code=?', [(string)($r['code'] ?? '')])->fetchColumn()) continue;
            $link = $str($r['link'] ?? '', 1000);
            if ($link !== '' && !preg_match('#^https://(outlook\.office365\.com|outlook\.office\.com)/#', $link)) $link = '';
            $kindE = in_array($r['kind'] ?? 'mail', ['mail', 'ag', 'immopen', 'finance'], true) ? $r['kind'] : 'mail';
            $st = q('INSERT OR IGNORE INTO building_events(code,at,kind,title,detail,link,ext_id,created_at) VALUES(?,?,?,?,?,?,?,?)',
              [$r['code'], strtotime((string)($r['date'] ?? '')) ?: time(), $kindE, $str($r['title'], 300), $str($r['detail'] ?? '', 600) ?: null, $link ?: null, $str($r['id'] ?? '', 400) ?: null, time()]);
            $n['events'] += $st->rowCount();
        }
        $send(['ok' => true] + $n);
    }
    if ($kind === 'compta') {
        $id = compta_store($d);
        $send(['ok' => true, 'id' => $id, 'url' => BASE_URL . '/comptabilite']);
    }
    if ($kind === 'audit') {
        $period = $str($d['period'] ?? '', 7);
        if (!preg_match('/^\d{4}-\d{2}$/', $period) || $str($d['headline'] ?? '', 300) === '') $send(['error' => 'period (AAAA-MM) et headline requis'], 400);
        $kindA = in_array($d['kind'] ?? 'honoraires', ['honoraires'], true) ? 'honoraires' : 'honoraires';
        q('INSERT INTO audits(kind,period,headline,data,created_at) VALUES(?,?,?,?,?) ON CONFLICT(kind,period) DO UPDATE SET headline=excluded.headline, data=excluded.data, created_at=excluded.created_at',
          [$kindA, $period, $str($d['headline'], 300), json_encode($d, JSON_UNESCAPED_UNICODE), time()]);
        $id = (int)q('SELECT id FROM audits WHERE kind=? AND period=?', [$kindA, $period])->fetchColumn();
        q('DELETE FROM audit_reads WHERE audit_id=?', [$id]);
        $send(['ok' => true, 'id' => $id, 'url' => BASE_URL . '/honoraires#opti']);
    }
    $send(['error' => 'type inconnu'], 404);
}


/** Enregistre un contrôle comptable (données uniquement, affichées échappées). */
function compta_store(array $d): int {
    $str = fn($v, int $max) => mb_substr(trim((string)($v ?? '')), 0, $max);
        $period = $str($d['period'] ?? date('Y-m-d'), 40);
        $clean = function (array $rows, array $fields) use ($str): array {
            $out = [];
            foreach (array_slice($rows, 0, 2000) as $r) {
                if (!is_array($r)) continue;
                $o = [];
                foreach ($fields as $f => $max) $o[$f] = is_numeric($r[$f] ?? null) && $max === 0 ? (float)$r[$f] : $str($r[$f] ?? '', $max ?: 40);
                $o['k'] = hash('sha256', json_encode([$r['id'] ?? '', $o['code'] ?? '', $o['montant'] ?? '', $o['date'] ?? '', $o['piece'] ?? ($o['reference'] ?? ''), $o['fournisseur'] ?? ($o['type'] ?? '')]));
                $out[] = $o;
            }
            return $out;
        };
        $data = [
            'period' => $period, 'source' => $str($d['source'] ?? '', 300), 'summary' => $str($d['summary'] ?? '', 2000),
            'by_comptable' => $clean((array)($d['by_comptable'] ?? []), ['name' => 80, 'immeubles' => 0, 'ecritures' => 0, 'anomalies' => 0, 'factures_manquantes' => 0, 'montant_manquant' => 0, 'note' => 400]),
            'anomalies' => $clean((array)($d['anomalies'] ?? []), ['id' => 120, 'comptable' => 80, 'code' => 10, 'immeuble' => 200, 'date' => 20, 'piece' => 60, 'compte' => 40, 'montant' => 0, 'type' => 120, 'detail' => 600, 'severity' => 10]),
            'factures' => $clean((array)($d['factures'] ?? []), ['id' => 120, 'comptable' => 80, 'code' => 10, 'immeuble' => 200, 'fournisseur' => 160, 'date' => 20, 'montant' => 0, 'reference' => 80, 'source' => 200, 'detail' => 600]),
        ];
        q('INSERT INTO compta_runs(period,data,created_at) VALUES(?,?,?)', [$period, json_encode($data, JSON_UNESCAPED_UNICODE), time()]);
    return (int)db()->lastInsertId();
}

/** Données ImmOpen relevées par Claude, livrées chiffrées dans le dépôt (app/seed/immopen.enc), chargées une seule fois par version. */
/** Honoraires annexes (fournisseurs GID … dans ImmOpen) : crédits par immeuble et par exercice (1er avril → 31 mars). */
function annexes_store(array $d): bool {
    if (empty($d['cats']) || empty($d['years']) || !is_array($d['years'])) return false;
    q("INSERT INTO kv(k,v,updated_at) VALUES('hono_annexes',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode($d, JSON_UNESCAPED_UNICODE), time()]);
    return true;
}
function annexes_sync(): void {
    $f = APP_DIR . '/seed/annexes.enc';
    if (!is_file($f)) return;
    $plain = unseal(trim((string)file_get_contents($f)));
    if ($plain === null) return;
    $d = json_decode(@gzdecode($plain) ?: $plain, true);
    if (!is_array($d) || empty($d['id'])) return;
    if ((string)q("SELECT v FROM kv WHERE k='annexes_seed'")->fetchColumn() === $d['id']) return;
    $cur = json_decode((string)q("SELECT v FROM kv WHERE k='hono_annexes'")->fetchColumn(), true);
    if (!is_array($cur) || ($cur['at'] ?? '') <= ($d['at'] ?? '')) annexes_store($d); // ne remplace jamais un relevé plus récent
    q("INSERT INTO kv(k,v,updated_at) VALUES('annexes_seed',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [$d['id'], time()]);
}
function annexes_data(): ?array {
    annexes_sync();
    $d = json_decode((string)q("SELECT v FROM kv WHERE k='hono_annexes'")->fetchColumn(), true);
    if (!is_array($d)) return null;
    $names = [];
    foreach (q('SELECT code, label FROM buildings')->fetchAll() as $b) $names[$b['code']] = $b['label'];
    $d['names'] = $names;
    return $d;
}

function immopen_sync(): void {
    $f = APP_DIR . '/seed/immopen.enc';
    if (!is_file($f)) return;
    $plain = unseal(trim((string)file_get_contents($f)));
    if ($plain === null) return;
    $plain = @gzdecode($plain) ?: $plain;
    $d = json_decode($plain, true);
    if (!is_array($d) || empty($d['id'])) return;
    if ((string)q("SELECT v FROM kv WHERE k='immopen_seed'")->fetchColumn() === $d['id']) return;
    if (!empty($d['compta'])) compta_store($d['compta']);
    if (!empty($d['ag']['rows'])) q("INSERT INTO kv(k,v,updated_at) VALUES('ag_planning',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode($d['ag'], JSON_UNESCAPED_UNICODE), time()]);
    q("INSERT INTO kv(k,v,updated_at) VALUES('immopen_seed',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [$d['id'], time()]);
}
