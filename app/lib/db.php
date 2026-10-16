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
}

/** Préférences d'affichage propres à chaque utilisateur (colonnes visibles, exercice choisi…). */
const PREF_KEYS = ['hono_cols', 'hono_order', 'ha_year'];
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
