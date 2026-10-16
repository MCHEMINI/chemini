<?php
declare(strict_types=1);

function page_compta(array $u): never {
    if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'La comptabilité est réservée à la Direction.'], false); }
    immopen_sync();
    $run = q('SELECT * FROM compta_runs ORDER BY created_at DESC LIMIT 1')->fetch() ?: null;
    $d = $run ? (json_decode($run['data'], true) ?: []) : [];
    $done = array_flip(q('SELECT k FROM compta_done')->fetchAll(PDO::FETCH_COLUMN));
    $comptables = q("SELECT compta AS name, COUNT(*) AS n FROM buildings WHERE kind='syndic' AND compta IS NOT NULL GROUP BY compta ORDER BY compta")->fetchAll();
    $emails = array_column(q('SELECT name, email FROM staff')->fetchAll(), 'email', 'name');
    render('compta', ['title' => 'Comptabilité', 'nav' => 'compta', 'u' => $u, 'wide' => true, 'run' => $run, 'd' => $d, 'done' => $done, 'comptables' => $comptables, 'emails' => $emails]);
}
