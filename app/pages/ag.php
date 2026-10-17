<?php
declare(strict_types=1);
/*
 * Planning des AG par gestionnaire (source : ImmOpen › A.G. › Planning AG), avec la pastille de rentabilité de chaque immeuble.
 */

function page_ag(array $u): never {
    if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Le planning des AG est réservé à la Direction.'], false); }
    immopen_sync();
    $raw = json_decode((string)q("SELECT v FROM kv WHERE k='ag_planning'")->fetchColumn(), true) ?: [];
    $today = date('Y-m-d');
    $in30 = date('Y-m-d', strtotime('+30 days'));
    $in180 = date('Y-m-d', strtotime('+180 days'));
    $rows = []; $alerts = ['mandat' => 0, 'convoc' => 0, 'aplanif' => 0, 'mois' => 0];
    foreach ($raw['rows'] ?? [] as $r) {
        $code = (string)($r['code'] ?? '');
        foreach (['gest', 'compta'] as $k) $r[$k] = trim(preg_replace('/^(M\.|Mme|Melle|Mlle)\s*/u', '', (string)($r[$k] ?? '')));
        $f = ctype_digit($code) ? bld_finance($code) : null;
        $r['color'] = $f['color'] ?? null; $r['colorLabel'] = $f['label'] ?? ''; $r['margin'] = $f['margin'] ?? null;
        $r['flags'] = []; $r['keys'] = [];
        $next = ($r['ag_date'] ?? null) && $r['ag_date'] >= $today ? $r['ag_date'] : null;
        $r['next'] = $next;
        if ($next) {
            $r['status'] = 'planifiee';
            if ($next <= $in30) { $alerts['mois']++; $r['keys'][] = 'mois'; }
            if (!empty($r['limite_convoc']) && $r['limite_convoc'] < $today && empty($r['convoc'])) { $r['flags'][] = ['crit', 'Convocation en retard (limite ' . date('d/m/Y', strtotime($r['limite_convoc'])) . ')']; $alerts['convoc']++; $r['keys'][] = 'convoc'; }
            elseif (!empty($r['limite_convoc']) && empty($r['convoc'])) $r['flags'][] = ['warn', 'Convoquer avant le ' . date('d/m/Y', strtotime($r['limite_convoc']))];
            elseif (!empty($r['convoc'])) $r['flags'][] = ['ok', 'Convocation envoyée'];
        } else {
            $heldForEx = !empty($r['last']) && !empty($r['fin_ex']) && $r['last'] >= $r['fin_ex'];
            $r['status'] = $heldForEx ? 'tenue' : 'aplanifier';
            if (!$heldForEx) { $alerts['aplanif']++; $r['keys'][] = 'aplanifier'; if (!empty($r['delai_max'])) $r['flags'][] = [$r['delai_max'] < $in180 ? 'crit' : 'warn', 'À tenir avant le ' . date('d/m/Y', strtotime($r['delai_max']))]; }
        }
        if (!empty($r['fin_mandat'])) {
            if ($r['fin_mandat'] < $today) { $r['flags'][] = ['crit', 'Mandat expiré le ' . date('d/m/Y', strtotime($r['fin_mandat']))]; $alerts['mandat']++; $r['keys'][] = 'mandat'; }
            elseif ($next && $r['fin_mandat'] < $next) { $r['flags'][] = ['crit', 'Mandat échu avant l\'AG (' . date('d/m/Y', strtotime($r['fin_mandat'])) . ')']; $alerts['mandat']++; $r['keys'][] = 'mandat'; }
            elseif (!$next && $r['fin_mandat'] < $in180) { $r['flags'][] = ['warn', 'Fin de mandat le ' . date('d/m/Y', strtotime($r['fin_mandat'])) . ' : AG de renouvellement à prévoir']; $alerts['mandat']++; $r['keys'][] = 'mandat'; }
        }
        $rows[] = $r;
    }
    $order = ['planifiee' => 0, 'aplanifier' => 1, 'tenue' => 2];
    usort($rows, fn($a, $b) => [$order[$a['status']], $a['next'] ?? ($a['delai_max'] ?? '9999'), $a['nom'] ?? ''] <=> [$order[$b['status']], $b['next'] ?? ($b['delai_max'] ?? '9999'), $b['nom'] ?? '']);
    $by = [];
    foreach ($rows as $r) $by[$r['gest'] ?: 'Sans gestionnaire'][] = $r;
    ksort($by);
    render('ag', ['title' => 'Planning des AG', 'nav' => 'ag', 'u' => $u, 'wide' => true, 'by' => $by, 'alerts' => $alerts, 'date' => $raw['date'] ?? null, 'total' => count($rows), 'stc' => array_count_values(array_column($rows, 'status')), 'nAlert' => count(array_filter($rows, fn($r) => (bool)array_filter($r['flags'], fn($f) => $f[0] === 'crit'))), 'prefs' => user_prefs((int)$u['id'])]);
}
