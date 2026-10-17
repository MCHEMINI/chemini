<?php
declare(strict_types=1);

/* Demandes des collaborateurs à la Direction.
   Le collaborateur écrit depuis son espace (visible par lui et la Direction). Une Tâche IA, invisible pour lui,
   prépare deux réponses argumentées (accepter / refuser) ; la Direction choisit, personnalise et publie. */

const RQ_CATS = ['conges' => 'Congés et absences', 'horaires' => 'Horaires et télétravail', 'formation' => 'Formation', 'materiel' => 'Matériel et outils', 'remuneration' => 'Rémunération', 'organisation' => 'Organisation du travail', 'autre' => 'Autre'];
const RQ_STATUS = ['new' => ['Envoyée', 'todo'], 'drafted' => ['En cours d\'examen', 'warn'], 'answered' => ['Répondue', 'ok'], 'withdrawn' => ['Retirée', 'neutral']];

function rq_mine(int $uid): array { return q('SELECT * FROM staff_requests WHERE user_id=? ORDER BY status=\'answered\', created_at DESC LIMIT 50', [$uid])->fetchAll(); }
function rq_for_direction(): array { return q("SELECT r.*, u.name AS user_name, a.name AS by_name FROM staff_requests r JOIN users u ON u.id=r.user_id LEFT JOIN users a ON a.id=r.answered_by WHERE r.status<>'withdrawn' ORDER BY r.status='answered', r.created_at DESC LIMIT 60")->fetchAll(); }

/** Nouvelle demande : enregistrée, puis Tâche IA (au nom de l'administrateur, jamais visible du collaborateur). */
function rq_create(array $u, string $cat, string $subject, string $body): array {
    if (!isset(RQ_CATS[$cat])) $cat = 'autre';
    $subject = mb_substr(trim($subject), 0, 160); $body = mb_substr(trim($body), 0, 4000);
    if ($subject === '' || $body === '') return ['error' => 'Indiquez un objet et votre demande.'];
    if (rate_limited('rq:' . $u['id'], 10, 86400)) return ['error' => 'Trop de demandes aujourd\'hui : réessayez demain.'];
    rate_hit('rq:' . $u['id']);
    q('INSERT INTO staff_requests(user_id,cat,subject,body,status,created_at,updated_at) VALUES(?,?,?,?,\'new\',?,?)', [(int)$u['id'], $cat, $subject, $body, time(), time()]);
    $id = (int)db()->lastInsertId();
    $admin = (int)q("SELECT id FROM users WHERE role='admin' AND disabled_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
    if ($admin) {
        $t = "Demande #$id à la Direction (" . RQ_CATS[$cat] . ") : préparer deux réponses argumentées.\n"
           . "Lis la demande avec GET /ingest/requests, puis envoie POST /ingest/requests {items:[{id:$id, analysis, accept, refuse}]} :\n"
           . "- analysis : contexte, cadre légal ou conventionnel applicable (convention collective de l'immobilier, Code du travail), impact pour le cabinet, recommandation ;\n"
           . "- accept : réponse prête à envoyer qui accepte, avec ses arguments et conditions éventuelles ;\n"
           . "- refuse : réponse prête à envoyer qui refuse, avec des arguments respectueux et une alternative si possible.\n"
           . "Ton cordial et professionnel, vouvoiement, signé « La Direction ». Ne contacte jamais le collaborateur.";
        q('INSERT INTO consignes(user_id,body,priority,status,space,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [$admin, $t, 'normale', 'todo', 'direction', time(), time()]);
        q('UPDATE staff_requests SET consigne_id=? WHERE id=?', [(int)db()->lastInsertId(), $id]);
    }
    audit((int)$u['id'], 'request_created:' . $id);
    return ['ok' => true, 'id' => $id];
}

/** Réponses préparées par l'IA. */
function rq_drafts(array $items): array {
    $out = [];
    foreach (array_slice($items, 0, 30) as $it) {
        $id = (int)($it['id'] ?? 0); $r = q('SELECT * FROM staff_requests WHERE id=?', [$id])->fetch();
        if (!$r || $r['status'] === 'answered' || $r['status'] === 'withdrawn') { $out[] = [$id, 'ignorée']; continue; }
        $s = fn($k, $m) => mb_substr(trim((string)($it[$k] ?? '')), 0, $m);
        if ($s('accept', 6000) === '' || $s('refuse', 6000) === '') { $out[] = [$id, 'incomplet']; continue; }
        q("UPDATE staff_requests SET ai_analysis=?, ai_accept=?, ai_refuse=?, status='drafted', updated_at=? WHERE id=?", [$s('analysis', 6000), $s('accept', 6000), $s('refuse', 6000), time(), $id]);
        if ($r['consigne_id']) q("UPDATE consignes SET status='done', result=?, updated_at=? WHERE id=?", ['Réponses préparées : à choisir sur le tableau de bord Direction.', time(), $r['consigne_id']]);
        $out[] = [$id, 'préparée'];
    }
    return $out;
}

/** Publication par la Direction : la réponse choisie (personnalisée) devient visible du collaborateur. */
function rq_answer(array $u, int $id, string $decision, string $text): array {
    $r = q('SELECT * FROM staff_requests WHERE id=?', [$id])->fetch();
    if (!$r || $r['status'] === 'withdrawn') return ['error' => 'Demande introuvable.'];
    if (!in_array($decision, ['accept', 'refuse', 'other'], true)) return ['error' => 'Choisissez accepter, refuser ou une autre réponse.'];
    $text = mb_substr(trim($text), 0, 6000); if ($text === '') return ['error' => 'La réponse est vide.'];
    q("UPDATE staff_requests SET status='answered', decision=?, answer=?, answered_by=?, answered_at=?, seen_at=NULL, updated_at=? WHERE id=?", [$decision, $text, (int)$u['id'], time(), time(), $id]);
    if ($r['consigne_id']) q("UPDATE consignes SET status='done', updated_at=? WHERE id=? AND status<>'done'", [time(), $r['consigne_id']]);
    audit((int)$u['id'], 'request_answered:' . $id . ':' . $decision);
    return ['ok' => true];
}
