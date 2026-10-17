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
function sig_serve_image(): never {
    $b = sig_current(); $f = $b ? sig_dir() . '/' . basename($b['file']) : '';
    if (!$b || !is_file($f)) { header('Content-Type: image/gif'); header('Cache-Control: no-cache'); echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); exit; }
    header('Content-Type: ' . $b['mime']); header('Cache-Control: public, max-age=900'); header('Content-Length: ' . filesize($f));
    header('ETag: "' . md5($b['id'] . '|' . filemtime($f)) . '"'); header_remove('Content-Security-Policy');
    readfile($f); exit;
}
function sig_redirect(): never {
    $b = sig_current(); $u = $b && $b['link'] ? $b['link'] : 'https://www.gidsas.fr/';
    header('Location: ' . $u, true, 302); header('Cache-Control: no-cache'); exit;
}
