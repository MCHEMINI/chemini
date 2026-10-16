<?php
declare(strict_types=1);
/* Historique des consignes faites : chacun ne voit que les siennes. */
function page_assistant_history(array $u): never {
    $uid = (int)$u['id'];
    $per = 20;
    $total = (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done'", [$uid])->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page = min($pages, max(1, (int)($_GET['p'] ?? 1)));
    $rows = q("SELECT * FROM consignes WHERE user_id=? AND status='done' ORDER BY COALESCE(done_at, updated_at) DESC LIMIT $per OFFSET " . (($page - 1) * $per), [$uid])->fetchAll();
    render('assistant', ['title' => 'Tâches IA', 'nav' => 'ia', 'u' => $u, 'rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages]);
}

/* Tableau des Tâches IA : En attente / En cours / Fait (10 dernières). */
function page_assistant(array $u): never {
    $uid = (int)$u['id'];
    render('assistant_board', [
        'title' => 'Tâches IA', 'nav' => 'ia', 'u' => $u,
        'ia_todo' => q("SELECT * FROM consignes WHERE user_id=? AND status='todo' ORDER BY CASE priority WHEN 'haute' THEN 0 WHEN 'normale' THEN 1 ELSE 2 END, created_at", [$uid])->fetchAll(),
        'ia_doing' => q("SELECT * FROM consignes WHERE user_id=? AND status='doing' ORDER BY updated_at DESC", [$uid])->fetchAll(),
        'ia_done' => q("SELECT * FROM consignes WHERE user_id=? AND status='done' ORDER BY COALESCE(done_at, updated_at) DESC LIMIT 10", [$uid])->fetchAll(),
        'ia_done_n' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done'", [$uid])->fetchColumn(),
    ]);
}
