<?php
declare(strict_types=1);

function page_guides(array $u, ?string $slug = null): never {
    $ed = guide_editor($u);
    if ($slug === null) {
        $all = array_values(array_filter(q('SELECT * FROM guides ORDER BY cat, pos, title')->fetchAll(), fn($g) => guide_visible($u, $g['audience'] ?? 'tous')));
        $rank = fn($c) => array_search($c, ['Chemini · Démarrer', 'Chemini · Perso', 'Chemini · Équipe', 'Chemini · Direction', 'Chemini · Président', 'Chemini · Famille', 'ImmOpen'], true);
        usort($all, fn($a, $b) => [$rank($a['cat']) === false ? 99 : $rank($a['cat']), $a['pos'], $a['title']] <=> [$rank($b['cat']) === false ? 99 : $rank($b['cat']), $b['pos'], $b['title']]);
        render('guides', ['title' => 'Guides', 'nav' => 'guides', 'u' => $u, 'ed' => $ed, 'all' => $all]);
    }
    $new = $slug === 'nouveau';
    $g = $new ? null : q('SELECT g.*, u.name AS by_name FROM guides g LEFT JOIN users u ON u.id=g.updated_by WHERE g.slug=?', [$slug])->fetch();
    if ($g && !guide_visible($u, $g['audience'] ?? 'tous')) $g = false;
    if (!$new && !$g) { http_response_code(404); render('notfound', ['title' => 'Introuvable', 'message' => 'Ce guide n\'existe pas.']); }
    if ($new && !$ed) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.']); }
    $cats = q('SELECT DISTINCT cat FROM guides ORDER BY cat')->fetchAll(PDO::FETCH_COLUMN);
    render('guide', ['title' => $g['title'] ?? 'Nouveau guide', 'nav' => 'guides', 'u' => $u, 'ed' => $ed, 'g' => $g, 'edit' => $new || ($ed && isset($_GET['modifier'])), 'cats' => $cats]);
}
