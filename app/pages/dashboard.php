<?php
declare(strict_types=1);

/** Tableau de bord GID : uniquement des informations partageables avec toute la société. */
function page_dashboard(array $u): never {
    $veille = q('SELECT * FROM veille ORDER BY week DESC, important DESC, COALESCE(published_at, created_at) DESC LIMIT 8')->fetchAll();
    $week = $veille ? $veille[0]['week'] : null;
    render('dashboard', [
        'title' => 'Tableau de bord', 'nav' => 'dash', 'u' => $u, 'isDir' => can_space($u, 'direction'),
        'veille' => $veille, 'legal' => $veille ? [] : feeds_merged(LEGAL_FEEDS, 4), 'news' => feeds_merged(NEWS_FEEDS, 6),
        'gidNote' => q("SELECT body FROM space_notes WHERE space='gid'")->fetch() ?: null,
        'gidTasks' => q("SELECT title FROM tasks WHERE space='gid' AND done=0 ORDER BY created_at DESC LIMIT 5")->fetchAll(),
        'ops' => ag_ops(),
        'stats' => [
            'syndic' => (int)q("SELECT COUNT(*) FROM buildings WHERE kind='syndic'")->fetchColumn(),
            'staff' => (int)q('SELECT COUNT(*) FROM staff')->fetchColumn(),
            'veilleImp' => $week ? (int)q('SELECT COUNT(*) FROM veille WHERE week=? AND important=1', [$week])->fetchColumn() : 0,
            'tasks' => (int)q("SELECT COUNT(*) FROM tasks WHERE space='gid' AND done=0")->fetchColumn(),
        ],
    ]);
}

/** Données du tableau de bord Direction (honoraires, comptabilité, points, échéances). */
function direction_board(array $u): array {
    $uid = (int)$u['id'];
    $audit = null;
    if (($a = audit_latest('honoraires')) && $a['created_at'] > time() - 40 * 86400
        && !q('SELECT 1 FROM audit_reads WHERE user_id=? AND audit_id=?', [$uid, $a['id']])->fetchColumn()) $audit = $a;
    $d = hono_data(); $act = 0; $ttc = 0; $h25 = 0; $ag = 0; $agV = 0; $agE = 0; $c = ['green' => 0, 'orange' => 0, 'red' => 0];
    foreach ($d['bld'] ?? [] as $b) {
        if (hono_lost($b)) continue;
        $act++; $ttc += hono_num($b['h2627v']); $h25 += hono_num($b['h2526']);
        if (!empty($b['dateAG'])) { $ag++; $agV += hono_num($b['h2627v']); $agE += hono_num($b['h2627e']); }
        if (($f = bld_finance((string)str_pad((string)$b['num'], 5, '0', STR_PAD_LEFT))) && isset($c[$f['color']])) $c[$f['color']]++;
    }
    $run = q('SELECT data FROM compta_runs ORDER BY created_at DESC LIMIT 1')->fetchColumn();
    $compta = null;
    if ($run) {
        $cd = json_decode((string)$run, true) ?: []; $done = array_flip(q('SELECT k FROM compta_done')->fetchAll(PDO::FETCH_COLUMN));
        $compta = ['anom' => count(array_filter($cd['anomalies'] ?? [], fn($x) => !isset($done[$x['k']]))), 'fact' => count(array_filter($cd['factures'] ?? [], fn($x) => !isset($done[$x['k']])))];
    }
    $fmt = fn($v) => number_format($v / 1000, 0, ',', ' ') . ' k€';
    $today = date('Y-m-d');
    $items = q("SELECT i.*, u.name AS owner_name, u.initials AS owner_initials, u.id AS oid FROM items i LEFT JOIN users u ON u.id=i.owner_id
               WHERE i.status='open' ORDER BY CASE i.severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END, i.created_at DESC LIMIT 12")->fetchAll();
    return [
        'audit' => $audit, 'compta' => $compta, 'items' => $items, 'users' => users_all(),
        'deadlines' => q('SELECT * FROM deadlines WHERE done=0 AND due_date>=? ORDER BY due_date ASC LIMIT 8', [$today])->fetchAll(),
        'overdue' => q('SELECT * FROM deadlines WHERE done=0 AND due_date<? ORDER BY due_date ASC', [$today])->fetchAll(),
        'kpi' => ['ttc' => $fmt($ttc), 'evol' => $h25 ? round(($ttc / $h25 - 1) * 100, 1) : 0, 'ag' => $ag . ' / ' . $act,
                  'agVote' => $ag ? number_format($agE, 0, ',', ' ') . ' € votés sur ' . number_format($agV, 0, ',', ' ') . ' €' : 'saison à venir',
                  'red' => $c['red'], 'orange' => $c['orange']],
    ];
}

/** Planning des AG ImmOpen (lignes normalisées, code sur 5 chiffres). */
function ag_rows(): array {
    if (function_exists('immopen_sync')) immopen_sync();
    $raw = json_decode((string)q("SELECT v FROM kv WHERE k='ag_planning'")->fetchColumn(), true) ?: [];
    $out = [];
    foreach ($raw['rows'] ?? [] as $r) { $r['code'] = str_pad(preg_replace('/\D/', '', (string)($r['code'] ?? '')), 5, '0', STR_PAD_LEFT); $out[] = $r; }
    return ['date' => $raw['date'] ?? null, 'rows' => $out];
}

/** Indicateurs opérationnels des AG (partageables avec l'équipe : aucune donnée financière). */
function ag_ops(): array {
    $today = date('Y-m-d'); $in14 = date('Y-m-d', strtotime('+14 days')); $in30 = date('Y-m-d', strtotime('+30 days')); $in60 = date('Y-m-d', strtotime('+60 days'));
    $p = ag_rows(); $up = []; $convoc = []; $mandat = [];
    foreach ($p['rows'] as $r) {
        $d = (string)($r['ag_date'] ?? '');
        if ($d >= $today && $d <= $in30) $up[] = $r;
        if (!empty($r['limite_convoc']) && $r['limite_convoc'] < $today && empty($r['convoc']) && $d >= $today) $convoc[] = $r;
        if (!empty($r['fin_mandat']) && $r['fin_mandat'] <= $in60 && !($d >= $today && $d <= (string)$r['fin_mandat'])) $mandat[] = $r;
    }
    usort($up, fn($a, $b) => strcmp((string)$a['ag_date'], (string)$b['ag_date']));
    $d = hono_data(); $nopv = [];
    foreach ($d['bld'] ?? [] as $b) if (!empty($b['dateAG']) && empty($b['datePV']) && strtotime((string)$b['dateAG']) < strtotime('-21 days') && strtotime((string)$b['dateAG']) > strtotime('-1 year')) $nopv[] = $b;
    return ['date' => $p['date'], 'upcoming' => $up, 'week' => count(array_filter($up, fn($r) => $r['ag_date'] <= $in14)), 'convoc' => $convoc, 'mandat' => $mandat, 'nopv' => $nopv];
}

/** Vue d'ensemble des sections pour le tableau de bord Direction. */
function dir_overview(array $u): array {
    $uid = (int)$u['id']; $ops = ag_ops(); $d = hono_data();
    $act = 0; $ttc = 0; $h25 = 0; $held = 0; $under = 0; $underGap = 0; $lost = 0; $lostH = 0; $col = ['green' => 0, 'orange' => 0, 'red' => 0]; $gapRed = 0;
    foreach ($d['bld'] ?? [] as $b) {
        if (hono_lost($b)) { $lost++; $lostH += hono_num($b['h2526']); continue; }
        $act++; $ttc += hono_num($b['h2627v']); $h25 += hono_num($b['h2526']);
        if (!empty($b['dateAG'])) $held++;
        $e = hono_num($b['h2627e']); if ($e > 0 && $e < hono_num($b['h2627v']) - 1) { $under++; $underGap += $e - hono_num($b['h2627v']); }
    }
    require_once APP_DIR . '/pages/strategies.php';
    $sn = strat_snapshot(); $col = $sn['data']['tot']; $gapRed = $sn['data']['gapRed'];
    $an = annexes_data(); $anCur = null;
    if ($an && !empty($an['years'])) {
        $ys = array_keys($an['years']); sort($ys); $y = end($ys); $tot = 0;
        foreach (($an['years'][$y]['b'] ?? []) as $vals) foreach ((array)$vals as $v) $tot += (float)$v;
        $anCur = ['year' => $y, 'total' => $tot, 'end' => $an['years'][$y]['end'] ?? null];
    }
    $run = q('SELECT data, created_at FROM compta_runs ORDER BY created_at DESC LIMIT 1')->fetch();
    $compta = null;
    if ($run) {
        $cd = json_decode((string)$run['data'], true) ?: []; $done = array_flip(q('SELECT k FROM compta_done')->fetchAll(PDO::FETCH_COLUMN));
        $compta = ['anom' => count(array_filter($cd['anomalies'] ?? [], fn($x) => !isset($done[$x['k']]))), 'fact' => count(array_filter($cd['factures'] ?? [], fn($x) => !isset($done[$x['k']]))), 'at' => (int)$run['created_at']];
    }
    return [
        'hono' => ['ttc' => $ttc, 'evol' => $h25 ? ($ttc / $h25 - 1) * 100 : 0, 'act' => $act, 'held' => $held, 'under' => $under, 'underGap' => $underGap, 'lost' => $lost, 'lostH' => $lostH, 'annexes' => $anCur],
        'rent' => $col + ['gapRed' => $gapRed],
        'ops' => $ops, 'compta' => $compta,
        'changes' => array_slice(hono_changes(time() - 7 * 86400, 50), 0, 5),
        'mails' => (int)q('SELECT COUNT(*) FROM mail_items WHERE user_id=? AND done=0', [$uid])->fetchColumn(),
        'ia' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status<>'done'", [$uid])->fetchColumn(),
    ];
}
