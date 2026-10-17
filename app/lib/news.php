<?php
declare(strict_types=1);

/* Actualités du métier, classées en quatre rubriques. Une actualité reste « en cours » 30 jours, puis passe aux archives (toujours consultables). */

const NEWS_CATS = [
    'syndic' => ['Syndic', 'Profession, contrats, honoraires et pratiques des syndics', '🏢'],
    'copro' => ['Copropriété', 'Lois, travaux, rénovation énergétique, AG et charges', '🏘️'],
    'transaction' => ['Achat-vente', 'Prix, volumes, crédit immobilier et marché de la transaction', '🔑'],
    'locatif' => ['Gestion locative', 'Loyers, encadrement, DPE et décence, fiscalité des bailleurs', '📋'],
];
const NEWS_DAYS = 30;

/** Rubrique déduite du titre et du résumé quand la synthèse ne l'indique pas. */
function news_guess_cat(string $txt): string {
    $t = mb_strtolower($txt);
    if (preg_match('/\b(loyer|locataire|bailleur|location|bail\b|encadrement|irl|meublé|lmnp|décence|vacant)/u', $t)) return 'locatif';
    if (preg_match('/\b(prix|notaire|crédit|taux|acquéreur|acheteur|vente|ventes|transaction|achat|primo)/u', $t)) return 'transaction';
    if (preg_match('/\b(syndic|syndics|honoraires du syndic|contrat de syndic|fnaim|unis|carte professionnelle)/u', $t)) return 'syndic';
    return 'copro';
}

/** Enregistre des articles (synthèse quotidienne) ; un article déjà connu (même adresse) est mis à jour. */
function news_store(array $items, string $origin = 'digest'): int {
    $n = 0;
    foreach ($items as $i) {
        $url = (string)($i['url'] ?? ''); $title = trim((string)($i['title'] ?? ''));
        if (!preg_match('#^https?://#', $url) || $title === '') continue;
        $cat = isset(NEWS_CATS[$i['cat'] ?? '']) ? $i['cat'] : news_guess_cat($title . ' ' . ($i['summary'] ?? ''));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($i['date'] ?? '')) ? $i['date'] : date('Y-m-d');
        q('INSERT INTO news_items(url,title,source,date,summary,why,cat,important,origin,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)
           ON CONFLICT(url) DO UPDATE SET title=excluded.title, source=excluded.source, summary=CASE WHEN excluded.summary<>\'\' THEN excluded.summary ELSE news_items.summary END,
             why=CASE WHEN excluded.why<>\'\' THEN excluded.why ELSE news_items.why END, cat=excluded.cat, important=MAX(news_items.important, excluded.important)',
          [$url, mb_substr($title, 0, 220), mb_substr((string)($i['source'] ?? ''), 0, 80), $date, mb_substr((string)($i['summary'] ?? ''), 0, 1200),
           mb_substr((string)($i['why'] ?? ''), 0, 500), $cat, !empty($i['important']) ? 1 : 0, $origin, time()]);
        $n++;
    }
    return $n;
}
function news_since(): string { return date('Y-m-d', strtotime('-' . NEWS_DAYS . ' days')); }

/** Compteurs par rubrique sur les 30 derniers jours : [cat => ['n' => total, 'imp' => importants, 'top' => [2 derniers titres]]]. */
function news_blocks(): array {
    $o = []; $since = news_since();
    foreach (NEWS_CATS as $k => $c) {
        $o[$k] = ['n' => (int)q('SELECT COUNT(*) FROM news_items WHERE cat=? AND date>=?', [$k, $since])->fetchColumn(),
                  'imp' => (int)q('SELECT COUNT(*) FROM news_items WHERE cat=? AND date>=? AND important=1', [$k, $since])->fetchColumn(),
                  'top' => q('SELECT title, source, date, important FROM news_items WHERE cat=? AND date>=? ORDER BY important DESC, date DESC, id DESC LIMIT 2', [$k, $since])->fetchAll()];
    }
    return $o;
}
function news_list(?string $cat, bool $archive, string $q = '', int $limit = 200): array {
    $w = [$archive ? 'date<?' : 'date>=?']; $p = [news_since()];
    if ($cat) { $w[] = 'cat=?'; $p[] = $cat; }
    if ($q !== '') { $w[] = '(title LIKE ? OR summary LIKE ? OR why LIKE ? OR source LIKE ?)'; $l = '%' . $q . '%'; array_push($p, $l, $l, $l, $l); }
    return q('SELECT * FROM news_items WHERE ' . implode(' AND ', $w) . ' ORDER BY ' . ($archive ? 'date DESC' : 'important DESC, date DESC') . ', id DESC LIMIT ' . $limit, $p)->fetchAll();
}

function page_news(array $u, string $sub = ''): never {
    $archive = $sub === 'archives'; $cat = isset(NEWS_CATS[$sub]) ? $sub : null;
    if ($sub !== '' && !$archive && !$cat) { http_response_code(404); render('notfound', ['title' => 'Introuvable']); }
    $q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80); $acat = isset(NEWS_CATS[$_GET['cat'] ?? '']) ? $_GET['cat'] : null;
    $items = news_list($archive ? $acat : $cat, $archive, $q);
    render('news', ['title' => 'Actualités', 'nav' => 'dash', 'u' => $u, 'cat' => $cat, 'archive' => $archive, 'acat' => $acat, 'q' => $q, 'items' => $items, 'blocks' => news_blocks(),
        'archN' => (int)q('SELECT COUNT(*) FROM news_items WHERE date<?', [news_since()])->fetchColumn()]);
}
