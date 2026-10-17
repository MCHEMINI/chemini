<?php
declare(strict_types=1);

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function redirect(string $path): never { header('Location: ' . $path); exit; }

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (!empty($_SESSION['csrf'])) header('X-CSRF-Token: ' . $_SESSION['csrf']);
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
    if ($view === 'notfound') $layout = false; // page d'erreur autonome (elle a sa propre en-tête)
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

function users_all(): array { static $c = null; return $c ??= q('SELECT id,name,initials,email,role FROM users ORDER BY id')->fetchAll(); }

function avatar(array $u, string $size = ''): string {
    $cls = ((int)$u['id'] % 2) ? 'av-a' : 'av-b';
    return '<span class="avatar ' . $cls . ' ' . $size . '">' . e($u['initials']) . '</span>';
}

/* ---------- Espaces et droits ---------- */
/* Tâches IA (Mon espace) : priorités, statuts et espace concerné des consignes */
const CONSIGNE_PRIO = ['haute' => 'Haute', 'normale' => 'Normale', 'basse' => 'Basse'];
/** Espaces proposés pour une consigne, dans cet ordre ; seuls ceux auxquels la personne a accès sont proposés. */
const CONSIGNE_SPACES = ['perso' => 'Perso', 'famille' => 'Famille', 'direction' => 'Direction', 'gid' => 'Équipe'];
function consigne_spaces(array $u): array { return array_filter(CONSIGNE_SPACES, fn($k) => $k === 'perso' || can_space($u, $k), ARRAY_FILTER_USE_KEY); }
const CONSIGNE_STATUS = ['todo' => 'À faire', 'doing' => 'En cours', 'done' => 'Fait'];
const SPACES = ['gid' => 'Équipe', 'direction' => 'Direction', 'famille' => 'Famille'];

function user_spaces(array $u): array {
    static $cache = [];
    $id = (int)$u['id'];
    if (!isset($cache[$id])) {
        $s = q('SELECT space FROM space_members WHERE user_id=?', [$id])->fetchAll(PDO::FETCH_COLUMN);
        $cache[$id] = array_values(array_unique(array_merge(['gid'], $s)));
    }
    return $cache[$id];
}
function can_space(array $u, string $space): bool { return in_array($space, user_spaces($u), true); }
function space_members(string $space): array {
    if ($space === 'gid') return users_all();
    static $all = null; // une seule requête par page pour toutes les sections
    if ($all === null) { $all = []; foreach (q('SELECT m.space, u.id,u.name,u.initials,u.email,u.role FROM space_members m JOIN users u ON u.id=m.user_id ORDER BY u.id')->fetchAll() as $r) { $sp = $r['space']; unset($r['space']); $all[$sp][] = $r; } }
    return $all[$space] ?? [];
}

/* ---------- Politique de mots de passe ---------- */
const PW_MIN = 12;
const PW_COMMON = ['password', 'motdepasse', 'azerty', 'qwerty', 'soleil', 'bonjour', 'chemini', 'gidsas', 'syndic', 'copropriete', 'paris', 'admin', 'welcome', 'letmein', 'iloveyou', 'marseille', 'football', 'princesse', 'doudou', 'loulou', 'nicolas', 'jetaime'];

/** Message d'erreur si le mot de passe est trop faible, null s'il est acceptable. */
function pw_check(string $pw, ?array $u = null): ?string {
    $len = mb_strlen($pw);
    if ($len < PW_MIN) return 'Au moins ' . PW_MIN . ' caractères.';
    if ($len > 200) return 'Mot de passe trop long.';
    $cls = (int)preg_match('/[a-z]/u', $pw) + (int)preg_match('/[A-Z]/u', $pw) + (int)preg_match('/\d/', $pw) + (int)preg_match('/[^a-zA-Z\d]/u', $pw);
    if ($cls < 3 && $len < 16) return 'Mélangez au moins trois types de caractères (minuscules, majuscules, chiffres, symboles), ou choisissez une phrase de 16 caractères ou plus.';
    if (preg_match('/(.)\1{3,}/u', $pw)) return 'Évitez les caractères répétés (aaaa, 1111…).';
    $low = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $pw) ?: $pw);
    foreach (['0123', '1234', '2345', '3456', '4567', '5678', '6789', 'abcd', 'azer', 'qwer'] as $seq) if (str_contains($low, $seq) && $len < 16) return 'Évitez les suites évidentes (1234, abcd, azer…).';
    $core = preg_replace('/[^a-z]/', '', $low);
    foreach (PW_COMMON as $w) if ($core === $w || (strlen($core) - strlen($w) <= 3 && str_contains($core, $w))) return 'Ce mot de passe est trop courant. Choisissez-en un plus personnel.';
    if ($u) {
        $parts = array_filter(array_merge(preg_split('/[\s.@_-]+/', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)($u['name'] ?? '')) ?: '')), [strtolower(strtok((string)($u['email'] ?? ''), '@') ?: '')]), fn($p) => strlen((string)$p) >= 4);
        foreach ($parts as $p) if (str_contains($low, $p)) return 'Le mot de passe ne doit pas contenir votre nom ou votre adresse e-mail.';
    }
    return null;
}

/** Hachage : Argon2id quand il est disponible, sinon bcrypt. */
function pw_hash(string $pw): string { return defined('PASSWORD_ARGON2ID') ? password_hash($pw, PASSWORD_ARGON2ID) : password_hash($pw, PASSWORD_DEFAULT); }
function pw_algo(): string|int|null { return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT; }

/* Rubriques du tableau d'affichage : libellé, couleur */
const BOARD_CATS = ['annonce' => ['Annonce', '#8A6A3B'], 'organisation' => ['Organisation', '#3F5F8A'], 'rh' => ['Vie du cabinet', '#3F7A5A'],
    'reglementation' => ['Réglementation', '#A6403A'], 'procedure' => ['Procédures', '#6B5B95'], 'evenement' => ['Événement', '#A8742A'], 'outils' => ['Outils', '#4A505A']];
