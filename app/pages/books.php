<?php
declare(strict_types=1);

/* Mes livres : bibliothèque personnelle (EPUB / PDF) et liseuse. Chaque livre n'est visible que par son propriétaire. */

const BOOK_KINDS = ['epub' => 'application/epub+zip', 'pdf' => 'application/pdf'];
const BOOK_MAX = 200 * 1024 * 1024;

function book_dir(int $uid): string {
    $d = DATA_DIR . '/books/' . $uid;
    if (!is_dir($d)) mkdir($d, 0750, true);
    return $d;
}
function book_path(array $b): string { return book_dir((int)$b['user_id']) . '/' . (int)$b['id'] . '.' . $b['kind']; }
function book_get(array $u, int $id): ?array {
    $b = q('SELECT * FROM books WHERE id=? AND user_id=?', [$id, (int)$u['id']])->fetch();
    return $b ?: null;
}

function page_books(array $u): never {
    $books = q('SELECT b.*, s.percent, s.read_at FROM books b LEFT JOIN book_state s ON s.book_id=b.id WHERE b.user_id=? AND b.size>0
                ORDER BY COALESCE(s.read_at, b.created_at) DESC', [(int)$u['id']])->fetchAll();
    render('books', ['title' => 'Mes livres', 'nav' => 'books', 'u' => $u, 'books' => $books]);
}

function page_book_read(array $u, int $id): never {
    $b = book_get($u, $id);
    if (!$b || !(int)$b['size'] || !is_file(book_path($b))) { http_response_code(404); render('notfound', ['title' => 'Introuvable', 'u' => $u]); }
    $st = q('SELECT * FROM book_state WHERE book_id=?', [$id])->fetch() ?: [];
    // La liseuse affiche le livre dans un cadre (images en blob:) : politique de sécurité propre à cette page
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: blob: https://fonts.gstatic.com; img-src 'self' data: blob:; frame-src 'self'; connect-src 'self'; frame-ancestors 'none'");
    render('book_reader', ['title' => $b['title'], 'u' => $u, 'book' => $b, 'cfg' => [
        'id' => (int)$b['id'], 'kind' => $b['kind'], 'title' => $b['title'], 'author' => (string)$b['author'],
        'file' => '/mes-livres/' . (int)$b['id'] . '/fichier', 'csrf' => $_SESSION['csrf'],
        'pos' => json_decode((string)($st['pos'] ?? ''), true), 'percent' => (float)($st['percent'] ?? 0),
        'bookmarks' => json_decode((string)($st['bookmarks'] ?? ''), true) ?: [], 'highlights' => json_decode((string)($st['highlights'] ?? ''), true) ?: [],
    ]], false);
}

function book_file(array $u, int $id): never {
    $b = book_get($u, $id);
    $p = $b ? book_path($b) : '';
    if (!$b || !is_file($p)) { http_response_code(404); exit; }
    header('Content-Type: ' . BOOK_KINDS[$b['kind']]);
    header('Content-Length: ' . filesize($p));
    header('Cache-Control: private, max-age=3600');
    header('Content-Disposition: inline; filename="livre-' . (int)$b['id'] . '.' . $b['kind'] . '"');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'self'");
    readfile($p);
    exit;
}

/** Actions API (utilisateur connecté). */
function books_api(string $r, array $u): never {
    $uid = (int)$u['id'];
    switch ($r) {
        case 'books/start': // nouvel envoi : {title, kind, size}
            $kind = in_str('kind', 10);
            $size = in_int('size');
            if (!isset(BOOK_KINDS[$kind])) json_out(['error' => 'Format accepté : EPUB ou PDF.'], 400);
            if ($size <= 0 || $size > BOOK_MAX) json_out(['error' => 'Fichier trop volumineux (200 Mo au plus).'], 400);
            $title = in_str('title', 200) ?: 'Sans titre';
            q('INSERT INTO books(user_id,title,kind,size,created_at,updated_at) VALUES(?,?,?,0,?,?)', [$uid, $title, $kind, time(), time()]);
            $id = (int)db()->lastInsertId();
            file_put_contents(book_dir($uid) . "/$id.part", '');
            json_out(['ok' => true, 'id' => $id]);
        case 'books/chunk': // morceau binaire (corps brut), en-têtes X-Book et X-Offset
            $b = book_get($u, (int)($_SERVER['HTTP_X_BOOK'] ?? 0));
            $part = $b ? book_dir($uid) . '/' . (int)$b['id'] . '.part' : '';
            if (!$b || !is_file($part)) json_out(['error' => 'Envoi introuvable.'], 404);
            clearstatcache();
            if (filesize($part) !== (int)($_SERVER['HTTP_X_OFFSET'] ?? -1)) json_out(['error' => 'Morceau hors séquence.'], 409);
            $data = file_get_contents('php://input') ?: '';
            if (filesize($part) + strlen($data) > BOOK_MAX) json_out(['error' => 'Fichier trop volumineux.'], 400);
            file_put_contents($part, $data, FILE_APPEND | LOCK_EX);
            json_out(['ok' => true, 'size' => filesize($part)]);
        case 'books/done':
            $b = book_get($u, in_int('id'));
            $part = $b ? book_dir($uid) . '/' . (int)$b['id'] . '.part' : '';
            if (!$b || !is_file($part)) json_out(['error' => 'Envoi introuvable.'], 404);
            $head = (string)file_get_contents($part, false, null, 0, 5);
            $ok = $b['kind'] === 'epub' ? str_starts_with($head, "PK\x03\x04") : str_starts_with($head, '%PDF');
            if (!$ok) { @unlink($part); q('DELETE FROM books WHERE id=?', [$b['id']]); json_out(['error' => 'Le fichier n\'est pas un ' . strtoupper($b['kind']) . ' valide.'], 400); }
            rename($part, book_path($b));
            clearstatcache();
            q('UPDATE books SET size=?, updated_at=? WHERE id=?', [filesize(book_path($b)), time(), $b['id']]);
            json_out(['ok' => true, 'id' => (int)$b['id']]);
        case 'books/meta': // titre, auteur, couverture (image data: JPEG/PNG réduite côté navigateur)
            $b = book_get($u, in_int('id'));
            if (!$b) json_out(['error' => 'Livre introuvable.'], 404);
            $d = body();
            $title = array_key_exists('title', $d) ? (in_str('title', 200) ?: $b['title']) : $b['title'];
            $author = array_key_exists('author', $d) ? in_str('author', 200) : $b['author'];
            $cover = $b['cover'];
            if (array_key_exists('cover', $d)) {
                $c = (string)$d['cover'];
                $cover = ($c !== '' && strlen($c) < 400000 && preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$#', $c)) ? $c : null;
            }
            q('UPDATE books SET title=?, author=?, cover=?, updated_at=? WHERE id=?', [$title, $author, $cover, time(), $b['id']]);
            json_out(['ok' => true]);
        case 'books/state': // position de lecture, signets, surlignements et notes (seuls les champs envoyés sont modifiés)
            $b = book_get($u, in_int('id'));
            if (!$b) json_out(['error' => 'Livre introuvable.'], 404);
            $d = body();
            $cur = q('SELECT * FROM book_state WHERE book_id=?', [$b['id']])->fetch() ?: ['pos' => null, 'percent' => 0, 'bookmarks' => null, 'highlights' => null];
            $enc = function ($v, int $max) { $j = json_encode($v, JSON_UNESCAPED_UNICODE); if ($j === false || strlen($j) > $max) json_out(['error' => 'Données trop volumineuses.'], 400); return $j; };
            $pos = array_key_exists('pos', $d) ? $enc($d['pos'], 2000) : $cur['pos'];
            $pct = array_key_exists('percent', $d) ? max(0, min(100, (float)$d['percent'])) : (float)$cur['percent'];
            $bm = array_key_exists('bookmarks', $d) && is_array($d['bookmarks']) ? $enc(array_slice($d['bookmarks'], 0, 2000), 400000) : $cur['bookmarks'];
            $hl = array_key_exists('highlights', $d) && is_array($d['highlights']) ? $enc(array_slice($d['highlights'], 0, 10000), 3000000) : $cur['highlights'];
            q('INSERT INTO book_state(book_id,user_id,pos,percent,bookmarks,highlights,read_at) VALUES(?,?,?,?,?,?,?)
               ON CONFLICT(book_id) DO UPDATE SET pos=excluded.pos, percent=excluded.percent, bookmarks=excluded.bookmarks, highlights=excluded.highlights, read_at=excluded.read_at',
              [$b['id'], $uid, $pos, $pct, $bm, $hl, time()]);
            json_out(['ok' => true]);
        case 'books/delete':
            $b = book_get($u, in_int('id'));
            if (!$b) json_out(['error' => 'Livre introuvable.'], 404);
            @unlink(book_path($b)); @unlink(book_dir($uid) . '/' . (int)$b['id'] . '.part');
            q('DELETE FROM book_state WHERE book_id=?', [$b['id']]);
            q('DELETE FROM books WHERE id=?', [$b['id']]);
            audit($uid, 'book_delete:' . (int)$b['id']);
            json_out(['ok' => true]);
    }
    json_out(['error' => 'Action inconnue'], 404);
}
