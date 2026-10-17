<?php
declare(strict_types=1);

/* Direction › Stratégies : leviers pour les immeubles non rentables et pour l'ensemble du portefeuille. */

const STRAT_HOUR = 150; // € HT de coût horaire (même hypothèse que le modèle de rentabilité)

function page_strategies(array $u): never {
    if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); }
    $d = hono_data(); $rows = []; $tot = ['green' => 0, 'orange' => 0, 'red' => 0]; $lots = ['green' => 0, 'orange' => 0, 'red' => 0];
    foreach ($d['bld'] ?? [] as $b) {
        if (hono_lost($b)) continue;
        $code = str_pad((string)$b['num'], 5, '0', STR_PAD_LEFT); $f = bld_finance($code); if (!$f || !isset($tot[$f['color']])) continue;
        $tot[$f['color']]++; $lots[$f['color']] += $f['lots'];
        $target = (COST_FIXED + COST_LOT * $f['lots'] + COST_HOUR * $f['hours']) / (1 - MARGIN_GREEN) * 1.2;
        $rows[] = ['code' => $code, 'imm' => (string)$b['imm'], 'port' => (string)($b['port'] ?? ''), 'color' => $f['color'], 'margin' => $f['margin'], 'lots' => $f['lots'], 'ttc' => $f['ttc'],
                   'hours' => $f['hours'], 'gap' => max(0, $target - $f['ttc']), 'idx' => $f['index'], 'med' => $f['median']];
    }
    $red = array_values(array_filter($rows, fn($r) => $r['color'] === 'red')); usort($red, fn($a, $b) => $b['gap'] <=> $a['gap']);
    $fragile = array_values(array_filter($rows, fn($r) => $r['color'] === 'orange'));
    $weak = array_merge($red, $fragile);
    $sum = fn(array $a, string $k) => array_sum(array_column($a, $k));
    $med = $rows ? $rows[0]['med'] : 0;
    $lotsWeak = $sum($weak, 'lots'); $lotsAll = $sum($rows, 'lots');
    $under = array_filter($rows, fn($r) => $r['idx'] > 0 && $r['idx'] < 0.85);
    $hoursWeak = array_filter($weak, fn($r) => $r['hours'] >= 4);
    // Estimations prudentes (hypothèses affichées sur la page)
    $L = [
        'weak' => [
            ['k' => 'ia', 'ico' => '🤖', 't' => 'Assistant IA copropriétaires', 'd' => 'Réponses automatiques aux demandes courantes (mails, téléphone), suivi des sinistres et des demandes, relances : le gestionnaire ne traite plus que les cas complexes.',
             'gain' => $lotsWeak * 0.5 * STRAT_HOUR * 1.2, 'hyp' => '30 min gagnées par lot et par an', 'n' => count($weak), 'effort' => 2, 'delai' => '2 à 3 mois'],
            ['k' => 'reval', 'ico' => '📈', 't' => 'Revalorisation ciblée à la prochaine AG', 'd' => 'Proposer l\'honoraire qui ramène l\'immeuble au vert, avec un argumentaire chiffré (coût réel, prix au lot du marché).',
             'gain' => $sum($red, 'gap') * 0.5, 'hyp' => 'la moitié de l\'écart obtenue', 'n' => count($red), 'effort' => 1, 'delai' => 'saison d\'AG'],
            ['k' => 'reel', 'ico' => '⏱️', 't' => 'Heures au-delà du forfait facturées', 'd' => 'Limiter les réunions de conseil syndical et visites incluses ; facturer au réel ce qui dépasse.',
             'gain' => array_sum(array_map(fn($r) => max(0, $r['hours'] - 2) * STRAT_HOUR * 1.2, $hoursWeak)), 'hyp' => 'heures au-delà de 2 h par an', 'n' => count($hoursWeak), 'effort' => 1, 'delai' => 'renouvellement'],
            ['k' => 'agdemat', 'ico' => '💻', 't' => 'AG courtes et dématérialisées', 'd' => 'Vote par correspondance, visio, ordre du jour resserré, convocations électroniques : moins d\'heures de soirée et de frais d\'envoi.',
             'gain' => count($weak) * 1.5 * STRAT_HOUR * 1.2, 'hyp' => '1 h 30 gagnée par AG', 'n' => count($weak), 'effort' => 2, 'delai' => 'prochaine saison'],
            ['k' => 'secteur', 'ico' => '🗺️', 't' => 'Regrouper les petits immeubles par secteur', 'd' => 'Même gestionnaire et mêmes jours de visite pour les immeubles proches : moins de déplacements.',
             'gain' => null, 'hyp' => 'gain de temps difficile à chiffrer', 'n' => count(array_filter($weak, fn($r) => $r['lots'] < 12)), 'effort' => 2, 'delai' => '6 mois'],
            ['k' => 'sortie', 'ico' => '🚪', 't' => 'Décider des mandats durablement déficitaires', 'd' => 'Si l\'AG refuse la revalorisation deux années de suite : ne pas renouveler et réallouer le temps aux immeubles rentables.',
             'gain' => null, 'hyp' => 'au cas par cas', 'n' => count(array_filter($red, fn($r) => $r['margin'] < -0.2)), 'effort' => 1, 'delai' => 'renouvellement'],
        ],
        'all' => [
            ['k' => 'index', 'ico' => '🔁', 't' => 'Indexation annuelle systématique', 'd' => 'Appliquer l\'indexation prévue au contrat à chaque renouvellement, sans exception.',
             'gain' => $sum($rows, 'ttc') * 0.01, 'hyp' => '1 point d\'indexation récupéré', 'n' => count($rows), 'effort' => 1, 'delai' => 'immédiat'],
            ['k' => 'median', 'ico' => '⚖️', 't' => 'Rattraper les prix au lot sous 85 % de la médiane', 'd' => 'Les immeubles nettement sous le prix au lot du cabinet sont des cibles prioritaires de revalorisation.',
             'gain' => array_sum(array_map(fn($r) => max(0, $r['lots'] * $r['med'] * 0.85 - $r['ttc']), $under)) * 0.5, 'hyp' => 'la moitié du rattrapage', 'n' => count($under), 'effort' => 1, 'delai' => 'saison d\'AG'],
            ['k' => 'annexes', 'ico' => '🧾', 't' => 'Facturer toutes les prestations annexes', 'd' => 'Mutations, relances, vacations hors forfait, photocopies : contrôle mensuel de ce qui est réalisé et non facturé.',
             'gain' => $lotsAll * 5, 'hyp' => '5 € par lot et par an', 'n' => count($rows), 'effort' => 1, 'delai' => '1 mois'],
            ['k' => 'pvia', 'ico' => '📝', 't' => 'PV d\'AG et comptes rendus générés par IA', 'd' => 'Projet de PV prêt le soir de l\'AG à partir des votes ; diffusion sous 48 h.',
             'gain' => count($rows) * 1 * STRAT_HOUR * 1.2, 'hyp' => '1 h gagnée par AG', 'n' => count($rows), 'effort' => 2, 'delai' => '2 mois'],
            ['k' => 'factia', 'ico' => '📥', 't' => 'Factures fournisseurs lues et pré-saisies par IA', 'd' => 'Lecture des factures reçues par mail, rapprochement avec l\'immeuble et le contrat, contrôle des doublons avant saisie.',
             'gain' => count($rows) * 1.5 * STRAT_HOUR * 1.2, 'hyp' => '1 h 30 par immeuble et par an', 'n' => count($rows), 'effort' => 3, 'delai' => '3 mois'],
            ['k' => 'extranet', 'ico' => '📱', 't' => 'Espace copropriétaire en ligne', 'd' => 'Documents, appels de fonds, suivi des demandes : moins d\'appels entrants et une image moderne face à la concurrence.',
             'gain' => null, 'hyp' => 'fidélisation et temps gagné', 'n' => count($rows), 'effort' => 2, 'delai' => '3 mois'],
        ],
    ];
    $choices = json_decode((string)q("SELECT v FROM kv WHERE k='strat_choices'")->fetchColumn(), true) ?: [];
    render('strategies', ['title' => 'Stratégies', 'nav' => 'strategies', 'u' => $u, 'tot' => $tot, 'lots' => $lots, 'red' => $red, 'fragile' => $fragile, 'L' => $L,
        'gapRed' => $sum($red, 'gap'), 'med' => $med, 'choices' => $choices, 'nAll' => count($rows)]);
}
