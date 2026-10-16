<?php
declare(strict_types=1);

function page_immeubles(array $u): never {
    $buildings = q("SELECT * FROM buildings ORDER BY kind DESC, code")->fetchAll();
    $staff = q('SELECT * FROM staff ORDER BY CASE role WHEN \'Gestionnaire\' THEN 0 WHEN \'Assistant(e)\' THEN 1 ELSE 2 END, name')->fetchAll();
    $count = [];
    foreach ($buildings as $b) foreach (['gest', 'compta', 'assist'] as $k) if ($b[$k]) $count[$b[$k]] = ($count[$b[$k]] ?? 0) + 1;
    render('immeubles', [
        'title' => 'Immeubles', 'nav' => 'immeubles', 'u' => $u, 'wide' => true,
        'buildings' => $buildings, 'staff' => $staff, 'count' => $count,
        'canEdit' => can_space($u, 'direction'),
        'fin' => can_space($u, 'direction') ? array_filter(array_map(fn($b) => ($f = bld_finance($b['code'])) ? ['c' => $f['color'], 'm' => $f['margin'], 'l' => $f['label']] : null, array_column($buildings, null, 'code'))) : [],
    ]);
}

function page_building(array $u, string $code): never {
    $b = q('SELECT * FROM buildings WHERE code=?', [$code])->fetch();
    if (!$b) { http_response_code(404); render('notfound', ['title' => 'Immeuble introuvable'], false); }
    $staff = [];
    foreach (q('SELECT * FROM staff')->fetchAll() as $s) $staff[$s['name']] = $s;
    $events = q('SELECT e.*, u.name AS by_name FROM building_events e LEFT JOIN users u ON u.id=e.user_id WHERE code=? ORDER BY at DESC LIMIT 60', [$code])->fetchAll();
    $isDir = can_space($u, 'direction');
    render('building', [
        'title' => $b['label'], 'nav' => 'immeubles', 'u' => $u, 'wide' => true,
        'b' => $b, 'info' => bld_info($code), 'staff' => $staff, 'events' => $events,
        'fin' => $isDir ? bld_finance($code) : null, 'isDir' => $isDir,
        'immopenAt' => (string)q("SELECT v FROM kv WHERE k='immopen_at'")->fetchColumn(),
    ]);
}
