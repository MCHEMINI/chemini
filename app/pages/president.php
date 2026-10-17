<?php
declare(strict_types=1);

function page_president(array $u, ?string $month = null): never {
    $ed = pw_editor($u);
    $all = q('SELECT * FROM president_words ORDER BY month DESC')->fetchAll();
    $list = array_values(array_filter($all, fn($w) => $ed || pw_published($w)));
    if ($month === null) $w = $ed ? (pw_get(pw_target_month()) ?: ($list[0] ?? null)) : ($list[0] ?? null);
    else { $w = pw_get($month); if ($w && !$ed && !pw_published($w)) $w = null; }
    if ($month !== null && !$w) { http_response_code(404); render('notfound', ['title' => 'Introuvable', 'message' => 'Ce mot du Président n\'est pas publié.']); }
    render('president', ['title' => 'Mot du Président', 'nav' => 'mot', 'u' => $u, 'ed' => $ed, 'w' => $w, 'list' => $list,
        'edit' => $ed && isset($_GET['modifier']), 'target' => pw_target_month()]);
}

/** Lien non répertorié : le mot s'affiche en lecture seule, sans compte ; introuvable dès que le lien est désactivé. */
function page_president_public(string $token): never {
    $w = q('SELECT * FROM president_words WHERE share_token=?', [$token])->fetch();
    header('X-Robots-Tag: noindex, nofollow, noarchive'); header('Referrer-Policy: no-referrer');
    if (!$w) { http_response_code(404); render('notfound', ['title' => 'Lien invalide', 'message' => 'Ce lien n\'existe pas ou a été désactivé.'], false); }
    render('president_public', ['title' => 'Mot du Président · ' . pw_month_label($w['month']), 'w' => $w], false);
}
