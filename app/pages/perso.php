<?php
declare(strict_types=1);

function page_perso(array $u): never {
    $uid = (int)$u['id'];
    render('perso', [
        'title' => 'Mon espace', 'nav' => 'perso', 'u' => $u,
        'tasks' => q('SELECT * FROM tasks WHERE user_id=? AND space IS NULL ORDER BY done ASC, created_at DESC', [$uid])->fetchAll(),
        'note' => q('SELECT * FROM notes WHERE user_id=?', [$uid])->fetch() ?: null,
        'mine' => q("SELECT * FROM items WHERE owner_id=? AND status='open' ORDER BY CASE severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END", [$uid])->fetchAll(),
    ]);
}
