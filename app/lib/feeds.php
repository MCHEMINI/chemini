<?php
declare(strict_types=1);

function http_get(string $url, array $headers = [], int $timeout = 8): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_USERAGENT => 'GID-Chemini/1.0 (+' . BASE_URL . ')', CURLOPT_HTTPHEADER => $headers,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
}

/** Récupère un flux RSS/Atom (cache 1 h) et renvoie [titre, lien, date, source, résumé]. */
function feed_items(string $url, int $limit = 6): array {
    $row = q('SELECT * FROM feed_cache WHERE url=?', [$url])->fetch();
    $body = $row['body'] ?? null;
    if (!$row || time() - (int)$row['fetched_at'] > FEED_TTL) {
        $fresh = http_get($url);
        if ($fresh) {
            $body = $fresh;
            q('INSERT INTO feed_cache(url,fetched_at,body) VALUES(?,?,?) ON CONFLICT(url) DO UPDATE SET fetched_at=excluded.fetched_at, body=excluded.body', [$url, time(), $fresh]);
        }
    }
    if (!$body) return [];
    $prev = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if (!$xml) return [];
    $out = [];
    $nodes = isset($xml->channel->item) ? $xml->channel->item : ($xml->entry ?? []);
    foreach ($nodes as $it) {
        $title = trim((string)$it->title);
        $link = (string)($it->link['href'] ?? '') ?: trim((string)$it->link);
        $date = strtotime((string)($it->pubDate ?: $it->updated ?: $it->published)) ?: null;
        $src = trim((string)($it->source ?? ''));
        // Google Actualités : « Titre - Source »
        if (!$src && preg_match('/^(.*) - ([^-]+)$/u', $title, $m)) { $title = $m[1]; $src = $m[2]; }
        $sum = trim(html_entity_decode(strip_tags((string)($it->description ?? $it->summary ?? '')), ENT_QUOTES, 'UTF-8'));
        if (str_contains($url, 'news.google.')) $sum = '';
        if (!preg_match('#^https?://#', $link)) continue;
        $out[] = ['title' => $title, 'link' => $link, 'date' => $date, 'source' => $src ?: (parse_url($url, PHP_URL_HOST) ?? ''), 'summary' => mb_strimwidth($sum, 0, 180, '…')];
        if (count($out) >= $limit) break;
    }
    return $out;
}

function feeds_merged(array $urls, int $limit): array {
    $all = [];
    foreach ($urls as $u) foreach (feed_items($u, $limit) as $i) $all[$i['link']] = $i;
    usort($all, fn($a, $b) => ($b['date'] ?? 0) <=> ($a['date'] ?? 0));
    return array_slice(array_values($all), 0, $limit);
}
