<?php
declare(strict_types=1);

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function redirect(string $path): never { header('Location: ' . $path); exit; }

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body(): array {
    static $b = null;
    if ($b === null) { $b = json_decode(file_get_contents('php://input') ?: '[]', true); if (!is_array($b)) $b = []; }
    return $b;
}
function in_str(string $k, int $max = 500): string { return mb_substr(trim((string)(body()[$k] ?? '')), 0, $max); }
function in_int(string $k): int { return (int)(body()[$k] ?? 0); }

function render(string $view, array $vars = [], bool $layout = true): never {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    extract($vars);
    ob_start();
    require APP_DIR . '/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($layout) require APP_DIR . '/views/layout.php'; else echo $content;
    exit;
}

const MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
const MOIS_LONG = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

function date_long(int $ts): string {
    return ucfirst(JOURS[(int)date('w', $ts)]) . ' ' . (int)date('j', $ts) . ' ' . MOIS_LONG[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
function date_short(int $ts): string { return (int)date('j', $ts) . ' ' . MOIS[(int)date('n', $ts) - 1]; }

function ago(?int $ts): string {
    if (!$ts) return '';
    $d = time() - $ts;
    if ($d < 3600) return 'Il y a ' . max(1, intdiv($d, 60)) . ' min';
    if ($d < 86400 && date('Ymd', $ts) === date('Ymd')) return 'Il y a ' . intdiv($d, 3600) . ' h';
    if (date('Ymd', $ts) === date('Ymd', time() - 86400)) return 'Hier';
    if ($d < 6 * 86400) return ucfirst(mb_substr(JOURS[(int)date('w', $ts)], 0, 3)) . '.';
    return date_short($ts);
}

function greeting(): string { $h = (int)date('G'); return ($h >= 18 || $h < 5) ? 'Bonsoir' : 'Bonjour'; }

const SEVERITY = ['crit' => 'Urgent', 'warn' => 'Cette semaine', 'ok' => 'À suivre'];

function users_all(): array { return q('SELECT id,name,initials,email,role FROM users ORDER BY id')->fetchAll(); }

function avatar(array $u, string $size = ''): string {
    $cls = ((int)$u['id'] % 2) ? 'av-a' : 'av-b';
    return '<span class="avatar ' . $cls . ' ' . $size . '">' . e($u['initials']) . '</span>';
}
