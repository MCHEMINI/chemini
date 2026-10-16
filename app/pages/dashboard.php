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
