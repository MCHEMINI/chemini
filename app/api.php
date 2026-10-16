<?php
declare(strict_types=1);

function api(string $r): never {
    if (preg_match('#^(login/|enroll/|reset/)#', $r)) ip_block_guard();
    /* ---------- Connexion (sans session) ---------- */
    if ($r === 'login/start') {
        $email = strtolower(in_str('email', 190));
        $u = q('SELECT * FROM users WHERE email=? AND totp_enabled=1', [$email])->fetch();
        // Réponse identique si le compte n'existe pas (pas de divulgation)
        $_SESSION['pending_uid'] = $u ? (int)$u['id'] : 0;
        $hasPk = $u && q('SELECT 1 FROM passkeys WHERE user_id=?', [$u['id']])->fetchColumn();
        json_out(['ok' => true, 'passkey' => (bool)$hasPk]);
    }
    if ($r === 'login/password') {
        $email = strtolower(in_str('email', 190));
        $u = q('SELECT * FROM users WHERE email=?', [$email])->fetch();
        $ok = $u && $u['password_hash'] && password_verify((string)(body()['password'] ?? ''), $u['password_hash']);
        if (!$ok) {
            if (!$u) password_verify('x', '$2y$10$65KTt1rntwzVlz1Ta3xogeE8ue36leMFTeMtegj4e./2fx0eQ5sne'); // temps constant
            ip_fail($u ? (int)$u['id'] : null, 'password');
            json_out(['error' => $u && !$u['password_hash'] ? 'Aucun mot de passe défini pour ce compte. Utilisez Face ID ou le lien par e-mail.' : 'Adresse ou mot de passe incorrect.'], 401);
        }
        if ((int)$u['must_change'] === 1) {
            if ($u['default_expires'] && time() > (int)$u['default_expires']) json_out(['error' => 'Le mot de passe provisoire a expiré. Demandez une réinitialisation.'], 401);
            $_SESSION['chg_uid'] = (int)$u['id']; $_SESSION['chg_t'] = time();
            json_out(['ok' => true, 'next' => 'newpw']);
        }
        if ((int)$u['totp_enabled']) {
            $_SESSION['pw_uid'] = (int)$u['id'];
            $_SESSION['pw_t'] = time();
            json_out(['ok' => true, 'next' => 'totp']);
        }
        // Code à 6 chiffres non activé (optionnel) : connexion directe
        ip_ok();
        login_session((int)$u['id']);
        audit((int)$u['id'], 'login_password');
        json_out(['ok' => true, 'next' => 'trust']);
    }
    if ($r === 'login/newpw') {
        $uid = (int)($_SESSION['chg_uid'] ?? 0);
        if (!$uid || time() - (int)($_SESSION['chg_t'] ?? 0) > 900) json_out(['error' => 'Session expirée, reconnectez-vous.'], 401);
        $pw = (string)(body()['password'] ?? '');
        if (mb_strlen($pw) < 10) json_out(['error' => 'Au moins 10 caractères.'], 400);
        if ($pw !== (string)(body()['confirm'] ?? '')) json_out(['error' => 'Les deux saisies ne correspondent pas.'], 400);
        $u = q('SELECT * FROM users WHERE id=?', [$uid])->fetch();
        if (password_verify($pw, (string)$u['password_hash'])) json_out(['error' => 'Choisissez un mot de passe différent du mot de passe provisoire.'], 400);
        q('UPDATE users SET password_hash=?, must_change=0, default_expires=NULL WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $uid]);
        audit($uid, 'password_initial_change');
        unset($_SESSION['chg_uid'], $_SESSION['chg_t']);
        if ((int)$u['totp_enabled']) { $_SESSION['pw_uid'] = $uid; $_SESSION['pw_t'] = time(); json_out(['ok' => true, 'next' => 'totp']); }
        ip_ok();
        login_session($uid);
        json_out(['ok' => true, 'next' => 'trust']);
    }
    if ($r === 'login/totp') {
        $uid = (int)($_SESSION['pw_uid'] ?? 0);
        if (!$uid || time() - (int)($_SESSION['pw_t'] ?? 0) > 300) json_out(['error' => 'Session expirée, saisissez à nouveau votre mot de passe.'], 401);
        $u = q('SELECT * FROM users WHERE id=?', [$uid])->fetch();
        $step = $u ? totp_verify($u['totp_secret'], in_str('code', 10), (int)$u['totp_last_step']) : null;
        if (!$step) { ip_fail($uid, 'totp'); json_out(['error' => 'Code incorrect ou expiré.'], 401); }
        ip_ok();
        q('UPDATE users SET totp_last_step=? WHERE id=?', [$step, $uid]);
        unset($_SESSION['pw_uid'], $_SESSION['pw_t']);
        login_session($uid);
        audit($uid, 'login_password_totp');
        json_out(['ok' => true]);
    }
    if ($r === 'login/forgot') {
        $email = strtolower(in_str('email', 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Saisissez votre adresse e-mail.'], 400);
        if (rate_limited('reset:' . $email, 3, 3600) || rate_limited('ip:' . client_ip(), 30, 900)) json_out(['error' => 'Trop de demandes. Réessayez plus tard.'], 429);
        rate_hit('reset:' . $email); rate_hit('ip:' . client_ip());
        $u = q('SELECT * FROM users WHERE email=? AND totp_enabled=1', [$email])->fetch();
        if ($u) {
            $tok = b64u_enc(random_bytes(32));
            q('INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,?)', [$u['id'], hash('sha256', $tok), time() + 1800]);
            $sent = send_reset_link($u, BASE_URL . '/connexion/reinitialiser?t=' . $tok);
            audit((int)$u['id'], $sent ? 'reset_sent' : 'reset_mail_failed');
        }
        json_out(['ok' => true]); // réponse identique, que le compte existe ou non
    }
    if ($r === 'reset/confirm') {
        $tok = in_str('token', 100);
        $row = $tok !== '' ? q('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>?', [hash('sha256', $tok), time()])->fetch() : null;
        if (!$row) json_out(['error' => 'Ce lien a expiré ou a déjà servi. Faites une nouvelle demande.'], 410);
        $pw = (string)(body()['password'] ?? '');
        if (mb_strlen($pw) < 10) json_out(['error' => 'Au moins 10 caractères.'], 400);
        if ($pw !== (string)(body()['confirm'] ?? '')) json_out(['error' => 'Les deux saisies ne correspondent pas.'], 400);
        q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $row['user_id']]);
        q('UPDATE password_resets SET used_at=? WHERE user_id=? AND used_at IS NULL', [time(), $row['user_id']]);
        audit((int)$row['user_id'], 'password_reset');
        $email = q('SELECT email FROM users WHERE id=?', [$row['user_id']])->fetchColumn();
        json_out(['ok' => true, 'email' => $email]);
    }
    if ($r === 'login/link') {
        $email = strtolower(in_str('email', 190));
        if (rate_limited('link:' . $email, 3, 900) || rate_limited('ip:' . client_ip(), 30, 900)) json_out(['error' => 'Trop de demandes. Réessayez dans 15 minutes.'], 429);
        rate_hit('link:' . $email); rate_hit('ip:' . client_ip());
        $u = q('SELECT * FROM users WHERE email=? AND totp_enabled=1', [$email])->fetch();
        if ($u) {
            $tok = b64u_enc(random_bytes(32));
            q('INSERT INTO magic_links(user_id,token_hash,expires_at) VALUES(?,?,?)', [$u['id'], hash('sha256', $tok), time() + 900]);
            $sent = send_magic_link($u, BASE_URL . '/connexion/lien?t=' . $tok);
            audit((int)$u['id'], $sent ? 'magic_link_sent' : 'magic_link_failed');
        }
        json_out(['ok' => true]); // réponse identique, que le compte existe ou non
    }
    if ($r === 'login/passkey-options') {
        $uid = (int)($_SESSION['pending_uid'] ?? 0);
        $ids = $uid ? q('SELECT cred_id FROM passkeys WHERE user_id=?', [$uid])->fetchAll(PDO::FETCH_COLUMN) : [];
        json_out(wa_login_options($ids));
    }
    if ($r === 'login/passkey') {
        try { $pk = wa_login_verify(body()); }
        catch (RuntimeException $ex) { ip_fail(null, 'passkey'); json_out(['error' => 'Vérification refusée : ' . $ex->getMessage()], 401); }
        ip_ok();
        $pending = (int)($_SESSION['pending_uid'] ?? 0);
        if ($pending && $pending !== (int)$pk['user_id']) json_out(['error' => 'Cette clé appartient à un autre compte.'], 401);
        login_session((int)$pk['user_id']);
        audit((int)$pk['user_id'], 'login_passkey');
        json_out(['ok' => true]);
    }
    if ($r === 'enroll/verify') {
        $e = $_SESSION['enroll'] ?? null;
        if (!$e || time() - $e['t'] > 1800) json_out(['error' => 'Lien expiré, rouvrez le lien d\'activation.'], 400);

        $pw = (string)(body()['password'] ?? '');
        if (mb_strlen($pw) < 10) json_out(['error' => 'Choisissez un mot de passe d\'au moins 10 caractères.'], 400);
        $step = totp_verify($e['secret'], in_str('code', 10));
        if (!$step) { ip_fail(null, 'enroll'); json_out(['error' => 'Code incorrect. Vérifiez l\'heure de votre téléphone.'], 401); }
        db()->beginTransaction();
        if ($e['invite']) {
            $inv = q('SELECT * FROM invites WHERE id=? AND used_at IS NULL AND expires_at>?', [$e['invite'], time()])->fetch();
            if (!$inv) { db()->rollBack(); json_out(['error' => 'Invitation expirée.'], 400); }
            q('UPDATE invites SET used_at=? WHERE id=?', [time(), $inv['id']]);
        } elseif (q("SELECT 1 FROM users WHERE role='admin'")->fetchColumn()) {
            db()->rollBack(); json_out(['error' => 'Compte déjà activé.'], 400);
        }
        $ex = q('SELECT id FROM users WHERE email=?', [$e['email']])->fetchColumn();
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        if ($ex) q('UPDATE users SET totp_secret=?, totp_enabled=1, totp_last_step=?, password_hash=? WHERE id=?', [$e['secret'], $step, $hash, $ex]);
        else q('INSERT INTO users(email,name,initials,role,totp_secret,totp_enabled,totp_last_step,password_hash,created_at) VALUES(?,?,?,?,?,1,?,?,?)',
            [$e['email'], $e['name'], $e['initials'], $e['role'], $e['secret'], $step, $hash, time()]);
        $uid = $ex ?: (int)db()->lastInsertId();
        db()->commit();
        unset($_SESSION['enroll']);
        login_session((int)$uid);
        audit((int)$uid, 'enrolled');
        json_out(['ok' => true]);
    }

    /* ---------- À partir d'ici : utilisateur connecté ---------- */
    $u = current_user();
    if (!$u) json_out(['error' => 'Session expirée, reconnectez-vous.'], 401);
    $uid = (int)$u['id'];

    if (preg_match('#^(items|deadlines|building)/#', $r) && !can_space($u, 'direction')) json_out(['error' => 'Réservé à la Direction.'], 403);
    switch ($r) {
        case 'trust':
            if (body()['trust'] ?? false) { trust_this_device($uid); audit($uid, 'device_trusted'); }
            json_out(['ok' => true]);

        case 'passkey/options':
            $ids = q('SELECT cred_id FROM passkeys WHERE user_id=?', [$uid])->fetchAll(PDO::FETCH_COLUMN);
            json_out(wa_registration_options($u, $ids));
        case 'passkey/register':
            try { $c = wa_registration_verify(body()); }
            catch (RuntimeException $ex) { json_out(['error' => 'Enregistrement refusé : ' . $ex->getMessage()], 400); }
            q('INSERT INTO passkeys(user_id,cred_id,public_key_pem,sign_count,label,created_at) VALUES(?,?,?,?,?,?)',
                [$uid, $c['credId'], $c['pem'], $c['signCount'], in_str('label', 60) ?: device_label(), time()]);
            audit($uid, 'passkey_added');
            json_out(['ok' => true]);
        case 'passkey/delete':
            q('DELETE FROM passkeys WHERE id=? AND user_id=?', [in_int('id'), $uid]);
            audit($uid, 'passkey_removed');
            json_out(['ok' => true]);

        case 'totp/start':
            $_SESSION['setup_secret'] = totp_new_secret();
            json_out(['ok' => true, 'secret' => trim(chunk_split($_SESSION['setup_secret'], 4, ' ')), 'uri' => totp_uri($_SESSION['setup_secret'], $u['email'])]);
        case 'totp/confirm':
            $sec = (string)($_SESSION['setup_secret'] ?? '');
            if ($sec === '') json_out(['error' => 'Recommencez l\'activation.'], 400);
            $step = totp_verify($sec, in_str('code', 10));
            if (!$step) json_out(['error' => 'Code incorrect. Vérifiez l\'heure de votre téléphone.'], 401);
            q('UPDATE users SET totp_secret=?, totp_enabled=1, totp_last_step=? WHERE id=?', [$sec, $step, $uid]);
            unset($_SESSION['setup_secret']);
            audit($uid, 'totp_enabled');
            json_out(['ok' => true]);
        case 'totp/disable':
            if (!$u['password_hash'] || !password_verify((string)(body()['password'] ?? ''), $u['password_hash'])) json_out(['error' => 'Mot de passe incorrect.'], 401);
            q('UPDATE users SET totp_enabled=0, totp_secret=NULL WHERE id=?', [$uid]);
            audit($uid, 'totp_disabled');
            json_out(['ok' => true]);

        case 'password/set':
            $new = (string)(body()['password'] ?? '');
            if (mb_strlen($new) < 10) json_out(['error' => 'Au moins 10 caractères.'], 400);
            if ($new !== (string)(body()['confirm'] ?? '')) json_out(['error' => 'Les deux saisies ne correspondent pas.'], 400);
            if ($u['password_hash'] && !password_verify((string)(body()['current'] ?? ''), $u['password_hash'])) json_out(['error' => 'Mot de passe actuel incorrect.'], 401);
            q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            audit($uid, 'password_set');
            json_out(['ok' => true]);

        case 'device/revoke':
            q('DELETE FROM trusted_devices WHERE id=? AND user_id=?', [in_int('id'), $uid]);
            audit($uid, 'device_revoked');
            json_out(['ok' => true]);

        case 'invite':
            if ($u['role'] !== 'admin') json_out(['error' => 'Réservé à l\'administrateur.'], 403);
            $email = strtolower(in_str('email', 190));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Adresse e-mail invalide.'], 400);
            $name = in_str('name', 60) ?: $email;
            $ini = mb_strtoupper(in_str('initials', 3) ?: mb_substr($name, 0, 1));
            $tok = b64u_enc(random_bytes(24));
            q('INSERT INTO invites(email,name,initials,role,token_hash,expires_at) VALUES(?,?,?,?,?,?)',
                [$email, $name, $ini, 'member', hash('sha256', $tok), time() + 7 * 86400]);
            audit($uid, 'invite_created:' . $email);
            json_out(['ok' => true, 'link' => BASE_URL . '/activation?t=' . $tok]);

        case 'microsoft/disconnect':
            q('DELETE FROM graph_tokens WHERE user_id=?', [$uid]);
            unset($_SESSION['mails_' . $uid]);
            json_out(['ok' => true]);

        /* Points importants (communs) */
        case 'items/create':
            $t = in_str('title', 200);
            if ($t === '') json_out(['error' => 'Indiquez un intitulé.'], 400);
            $sev = in_array(body()['severity'] ?? '', ['crit', 'warn', 'ok'], true) ? body()['severity'] : 'warn';
            $owner = in_int('owner_id') ?: $uid;
            q('INSERT INTO items(title,detail,copro,severity,owner_id,created_by,created_at) VALUES(?,?,?,?,?,?,?)',
                [$t, in_str('detail', 400), in_str('copro', 120), $sev, $owner, $uid, time()]);
            json_out(['ok' => true]);
        case 'items/done':
            q("UPDATE items SET status='done', done_at=? WHERE id=?", [time(), in_int('id')]);
            json_out(['ok' => true]);
        case 'items/delete':
            q('DELETE FROM items WHERE id=?', [in_int('id')]);
            json_out(['ok' => true]);

        /* Échéances (communes) */
        case 'deadlines/create':
            $t = in_str('title', 200); $d = in_str('due_date', 10);
            if ($t === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) json_out(['error' => 'Indiquez un intitulé et une date.'], 400);
            q('INSERT INTO deadlines(title,detail,copro,due_date,created_by,created_at) VALUES(?,?,?,?,?,?)',
                [$t, in_str('detail', 400), in_str('copro', 120), $d, $uid, time()]);
            json_out(['ok' => true]);
        case 'deadlines/done':
            q('UPDATE deadlines SET done=1 WHERE id=?', [in_int('id')]);
            json_out(['ok' => true]);
        case 'deadlines/delete':
            q('DELETE FROM deadlines WHERE id=?', [in_int('id')]);
            json_out(['ok' => true]);

        /* Outils partagés : état commun */
        case 'prefs/set': // préférences d'affichage de l'utilisateur connecté uniquement
            $k = in_str('k', 40);
            if (!in_array($k, PREF_KEYS, true)) json_out(['error' => 'Préférence inconnue'], 400);
            $v = json_encode(body()['v'] ?? null, JSON_UNESCAPED_UNICODE);
            if (strlen($v) > 4000) json_out(['error' => 'Trop long'], 400);
            q('INSERT INTO user_prefs(user_id,k,v,updated_at) VALUES(?,?,?,?) ON CONFLICT(user_id,k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at', [$uid, $k, $v, time()]);
            json_out(['ok' => true]);
        case 'doc/state':
            $slug = in_str('slug', 40);
            $dsp = q('SELECT space FROM docs WHERE slug=?', [$slug])->fetchColumn();
            if ($dsp === false) json_out(['error' => 'Document inconnu'], 404);
            if (!can_space($u, $dsp)) json_out(['error' => 'Accès refusé.'], 403);
            require_once APP_DIR . '/pages/docs.php';
            if (!doc_can_edit($u, $slug)) json_out(['error' => 'Lecture seule : seul Mehdi Chemini peut modifier ce tableau.'], 403);
            if (body()['reset'] ?? false) { q('DELETE FROM doc_state WHERE slug=?', [$slug]); json_out(['ok' => true]); }
            $json = (string)(body()['json'] ?? '');
            if (strlen($json) > 6_000_000 || json_decode($json) === null) json_out(['error' => 'Données invalides'], 400);
            q('INSERT INTO doc_state(slug,json,updated_at,updated_by) VALUES(?,?,?,?) ON CONFLICT(slug) DO UPDATE SET json=excluded.json, updated_at=excluded.updated_at, updated_by=excluded.updated_by',
                [$slug, $json, time(), $uid]);
            json_out(['ok' => true]);

        /* Espace personnel (privé) */
        case 'tasks/create':
            $t = in_str('title', 200);
            if ($t === '') json_out(['error' => 'Indiquez une tâche.'], 400);
            $sp = in_str('space', 20) ?: null;
            if ($sp !== null && (!isset(SPACES[$sp]) || !can_space($u, $sp))) json_out(['error' => 'Accès refusé.'], 403);
            q('INSERT INTO tasks(user_id,title,space,created_at) VALUES(?,?,?,?)', [$uid, $t, $sp, time()]);
            json_out(['ok' => true]);
        case 'tasks/toggle':
        case 'tasks/delete':
            $task = q('SELECT * FROM tasks WHERE id=?', [in_int('id')])->fetch();
            $allowed = $task && ($task['space'] === null ? (int)$task['user_id'] === $uid : can_space($u, $task['space']));
            if (!$allowed) json_out(['error' => 'Accès refusé.'], 403);
            q($r === 'tasks/toggle' ? 'UPDATE tasks SET done = 1 - done WHERE id=?' : 'DELETE FROM tasks WHERE id=?', [$task['id']]);
            json_out(['ok' => true]);
        case 'consignes/create':
            $b = in_str('body', 4000);
            if ($b === '') json_out(['error' => 'Écrivez une consigne.'], 400);
            $p = in_str('priority', 10);
            if (!isset(CONSIGNE_PRIO[$p])) $p = 'normale';
            $sp = in_str('space', 20);
            if (!isset(consigne_spaces($u)[$sp])) $sp = 'perso';
            q('INSERT INTO consignes(user_id,body,priority,status,space,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [$uid, $b, $p, 'todo', $sp, time(), time()]);
            json_out(['ok' => true]);
        case 'consignes/delete':
            q('DELETE FROM consignes WHERE id=? AND user_id=?', [in_int('id'), $uid]);
            json_out(['ok' => true]);
        case 'mail/toggle':
            q('UPDATE mail_items SET done = 1 - done, done_at = ? WHERE id=? AND user_id=?', [time(), in_int('id'), $uid]);
            json_out(['ok' => true]);
        case 'building/save':
            $code = in_str('code', 10);
            if (!q('SELECT 1 FROM buildings WHERE code=?', [$code])->fetchColumn()) json_out(['error' => 'Immeuble inconnu.'], 404);
            $email = in_str('president_email', 190);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'E-mail du président invalide.'], 422);
            q('INSERT INTO building_info(code,president,president_phone,president_email,cs_members,gardien,acces,notes,updated_at,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?)
               ON CONFLICT(code) DO UPDATE SET president=excluded.president, president_phone=excluded.president_phone, president_email=excluded.president_email,
               cs_members=excluded.cs_members, gardien=excluded.gardien, acces=excluded.acces, notes=excluded.notes, updated_at=excluded.updated_at, updated_by=excluded.updated_by',
              [$code, in_str('president', 120), in_str('president_phone', 40), $email, in_str('cs_members', 2000), in_str('gardien', 2000), in_str('acces', 2000), in_str('notes', 4000), time(), $uid]);
            json_out(['ok' => true]);
        case 'building/note':
            $code = in_str('code', 10); $t = in_str('title', 300);
            if ($t === '' || !q('SELECT 1 FROM buildings WHERE code=?', [$code])->fetchColumn()) json_out(['error' => 'Note vide.'], 422);
            q('INSERT INTO building_events(code,at,kind,title,user_id,created_at) VALUES(?,?,?,?,?,?)', [$code, time(), 'note', $t, $uid, time()]);
            json_out(['ok' => true]);
        case 'compta/done':
            if (!can_space($u, 'direction')) json_out(['error' => 'Accès refusé.'], 403);
            $k = in_str('k', 64);
            if (q('SELECT 1 FROM compta_done WHERE k=?', [$k])->fetchColumn()) q('DELETE FROM compta_done WHERE k=?', [$k]);
            else q('INSERT INTO compta_done(k,user_id,done_at) VALUES(?,?,?)', [$k, $uid, time()]);
            json_out(['ok' => true]);
        case 'audit/read':
            q('INSERT OR IGNORE INTO audit_reads(user_id,audit_id,read_at) VALUES(?,?,?)', [$uid, in_int('id'), time()]);
            json_out(['ok' => true]);
        case 'space/note':
            $sp = in_str('space', 20);
            if (!isset(SPACES[$sp]) || !can_space($u, $sp)) json_out(['error' => 'Accès refusé.'], 403);
            q('INSERT INTO space_notes(space,body,updated_at,updated_by) VALUES(?,?,?,?) ON CONFLICT(space) DO UPDATE SET body=excluded.body, updated_at=excluded.updated_at, updated_by=excluded.updated_by',
                [$sp, in_str('body', 20000), time(), $uid]);
            json_out(['ok' => true]);
        case 'staff/update':
            if (!can_space($u, 'direction')) json_out(['error' => 'Réservé à la direction.'], 403);
            $name = in_str('name', 120);
            $email = strtolower(in_str('email', 190));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Adresse e-mail invalide.'], 400);
            q('UPDATE staff SET email=?, phone=?, updated_at=? WHERE name=?', [$email ?: null, in_str('phone', 40) ?: null, time(), $name]);
            json_out(['ok' => true]);
        case 'note/save':
            q('INSERT INTO notes(user_id,body,updated_at) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET body=excluded.body, updated_at=excluded.updated_at',
                [$uid, in_str('body', 5000), time()]);
            json_out(['ok' => true]);
    }
    json_out(['error' => 'Action inconnue'], 404);
}
