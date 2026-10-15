<?php
declare(strict_types=1);

function page_enroll(string $token): never {
    $ctx = null;
    if ($token !== '' && hash_equals(SETUP_TOKEN, $token) && !q("SELECT 1 FROM users WHERE role='admin'")->fetchColumn()) {
        $ctx = ['email' => ADMIN_EMAIL, 'name' => ADMIN_NAME, 'initials' => ADMIN_INITIALS, 'role' => 'admin', 'invite' => 0];
    } elseif ($token !== '') {
        $inv = q('SELECT * FROM invites WHERE token_hash=? AND used_at IS NULL AND expires_at>?', [hash('sha256', $token), time()])->fetch();
        if ($inv) $ctx = ['email' => $inv['email'], 'name' => $inv['name'], 'initials' => $inv['initials'], 'role' => $inv['role'], 'invite' => (int)$inv['id']];
    }
    if (!$ctx) { http_response_code(410); render('notfound', ['title' => 'Lien expiré', 'message' => 'Ce lien d\'activation n\'est plus valable. Demandez-en un nouveau.'], false); }

    $e = $_SESSION['enroll'] ?? null;
    if (!$e || $e['email'] !== $ctx['email'] || time() - $e['t'] > 1800) {
        $e = $ctx + ['secret' => totp_new_secret(), 't' => time()];
        $_SESSION['enroll'] = $e;
    }
    render('enroll', ['title' => 'Activation', 'enroll' => $e], false);
}
