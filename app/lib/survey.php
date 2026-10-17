<?php
declare(strict_types=1);

/* Enquête de fonctionnement des équipes : questions, et outils candidats déduits des réponses. */

const SURVEY_TASK = 'Répondre au questionnaire de fonctionnement';

const SURVEY = [
    ['Vous', [
        'role' => ['Votre métier', 'radio', ['Gestionnaire', 'Comptable', 'Assistant(e)', 'Direction', 'Autre']],
        'anciennete' => ['Depuis combien de temps êtes-vous chez GID ?', 'radio', ['Moins d\'un an', '1 à 3 ans', '3 à 10 ans', 'Plus de 10 ans']],
    ]],
    ['Votre temps', [
        'temps' => ['Où passez-vous le plus de temps ? (3 choix maximum)', 'check3', ['Mails', 'Téléphone', 'Saisie dans ImmOpen', 'Recherches dans ImmOpen', 'Préparation des AG', 'AG et réunions de conseil syndical', 'Visites d\'immeubles', 'Suivi des travaux', 'Sinistres et assurances', 'Courrier et classement', 'Relances (impayés, prestataires)']],
        'interruptions' => ['Combien de fois par jour êtes-vous interrompu(e) par une demande urgente ?', 'radio', ['Moins de 5', '5 à 10', '10 à 20', 'Plus de 20']],
    ]],
    ['Vos tâches récurrentes', [
        'recurrent' => ['Quelles tâches faites-vous chaque semaine ?', 'check', ['Répondre aux mails des copropriétaires', 'Relancer les impayés', 'Appels de fonds', 'Saisir des factures', 'Rapprochement bancaire', 'Demander et comparer des devis', 'Ordres de service aux entreprises', 'Suivre les sinistres', 'Préparer les convocations d\'AG', 'Rédiger et diffuser les PV', 'Questionnaires notaires / pré-états datés', 'Mutations (ventes de lots)', 'Mettre à jour les fiches immeubles et copropriétaires', 'Suivre les contrats fournisseurs', 'Comptes rendus de visite', 'Classement et archivage']],
        'recurrent_long' => ['Parmi elles, laquelle vous prend le plus de temps et comment la faites-vous aujourd\'hui ?', 'text', []],
    ]],
    ['Les demandes des copropriétaires', [
        'demandes' => ['Quelles informations les copropriétaires vous demandent-ils le plus souvent ?', 'check', ['Leur solde ou relevé de compte', 'Le détail d\'un appel de fonds', 'La date de la prochaine AG', 'Une copie de PV, du règlement ou de documents', 'L\'avancement des travaux', 'Le suivi d\'un sinistre', 'Les coordonnées d\'un prestataire ou du gardien', 'Une attestation (assurance, paiement)', 'Badges, clés, télécommandes', 'Troubles de voisinage, nuisances', 'Contrats et entretien de l\'immeuble', 'Budget, charges, répartition']],
        'volume' => ['Combien de demandes de copropriétaires traitez-vous par jour ?', 'radio', ['Moins de 5', '5 à 15', '15 à 30', 'Plus de 30']],
        'canal' => ['Par quel canal arrivent-elles surtout ?', 'check', ['Mail', 'Téléphone', 'Courrier', 'Visite au cabinet', 'Espace client (Immoscope)']],
        'cs' => ['Que préparez-vous régulièrement pour les conseils syndicaux ?', 'check', ['État des dépenses', 'Budget prévu / réalisé', 'Avancement des travaux', 'Liste des impayés', 'Suivi des sinistres', 'Contrats et prestataires', 'Planning des interventions']],
        'format' => ['Sous quelle forme aimeriez-vous partager ces informations ?', 'check', ['Tableau en ligne (lien à partager)', 'Présentation', 'PDF', 'Excel', 'Espace copropriétaire en ligne']],
    ]],
    ['Vos recherches dans ImmOpen', [
        'immopen' => ['Que recherchez-vous le plus souvent dans ImmOpen ?', 'check', ['Solde d\'un copropriétaire', 'Historique des paiements', 'Facture d\'un fournisseur', 'Coordonnées des copropriétaires', 'Lots et tantièmes', 'Contrats de l\'immeuble', 'PV et historique des AG', 'Budget et dépenses réalisées', 'Détail d\'un appel de fonds', 'Travaux votés', 'Dates d\'échéance (mandat, exercice)']],
        'immopen_freq' => ['Combien de recherches faites-vous dans ImmOpen par jour ?', 'radio', ['Moins de 10', '10 à 30', '30 à 60', 'Plus de 60']],
        'immopen_pain' => ['Qu\'est-ce qui est long ou difficile à trouver dans ImmOpen ?', 'text', []],
    ]],
    ['Ce que vous aimeriez automatiser', [
        'auto' => ['Qu\'aimeriez-vous automatiser en priorité ? (5 choix maximum)', 'check5', ['Réponses types aux mails fréquents', 'Relances automatiques des impayés', 'Convocations d\'AG pré-remplies', 'PV d\'AG pré-rédigés', 'Suivi des travaux partagé avec le conseil syndical', 'Tableau de bord partageable pour le conseil syndical', 'Saisie automatique des factures reçues par mail', 'Rappels d\'échéances (contrats, mandats, diagnostics)', 'Comptes rendus de visite sur téléphone', 'Pré-états datés et questionnaires notaires', 'Suivi des sinistres avec relances', 'Fiche immeuble complète en un clic']],
        'outil' => ['Si vous aviez un outil sur mesure, que ferait-il ?', 'text', []],
    ]],
    ['Vos outils', [
        'note_immopen' => ['ImmOpen vous convient-il ?', 'radio', ['Très bien', 'Bien', 'Moyennement', 'Pas du tout']],
        'note_chemini' => ['Chemini vous est-il utile ?', 'radio', ['Très utile', 'Utile', 'Peu utile', 'Je ne l\'utilise pas encore']],
        'remarque' => ['Une remarque, une idée ?', 'text', []],
    ]],
];

/** Outils candidats : signaux (question => réponses) qui les justifient, bénéfice, partage. */
const SURVEY_TOOLS = [
    ['Fiche immeuble partageable', 'Toutes les informations d\'un immeuble en un clic (comptes, travaux, contrats, sinistres, AG), exportable en tableau en ligne pour le conseil syndical.', ['auto' => ['Fiche immeuble complète en un clic', 'Tableau de bord partageable pour le conseil syndical'], 'immopen' => ['Contrats de l\'immeuble', 'Budget et dépenses réalisées', 'PV et historique des AG'], 'cs' => ['État des dépenses', 'Budget prévu / réalisé']], 2],
    ['Assistant de réponses aux copropriétaires', 'Réponses types préparées à partir des données (solde, date d\'AG, documents), à valider avant envoi.', ['auto' => ['Réponses types aux mails fréquents'], 'demandes' => ['Leur solde ou relevé de compte', 'La date de la prochaine AG', 'Une copie de PV, du règlement ou de documents', 'Une attestation (assurance, paiement)'], 'temps' => ['Mails']], 2],
    ['Relances d\'impayés automatiques', 'Relances graduées préparées chaque semaine, liste des débiteurs par immeuble pour le conseil syndical.', ['auto' => ['Relances automatiques des impayés'], 'recurrent' => ['Relancer les impayés'], 'cs' => ['Liste des impayés'], 'immopen' => ['Solde d\'un copropriétaire', 'Historique des paiements']], 2],
    ['Suivi des travaux partagé', 'Avancement des travaux votés (devis, ordre de service, réception) dans un tableau en ligne partagé avec le conseil syndical.', ['auto' => ['Suivi des travaux partagé avec le conseil syndical'], 'demandes' => ['L\'avancement des travaux'], 'cs' => ['Avancement des travaux', 'Planning des interventions'], 'recurrent' => ['Ordres de service aux entreprises', 'Demander et comparer des devis']], 2],
    ['Préparation d\'AG assistée', 'Convocation, ordre du jour et projet de PV pré-remplis à partir des données de l\'immeuble.', ['auto' => ['Convocations d\'AG pré-remplies', 'PV d\'AG pré-rédigés'], 'recurrent' => ['Préparer les convocations d\'AG', 'Rédiger et diffuser les PV'], 'temps' => ['Préparation des AG']], 3],
    ['Lecture automatique des factures', 'Factures reçues par mail lues, rattachées à l\'immeuble et au contrat, prêtes à saisir.', ['auto' => ['Saisie automatique des factures reçues par mail'], 'recurrent' => ['Saisir des factures', 'Rapprochement bancaire'], 'immopen' => ['Facture d\'un fournisseur']], 3],
    ['Suivi des sinistres', 'Déclarations, expertises et relances avec rappels, état partageable avec le copropriétaire concerné.', ['auto' => ['Suivi des sinistres avec relances'], 'recurrent' => ['Suivre les sinistres'], 'demandes' => ['Le suivi d\'un sinistre'], 'temps' => ['Sinistres et assurances']], 1],
    ['Pré-états datés automatiques', 'Questionnaires notaires pré-remplis (soldes, procédures, travaux votés).', ['auto' => ['Pré-états datés et questionnaires notaires'], 'recurrent' => ['Questionnaires notaires / pré-états datés', 'Mutations (ventes de lots)'], 'demandes' => ['Budget, charges, répartition']], 2],
    ['Visites d\'immeubles sur téléphone', 'Compte rendu de visite avec photos depuis l\'iPhone, envoyé au conseil syndical.', ['auto' => ['Comptes rendus de visite sur téléphone'], 'recurrent' => ['Comptes rendus de visite'], 'temps' => ['Visites d\'immeubles']], 2],
    ['Rappels d\'échéances', 'Contrats, mandats, diagnostics et assurances : rappel automatique avant chaque échéance.', ['auto' => ['Rappels d\'échéances (contrats, mandats, diagnostics)'], 'recurrent' => ['Suivre les contrats fournisseurs'], 'immopen' => ['Dates d\'échéance (mandat, exercice)', 'Contrats de l\'immeuble']], 1],
    ['Espace copropriétaire enrichi', 'Solde, documents, travaux et sinistres consultables en ligne : moins d\'appels entrants.', ['format' => ['Espace copropriétaire en ligne', 'Tableau en ligne (lien à partager)'], 'demandes' => ['Leur solde ou relevé de compte', 'Une copie de PV, du règlement ou de documents', 'Le détail d\'un appel de fonds'], 'canal' => ['Téléphone']], 3],
];

function survey_all(): array {
    $rows = q('SELECT s.*, u.name, u.email FROM survey_answers s JOIN users u ON u.id=s.user_id')->fetchAll();
    foreach ($rows as &$r) $r['d'] = json_decode($r['data'], true) ?: []; unset($r);
    return $rows;
}

/** Score de chaque outil : nombre de réponses concordantes (une personne compte au plus une fois par question). */
function survey_tools(array $done): array {
    $out = [];
    foreach (SURVEY_TOOLS as [$name, $desc, $sig, $effort]) {
        $score = 0; $people = 0; $roles = [];
        foreach ($done as $r) {
            $hit = 0;
            foreach ($sig as $q => $opts) if (array_intersect((array)($r['d'][$q] ?? []), $opts)) $hit++;
            if ($hit) { $people++; $score += $hit; $roles[(string)($r['d']['role'][0] ?? '?')] = true; }
        }
        $out[] = ['name' => $name, 'desc' => $desc, 'score' => $score, 'people' => $people, 'roles' => array_keys($roles), 'effort' => $effort];
    }
    usort($out, fn($a, $b) => [$b['score'], $a['effort']] <=> [$a['score'], $b['effort']]);
    return $out;
}
