<?php
declare(strict_types=1);

/* Recherche sur toute la plateforme, limitée à ce que la personne a le droit de voir.
   Titres, textes et documents (texte des livres EPUB, et texte des documents numérisés par OCR à mesure qu'ils sont importés). */

/** Texte d'un document (livre, PDF numérisé…) pour la recherche : enregistré à l'import, ou par OCR plus tard. */
function doc_text_save(string $kind, string $ref, string $title, string $text, ?int $owner, ?string $space, string $url, bool $needsOcr = false, bool $ocr = false): void {
    $text = mb_substr(trim(preg_replace('/\s+/u', ' ', $text)), 0, 400000);
    q('INSERT INTO doc_texts(kind,ref,title,text,owner_id,space,url,needs_ocr,ocr,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)
       ON CONFLICT(kind,ref) DO UPDATE SET title=excluded.title, text=CASE WHEN excluded.text<>\'\' THEN excluded.text ELSE doc_texts.text END, owner_id=excluded.owner_id, space=excluded.space, url=excluded.url,
         needs_ocr=excluded.needs_ocr, ocr=MAX(doc_texts.ocr, excluded.ocr), updated_at=excluded.updated_at',
      [$kind, $ref, $title, $text, $owner, $space, $url, $needsOcr ? 1 : 0, $ocr ? 1 : 0, time()]);
}
function doc_text_delete(string $kind, string $ref): void { q('DELETE FROM doc_texts WHERE kind=? AND ref=?', [$kind, $ref]); }

/** Texte d'un EPUB (chapitres HTML de l'archive). */
function epub_text(string $path): string {
    if (!class_exists('ZipArchive')) return '';
    $z = new ZipArchive(); if ($z->open($path) !== true) return '';
    $out = '';
    for ($i = 0; $i < $z->numFiles && mb_strlen($out) < 400000; $i++) {
        $n = (string)$z->getNameIndex($i);
        if (!preg_match('/\.(x?html?)$/i', $n)) continue;
        $h = (string)$z->getFromIndex($i);
        $out .= ' ' . html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', '', $h)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $z->close();
    return $out;
}

/** Extrait autour du premier terme trouvé, mis en évidence. */
function search_snip(string $txt, array $terms, int $len = 170): string {
    $txt = trim(preg_replace(['/\[\[([^|\]]+)\|[^\]]*\]\]/u', '/[*#>`_]{1,3}/u', '/\s+/u'], ['$1', '', ' '], html_entity_decode(strip_tags($txt), ENT_QUOTES)));
    $low = mb_strtolower($txt); $pos = 0;
    foreach ($terms as $t) { $p = mb_strpos($low, $t); if ($p !== false) { $pos = $p; break; } }
    $start = max(0, $pos - 60); $s = mb_substr($txt, $start, $len);
    $s = ($start ? '…' : '') . $s . (mb_strlen($txt) > $start + $len ? '…' : '');
    $h = e($s);
    foreach ($terms as $t) if ($t !== '') $h = preg_replace('/(' . preg_quote(e($t), '/') . ')/iu', '<mark>$1</mark>', $h);
    return $h;
}

/**
 * Résultats groupés : [['k' => clé, 'label' => libellé, 'icon' => …, 'items' => [['title','snip','url','meta']]]].
 * Chaque source vérifie les droits de la personne (section, auteur, destinataire).
 */
function search_all(array $u, string $q, int $per = 6): array {
    $q = trim(mb_substr($q, 0, 80));
    $terms = array_values(array_filter(array_map(fn($t) => mb_strtolower($t), preg_split('/\s+/u', $q)), fn($t) => mb_strlen($t) >= 2));
    if (!$terms) return [];
    $uid = (int)$u['id']; $dir = can_space($u, 'direction'); $fam = can_space($u, 'famille'); $sp = user_spaces($u);
    // Condition « tous les mots » sur plusieurs colonnes
    $like = function (array $cols) use ($terms): array { $w = []; $p = []; foreach ($terms as $t) { $w[] = '(' . implode(' OR ', array_map(fn($c) => "lower($c) LIKE ?", $cols)) . ')'; foreach ($cols as $c) $p[] = '%' . $t . '%'; } return [implode(' AND ', $w), $p]; };
    $hit = fn(string $hay) => array_reduce($terms, fn($ok, $t) => $ok && mb_strpos(mb_strtolower($hay), $t) !== false, true);
    $G = []; $add = function (string $k, string $label, string $icon, array $items) use (&$G, $per) { if ($items) $G[] = ['k' => $k, 'label' => $label, 'icon' => $icon, 'items' => array_slice($items, 0, $per), 'more' => max(0, count($items) - $per)]; };
    $safe = function (callable $f) { try { return $f(); } catch (Throwable $e) { error_log("search: " . $e->getMessage()); return []; } };

    // Pages de la plateforme
    $pages = [['Tableau de bord perso', '/', true], ['Mails à traiter', '/espace', true], ['Mot du Président', '/mot-du-president', true], ['Mes tâches', '/mes-taches', true], ['Tâches IA', '/assistant', true],
        ['Bloc-notes', '/bloc-notes', true], ['Mes livres', '/mes-livres', true], ['Tableau de bord Équipe', '/tableau-de-bord', true], ['Actualités du métier', '/actualites', true], ['Informations', '/gid', true],
        ['Collaborateurs', '/organigramme', true], ['Accueil', '/accueil', true], ['Guides', '/guides', true], ['Sécurité', '/securite', true], ['Nouveautés (versioning)', '/versioning', true],
        ['Tableau de bord Direction', '/direction', $dir], ['Immeubles', '/immeubles', $dir], ['Planning des AG', '/planning-ag', $dir], ['Honoraires', '/honoraires', $dir], ['Stratégies', '/strategies', $dir],
        ['Comptabilité', '/comptabilite', $dir], ['Pilotage', '/pilotage', $dir], ['Enquête', '/enquete/resultats', $dir], ['Signatures e-mail', '/signatures', $dir],
        ['Site Web · statistiques', '/site-web', wp_poster($u)], ['Site Web · annonces', '/site-web/annonces', wp_poster($u)], ['Appli Annonces', '/annonces/', wp_poster($u)],
        ['Espace Famille', '/famille', $fam], ['Voyages', '/voyages', $fam]];
    foreach ($safe(fn() => q('SELECT slug, title, space FROM docs')->fetchAll()) as $d) // tableaux importés (honoraires…)
        if (!in_array('/' . $d['slug'], array_column($pages, 1), true)) $pages[] = [$d['title'], '/' . $d['slug'], can_space($u, $d['space'] ?: 'famille')];
    $add('pages', 'Pages', '🧭', array_values(array_map(fn($p) => ['title' => $p[0], 'snip' => '', 'url' => $p[1], 'meta' => 'Page'], array_filter($pages, fn($p) => $p[2] && $hit($p[0])))));

    // Guides
    $add('guides', 'Guides', '📘', $safe(function () use ($like, $u, $terms) { [$w, $p] = $like(['title', 'summary', 'body']); $o = [];
        foreach (q("SELECT slug, title, summary, body, cat, audience FROM guides WHERE $w ORDER BY pos LIMIT 40", $p)->fetchAll() as $g) if (guide_visible($u, $g['audience'] ?? 'tous'))
            $o[] = ['title' => $g['title'], 'snip' => search_snip($g['summary'] . ' ' . guide_plain($g['body']), $terms), 'url' => '/guides/' . $g['slug'], 'meta' => $g['cat']]; return $o; }));

    // Collaborateurs
    $add('team', 'Collaborateurs', '👥', $safe(function () use ($hit) { $o = [];
        foreach (team_people() as $k => $p) if ($hit($p['name'] . ' ' . $p['role'] . ' ' . $p['email'] . ' ' . ($p['phone'] ?? '')))
            $o[] = ['title' => $p['name'], 'snip' => e(trim($p['role'] . ' · ' . $p['email'] . ($p['phone'] ? ' · ' . $p['phone'] : ''), ' ·')), 'url' => '/equipe/' . $k, 'meta' => 'Fiche']; return $o; }));

    // Tâches (équipe, sections, IA)
    $add('tasks', 'Tâches', '✅', $safe(function () use ($like, $u, $uid, $sp, $terms) { $o = [];
        [$w, $p] = $like(['t.title']); [$tw, $tp] = team_where($u);
        foreach (q("SELECT t.* FROM team_tasks t WHERE $w AND $tw ORDER BY t.done_at IS NOT NULL, t.created_at DESC LIMIT 30", array_merge($p, $tp))->fetchAll() as $t)
            $o[] = ['title' => $t['title'], 'snip' => '', 'url' => '/mes-taches', 'meta' => ($t['done_at'] ? 'Terminée' : 'En cours') . ($t['due'] ? ' · échéance ' . date('d/m/Y', strtotime($t['due'])) : '')];
        [$w, $p] = $like(['title']); $in = implode(',', array_fill(0, count($sp), '?'));
        foreach (q("SELECT * FROM tasks WHERE $w AND (user_id=? OR space IN ($in)) ORDER BY done, created_at DESC LIMIT 20", array_merge($p, [$uid], $sp))->fetchAll() as $t)
            $o[] = ['title' => $t['title'], 'snip' => '', 'url' => $t['space'] ? '/' . ($t['space'] === 'gid' ? 'gid' : $t['space']) : '/mes-taches', 'meta' => ($t['done'] ? 'Terminée' : 'En cours') . ($t['space'] ? ' · ' . (SPACES[$t['space']] ?? $t['space']) : ' · perso')];
        [$w, $p] = $like(['body', 'result']);
        foreach (q("SELECT * FROM consignes WHERE user_id=? AND $w ORDER BY created_at DESC LIMIT 20", array_merge([$uid], $p))->fetchAll() as $c)
            $o[] = ['title' => mb_strimwidth($c['body'], 0, 90, '…'), 'snip' => search_snip($c['body'] . ' ' . $c['result'], $terms), 'url' => '/assistant', 'meta' => 'Tâche IA · ' . (CONSIGNE_STATUS[$c['status']] ?? $c['status'])];
        return $o; }));

    // Mails à traiter (les siens)
    $add('mails', 'Mails à traiter', '✉️', $safe(function () use ($like, $uid, $terms) { [$w, $p] = $like(['subject', 'summary', 'from_name', 'from_email']); $o = [];
        foreach (q("SELECT * FROM mail_items WHERE user_id=? AND $w ORDER BY done, id DESC LIMIT 20", array_merge([$uid], $p))->fetchAll() as $m)
            $o[] = ['title' => $m['subject'], 'snip' => search_snip(($m['from_name'] ?: $m['from_email']) . ' · ' . $m['summary'], $terms), 'url' => '/espace', 'meta' => $m['done'] ? 'Traité' : 'À traiter']; return $o; }));

    // Notes (la sienne + notes des sections)
    $add('notes', 'Notes', '📝', $safe(function () use ($uid, $sp, $hit, $terms) { $o = [];
        $n = q('SELECT body FROM notes WHERE user_id=?', [$uid])->fetchColumn(); if ($n && $hit($n)) $o[] = ['title' => 'Mon bloc-notes', 'snip' => search_snip($n, $terms), 'url' => '/bloc-notes', 'meta' => 'Privé'];
        foreach (q('SELECT space, body FROM space_notes')->fetchAll() as $s) if (in_array($s['space'], $sp, true) && $hit($s['body']))
            $o[] = ['title' => 'Bloc-notes ' . (SPACES[$s['space']] ?? $s['space']), 'snip' => search_snip($s['body'], $terms), 'url' => '/' . $s['space'], 'meta' => 'Commun'];
        return $o; }));

    // Actualités, veille, informations, Mot du Président
    $add('news', 'Actualités et veille', '📰', $safe(function () use ($like, $terms) { $o = [];
        [$w, $p] = $like(['title', 'summary', 'why', 'source']);
        foreach (q("SELECT * FROM news_items WHERE $w ORDER BY date DESC LIMIT 30", $p)->fetchAll() as $n)
            $o[] = ['title' => $n['title'], 'snip' => search_snip($n['summary'] . ' ' . $n['why'], $terms), 'url' => '/actualites/' . ($n['date'] < news_since() ? 'archives?q=' . rawurlencode(mb_substr($n['title'], 0, 40)) : $n['cat']), 'meta' => (NEWS_CATS[$n['cat']][0] ?? '') . ' · ' . date('d/m/Y', strtotime($n['date'])) . ($n['important'] ? ' · Important' : '')];
        [$w, $p] = $like(['title', 'summary', 'impact', 'source']);
        foreach (q("SELECT * FROM veille WHERE $w ORDER BY week DESC LIMIT 20", $p)->fetchAll() as $v)
            $o[] = ['title' => $v['title'], 'snip' => search_snip($v['summary'] . ' ' . $v['impact'], $terms), 'url' => '/tableau-de-bord', 'meta' => 'Veille juridique · ' . $v['week']];
        [$w, $p] = $like(['title', 'body']);
        foreach (q("SELECT * FROM board_posts WHERE $w ORDER BY created_at DESC LIMIT 20", $p)->fetchAll() as $b)
            $o[] = ['title' => $b['title'], 'snip' => search_snip($b['body'], $terms), 'url' => '/gid', 'meta' => 'Informations'];
        return $o; }));
    $add('pw', 'Mot du Président', '🖋️', $safe(function () use ($like, $u, $terms) { [$w, $p] = $like(['title', 'chapo', 'body']); $o = []; $ed = pw_editor($u);
        foreach (q("SELECT * FROM president_words WHERE $w ORDER BY month DESC LIMIT 12", $p)->fetchAll() as $x) if ($ed || pw_published($x))
            $o[] = ['title' => $x['title'], 'snip' => search_snip($x['chapo'] . ' ' . $x['body'], $terms), 'url' => '/mot-du-president/' . $x['month'], 'meta' => pw_month_label($x['month'])]; return $o; }));

    // Direction : immeubles, contacts des conseils syndicaux, historique
    if ($dir) {
        $add('imm', 'Immeubles', '🏢', $safe(function () use ($like, $terms) { $o = [];
            [$w, $p] = $like(['b.code', 'b.label', 'b.addr2', 'b.ville', 'b.gest', 'b.compta', 'i.president', 'i.cs_members', 'i.gardien', 'i.notes']);
            foreach (q("SELECT b.*, i.president, i.notes FROM buildings b LEFT JOIN building_info i ON i.code=b.code WHERE $w ORDER BY b.code LIMIT 40", $p)->fetchAll() as $b)
                $o[] = ['title' => $b['code'] . ' · ' . $b['label'], 'snip' => search_snip(trim(($b['gest'] ? 'Gestionnaire : ' . $b['gest'] . ' · ' : '') . ($b['president'] ? 'Président du CS : ' . $b['president'] . ' · ' : '') . $b['notes'], ' ·'), $terms), 'url' => '/immeubles/' . $b['code'], 'meta' => trim($b['cp'] . ' ' . $b['ville'])];
            [$w, $p] = $like(['e.title', 'e.detail']);
            foreach (q("SELECT e.*, b.label FROM building_events e LEFT JOIN buildings b ON b.code=e.code WHERE $w ORDER BY e.at DESC LIMIT 20", $p)->fetchAll() as $e)
                $o[] = ['title' => $e['title'], 'snip' => search_snip($e['detail'] ?? '', $terms), 'url' => '/immeubles/' . $e['code'], 'meta' => $e['code'] . ' ' . $e['label'] . ' · ' . date('d/m/Y', (int)$e['at'])];
            return $o; }));
    }
    // Site Web : annonces
    if (wp_poster($u)) $add('ann', 'Annonces du site', '🔑', $safe(function () use ($hit) { $o = [];
        $s = json_decode((string)q("SELECT v FROM kv WHERE k='wpc:snapshot'")->fetchColumn(), true) ?: [];
        foreach ((array)($s['annonces'] ?? []) as $a) if ($hit($a['title'])) $o[] = ['title' => $a['title'], 'snip' => '', 'url' => '/annonces/#modifier-' . $a['id'], 'meta' => $a['status'] === 'publish' ? 'En ligne' : 'Brouillon'];
        return $o; }));
    // Famille : voyages
    if ($fam) $add('trips', 'Voyages', '🧳', $safe(function () use ($like, $terms) { [$w, $p] = $like(['destination', 'data']); $o = [];
        foreach (q("SELECT id, destination, duration, data FROM trips WHERE $w ORDER BY updated_at DESC LIMIT 10", $p)->fetchAll() as $t)
            $o[] = ['title' => $t['destination'] . ($t['duration'] ? ' · ' . $t['duration'] : ''), 'snip' => search_snip(implode(' ', array_filter(array_map(fn($v) => is_string($v) ? $v : '', json_decode((string)$t['data'], true) ?: []))), $terms), 'url' => '/voyages/' . $t['id'], 'meta' => 'Voyage']; return $o; }));

    // Documents : livres (les siens) et documents numérisés selon la section
    $add('docs', 'Documents', '📄', $safe(function () use ($like, $uid, $sp, $terms) { $o = []; $in = implode(',', array_fill(0, count($sp), '?'));
        [$w, $p] = $like(['title', 'text']);
        foreach (q("SELECT kind, ref, title, text, url, ocr FROM doc_texts WHERE $w AND (owner_id=? OR (owner_id IS NULL AND space IN ($in))) ORDER BY updated_at DESC LIMIT 20", array_merge($p, [$uid], $sp))->fetchAll() as $d)
            $o[] = ['title' => $d['title'], 'snip' => search_snip($d['text'], $terms), 'url' => $d['url'], 'meta' => ($d['kind'] === 'book' ? 'Livre' : 'Document') . ($d['ocr'] ? ' · texte numérisé (OCR)' : '')];
        [$w, $p] = $like(['title', 'author']);
        foreach (q("SELECT id, title, author FROM books WHERE user_id=? AND $w ORDER BY updated_at DESC LIMIT 10", array_merge([$uid], $p))->fetchAll() as $b)
            if (!array_filter($o, fn($x) => $x['url'] === '/mes-livres/' . $b['id'])) $o[] = ['title' => $b['title'], 'snip' => e((string)$b['author']), 'url' => '/mes-livres/' . $b['id'], 'meta' => 'Livre'];
        return $o; }));
    return $G;
}

function page_search(array $u): never {
    $q = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 80));
    render('search', ['title' => 'Recherche', 'nav' => 'search', 'u' => $u, 'q' => $q, 'groups' => $q !== '' ? search_all($u, $q, 50) : []]);
}
