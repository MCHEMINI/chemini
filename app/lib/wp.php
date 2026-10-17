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
    if (!preg_match('#^https://[a-z0-9.-]+(?::\d+)?(/[^\s?#]*)?$#i', $u)) return null; // HTTPS uniquement
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
        CURLOPT_USERAGENT => 'GID-Chemini/1.0 (+' . BASE_URL . ')',
        CURLOPT_HEADERFUNCTION => function ($ch, $h) use (&$hdr) { $p = explode(':', $h, 2); if (count($p) === 2) $hdr[strtolower(trim($p[0]))] = trim($p[1]); return strlen($h); },
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    $res = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    $json = is_string($res) ? json_decode($res, true) : null;
    return ['code' => $res === false ? 0 : $code, 'json' => $json, 'total' => isset($hdr['x-wp-total']) ? (int)$hdr['x-wp-total'] : null,
            'error' => $res === false ? ($err ?: 'Site injoignable') : ($code >= 400 ? (string)($json['message'] ?? ('Erreur ' . $code)) : null)];
}

/* Cache des lectures (le tableau de bord ne sollicite le site qu'une fois toutes les 10 minutes) */
function wp_cache_clear(): void { q("DELETE FROM kv WHERE k LIKE 'wpc:%'"); }
function wp_cached(string $key, callable $fn, int $ttl = WP_CACHE_TTL, bool $fresh = false): array {
    $row = q('SELECT v, updated_at FROM kv WHERE k=?', ['wpc:' . $key])->fetch();
    if (!$fresh && $row && time() - (int)$row['updated_at'] < $ttl) return json_decode($row['v'], true) ?: [];
    $v = $fn();
    if (empty($v['_error'])) q("INSERT INTO kv(k,v,updated_at) VALUES(?,?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", ['wpc:' . $key, json_encode($v, JSON_UNESCAPED_UNICODE), time()]);
    elseif ($row) { $old = json_decode($row['v'], true) ?: []; $old['_stale'] = $v['_error']; return $old; }
    return $v;
}

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

/** Instantané du site : annonces, taxonomies, pages, médias, extensions, formulaires, santé. */
function wp_snapshot(bool $fresh = false): array {
    return wp_cached('snapshot', function () {
        $s = ['at' => time(), 'url' => wp_url()];
        $ann = wp_all('wp/v2/annonces', ['status' => 'any', 'context' => 'edit', '_fields' => 'id,status,title,date,modified,link,statuts,types,emplacement,featured_media']);
        if (isset($ann['_error'])) return ['_error' => $ann['_error']];
        $s['annonces'] = array_map(fn($a) => ['id' => (int)$a['id'], 'status' => $a['status'], 'title' => html_entity_decode((string)($a['title']['raw'] ?? $a['title']['rendered'] ?? ''), ENT_QUOTES),
            'date' => substr((string)$a['date'], 0, 10), 'modified' => substr((string)$a['modified'], 0, 10), 'link' => (string)$a['link'],
            'statuts' => $a['statuts'] ?? [], 'types' => $a['types'] ?? [], 'emplacement' => $a['emplacement'] ?? [], 'photo' => (int)($a['featured_media'] ?? 0)], $ann);
        foreach (['statuts', 'types', 'emplacement'] as $tx) {
            $t = wp_all('wp/v2/' . $tx, ['hide_empty' => 'false', '_fields' => 'id,name,count']);
            $s['tax'][$tx] = isset($t['_error']) ? [] : array_column(array_map(fn($x) => ['id' => (int)$x['id'], 'name' => html_entity_decode((string)$x['name'], ENT_QUOTES)], $t), 'name', 'id');
        }
        $pages = wp_all('wp/v2/pages', ['status' => 'any', 'context' => 'edit', '_fields' => 'id,status,title,modified,link,yoast_head_json']);
        $s['pages'] = isset($pages['_error']) ? [] : array_map(fn($p) => ['id' => (int)$p['id'], 'status' => $p['status'], 'title' => html_entity_decode((string)($p['title']['raw'] ?? ''), ENT_QUOTES),
            'modified' => substr((string)$p['modified'], 0, 10), 'link' => (string)$p['link'],
            'seo_title' => (string)($p['yoast_head_json']['title'] ?? ''), 'seo_desc' => (string)($p['yoast_head_json']['description'] ?? $p['yoast_head_json']['og_description'] ?? ''),
            'noindex' => str_contains((string)($p['yoast_head_json']['robots']['index'] ?? ''), 'noindex')], $pages);
        $m = wp_req('GET', 'wp/v2/media', ['per_page' => 1, '_fields' => 'id']); $s['media'] = $m['total'];
        $pl = wp_req('GET', 'wp/v2/plugins', ['context' => 'edit', '_fields' => 'plugin,name,status,version']);
        $s['plugins'] = is_array($pl['json']) && $pl['code'] === 200 ? array_map(fn($p) => ['name' => html_entity_decode(strip_tags((string)$p['name']), ENT_QUOTES), 'status' => $p['status'], 'version' => $p['version']], $pl['json']) : [];
        $us = wp_req('GET', 'wp/v2/users', ['per_page' => 1, 'context' => 'edit', '_fields' => 'id']); $s['users'] = $us['total'];
        $cf = wp_req('GET', 'contact-form-7/v1/contact-forms'); $s['forms'] = is_array($cf['json']) && $cf['code'] === 200 ? array_map(fn($f) => (string)($f['title'] ?? ''), $cf['json']) : [];
        $st = wp_req('GET', 'wp/v2/settings', ['_fields' => 'title,description,url,timezone,blog_public']); $s['settings'] = $st['code'] === 200 ? $st['json'] : [];
        $s['health'] = [];
        foreach (['https-status' => 'HTTPS', 'background-updates' => 'Mises à jour automatiques', 'loopback-requests' => 'Requêtes internes', 'dotorg-communication' => 'Liaison WordPress.org', 'page-cache' => 'Cache des pages'] as $t => $lab) {
            $h = wp_req('GET', 'wp-site-health/v1/tests/' . $t);
            if ($h['code'] === 200 && is_array($h['json'])) $s['health'][] = ['label' => $lab, 'status' => (string)($h['json']['status'] ?? ''), 'text' => html_entity_decode(strip_tags((string)($h['json']['label'] ?? '')), ENT_QUOTES)];
        }
        return $s;
    }, WP_CACHE_TTL, $fresh);
}

/** Statut commercial d'une annonce (premier terme « statuts »). */
function wp_ann_statut(array $a, array $tax): string { foreach ($a['statuts'] as $id) if (isset($tax['statuts'][$id])) return $tax['statuts'][$id]; return 'Sans statut'; }
function wp_edit_link(int $id, bool $elementor = false): string { return wp_url() . '/wp-admin/post.php?post=' . $id . '&action=' . ($elementor ? 'elementor' : 'edit'); }
