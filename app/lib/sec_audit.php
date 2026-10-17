<?php
declare(strict_types=1);

/*
 * Audit hebdomadaire de fonctionnement et de sécurité de la plateforme (samedi à 12 h).
 * Lancé automatiquement à la première requête qui suit l'échéance ; résultat dans la table audits (kind « securite »).
 * Aucune valeur secrète n'est stockée ni affichée : uniquement des constats.
 */

/** Dernière échéance passée : samedi 12 h. */
function sec_audit_due(): int {
    $sat = (int)date('N') === 6 && (int)date('G') >= 12 ? strtotime('today 12:00') : strtotime('last saturday 12:00');
    return (int)$sat;
}

function sec_audit_maybe(): void {
    static $done = false; if ($done) return; $done = true;
    $due = sec_audit_due();
    $last = (int)q("SELECT v FROM kv WHERE k='sec_audit_at'")->fetchColumn();
    if ($last >= $due) return;
    q("INSERT INTO kv(k,v,updated_at) VALUES('sec_audit_at',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [(string)time(), time()]);
    try { sec_audit_run(date('Y-m-d', $due)); } catch (Throwable $e) { error_log('[chemini] sec_audit ' . $e->getMessage()); }
}

function sec_audit_run(?string $period = null): array {
    $now = time(); $C = [];
    $add = function (string $g, string $lv, string $t, string $d = '', string $fix = '') use (&$C) { $C[] = ['g' => $g, 'lv' => $lv, 't' => $t, 'd' => $d, 'fix' => $fix]; };
    $days = fn(int $ts) => $ts ? (int)floor((time() - $ts) / 86400) : 9999;

    /* ---------- Sécurité ---------- */
    $https = str_starts_with(BASE_URL, 'https://');
    $add('sec', $https ? 'ok' : 'crit', 'Connexion chiffrée (HTTPS)', $https ? 'HTTPS imposé, HSTS d\'un an.' : 'Le site n\'est pas servi en HTTPS.', $https ? '' : 'Activer le certificat SSL et la redirection HTTPS.');
    $hdr = implode("\n", headers_list());
    $okH = str_contains($hdr, 'Content-Security-Policy') && str_contains($hdr, 'X-Frame-Options') && str_contains($hdr, 'X-Content-Type-Options');
    $add('sec', $okH ? 'ok' : 'warn', 'En-têtes de sécurité', $okH ? 'CSP, protection contre l\'intégration en cadre, nosniff, Referrer-Policy.' : 'Certains en-têtes de sécurité manquent.');
    $priv = is_file(dirname(APP_DIR, 2) . '/app/config.php');
    $add('sec', strlen(APP_KEY) >= 64 && $priv ? 'ok' : 'crit', 'Clé de chiffrement et secrets', $priv ? 'Clé présente dans la configuration privée, hors du dossier publié et hors du dépôt.' : 'Configuration privée absente du dossier hors web.', $priv ? '' : 'Placer la configuration privée hors du dossier publié.');
    $root = realpath(dirname(APP_DIR)) ?: ''; $data = realpath(DATA_DIR) ?: DATA_DIR;
    $outside = $root && !str_starts_with($data, $root . DIRECTORY_SEPARATOR);
    $add('sec', $outside || is_file(DATA_DIR . '/.htaccess') ? 'ok' : 'crit', 'Base de données inaccessible depuis le web', $outside ? 'Données stockées hors du dossier publié.' : 'Données dans le dossier publié, protégées par une règle d\'accès.');
    $cp = session_get_cookie_params();
    $okC = !empty($cp['httponly']) && ($cp['samesite'] ?? '') !== '' && (!$https || !empty($cp['secure']));
    $add('sec', $okC ? 'ok' : 'warn', 'Cookies de session', $okC ? 'HttpOnly, SameSite et Secure.' : 'Attributs de cookie incomplets.');
    $add('sec', version_compare(PHP_VERSION, '8.2', '>=') ? 'ok' : 'warn', 'Version de PHP', 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . (version_compare(PHP_VERSION, '8.2', '>=') ? ', maintenue.' : ', à mettre à jour.'));
    $blocks = (int)q("SELECT COUNT(*) FROM audit WHERE event LIKE 'ip_blocked:%' AND at>?", [$now - 7 * 86400])->fetchColumn();
    $add('sec', $blocks > 20 ? 'crit' : ($blocks > 0 ? 'warn' : 'ok'), 'Tentatives de connexion échouées', $blocks ? $blocks . ' blocage' . ($blocks > 1 ? 's' : '') . ' d\'adresse IP sur 7 jours (5 échecs en 30 min).' : 'Aucun blocage d\'adresse IP sur 7 jours.', $blocks > 20 ? 'Vérifier les adresses dans le journal ; envisager un filtrage chez l\'hébergeur.' : '');
    $users = q('SELECT u.id, u.name, u.totp_enabled, u.must_change, u.created_at, (SELECT COUNT(*) FROM passkeys p WHERE p.user_id=u.id) AS pk,
                (SELECT MAX(at) FROM audit a WHERE a.user_id=u.id AND a.event LIKE \'login_%\') AS last_login FROM users u ORDER BY u.id')->fetchAll();
    $weak = array_filter($users, fn($x) => !(int)$x['totp_enabled'] && !(int)$x['pk']);
    $add('sec', $weak ? 'warn' : 'ok', 'Double authentification', $weak ? 'Sans code à 6 chiffres ni Face ID : ' . implode(', ', array_column($weak, 'name')) . '.' : 'Tous les comptes ont une seconde méthode.', $weak ? 'Activer le code à 6 chiffres ou Face ID dans Sécurité.' : '');
    $prov = array_filter($users, fn($x) => (int)$x['must_change'] && $days((int)$x['created_at']) > 7);
    if ($prov) $add('sec', 'warn', 'Comptes provisoires non activés', implode(', ', array_column($prov, 'name')) . ' : mot de passe provisoire toujours en place.', 'Relancer la personne ou désactiver le compte.');
    $idle = array_filter($users, fn($x) => $x['last_login'] && $days((int)$x['last_login']) > 60);
    $add('sec', $idle ? 'warn' : 'ok', 'Comptes inactifs', $idle ? implode(', ', array_column($idle, 'name')) . ' : aucune connexion depuis plus de 60 jours.' : 'Aucun compte inactif depuis plus de 60 jours.');
    $stale = (int)q('SELECT COUNT(*) FROM trusted_devices WHERE expires_at>? AND last_seen_at<?', [$now, $now - 45 * 86400])->fetchColumn();
    $purged = q('DELETE FROM trusted_devices WHERE expires_at<?', [$now])->rowCount();
    $add('sec', $stale ? 'warn' : 'ok', 'Appareils de confiance', ($stale ? $stale . ' appareil' . ($stale > 1 ? 's' : '') . ' inutilisé' . ($stale > 1 ? 's' : '') . ' depuis 45 jours.' : 'Tous les appareils actifs ont été utilisés récemment.') . ($purged ? ' ' . $purged . ' appareil' . ($purged > 1 ? 's' : '') . ' expiré' . ($purged > 1 ? 's' : '') . ' supprimé' . ($purged > 1 ? 's' : '') . '.' : ''), $stale ? 'Révoquer les appareils inutilisés dans Sécurité.' : '');
    $inv = (int)q('SELECT COUNT(*) FROM invites WHERE used_at IS NULL AND expires_at<?', [$now])->fetchColumn();
    if ($inv) { q('DELETE FROM invites WHERE used_at IS NULL AND expires_at<?', [$now]); $add('sec', 'ok', 'Invitations expirées', $inv . ' lien' . ($inv > 1 ? 's' : '') . ' d\'invitation expiré' . ($inv > 1 ? 's' : '') . ' supprimé' . ($inv > 1 ? 's' : '') . '.'); }
    q('DELETE FROM magic_links WHERE expires_at<?', [$now]); q('DELETE FROM password_resets WHERE expires_at<?', [$now]);

    /* ---------- Fonctionnement ---------- */
    $ic = (string)q('PRAGMA quick_check')->fetchColumn();
    $add('run', $ic === 'ok' ? 'ok' : 'crit', 'Intégrité de la base de données', $ic === 'ok' ? 'Contrôle SQLite sans erreur.' : 'Anomalie détectée : ' . mb_substr($ic, 0, 120), $ic === 'ok' ? '' : 'Restaurer la dernière sauvegarde.');
    // Sauvegarde hebdomadaire de la base (8 dernières conservées)
    $bdir = DATA_DIR . '/backups'; $bk = '';
    try {
        if (!is_dir($bdir)) @mkdir($bdir, 0700, true);
        $file = $bdir . '/chemini-' . date('Y-m-d') . '.sqlite';
        if (!is_file($file)) db()->exec("VACUUM INTO '" . str_replace("'", "''", $file) . "'");
        $all = glob($bdir . '/chemini-*.sqlite') ?: []; sort($all);
        foreach (array_slice($all, 0, max(0, count($all) - 8)) as $old) @unlink($old);
        $bk = is_file($file) ? 'Sauvegarde du ' . date('d/m/Y') . ' (' . number_format(filesize($file) / 1048576, 1, ',', ' ') . ' Mo) · ' . min(8, count($all)) . ' conservées.' : '';
    } catch (Throwable $e) { $bk = ''; }
    $add('run', $bk ? 'ok' : 'crit', 'Sauvegarde de la base', $bk ?: 'La sauvegarde automatique a échoué.', $bk ? '' : 'Vérifier les droits d\'écriture du dossier de données.');
    $sz = @filesize(DATA_DIR . '/chemini.sqlite') ?: 0; $free = @disk_free_space(DATA_DIR) ?: 0;
    $add('run', $free && $free < 300 * 1048576 ? 'warn' : 'ok', 'Espace disque', 'Base de ' . number_format($sz / 1048576, 1, ',', ' ') . ' Mo' . ($free ? ' · ' . number_format($free / 1073741824, 1, ',', ' ') . ' Go libres.' : '.'));
    $io = (string)q("SELECT v FROM kv WHERE k='immopen_at'")->fetchColumn(); $ioD = $io ? $days((int)strtotime($io)) : 9999;
    $add('run', $ioD > 35 ? 'warn' : 'ok', 'Relevé ImmOpen (planning, immeubles)', $io ? 'Dernier relevé le ' . date('d/m/Y', strtotime($io)) . '.' : 'Aucun relevé enregistré.', $ioD > 35 ? 'Relancer le relevé mensuel ImmOpen.' : '');
    $cr = (int)q('SELECT MAX(created_at) FROM compta_runs')->fetchColumn();
    $add('run', $days($cr) > 8 ? 'warn' : 'ok', 'Contrôle comptable du vendredi', $cr ? 'Dernier contrôle le ' . date('d/m/Y', $cr) . '.' : 'Aucun contrôle enregistré.', $days($cr) > 8 ? 'Le contrôle du vendredi 19 h n\'a pas tourné.' : '');
    $vl = (int)q('SELECT MAX(created_at) FROM veille')->fetchColumn();
    $add('run', $days($vl) > 8 ? 'warn' : 'ok', 'Veille hebdomadaire', $vl ? 'Dernière mise à jour le ' . date('d/m/Y', $vl) . '.' : 'Aucune veille enregistrée.');
    $ah = (int)q("SELECT MAX(created_at) FROM audits WHERE kind='honoraires'")->fetchColumn();
    $add('run', $days($ah) > 8 ? 'warn' : 'ok', 'Contrôle hebdomadaire des honoraires', $ah ? 'Dernier contrôle le ' . date('d/m/Y', $ah) . '.' : 'Aucun contrôle enregistré.', $days($ah) > 8 ? 'Le contrôle du lundi 7 h n\'a pas tourné.' : '');
    $hd = (string)q("SELECT v FROM kv WHERE k='hono_daily'")->fetchColumn();
    $add('run', $hd && $days((int)strtotime($hd)) <= 1 ? 'ok' : 'warn', 'Mise à jour quotidienne de 8 h', $hd ? 'Dernière mise à jour le ' . date('d/m/Y', strtotime($hd)) . '.' : 'Pas encore exécutée.');
    $stuck = (int)q("SELECT COUNT(*) FROM consignes WHERE status='doing' AND updated_at<?", [$now - 86400])->fetchColumn();
    $late = (int)q("SELECT COUNT(*) FROM consignes WHERE status='todo' AND created_at<?", [$now - 2 * 86400])->fetchColumn();
    $add('run', $stuck ? 'warn' : 'ok', 'Tâches IA', ($stuck ? $stuck . ' en cours depuis plus de 24 h' : 'Aucune tâche bloquée') . ($late ? ' · ' . $late . ' en attente depuis plus de 48 h.' : '.'), $stuck || $late ? 'Vérifier la tâche planifiée horaire des consignes.' : '');
    $errs = 0; $log = ini_get('error_log');
    if ($log && is_readable($log) && filesize($log) < 50 * 1048576) {
        $since = $now - 7 * 86400; foreach (array_slice(file($log, FILE_IGNORE_NEW_LINES) ?: [], -5000) as $l) if (str_contains($l, '[chemini]') && preg_match('/^\[([^\]]+)\]/', $l, $m) && strtotime($m[1]) >= $since) $errs++;
        $add('run', $errs > 20 ? 'warn' : 'ok', 'Erreurs applicatives', $errs ? $errs . ' erreur' . ($errs > 1 ? 's' : '') . ' sur 7 jours.' : 'Aucune erreur sur 7 jours.');
    }

    $n = ['crit' => 0, 'warn' => 0, 'ok' => 0]; foreach ($C as $c) $n[$c['lv']]++;
    $score = max(0, 100 - 15 * $n['crit'] - 4 * $n['warn']);
    $headline = $n['crit'] ? $n['crit'] . ' point' . ($n['crit'] > 1 ? 's' : '') . ' critique' . ($n['crit'] > 1 ? 's' : '') . ' à traiter' : ($n['warn'] ? $n['warn'] . ' point' . ($n['warn'] > 1 ? 's' : '') . ' à surveiller' : 'Aucun point d\'attention');
    $period = $period ?: date('Y-m-d');
    $data = ['score' => $score, 'n' => $n, 'checks' => $C, 'at' => $now];
    q('INSERT INTO audits(kind,period,headline,data,created_at) VALUES(\'securite\',?,?,?,?) ON CONFLICT(kind,period) DO UPDATE SET headline=excluded.headline, data=excluded.data, created_at=excluded.created_at',
      [$period, $headline, json_encode($data, JSON_UNESCAPED_UNICODE), $now]);
    audit(null, 'sec_audit:' . $period);
    return $data;
}
