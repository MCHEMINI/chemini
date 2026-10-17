<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dir = DATA_DIR;
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    $pdo = new PDO('sqlite:' . $dir . '/chemini.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000;');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $v = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($v < 1) {
        $pdo->exec(<<<SQL
CREATE TABLE users(
  id INTEGER PRIMARY KEY, email TEXT UNIQUE NOT NULL, name TEXT NOT NULL, initials TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'member', totp_secret TEXT, totp_enabled INTEGER NOT NULL DEFAULT 0,
  totp_last_step INTEGER NOT NULL DEFAULT 0, created_at INTEGER NOT NULL);
CREATE TABLE invites(
  id INTEGER PRIMARY KEY, email TEXT NOT NULL, name TEXT NOT NULL, initials TEXT NOT NULL, role TEXT NOT NULL,
  token_hash TEXT UNIQUE NOT NULL, expires_at INTEGER NOT NULL, used_at INTEGER);
CREATE TABLE passkeys(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  cred_id TEXT UNIQUE NOT NULL, public_key_pem TEXT NOT NULL, sign_count INTEGER NOT NULL DEFAULT 0,
  label TEXT NOT NULL, created_at INTEGER NOT NULL, last_used_at INTEGER);
CREATE TABLE trusted_devices(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash TEXT UNIQUE NOT NULL, label TEXT NOT NULL, ip TEXT, created_at INTEGER NOT NULL,
  last_seen_at INTEGER NOT NULL, expires_at INTEGER NOT NULL);
CREATE TABLE items(
  id INTEGER PRIMARY KEY, title TEXT NOT NULL, detail TEXT NOT NULL DEFAULT '', copro TEXT NOT NULL DEFAULT '',
  severity TEXT NOT NULL DEFAULT 'warn', owner_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
  status TEXT NOT NULL DEFAULT 'open', created_by INTEGER, created_at INTEGER NOT NULL, done_at INTEGER);
CREATE TABLE deadlines(
  id INTEGER PRIMARY KEY, title TEXT NOT NULL, detail TEXT NOT NULL DEFAULT '', copro TEXT NOT NULL DEFAULT '',
  due_date TEXT NOT NULL, done INTEGER NOT NULL DEFAULT 0, created_by INTEGER, created_at INTEGER NOT NULL);
CREATE TABLE tasks(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  title TEXT NOT NULL, done INTEGER NOT NULL DEFAULT 0, created_at INTEGER NOT NULL);
CREATE TABLE notes(user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE, body TEXT NOT NULL, updated_at INTEGER NOT NULL);
CREATE TABLE feed_cache(url TEXT PRIMARY KEY, fetched_at INTEGER NOT NULL, body TEXT NOT NULL);
CREATE TABLE graph_tokens(user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE, enc TEXT NOT NULL, expires_at INTEGER NOT NULL);
CREATE TABLE attempts(id INTEGER PRIMARY KEY, k TEXT NOT NULL, at INTEGER NOT NULL);
CREATE INDEX attempts_k ON attempts(k, at);
CREATE TABLE audit(id INTEGER PRIMARY KEY, user_id INTEGER, event TEXT NOT NULL, ip TEXT, at INTEGER NOT NULL);
PRAGMA user_version = 1;
SQL);
    }
    if ($v < 2) {
        $pdo->exec(<<<SQL
CREATE TABLE docs(
  slug TEXT PRIMARY KEY, title TEXT NOT NULL, html TEXT NOT NULL, store_key TEXT,
  updated_at INTEGER NOT NULL, updated_by INTEGER);
CREATE TABLE doc_state(
  slug TEXT PRIMARY KEY, json TEXT NOT NULL, updated_at INTEGER NOT NULL, updated_by INTEGER);
PRAGMA user_version = 2;
SQL);
    }
    if ($v < 3) {
        $pdo->exec(<<<SQL
ALTER TABLE users ADD COLUMN password_hash TEXT;
CREATE TABLE magic_links(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash TEXT UNIQUE NOT NULL, expires_at INTEGER NOT NULL, used_at INTEGER);
PRAGMA user_version = 3;
SQL);
    }
    if ($v < 4) {
        $pdo->exec(<<<SQL
CREATE TABLE password_resets(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash TEXT UNIQUE NOT NULL, expires_at INTEGER NOT NULL, used_at INTEGER);
PRAGMA user_version = 4;
SQL);
    }
    if ($v < 5) {
        $pdo->exec(<<<SQL
ALTER TABLE users ADD COLUMN must_change INTEGER NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN default_expires INTEGER;
ALTER TABLE tasks ADD COLUMN space TEXT;
ALTER TABLE docs ADD COLUMN space TEXT NOT NULL DEFAULT 'famille';
CREATE TABLE space_members(space TEXT NOT NULL, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, PRIMARY KEY(space, user_id));
CREATE TABLE space_notes(space TEXT PRIMARY KEY, body TEXT NOT NULL, updated_at INTEGER NOT NULL, updated_by INTEGER);
CREATE TABLE buildings(
  code TEXT PRIMARY KEY, kind TEXT NOT NULL, label TEXT NOT NULL, addr2 TEXT, cp TEXT, ville TEXT,
  gest TEXT, compta TEXT, assist TEXT, exercice TEXT);
CREATE TABLE staff(name TEXT PRIMARY KEY, role TEXT, email TEXT, phone TEXT, updated_at INTEGER);
PRAGMA user_version = 5;
SQL);
        seed_from_encrypted($pdo);
    }
    if ($v < 6) {
        seed_doc($pdo, 'honoraires', 'famille');
        $pdo->exec('PRAGMA user_version = 6');
    }
    if ($v < 7) {
        $pdo->exec(<<<SQL
UPDATE docs SET space='direction' WHERE slug='honoraires';
CREATE TABLE mail_items(
  id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, msg_id TEXT NOT NULL,
  from_name TEXT, from_email TEXT, subject TEXT NOT NULL, summary TEXT, reason TEXT, priority TEXT NOT NULL DEFAULT 'warn',
  received_at INTEGER, link TEXT, done INTEGER NOT NULL DEFAULT 0, done_at INTEGER, created_at INTEGER NOT NULL,
  UNIQUE(user_id, msg_id));
CREATE TABLE trips(
  id INTEGER PRIMARY KEY, space TEXT NOT NULL DEFAULT 'famille', destination TEXT NOT NULL, duration TEXT,
  data TEXT NOT NULL, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL);
PRAGMA user_version = 7;
SQL);
    }
    if ($v < 8) {
        seed_doc($pdo, 'honoraires', 'direction'); // analyse par gestionnaire
        $pdo->exec('PRAGMA user_version = 8');
    }
    if ($v < 9) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS veille (id INTEGER PRIMARY KEY, url TEXT NOT NULL UNIQUE, title TEXT NOT NULL, source TEXT,
  published_at INTEGER, summary TEXT NOT NULL, impact TEXT, important INTEGER NOT NULL DEFAULT 0, week TEXT NOT NULL, created_at INTEGER NOT NULL);
PRAGMA user_version = 9;
SQL);
    }
    if ($v < 10) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS audits (id INTEGER PRIMARY KEY, kind TEXT NOT NULL, period TEXT NOT NULL, headline TEXT NOT NULL,
  data TEXT NOT NULL, created_at INTEGER NOT NULL, UNIQUE(kind, period));
CREATE TABLE IF NOT EXISTS audit_reads (user_id INTEGER NOT NULL, audit_id INTEGER NOT NULL, read_at INTEGER NOT NULL, PRIMARY KEY(user_id, audit_id));
PRAGMA user_version = 10;
SQL);
    }
    if ($v < 11) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS building_info (code TEXT PRIMARY KEY, president TEXT, president_phone TEXT, president_email TEXT,
  cs_members TEXT, gardien TEXT, acces TEXT, notes TEXT, sources TEXT, updated_at INTEGER, updated_by INTEGER);
CREATE TABLE IF NOT EXISTS building_events (id INTEGER PRIMARY KEY, code TEXT NOT NULL, at INTEGER NOT NULL, kind TEXT NOT NULL,
  title TEXT NOT NULL, detail TEXT, link TEXT, ext_id TEXT UNIQUE, user_id INTEGER, created_at INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS building_events_code ON building_events(code, at DESC);
CREATE TABLE IF NOT EXISTS kv (k TEXT PRIMARY KEY, v TEXT, updated_at INTEGER);
INSERT OR IGNORE INTO kv(k,v,updated_at) VALUES('immopen_at','2026-10-16',strftime('%s','now'));
PRAGMA user_version = 11;
SQL);
    }
    if ($v < 12) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS compta_runs (id INTEGER PRIMARY KEY, period TEXT NOT NULL, data TEXT NOT NULL, created_at INTEGER NOT NULL);
CREATE TABLE IF NOT EXISTS compta_done (k TEXT PRIMARY KEY, user_id INTEGER, done_at INTEGER NOT NULL);
PRAGMA user_version = 12;
SQL);
    }
    if ($v < 13) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS consignes (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  body TEXT NOT NULL, priority TEXT NOT NULL DEFAULT 'normale', status TEXT NOT NULL DEFAULT 'todo', result TEXT,
  created_at INTEGER NOT NULL, started_at INTEGER, done_at INTEGER, updated_at INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS consignes_user ON consignes(user_id, status);
PRAGMA user_version = 13;
SQL);
    }
    if ($v < 14) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS user_prefs (user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, k TEXT NOT NULL,
  v TEXT NOT NULL, updated_at INTEGER NOT NULL, PRIMARY KEY(user_id, k));
PRAGMA user_version = 14;
SQL);
    }
    if ($v < 15) {
        $pdo->exec("ALTER TABLE consignes ADD COLUMN space TEXT NOT NULL DEFAULT 'perso'; PRAGMA user_version = 15;");
    }
    if ($v < 16) {
        $pdo->exec("ALTER TABLE trips ADD COLUMN share_token TEXT; CREATE UNIQUE INDEX IF NOT EXISTS trips_share ON trips(share_token); PRAGMA user_version = 16;");
    }
    if ($v < 17) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS hono_archive (id INTEGER PRIMARY KEY, num TEXT, port TEXT, imm TEXT NOT NULL, data TEXT NOT NULL,
  created_by INTEGER, created_at INTEGER NOT NULL, restored_at INTEGER);
CREATE TABLE IF NOT EXISTS pv_votes (id INTEGER PRIMARY KEY, msg_id TEXT NOT NULL, code TEXT NOT NULL, ag_date TEXT NOT NULL, exercice TEXT NOT NULL,
  gc_ht REAL, gc_ttc REAL, travaux_ttc REAL NOT NULL DEFAULT 0, detail TEXT, link TEXT, created_at INTEGER NOT NULL, UNIQUE(code, ag_date));
CREATE INDEX IF NOT EXISTS pv_msg ON pv_votes(msg_id);
PRAGMA user_version = 17;
SQL);
    }
    if ($v < 18) {
        $pdo->exec(<<<SQL
ALTER TABLE pv_votes ADD COLUMN pv_date TEXT;
CREATE TABLE IF NOT EXISTS books (id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, title TEXT NOT NULL, author TEXT, kind TEXT NOT NULL,
  size INTEGER NOT NULL DEFAULT 0, cover TEXT, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS books_user ON books(user_id);
CREATE TABLE IF NOT EXISTS book_state (book_id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL, pos TEXT, percent REAL NOT NULL DEFAULT 0,
  bookmarks TEXT, highlights TEXT, read_at INTEGER);
PRAGMA user_version = 18;
SQL);
    }
    if ($v < 19) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS hono_changes (id INTEGER PRIMARY KEY, at INTEGER NOT NULL, user_id INTEGER, source TEXT NOT NULL DEFAULT 'manuel',
  tab TEXT NOT NULL, ref TEXT, field TEXT, old TEXT, new TEXT);
CREATE INDEX IF NOT EXISTS hono_changes_at ON hono_changes(at);
PRAGMA user_version = 19;
SQL);
    }
    if ($v < 20) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS team_tasks (id INTEGER PRIMARY KEY, person TEXT NOT NULL, title TEXT NOT NULL, due TEXT,
  created_by INTEGER NOT NULL, by_dir INTEGER NOT NULL DEFAULT 0, created_at INTEGER NOT NULL, done_at INTEGER, done_by INTEGER);
CREATE INDEX IF NOT EXISTS team_tasks_person ON team_tasks(person, done_at);
PRAGMA user_version = 20;
SQL);
    }
    if ($v < 21) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS board_posts (id INTEGER PRIMARY KEY, title TEXT NOT NULL, body TEXT NOT NULL DEFAULT '', cat TEXT NOT NULL DEFAULT 'annonce',
  pinned INTEGER NOT NULL DEFAULT 0, link TEXT, author INTEGER, created_at INTEGER NOT NULL, until TEXT);
PRAGMA user_version = 21;
SQL);
    }
    if ($v < 22) {
        // Tâches perso fusionnées avec les tâches d'équipe : privées (visibles par la seule personne concernée)
        $pdo->exec('ALTER TABLE team_tasks ADD COLUMN priv INTEGER NOT NULL DEFAULT 0');
        $slug = fn(string $n) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $n) ?: $n)), '-');
        $rows = $pdo->query('SELECT t.*, u.name FROM tasks t JOIN users u ON u.id=t.user_id WHERE t.space IS NULL')->fetchAll(PDO::FETCH_ASSOC);
        $ins = $pdo->prepare('INSERT INTO team_tasks(person,title,due,created_by,by_dir,created_at,done_at,done_by,priv) VALUES(?,?,NULL,?,0,?,?,?,1)');
        foreach ($rows as $r) $ins->execute([$slug($r['name']), $r['title'], $r['user_id'], $r['created_at'], $r['done'] ? $r['created_at'] : null, $r['done'] ? $r['user_id'] : null]);
        $pdo->exec('DELETE FROM tasks WHERE space IS NULL; PRAGMA user_version = 22;');
    }
    if ($v < 23) {
        $t = time(); $ins = $pdo->prepare('INSERT INTO board_posts(title,body,cat,pinned,link,author,created_at) VALUES(?,?,?,?,NULL,NULL,?)');
        $ins->execute(['Bienvenue sur Chemini', "La plateforme interne de GID réunit les informations générales du cabinet, vos tâches, l'organigramme et la page Accueil des nouveaux arrivants.\nPour vous connecter : Face ID ou mot de passe et code à 6 chiffres.", 'outils', 1, $t]);
        $ins->execute(['Organigramme et page Accueil', "Retrouvez qui travaille avec qui (gestionnaire, assistante, comptables de chaque pôle) dans Organigramme, et la présentation de la société dans Accueil.", 'organisation', 0, $t + 1]);
        $ins->execute(['Tâches d\'équipe', "Dans Collaborateurs, chacun peut affecter une tâche à un collègue, avec ou sans échéance. La personne la coche une fois faite : la date de traitement est enregistrée.", 'outils', 0, $t + 2]);
        $pdo->exec('PRAGMA user_version = 23;');
    }
    if ($v < 24) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS reg_items (id INTEGER PRIMARY KEY, reg TEXT NOT NULL, code TEXT, data TEXT NOT NULL, ext_key TEXT,
  created_by INTEGER, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL);
CREATE INDEX IF NOT EXISTS reg_items_reg ON reg_items(reg, code);
CREATE UNIQUE INDEX IF NOT EXISTS reg_items_ext ON reg_items(reg, ext_key);
PRAGMA user_version = 24;
SQL);
    }
    if ($v < 25) { // obligations du cabinet à dater (registre Conformité)
        $ins = $pdo->prepare("INSERT OR IGNORE INTO reg_items(reg,code,data,ext_key,created_at,updated_at) VALUES('conformite','',?,?,?,?)");
        foreach (['Carte professionnelle (renouvellement 3 ans)' => 'Paris B 328 620 737, délivrée par la CCI Paris Île-de-France', 'Garantie financière (attestation annuelle)' => 'GALIAN : 13 380 000 € gestion, 120 000 € transaction', 'Assurance RCP (attestation annuelle)' => 'Contrat groupe MMA'] as $o => $n)
            $ins->execute([json_encode(['objet' => $o, 'personne' => '', 'echeance' => '', 'note' => $n], JSON_UNESCAPED_UNICODE), 'seed-' . md5($o), time(), time()]);
        $pdo->exec('PRAGMA user_version = 25;');
    }
    if ($v < 26) { // enquête de fonctionnement : réponses + tâche « répondre au questionnaire » pour chaque collaborateur
        $pdo->exec('CREATE TABLE IF NOT EXISTS survey_answers (user_id INTEGER PRIMARY KEY, data TEXT NOT NULL, updated_at INTEGER NOT NULL, submitted_at INTEGER)');
        $slug = fn(string $n) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $n) ?: $n)), '-');
        $admin = (int)$pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
        $ins = $pdo->prepare("INSERT INTO team_tasks(person,title,due,created_by,by_dir,created_at,priv) VALUES(?,?,?,?,1,?,0)");
        foreach ($pdo->query('SELECT name FROM staff')->fetchAll(PDO::FETCH_COLUMN) as $n) $ins->execute([$slug($n), 'Répondre au questionnaire de fonctionnement', date('Y-m-d', strtotime('+14 days')), $admin ?: 1, time()]);
        $pdo->exec('PRAGMA user_version = 26;');
    }
    if ($v < 27) { // annuaire des collaborateurs synchronisé avec ImmOpen : archivage et comptes désactivés
        $pdo->exec('ALTER TABLE staff ADD COLUMN archived_at INTEGER; ALTER TABLE users ADD COLUMN disabled_at INTEGER; PRAGMA user_version = 27;');
    }
    if ($v < 28) {
        $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS banners (id INTEGER PRIMARY KEY, title TEXT NOT NULL, file TEXT NOT NULL, mime TEXT NOT NULL, w INTEGER, h INTEGER, link TEXT,
  start TEXT, end TEXT, is_default INTEGER NOT NULL DEFAULT 0, created_by INTEGER, created_at INTEGER NOT NULL);
PRAGMA user_version = 28;
SQL);
    }
    if ($v < 29) { // bandeau actuel d'Exclaimer comme bandeau par défaut ; questionnaire aussi pour la Direction
        $src = APP_DIR . '/seed/banner-default.png'; $dir = DATA_DIR . '/banners';
        if (is_file($src) && !(int)$pdo->query('SELECT COUNT(*) FROM banners')->fetchColumn()) {
            if (!is_dir($dir)) @mkdir($dir, 0700, true);
            if (@copy($src, $dir . '/default-60ans.png'))
                $pdo->prepare('INSERT INTO banners(title,file,mime,w,h,link,start,end,is_default,created_by,created_at) VALUES(?,?,?,?,?,?,NULL,NULL,1,NULL,?)')->execute(['60 ans de gestion (bandeau actuel)', 'default-60ans.png', 'image/png', 600, 200, 'https://www.gidsas.fr/', time()]);
        }
        $slug = fn(string $n) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $n) ?: $n)), '-');
        $admin = (int)$pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
        $ins = $pdo->prepare("INSERT INTO team_tasks(person,title,due,created_by,by_dir,created_at,priv) VALUES(?,?,?,?,1,?,0)");
        foreach ($pdo->query("SELECT u.id, u.name FROM space_members m JOIN users u ON u.id=m.user_id WHERE m.space='direction'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ($pdo->query('SELECT 1 FROM survey_answers WHERE submitted_at IS NOT NULL AND user_id=' . (int)$r['id'])->fetchColumn()) continue;
            $ins->execute([$slug($r['name']), 'Répondre au questionnaire de fonctionnement', date('Y-m-d', strtotime('+14 days')), $admin ?: 1, time()]);
        }
        $pdo->exec('PRAGMA user_version = 29;');
    }
    if ($v < 30) { // coordonnées professionnelles (ligne directe, raccourci) par adresse e-mail
        $pdo->exec('CREATE TABLE IF NOT EXISTS contacts(email TEXT PRIMARY KEY, phone TEXT, ext TEXT, updated_at INTEGER); PRAGMA user_version = 30;');
    }
    if ($v < 31) { // un bandeau à période n'est jamais le bandeau par défaut : on rend ce rôle au dernier bandeau sans période
        $pdo->exec("UPDATE banners SET is_default=0 WHERE start IS NOT NULL OR end IS NOT NULL");
        if (!(int)$pdo->query('SELECT COUNT(*) FROM banners WHERE is_default=1')->fetchColumn())
            $pdo->exec("UPDATE banners SET is_default=1 WHERE id=(SELECT id FROM banners WHERE start IS NULL AND end IS NULL ORDER BY created_at DESC LIMIT 1)");
        $pdo->exec('PRAGMA user_version = 31;');
    }
    if ($v < 32) { $pdo->exec("UPDATE banners SET link='https://www.gidsas.fr/'; PRAGMA user_version = 32;"); }
    if ($v < 33) { $pdo->exec("ALTER TABLE banners ADD COLUMN active_at INTEGER; PRAGMA user_version = 33;"); } // brouillons activés à la main
    if ($v < 34) { $pdo->exec("ALTER TABLE banners ADD COLUMN rotation INTEGER NOT NULL DEFAULT 0; ALTER TABLE banners ADD COLUMN rot_order INTEGER NOT NULL DEFAULT 0; PRAGMA user_version = 34;"); } // visuel différent chaque mois
    if ($v < 35) { // visuels fournis le 17/10/2026 : 4 en rotation mensuelle, fêtes, vœux, été programmés, saison des AG en brouillon
        $dir = DATA_DIR . '/banners'; if (!is_dir($dir)) @mkdir($dir, 0700, true); $o = 0;
        $ins = $pdo->prepare('INSERT INTO banners(title,file,mime,w,h,link,start,end,is_default,rotation,rot_order,created_at) VALUES(?,?,?,1200,400,?,?,?,0,?,?,?)');
        foreach ([['institutionnel', 'Institutionnel · Administrateur de biens', 'rotation'], ['syndic', 'Syndic · Gestion de proximité', 'rotation'], ['locative', 'Gestion locative · Valorisez votre patrimoine', 'rotation'], ['transaction', 'Transaction · Vendre ou louer à Paris', 'rotation'],
                  ['fetes', 'Belles fêtes de fin d\'année', '2026-12-15|2026-12-31'], ['voeux-2027', 'Meilleurs vœux 2027', '2027-01-01|2027-01-31'], ['ete', 'Bel été', '2027-07-14|2027-08-31'], ['saison-ag', 'Saison des assemblées générales', 'draft']] as [$f, $t, $m]) {
            $src = APP_DIR . '/seed/banners/' . $f . '.jpg'; if (!is_file($src)) continue;
            $chk = $pdo->prepare('SELECT 1 FROM banners WHERE title=?'); $chk->execute([$t]); if ($chk->fetchColumn()) continue;
            $file = 'seed-' . $f . '.jpg'; if (!@copy($src, $dir . '/' . $file)) continue;
            [$st, $en] = str_contains($m, '|') ? explode('|', $m) : [null, null];
            $ins->execute([$t, $file, 'image/jpeg', 'https://www.gidsas.fr/', $st, $en, $m === 'rotation' ? 1 : 0, $m === 'rotation' ? ++$o : 0, time()]);
        }
        $pdo->exec('PRAGMA user_version = 35;');
    }
    if ($v < 36) { // Mot du Président : un article par mois, brouillon tant qu'il n'est pas validé
        $pdo->exec("CREATE TABLE IF NOT EXISTS president_words(id INTEGER PRIMARY KEY, month TEXT NOT NULL UNIQUE, title TEXT NOT NULL, chapo TEXT, body TEXT NOT NULL, sources TEXT, status TEXT NOT NULL DEFAULT 'draft', validated_at INTEGER, validated_by INTEGER, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL); PRAGMA user_version = 36;");
    }
    if ($v < 37) { // Guides (Équipe) : procédures modifiables, version 1 depuis app/seed/guides.php
        $pdo->exec("CREATE TABLE IF NOT EXISTS guides(id INTEGER PRIMARY KEY, slug TEXT NOT NULL UNIQUE, cat TEXT NOT NULL, icon TEXT, title TEXT NOT NULL, summary TEXT, duration TEXT, level TEXT, body TEXT NOT NULL, pos INTEGER NOT NULL DEFAULT 0, updated_by INTEGER, created_at INTEGER NOT NULL, updated_at INTEGER NOT NULL)");
        $seed = is_file(APP_DIR . '/seed/guides.php') ? require APP_DIR . '/seed/guides.php' : [];
        $ins = $pdo->prepare('INSERT OR IGNORE INTO guides(slug,cat,icon,title,summary,duration,level,body,pos,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($seed as $i => [$slug, $cat, $icon, $title, $sum, $dur, $lvl, $body]) $ins->execute([$slug, $cat, $icon, $title, $sum, $dur, $lvl, $body, $i, time(), time()]);
        $pdo->exec('PRAGMA user_version = 37;');
    }
}

/** Préférences d'affichage propres à chaque utilisateur (colonnes visibles, exercice choisi…). */
const PREF_KEYS = ['hono_cols', 'hono_order', 'ha_year', 'ag_cols', 'ag_order', 'ag_sort'];
function user_prefs(int $uid): array {
    $o = [];
    foreach (q('SELECT k, v FROM user_prefs WHERE user_id=?', [$uid])->fetchAll() as $r) $o[$r['k']] = json_decode($r['v'], true);
    return $o;
}

/** Dernier audit d'un type (ex. honoraires), décodé. */
function audit_latest(string $kind): ?array {
    $a = q('SELECT * FROM audits WHERE kind=? ORDER BY period DESC LIMIT 1', [$kind])->fetch();
    if (!$a) return null;
    $a['d'] = json_decode($a['data'], true) ?: [];
    return $a;
}

/** Outil HTML chiffré dans le dépôt : mis en place (ou remplacé) en gardant les modifications de l'équipe. */
function seed_doc(PDO $pdo, string $slug, string $space): void {
    $f = APP_DIR . '/seed/' . $slug . '.enc';
    if (!is_file($f)) return;
    $html = unseal(trim((string)file_get_contents($f)));
    if ($html === null) return;
    $key = preg_match('/STORE_KEY\s*=\s*["\']([^"\']+)["\']/', $html, $m) ? $m[1] : null;
    $title = preg_match('/<title>(.*?)<\/title>/is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : $slug;
    $pdo->prepare('INSERT INTO docs(slug,title,html,store_key,space,updated_at,updated_by) VALUES(?,?,?,?,?,?,NULL)
       ON CONFLICT(slug) DO UPDATE SET title=excluded.title, html=excluded.html, store_key=excluded.store_key, updated_at=excluded.updated_at')
        ->execute([$slug, $title, $html, $key, $space, time()]);
}

/**
 * Données de départ chiffrées (comptes, immeubles) : le dépôt est public, elles y sont stockées
 * chiffrées avec APP_KEY, qui n'existe que dans la configuration privée du serveur.
 */
function seed_read(string $name): ?array {
    $f = APP_DIR . '/seed/' . $name . '.enc';
    if (!is_file($f)) return null;
    $plain = unseal(trim((string)file_get_contents($f)));
    $d = $plain === null ? null : json_decode($plain, true);
    return is_array($d) ? $d : null;
}

function seed_from_encrypted(PDO $pdo): void {
    $users = seed_read('users');
    if ($users) {
        foreach ($users as $s) {
            $id = $pdo->prepare('SELECT id FROM users WHERE email=?'); $id->execute([$s['email']]); $id = $id->fetchColumn();
            if (!$id) {
                $pdo->prepare('INSERT INTO users(email,name,initials,role,totp_enabled,password_hash,must_change,default_expires,created_at) VALUES(?,?,?,?,0,?,1,?,?)')
                    ->execute([$s['email'], $s['name'], $s['initials'], $s['role'], $s['hash'], time() + 30 * 86400, time()]);
                $id = (int)$pdo->lastInsertId();
            } else {
                $pdo->prepare('UPDATE users SET name=?, initials=? WHERE id=?')->execute([$s['name'], $s['initials'], $id]);
            }
            foreach ($s['spaces'] as $sp) $pdo->prepare('INSERT OR IGNORE INTO space_members(space,user_id) VALUES(?,?)')->execute([$sp, $id]);
        }
    }
    $imm = seed_read('immeubles');
    if ($imm) {
        $b = $pdo->prepare('INSERT OR REPLACE INTO buildings(code,kind,label,addr2,cp,ville,gest,compta,assist,exercice) VALUES(?,?,?,?,?,?,?,?,?,?)');
        foreach ($imm['buildings'] as $r) $b->execute([$r['code'], $r['kind'], $r['label'], $r['addr2'], $r['cp'], $r['ville'], $r['gest'], $r['compta'], $r['assist'], $r['exercice']]);
        $st = $pdo->prepare('INSERT OR IGNORE INTO staff(name,role,email,phone,updated_at) VALUES(?,?,?,?,?)');
        foreach ($imm['staff'] as $r) $st->execute([$r['name'], $r['role'], $r['email'], null, time()]);
    }
}

function q(string $sql, array $p = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}

function audit(?int $uid, string $event): void {
    q('INSERT INTO audit(user_id,event,ip,at) VALUES(?,?,?,?)', [$uid, $event, client_ip(), time()]);
}

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

/** Limite simple : max $max tentatives par clé sur $window secondes. */
function rate_limited(string $key, int $max = 6, int $window = 900): bool {
    q('DELETE FROM attempts WHERE at < ?', [time() - 86400]);
    $n = (int)q('SELECT COUNT(*) FROM attempts WHERE k=? AND at>?', [$key, time() - $window])->fetchColumn();
    return $n >= $max;
}
function rate_hit(string $key): void { q('INSERT INTO attempts(k,at) VALUES(?,?)', [$key, time()]); }
function rate_clear(string $key): void { q('DELETE FROM attempts WHERE k=?', [$key]); }

/* Blocage de l'adresse IP : 5 échecs de connexion en 30 minutes => IP bloquée 30 minutes. */
const IP_MAX_FAILS = 5;
const IP_BLOCK_SECONDS = 1800;
function ip_blocked(): bool { return rate_limited('fail:' . client_ip(), IP_MAX_FAILS, IP_BLOCK_SECONDS); }
function ip_fail(?int $uid = null, string $what = 'login'): void {
    rate_hit('fail:' . client_ip());
    if (ip_blocked()) audit($uid, 'ip_blocked:' . $what);
}
function ip_ok(): void { rate_clear('fail:' . client_ip()); }
function ip_block_guard(): void {
    if (ip_blocked()) json_out(['error' => 'Trop de tentatives échouées : cette adresse IP est bloquée pendant 30 minutes.'], 429);
}
