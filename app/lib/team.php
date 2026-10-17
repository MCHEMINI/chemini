<?php
declare(strict_types=1);

/*
 * Tâches d'équipe : un bloc par collaborateur, regroupés par service.
 * Visibilité : la Direction voit tout ; un collaborateur voit les tâches qui lui sont affectées
 * et celles qu'il a lui-même affectées, jamais celles qu'un membre de la Direction a affectées à d'autres.
 */

const TEAM_SERVICES = ['Direction' => 'Direction', 'Gestionnaire' => 'Gestion', 'Comptable' => 'Comptabilité', 'Assistant(e)' => 'Assistanat'];

function team_slug(string $name): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
}

/** Personnes : membres de la Direction (comptes de la plateforme) + annuaire ImmOpen des collaborateurs. */
function team_people(): array {
    static $cache = null; if ($cache !== null) return $cache;
    $out = [];
    foreach (space_members('direction') as $m) $out[team_slug($m['name'])] = ['key' => team_slug($m['name']), 'name' => $m['name'], 'role' => 'Direction', 'email' => strtolower((string)$m['email']), 'uid' => (int)$m['id']];
    foreach (q('SELECT name, role, email FROM staff WHERE archived_at IS NULL ORDER BY name')->fetchAll() as $s) {
        $k = team_slug($s['name']); if (isset($out[$k])) continue;
        $out[$k] = ['key' => $k, 'name' => $s['name'], 'role' => $s['role'] ?: 'Assistant(e)', 'email' => strtolower((string)$s['email']), 'uid' => 0];
    }
    $users = q('SELECT id, email FROM users')->fetchAll();
    foreach ($out as &$p) if (!$p['uid'] && $p['email']) foreach ($users as $x) if (strtolower($x['email']) === $p['email']) $p['uid'] = (int)$x['id'];
    unset($p);
    return $cache = $out;
}

/** Clé de la personne correspondant au compte connecté (ou null s'il n'est pas dans l'annuaire). */
function team_me(array $u): ?string {
    foreach (team_people() as $k => $p) if ($p['uid'] === (int)$u['id'] || ($p['email'] && $p['email'] === strtolower((string)$u['email']))) return $k;
    return null;
}

/** Filtre SQL de visibilité pour l'utilisateur. */
function team_where(array $u): array {
    $me = team_me($u) ?? '-';
    if (can_space($u, 'direction')) return ['(t.priv=0 OR t.person=? OR t.created_by=?)', [$me, (int)$u['id']]];
    return ['(t.person=? OR t.created_by=?)', [$me, (int)$u['id']]];
}

function team_can_edit(array $u, array $t): bool {
    if ((int)($t['priv'] ?? 0)) return $t['person'] === team_me($u) || (int)$t['created_by'] === (int)$u['id'];
    return can_space($u, 'direction') || (int)$t['created_by'] === (int)$u['id'] || $t['person'] === team_me($u);
}

/** Rôle normalisé à partir de la fonction ImmOpen. */
function staff_role(string $r): string {
    $l = mb_strtolower($r);
    if (str_contains($l, 'assist') || str_contains($l, 'secr') || str_contains($l, 'accueil') || str_contains($l, 'standard')) return 'Assistant(e)';
    if (str_contains($l, 'compta')) return 'Comptable';
    if (str_contains($l, 'gestion') || str_contains($l, 'principal')) return 'Gestionnaire';
    return $r !== '' ? mb_substr($r, 0, 40) : 'Assistant(e)';
}

/** Synchronise l'annuaire avec la liste complète ImmOpen : ajouts, retours, archivages (et comptes désactivés). */
function staff_sync(array $rows): array {
    $now = time(); $seen = []; $log = ['added' => [], 'archived' => [], 'back' => [], 'updated' => 0];
    $cur = []; foreach (q('SELECT * FROM staff')->fetchAll() as $s) $cur[mb_strtolower(trim($s['name']))] = $s;
    $admins = array_map('strtolower', q("SELECT email FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN));
    $dirEmails = array_map(fn($m) => strtolower((string)$m['email']), space_members('direction'));
    foreach ($rows as $r) {
        $name = trim(preg_replace('/\s+/', ' ', (string)($r['name'] ?? ''))); if ($name === '') continue;
        $email = strtolower(trim((string)($r['email'] ?? ''))); if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
        $phone = mb_substr(trim((string)($r['phone'] ?? '')), 0, 40); $role = staff_role(trim((string)($r['role'] ?? '')));
        $k = mb_strtolower($name); $seen[$k] = true;
        if (!isset($cur[$k])) {
            q('INSERT INTO staff(name,role,email,phone,updated_at) VALUES(?,?,?,?,?)', [$name, $role, $email ?: null, $phone ?: null, $now]);
            $log['added'][] = $name;
            q('INSERT INTO team_tasks(person,title,due,created_by,by_dir,created_at,priv) VALUES(?,?,?,?,1,?,0)', [team_slug($name), SURVEY_TASK, date('Y-m-d', strtotime('+14 days')), (int)q("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn(), $now]);
            continue;
        }
        $s = $cur[$k];
        if ($s['archived_at']) $log['back'][] = $name;
        q('UPDATE staff SET role=?, email=COALESCE(?, email), phone=COALESCE(?, phone), archived_at=NULL, updated_at=? WHERE name=?', [$role, $email ?: null, $phone ?: null, $now, $s['name']]);
        $log['updated']++;
        if ($email) q('UPDATE users SET disabled_at=NULL WHERE lower(email)=?', [$email]);
    }
    foreach ($cur as $k => $s) {
        if (isset($seen[$k]) || $s['archived_at']) continue;
        q('UPDATE staff SET archived_at=? WHERE name=?', [$now, $s['name']]);
        $log['archived'][] = $s['name'];
        $em = strtolower((string)$s['email']);
        if ($em && !in_array($em, $admins, true) && !in_array($em, $dirEmails, true)) q('UPDATE users SET disabled_at=? WHERE lower(email)=?', [$now, $em]);
        q('UPDATE team_tasks SET done_at=? WHERE person=? AND title=? AND done_at IS NULL', [$now, team_slug($s['name']), SURVEY_TASK]);
    }
    q("INSERT INTO kv(k,v,updated_at) VALUES('staff_sync',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode($log + ['at' => $now, 'n' => count($seen)], JSON_UNESCAPED_UNICODE), $now]);
    return $log;
}

/** Accès d'un collaborateur : compte actif, invitation en attente ou aucun. */
function staff_access(): array {
    $users = []; foreach (q('SELECT id, email, disabled_at FROM users')->fetchAll() as $x) $users[strtolower($x['email'])] = $x;
    $inv = []; foreach (q('SELECT email, MAX(expires_at) e FROM invites WHERE used_at IS NULL AND expires_at>? GROUP BY email', [time()])->fetchAll() as $x) $inv[strtolower($x['email'])] = (int)$x['e'];
    $out = [];
    foreach (q('SELECT * FROM staff ORDER BY archived_at IS NOT NULL, role, name')->fetchAll() as $s) {
        $em = strtolower((string)$s['email']);
        $st = $s['archived_at'] ? 'archived' : (!$em ? 'noemail' : (isset($users[$em]) ? ($users[$em]['disabled_at'] ? 'disabled' : 'active') : (isset($inv[$em]) ? 'invited' : 'none')));
        $out[] = $s + ['status' => $st, 'inv_exp' => $inv[$em] ?? null];
    }
    return $out;
}
