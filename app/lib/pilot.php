<?php
declare(strict_types=1);

/*
 * Pilotage (Direction) : registres (sinistres, contrats, demandes, échéances réglementaires, conformité, impayés, travaux votés)
 * et indicateurs calculés. Un registre = une liste de fiches JSON dans reg_items, avec une règle d'alerte.
 */

const REGS = [
    'travaux' => ['Travaux votés', '🛠️', 'Résolutions de travaux votées en AG et leur avancement', [
        'code' => ['Immeuble', 'bld'], 'objet' => ['Résolution', 'text'], 'ttc' => ['Honoraires travaux TTC', 'money'], 'ag' => ['AG du', 'date'],
        'statut' => ['Avancement', ['À lancer', 'Devis en cours', 'Ordre de service', 'Travaux en cours', 'Réceptionné', 'Abandonné']],
        'echeance' => ['Prochaine étape le', 'date'], 'note' => ['Note', 'text']]],
    'sinistres' => ['Sinistres', '💧', 'Déclarations, expertises et indemnisations', [
        'code' => ['Immeuble', 'bld'], 'date' => ['Survenu le', 'date'], 'nature' => ['Nature', ['Dégât des eaux', 'Incendie', 'Bris de glace', 'Vol / vandalisme', 'Catastrophe naturelle', 'Responsabilité civile', 'Autre']],
        'assureur' => ['Assureur', 'text'], 'ref' => ['Référence', 'text'], 'montant' => ['Montant estimé', 'money'],
        'statut' => ['Statut', ['Déclaré', 'Expertise', 'Accord / indemnisation', 'Travaux', 'Clos', 'Refusé']], 'relance' => ['Relancer le', 'date']]],
    'contrats' => ['Contrats fournisseurs', '📑', 'Échéances et dates limites de résiliation', [
        'code' => ['Immeuble', 'bld'], 'fournisseur' => ['Fournisseur', 'text'], 'objet' => ['Objet', ['Ascenseur', 'Nettoyage', 'Chauffage / ECS', 'Assurance immeuble', 'Espaces verts', 'Contrôle d\'accès', 'Désinsectisation', 'Énergie', 'Autre']],
        'montant' => ['Montant annuel TTC', 'money'], 'echeance' => ['Échéance', 'date'], 'preavis' => ['Préavis (mois)', 'int']]],
    'demandes' => ['Demandes copropriétaires', '✉️', 'Registre des demandes et réclamations', [
        'code' => ['Immeuble', 'bld'], 'demandeur' => ['Demandeur', 'text'], 'objet' => ['Objet', 'text'], 'canal' => ['Canal', ['Mail', 'Téléphone', 'Courrier', 'Visite', 'Espace client']],
        'recu' => ['Reçue le', 'date'], 'assigne' => ['Suivie par', 'person'], 'statut' => ['Statut', ['Nouvelle', 'En cours', 'Répondue', 'Close']]]],
    'echeances' => ['Échéancier réglementaire', '📅', 'DTG, PPT, ascenseurs, amiante, registre ANAH…', [
        'code' => ['Immeuble', 'bld'], 'type' => ['Obligation', ['Plan pluriannuel de travaux (PPT)', 'Diagnostic technique global (DTG)', 'DPE collectif', 'Ascenseur : contrôle technique quinquennal', 'Dossier technique amiante (DTA)', 'Ravalement', 'Registre national (ANAH) : mise à jour annuelle', 'Assurance de l\'immeuble', 'Carnet d\'entretien', 'Autre']],
        'derniere' => ['Dernière réalisation', 'date'], 'periodicite' => ['Périodicité (ans)', 'int'], 'prochaine' => ['Prochaine échéance', 'date']]],
    'conformite' => ['Conformité du cabinet', '🛡️', 'Carte pro, garantie financière, RCP, formations ALUR', [
        'objet' => ['Obligation', ['Carte professionnelle (renouvellement 3 ans)', 'Garantie financière (attestation annuelle)', 'Assurance RCP (attestation annuelle)', 'Formation continue ALUR (42 h sur 3 ans)', 'Déclaration RGPD / registre des traitements', 'Lutte anti-blanchiment (procédure)', 'Autre']],
        'personne' => ['Personne concernée', 'person'], 'echeance' => ['Échéance', 'date'], 'note' => ['Note', 'text']]],
    'impayes' => ['Impayés par immeuble', '💶', 'Charges impayées des copropriétaires (relevé ImmOpen)', [
        'code' => ['Immeuble', 'bld'], 'montant' => ['Impayés totaux', 'money'], 'plus90' => ['Dont plus de 90 jours', 'money'], 'debiteurs' => ['Débiteurs', 'int'], 'date' => ['Relevé du', 'date'], 'note' => ['Action', 'text']]],
];

function reg_items(string $reg): array {
    $rows = q('SELECT * FROM reg_items WHERE reg=? ORDER BY updated_at DESC', [$reg])->fetchAll();
    foreach ($rows as &$r) $r['d'] = json_decode($r['data'], true) ?: []; unset($r);
    return $rows;
}

/** Niveau d'alerte d'une fiche : [crit|warn|ok|'', libellé]. */
function reg_alert(string $reg, array $d): array {
    $today = date('Y-m-d'); $in = fn(int $days) => date('Y-m-d', strtotime("+$days days"));
    $date = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($d[$k] ?? '')) ? (string)$d[$k] : '';
    switch ($reg) {
        case 'travaux':
            if (in_array($d['statut'] ?? '', ['Réceptionné', 'Abandonné'], true)) return ['ok', $d['statut']];
            if (($e = $date('echeance')) && $e < $today) return ['crit', 'Étape dépassée'];
            if (($d['statut'] ?? 'À lancer') === 'À lancer' && ($a = $date('ag')) && $a < date('Y-m-d', strtotime('-60 days'))) return ['warn', 'Voté il y a plus de 2 mois, non lancé'];
            return ['', $d['statut'] ?? 'À lancer'];
        case 'sinistres':
            if (in_array($d['statut'] ?? '', ['Clos', 'Refusé'], true)) return ['ok', $d['statut']];
            if (($r = $date('relance')) && $r <= $today) return ['crit', 'À relancer'];
            if (($s = $date('date')) && $s < date('Y-m-d', strtotime('-90 days'))) return ['warn', 'Ouvert depuis plus de 90 jours'];
            return ['', $d['statut'] ?? 'Déclaré'];
        case 'contrats':
            if (!($e = $date('echeance'))) return ['', ''];
            $lim = date('Y-m-d', strtotime($e . ' -' . max(0, (int)($d['preavis'] ?? 3)) . ' months'));
            if ($e < $today) return ['warn', 'Échéance passée (reconduit ?)'];
            if ($lim < $today) return ['warn', 'Préavis dépassé le ' . date('d/m/Y', strtotime($lim))];
            if ($lim <= $in(60)) return ['crit', 'Résilier avant le ' . date('d/m/Y', strtotime($lim))];
            return ['', 'Résiliable jusqu\'au ' . date('d/m/Y', strtotime($lim))];
        case 'demandes':
            if (in_array($d['statut'] ?? '', ['Répondue', 'Close'], true)) return ['ok', $d['statut']];
            if (($r = $date('recu')) && $r < date('Y-m-d', strtotime('-7 days'))) return ['crit', 'Sans réponse depuis plus de 7 jours'];
            if (($r = $date('recu')) && $r < date('Y-m-d', strtotime('-2 days'))) return ['warn', 'Réponse attendue sous 48 h'];
            return ['', $d['statut'] ?? 'Nouvelle'];
        case 'echeances':
            $n = reg_next($d);
            if (!$n) return ['warn', 'Date à renseigner'];
            if ($n < $today) return ['crit', 'Échue le ' . date('d/m/Y', strtotime($n))];
            if ($n <= $in(180)) return ['warn', 'Le ' . date('d/m/Y', strtotime($n))];
            return ['', 'Le ' . date('d/m/Y', strtotime($n))];
        case 'conformite':
            if (!($e = $date('echeance'))) return ['warn', 'Échéance à renseigner'];
            if ($e < $today) return ['crit', 'Échue le ' . date('d/m/Y', strtotime($e))];
            if ($e <= $in(60)) return ['warn', 'Le ' . date('d/m/Y', strtotime($e))];
            return ['', 'Le ' . date('d/m/Y', strtotime($e))];
        case 'impayes':
            $m = (float)($d['montant'] ?? 0); $p = (float)($d['plus90'] ?? 0);
            if ($p >= 5000 || $m >= 15000) return ['crit', 'Impayés élevés'];
            if ($m >= 5000) return ['warn', 'À surveiller'];
            return ['', ''];
    }
    return ['', ''];
}
function reg_next(array $d): string {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($d['prochaine'] ?? ''))) return $d['prochaine'];
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($d['derniere'] ?? '')) && (int)($d['periodicite'] ?? 0) > 0) return date('Y-m-d', strtotime($d['derniere'] . ' +' . (int)$d['periodicite'] . ' years'));
    return '';
}

/** Travaux votés relevés dans les PV : une fiche par résolution (créée une seule fois). */
function reg_sync_travaux(): void {
    $rows = q('SELECT id, code, ag_date, detail FROM pv_votes')->fetchAll();
    foreach ($rows as $r) {
        foreach ((array)(json_decode((string)$r['detail'], true) ?: []) as $i => $t) {
            if (!is_array($t) || (float)($t['ttc'] ?? 0) <= 0) continue;
            $key = $r['code'] . '|' . $r['ag_date'] . '|' . ($t['res'] ?? $i);
            $d = ['code' => $r['code'], 'objet' => trim(($t['res'] ?? '') !== '' ? 'Résolution ' . $t['res'] . ' : ' . ($t['objet'] ?? '') : (string)($t['objet'] ?? '')), 'ttc' => (float)$t['ttc'], 'ag' => $r['ag_date'], 'statut' => 'À lancer'];
            q('INSERT OR IGNORE INTO reg_items(reg,code,data,ext_key,created_at,updated_at) VALUES(\'travaux\',?,?,?,?,?)', [$r['code'], json_encode($d, JSON_UNESCAPED_UNICODE), $key, time(), time()]);
        }
    }
}

/** Index des immeubles actifs (code => [imm, port, lots, v, p, gest]). */
function pilot_blds(): array {
    static $c = null; if ($c !== null) return $c; $c = [];
    foreach (hono_data()['bld'] ?? [] as $b) {
        if (hono_lost($b)) continue;
        $code = str_pad((string)$b['num'], 5, '0', STR_PAD_LEFT);
        $c[$code] = ['imm' => trim((string)$b['imm']), 'port' => strtoupper(trim((string)($b['port'] ?? ''))), 'lots' => (int)hono_num($b['lots'] ?? 0), 'v' => hono_num($b['h2627v'] ?? 0), 'p' => hono_num($b['h2526'] ?? 0), 'dateAG' => (string)($b['dateAG'] ?? ''), 'datePV' => (string)($b['datePV'] ?? '')];
    }
    foreach (q("SELECT code, label, gest, compta, assist FROM buildings WHERE kind='syndic'")->fetchAll() as $r) {
        $k = str_pad(preg_replace('/\D/', '', (string)$r['code']), 5, '0', STR_PAD_LEFT);
        if (isset($c[$k])) $c[$k] += ['gest' => (string)$r['gest'], 'compta' => (string)$r['compta'], 'assist' => (string)$r['assist']];
    }
    ksort($c, SORT_STRING);
    return $c;
}
