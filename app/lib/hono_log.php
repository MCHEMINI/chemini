<?php
declare(strict_types=1);

/* Journal des modifications du tableau des honoraires (onglet « Modifications » et page d'historique). */

const HONO_FIELDS = [
    'port' => 'Portefeuille', 'gest' => 'Gestionnaire', 'num' => 'N°', 'imm' => 'Immeuble', 'lots' => 'Lots', 'perdus' => 'Lots perdus', 'archive' => 'Archive',
    'plage' => 'Plage horaire AG', 'fin' => 'Heure fin AG', 'cs1' => 'CS 1h', 'cs2' => 'CS 2h', 'visite' => 'Visites 1h', 'coutAG' => 'Coût AG supp. TTC',
    'dateAG' => 'AG tenue le', 'datePV' => 'Remise PV', 'h2526' => 'Hon. 25-26 voté', 'h2627v' => 'Hon. 26-27 à voter', 'h2627e' => 'Hon. 26-27 voté',
    'h2728' => 'Budget 27-28', 'h2829' => 'Budget 28-29', 'taux' => 'Taux spécifique', 'nv' => 'Nouveau',
    'contrat' => 'N° contrat', 'nom' => 'Nom', 'lib' => 'Contrat', 'ht' => 'Montant HT',
];

/** État actuel du tableau (état partagé, sinon données d'origine du fichier). */
function hono_state(): array {
    $st = q('SELECT json FROM doc_state WHERE slug=?', ['honoraires'])->fetchColumn();
    $d = $st ? json_decode((string)$st, true) : null;
    if (!$d) {
        $html = (string)q('SELECT html FROM docs WHERE slug=?', ['honoraires'])->fetchColumn();
        if (preg_match('#<script id="seed" type="application/json">(.*?)</script>#s', $html, $m)) $d = json_decode($m[1], true);
    }
    return is_array($d) ? $d : [];
}

function hono_v($v): string {
    if ($v === null) return '';
    if (is_float($v) || is_int($v)) return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    if (is_bool($v)) return $v ? 'oui' : 'non';
    if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
    $s = trim((string)$v);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) return $m[3] . '/' . $m[2] . '/' . $m[1];
    if (is_numeric($s) && preg_match('/^-?\d+(\.\d+)?$/', $s)) return hono_v((float)$s);
    return $s;
}

/** Compare deux états et enregistre les différences. Renvoie le nombre de changements notés. */
function hono_log_diff(array $old, array $new, ?int $uid, string $source): int {
    if (!$old) return 0;
    $rows = [];
    $ref = fn(array $b) => trim((isset($b['num']) && $b['num'] !== '' ? $b['num'] . ' · ' : '') . (string)($b['imm'] ?? $b['nom'] ?? ''));
    foreach (['bld' => 'Immeubles', 'contr' => 'Contrats HT'] as $k => $tab) {
        $o = []; $n = [];
        $kf = fn($b, $i) => $k === 'bld' ? (string)($b['_key'] ?? (($b['port'] ?? '') . '|' . ($b['num'] ?? '') . '|' . ($b['imm'] ?? ''))) : (string)($b['contrat'] ?? $i);
        foreach ((array)($old[$k] ?? []) as $i => $b) if (is_array($b)) $o[$kf($b, $i)] = $b;
        foreach ((array)($new[$k] ?? []) as $i => $b) if (is_array($b)) $n[$kf($b, $i)] = $b;
        foreach ($n as $key => $b) {
            if (!isset($o[$key])) { $rows[] = [$tab, $ref($b), 'Ligne ajoutée', '', '']; continue; }
            foreach ($b as $f => $v) {
                if ($f === '_key' || $f[0] === '_') continue;
                $a = hono_v($o[$key][$f] ?? null); $c = hono_v($v);
                if ($a !== $c) $rows[] = [$tab, $ref($b), HONO_FIELDS[$f] ?? $f, $a, $c];
            }
        }
        foreach ($o as $key => $b) if (!isset($n[$key])) $rows[] = [$tab, $ref($b), 'Ligne supprimée', '', ''];
    }
    if (json_encode($old['hist'] ?? null) !== json_encode($new['hist'] ?? null)) $rows[] = ['Historique 2019-2029', '', 'Données de l’historique', '', 'modifiées'];
    if (count($rows) > 300) $rows = [['Tous les onglets', '', 'Remplacement des données', '', count($rows) . ' valeurs modifiées']];
    $t = time();
    foreach ($rows as $r) q('INSERT INTO hono_changes(at,user_id,source,tab,ref,field,old,new) VALUES(?,?,?,?,?,?,?,?)', [$t, $uid, $source, $r[0], mb_substr($r[1], 0, 200), mb_substr($r[2], 0, 80), mb_substr($r[3], 0, 300), mb_substr($r[4], 0, 300)]);
    return count($rows);
}

/** Modifications récentes groupées pour l'affichage : [{at, user, source, tab, ref, field, old, new}] */
function hono_changes(int $since = 0, int $limit = 3000): array {
    return array_map(fn($r) => ['at' => (int)$r['at'], 'user' => $r['name'] ?: ($r['source'] === 'auto' ? 'Mise à jour automatique' : '—'), 'source' => $r['source'], 'tab' => $r['tab'],
        'ref' => (string)$r['ref'], 'field' => (string)$r['field'], 'old' => (string)$r['old'], 'new' => (string)$r['new']],
        q('SELECT c.*, u.name FROM hono_changes c LEFT JOIN users u ON u.id=c.user_id WHERE c.at>=? ORDER BY c.at DESC, c.id DESC LIMIT ' . (int)$limit, [$since])->fetchAll());
}

/**
 * Mise à jour automatique des cellules vides d'après les PV d'AG (mails) et le planning ImmOpen :
 * AG tenue le, Remise PV, Hon. 26-27 voté. Ne remplace jamais une valeur saisie.
 */
function hono_autofill(?array $extra = null): array {
    $st = hono_state(); if (!$st || empty($st['bld'])) return ['ok' => false, 'error' => 'tableau vide'];
    $old = $st;
    $y = (int)date('Y'); $sy = (int)date('n') >= 7 ? $y : $y - 1; $from = $sy . '-07-01'; $to = ($sy + 1) . '-06-30';
    $pv = [];
    foreach (q('SELECT code, ag_date, pv_date, gc_ttc FROM pv_votes WHERE ag_date BETWEEN ? AND ? ORDER BY ag_date', [$from, $to])->fetchAll() as $r) if ($r['gc_ttc'] !== null) $pv[$r['code']] = $r;
    $ag = json_decode((string)q("SELECT v FROM kv WHERE k='ag_planning'")->fetchColumn(), true) ?: [];
    $io = []; foreach ($ag['rows'] ?? [] as $r) $io[str_pad(preg_replace('/\D/', '', (string)($r['code'] ?? '')), 5, '0', STR_PAD_LEFT)] = $r;
    $n = 0; $filled = [];
    foreach ($st['bld'] as &$b) {
        $code = str_pad(preg_replace('/\D/', '', (string)($b['num'] ?? '')), 5, '0', STR_PAD_LEFT); if ($code === '00000') continue;
        $set = function (string $f, $v) use (&$b, &$n, &$filled, $code) { if ($v === null || $v === '' || trim((string)($b[$f] ?? '')) !== '' && (float)($b[$f] ?? 0) != 0) return; $b[$f] = $v; $n++; $filled[] = $code . ':' . $f; };
        $p = $pv[$code] ?? null;
        $last = $io[$code]['last'] ?? null; if (!is_string($last) || $last < $from || $last > $to) $last = null;
        $set('dateAG', $p ? $p['ag_date'] : $last);
        if ($p) { $set('datePV', $p['pv_date']); $set('h2627e', round((float)$p['gc_ttc'], 2)); }
        foreach ((array)($extra[$code] ?? []) as $f => $v) if (in_array($f, ['dateAG', 'datePV', 'h2627e', 'lots'], true)) $set($f, $v);
    }
    unset($b);
    if ($n) {
        q('INSERT INTO doc_state(slug,json,updated_at,updated_by) VALUES(?,?,?,NULL) ON CONFLICT(slug) DO UPDATE SET json=excluded.json, updated_at=excluded.updated_at, updated_by=NULL',
          ['honoraires', json_encode($st, JSON_UNESCAPED_UNICODE), time()]);
        hono_log_diff($old, $st, null, 'auto');
    }
    return ['ok' => true, 'filled' => $n, 'cells' => $filled, 'window' => [$from, $to]];
}
