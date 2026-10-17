<?php
declare(strict_types=1);

/* Journal des modifications du tableau des honoraires (onglet « Modifications » et page d'historique). */

const HONO_FIELDS = [
    'port' => 'Portefeuille', 'gest' => 'Gestionnaire', 'num' => 'N°', 'imm' => 'Immeuble', 'lots' => 'Lots', 'perdus' => 'Lots perdus', 'archive' => 'Archive',
    'plage' => 'Plage horaire AG', 'fin' => 'Heure fin AG', 'cs1' => 'CS 1h', 'cs2' => 'CS 2h', 'visite' => 'Visites 1h', 'coutAG' => 'Coût AG supp. TTC',
    'dateAG' => 'AGO tenue le', 'datePV' => 'Remise PV', 'h2526' => 'Hon. 25-26 voté', 'h2627v' => 'Hon. 26-27 à voter', 'h2627e' => 'Hon. 26-27 voté',
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
/** Mise à jour quotidienne à partir de 8 h : relevé ImmOpen et cellules vides complétées d'après les PV (une fois par jour). */
function hono_daily(): void {
    static $done = false; if ($done || (int)date('G') < 8) return; $done = true;
    $today = date('Y-m-d');
    if (q("SELECT v FROM kv WHERE k='hono_daily'")->fetchColumn() === $today) return;
    q("INSERT INTO kv(k,v,updated_at) VALUES('hono_daily',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [$today, time()]);
    try { if (function_exists('immopen_sync')) immopen_sync(); hono_autofill(); } catch (Throwable $e) { error_log('[chemini] hono_daily ' . $e->getMessage()); }
}

function hono_changes(int $since = 0, int $limit = 3000): array {
    hono_backfill(); hono_split_ags(); hono_daily();
    return array_map(fn($r) => ['at' => (int)$r['at'], 'user' => $r['name'] ?: ($r['source'] === 'auto' ? 'Mise à jour automatique' : ($r['source'] === 'evolution' ? 'Évolution' : '—')), 'source' => $r['source'], 'tab' => $r['tab'],
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
    // « AGO tenue le » : uniquement l'AG ordinaire (celle qui vote le contrat de syndic). Les AG spéciales n'y figurent pas (colonne « AG spéciale »).
    foreach (q('SELECT code, ag_date, pv_date, gc_ttc FROM pv_votes WHERE ag_date BETWEEN ? AND ? AND ' . PV_AGO_SQL . ' ORDER BY ag_date', [$from, $to])->fetchAll() as $r) if ($r['gc_ttc'] !== null) $pv[$r['code']] = $r;
    $n = 0; $filled = [];
    foreach ($st['bld'] as &$b) {
        $code = str_pad(preg_replace('/\D/', '', (string)($b['num'] ?? '')), 5, '0', STR_PAD_LEFT); if ($code === '00000') continue;
        $set = function (string $f, $v) use (&$b, &$n, &$filled, $code) { if ($v === null || $v === '' || trim((string)($b[$f] ?? '')) !== '' && (float)($b[$f] ?? 0) != 0) return; $b[$f] = $v; $n++; $filled[] = $code . ':' . $f; };
        $p = $pv[$code] ?? null;
        $set('dateAG', $p ? $p['ag_date'] : null); // plus de repli sur la dernière AG ImmOpen : son type (ordinaire ou spéciale) n'y figure pas
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

/** Condition SQL : AG ordinaire (type relevé, ou à défaut contrat de syndic voté dans le PV). */
const PV_AGO_SQL = "(ag_type='AGO' OR (ag_type IS NULL AND gc_ttc IS NOT NULL))";

/**
 * Reprise unique (17/10/2026) : « AG tenue le » ne doit contenir que des AG ordinaires. Les dates venues de la dernière AG ImmOpen
 * ou d'un PV d'AG spéciale, sans honoraires de gestion votés, sont retirées de la colonne (elles restent visibles dans « AG spéciale »).
 */
function hono_split_ags(): void {
    if (q("SELECT 1 FROM kv WHERE k='hono_ags_split_v1'")->fetchColumn()) return;
    q("INSERT INTO kv(k,v,updated_at) VALUES('hono_ags_split_v1','1',?) ON CONFLICT(k) DO UPDATE SET v='1'", [time()]);
    $st = hono_state(); if (!$st || empty($st['bld'])) return;
    $old = $st; $ago = []; $other = [];
    foreach (q('SELECT code, ag_date, ' . PV_AGO_SQL . ' AS ago FROM pv_votes')->fetchAll() as $r) { if ($r['ago']) $ago[$r['code'] . '|' . $r['ag_date']] = 1; else $other[$r['code'] . '|' . $r['ag_date']] = 1; }
    $ag = json_decode((string)q("SELECT v FROM kv WHERE k='ag_planning'")->fetchColumn(), true) ?: [];
    $io = []; foreach ($ag['rows'] ?? [] as $r) $io[str_pad(preg_replace('/\D/', '', (string)($r['code'] ?? '')), 5, '0', STR_PAD_LEFT)] = (string)($r['last'] ?? '');
    $n = 0;
    foreach ($st['bld'] as &$b) {
        $code = str_pad(preg_replace('/\D/', '', (string)($b['num'] ?? '')), 5, '0', STR_PAD_LEFT); $d = trim((string)($b['dateAG'] ?? ''));
        if ($code === '00000' || $d === '' || isset($ago[$code . '|' . $d]) || hono_num($b['h2627e'] ?? 0) != 0) continue; // AG ordinaire avérée : on garde
        if (($io[$code] ?? '') !== $d && !isset($other[$code . '|' . $d])) continue; // date saisie à la main, d'origine inconnue : on garde
        $b['dateAG'] = ''; $n++;
    }
    unset($b);
    if ($n) {
        q('INSERT INTO doc_state(slug,json,updated_at,updated_by) VALUES(?,?,?,NULL) ON CONFLICT(slug) DO UPDATE SET json=excluded.json, updated_at=excluded.updated_at, updated_by=NULL',
          ['honoraires', json_encode($st, JSON_UNESCAPED_UNICODE), time()]);
        hono_log_diff($old, $st, null, 'auto');
    }
}

/** Évolutions de l'outil Honoraires (affichées dans le journal, onglet « Évolution de l'outil »). */
const HONO_EVOLUTIONS = [
    ['2026-10-16 01:09', 'Section Honoraires partagée avec l’équipe (lecture seule sauf Mehdi Chemini)'],
    ['2026-10-16 02:17', 'Honoraires aux couleurs du site, onglet « Optimisation & stratégie »'],
    ['2026-10-16 02:41', 'Audit mensuel affiché en tête d’« Optimisation & stratégie », alerte sur le tableau de bord'],
    ['2026-10-16 15:31', 'Bloc « Honoraires annexes » (exercice 1er avril–31 mars) et choix des colonnes mémorisé'],
    ['2026-10-16 15:43', 'Exercices du 1er avril au 31 mars, ordre des colonnes réglable'],
    ['2026-10-16 16:02', 'Honoraires annexes en pleine page avec retour'],
    ['2026-10-16 16:32', 'Contrôle croisé avec ImmOpen, suppression avec confirmation et archivage, honoraires travaux votés d’après les PV'],
    ['2026-10-16 18:05', 'Colonnes HTV avec bouton i (détail des votes en AG)'],
    ['2026-10-16 18:15', 'HTV : honoraires travaux votés en AG uniquement, exercice des AG du 1er juillet au 30 juin'],
    ['2026-10-16 19:23', '« AG tenue le » et « Remise PV » complétés d’après les PV d’AG'],
    ['2026-10-17 00:07', 'Version mobile : onglets regroupés dans un menu déroulant'],
    ['2026-10-17 02:02', 'Fenêtres de détail à la charte du site'],
    ['2026-10-17 02:06', 'Dates en champ texte JJ/MM/AAAA, complétées aussi d’après le planning ImmOpen'],
    ['2026-10-17 02:17', 'Onglet « Modifications », tableau de décision dans « Optimisation & stratégie », mise à jour automatique des cellules vides'],
    ['2026-10-17 10:08', '« AGO tenue le » limité aux AG ordinaires (vote du contrat de syndic), AG spéciales dans une colonne distincte'],
];

/** Reprise unique de l'historique antérieur au journal : PV relevés, archives, saisies depuis le fichier d'origine, évolutions de l'outil. */
function hono_backfill(): void {
    if (q("SELECT 1 FROM kv WHERE k='hono_backfill_v1'")->fetchColumn()) return;
    q("INSERT INTO kv(k,v,updated_at) VALUES('hono_backfill_v1','1',?) ON CONFLICT(k) DO UPDATE SET v='1'", [time()]);
    $ins = fn($at, $uid, $src, $tab, $ref, $field, $old, $new) => q('INSERT INTO hono_changes(at,user_id,source,tab,ref,field,old,new) VALUES(?,?,?,?,?,?,?,?)', [$at, $uid, $src, $tab, mb_substr((string)$ref, 0, 200), $field, mb_substr((string)$old, 0, 300), mb_substr((string)$new, 0, 300)]);
    $st = hono_state(); $names = [];
    foreach ((array)($st['bld'] ?? []) as $b) $names[str_pad(preg_replace('/\D/', '', (string)($b['num'] ?? '')), 5, '0', STR_PAD_LEFT)] = ($b['num'] ?? '') . ' · ' . ($b['imm'] ?? '');
    foreach (q('SELECT * FROM pv_votes ORDER BY created_at')->fetchAll() as $r) {
        $det = json_decode((string)$r['detail'], true) ?: [];
        $ins((int)$r['created_at'], null, 'auto', 'Immeubles', $names[$r['code']] ?? $r['code'], 'PV d’AG relevé', '',
            'AG du ' . hono_v($r['ag_date']) . ($r['pv_date'] ? ', PV remis le ' . hono_v($r['pv_date']) : '') . ' · HTV ' . number_format((float)$r['travaux_ttc'], 2, ',', ' ') . ' €' . ($r['gc_ttc'] !== null ? ' · gestion votée ' . number_format((float)$r['gc_ttc'], 2, ',', ' ') . ' €' : '') . (count($det) ? ' (' . count($det) . ' résolution' . (count($det) > 1 ? 's' : '') . ')' : ''));
    }
    foreach (q('SELECT * FROM hono_archive')->fetchAll() as $a) {
        $ins((int)$a['created_at'], (int)$a['created_by'] ?: null, 'manuel', 'Immeubles', trim(($a['num'] ? $a['num'] . ' · ' : '') . $a['imm']), 'Ligne archivée', '', 'archives');
        if ($a['restored_at']) $ins((int)$a['restored_at'], null, 'manuel', 'Immeubles', trim(($a['num'] ? $a['num'] . ' · ' : '') . $a['imm']), 'Ligne restaurée', 'archives', 'tableau');
    }
    // saisies faites depuis le fichier d'origine
    $html = (string)q('SELECT html FROM docs WHERE slug=?', ['honoraires'])->fetchColumn();
    $sv = q('SELECT updated_at, updated_by FROM doc_state WHERE slug=?', ['honoraires'])->fetch();
    if ($sv && preg_match('#<script id="seed" type="application/json">(.*?)</script>#s', $html, $m) && ($seed = json_decode($m[1], true))) {
        $before = (int)q('SELECT COUNT(*) FROM hono_changes')->fetchColumn();
        $cur = $st; hono_log_diff($seed, $cur, $sv['updated_by'] ? (int)$sv['updated_by'] : null, 'manuel');
        q("UPDATE hono_changes SET at=? WHERE id > (SELECT COALESCE(MAX(id),0) FROM hono_changes) - ? AND at>=?", [(int)$sv['updated_at'], (int)q('SELECT COUNT(*) FROM hono_changes')->fetchColumn() - $before, time() - 5]);
    }
    foreach (HONO_EVOLUTIONS as $e) $ins(strtotime($e[0]), null, 'evolution', 'Évolution de l’outil', '', $e[1], '', '');
}
