<?php
declare(strict_types=1);

function page_trips(array $u): never {
    $spaces = user_spaces($u);
    $in = implode(',', array_fill(0, count($spaces), '?'));
    $trips = q("SELECT * FROM trips WHERE space IN ($in) ORDER BY updated_at DESC", $spaces)->fetchAll();
    foreach ($trips as &$t) $t['d'] = json_decode($t['data'], true) ?: [];
    render('trips', ['title' => 'Voyages', 'nav' => 'voyages', 'u' => $u, 'trips' => $trips]);
}

function page_trip(array $u, int $id): never {
    $t = q('SELECT * FROM trips WHERE id=?', [$id])->fetch();
    if (!$t || !can_space($u, $t['space'])) { http_response_code(404); render('notfound', ['title' => 'Voyage introuvable'], false); }
    $t['d'] = json_decode($t['data'], true) ?: [];
    render('trip', ['title' => $t['destination'], 'nav' => 'voyages', 'u' => $u, 't' => $t]);
}
