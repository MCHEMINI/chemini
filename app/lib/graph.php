<?php
declare(strict_types=1);
/* Microsoft 365 (Outlook) via Microsoft Graph — lecture seule des mails. */

function graph_enabled(): bool { return GRAPH_CLIENT_ID !== '' && GRAPH_CLIENT_SECRET !== ''; }
function graph_redirect(): string { return BASE_URL . '/microsoft/retour'; }
const GRAPH_SCOPES = 'offline_access User.Read Mail.Read';

function seal(string $plain): string {
    $key = sodium_crypto_generichash(APP_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return b64u_enc($n . sodium_crypto_secretbox($plain, $n, $key));
}
function unseal(string $enc): ?string {
    $key = sodium_crypto_generichash(APP_KEY, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    $raw = b64u_dec($enc);
    $p = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $key);
    return $p === false ? null : $p;
}

function graph_auth_url(): string {
    $_SESSION['ms_state'] = b64u_enc(random_bytes(16));
    return 'https://login.microsoftonline.com/' . rawurlencode(GRAPH_TENANT) . '/oauth2/v2.0/authorize?' . http_build_query([
        'client_id' => GRAPH_CLIENT_ID, 'response_type' => 'code', 'redirect_uri' => graph_redirect(),
        'response_mode' => 'query', 'scope' => GRAPH_SCOPES, 'state' => $_SESSION['ms_state'], 'login_hint' => current_user()['email'] ?? '',
    ]);
}

function graph_token_request(array $params): ?array {
    $ch = curl_init('https://login.microsoftonline.com/' . rawurlencode(GRAPH_TENANT) . '/oauth2/v2.0/token');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query($params + ['client_id' => GRAPH_CLIENT_ID, 'client_secret' => GRAPH_CLIENT_SECRET, 'scope' => GRAPH_SCOPES, 'redirect_uri' => graph_redirect()])]);
    $r = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    return isset($r['access_token']) ? $r : null;
}

function graph_store(int $uid, array $tok): void {
    q('INSERT INTO graph_tokens(user_id,enc,expires_at) VALUES(?,?,?) ON CONFLICT(user_id) DO UPDATE SET enc=excluded.enc, expires_at=excluded.expires_at',
        [$uid, seal(json_encode(['a' => $tok['access_token'], 'r' => $tok['refresh_token'] ?? ''])), time() + (int)$tok['expires_in'] - 60]);
}

function graph_callback(int $uid, string $code, string $state): bool {
    if (!hash_equals($_SESSION['ms_state'] ?? '', $state)) return false;
    unset($_SESSION['ms_state']);
    $tok = graph_token_request(['grant_type' => 'authorization_code', 'code' => $code]);
    if (!$tok) return false;
    graph_store($uid, $tok);
    return true;
}

function graph_access_token(int $uid): ?string {
    $row = q('SELECT * FROM graph_tokens WHERE user_id=?', [$uid])->fetch();
    if (!$row) return null;
    $t = json_decode((string)unseal($row['enc']), true);
    if (!$t) return null;
    if (time() < (int)$row['expires_at']) return $t['a'];
    $new = graph_token_request(['grant_type' => 'refresh_token', 'refresh_token' => $t['r']]);
    if (!$new) { q('DELETE FROM graph_tokens WHERE user_id=?', [$uid]); return null; }
    if (empty($new['refresh_token'])) $new['refresh_token'] = $t['r'];
    graph_store($uid, $new);
    return $new['access_token'];
}

function graph_connected(int $uid): bool { return (bool)q('SELECT 1 FROM graph_tokens WHERE user_id=?', [$uid])->fetchColumn(); }

/** Mails non lus des 14 derniers jours, les importants / signalés en premier. Cache de session 5 min. */
function graph_important_mails(int $uid, int $limit = 6): ?array {
    $ck = 'mails_' . $uid;
    if (isset($_SESSION[$ck]) && time() - $_SESSION[$ck]['t'] < 300) return $_SESSION[$ck]['d'];
    $at = graph_access_token($uid);
    if (!$at) return null;
    $since = gmdate('Y-m-d\TH:i:s\Z', time() - 14 * 86400);
    $url = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?' . http_build_query([
        '$filter' => "receivedDateTime ge $since and isRead eq false",
        '$orderby' => 'receivedDateTime desc', '$top' => 40,
        '$select' => 'subject,from,receivedDateTime,importance,flag,bodyPreview,webLink,inferenceClassification',
    ]);
    $body = http_get($url, ['Authorization: Bearer ' . $at, 'Prefer: outlook.body-content-type="text"']);
    if ($body === null) return null;
    $msgs = json_decode($body, true)['value'] ?? [];
    $rank = function ($m) {
        $s = 0;
        if (($m['importance'] ?? '') === 'high') $s += 2;
        if (($m['flag']['flagStatus'] ?? '') === 'flagged') $s += 2;
        if (($m['inferenceClassification'] ?? '') === 'focused') $s += 1;
        return $s;
    };
    $msgs = array_values(array_filter($msgs, fn($m) => $rank($m) > 0));
    usort($msgs, fn($a, $b) => [$rank($b), $b['receivedDateTime']] <=> [$rank($a), $a['receivedDateTime']]);
    $out = array_map(fn($m) => [
        'from' => $m['from']['emailAddress']['name'] ?? ($m['from']['emailAddress']['address'] ?? ''),
        'subject' => $m['subject'] ?: '(sans objet)', 'preview' => $m['bodyPreview'] ?? '',
        'date' => strtotime($m['receivedDateTime']), 'link' => $m['webLink'] ?? '',
        'level' => $rank($m) >= 2 ? 'crit' : 'warn',
    ], array_slice($msgs, 0, $limit));
    $_SESSION[$ck] = ['t' => time(), 'd' => $out, 'n' => count($msgs)];
    return $out;
}
