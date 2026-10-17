<?php
declare(strict_types=1);

function page_security(array $u): never {
    $uid = (int)$u['id'];
    render('security', [
        'title' => 'Sécurité', 'nav' => 'secu', 'u' => $u,
        'passkeys' => q('SELECT * FROM passkeys WHERE user_id=? ORDER BY created_at', [$uid])->fetchAll(),
        'devices' => q('SELECT * FROM trusted_devices WHERE user_id=? AND expires_at>? ORDER BY last_seen_at DESC', [$uid, time()])->fetchAll(),
        'currentDevice' => (int)($_SESSION['device_id'] ?? 0),
        'msConnected' => graph_connected($uid), 'msEnabled' => graph_enabled(), 'msError' => isset($_GET['ms']),
        'team' => $u['role'] === 'admin' ? users_all() : [],
        'access' => $u['role'] === 'admin' ? staff_access() : [],
        'sync' => $u['role'] === 'admin' ? (json_decode((string)q("SELECT v FROM kv WHERE k='staff_sync'")->fetchColumn(), true) ?: null) : null,
        'secAudits' => $u['role'] === 'admin' ? array_map(fn($a) => $a + ['d' => json_decode($a['data'], true) ?: []], q("SELECT * FROM audits WHERE kind='securite' ORDER BY period DESC LIMIT 8")->fetchAll()) : [],
        'invites' => $u['role'] === 'admin' ? q('SELECT * FROM invites WHERE used_at IS NULL AND expires_at>? ORDER BY id DESC', [time()])->fetchAll() : [],
    ]);
}
