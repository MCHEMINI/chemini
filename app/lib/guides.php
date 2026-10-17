<?php
declare(strict_types=1);

/* Guides de l'équipe : procédures pas à pas, modifiables par la Direction. */

function guide_editor(array $u): bool { return can_space($u, 'direction'); }
/** Qui voit un guide : toute l'équipe, Direction, Président ou Famille. */
const GUIDE_AUD = ['tous' => 'Toute l\'équipe', 'direction' => 'Direction', 'president' => 'Président', 'famille' => 'Famille'];
function guide_visible(array $u, ?string $aud): bool {
    return match ($aud ?: 'tous') { 'direction' => can_space($u, 'direction'), 'famille' => can_space($u, 'famille'), 'president' => pw_editor($u), default => true };
}
function guide_slug(string $t): string { return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t)), '-') ?: 'guide'; }

/** Mise en forme : « ## » section, « 1. » étape (case à cocher), « > » astuce, « ! » attention, « - » liste, **gras**, `chemin`. */
function guide_render(string $txt, string $slug): string {
    $out = ''; $n = 0; $open = '';
    $close = function () use (&$open, &$out) { if ($open) { $out .= "</$open>"; $open = ''; } };
    foreach (preg_split('/\R/', trim($txt)) as $line) {
        $l = rtrim($line);
        if ($l === '') { $close(); continue; }
        if (str_starts_with($l, '## ')) { $close(); $out .= '<h2 class="gd-sec">' . guide_inline(substr($l, 3)) . '</h2>'; continue; }
        if (preg_match('/^\d+[.)]\s+(.*)$/', $l, $m)) {
            if ($open !== 'ol') { $close(); $out .= '<ol class="gd-steps">'; $open = 'ol'; }
            $n++; $id = 'st-' . $slug . '-' . $n;
            $out .= '<li class="gd-step"><input type="checkbox" id="' . e($id) . '" data-step><label for="' . e($id) . '"><span class="gd-n">' . $n . '</span><span class="gd-t">' . guide_inline($m[1]) . '</span></label></li>';
            continue;
        }
        if (preg_match('/^[-•]\s+(.*)$/', $l, $m)) { $li = guide_inline($m[1]); if (str_contains($m[1], '[[') && !str_contains($li, 'gd-btn')) continue; // ligne de liens toute réservée à un autre rôle
            if ($open !== 'ul') { $close(); $out .= '<ul class="gd-list">'; $open = 'ul'; } $out .= '<li>' . $li . '</li>'; continue; }
        $close();
        if (str_starts_with($l, '> ')) { $out .= '<p class="gd-tip"><span aria-hidden="true">💡</span><span>' . guide_inline(preg_replace('/^Astuce\s*:\s*/u', '', substr($l, 2))) . '</span></p>'; continue; }
        if (str_starts_with($l, '! ')) { $out .= '<p class="gd-warn"><span aria-hidden="true">⚠️</span><span>' . guide_inline(substr($l, 2)) . '</span></p>'; continue; }
        $out .= '<p>' . guide_inline($l) . '</p>';
    }
    $close();
    return $out;
}
function guide_inline(string $s): string {
    $s = e($s);
    $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/`(.+?)`/', '<code>$1</code>', $s);
    // [[Libellé|/adresse]] : bouton vers une page de Chemini (adresses internes uniquement)
    $s = preg_replace_callback('/\[\[([^|\]]{1,80})\|(\/[a-z0-9\/_#-]{0,80})\]\]/', function ($m) {
        if (preg_match('#^/guides/([a-z0-9-]+)$#', $m[2], $g) && !guide_can_open($g[1])) return ''; // guide réservé à un autre rôle : pas de bouton
        return '<a class="gd-btn" href="' . $m[2] . '">' . $m[1] . ' <span aria-hidden="true">→</span></a>';
    }, $s);
    return preg_replace('/(?<=\s|^)((?:[A-ZÉ][\w.\'’ -]*?\s›\s)+[A-ZÉ][\w.\'’ -]*?)(?=[.,:;)]|\s(?:puis|pour|avec)\b|$)/u', '<span class="gd-path">$1</span>', $s);
}
function guide_steps(string $txt): int { return preg_match_all('/^\d+[.)]\s+/m', $txt); }
function guide_can_open(string $slug): bool {
    static $aud = null; $u = current_user(); if (!$u) return false;
    $aud ??= q('SELECT slug, audience FROM guides')->fetchAll(PDO::FETCH_KEY_PAIR);
    return isset($aud[$slug]) && guide_visible($u, $aud[$slug]);
}
function guide_plain(string $txt): string { return preg_replace('/\[\[([^|\]]+)\|[^\]]*\]\]/', '$1', $txt); }
