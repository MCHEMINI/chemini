<?php
declare(strict_types=1);

function api(string $r): never {
    /* ---------- Connexion (sans session) ---------- */
    if ($r === 'login/start') {
        $email = strtolower(in_str('email', 190));
        if (rate_limited('ip:' . client_ip(), 30, 900)) json_out(['error' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429);
        rate_hit('ip:' . client_ip());
        $u = q('SELECT * FROM users WHERE email=? AND totp_enabled=1', [$email])->fetch();
        // Réponse identique si le compte n'existe pas (pas de divulgation)
        $_SESSION['pending_uid'] = $u ? (int)$u['id'] : 0;
        $hasPk = $u && q('SELECT 1 FROM passkeys WHERE user_id=?', [$u['id']])->fetchColumn();
        json_out(['ok' => true, 'passkey' => (bool)$hasPk]);
    }
    if ($r === 'login/totp') {
        $uid = (int)($_SESSION['pending_uid'] ?? 0);
        $key = 'totp:' . $uid . ':' . client_ip();
        if (rate_limited($key, 6, 900)) json_out(['error' => 'Trop de codes erronés. Réessayez dans 15 minutes.'], 429);
        $u = $uid ? q('SELECT * FROM users WHERE id=?', [$uid])->fetch() : null;
        $step = $u ? totp_verify($u['totp_secret'], in_str('code', 10), (int)$u['totp_last_step']) : null;
        if (!$step) { rate_hit($key); json_out(['error' => 'Code incorrect ou expiré.'], 401); }
        rate_clear($key);
        q('UPDATE users SET totp_last_step=? WHERE id=?', [$step, $uid]);
        login_session($uid);
        audit($uid, 'login_totp');
        json_out(['ok' => true]);
    }
    if ($r === 'login/passkey-options') {
        $uid = (int)($_SESSION['pending_uid'] ?? 0);
        $ids = $uid ? q('SELECT cred_id FROM passkeys WHERE user_id=?', [$uid])->fetchAll(PDO::FETCH_COLUMN) : [];
        json_out(wa_login_options($ids));
    }
    if ($r === 'login/passkey') {
        if (rate_limited('pk:' . client_ip(), 10, 900)) json_out(['error' => 'Trop de tentatives.'], 429);
        try { $pk = wa_login_verify(body()); }
        catch (RuntimeException $ex) { rate_hit('pk:' . client_ip()); json_out(['error' => 'Vérification refusée : ' . $ex->getMessage()], 401); }
        $pending = (int)($_SESSION['pending_uid'] ?? 0);
        if ($pending && $pending !== (int)$pk['user_id']) json_out(['error' => 'Cette clé appartient à un autre compte.'], 401);
        login_session((int)$pk['user_id']);
        audit((int)$pk['user_id'], 'login_passkey');
        json_out(['ok' => true]);
    }
    if ($r === 'enroll/verify') {
        $e = $_SESSION['enroll'] ?? null;
        if (!$e || time() - $e['t'] > 1800) json_out(['error' => 'Lien expiré, rouvrez le lien d\'activation.'], 400);
        if (rate_limited('enroll:' . client_ip(), 8, 900)) json_out(['error' => 'Trop de tentatives.'], 429);
        $step = totp_verify($e['secret'], in_str('code', 10));
        if (!$step) { rate_hit('enroll:' . client_ip()); json_out(['error' => 'Code incorrect. Vérifiez l\'heure de votre téléphone.'], 401); }
        db()->beginTransaction();
        if ($e['invite']) {
            $inv = q('SELECT * FROM invites WHERE id=? AND used_at IS NULL AND expires_at>?', [$e['invite'], time()])->fetch();
            if (!$inv) { db()->rollBack(); json_out(['error' => 'Invitation expirée.'], 400); }
            q('UPDATE invites SET used_at=? WHERE id=?', [time(), $inv['id']]);
        } elseif (q("SELECT 1 FROM users WHERE role='admin'")->fetchColumn()) {
            db()->rollBack(); json_out(['error' => 'Compte déjà activé.'], 400);
        }
        $ex = q('SELECT id FROM users WHERE email=?', [$e['email']])->fetchColumn();
        if ($ex) q('UPDATE users SET totp_secret=?, totp_enabled=1, totp_last_step=? WHERE id=?', [$e['secret'], $step, $ex]);
        else q('INSERT INTO users(email,name,initials,role,totp_secret,totp_enabled,totp_last_step,created_at) VALUES(?,?,?,?,?,1,?,?)',
            [$e['email'], $e['name'], $e['initials'], $e['role'], $e['secret'], $step, time()]);
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

        /* Espace personnel (privé) */
        case 'tasks/create':
            $t = in_str('title', 200);
            if ($t === '') json_out(['error' => 'Indiquez une tâche.'], 400);
            q('INSERT INTO tasks(user_id,title,created_at) VALUES(?,?,?)', [$uid, $t, time()]);
            json_out(['ok' => true]);
        case 'tasks/toggle':
            q('UPDATE tasks SET done = 1 - done WHERE id=? AND user_id=?', [in_int('id'), $uid]);
            json_out(['ok' => true]);
        case 'tasks/delete':
            q('DELETE FROM tasks WHERE id=? AND user_id=?', [in_int('id'), $uid]);
            json_out(['ok' => true]);
        case 'note/save':
            q('INSERT INTO notes(user_id,body,updated_at) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET body=excluded.body, updated_at=excluded.updated_at',
                [$uid, in_str('body', 5000), time()]);
            json_out(['ok' => true]);
    }
    json_out(['error' => 'Action inconnue'], 404);
}
