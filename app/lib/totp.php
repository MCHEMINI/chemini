<?php
declare(strict_types=1);

const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function b32_encode(string $bin): string {
    $bits = '';
    foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 5) as $chunk) $out .= B32[bindec(str_pad($chunk, 5, '0'))];
    return $out;
}

function b32_decode(string $s): string {
    $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
    $bits = '';
    foreach (str_split($s) as $c) $bits .= str_pad(decbin(strpos(B32, $c)), 5, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
    return $out;
}

function totp_new_secret(): string { return b32_encode(random_bytes(20)); }

function totp_code(string $secretB32, int $step): string {
    $key = b32_decode($secretB32);
    $msg = pack('N*', 0) . pack('N*', $step);
    $h = hash_hmac('sha1', $msg, $key, true);
    $o = ord($h[19]) & 0x0f;
    $n = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
}

/** Retourne le pas de temps accepté (fenêtre ±1, soit ±30 s) ou null. */
function totp_verify(string $secretB32, string $code, int $lastStep = 0): ?int {
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return null;
    $now = intdiv(time(), 30);
    for ($i = -1; $i <= 1; $i++) {
        $s = $now + $i;
        if ($s <= $lastStep) continue; // anti-rejeu
        if (hash_equals(totp_code($secretB32, $s), $code)) return $s;
    }
    return null;
}

function totp_uri(string $secret, string $email): string {
    return 'otpauth://totp/' . rawurlencode(APP_NAME . ':' . $email)
        . '?secret=' . $secret . '&issuer=' . rawurlencode(APP_NAME) . '&algorithm=SHA1&digits=6&period=30';
}
