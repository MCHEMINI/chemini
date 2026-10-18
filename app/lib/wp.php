<?php
declare(strict_types=1);

/* Site Web : administration déportée du site WordPress de GID (préproduction puis production) via l'API REST.
   Connexion : identifiant + mot de passe d'application WordPress, saisis par l'administrateur et chiffrés en base. */

const WP_DEFAULT_URL = 'https://preprod.gidsas.fr';
const WP_CACHE_TTL = 600; // 10 minutes

function wp_viewer(array $u): bool { return can_space($u, 'direction'); }
function wp_admin(array $u): bool { return ($u['role'] ?? '') === 'admin'; }

/** Paramètres de connexion : ['url','user','pass','at','by'] ou null. Le mot de passe n'est jamais renvoyé au navigateur. */
function wp_conn(): ?array {
    $raw = q("SELECT v FROM kv WHERE k='wp_conn'")->fetchColumn();
    if (!$raw) return null;
    $c = json_decode((string)$raw, true); if (!$c || empty($c['url'])) return null;
    $c['pass'] = !empty($c['pass_enc']) ? (unseal($c['pass_enc']) ?? '') : '';
    return $c;
}
function wp_conn_save(string $url, string $user, string $pass, int $by): void {
    q("INSERT INTO kv(k,v,updated_at) VALUES('wp_conn',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at",
      [json_encode(['url' => $url, 'user' => $user, 'pass_enc' => seal($pass), 'at' => time(), 'by' => $by], JSON_UNESCAPED_SLASHES), time()]);
    wp_cache_clear();
}
function wp_conn_clear(): void { q("DELETE FROM kv WHERE k='wp_conn'"); wp_cache_clear(); }
function wp_url(): string { return rtrim((wp_conn()['url'] ?? WP_DEFAULT_URL), '/'); }
function wp_normalize_url(string $u): ?string {
    $u = trim($u); if ($u === '') return null;
    if (!preg_match('~^https://[a-z0-9.-]+(?::\d+)?(/[^\s?#]*)?$~i', $u)) return null; // HTTPS uniquement
    return rtrim(preg_replace('#/wp-admin.*$|/wp-json.*$#i', '', $u), '/');
}

/**
 * Appel REST : wp_req('GET', 'wp/v2/pages', ['per_page' => 100]) → ['code' => 200, 'json' => …, 'total' => X-WP-Total] ; code 0 si injoignable.
 * $conn permet de tester des identifiants avant de les enregistrer.
 */
function wp_req(string $method, string $path, array $query = [], ?array $body = null, ?array $conn = null): array {
    $c = $conn ?? wp_conn();
    if (!$c || empty($c['user']) || empty($c['pass'])) return ['code' => -1, 'json' => null, 'total' => null, 'error' => 'Site non connecté'];
    $url = rtrim($c['url'], '/') . '/wp-json/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : '');
    $hdr = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_USERPWD => $c['user'] . ':' . str_replace(' ', '', $c['pass']),
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_USERAGENT => 'GID-Dashboard/1.0 (+' . BASE_URL . ')',
        CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$hdr) { $p = explode(':', $h, 2); if (count($p) === 2) $hdr[strtolower(trim($p[0]))] = trim($p[1]); return strlen($h); },
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    $json = is_string($res) ? json_decode($res, true) : null;
    return ['code' => $res === false ? 0 : $code, 'json' => $json, 'total' => isset($hdr['x-wp-total']) ? (int)$hdr['x-wp-total'] : null,
            'error' => $res === false ? ($err ?: 'Site injoignable') : ($code >= 400 ? (string)($json['message'] ?? ('Erreur ' . $code)) : null)];
}

/** Plusieurs lectures en parallèle : [clé => [chemin, query]] → [clé => réponse wp_req]. Le site répond en ~1 s par appel : en parallèle, tout arrive en 1 à 2 s. */
function wp_req_multi(array $reqs): array {
    $c = wp_conn(); $out = [];
    if (!$c || empty($c['user']) || empty($c['pass'])) { foreach ($reqs as $k => $_) $out[$k] = ['code' => -1, 'json' => null, 'total' => null, 'error' => 'Site non connecté']; return $out; }
    $mh = curl_multi_init(); $hs = []; $hdr = [];
    foreach ($reqs as $k => [$path, $query]) {
        $hdr[$k] = [];
        $ch = curl_init(rtrim($c['url'], '/') . '/wp-json/' . ltrim($path, '/') . ($query ? '?' . http_build_query($query) : ''));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERPWD => $c['user'] . ':' . str_replace(' ', '', $c['pass']), CURLOPT_HTTPHEADER => ['Accept: application/json'], CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'GID-Dashboard/1.0 (+' . BASE_URL . ')',
            CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$hdr, $k) { $p = explode(':', $h, 2); if (count($p) === 2) $hdr[$k][strtolower(trim($p[0]))] = trim($p[1]); return strlen($h); }]);
        curl_multi_add_handle($mh, $ch); $hs[$k] = $ch;
    }
    do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $st === CURLM_OK);
    foreach ($hs as $k => $ch) {
        $res = curl_multi_getcontent($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch);
        $json = is_string($res) && $res !== '' ? json_decode($res, true) : null;
        $out[$k] = ['code' => ($res === null || $res === '' && $code === 0) ? 0 : $code, 'json' => $json, 'total' => isset($hdr[$k]['x-wp-total']) ? (int)$hdr[$k]['x-wp-total'] : null, 'pages' => isset($hdr[$k]['x-wp-totalpages']) ? (int)$hdr[$k]['x-wp-totalpages'] : 1,
            'error' => $code === 0 ? ($err ?: 'Site injoignable') : ($code >= 400 ? (string)($json['message'] ?? ('Erreur ' . $code)) : null)];
        curl_multi_remove_handle($mh, $ch); curl_close($ch);
    }
    curl_multi_close($mh);
    return $out;
}
/** Collection complète : 1re page déjà lue, pages suivantes en parallèle. */
function wp_rest_pages(array $first, string $path, array $query): array {
    if ($first['code'] !== 200 || !is_array($first['json'])) return ['_error' => $first['error'] ?? ('Erreur ' . $first['code'])];
    $out = $first['json']; $n = min(10, (int)($first['pages'] ?? 1));
    if ($n > 1) { $more = []; for ($p = 2; $p <= $n; $p++) $more[$p] = [$path, $query + ['per_page' => 100, 'page' => $p]];
        foreach (wp_req_multi($more) as $r) if ($r['code'] === 200 && is_array($r['json'])) $out = array_merge($out, $r['json']); }
    return $out;
}

/* Cache des lectures. Le site WordPress répond lentement (~1 s par appel) : le tableau de bord affiche toujours la dernière
   version connue, immédiatement, et l'actualise en arrière-plan quand elle a plus de 10 minutes (requête lancée par la page). */
function wp_cache_clear(): void { q("DELETE FROM kv WHERE k LIKE 'wpc:%'"); }
/** Marque l'instantané comme périmé sans l'effacer (affiché tel quel, actualisé en arrière-plan). */
function wp_cache_stale(): void { q("UPDATE kv SET updated_at=0 WHERE k='wpc:snapshot'"); }
/** Modifie l'instantané en place (ex. statut d'une annonce changé depuis le tableau de bord). */
function wp_snap_patch(callable $fn): void {
    $raw = q("SELECT v FROM kv WHERE k='wpc:snapshot'")->fetchColumn(); if (!$raw) return;
    $s = json_decode((string)$raw, true); if (!is_array($s)) return;
    $s = $fn($s); q("UPDATE kv SET v=?, updated_at=0 WHERE k='wpc:snapshot'", [json_encode($s, JSON_UNESCAPED_UNICODE)]);
}
function wp_cache_age(string $key = 'snapshot'): ?int { $t = q('SELECT updated_at FROM kv WHERE k=?', ['wpc:' . $key])->fetchColumn(); return $t === false ? null : time() - (int)$t; }
function wp_cached(string $key, callable $fn, int $ttl = WP_CACHE_TTL, bool $fresh = false, bool $swr = true): array {
    $row = q('SELECT v, updated_at FROM kv WHERE k=?', ['wpc:' . $key])->fetch();
    if (!$fresh && $row && ($swr || time() - (int)$row['updated_at'] < $ttl)) { // version connue : affichée tout de suite
        $v = json_decode($row['v'], true) ?: [];
        if (time() - (int)$row['updated_at'] >= $ttl) $GLOBALS['wp_stale'][$key] = true;
        return $v;
    }
    $lock = 'wpc-lock:' . $key; $lk = q('SELECT updated_at FROM kv WHERE k=?', [$lock])->fetchColumn();
    if ($row && $lk !== false && time() - (int)$lk < 90) return json_decode($row['v'], true) ?: []; // déjà en cours d'actualisation
    q("INSERT INTO kv(k,v,updated_at) VALUES(?,'1',?) ON CONFLICT(k) DO UPDATE SET updated_at=excluded.updated_at", [$lock, time()]);
    @set_time_limit(120);
    try { $v = $fn(); } finally { q('DELETE FROM kv WHERE k=?', [$lock]); }
    if (empty($v['_error'])) q("INSERT INTO kv(k,v,updated_at) VALUES(?,?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", ['wpc:' . $key, json_encode($v, JSON_UNESCAPED_UNICODE), time()]);
    elseif ($row) { $old = json_decode($row['v'], true) ?: []; $old['_stale'] = $v['_error']; return $old; }
    return $v;
}
function wp_is_stale(): bool { return !empty($GLOBALS['wp_stale']); }

/** Récupère toutes les pages d'une collection (100 par appel, 10 appels au plus). */
function wp_all(string $path, array $query = []): array {
    $out = []; $page = 1;
    do {
        $r = wp_req('GET', $path, $query + ['per_page' => 100, 'page' => $page]);
        if ($r['code'] !== 200 || !is_array($r['json'])) { if ($page === 1) return ['_error' => $r['error'] ?? ('Erreur ' . $r['code'])]; break; }
        $out = array_merge($out, $r['json']); $page++;
    } while (count($r['json']) === 100 && $page <= 10);
    return $out;
}

/** Instantané du site : annonces, taxonomies, pages, médias, extensions, formulaires, santé (lectures en parallèle). */
function wp_snapshot(bool $fresh = false): array {
    $snap = wp_cached('snapshot', function () {
        $s = ['at' => time(), 'url' => wp_url()];
        $Q = ['ann' => ['wp/v2/annonces', ['status' => 'any', 'context' => 'edit', 'per_page' => 100, '_fields' => 'id,status,title,date,modified,link,statuts,types,emplacement,featured_media']],
              'statuts' => ['wp/v2/statuts', ['hide_empty' => 'false', 'per_page' => 100, '_fields' => 'id,name,count']],
              'types' => ['wp/v2/types', ['hide_empty' => 'false', 'per_page' => 100, '_fields' => 'id,name,count']],
              'emplacement' => ['wp/v2/emplacement', ['hide_empty' => 'false', 'per_page' => 100, '_fields' => 'id,name,count']],
              'pages' => ['wp/v2/pages', ['status' => 'any', 'context' => 'edit', 'per_page' => 100, '_fields' => 'id,status,title,modified,link,yoast_head_json']],
              'media' => ['wp/v2/media', ['per_page' => 1, '_fields' => 'id']],
              'plugins' => ['wp/v2/plugins', ['context' => 'edit', '_fields' => 'plugin,name,status,version']],
              'users' => ['wp/v2/users', ['per_page' => 1, 'context' => 'edit', '_fields' => 'id']],
              'forms' => ['contact-form-7/v1/contact-forms', []],
              'settings' => ['wp/v2/settings', ['_fields' => 'title,description,url,timezone,blog_public']],
              // « types » est masqué par la route WordPress des types de contenus : termes lus via les annonces, dans le même lot
              'emb' => ['wp/v2/annonces', ['status' => 'any', 'context' => 'edit', 'per_page' => 100, '_embed' => 'wp:term', '_fields' => 'id,_links,_embedded']]];
        $R = wp_req_multi($Q);
        $ann = wp_rest_pages($R['ann'], $Q['ann'][0], array_diff_key($Q['ann'][1], ['per_page' => 1]));
        if (isset($ann['_error'])) return ['_error' => $ann['_error']];
        $s['annonces'] = array_map(fn($a) => ['id' => (int)$a['id'], 'status' => $a['status'], 'title' => html_entity_decode((string)($a['title']['raw'] ?? $a['title']['rendered'] ?? ''), ENT_QUOTES),
            'date' => substr((string)$a['date'], 0, 10), 'modified' => substr((string)$a['modified'], 0, 10), 'link' => (string)$a['link'],
            'statuts' => $a['statuts'] ?? [], 'types' => $a['types'] ?? [], 'emplacement' => $a['emplacement'] ?? [], 'photo' => (int)($a['featured_media'] ?? 0)], $ann);
        // Visuels : adresse de la photo principale de chaque annonce (taille moyenne), lots de 100 en parallèle
        $mids = array_values(array_filter(array_unique(array_column($s['annonces'], 'photo')))); $urls = []; $MQ = [];
        foreach (array_chunk($mids, 100) as $n => $ch) $MQ[$n] = ['wp/v2/media', ['include' => implode(',', $ch), 'per_page' => 100, '_fields' => 'id,source_url,media_details']];
        foreach ($MQ ? wp_req_multi($MQ) : [] as $m) if ($m['code'] === 200 && is_array($m['json'])) foreach ($m['json'] as $x) { $sz = $x['media_details']['sizes'] ?? []; $urls[(int)$x['id']] = (string)($sz['medium_large']['source_url'] ?? $sz['medium']['source_url'] ?? $x['source_url'] ?? ''); }
        foreach ($s['annonces'] as &$a) $a['photo_url'] = $urls[$a['photo']] ?? ''; unset($a);
        $missing = [];
        foreach (['statuts', 'types', 'emplacement'] as $tx) {
            $t = $R[$tx]; $t = ($t['code'] === 200 && is_array($t['json'])) ? $t['json'] : ['_error' => 1];
            // « types » est masqué par la route WordPress /wp/v2/types (types de contenus) : on vérifie qu'il s'agit bien de termes
            $ok = !isset($t['_error']) && array_is_list($t) && (!$t || (isset($t[0]['id'], $t[0]['name']) && is_int($t[0]['id'])));
            $s['tax'][$tx] = $ok ? array_column(array_map(fn($x) => ['id' => (int)$x['id'], 'name' => html_entity_decode((string)$x['name'], ENT_QUOTES)], $t), 'name', 'id') : [];
            if (!$ok) $missing[] = $tx;
        }
        if ($missing) { // repli : termes utilisés par les annonces existantes
            $emb = wp_rest_pages($R['emb'], $Q['emb'][0], array_diff_key($Q['emb'][1], ['per_page' => 1]));
            if (!isset($emb['_error'])) foreach ($emb as $a) foreach ((array)($a['_embedded']['wp:term'] ?? []) as $grp) foreach ((array)$grp as $term)
                if (in_array($term['taxonomy'] ?? '', $missing, true)) $s['tax'][$term['taxonomy']][(int)$term['id']] = html_entity_decode((string)$term['name'], ENT_QUOTES);
        }
        $pages = wp_rest_pages($R['pages'], $Q['pages'][0], array_diff_key($Q['pages'][1], ['per_page' => 1]));
        $s['pages'] = isset($pages['_error']) ? [] : array_map(fn($p) => ['id' => (int)$p['id'], 'status' => $p['status'], 'title' => html_entity_decode((string)($p['title']['raw'] ?? ''), ENT_QUOTES),
            'modified' => substr((string)$p['modified'], 0, 10), 'link' => (string)$p['link'],
            'seo_title' => (string)($p['yoast_head_json']['title'] ?? ''), 'seo_desc' => (string)($p['yoast_head_json']['description'] ?? $p['yoast_head_json']['og_description'] ?? ''),
            'noindex' => str_contains((string)($p['yoast_head_json']['robots']['index'] ?? ''), 'noindex')], $pages);
        $s['media'] = $R['media']['total'];
        $pl = $R['plugins']; $s['plugins'] = is_array($pl['json']) && $pl['code'] === 200 ? array_map(fn($p) => ['name' => html_entity_decode(strip_tags((string)$p['name']), ENT_QUOTES), 'status' => $p['status'], 'version' => $p['version']], $pl['json']) : [];
        $s['users'] = $R['users']['total'];
        $cf = $R['forms']; $s['forms'] = is_array($cf['json']) && $cf['code'] === 200 ? array_map(fn($f) => (string)($f['title'] ?? ''), $cf['json']) : [];
        $s['settings'] = $R['settings']['code'] === 200 ? $R['settings']['json'] : [];
        return $s;
    }, WP_CACHE_TTL, $fresh);
    if (empty($snap['_error'])) $snap['health'] = wp_health(false, false); // jamais bloquant : actualisée en arrière-plan
    return $snap;
}

/** Santé du site (tests WordPress lents, dont les requêtes internes) : actualisée toutes les 6 heures, en arrière-plan.
    $fetch=false : lecture du cache seulement (affichage) ; une version absente ou ancienne déclenche l'actualisation par la page. */
function wp_health(bool $fresh = false, bool $fetch = true): array {
    if (!$fetch) {
        $row = q("SELECT v, updated_at FROM kv WHERE k='wpc:health'")->fetch();
        if (!$row || time() - (int)$row['updated_at'] >= 6 * 3600) $GLOBALS['wp_stale']['health'] = true;
        return $row ? array_values(json_decode($row['v'], true) ?: []) : [];
    }
    return array_values(wp_cached('health', function () {
        $T = ['https-status' => 'HTTPS', 'background-updates' => 'Mises à jour automatiques', 'loopback-requests' => 'Requêtes internes', 'dotorg-communication' => 'Liaison WordPress.org', 'page-cache' => 'Cache des pages'];
        $Q = []; foreach ($T as $t => $_) $Q[$t] = ['wp-site-health/v1/tests/' . $t, []];
        $out = [];
        foreach (wp_req_multi($Q) as $t => $h) if ($h['code'] === 200 && is_array($h['json'])) $out[] = ['label' => $T[$t], 'status' => (string)($h['json']['status'] ?? ''), 'text' => html_entity_decode(strip_tags((string)($h['json']['label'] ?? '')), ENT_QUOTES)];
        return $out ?: ['_error' => 'santé indisponible'];
    }, 6 * 3600, $fresh, false));
}

/** Statut commercial d'une annonce (premier terme « statuts »). */
function wp_ann_statut(array $a, array $tax): string { foreach ($a['statuts'] as $id) if (isset($tax['statuts'][$id])) return $tax['statuts'][$id]; return 'Sans statut'; }
function wp_edit_link(int $id, bool $elementor = false): string { return wp_url() . '/wp-admin/post.php?post=' . $id . '&action=' . ($elementor ? 'elementor' : 'edit'); }

/* ---------- Annonceurs : personnes autorisées à publier des annonces sans être en Direction ---------- */
function wp_posters(): array {
    static $c = null; if ($c !== null) return $c;
    $raw = q("SELECT v FROM kv WHERE k='wp_posters'")->fetchColumn();
    // Personnes invitées avec l'accès Site Web : accès dès l'activation de leur compte
    $em = json_decode((string)q("SELECT v FROM kv WHERE k='wp_poster_emails'")->fetchColumn(), true) ?: [];
    $byEmail = $em ? array_map('intval', q('SELECT id FROM users WHERE lower(email) IN (' . implode(',', array_fill(0, count($em), '?')) . ') AND disabled_at IS NULL', array_map('strtolower', $em))->fetchAll(PDO::FETCH_COLUMN)) : [];
    if ($raw !== false) return $c = array_values(array_unique(array_merge(array_map('intval', json_decode((string)$raw, true) ?: []), $byEmail)));
    // Tant que l'administrateur n'a pas choisi : Gaëtan GENDRY (transaction) est annonceur dès que son compte existe
    return $c = array_values(array_unique(array_merge(array_map('intval', q("SELECT id FROM users WHERE (lower(name) LIKE '%gendry%' OR lower(email) LIKE '%gendry%') AND disabled_at IS NULL")->fetchAll(PDO::FETCH_COLUMN)), $byEmail)));
}
function wp_posters_save(array $ids): void { q("INSERT INTO kv(k,v,updated_at) VALUES('wp_posters',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode(array_values(array_unique(array_map('intval', $ids)))), time()]); }
function wp_poster(array $u): bool { return wp_viewer($u) || in_array((int)$u['id'], wp_posters(), true); }

/** Envoi d'une image dans la médiathèque WordPress ; renvoie l'id du média ou une erreur. */
function wp_upload_media(string $bin, string $mime, string $name, string $alt = ''): array {
    $c = wp_conn(); if (!$c) return ['error' => 'Site non connecté'];
    $ch = curl_init(rtrim($c['url'], '/') . '/wp-json/wp/v2/media');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $bin, CURLOPT_TIMEOUT => 60, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERPWD => $c['user'] . ':' . str_replace(' ', '', $c['pass']),
        CURLOPT_HTTPHEADER => ['Content-Type: ' . $mime, 'Content-Disposition: attachment; filename="' . preg_replace('/[^a-z0-9._-]/i', '-', $name) . '"', 'Accept: application/json']]);
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $j = is_string($res) ? json_decode($res, true) : null;
    if ($code !== 201 || empty($j['id'])) return ['error' => (string)($j['message'] ?? ('envoi de la photo refusé (' . $code . ')'))];
    if ($alt !== '') wp_req('POST', 'wp/v2/media/' . (int)$j['id'], [], ['alt_text' => $alt]);
    return ['id' => (int)$j['id']];
}

/* ---------- Mentions légales des annonces (professionnel de l'immobilier) ----------
   Vente : prix honoraires inclus et hors honoraires, honoraires (arrêté du 10/01/2017) ; superficie loi Carrez (lot de copropriété) ;
   copropriété : nombre de lots, charges annuelles moyennes, procédure en cours (art. L721-1 CCH, loi ALUR).
   Location : loyer mensuel charges comprises, charges et leur mode de règlement, dépôt de garantie, honoraires locataire dont état des lieux,
   meublé ou non, surface habitable ; Paris : encadrement des loyers (loyer de référence, majoré, loyer de base, complément de loyer).
   Tous : DPE et GES (classes), dépenses annuelles d'énergie estimées et année de référence des prix (depuis le 01/01/2022),
   mention « Logement à consommation énergétique excessive » en F ou G, information Géorisques (depuis le 01/01/2023). */
function wp_ann_check(array $d): array {
    $err = []; $loc = ($d['tx'] ?? '') === 'location';
    $need = function (string $k, string $lab) use (&$err, $d) { if (!isset($d[$k]) || trim((string)$d[$k]) === '') $err[] = $lab; };
    $need('title', 'titre'); $need('type', 'type de bien'); $need('emplacement', 'secteur'); $need('address', 'adresse'); $need('surface', 'surface habitable');
    if ($loc) { $need('loyer', 'loyer mensuel charges comprises'); $need('charges', 'montant des charges'); $need('depot', 'dépôt de garantie'); $need('hono_loc', 'honoraires locataire'); $need('hono_edl', 'dont état des lieux');
        if (!empty($d['encadre'])) { $need('loyer_ref', 'loyer de référence'); $need('loyer_maj', 'loyer de référence majoré'); } }
    else { $need('prix', 'prix honoraires inclus'); $need('hono_charge', 'honoraires à la charge de');
        if (($d['hono_charge'] ?? '') === 'acquereur') { $need('hono_pct', 'pourcentage des honoraires'); } }
    if (!empty($d['copro'])) { $need('lots', 'nombre de lots de la copropriété'); $need('charges_copro', 'charges annuelles de copropriété'); if (!$loc) $need('carrez', 'superficie loi Carrez'); }
    if (($d['dpe_cas'] ?? 'dpe') === 'dpe') { $need('dpe', 'classe énergie (DPE)'); $need('ges', 'classe climat (GES)'); if (!empty($d['logement'])) { $need('ener_min', 'dépenses d\'énergie minimum'); $need('ener_max', 'dépenses d\'énergie maximum'); $need('ener_annee', 'année de référence des prix de l\'énergie'); } }
    if (empty($d['photos'])) $err[] = 'au moins une photo';
    return $err;
}
function wp_eur($v, int $dec = 0): string { return number_format((float)str_replace([' ', ','], ['', '.'], (string)$v), $dec, ',', ' ') . ' €'; }
function wp_ann_legal_html(array $d): string {
    $loc = ($d['tx'] ?? '') === 'location'; $li = []; $n = fn($k) => (float)str_replace([' ', ','], ['', '.'], (string)($d[$k] ?? 0));
    if ($loc) {
        $li[] = 'Loyer : <strong>' . wp_eur($d['loyer']) . ' par mois charges comprises</strong>, dont ' . wp_eur($d['charges']) . ' de charges (' . (($d['charges_mode'] ?? 'provision') === 'forfait' ? 'forfait' : 'provision avec régularisation annuelle') . ')' . ' · loyer hors charges : ' . wp_eur($n('loyer') - $n('charges'));
        $li[] = 'Dépôt de garantie : ' . wp_eur($d['depot']);
        $li[] = 'Honoraires TTC à la charge du locataire : ' . wp_eur($d['hono_loc']) . ', dont ' . wp_eur($d['hono_edl']) . ' pour l\'état des lieux';
        $li[] = (!empty($d['meuble']) ? 'Location meublée' : 'Location vide') . ' · surface habitable : ' . e((string)$d['surface']) . ' m²';
        if (!empty($d['encadre'])) $li[] = 'Zone soumise à l\'encadrement des loyers · loyer de référence : ' . wp_eur($d['loyer_ref'], 2) . '/m² · loyer de référence majoré : ' . wp_eur($d['loyer_maj'], 2) . '/m²' . ($n('complement') > 0 ? ' · complément de loyer : ' . wp_eur($d['complement']) . (!empty($d['complement_why']) ? ' (' . e((string)$d['complement_why']) . ')' : '') : '');
    } else {
        $prix = $n('prix'); $acq = ($d['hono_charge'] ?? '') === 'acquereur'; $pct = $n('hono_pct');
        $li[] = 'Prix : <strong>' . wp_eur($prix) . ($acq ? ' honoraires inclus' : '') . '</strong>';
        if ($acq) { $hors = $pct > 0 ? round($prix / (1 + $pct / 100)) : $prix; $li[] = 'Honoraires de ' . rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',') . ' % TTC à la charge de l\'acquéreur · prix hors honoraires : ' . wp_eur($hors); }
        else $li[] = 'Honoraires à la charge du vendeur';
        $li[] = 'Surface habitable : ' . e((string)$d['surface']) . ' m²' . (!empty($d['carrez']) ? ' · superficie loi Carrez : ' . e((string)$d['carrez']) . ' m²' : '');
    }
    if (!empty($d['copro'])) $li[] = 'Bien soumis au statut de la copropriété : ' . (int)$d['lots'] . ' lots · charges courantes annuelles moyennes (quote-part du lot) : ' . wp_eur($d['charges_copro']) . ' · ' . (!empty($d['procedure']) ? 'procédure en cours : ' . e((string)($d['procedure_txt'] ?: 'oui')) : 'aucune procédure en cours contre le syndicat des copropriétaires');
    $cas = $d['dpe_cas'] ?? 'dpe';
    if ($cas === 'dpe') {
        $li[] = 'CLASSE ÉNERGIE : <strong>' . e((string)$d['dpe']) . '</strong>' . (!empty($d['conso']) ? ' (' . e((string)$d['conso']) . ' kWh/m²/an)' : '') . ' · CLASSE CLIMAT : <strong>' . e((string)$d['ges']) . '</strong>' . (!empty($d['emission']) ? ' (' . e((string)$d['emission']) . ' kg CO₂/m²/an)' : '');
        if (!empty($d['logement'])) $li[] = 'Montant estimé des dépenses annuelles d\'énergie pour un usage standard : entre ' . wp_eur($d['ener_min']) . ' et ' . wp_eur($d['ener_max']) . ' par an. Prix moyens des énergies indexés sur l\'année ' . e((string)$d['ener_annee']) . ' (abonnements compris)';
        if (!empty($d['logement']) && in_array($d['dpe'], ['F', 'G'], true)) $li[] = '<strong>Logement à consommation énergétique excessive : classe ' . e((string)$d['dpe']) . '</strong>';
    } elseif ($cas === 'vierge') $li[] = 'DPE vierge (consommations non exploitables)';
    elseif ($cas === 'encours') $li[] = 'DPE en cours de réalisation';
    else $li[] = 'Bien non soumis au diagnostic de performance énergétique';
    $li[] = 'Les informations sur les risques auxquels ce bien est exposé sont disponibles sur le site Géorisques : <a href="https://www.georisques.gouv.fr" rel="noopener" target="_blank">www.georisques.gouv.fr</a>';
    $li[] = 'GID SAS, administrateur de biens · barème des honoraires disponible à l\'agence et sur le site';
    return '<h3>Informations réglementaires</h3><ul class="gid-mentions"><li>' . implode('</li><li>', $li) . '</li></ul>';
}


/* ---------- Fiches d'annonces saisies depuis Dashboard (pour les rouvrir et les modifier) ---------- */
function wp_ann_src(int $id): ?array { $r = q('SELECT data FROM wp_annonces WHERE wp_id=?', [$id])->fetchColumn(); return $r ? (json_decode((string)$r, true) ?: null) : null; }
function wp_ann_src_save(int $id, array $d, int $uid): void { unset($d['photos']); q('INSERT INTO wp_annonces(wp_id,data,updated_at,updated_by) VALUES(?,?,?,?) ON CONFLICT(wp_id) DO UPDATE SET data=excluded.data, updated_at=excluded.updated_at, updated_by=excluded.updated_by', [$id, json_encode($d, JSON_UNESCAPED_UNICODE), time(), $uid]); }

/** Fiche d'une annonce pour le formulaire : la saisie Dashboard si elle existe, sinon ce que WordPress fournit (titre, texte, taxonomies, photo). */
function wp_ann_get(int $id): array {
    $r = wp_req('GET', 'wp/v2/annonces/' . $id, ['context' => 'edit', '_fields' => 'id,status,title,content,statuts,types,emplacement,featured_media,link,meta']);
    if ($r['code'] !== 200) return ['error' => $r['error'] ?? ('Annonce introuvable (' . $r['code'] . ')')];
    $a = $r['json']; $src = wp_ann_src($id) ?? [];
    $raw = (string)($a['content']['raw'] ?? ''); $desc = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>#i', "\n", preg_replace('#</p>\s*<p>#i', "\n\n", explode('<h3>Informations réglementaires</h3>', $raw)[0]))), ENT_QUOTES));
    $d = $src + ['title' => html_entity_decode((string)($a['title']['raw'] ?? ''), ENT_QUOTES), 'description' => $desc, 'type' => (string)($a['types'][0] ?? ''), 'emplacement' => (string)($a['emplacement'][0] ?? '')];
    $d['title'] = html_entity_decode((string)($a['title']['raw'] ?? $d['title']), ENT_QUOTES); $d['description'] = $src['description'] ?? $desc;
    $meta = is_array($a['meta'] ?? null) ? $a['meta'] : [];
    if (!isset($src['address']) && !empty($meta['address'])) $d['address'] = $meta['address'];
    $media = array_values(array_filter(array_unique(array_merge([(int)($a['featured_media'] ?? 0)], array_map('intval', $src['media'] ?? [])))));
    $ph = [];
    if ($media) { $m = wp_req('GET', 'wp/v2/media', ['include' => implode(',', $media), 'per_page' => 50, '_fields' => 'id,source_url,media_details']);
        $by = []; if ($m['code'] === 200) foreach ((array)$m['json'] as $x) { $sz = $x['media_details']['sizes'] ?? []; $by[(int)$x['id']] = (string)($sz['medium_large']['source_url'] ?? $sz['medium']['source_url'] ?? $x['source_url'] ?? ''); }
        foreach ($media as $mid) if (!empty($by[$mid])) $ph[] = ['id' => $mid, 'url' => $by[$mid]]; }
    $d['photos'] = $ph; $d['id'] = (int)$a['id']; $d['status'] = (string)$a['status']; $d['link'] = (string)$a['link']; $d['from_chemini'] = (bool)$src;
    return $d;
}

/** Création ou mise à jour d'une annonce. Photos : [{id}] (déjà sur le site) ou « data:image/… » (nouvelles), dans l'ordre (la première est la principale). */
function wp_ann_save(array $d, int $uid): array {
    foreach ($d as $k => $v) if (is_string($v)) $d[$k] = mb_substr(trim($v), 0, $k === 'description' ? 8000 : 300);
    $d['tx'] = ($d['tx'] ?? '') === 'location' ? 'location' : 'vente';
    $id = (int)($d['id'] ?? 0);
    $photos = array_slice(array_values((array)($d['photos'] ?? [])), 0, 15); $d['photos'] = $photos;
    if ($miss = wp_ann_check($d)) return ['error' => 'Champs obligatoires manquants : ' . implode(', ', $miss) . '.', 'code' => 400];
    $snap = wp_snapshot(); $tax = $snap['tax'] ?? [];
    $term = function (string $tx, $want) use ($tax) { foreach ($tax[$tx] ?? [] as $tid => $nm) if ((string)$tid === (string)$want || mb_strtolower($nm) === mb_strtolower((string)$want)) return (int)$tid; return 0; };
    $title = mb_substr((string)$d['title'], 0, 160); $ids = [];
    foreach ($photos as $i => $p) {
        if (is_array($p) && !empty($p['id'])) { $ids[] = (int)$p['id']; continue; }
        if (!is_string($p) || !preg_match('#^data:(image/(?:jpeg|png|webp));base64,(.+)$#', $p, $m)) return ['error' => 'Photo ' . ($i + 1) . ' : format non reconnu (JPEG, PNG ou WebP).', 'code' => 400];
        $bin = base64_decode($m[2], true); if ($bin === false || strlen($bin) > 8 * 1024 * 1024 || !@getimagesizefromstring($bin)) return ['error' => 'Photo ' . ($i + 1) . ' illisible ou trop lourde.', 'code' => 400];
        $up = wp_upload_media($bin, $m[1], guide_slug($title) . '-' . ($i + 1) . ($m[1] === 'image/png' ? '.png' : ($m[1] === 'image/webp' ? '.webp' : '.jpg')), $title);
        if (isset($up['error'])) return ['error' => 'Photo ' . ($i + 1) . ' : ' . $up['error'], 'code' => 502];
        $ids[] = $up['id'];
    }
    $desc = ''; foreach (preg_split('/\R{2,}/', (string)($d['description'] ?? '')) as $para) if (trim($para) !== '') $desc .= '<p>' . nl2br(e(trim($para))) . '</p>';
    $feat = []; foreach (['balcon', 'parking', 'jardin', 'terrasse', 'cave', 'ascenseur', 'garage', 'box', 'piscine', 'gardien', 'wc'] as $f) $feat[$f] = in_array($f, (array)($d['features'] ?? []), true) ? 'true' : 'false';
    $num = fn($k) => (string)(float)str_replace([' ', ','], ['', '.'], (string)($d[$k] ?? ''));
    $meta = ['address' => mb_substr((string)($d['address'] ?? ''), 0, 200), 'price' => $d['tx'] === 'location' ? $num('loyer') : $num('prix'), 'property-size' => $num('surface'),
             'energy_balance' => ($d['dpe_cas'] ?? 'dpe') === 'dpe' ? (string)($d['dpe'] ?? '') : '', 'consommation' => (string)($d['conso'] ?? ''), 'gaz' => (string)($d['emission'] ?? ''),
             'pieces' => (string)($d['pieces'] ?? ''), 'bedrooms' => (string)($d['chambres'] ?? ''), 'bathsrooms' => (string)($d['sdb'] ?? ''), 'floor' => (string)($d['etage'] ?? ''),
             'features' => $feat, 'gallery' => implode(',', array_slice($ids, 1))];
    $post = ['title' => $title, 'content' => $desc . wp_ann_legal_html($d), 'types' => array_values(array_filter([$term('types', $d['type'] ?? '')])),
             'emplacement' => array_values(array_filter([$term('emplacement', $d['emplacement'] ?? '')])), 'featured_media' => $ids[0] ?? 0, 'meta' => $meta];
    if (isset($d['publier'])) $post['status'] = $d['publier'] === '1' ? 'publish' : 'draft';
    if (!$id) { $stId = $term('statuts', $d['tx'] === 'location' ? 'A louer' : 'En vente') ?: $term('statuts', 'À louer'); $post['statuts'] = $stId ? [$stId] : []; $post['status'] ??= 'draft'; }
    elseif (!empty($d['statut'])) $post['statuts'] = [(int)$d['statut']];
    $res = wp_req('POST', 'wp/v2/annonces' . ($id ? '/' . $id : ''), ['context' => 'edit', '_fields' => 'id,status,link,meta'], $post);
    if (!in_array($res['code'], [200, 201], true)) return ['error' => 'WordPress : ' . ($res['error'] ?? 'erreur ' . $res['code']), 'code' => 502];
    $nid = (int)$res['json']['id']; $d['media'] = $ids; wp_ann_src_save($nid, $d, $uid); wp_cache_stale();
    return ['ok' => true, 'id' => $nid, 'status' => $res['json']['status'], 'link' => $res['json']['link'], 'edit' => wp_edit_link($nid), 'meta_ok' => !empty($res['json']['meta']['price'] ?? null), 'photos' => count($ids)];
}
