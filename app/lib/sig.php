<?php
declare(strict_types=1);

/* Bandeau des signatures e-mail : seul le bandeau change, servi à une adresse fixe utilisée par Exclaimer. */

function sig_dir(): string { $d = DATA_DIR . '/banners'; if (!is_dir($d)) @mkdir($d, 0700, true); return $d; }

function sig_all(): array { return q('SELECT b.*, u.name AS by_name FROM banners b LEFT JOIN users u ON u.id=b.created_by ORDER BY COALESCE(b.start, \'0000\') DESC, b.created_at DESC')->fetchAll(); }

/** Bandeau du jour : le plus récent dont la période couvre aujourd'hui, sinon le bandeau par défaut. */
function sig_current(): ?array {
    $t = date('Y-m-d');
    $b = q('SELECT * FROM banners WHERE (start IS NULL OR start<=?) AND (end IS NULL OR end>=?) AND (start IS NOT NULL OR end IS NOT NULL) ORDER BY COALESCE(start, \'0000\') DESC, created_at DESC LIMIT 1', [$t, $t])->fetch();
    return $b ?: (q('SELECT * FROM banners WHERE is_default=1 ORDER BY created_at DESC LIMIT 1')->fetch() ?: null);
}

function sig_state(array $b): array {
    $t = date('Y-m-d'); $cur = sig_current();
    if ($cur && (int)$cur['id'] === (int)$b['id']) return ['En cours d\'envoi', 'ok'];
    if ($b['start'] && $b['start'] > $t) return ['Programmé', 'warn'];
    if ($b['end'] && $b['end'] < $t) return ['Terminé', 'neutral'];
    return [$b['is_default'] ? 'Par défaut' : 'En attente', 'neutral'];
}

/** Image publique (adresse fixe pour Exclaimer). */
/** Mémorise qui lit le bandeau : prouve qu'Exclaimer (ou les messageries des destinataires) est bien relié à Chemini. */
function sig_track(): void {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $ext = $ua === '' || preg_match('/exclaimer|microsoft office|outlook|ms-office|googleimageproxy|yahoomailproxy|apple ?mail|thunderbird|proxy|bot/i', $ua);
    $h = json_decode((string)q("SELECT v FROM kv WHERE k='sig_hits'")->fetchColumn(), true) ?: ['n' => 0];
    $h['n'] = (int)$h['n'] + 1; $h['last'] = time();
    if ($ext) { $h['ext'] = time(); $h['ext_ua'] = mb_substr($ua ?: 'service sans nom', 0, 120); $h['ext_n'] = (int)($h['ext_n'] ?? 0) + 1; }
    q("INSERT INTO kv(k,v,updated_at) VALUES('sig_hits',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode($h, JSON_UNESCAPED_UNICODE), time()]);
}
function sig_serve_image(): never {
    try { sig_track(); } catch (Throwable) {}
    $b = sig_current(); $f = $b ? sig_fit(sig_dir() . '/' . basename($b['file']), (string)$b['mime']) : '';
    if (!$b || !is_file($f)) { header('Content-Type: image/gif'); header('Cache-Control: no-cache'); echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); exit; }
    header('Content-Type: ' . (str_ends_with($f, '.jpg') ? 'image/jpeg' : (str_ends_with($f, '.png') ? 'image/png' : $b['mime']))); header('Cache-Control: public, max-age=900'); header('Content-Length: ' . filesize($f));
    header('ETag: "' . md5($b['id'] . '|' . filemtime($f)) . '"'); header_remove('Content-Security-Policy');
    readfile($f); exit;
}
/** Version au format exact de la signature (600 × 200, recadrée au centre si le ratio diffère) : Exclaimer affiche l'image liée à sa taille réelle. */
const SIG_W = 600, SIG_H = 200;
function sig_fit(string $f, string $mime): string {
    if (!is_file($f) || !function_exists('imagecreatefromstring')) return $f;
    $out = $f . '.' . SIG_W . 'x' . SIG_H . ($mime === 'image/jpeg' ? '.jpg' : '.png');
    if (is_file($out) && filemtime($out) >= filemtime($f)) return $out;
    $src = @imagecreatefromstring((string)file_get_contents($f)); if (!$src) return $f;
    $w = imagesx($src); $h = imagesy($src);
    if ($w === SIG_W && $h === SIG_H) { imagedestroy($src); return $f; }
    $r = SIG_W / SIG_H; // recadrage centré au ratio 3:1
    if ($w / $h > $r) { $cw = (int)round($h * $r); $ch = $h; $cx = (int)(($w - $cw) / 2); $cy = 0; }
    else { $cw = $w; $ch = (int)round($w / $r); $cx = 0; $cy = (int)(($h - $ch) / 2); }
    $dst = imagecreatetruecolor(SIG_W, SIG_H); imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, $cx, $cy, SIG_W, SIG_H, $cw, $ch);
    $ok = $mime === 'image/jpeg' ? imagejpeg($dst, $out, 90) : imagepng($dst, $out, 6);
    imagedestroy($src); imagedestroy($dst);
    return $ok ? $out : $f;
}
function sig_redirect(): never {
    $b = sig_current(); $u = $b && $b['link'] ? $b['link'] : 'https://www.gidsas.fr/';
    header('Location: ' . $u, true, 302); header('Cache-Control: no-cache'); exit;
}
