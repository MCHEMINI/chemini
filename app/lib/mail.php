<?php
declare(strict_types=1);
/*
 * Envoi d'e-mails. Si SMTP_HOST est configuré (ex. smtp.office365.com, port 587, STARTTLS),
 * l'envoi est authentifié ; sinon la fonction mail() de l'hébergement est utilisée.
 */

function send_mail(string $to, string $subject, string $text, string $html): bool {
    $from = MAIL_FROM;
    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = [
        'From: ' . mb_encode_mimeheader(APP_NAME) . " <$from>",
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        'X-Auto-Response-Suppress: All',
    ];
    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
          . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
          . "--$boundary--\r\n";
    $subj = mb_encode_mimeheader($subject, 'UTF-8');

    if (SMTP_HOST === '') {
        return @mail($to, $subj, $body, implode("\r\n", $headers), '-f' . $from);
    }
    return smtp_send($to, $subj, $body, $headers);
}

function smtp_send(string $to, string $subj, string $body, array $headers): bool {
    $s = @stream_socket_client('tcp://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $err, 10);
    if (!$s) { error_log("[chemini] smtp connect: $err"); return false; }
    stream_set_timeout($s, 15);
    $read = function () use ($s) { $r = ''; while (($l = fgets($s, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
    $cmd = function (string $c, array $ok) use ($s, $read) {
        if ($c !== '') fwrite($s, $c . "\r\n");
        $r = $read();
        if (!in_array((int)substr($r, 0, 3), $ok, true)) throw new RuntimeException('SMTP: ' . trim($r));
        return $r;
    };
    try {
        $cmd('', [220]);
        $host = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';
        $cmd("EHLO $host", [250]);
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($s, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) throw new RuntimeException('TLS');
        $cmd("EHLO $host", [250]);
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode(SMTP_USER), [334]);
        $cmd(base64_encode(SMTP_PASS), [235]);
        $cmd('MAIL FROM:<' . MAIL_FROM . '>', [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        $msg = implode("\r\n", array_merge($headers, ["To: <$to>", "Subject: $subj", 'Date: ' . date('r')])) . "\r\n\r\n" . $body;
        $msg = preg_replace('/^\./m', '..', $msg);
        $cmd($msg . "\r\n.", [250]);
        $cmd('QUIT', [221]);
        fclose($s);
        return true;
    } catch (Throwable $e) {
        error_log('[chemini] ' . $e->getMessage());
        fclose($s);
        return false;
    }
}

function mail_template(string $title, string $intro, string $button, string $link): array {
    $l = htmlspecialchars($link, ENT_QUOTES);
    $html = "<div style=\"font-family:Georgia,serif;max-width:480px;margin:0 auto;padding:32px;color:#151A22\">"
          . "<p style=\"font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:#8A6A3B\">GID SAS · Chemini</p>"
          . "<h1 style=\"font-weight:400;font-size:26px\">" . htmlspecialchars($title) . "</h1>"
          . "<p style=\"font-family:Arial,sans-serif;font-size:15px;color:#52514e\">" . htmlspecialchars($intro) . "</p>"
          . "<p><a href=\"$l\" style=\"display:inline-block;background:#151A22;color:#F3F3F0;padding:14px 22px;border-radius:6px;text-decoration:none;font-family:Arial,sans-serif;font-weight:bold\">" . htmlspecialchars($button) . "</a></p>"
          . "<p style=\"font-family:Arial,sans-serif;font-size:12px;color:#85837d\">Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p></div>";
    $text = "$title\n\n$intro\n$link\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";
    return [$text, $html];
}

function send_magic_link(array $user, string $link): bool {
    [$text, $html] = mail_template('Votre lien de connexion', 'Valable 15 minutes, utilisable une seule fois.', 'Se connecter', $link);
    return send_mail($user['email'], 'Votre lien de connexion · ' . APP_NAME, $text, $html);
}

function send_reset_link(array $user, string $link): bool {
    [$text, $html] = mail_template('Réinitialiser votre mot de passe', 'Ce lien est valable 30 minutes et utilisable une seule fois. Votre code à 6 chiffres restera demandé à la connexion.', 'Choisir un nouveau mot de passe', $link);
    return send_mail($user['email'], 'Réinitialisation du mot de passe · ' . APP_NAME, $text, $html);
}
