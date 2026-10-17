<?php
declare(strict_types=1);

/* Bandeau des signatures e-mail : seul le bandeau change, servi à une adresse fixe utilisée par Exclaimer. */

const SIG_LINK = 'https://www.gidsas.fr/'; // lien au clic de tous les bandeaux

function sig_dir(): string { $d = DATA_DIR . '/banners'; if (!is_dir($d)) @mkdir($d, 0700, true); return $d; }

function sig_all(): array { return q('SELECT b.*, u.name AS by_name FROM banners b LEFT JOIN users u ON u.id=b.created_by ORDER BY COALESCE(b.start, \'0000\') DESC, b.created_at DESC')->fetchAll(); }

/** Bandeau d'un jour donné : brouillon activé à la main > période qui couvre le jour > visuel du mois (rotation) > bandeau par défaut. */
function sig_for(string $day, bool $withActive = true): ?array {
    $b = $withActive ? q('SELECT * FROM banners WHERE active_at IS NOT NULL ORDER BY active_at DESC LIMIT 1')->fetch() : null;
    $b = $b ?: q('SELECT * FROM banners WHERE (start IS NULL OR start<=?) AND (end IS NULL OR end>=?) AND (start IS NOT NULL OR end IS NOT NULL) ORDER BY COALESCE(start, \'0000\') DESC, created_at DESC LIMIT 1', [$day, $day])->fetch();
    if (!$b) { $pool = sig_pool(); if ($pool) $b = $pool[((int)substr($day, 0, 4) * 12 + (int)substr($day, 5, 2) - 1) % count($pool)]; }
    return $b ?: (q('SELECT * FROM banners WHERE is_default=1 ORDER BY created_at DESC LIMIT 1')->fetch() ?: null);
}
/** Visuels en rotation mensuelle, dans l'ordre de passage. */
function sig_pool(): array { static $p = null; return $p ??= q('SELECT * FROM banners WHERE rotation=1 AND is_default=0 AND start IS NULL AND end IS NULL ORDER BY rot_order, id')->fetchAll(); }
function sig_current(): ?array { static $c = false; return $c !== false ? $c : ($c = sig_for(date('Y-m-d'))); }

/** Calendrier : bandeau envoyé le 1er et le 15 de chaque mois sur 12 mois (hors brouillon activé). */
function sig_calendar(int $months = 12): array {
    $out = [];
    for ($i = 0; $i < $months; $i++) {
        $m = date('Y-m', strtotime(date('Y-m-01') . " +$i month")); $days = [];
        foreach (['01', '08', '15', '22'] as $d) { $b = sig_for("$m-$d", false); if ($b) $days[$b['id']] = $b; }
        foreach (q('SELECT * FROM banners WHERE start IS NOT NULL AND start<=? AND end>=?', [$m . '-31', $m . '-01'])->fetchAll() as $b) $days[$b['id']] = $b;
        $out[$m] = array_values($days);
    }
    return $out;
}

/** Type d'un bandeau : défaut, période ou brouillon. */
function sig_mode(array $b): string { return $b['is_default'] ? 'default' : (($b['start'] || $b['end']) ? 'period' : (!empty($b['rotation']) ? 'rotation' : 'draft')); }

function sig_state(array $b): array {
    $t = date('Y-m-d'); $cur = sig_current(); $on = $cur && (int)$cur['id'] === (int)$b['id'];
    if ($b['active_at']) return [$on ? 'Activé · en cours d\'envoi' : 'Activé', 'ok'];
    if ($on) return ['En cours d\'envoi', 'ok'];
    if ($b['is_default']) return ['Par défaut', 'neutral'];
    if ($b['end'] && $b['end'] < $t) return ['Terminé', 'muted'];
    if ($b['start'] || $b['end']) return ['Programmé', 'warn'];
    if (!empty($b['rotation'])) return ['Rotation mensuelle', 'neutral'];
    return ['Brouillon', 'muted'];
}

/** Image publique (adresse fixe pour Exclaimer). */
/** Mémorise qui lit le bandeau : prouve qu'Exclaimer (ou les messageries des destinataires) est bien relié à Dashboard. */
function sig_track(): void {
    if (isset($_GET['v'])) return; // aperçu affiché dans Dashboard : ne compte pas
    $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160); $ref = mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 160);
    $h = json_decode((string)q("SELECT v FROM kv WHERE k='sig_hits'")->fetchColumn(), true) ?: [];
    if (time() - (int)($h['ext'] ?? 0) < 60 && stripos($ref . $ua, 'exclaimer') === false) return; // au plus une écriture par minute : pas d'écritures en rafale sur une adresse publique
    // Toute lecture de l'adresse publique hors de Dashboard = Exclaimer (éditeur, serveurs d'envoi) ou messagerie d'un destinataire
    $h['ext'] = time(); $h['ext_n'] = (int)($h['ext_n'] ?? 0) + 1; $h['ext_ua'] = $ua ?: 'service sans nom';
    if (stripos($ref . $ua, 'exclaimer') !== false) $h['exclaimer'] = time();
    $h['log'] = array_slice(array_merge([[time(), $ua, $ref]], (array)($h['log'] ?? [])), 0, 10);
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
    $u = SIG_LINK; // lien au clic : toujours le site de GID
    header('Location: ' . $u, true, 302); header('Cache-Control: no-cache'); exit;
}

/** Enregistre un bandeau (création ou modification). Modes : default, period, rotation, draft. Toujours un bandeau par défaut. */
function sig_save(?array $old, string $raw, string $mode, string $st, string $en, string $t, ?int $uid): array {
    $info = null; $bin = null;
    if ($raw !== '' || !$old) {
        if (!preg_match('#^data:(image/(png|jpeg|gif));base64,(.+)$#s', $raw, $m)) return ['error' => 'Choisissez l\'image générée (PNG, JPG ou GIF).'];
        $bin = base64_decode($m[3], true); if ($bin === false || strlen($bin) > 2 * 1048576) return ['error' => 'Image trop lourde (2 Mo au plus).'];
        $info = @getimagesizefromstring($bin); if (!$info || !in_array($info['mime'], ['image/png', 'image/jpeg', 'image/gif'], true)) return ['error' => 'Image illisible.'];
    }
    $re = '/^\d{4}-\d{2}-\d{2}$/'; $st = preg_match($re, $st) ? $st : null; $en = preg_match($re, $en) ? $en : null;
    if (!in_array($mode, ['default', 'period', 'rotation', 'draft'], true)) $mode = 'default';
    $def = $mode === 'default' ? 1 : 0; $rot = $mode === 'rotation' ? 1 : 0;
    $otherDef = (bool)q('SELECT 1 FROM banners WHERE is_default=1 AND id<>?', [$old['id'] ?? 0])->fetchColumn();
    if ($mode !== 'period') { $st = $en = null; if ($old && $old['is_default'] && !$def && !$otherDef) return ['error' => 'Il faut toujours un bandeau par défaut : définissez-en un autre avant de changer celui-ci.']; }
    else {
        if (!$st || !$en) return ['error' => 'Indiquez la période (du… au…).'];
        if ($en < $st) return ['error' => 'La date de fin est avant la date de début.'];
        if ($en < date('Y-m-d') && (!$old || $en !== $old['end'])) return ['error' => 'Cette période est déjà passée.'];
    }
    if (!$def && !$otherDef) return ['error' => 'Il faut toujours un bandeau par défaut : ajoutez d\'abord celui qui s\'affichera hors périodes.'];
    if ($t === '') $t = $old['title'] ?? ['default' => 'Bandeau par défaut du ' . date('d/m/Y'), 'draft' => 'Brouillon du ' . date('d/m/Y'), 'rotation' => 'Visuel du mois ' . date('d/m/Y'), 'period' => 'Bandeau du ' . ($st ? date('d/m', strtotime($st)) . ' au ' . date('d/m/Y', strtotime($en)) : '')][$mode];
    $file = $old['file'] ?? null; $mime = $old['mime'] ?? null; $w = $old['w'] ?? 0; $h = $old['h'] ?? 0;
    if ($bin !== null) {
        $file = bin2hex(random_bytes(8)) . '.' . ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif'][$info['mime']];
        file_put_contents(sig_dir() . '/' . $file, $bin); $mime = $info['mime']; $w = $info[0]; $h = $info[1];
        if ($old) foreach (glob(sig_dir() . '/' . basename($old['file']) . '*') ?: [] as $f) @unlink($f);
    }
    if ($def) q('UPDATE banners SET is_default=0');
    $ord = $rot ? (int)q('SELECT COALESCE(MAX(rot_order),0)+1 FROM banners')->fetchColumn() : 0;
    if ($old) q('UPDATE banners SET title=?, file=?, mime=?, w=?, h=?, link=?, start=?, end=?, is_default=?, rotation=?, rot_order=' . ($rot && !$old['rotation'] ? $ord : 'rot_order') . ', active_at=' . ($mode === 'draft' ? 'active_at' : 'NULL') . ' WHERE id=?', [$t, $file, $mime, $w, $h, SIG_LINK, $st, $en, $def, $rot, $old['id']]);
    else q('INSERT INTO banners(title,file,mime,w,h,link,start,end,is_default,rotation,rot_order,created_by,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)', [$t, $file, $mime, $w, $h, SIG_LINK, $st, $en, $def, $rot, $ord, $uid, time()]);
    return ['ok' => true];
}
