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
