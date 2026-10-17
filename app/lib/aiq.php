<?php
declare(strict_types=1);

/* Questions de l'IA (tableau de bord Direction) : Claude pose une question sous forme de quiz,
   la réponse crée aussitôt une Tâche IA de correction (espace, site, bandeaux…). */

function aiq_all(): array { return q("SELECT q.*, u.name AS by_name, c.status AS c_status FROM ai_questions q LEFT JOIN users u ON u.id=q.answered_by LEFT JOIN consignes c ON c.id=q.consigne_id ORDER BY q.status='open' DESC, q.created_at, q.id")->fetchAll(); }
function aiq_open_count(): int { return (int)q("SELECT COUNT(*) FROM ai_questions WHERE status='open'")->fetchColumn(); }

/** Ajout ou mise à jour par clé : {key, question, context, options[], multi, other, task (avec {answer})}. Une question déjà répondue n'est pas rouverte. */
function aiq_store(array $items): array {
    $out = [];
    foreach (array_slice($items, 0, 30) as $it) {
        if (!is_array($it)) continue;
        $k = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($it['key'] ?? '')));
        $qq = mb_substr(trim((string)($it['question'] ?? '')), 0, 300);
        $task = mb_substr(trim((string)($it['task'] ?? '')), 0, 3000);
        $opts = array_values(array_filter(array_map(fn($o) => mb_substr(trim((string)$o), 0, 160), (array)($it['options'] ?? [])), fn($o) => $o !== ''));
        if ($k === '' || $qq === '' || $task === '' || (!$opts && empty($it['other']))) { $out[] = [$k, 'incomplet']; continue; }
        $old = q('SELECT status FROM ai_questions WHERE qkey=?', [$k])->fetchColumn();
        if ($old && $old !== 'open') { $out[] = [$k, 'déjà répondue']; continue; }
        q('INSERT INTO ai_questions(qkey,question,context,options,multi,other,task,status,created_at) VALUES(?,?,?,?,?,?,?,\'open\',?)
           ON CONFLICT(qkey) DO UPDATE SET question=excluded.question, context=excluded.context, options=excluded.options, multi=excluded.multi, other=excluded.other, task=excluded.task',
          [$k, $qq, mb_substr(trim((string)($it['context'] ?? '')), 0, 800), json_encode($opts, JSON_UNESCAPED_UNICODE), empty($it['multi']) ? 0 : 1, empty($it['other']) ? 0 : 1, $task, time()]);
        $out[] = [$k, $old ? 'mise à jour' : 'ajoutée'];
    }
    return $out;
}

/** Réponse : enregistre le choix et crée la Tâche IA de correction. */
function aiq_answer(array $u, int $id, array $choices, string $other): array {
    $qq = q("SELECT * FROM ai_questions WHERE id=? AND status='open'", [$id])->fetch();
    if (!$qq) return ['error' => 'Question introuvable ou déjà traitée.'];
    $opts = json_decode((string)$qq['options'], true) ?: [];
    $pick = array_values(array_intersect($opts, array_map('strval', $choices)));
    if (!$qq['multi']) $pick = array_slice($pick, 0, 1);
    $other = mb_substr(trim($other), 0, 300);
    if ($other !== '' && $qq['other']) $pick[] = $other;
    if (!$pick) return ['error' => 'Choisissez une réponse.'];
    $ans = implode(' ; ', $pick);
    $body = "Question IA : " . $qq['question'] . "\nRéponse de " . $u['name'] . " : " . $ans . "\n\n" . str_replace('{answer}', $ans, $qq['task']);
    q('INSERT INTO consignes(user_id,body,priority,status,space,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [(int)$u['id'], mb_substr($body, 0, 4000), 'normale', 'todo', 'direction', time(), time()]);
    $cid = (int)db()->lastInsertId();
    q("UPDATE ai_questions SET status='answered', answer=?, answered_by=?, answered_at=?, consigne_id=? WHERE id=?", [$ans, (int)$u['id'], time(), $cid, $id]);
    audit((int)$u['id'], 'aiq_answer:' . $qq['qkey']);
    return ['ok' => true, 'consigne' => $cid];
}

/* ---------- Maquettes HTML à valider (tableau de bord Direction) ---------- */
const MQ_STATUS = ['pending' => ['À valider', 'warn'], 'validated' => ['Validée', 'ok'], 'changes' => ['Modifications demandées', 'todo'], 'refused' => ['Refusée', 'neutral']];

function mq_all(): array { return q('SELECT m.id, m.slug, m.title, m.description, m.status, m.comment, m.decided_at, m.consigne_id, m.updated_at, u.name AS by_name, c.status AS c_status FROM maquettes m LEFT JOIN users u ON u.id=m.decided_by LEFT JOIN consignes c ON c.id=m.consigne_id ORDER BY m.status=\'pending\' DESC, m.updated_at DESC')->fetchAll(); }

/** Ajout ou nouvelle version (repasse « à valider » si le contenu change). */
function mq_store(array $items): array {
    $out = [];
    foreach (array_slice($items, 0, 10) as $it) {
        if (!is_array($it)) continue;
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($it['slug'] ?? ''))); $t = mb_substr(trim((string)($it['title'] ?? '')), 0, 160); $h = (string)($it['html'] ?? '');
        if ($slug === '' || $t === '' || strlen($h) < 50 || strlen($h) > 1_500_000) { $out[] = [$slug, 'incomplet']; continue; }
        $old = q('SELECT html FROM maquettes WHERE slug=?', [$slug])->fetchColumn();
        $desc = mb_substr(trim((string)($it['description'] ?? '')), 0, 600); $task = mb_substr(trim((string)($it['task'] ?? '')), 0, 3000);
        if ($old === false) q('INSERT INTO maquettes(slug,title,description,html,task,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [$slug, $t, $desc, $h, $task, time(), time()]);
        elseif ($old !== $h) q("UPDATE maquettes SET title=?, description=?, html=?, task=?, status='pending', comment=NULL, decided_by=NULL, decided_at=NULL, consigne_id=NULL, updated_at=? WHERE slug=?", [$t, $desc, $h, $task, time(), $slug]);
        else q('UPDATE maquettes SET title=?, description=?, task=? WHERE slug=?', [$t, $desc, $task, $slug]);
        $out[] = [$slug, $old === false ? 'ajoutée' : ($old !== $h ? 'nouvelle version' : 'inchangée')];
    }
    return $out;
}

/** validated → Tâche IA d'intégration ; changes → Tâche IA de reprise avec le commentaire ; refused → aucune tâche. */
function mq_decide(array $u, int $id, string $dec, string $comment): array {
    $m = q('SELECT * FROM maquettes WHERE id=?', [$id])->fetch();
    if (!$m) return ['error' => 'Maquette introuvable.'];
    if (!in_array($dec, ['validated', 'changes', 'refused'], true)) return ['error' => 'Décision inconnue.'];
    if ($m['status'] !== 'pending') return ['error' => 'Cette maquette a déjà été traitée.'];
    $comment = trim($comment);
    if ($dec === 'changes' && $comment === '') return ['error' => 'Indiquez les modifications souhaitées.'];
    $cid = null;
    if ($dec !== 'refused') {
        $body = $dec === 'validated'
            ? "Maquette validée par " . $u['name'] . " : « " . $m['title'] . " » (" . site_url() . "/maquettes/" . $m['slug'] . ").\n" . ($comment !== '' ? "Remarques : $comment\n" : '') . "\n" . ($m['task'] ?: 'Développer et intégrer cette maquette telle que validée.')
            : "Modifications demandées par " . $u['name'] . " sur la maquette « " . $m['title'] . " » (" . site_url() . "/maquettes/" . $m['slug'] . ") :\n$comment\n\nReprendre la maquette, puis la republier pour validation sur le tableau de bord Direction (ingest maquettes, même slug).";
        q('INSERT INTO consignes(user_id,body,priority,status,space,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [(int)$u['id'], mb_substr($body, 0, 4000), 'normale', 'todo', 'direction', time(), time()]);
        $cid = (int)db()->lastInsertId();
    }
    q('UPDATE maquettes SET status=?, comment=?, decided_by=?, decided_at=?, consigne_id=? WHERE id=?', [$dec, $comment !== '' ? mb_substr($comment, 0, 3000) : null, (int)$u['id'], time(), $cid, $id]);
    audit((int)$u['id'], "maquette_$dec:" . $m['slug']);
    return ['ok' => true, 'consigne' => $cid];
}

/** Affiche la maquette (page autonome, hors charte de l'espace), réservée à la Direction. */
function mq_serve(array $u, string $slug): never {
    if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); }
    $m = q('SELECT title, html FROM maquettes WHERE slug=?', [$slug])->fetch();
    if (!$m) { http_response_code(404); render('notfound', ['title' => 'Introuvable', 'message' => 'Maquette introuvable.'], false); }
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src data: https:; script-src 'unsafe-inline'; frame-ancestors 'none'; form-action 'none'; base-uri 'none'; sandbox allow-scripts allow-popups", true);
    header('Content-Type: text/html; charset=utf-8'); header('Cache-Control: private, no-store'); header('X-Robots-Tag: noindex, nofollow', true);
    $h = $m['html'];
    if (!preg_match('/<html[\s>]/i', $h)) $h = "<!doctype html>\n<html lang=\"fr\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\"><meta name=\"robots\" content=\"noindex, nofollow\"></head><body style=\"margin:0\">\n" . $h . "\n</body></html>";
    echo $h; exit;
}
