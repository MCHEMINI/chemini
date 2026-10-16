<?php
declare(strict_types=1);

function page_space(array $u, string $space): never {
    if (!isset(SPACES[$space]) || !can_space($u, $space)) {
        http_response_code(403);
        render('notfound', ['title' => 'Accès réservé', 'message' => 'Vous ne faites pas partie de cet espace.'], false);
    }
    $extra = [];
    if ($space === 'direction') { require_once APP_DIR . '/pages/dashboard.php'; $extra = direction_board($u); }
    if ($space === 'famille') {
        $trips = q("SELECT * FROM trips WHERE space='famille' ORDER BY updated_at DESC LIMIT 4")->fetchAll();
        foreach ($trips as &$t) $t['d'] = json_decode($t['data'], true) ?: [];
        $extra['trips'] = $trips;
    }
    render('space', $extra + [
        'title' => $space === 'direction' ? 'Tableau de bord Direction' : 'Espace ' . SPACES[$space], 'nav' => 'space-' . $space, 'u' => $u, 'space' => $space,
        'tasks' => q('SELECT t.*, u.initials, u.id AS uid FROM tasks t LEFT JOIN users u ON u.id=t.user_id WHERE t.space=? ORDER BY t.done ASC, t.created_at DESC', [$space])->fetchAll(),
        'note' => q('SELECT n.*, u.name AS by_name FROM space_notes n LEFT JOIN users u ON u.id=n.updated_by WHERE space=?', [$space])->fetch() ?: null,
        'members' => space_members($space),
        'docs' => q('SELECT slug, title, updated_at FROM docs WHERE space=?', [$space])->fetchAll(),
    ]);
}
