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
    require_once APP_DIR . '/pages/team.php';
    $me = team_me($u);
    $all = $me ? team_load($u, $me) : [];
    $open = array_values(array_filter($all, fn($t) => !$t['done_at'])); $done = array_values(array_filter($all, fn($t) => $t['done_at'] && $t['title'] !== SURVEY_TASK));
    usort($done, fn($a, $b) => $b['done_at'] <=> $a['done_at']);
    $spaces = array_values(array_filter(array_keys(SPACES), fn($s) => can_space($u, $s)));
    $shared = $spaces ? q('SELECT t.*, u.name AS by_name FROM tasks t LEFT JOIN users u ON u.id=t.user_id WHERE t.space IN (' . implode(',', array_fill(0, count($spaces), '?')) . ') ORDER BY t.done, t.created_at DESC LIMIT 200', $spaces)->fetchAll() : [];
    render('perso_tasks', ['title' => 'Mes tâches', 'nav' => 'tasks', 'u' => $u, 'me' => $me, 'open' => $open, 'done' => $done, 'isDir' => can_space($u, 'direction'), 'spaces' => $spaces, 'shared' => $shared]);
}

function page_notes(array $u): never {
    render('perso_notes', [
        'title' => 'Bloc-notes', 'nav' => 'notes', 'u' => $u,
        'note' => q('SELECT * FROM notes WHERE user_id=?', [(int)$u['id']])->fetch() ?: null,
    ]);
}
