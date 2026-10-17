<?php
declare(strict_types=1);

function team_load(array $u, ?string $person = null): array {
    [$w, $p] = team_where($u);
    if ($person !== null) { $w .= ' AND t.person=?'; $p[] = $person; }
    return q("SELECT t.*, uc.name AS by_name, ud.name AS done_name FROM team_tasks t LEFT JOIN users uc ON uc.id=t.created_by LEFT JOIN users ud ON ud.id=t.done_by
              WHERE $w ORDER BY t.done_at IS NOT NULL, CASE WHEN t.due IS NULL THEN 1 ELSE 0 END, t.due, t.created_at DESC", $p)->fetchAll();
}

function page_team(array $u): never {
    $people = team_people(); $tasks = team_load($u); $today = date('Y-m-d');
    $stat = [];
    foreach ($tasks as $t) {
        $s = &$stat[$t['person']]; $s = ($s ?? []) + ['open' => 0, 'late' => 0, 'today' => 0, 'done' => 0];
        if ($t['done_at']) { $s['done']++; continue; }
        $s['open']++; if ($t['due'] && $t['due'] < $today) $s['late']++; elseif ($t['due'] === $today) $s['today']++;
        unset($s);
    }
    $groups = [];
    foreach ($people as $k => $p) $groups[TEAM_SERVICES[$p['role']] ?? 'Autres'][] = $p;
    $order = array_flip(array_values(TEAM_SERVICES));
    uksort($groups, fn($a, $b) => ($order[$a] ?? 9) <=> ($order[$b] ?? 9));
    $todayList = array_values(array_filter($tasks, fn($t) => !$t['done_at'] && $t['due'] && $t['due'] <= $today));
    render('team', ['title' => 'Collaborateurs', 'nav' => 'team', 'u' => $u, 'groups' => $groups, 'stat' => $stat, 'todayList' => $todayList, 'people' => $people,
        'me' => team_me($u), 'isDir' => can_space($u, 'direction')]);
}

function page_team_person(array $u, string $key): never {
    $people = team_people();
    if (!isset($people[$key])) { http_response_code(404); render('notfound', ['title' => 'Introuvable', 'message' => 'Collaborateur introuvable.']); }
    $m0 = strtotime(date('Y-m-01'));
    $tasks = team_load($u, $key);
    $open = array_values(array_filter($tasks, fn($t) => !$t['done_at']));
    $done = array_values(array_filter($tasks, fn($t) => $t['done_at'] && $t['title'] !== SURVEY_TASK));
    usort($done, fn($a, $b) => $b['done_at'] <=> $a['done_at']);
    render('team_person', ['title' => $people[$key]['name'], 'nav' => 'team', 'u' => $u, 'p' => $people[$key], 'open' => $open, 'done' => $done,
        'me' => team_me($u), 'isDir' => can_space($u, 'direction')]);
}
