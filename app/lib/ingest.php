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
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $send(['error' => 'POST attendu'], 405);
    if (ip_blocked()) $send(['error' => 'IP bloquée'], 429);
    if (!hash_equals(ingest_key(), (string)($_SERVER['HTTP_X_INGEST_KEY'] ?? ''))) { ip_fail(null, 'ingest'); $send(['error' => 'clé invalide'], 403); }
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
    $send(['error' => 'type inconnu'], 404);
}
