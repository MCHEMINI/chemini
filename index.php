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
require APP_DIR . '/lib/president.php';
require APP_DIR . '/lib/guides.php';
require APP_DIR . '/lib/wp.php';
require APP_DIR . '/lib/stats.php';
require APP_DIR . '/lib/news.php';
require APP_DIR . '/lib/search.php';
require APP_DIR . '/lib/aiq.php';
require APP_DIR . '/lib/requests.php';
require APP_DIR . '/lib/candidates.php';

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet'); // plateforme privée : jamais référencée (dashboard.gidsas.fr comme chemini.gidsas.fr)
$wpImg = ''; try { $wpH = parse_url((string)(json_decode((string)q("SELECT v FROM kv WHERE k='wp_conn'")->fetchColumn(), true)['url'] ?? ''), PHP_URL_HOST); if ($wpH && preg_match('/^[a-z0-9.-]+$/i', $wpH)) $wpImg = ' https://' . $wpH; } catch (Throwable) {} // visuels des annonces du site
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:$wpImg; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; form-action 'self' https://login.microsoftonline.com");
if (str_starts_with(BASE_URL, 'https://')) header('Strict-Transport-Security: max-age=31536000');

$route0 = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
if ($route0 === 'signature/banniere') sig_serve_image();
if ($route0 === 'signature/lien') sig_redirect();
if ($route0 === 't') stats_collect(); // mesure d'audience du site gidsas.fr (public, sans cookie)
if ($route0 === 't.js') stats_script();
if ($route0 === 'candidature') cand_receive(); // formulaire Recrutement du site gidsas.fr (public)
if ($route0 === 'rc.js') cand_script();
if (preg_match('#^ingest/(mails|trip|veille|audit|honoraires|buildings|compta|consignes|annexes|ag|pv|honofill|news|reg|staff|contacts|banners|president|ocr|questions|maquettes|users|requests|candidats)$#', $route0, $m)) ingest_handle($m[1]);

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
            require APP_DIR . '/pages/perso.php';
            page_home($u);
        case 'guides':
            $u = require_user();
            require APP_DIR . '/pages/guides.php';
            page_guides($u);
        case 'installer':
            $u = require_user();
            render('install', ['title' => 'Installer sur iPhone', 'nav' => 'install', 'u' => $u]);
        case 'recherche':
            $u = require_user();
            page_search($u);
        case 'actualites':
        case 'actualites/syndic':
        case 'actualites/copro':
        case 'actualites/transaction':
        case 'actualites/locatif':
        case 'actualites/archives':
            $u = require_user();
            page_news($u, (string)substr($route, 11));
        case 'tableau-de-bord':
            $u = require_user();
            require APP_DIR . '/pages/dashboard.php';
            page_dashboard($u);
        case 'mot-du-president':
            $u = require_user();
            require APP_DIR . '/pages/president.php';
            page_president($u);
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
        case 'site-web/annonces/nouvelle': redirect('/annonces/#nouvelle');
        case 'annonces': // appli Annonces (installable, hors ligne) : création, consultation, modification, suppression
            $u = require_user();
            if (!wp_poster($u)) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction et aux annonceurs.'], false); }
            if (!str_ends_with(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '', '/')) redirect('/annonces/');
            $conn = wp_conn();
            render('annonces_app', ['title' => 'Annonces', 'u' => $u, 'snap' => $conn ? wp_snapshot() : ['tax' => []], 'cssV' => ASSET_CSS_V, 'jsV' => ASSET_ANN_V], false);
        case 'annonces/manifest.webmanifest':
            header('Content-Type: application/manifest+json');
            echo json_encode(['name' => 'Annonces · GID', 'short_name' => 'Annonces', 'start_url' => '/annonces/', 'scope' => '/annonces/', 'display' => 'standalone', 'background_color' => '#121720', 'theme_color' => '#121720', 'lang' => 'fr',
                'icons' => [['src' => '/assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'], ['src' => '/assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable']],
                'shortcuts' => [['name' => 'Nouvelle annonce', 'url' => '/annonces/#nouvelle']]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit;
        case 'annonces/sw.js':
            header('Content-Type: application/javascript; charset=utf-8'); header('Service-Worker-Allowed: /annonces/'); header('Cache-Control: no-cache');
            $V = 'gid-annonces-' . ASSET_CSS_V . '-' . ASSET_ANN_V;
            echo "/* Appli Annonces : écran disponible hors ligne ; les données passent par IndexedDB dans la page. */\n"
               . "const V = '$V'; const SHELL = ['/annonces/', '/assets/app.css?v=" . ASSET_CSS_V . "', '/assets/annonces.js?v=" . ASSET_ANN_V . "', '/assets/theme.js?v=3', '/assets/favicon.svg', '/assets/icon-192.png', '/assets/apple-touch-icon.png'];\n"
               . "self.addEventListener('install', e => { self.skipWaiting(); e.waitUntil(caches.open(V).then(c => c.addAll(SHELL)).catch(() => {})); });\n"
               . "self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x => x.startsWith('gid-annonces-') && x !== V).map(x => caches.delete(x)))).then(() => self.clients.claim())); });\n"
               . "self.addEventListener('fetch', e => { const r = e.request; if (r.method !== 'GET') return; const u = new URL(r.url);\n"
               . "  if (r.mode === 'navigate') { e.respondWith(fetch(r).then(res => { if (res.ok && u.pathname === '/annonces/') caches.open(V).then(c => c.put('/annonces/', res.clone())); return res; }).catch(() => caches.match('/annonces/'))); return; }\n"
               . "  if (u.origin === location.origin && u.pathname.startsWith('/assets/')) { e.respondWith(caches.match(r).then(h => h || fetch(r).then(res => { if (res.ok) caches.open(V).then(c => c.put(r, res.clone())); return res; }))); return; }\n"
               . "  if (r.destination === 'image' && u.origin !== location.origin) { e.respondWith(caches.open(V + '-img').then(c => c.match(r).then(h => h || fetch(r).then(res => { c.put(r, res.clone()); return res; }).catch(() => h)))); }\n"
               . "});\n";
            exit;
        case 'site-web':
        case 'site-web/annonces':
        case 'site-web/pages':
        case 'site-web/connexion':
            $u = require_user();
            require APP_DIR . '/pages/site.php';
            page_site($u, (string)substr($route, 9));
        case 'candidats':
        case 'candidats/base':
            $u = require_user();
            if (!cand_viewer($u)) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); }
            $base = $route === 'candidats/base';
            $f = ['job' => (string)($_GET['metier'] ?? ''), 'q' => mb_substr((string)($_GET['q'] ?? ''), 0, 120), 'starred' => $base];
            $list = cand_list($f); $counts = cand_counts();
            if ($ids = array_column(array_filter($list, fn($c) => !$c['seen_at']), 'id')) q('UPDATE candidates SET seen_at=? WHERE seen_at IS NULL AND id IN (' . implode(',', array_map('intval', $ids)) . ')', [time()]); // « Nouveau » affiché une fois
            render('candidats', ['title' => $base ? 'Base de recrutement' : 'Candidats', 'nav' => $base ? 'cand-base' : 'cand', 'u' => $u, 'base' => $base, 'f' => $f, 'list' => $list, 'counts' => $counts]);
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
            $tok = (string)($_POST['t'] ?? $_GET['t'] ?? '');
            $row = $tok !== '' ? q('SELECT * FROM magic_links WHERE token_hash=? AND used_at IS NULL AND expires_at>?', [hash('sha256', $tok), time()])->fetch() : null;
            if (!$row) { if ($tok !== '') ip_fail(null, 'magic_link'); http_response_code(410); render('notfound', ['title' => 'Lien expiré', 'message' => 'Ce lien de connexion a expiré ou a déjà été utilisé. Demandez-en un nouveau.'], false); }
            // Ouverture du lien (GET) : simple confirmation, le lien n'est consommé qu'au clic (les antivirus de messagerie qui pré-ouvrent les liens ne le grillent plus)
            if ($method !== 'POST') render('magic_confirm', ['title' => 'Connexion', 'token' => $tok], false);
            if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) redirect('/connexion/lien?t=' . rawurlencode($tok));
            q('UPDATE magic_links SET used_at=? WHERE id=?', [time(), $row['id']]);
            login_session((int)$row['user_id']);
            audit((int)$row['user_id'], 'login_magic_link');
            render('login', ['title' => 'Connexion', 'startStep' => 'trust'], false);
        case 'activation':
            require APP_DIR . '/pages/enroll.php';
            page_enroll((string)($_GET['t'] ?? ''));
        case 'deconnexion': // POST + jeton : une page tierce ne peut pas déconnecter
            if ($method !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) redirect('/');
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
            if (preg_match('#^candidats/fichier/(\d+)$#', $route, $m)) cand_file_serve(require_user(), (int)$m[1]);
            if (preg_match('#^signatures/image/(\d+)$#', $route, $m)) {
                $u = require_user(); if (!can_space($u, 'direction')) { http_response_code(403); exit; }
                $b = q('SELECT * FROM banners WHERE id=?', [(int)$m[1]])->fetch(); $f = $b ? sig_dir() . '/' . basename($b['file']) : '';
                if (!$b || !is_file($f)) { http_response_code(404); exit; }
                header('Content-Type: ' . $b['mime']); header('Cache-Control: private, max-age=86400'); readfile($f); exit;
            }
            if (preg_match('#^guides/([a-z0-9-]{1,80})$#', $route, $m)) { $u = require_user(); require APP_DIR . '/pages/guides.php'; page_guides($u, $m[1]); }
            if (preg_match('#^mot-du-president/(\d{4}-\d{2})$#', $route, $m)) { $u = require_user(); require APP_DIR . '/pages/president.php'; page_president($u, $m[1]); }
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
            if (preg_match('#^maquettes/([a-z0-9-]{1,60})$#', $route, $m)) { $u = require_user(); mq_serve($u, $m[1]); }
            if (preg_match('#^mot/([A-Za-z0-9_-]{24,64})$#', $route, $m)) { require APP_DIR . '/pages/president.php'; page_president_public($m[1]); }
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
