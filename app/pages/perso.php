<?php
declare(strict_types=1);

function page_perso(array $u): never {
    $uid = (int)$u['id'];
    render('perso', [
        'title' => 'Mon espace', 'nav' => 'perso', 'u' => $u,
        'tasks' => q('SELECT * FROM tasks WHERE user_id=? AND space IS NULL ORDER BY done ASC, created_at DESC', [$uid])->fetchAll(),
        'ia_todo' => q("SELECT * FROM consignes WHERE user_id=? AND status='todo' ORDER BY CASE priority WHEN 'haute' THEN 0 WHEN 'normale' THEN 1 ELSE 2 END, created_at", [$uid])->fetchAll(),
        'ia_doing' => q("SELECT * FROM consignes WHERE user_id=? AND status='doing' ORDER BY updated_at DESC", [$uid])->fetchAll(),
        'ia_done' => q("SELECT * FROM consignes WHERE user_id=? AND status='done' ORDER BY COALESCE(done_at, updated_at) DESC LIMIT 10", [$uid])->fetchAll(),
        'ia_done_n' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done'", [$uid])->fetchColumn(),
        'note' => q('SELECT * FROM notes WHERE user_id=?', [$uid])->fetch() ?: null,
        'mails' => q('SELECT * FROM mail_items WHERE user_id=? AND (done=0 OR done_at > ?) ORDER BY done ASC, CASE priority WHEN \'crit\' THEN 0 WHEN \'warn\' THEN 1 ELSE 2 END, received_at DESC LIMIT 40', [$uid, time() - 7 * 86400])->fetchAll(),
        'mine' => q("SELECT * FROM items WHERE owner_id=? AND status='open' ORDER BY CASE severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END", [$uid])->fetchAll(),
    ]);
}
