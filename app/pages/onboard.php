<?php
declare(strict_types=1);

/* Équipe : organigramme, page Accueil (nouvel arrivant), présentation de la société, questionnaire de la Direction. */

const ACCUEIL_QCM = [
    'histoire' => ['Histoire du cabinet', 'Année de fondation du cabinet (le site indique « plus de 50 ans », la société actuelle est immatriculée en 1983) et étapes marquantes', 'text', []],
    'dorothee' => ['Direction', 'Rôle de Dorothée Gleeson dans l\'organisation', 'radio', ['Direction administrative et financière', 'Direction des ressources humaines', 'Direction de la gestion / des gestionnaires', 'Responsable juridique']],
    'valeurs' => ['Valeurs', 'Valeurs à mettre en avant (le site cite rigueur, confiance, transparence)', 'check', ['Rigueur', 'Confiance', 'Transparence', 'Réactivité', 'Proximité', 'Indépendance']],
    'horaires' => ['Organisation', 'Horaires de bureau', 'radio', ['9 h – 18 h', '9 h – 17 h 30', '8 h 30 – 17 h 30', '9 h 30 – 18 h 30']],
    'accueil_public' => ['Organisation', 'Horaires d\'accueil du public et du standard', 'radio', ['Identiques aux horaires de bureau', 'Le matin uniquement', 'Sur rendez-vous uniquement']],
    'teletravail' => ['Organisation', 'Télétravail', 'radio', ['Pas de télétravail', '1 jour par semaine', '2 jours par semaine', 'Au cas par cas avec la Direction']],
    'binomes' => ['Organisation', 'Organisation des équipes (relevée dans ImmOpen : chaque gestionnaire travaille avec une assistante et un ou deux comptables)', 'radio', ['Oui, des binômes / trinômes stables par portefeuille', 'Oui, mais les assistantes travaillent aussi en pool', 'Non, organisation à préciser']],
    'tenue' => ['Savoir-vivre', 'Tenue', 'radio', ['Tenue professionnelle (veste) exigée', 'Tenue correcte, business casual', 'Libre, tenue soignée pour les AG et rendez-vous']],
    'relation' => ['Savoir-vivre', 'Règles de la relation copropriétaires', 'check', ['Vouvoiement systématique', 'Réponse aux mails sous 48 h', 'Rappel téléphonique sous 24 h', 'Signature de mail commune', 'Accusé de réception des demandes', 'Ne jamais s\'engager sur un délai sans validation']],
    'vie' => ['Savoir-vivre', 'Vie au bureau', 'check', ['Ponctualité aux AG (arrivée 30 min avant)', 'Bureau rangé en fin de journée', 'Confidentialité des dossiers', 'Prévenir son binôme en cas d\'absence', 'Cuisine / espace commun à laisser propre']],
    'rituels' => ['Vie du cabinet', 'Rituels d\'équipe', 'check', ['Réunion d\'équipe hebdomadaire', 'Point mensuel avec la Direction', 'Séminaire annuel', 'Repas / afterwork d\'équipe', 'Galette / fêtes de fin d\'année']],
    'dejeuner' => ['Vie du cabinet', 'Déjeuner', 'radio', ['Tickets restaurant / carte déjeuner', 'Pause d\'une heure, libre', 'Cuisine équipée sur place']],
    'outils' => ['Outils', 'Outils utilisés au quotidien', 'check', ['ImmOpen (gestion)', 'Outlook / Microsoft 365', 'Teams', 'Immoscope (espace client)', 'Chemini (cette plateforme)', 'Signature électronique', 'Vote électronique en AG']],
    'integration' => ['Intégration', 'Accueil d\'un nouvel arrivant', 'check', ['Un tuteur désigné', 'Formation ImmOpen la première semaine', 'Présentation aux équipes', 'Point d\'étonnement à 1 mois', 'Formation ALUR (loi : 42 h sur 3 ans)']],
    'contacts' => ['Intégration', 'Contacts utiles à indiquer (informatique, RH, paie, clés, badge, fournitures)', 'text', []],
];

function accueil_answers(): array { return json_decode((string)q("SELECT v FROM kv WHERE k='accueil_qcm'")->fetchColumn(), true) ?: []; }
/** Réponse lisible : choix + précision libre. */
function qa(array $A, string $k): array {
    $a = $A[$k] ?? []; $out = array_values(array_filter((array)($a['c'] ?? []), 'strlen'));
    if (trim((string)($a['o'] ?? '')) !== '') $out[] = trim((string)$a['o']);
    return $out;
}

/** Chiffres et équipes publics (aucune donnée financière interne). */
function company_facts(): array {
    $people = team_people(); $by = [];
    foreach ($people as $p) $by[$p['role']][] = $p['name'];
    $teams = [];
    foreach (q("SELECT gest, compta, assist, COUNT(*) n FROM buildings WHERE kind='syndic' AND gest IS NOT NULL GROUP BY gest, compta, assist")->fetchAll() as $r) {
        $g = trim((string)$r['gest']); if ($g === '') continue;
        $t = &$teams[$g]; $t = ($t ?? []) + ['gest' => $g, 'n' => 0, 'compta' => [], 'assist' => []];
        $t['n'] += (int)$r['n'];
        if (($c = trim((string)$r['compta'])) !== '') $t['compta'][$c] = ($t['compta'][$c] ?? 0) + (int)$r['n'];
        if (($a = trim((string)$r['assist'])) !== '' && $a !== '(aucun)') $t['assist'][$a] = ($t['assist'][$a] ?? 0) + (int)$r['n'];
        unset($t);
    }
    foreach ($teams as &$t) { arsort($t['compta']); arsort($t['assist']); } unset($t);
    uasort($teams, fn($a, $b) => $b['n'] <=> $a['n']);
    $lots = 0; foreach (hono_data()['bld'] ?? [] as $b) if (!hono_lost($b)) $lots += (int)hono_num($b['lots'] ?? 0);
    return [
        'buildings' => (int)q("SELECT COUNT(*) FROM buildings WHERE kind='syndic'")->fetchColumn(),
        'lots' => $lots, 'staff' => count($people), 'by' => $by, 'teams' => $teams,
    ];
}

function page_organigramme(array $u): never {
    require_once APP_DIR . '/pages/team.php';
    $today = date('Y-m-d'); $stat = []; $todayList = [];
    foreach (team_load($u) as $t) { // visibilité selon le rôle (team_where)
        if ($t['done_at'] || $t['title'] === SURVEY_TASK) continue;
        $s = &$stat[$t['person']]; $s = ($s ?? []) + ['open' => 0, 'late' => 0]; $s['open']++;
        if ($t['due'] && $t['due'] < $today) $s['late']++; unset($s);
        if ($t['due'] && $t['due'] <= $today && $t['title'] !== SURVEY_TASK) $todayList[] = $t;
    }
    render('organigramme', ['title' => 'Collaborateurs', 'nav' => 'orga', 'u' => $u, 'f' => company_facts(), 'people' => team_people(), 'A' => accueil_answers(),
        'stat' => $stat, 'todayList' => $todayList, 'isDir' => can_space($u, 'direction'), 'me' => team_me($u)]);
}

function page_accueil(array $u): never {
    render('accueil', ['title' => 'Accueil', 'nav' => 'accueil', 'u' => $u, 'f' => company_facts(), 'A' => accueil_answers(), 'isDir' => can_space($u, 'direction'), 'Q' => ACCUEIL_QCM]);
}

function page_presentation(array $u): never {
    render('presentation', ['title' => 'Présentation de GID', 'nav' => 'accueil', 'u' => $u, 'wide' => true, 'f' => company_facts(), 'A' => accueil_answers(), 'isDir' => can_space($u, 'direction')]);
}
