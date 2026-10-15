<?php
declare(strict_types=1);
/*
 * WebAuthn (clés d'accès / Face ID) — implémentation autonome, sans dépendance.
 * Attestation "none", algorithmes ES256 (-7) et RS256 (-257).
 */

function b64u_enc(string $b): string { return rtrim(strtr(base64_encode($b), '+/', '-_'), '='); }
function b64u_dec(string $s): string { return base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4)); }

/* ---------- CBOR minimal ---------- */
final class Cbor {
    private int $p = 0;
    private function __construct(private string $d) {}
    public static function decode(string $d, ?int &$end = null) { $c = new self($d); $v = $c->item(); $end = $c->p; return $v; }
    private function byte(): int { if ($this->p >= strlen($this->d)) throw new RuntimeException('CBOR tronqué'); return ord($this->d[$this->p++]); }
    private function bytes(int $n): string { if ($this->p + $n > strlen($this->d)) throw new RuntimeException('CBOR tronqué'); $s = substr($this->d, $this->p, $n); $this->p += $n; return $s; }
    private function len(int $ai): int {
        if ($ai < 24) return $ai;
        return match ($ai) {
            24 => $this->byte(),
            25 => unpack('n', $this->bytes(2))[1],
            26 => unpack('N', $this->bytes(4))[1],
            27 => (int)unpack('J', $this->bytes(8))[1],
            default => throw new RuntimeException('CBOR longueur invalide'),
        };
    }
    private function item() {
        $ib = $this->byte(); $mt = $ib >> 5; $ai = $ib & 31;
        switch ($mt) {
            case 0: return $this->len($ai);
            case 1: return -1 - $this->len($ai);
            case 2: return ['__bytes' => $this->bytes($this->len($ai))];
            case 3: return $this->bytes($this->len($ai));
            case 4: $n = $this->len($ai); $a = []; for ($i = 0; $i < $n; $i++) $a[] = $this->item(); return $a;
            case 5: $n = $this->len($ai); $m = []; for ($i = 0; $i < $n; $i++) { $k = $this->item(); $m[is_array($k) ? 'b' : $k] = $this->item(); } return $m;
            case 6: $this->len($ai); return $this->item();
            case 7:
                if ($ai === 20) return false; if ($ai === 21) return true; if ($ai === 22 || $ai === 23) return null;
                if ($ai === 25) { $this->bytes(2); return 0.0; } if ($ai === 26) return unpack('G', $this->bytes(4))[1]; if ($ai === 27) return unpack('E', $this->bytes(8))[1];
                return null;
        }
        throw new RuntimeException('CBOR type inconnu');
    }
}
function cbor_bytes($v): string { if (!is_array($v) || !isset($v['__bytes'])) throw new RuntimeException('octets attendus'); return $v['__bytes']; }

/* ---------- DER helpers ---------- */
function der_len(int $l): string { if ($l < 128) return chr($l); $s = ltrim(pack('N', $l), "\0"); return chr(0x80 | strlen($s)) . $s; }
function der(int $tag, string $v): string { return chr($tag) . der_len(strlen($v)) . $v; }
function der_uint(string $b): string { $b = ltrim($b, "\0"); if ($b === '' || ord($b[0]) & 0x80) $b = "\0" . $b; return der(0x02, $b); }

function cose_to_pem(array $k): array {
    $kty = $k[1] ?? null; $alg = $k[3] ?? null;
    if ($kty === 2 && $alg === -7 && ($k[-1] ?? null) === 1) {
        $x = cbor_bytes($k[-2]); $y = cbor_bytes($k[-3]);
        if (strlen($x) !== 32 || strlen($y) !== 32) throw new RuntimeException('clé EC invalide');
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . "\x04" . $x . $y;
    } elseif ($kty === 3 && $alg === -257) {
        $n = cbor_bytes($k[-1]); $e = cbor_bytes($k[-2]);
        $rsa = der(0x30, der_uint($n) . der_uint($e));
        $der = der(0x30, der(0x30, hex2bin('06092a864886f70d0101010500')) . der(0x03, "\0" . $rsa));
    } else {
        throw new RuntimeException('algorithme non pris en charge');
    }
    return [$alg, "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n"];
}

/* ---------- Cérémonies ---------- */
function wa_rp_id(): string { return parse_url(BASE_URL, PHP_URL_HOST); }
function wa_origin(): string { $u = parse_url(BASE_URL); return $u['scheme'] . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : ''); }

function wa_check_client_data(string $cdj, string $type, string $challenge): void {
    $c = json_decode($cdj, true);
    if (!is_array($c)) throw new RuntimeException('clientData invalide');
    if (($c['type'] ?? '') !== $type) throw new RuntimeException('type inattendu');
    if (!hash_equals(b64u_enc($challenge), (string)($c['challenge'] ?? ''))) throw new RuntimeException('défi invalide');
    if (($c['origin'] ?? '') !== wa_origin()) throw new RuntimeException('origine invalide');
}

function wa_parse_auth_data(string $ad): array {
    if (strlen($ad) < 37) throw new RuntimeException('authData trop court');
    $r = ['rpIdHash' => substr($ad, 0, 32), 'flags' => ord($ad[32]), 'signCount' => unpack('N', substr($ad, 33, 4))[1]];
    if (!hash_equals(hash('sha256', wa_rp_id(), true), $r['rpIdHash'])) throw new RuntimeException('rpId invalide');
    if (!($r['flags'] & 0x01)) throw new RuntimeException('présence utilisateur absente');
    if (!($r['flags'] & 0x04)) throw new RuntimeException('vérification biométrique absente');
    if ($r['flags'] & 0x40) {
        $credLen = unpack('n', substr($ad, 53, 2))[1];
        $r['credId'] = substr($ad, 55, $credLen);
        $r['cose'] = Cbor::decode(substr($ad, 55 + $credLen));
    }
    return $r;
}

function wa_registration_options(array $user, array $existingCredIds): array {
    $ch = random_bytes(32);
    $_SESSION['wa_reg'] = ['c' => $ch, 'u' => $user['id'], 't' => time()];
    return [
        'challenge' => b64u_enc($ch),
        'rp' => ['name' => APP_NAME, 'id' => wa_rp_id()],
        'user' => ['id' => b64u_enc(pack('J', $user['id'])), 'name' => $user['email'], 'displayName' => $user['name']],
        'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7], ['type' => 'public-key', 'alg' => -257]],
        'timeout' => 60000,
        'attestation' => 'none',
        'authenticatorSelection' => ['residentKey' => 'preferred', 'userVerification' => 'required'],
        'excludeCredentials' => array_map(fn($id) => ['type' => 'public-key', 'id' => $id], $existingCredIds),
    ];
}

/** @return array{credId:string,pem:string,signCount:int} */
function wa_registration_verify(array $in): array {
    $s = $_SESSION['wa_reg'] ?? null; unset($_SESSION['wa_reg']);
    if (!$s || time() - $s['t'] > 300) throw new RuntimeException('session expirée');
    $cdj = b64u_dec($in['clientDataJSON'] ?? '');
    wa_check_client_data($cdj, 'webauthn.create', $s['c']);
    $att = Cbor::decode(b64u_dec($in['attestationObject'] ?? ''));
    $ad = wa_parse_auth_data(cbor_bytes($att['authData'] ?? null));
    if (empty($ad['credId'])) throw new RuntimeException('identifiant absent');
    [, $pem] = cose_to_pem($ad['cose']);
    return ['credId' => b64u_enc($ad['credId']), 'pem' => $pem, 'signCount' => $ad['signCount']];
}

function wa_login_options(array $credIds): array {
    $ch = random_bytes(32);
    $_SESSION['wa_auth'] = ['c' => $ch, 't' => time()];
    return [
        'challenge' => b64u_enc($ch), 'timeout' => 60000, 'rpId' => wa_rp_id(), 'userVerification' => 'required',
        'allowCredentials' => array_map(fn($id) => ['type' => 'public-key', 'id' => $id], $credIds),
    ];
}

/** Vérifie une assertion ; retourne la ligne passkey mise à jour. */
function wa_login_verify(array $in): array {
    $s = $_SESSION['wa_auth'] ?? null; unset($_SESSION['wa_auth']);
    if (!$s || time() - $s['t'] > 300) throw new RuntimeException('session expirée');
    $pk = q('SELECT * FROM passkeys WHERE cred_id=?', [(string)($in['id'] ?? '')])->fetch();
    if (!$pk) throw new RuntimeException('clé inconnue');
    $cdj = b64u_dec($in['clientDataJSON'] ?? '');
    wa_check_client_data($cdj, 'webauthn.get', $s['c']);
    $adRaw = b64u_dec($in['authenticatorData'] ?? '');
    $ad = wa_parse_auth_data($adRaw);
    $sig = b64u_dec($in['signature'] ?? '');
    $ok = openssl_verify($adRaw . hash('sha256', $cdj, true), $sig, $pk['public_key_pem'], OPENSSL_ALGO_SHA256);
    if ($ok !== 1) throw new RuntimeException('signature invalide');
    if ($ad['signCount'] > 0 && $ad['signCount'] <= (int)$pk['sign_count']) throw new RuntimeException('compteur incohérent (clé clonée ?)');
    q('UPDATE passkeys SET sign_count=?, last_used_at=? WHERE id=?', [$ad['signCount'], time(), $pk['id']]);
    return $pk;
}
