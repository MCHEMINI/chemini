<?php
declare(strict_types=1);
/*
 * Fiche immeuble : données ImmOpen (annuaire), tableau des honoraires (finances) et informations saisies par l'équipe.
 * La rentabilité est une estimation transparente : honoraires HT comparés à un coût de gestion modélisé.
 */

const COST_FIXED = 2400;   // € HT / an / immeuble : AG, comptabilité, suivi courant
const COST_LOT = 95;       // € HT / an / lot : appels de fonds, relations copropriétaires
const COST_HOUR = 150;     // € HT / heure de prestation incluse au contrat (CS, visites)
const MARGIN_GREEN = 0.20; // marge estimée ≥ 20 % : vert
const MARGIN_ORANGE = 0.0; // 0 à 20 % : orange ; négative : rouge

/** Données actuelles du tableau des honoraires (modifications de l'équipe comprises). */
function hono_data(): array {
    static $d = null;
    if ($d !== null) return $d;
    $st = q('SELECT json FROM doc_state WHERE slug=?', ['honoraires'])->fetchColumn();
    $d = $st ? (json_decode((string)$st, true) ?: []) : [];
    if (empty($d['bld'])) {
        $html = (string)q('SELECT html FROM docs WHERE slug=?', ['honoraires'])->fetchColumn();
        if (preg_match('#<script id="seed" type="application/json">(.*?)</script>#s', $html, $m)) $d = json_decode($m[1], true) ?: [];
    }
    return $d;
}

function hono_num(mixed $v): float { return is_numeric($v) ? (float)$v : 0.0; }
function hono_lost(array $b): bool { return hono_num($b['perdus'] ?? 0) > 0 && hono_num($b['h2627v'] ?? 0) == 0.0; }

/** Index par numéro d'immeuble ImmOpen + médiane du prix au lot des immeubles actifs. */
function hono_index(): array {
    static $ix = null;
    if ($ix !== null) return $ix;
    $d = hono_data(); $by = []; $ratios = [];
    foreach ($d['bld'] ?? [] as $b) {
        if (!isset($b['num'])) continue;
        $by[(int)$b['num']] = $b;
        if (!hono_lost($b) && hono_num($b['lots']) > 0 && hono_num($b['h2627v']) > 0) $ratios[] = hono_num($b['h2627v']) / hono_num($b['lots']);
    }
    sort($ratios); $c = count($ratios);
    $median = $c ? ($c % 2 ? $ratios[intdiv($c, 2)] : ($ratios[$c / 2 - 1] + $ratios[$c / 2]) / 2) : 0;
    $contr = [];
    foreach ($d['contr'] ?? [] as $k) if (ctype_digit((string)$k['imm'])) $contr[(int)$k['imm']] = $k;
    return $ix = ['by' => $by, 'median' => $median, 'contr' => $contr];
}

/** Rapport financier d'un immeuble, avec la couleur de rentabilité et son explication. */
function bld_finance(string $code): ?array {
    if (!ctype_digit($code)) return null;
    $ix = hono_index(); $b = $ix['by'][(int)$code] ?? null;
    if (!$b) return null;
    $lots = hono_num($b['lots']); $ttc = hono_num($b['h2627v']); $ht = $ttc / 1.2;
    $hours = hono_num($b['cs1']) + 2 * hono_num($b['cs2']) + hono_num($b['visite']);
    $cost = COST_FIXED + COST_LOT * $lots + COST_HOUR * $hours;
    $margin = $ht > 0 ? ($ht - $cost) / $ht : -1;
    $perLot = $lots > 0 ? $ttc / $lots : 0; $med = $ix['median']; $idx = $med > 0 ? $perLot / $med : 0;
    $f = ['lots' => $lots, 'perdus' => hono_num($b['perdus']), 'ttc' => $ttc, 'ht' => $ht, 'hours' => $hours, 'cost' => $cost, 'margin' => $margin,
          'perLot' => $perLot, 'median' => $med, 'index' => $idx, 'b' => $b, 'contr' => $ix['contr'][(int)$code] ?? null, 'reasons' => [], 'todo' => []];
    $f['years'] = ['2025-2026 voté' => hono_num($b['h2526']), '2026-2027 proposé' => $ttc, '2026-2027 voté' => hono_num($b['h2627e']), '2027-2028 budget' => hono_num($b['h2728']), '2028-2029 budget' => hono_num($b['h2829'])];

    if (hono_lost($b)) {
        $f['color'] = 'lost'; $f['label'] = 'Immeuble perdu';
        $f['reasons'][] = 'Le mandat n\'a pas été renouvelé : ' . number_format(hono_num($b['h2526']), 0, ',', ' ') . ' € d\'honoraires 2025-2026 en moins.';
        $f['todo'][] = 'Faire un retour d\'expérience (motif, concurrent, prix) pour protéger les immeubles comparables.';
        return $f;
    }
    $f['color'] = $margin >= MARGIN_GREEN ? 'green' : ($margin >= MARGIN_ORANGE ? 'orange' : 'red');
    $f['label'] = ['green' => 'Rentable', 'orange' => 'Rentabilité fragile', 'red' => 'Non rentable'][$f['color']];
    $fmt = fn($v) => number_format($v, 0, ',', ' ');
    $f['reasons'][] = 'Honoraires 2026-2027 de ' . $fmt($ht) . ' € HT pour un coût de gestion estimé à ' . $fmt($cost) . ' € HT, soit une marge estimée de ' . round($margin * 100) . ' %.';
    if ($idx > 0) $f['reasons'][] = 'Prix au lot de ' . $fmt($perLot) . ' € TTC, soit ' . round($idx * 100) . ' % de la médiane du cabinet (' . $fmt($med) . ' €).';
    if ($hours >= 4) $f['reasons'][] = round($hours) . ' heures de conseil syndical et de visites sont incluses au contrat, ce qui alourdit le coût.';
    if ($lots > 0 && $lots < 12) $f['reasons'][] = 'Petit immeuble (' . (int)$lots . ' lots) : le travail fixe (AG, comptabilité) pèse lourd par rapport aux honoraires.';
    $e = hono_num($b['h2627e']);
    if ($e > 0 && $e < $ttc - 1) $f['reasons'][] = 'L\'AG a voté ' . $fmt($e) . ' € au lieu des ' . $fmt($ttc) . ' € proposés.';
    if (hono_num($b['h2526']) > 0 && $ttc <= hono_num($b['h2526']) * 1.001) $f['reasons'][] = 'Aucune hausse proposée par rapport à 2025-2026.';

    if ($f['color'] !== 'green') {
        $target = (COST_FIXED + COST_LOT * $lots + COST_HOUR * $hours) / (1 - MARGIN_GREEN) * 1.2;
        $f['todo'][] = 'Pour passer au vert, viser environ ' . $fmt($target) . ' € TTC d\'honoraires annuels (+' . $fmt(max(0, $target - $ttc)) . ' €).';
        if ($idx > 0 && $idx < 0.85) $f['todo'][] = 'Le prix au lot est nettement sous la médiane : préparer une revalorisation argumentée à la prochaine AG.';
        if ($hours >= 4) $f['todo'][] = 'Limiter les réunions et visites incluses, ou facturer les heures au-delà du forfait.';
    } elseif ($idx > 0 && $idx < 0.7) {
        $f['todo'][] = 'Rentable grâce à sa taille, mais le prix au lot est à ' . round($idx * 100) . ' % de la médiane : le remonter à 85 % rapporterait environ ' . $fmt(max(0, $lots * $med * 0.85 - $ttc)) . ' € TTC par an.';
    }
    return $f;
}

function bld_info(string $code): array {
    return q('SELECT i.*, u.name AS by_name FROM building_info i LEFT JOIN users u ON u.id=i.updated_by WHERE code=?', [$code])->fetch() ?: [];
}
