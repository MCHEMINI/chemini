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
    ]);
}
