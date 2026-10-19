<?php
declare(strict_types=1);

/* Mesure d'audience maison du site gidsas.fr : sans cookie, sans service tiers.
   Un visiteur = empreinte quotidienne (adresse IP + navigateur + jour, hachée avec une clé du jour, jamais stockée en clair) :
   on compte des visiteurs uniques par jour sans pouvoir suivre une personne d'un jour à l'autre (mesure d'audience exemptée de consentement). */

const STATS_KINDS = ['pv' => 'Page vue', 'tel' => 'Appel', 'mail' => 'E-mail', 'form' => 'Formulaire envoyé', 'out' => 'Lien sortant'];

function stats_hosts(): array {
    $h = ['gidsas.fr', 'www.gidsas.fr', 'preprod.gidsas.fr'];
    $c = wp_conn(); if ($c && ($x = parse_url($c['url'], PHP_URL_HOST))) $h[] = strtolower($x);
    return array_values(array_unique($h));
}
function stats_device(string $ua, int $w): string {
    if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) return 'Tablette';
    if (preg_match('/Mobi|iPhone|Android/i', $ua) || ($w > 0 && $w < 700)) return 'Mobile';
    return 'Ordinateur';
}
function stats_channel(string $ref, string $q, string $self): array {
    parse_str(ltrim($q, '?'), $p);
    if (!empty($p['utm_source']) || !empty($p['gclid']) || !empty($p['fbclid'])) return ['Campagne', mb_substr((string)($p['utm_source'] ?? (isset($p['gclid']) ? 'google ads' : 'facebook ads')), 0, 60)];
    $h = strtolower((string)parse_url($ref, PHP_URL_HOST)); $h = preg_replace('/^www\./', '', $h);
    if ($h === '' || $h === preg_replace('/^www\./', '', $self)) return ['Accès direct', ''];
    if (preg_match('/(^|\.)(google|bing|qwant|duckduckgo|ecosia|yahoo|yandex|lilo|brave|startpage)\./', $h)) return ['Moteurs de recherche', $h];
    if (preg_match('/(facebook|instagram|linkedin|twitter|x\.com|t\.co|tiktok|youtube|pinterest|whatsapp|lnkd)/', $h)) return ['Réseaux sociaux', $h];
    if (preg_match('/(seloger|leboncoin|bienici|logic-immo|pap\.fr|figaro|meilleursagents)/', $h)) return ['Portails immobiliers', $h];
    if (preg_match('/(outlook|mail\.|gmail|yahoo)/', $h)) return ['E-mails', $h];
    return ['Sites référents', $h];
}

/** Point de collecte public (/t) : appelé par le script installé sur le site. */
function stats_collect(): never {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? ''); $host = strtolower((string)parse_url($origin ?: (string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST));
    $okHost = in_array($host, stats_hosts(), true);
    if ($okHost && $origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); }
    header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { header('Access-Control-Allow-Methods: POST'); header('Access-Control-Allow-Headers: Content-Type'); http_response_code(204); exit; }
    $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
    $d = json_decode((string)file_get_contents('php://input', false, null, 0, 4096), true);
    if (!$okHost || !is_array($d) || $ua === '' || preg_match('/bot|crawl|spider|slurp|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|facebookexternalhit|semrush|ahrefs/i', $ua)) { http_response_code(204); exit; }
    $kind = isset(STATS_KINDS[$d['k'] ?? '']) ? $d['k'] : 'pv';
    $day = date('Y-m-d'); $ip = client_ip();
    $vid = substr(hash_hmac('sha256', $ip . '|' . $ua . '|' . $host, hash_hmac('sha256', 'stats|' . $day, APP_KEY)), 0, 16);
    $n = (int)q('SELECT COUNT(*) FROM site_hits WHERE day=? AND vid=?', [$day, $vid])->fetchColumn();
    if ($n > 400) { http_response_code(204); exit; } // sécurité contre les envois en boucle
    $path = mb_substr('/' . ltrim((string)parse_url((string)($d['p'] ?? '/'), PHP_URL_PATH), '/'), 0, 300);
    [$ch, $src] = stats_channel((string)($d['r'] ?? ''), (string)($d['q'] ?? ''), $host);
    $first = !q('SELECT 1 FROM site_hits WHERE day=? AND vid=? AND kind=\'pv\' LIMIT 1', [$day, $vid])->fetchColumn();
    q('INSERT INTO site_hits(ts,day,vid,kind,path,title,channel,source,device,label,entry) VALUES(?,?,?,?,?,?,?,?,?,?,?)',
      [time(), $day, $vid, $kind, $path, mb_substr(trim((string)($d['t'] ?? '')), 0, 160), $ch, $src, stats_device($ua, (int)($d['w'] ?? 0)), mb_substr((string)($d['l'] ?? ''), 0, 120), $first && $kind === 'pv' ? 1 : 0]);
    if (random_int(1, 500) === 1) q('DELETE FROM site_hits WHERE ts < ?', [time() - 400 * 86400]); // 13 mois de conservation
    http_response_code(204); exit;
}

/** Script de mesure (/t.js) à placer sur le site. */
function stats_script(): never {
    header('Content-Type: application/javascript; charset=utf-8'); header('Cache-Control: public, max-age=3600'); header('X-Robots-Tag: noindex');
    $e = json_encode(site_url() . '/t');
    $rc = json_encode(BASE_URL . '/rc.js');
    echo "/* Mesure d'audience GID (sans cookie) + page Recrutement */\n(function(){var E=$e;"
       . "function rc(){if(document.querySelector('form.wpcf7-form')&&!document.getElementById('gid-rc')){var x=document.createElement('script');x.id='gid-rc';x.src=$rc;x.async=true;document.head.appendChild(x)}}"
       . "if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',rc);else rc();"
       . "if(navigator.webdriver||/[?&]nostat/.test(location.search))return;"
       . "function on(){return !(document.body&&document.body.classList.contains('logged-in'))}"
       . "function s(k,l){if(!on())return;var d=JSON.stringify({k:k,p:location.pathname,t:document.title,r:k==='pv'?document.referrer:'',q:k==='pv'?location.search:'',w:screen.width||0,l:l||''});"
       . "try{if(navigator.sendBeacon&&navigator.sendBeacon(E,new Blob([d],{type:'text/plain'})))return}catch(e){}try{fetch(E,{method:'POST',body:d,keepalive:true,mode:'no-cors'})}catch(e){}}"
       . "if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){s('pv')});else s('pv');"
       . "document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a[href]'):null;if(!a)return;var h=a.getAttribute('href')||'';"
       . "if(/^tel:/i.test(h))s('tel',h.slice(4));else if(/^mailto:/i.test(h))s('mail',h.slice(7).split('?')[0]);else if(a.host&&a.host!==location.host)s('out',a.host)},true);"
       . "document.addEventListener('wpcf7mailsent',function(e){s('form',(e.detail&&e.detail.contactFormId)||'')});})();\n";
    exit;
}

/** Statistiques agrégées sur une période (jours). */
function stats_report(int $days): array {
    $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days')); $prevFrom = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
    $one = fn(string $sql, array $p = []) => q($sql, $p)->fetchColumn();
    $r = ['days' => $days, 'from' => $from];
    $r['total'] = (int)$one('SELECT COUNT(*) FROM site_hits');
    $r['visitors'] = (int)$one("SELECT COUNT(DISTINCT day||vid) FROM site_hits WHERE day>=? AND kind='pv'", [$from]);
    $r['views'] = (int)$one("SELECT COUNT(*) FROM site_hits WHERE day>=? AND kind='pv'", [$from]);
    $r['prev_visitors'] = (int)$one("SELECT COUNT(DISTINCT day||vid) FROM site_hits WHERE day>=? AND day<? AND kind='pv'", [$prevFrom, $from]);
    $r['contacts'] = (int)$one("SELECT COUNT(*) FROM site_hits WHERE day>=? AND kind IN ('tel','mail','form')", [$from]);
    $r['bounce'] = (function () use ($from) { $rows = q("SELECT COUNT(*) n FROM site_hits WHERE day>=? AND kind='pv' GROUP BY day, vid", [$from])->fetchAll(PDO::FETCH_COLUMN); return $rows ? round(count(array_filter($rows, fn($n) => (int)$n === 1)) / count($rows) * 100) : 0; })();
    $daily = []; for ($i = $days - 1; $i >= 0; $i--) $daily[date('Y-m-d', strtotime("-$i days"))] = ['v' => 0, 'p' => 0];
    foreach (q("SELECT day, COUNT(DISTINCT vid) v, COUNT(*) p FROM site_hits WHERE day>=? AND kind='pv' GROUP BY day", [$from])->fetchAll() as $x) if (isset($daily[$x['day']])) $daily[$x['day']] = ['v' => (int)$x['v'], 'p' => (int)$x['p']];
    $r['daily'] = $daily;
    $r['pages'] = q("SELECT path, MAX(title) title, COUNT(*) n, COUNT(DISTINCT day||vid) v FROM site_hits WHERE day>=? AND kind='pv' AND path NOT LIKE '/annonces/%' GROUP BY path ORDER BY n DESC LIMIT 10", [$from])->fetchAll();
    $r['annonces'] = q("SELECT path, MAX(title) title, COUNT(*) n, COUNT(DISTINCT day||vid) v FROM site_hits WHERE day>=? AND kind='pv' AND path LIKE '/annonces/_%' GROUP BY path ORDER BY n DESC LIMIT 10", [$from])->fetchAll();
    $r['channels'] = q("SELECT channel k, COUNT(DISTINCT day||vid) n FROM site_hits WHERE day>=? AND kind='pv' AND entry=1 GROUP BY channel ORDER BY n DESC", [$from])->fetchAll(PDO::FETCH_KEY_PAIR);
    $r['sources'] = q("SELECT source k, COUNT(*) n FROM site_hits WHERE day>=? AND kind='pv' AND entry=1 AND source<>'' GROUP BY source ORDER BY n DESC LIMIT 8", [$from])->fetchAll(PDO::FETCH_KEY_PAIR);
    $r['devices'] = q("SELECT device k, COUNT(DISTINCT day||vid) n FROM site_hits WHERE day>=? AND kind='pv' GROUP BY device ORDER BY n DESC", [$from])->fetchAll(PDO::FETCH_KEY_PAIR);
    $r['events'] = q("SELECT kind, COUNT(*) n FROM site_hits WHERE day>=? AND kind<>'pv' GROUP BY kind", [$from])->fetchAll(PDO::FETCH_KEY_PAIR);
    $r['last'] = q("SELECT ts, kind, path, label FROM site_hits WHERE kind IN ('tel','mail','form') ORDER BY ts DESC LIMIT 8")->fetchAll();
    $r['last_hit'] = (int)$one('SELECT MAX(ts) FROM site_hits');
    return $r;
}
