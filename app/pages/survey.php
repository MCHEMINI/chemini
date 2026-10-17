<?php
declare(strict_types=1);

function page_survey(array $u): never {
    $row = q('SELECT * FROM survey_answers WHERE user_id=?', [(int)$u['id']])->fetch() ?: null;
    render('survey', ['title' => 'Questionnaire de fonctionnement', 'nav' => 'space-gid', 'u' => $u, 'A' => $row ? (json_decode($row['data'], true) ?: []) : [], 'row' => $row, 'isDir' => can_space($u, 'direction')]);
}

function page_survey_results(array $u): never {
    if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); }
    $all = survey_all(); $done = array_values(array_filter($all, fn($r) => $r['submitted_at']));
    $staff = (int)q('SELECT COUNT(*) FROM staff')->fetchColumn();
    $pending = q("SELECT person FROM team_tasks WHERE title=? AND done_at IS NULL", [SURVEY_TASK])->fetchAll(PDO::FETCH_COLUMN);
    render('survey_results', ['title' => 'Enquête des équipes', 'nav' => 'survey', 'u' => $u, 'wide' => true, 'all' => $all, 'done' => $done, 'staff' => $staff, 'pending' => $pending, 'tools' => survey_tools($done), 'people' => team_people()]);
}
