<?php
declare(strict_types=1);

define('APP_DIR', __DIR__ . '/app');
require APP_DIR . '/config.php';
require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/totp.php';
require APP_DIR . '/lib/webauthn.php';
require APP_DIR . '/lib/auth.php';
require APP_DIR . '/lib/feeds.php';
require APP_DIR . '/lib/graph.php';
require APP_DIR . '/lib/mail.php';
require APP_DIR . '/lib/ingest.php';
require APP_DIR . '/lib/building.php';
require APP_DIR . '/lib/hono_log.php';
require APP_DIR . '/lib/sec_audit.php';
require APP_DIR . '/lib/team.php';
require APP_DIR . '/lib/survey.php';
require APP_DIR . '/lib/sig.php';

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; form-action 'self' https://login.microsoftonline.com");
if (str_starts_with(BASE_URL, 'https://')) header('Strict-Transport-Security: max-age=31536000');

$route0 = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
if ($route0 === 'signature/banniere') sig_serve_image();
if ($route0 === 'signature/lien') sig_redirect();
if (preg_match('#^ingest/(mails|trip|veille|audit|honoraires|buildings|compta|consignes|annexes|ag|pv|honofill|news|reg|staff|contacts)$#', $route0, $m)) ingest_handle($m[1]);

session_boot();

$route = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if (str_starts_with($route, 'api/')) {
        if ($method !== 'POST') json_out(['error' => 'Méthode non autorisée'], 405);
        csrf_check();
        require APP_DIR . '/api.php';
        api(substr($route, 4));
        exit;
    }

    switch ($route) {
        case '':
            $u = require_user();
            require APP_DIR . '/pages/dashboard.php';
            page_dashboard($u);
        case 'espace':
            $u = require_user();
            require APP_DIR . '/pages/perso.php';
            page_perso($u);
        case 'mes-taches':
        case 'bloc-notes':
            $u = require_user();
            require APP_DIR . '/pages/perso.php';
            if ($route === 'mes-taches') page_tasks($u);
            page_notes($u);
        case 'assistant':
        case 'assistant/historique':
        case 'versioning':
            $u = require_user();
            require APP_DIR . '/pages/assistant.php';
            if ($route === 'assistant/historique') page_assistant_history($u);
            if ($route === 'versioning') page_versioning($u);
            page_assistant($u);
        case 'equipe':
            require_user();
            redirect('/organigramme');
        case 'signatures':
            $u = require_user();
            if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); }
            render('signatures', ['title' => 'Signatures', 'nav' => 'sig', 'u' => $u, 'list' => sig_all(), 'cur' => sig_current(), 'tpl' => json_decode((string)q("SELECT v FROM kv WHERE k='sig_template'")->fetchColumn(), true) ?: null]);
        case 'enquete':
        case 'enquete/resultats':
            $u = require_user();
            require APP_DIR . '/pages/survey.php';
            if ($route === 'enquete') page_survey($u);
            page_survey_results($u);
        case 'organigramme':
        case 'accueil':
        case 'accueil/presentation':
            $u = require_user();
            require APP_DIR . '/pages/onboard.php';
            if ($route === 'organigramme') page_organigramme($u);
            if ($route === 'accueil') page_accueil($u);
            page_presentation($u);
        case 'strategies':
            $u = require_user();
            require_once APP_DIR . '/pages/strategies.php';
            page_strategies($u);
        case 'immeubles':
            $u = require_user();
            require APP_DIR . '/pages/immeubles.php';
            page_immeubles($u);
        case 'gid':
        case 'direction':
        case 'famille':
            $u = require_user();
            require APP_DIR . '/pages/space.php';
            page_space($u, $route);
        case 'voyages':
            $u = require_user();
            require APP_DIR . '/pages/trips.php';
            page_trips($u);
        case 'planning-ag':
            $u = require_user();
            require APP_DIR . '/pages/ag.php';
            page_ag($u);
        case 'comptabilite':
            $u = require_user();
            require APP_DIR . '/pages/compta.php';
            page_compta($u);
        case 'honoraires':
        case 'honoraires/vue':
        case 'honoraires/import':
            $u = require_user();
            require APP_DIR . '/pages/docs.php';
            if ($route === 'honoraires/vue') doc_view($u, 'honoraires');
            if ($route === 'honoraires/import' && $method === 'POST') doc_import($u, 'honoraires');
            page_doc($u, 'honoraires');
        case 'securite':
            $u = require_user();
            require APP_DIR . '/pages/security.php';
            page_security($u);
        case 'connexion':
            if (current_user()) redirect('/');
            render('login', ['title' => 'Connexion', 'startStep' => 'main'], false);
        case 'connexion/reinitialiser':
            if (ip_blocked()) { http_response_code(429); render('notfound', ['title' => 'Accès bloqué', 'message' => 'Trop de tentatives échouées depuis cette adresse : réessayez dans 30 minutes.'], false); }
            $tok = (string)($_GET['t'] ?? '');
            $ok = $tok !== '' && q('SELECT 1 FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>?', [hash('sha256', $tok), time()])->fetchColumn();
            if (!$ok) { if ($tok !== '') ip_fail(null, 'reset_link'); http_response_code(410); render('notfound', ['title' => 'Lien expiré', 'message' => 'Ce lien de réinitialisation a expiré ou a déjà servi. Faites une nouvelle demande depuis la page de connexion.'], false); }
            render('reset', ['title' => 'Nouveau mot de passe', 'token' => $tok], false);
        case 'connexion/lien':
            if (ip_blocked()) { http_response_code(429); render('notfound', ['title' => 'Accès bloqué', 'message' => 'Trop de tentatives échouées depuis cette adresse : réessayez dans 30 minutes.'], false); }
            $tok = (string)($_GET['t'] ?? '');
            $row = $tok !== '' ? q('SELECT * FROM magic_links WHERE token_hash=? AND used_at IS NULL AND expires_at>?', [hash('sha256', $tok), time()])->fetch() : null;
            if (!$row) { if ($tok !== '') ip_fail(null, 'magic_link'); http_response_code(410); render('notfound', ['title' => 'Lien expiré', 'message' => 'Ce lien de connexion a expiré ou a déjà été utilisé. Demandez-en un nouveau.'], false); }
            q('UPDATE magic_links SET used_at=? WHERE id=?', [time(), $row['id']]);
            login_session((int)$row['user_id']);
            audit((int)$row['user_id'], 'login_magic_link');
            render('login', ['title' => 'Connexion', 'startStep' => 'trust'], false);
        case 'activation':
            require APP_DIR . '/pages/enroll.php';
            page_enroll((string)($_GET['t'] ?? ''));
        case 'deconnexion':
            logout();
            redirect('/connexion');
        case 'microsoft/connexion':
            require_user();
            if (!graph_enabled()) redirect('/securite');
            redirect(graph_auth_url());
        case 'microsoft/retour':
            $u = require_user();
            $ok = isset($_GET['code']) && graph_callback((int)$u['id'], (string)$_GET['code'], (string)($_GET['state'] ?? ''));
            audit((int)$u['id'], $ok ? 'microsoft_connected' : 'microsoft_failed');
            redirect($ok ? '/' : '/securite?ms=erreur');
        default:
            if (preg_match('#^immeubles/([0-9A-Za-z]{1,10})$#', $route, $m)) {
                $u = require_user();
                require APP_DIR . '/pages/immeubles.php';
                page_building($u, $m[1]);
            }
            if ($route === 'honoraires/classement') {
                $u = require_user();
                if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès refusé', 'u' => $u]); }
                $rows = [];
                foreach (hono_data()['bld'] ?? [] as $b) {
                    if (hono_lost($b)) continue;
                    $code = str_pad((string)$b['num'], 5, '0', STR_PAD_LEFT); $f = bld_finance($code);
                    $v = hono_num($b['h2627v']); $p = hono_num($b['h2526']); $l = (int)hono_num($b['lots']);
                    $rows[] = ['code' => $code, 'imm' => trim((string)$b['imm']), 'port' => strtoupper(trim((string)($b['port'] ?? ''))), 'lots' => $l, 'v' => $v, 'p' => $p,
                               'ev' => $p ? ($v / $p - 1) * 100 : null, 'pl' => $l ? $v / $l : 0, 'color' => $f['color'] ?? null, 'clabel' => $f['label'] ?? ''];
                }
                usort($rows, fn($a, $b) => $b['v'] <=> $a['v']);
                render('hono_rank', ['title' => 'Classement des immeubles', 'nav' => 'honoraires', 'u' => $u, 'rows' => $rows]);
            }
            if ($route === 'honoraires/modifications') {
                $u = require_user();
                if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès refusé', 'u' => $u]); }
                render('hono_changes', ['title' => 'Modifications des honoraires', 'nav' => 'honoraires', 'u' => $u, 'changes' => hono_changes(0, 20000)]);
            }
            if (preg_match('#^pilotage(?:/([a-z]+))?(?:/(\d{5}))?$#', $route, $m)) {
                $u = require_user();
                require APP_DIR . '/pages/pilot.php';
                page_pilot($u, $m[1] ?? '', $m[2] ?? '');
            }
            if (preg_match('#^calendrier/([a-f0-9]{32})\.ics$#', $route, $m)) {
                require APP_DIR . '/pages/pilot.php';
                ical_feed($m[1]);
            }
            if (preg_match('#^signatures/image/(\d+)$#', $route, $m)) {
                $u = require_user(); if (!can_space($u, 'direction')) { http_response_code(403); exit; }
                $b = q('SELECT * FROM banners WHERE id=?', [(int)$m[1]])->fetch(); $f = $b ? sig_dir() . '/' . basename($b['file']) : '';
                if (!$b || !is_file($f)) { http_response_code(404); exit; }
                header('Content-Type: ' . $b['mime']); header('Cache-Control: private, max-age=86400'); readfile($f); exit;
            }
            if (preg_match('#^equipe/([a-z0-9-]{1,80})$#', $route, $m)) {
                $u = require_user();
                require APP_DIR . '/pages/team.php';
                page_team_person($u, $m[1]);
            }
            if (preg_match('#^mes-livres(?:/(\d+)(/fichier)?)?$#', $route, $m)) {
                $u = require_user();
                require APP_DIR . '/pages/books.php';
                if (empty($m[1])) page_books($u);
                if (!empty($m[2])) book_file($u, (int)$m[1]);
                page_book_read($u, (int)$m[1]);
            }
            if (preg_match('#^partage/([A-Za-z0-9_-]{24,64})$#', $route, $m)) {
                require APP_DIR . '/pages/trips.php';
                page_trip_public($m[1]);
            }
            if (preg_match('#^voyages/(\d+)$#', $route, $m)) {
                $u = require_user();
                require APP_DIR . '/pages/trips.php';
                page_trip($u, (int)$m[1]);
            }
            http_response_code(404);
            render('notfound', ['title' => 'Page introuvable'], false);
    }
} catch (Throwable $ex) {
    error_log('[chemini] ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    if (str_starts_with($route, 'api/')) json_out(['error' => 'Erreur interne. Réessayez.'], 500);
    http_response_code(500);
    echo 'Erreur interne.';
}
