<?php
declare(strict_types=1);

/* Mot du Président : publié le 1er de chaque mois, après validation. Brouillon par défaut. */

/** Seul le Président (administrateur) rédige et valide. */
function pw_editor(array $u): bool { return ($u['role'] ?? '') === 'admin'; }

function pw_get(string $month): ?array { return q('SELECT * FROM president_words WHERE month=?', [$month])->fetch() ?: null; }

/** Publié = validé et son mois est commencé. */
function pw_published(array $w): bool { return $w['status'] === 'validated' && $w['month'] <= date('Y-m'); }

function pw_latest_published(): ?array { return q("SELECT * FROM president_words WHERE status='validated' AND month<=? ORDER BY month DESC LIMIT 1", [date('Y-m')])->fetch() ?: null; }

/** Mois à préparer : le mois prochain à partir du 10, sinon le mois en cours (s'il n'est pas validé). */
function pw_target_month(): string {
    $cur = date('Y-m'); $w = pw_get($cur);
    if ((int)date('j') < 10 && (!$w || $w['status'] !== 'validated')) return $cur;
    return date('Y-m', strtotime(date('Y-m-01') . ' +1 month'));
}

/** Alerte pour le Président : mot du mois cible non validé (ou absent). */
function pw_alert(): ?array {
    $m = pw_target_month(); $w = pw_get($m);
    if ($w && $w['status'] === 'validated') return null;
    if (!$w && (int)date('j') < 25 && $m > date('Y-m')) return null; // brouillon préparé le 25 : pas d'alerte avant
    return ['month' => $m, 'word' => $w, 'late' => $m <= date('Y-m')];
}

function pw_month_label(string $m): string { return ucfirst(MOIS_LONG[(int)substr($m, 5, 2) - 1]) . ' ' . substr($m, 0, 4); }

/** Mise en forme : « ## » intertitre, « - » liste, **gras**, paragraphes séparés par une ligne vide. */
function pw_render(string $txt): string {
    $out = ''; $list = false;
    foreach (preg_split('/\R{2,}/', trim($txt)) as $block) {
        $lines = preg_split('/\R/', trim($block));
        if (str_starts_with($lines[0], '## ')) { $out .= '<h2>' . pw_inline(substr(array_shift($lines), 3)) . '</h2>'; if (!$lines) continue; }
        if (preg_match('/^[-•] /', $lines[0])) { $out .= '<ul>'; foreach ($lines as $l) $out .= '<li>' . pw_inline(preg_replace('/^[-•] /', '', $l)) . '</li>'; $out .= '</ul>'; continue; }
        $out .= '<p>' . implode('<br>', array_map('pw_inline', $lines)) . '</p>';
    }
    return $out;
}
function pw_inline(string $s): string { return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($s)); }

/** Enregistre (création ou mise à jour) ; un mot validé n'est modifiable que par l'éditeur depuis la page. */
function pw_save(string $month, string $title, string $chapo, string $body, ?array $sources, bool $fromIngest = false): array {
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) return ['error' => 'Mois invalide.'];
    $title = trim($title); $body = trim($body);
    if ($title === '' || $body === '') return ['error' => 'Le titre et le texte sont obligatoires.'];
    $old = pw_get($month);
    if ($old && $fromIngest && $old['status'] === 'validated') return ['error' => 'Déjà validé : non remplacé.'];
    $src = $sources === null ? ($old['sources'] ?? null) : json_encode(array_values($sources), JSON_UNESCAPED_UNICODE);
    if ($old) q('UPDATE president_words SET title=?, chapo=?, body=?, sources=?, updated_at=? WHERE id=?', [mb_substr($title, 0, 200), mb_substr($chapo, 0, 800), $body, $src, time(), $old['id']]);
    else q('INSERT INTO president_words(month,title,chapo,body,sources,status,created_at,updated_at) VALUES(?,?,?,?,?,\'draft\',?,?)', [$month, mb_substr($title, 0, 200), mb_substr($chapo, 0, 800), $body, $src, time(), time()]);
    return ['ok' => true];
}
