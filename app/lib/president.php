<?php
declare(strict_types=1);

/* Mot du Président : préparé le 25, publié automatiquement le 1er du mois suivant, validé ou non.
   Sans validation, Mehdi est alerté sur son tableau de bord 5 jours avant et 5 jours après la publication. */

/** Seul le Président (administrateur) rédige et valide. */
function pw_editor(array $u): bool { return ($u['role'] ?? '') === 'admin'; }

function pw_get(string $month): ?array { return q('SELECT * FROM president_words WHERE month=?', [$month])->fetch() ?: null; }

/** Publié = son mois est commencé (le 1er), qu'il ait été validé ou non. */
function pw_published(array $w): bool { return $w['month'] <= date('Y-m'); }

function pw_latest_published(): ?array { return q('SELECT * FROM president_words WHERE month<=? ORDER BY month DESC LIMIT 1', [date('Y-m')])->fetch() ?: null; }

/** Mois à préparer : le mois prochain à partir du 10, sinon le mois en cours (s'il n'est pas validé). */
function pw_target_month(): string {
    $cur = date('Y-m'); $w = pw_get($cur);
    if ((int)date('j') < 10 && (!$w || $w['status'] !== 'validated')) return $cur;
    return date('Y-m', strtotime(date('Y-m-01') . ' +1 month'));
}

/** Alerte pour le Président : les 5 derniers jours du mois si le mot du mois suivant n'est pas validé (il partira quand même le 1er),
    et du 1er au 5 si le mot publié n'a pas été validé. */
function pw_alert(): ?array {
    $d = (int)date('j'); $last = (int)date('t');
    if ($d <= 5) { $m = date('Y-m'); $w = pw_get($m); if ($w && $w['status'] !== 'validated') return ['month' => $m, 'word' => $w, 'phase' => 'after', 'late' => true]; return null; }
    if ($d >= $last - 4) { $m = date('Y-m', strtotime(date('Y-m-01') . ' +1 month')); $w = pw_get($m); if (!$w || $w['status'] !== 'validated') return ['month' => $m, 'word' => $w, 'phase' => 'before', 'late' => false, 'days' => $last - $d + 1]; }
    return null;
}
/** Texte de l'alerte (titre, détail). */
function pw_alert_text(array $al): array {
    $lab = pw_month_label($al['month']);
    if ($al['phase'] === 'after') return ["Le mot de $lab a été publié sans votre validation", 'Il est en ligne depuis le 1er : relisez-le et validez-le, ou corrigez-le si besoin (la modification est visible aussitôt).'];
    $j = $al['days'] ?? 1; $quand = $j <= 1 ? 'demain' : "dans $j jours";
    if (!$al['word']) return ["Le mot de $lab n'est pas encore rédigé", "Publication prévue le 1er ($quand) : le brouillon est préparé automatiquement le 25."];
    return ["Le mot de $lab n'est pas validé", "Il sera publié automatiquement le 1er ($quand), même sans validation : relisez-le et cliquez sur « Valider la publication »."];
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
    if ($old && $fromIngest && $month <= date('Y-m')) return ['error' => 'Déjà publié : non remplacé.'];
    $src = $sources === null ? ($old['sources'] ?? null) : json_encode(array_values($sources), JSON_UNESCAPED_UNICODE);
    if ($old) q('UPDATE president_words SET title=?, chapo=?, body=?, sources=?, updated_at=? WHERE id=?', [mb_substr($title, 0, 200), mb_substr($chapo, 0, 800), $body, $src, time(), $old['id']]);
    else q('INSERT INTO president_words(month,title,chapo,body,sources,status,created_at,updated_at) VALUES(?,?,?,?,?,\'draft\',?,?)', [$month, mb_substr($title, 0, 200), mb_substr($chapo, 0, 800), $body, $src, time(), time()]);
    return ['ok' => true];
}
