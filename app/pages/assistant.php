<?php
declare(strict_types=1);
/* Historique des consignes faites : chacun ne voit que les siennes. */
function page_assistant(array $u): never {
    $uid = (int)$u['id'];
    $per = 20;
    $total = (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done'", [$uid])->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page = min($pages, max(1, (int)($_GET['p'] ?? 1)));
    $rows = q("SELECT * FROM consignes WHERE user_id=? AND status='done' ORDER BY COALESCE(done_at, updated_at) DESC LIMIT $per OFFSET " . (($page - 1) * $per), [$uid])->fetchAll();
    render('assistant', ['title' => 'Tâches IA', 'nav' => 'perso', 'u' => $u, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages]);
}
