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

/** Lien de partage public (lecture seule, sans compte), créé ou désactivé depuis la fiche du voyage. */
function page_trip_public(string $token): never {
    $t = strlen($token) >= 24 ? q('SELECT * FROM trips WHERE share_token=?', [$token])->fetch() : false;
    if (!$t) { http_response_code(404); render('notfound', ['title' => 'Lien invalide', 'message' => 'Ce lien de partage n\'existe pas ou a été désactivé.'], false); }
    $t['d'] = json_decode($t['data'], true) ?: [];
    header('X-Robots-Tag: noindex, nofollow');
    render('trip_public', ['title' => $t['destination'], 't' => $t, 'public' => true], false);
}
