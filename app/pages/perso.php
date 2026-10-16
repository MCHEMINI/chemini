<?php
declare(strict_types=1);

function page_perso(array $u): never {
    $uid = (int)$u['id'];
    render('perso', [
        'title' => 'Mon espace', 'nav' => 'perso', 'u' => $u,
        'mails' => q('SELECT * FROM mail_items WHERE user_id=? AND (done=0 OR done_at > ?) ORDER BY done ASC, CASE priority WHEN \'crit\' THEN 0 WHEN \'warn\' THEN 1 ELSE 2 END, received_at DESC LIMIT 40', [$uid, time() - 7 * 86400])->fetchAll(),
        'mine' => q("SELECT * FROM items WHERE owner_id=? AND status='open' ORDER BY CASE severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END", [$uid])->fetchAll(),
    ]);
}

function page_tasks(array $u): never {
    render('perso_tasks', [
        'title' => 'Mes tâches', 'nav' => 'tasks', 'u' => $u,
        'tasks' => q('SELECT * FROM tasks WHERE user_id=? AND space IS NULL ORDER BY done ASC, created_at DESC', [(int)$u['id']])->fetchAll(),
    ]);
}

function page_notes(array $u): never {
    render('perso_notes', [
        'title' => 'Bloc-notes', 'nav' => 'notes', 'u' => $u,
        'note' => q('SELECT * FROM notes WHERE user_id=?', [(int)$u['id']])->fetch() ?: null,
    ]);
}
