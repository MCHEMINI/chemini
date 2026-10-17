<?php
declare(strict_types=1);

const TRUST_COOKIE = 'gid_trust';

function session_boot(): void {
    $secure = str_starts_with(BASE_URL, 'https://');
    session_name('gid_sess');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.save_path', DATA_DIR . '/sessions');
    if (!is_dir(DATA_DIR . '/sessions')) mkdir(DATA_DIR . '/sessions', 0750, true);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = b64u_enc(random_bytes(24));
    // Expiration d'inactivité d'une session non « appareil de confiance » : 12 h
    if (isset($_SESSION['uid'], $_SESSION['seen']) && time() - $_SESSION['seen'] > 43200) { $_SESSION = []; session_regenerate_id(true); }
    $_SESSION['seen'] = time();
}

function current_user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = q('SELECT * FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null;
    }
    if (!$u && !empty($_COOKIE[TRUST_COOKIE])) {
        $row = q('SELECT * FROM trusted_devices WHERE token_hash=? AND expires_at>?', [hash('sha256', $_COOKIE[TRUST_COOKIE]), time()])->fetch();
        if ($row) {
            $u = q('SELECT * FROM users WHERE id=?', [$row['user_id']])->fetch() ?: null;
            if ($u) {
                login_session((int)$u['id']);
                $_SESSION['device_id'] = (int)$row['id'];
                q('UPDATE trusted_devices SET last_seen_at=?, ip=? WHERE id=?', [time(), client_ip(), $row['id']]);
            }
        } else {
            clear_trust_cookie();
        }
    }
    if ($u && !empty($u['disabled_at'])) { unset($_SESSION['uid']); $u = null; } // collaborateur sorti de l'annuaire ImmOpen
    return $u;
}

function login_session(int $uid): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = $uid;
    $_SESSION['seen'] = time();
    $_SESSION['csrf'] = b64u_enc(random_bytes(24));
    unset($_SESSION['pending_uid'], $_SESSION['pending_email']);
}

function trust_this_device(int $uid): void {
    $tok = b64u_enc(random_bytes(32));
    $exp = time() + TRUST_DAYS * 86400;
    q('INSERT INTO trusted_devices(user_id,token_hash,label,ip,created_at,last_seen_at,expires_at) VALUES(?,?,?,?,?,?,?)',
        [$uid, hash('sha256', $tok), device_label(), client_ip(), time(), time(), $exp]);
    $_SESSION['device_id'] = (int)db()->lastInsertId();
    setcookie(TRUST_COOKIE, $tok, ['expires' => $exp, 'path' => '/', 'secure' => str_starts_with(BASE_URL, 'https://'), 'httponly' => true, 'samesite' => 'Lax']);
}

function clear_trust_cookie(): void {
    setcookie(TRUST_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => str_starts_with(BASE_URL, 'https://'), 'httponly' => true, 'samesite' => 'Lax']);
}

function device_label(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $os = match (true) {
        str_contains($ua, 'iPhone') => 'iPhone', str_contains($ua, 'iPad') => 'iPad',
        str_contains($ua, 'Android') => 'Android', str_contains($ua, 'Mac OS') => 'Mac',
        str_contains($ua, 'Windows') => 'PC Windows', default => 'Appareil',
    };
    $br = match (true) {
        str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'Brave') => 'Brave', str_contains($ua, 'Firefox') => 'Firefox',
        str_contains($ua, 'Chrome') => 'Chrome', str_contains($ua, 'Safari') => 'Safari', default => 'navigateur',
    };
    return "$os · $br";
}

function logout(): void {
    if (!empty($_COOKIE[TRUST_COOKIE])) {
        q('DELETE FROM trusted_devices WHERE token_hash=?', [hash('sha256', $_COOKIE[TRUST_COOKIE])]);
        clear_trust_cookie();
    }
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_user(): array {
    $u = current_user();
    if (!$u) { redirect('/connexion'); }
    if (function_exists('sec_audit_maybe')) sec_audit_maybe();
    return $u;
}

function csrf_check(): void {
    $t = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['csrf'] ?? '');
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) json_out(['error' => 'Session expirée, rechargez la page.'], 403);
}
