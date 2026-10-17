<?php
$val = qa($A, 'valeurs') ?: ['Rigueur', 'Confiance', 'Transparence'];
$sv = array_merge(($t = qa($A, 'tenue')) ? ['Tenue : ' . $t[0]] : [], qa($A, 'relation'), qa($A, 'vie'));
$org = array_merge(($h = qa($A, 'horaires')) ? ['Horaires : ' . implode(', ', $h)] : [], ($t = qa($A, 'teletravail')) ? ['Télétravail : ' . implode(', ', $t)] : [], qa($A, 'rituels'));
$tools = qa($A, 'outils') ?: ['ImmOpen', 'Outlook / Microsoft 365', 'Chemini', 'Immoscope (espace client)'];
$hist = qa($A, 'histoire');
$slides = [];
$slides[] = ['cover', ''];
$slides[] = ['chiffres', 'GID en chiffres'];
$slides[] = ['metiers', 'Nos métiers'];
$slides[] = ['valeurs', 'Nos valeurs'];
$slides[] = ['gouv', 'Gouvernance'];
$slides[] = ['orga', 'Organisation'];
$slides[] = ['equipes', 'Les équipes'];
$slides[] = ['garanties', 'Garanties et engagements'];
$slides[] = ['outils', 'Nos outils'];
if ($sv || $isDir) $slides[] = ['savoir', 'Savoir-vivre chez GID'];
$slides[] = ['fin', 'Bienvenue'];
$n = count($slides);
?>
<div class="deck" id="deck" tabindex="0" aria-roledescription="présentation">
  <div class="deck-bar"><a href="/accueil" class="btn-link">← Accueil</a><span class="deck-c"><b id="deckI">1</b> / <?= $n ?></span><button type="button" class="btn btn-ghost" id="deckFs">Plein écran</button></div>
  <div class="deck-stage">
  <?php foreach ($slides as $i => [$k, $title]): ?>
  <section class="sl sl-<?= $k ?>" data-i="<?= $i ?>"<?= $i ? ' hidden' : '' ?>>
    <?php if ($title): ?><div class="sl-eye">GID · <?= str_pad((string)($i), 2, '0', STR_PAD_LEFT) ?></div><h2><?= e($title) ?></h2><?php endif; ?>
    <?php switch ($k): case 'cover': ?>
      <div class="sl-logo">G</div>
      <h1>GID</h1>
      <p class="sl-sub">Administrateur de biens immobiliers indépendant depuis plus de 50 ans</p>
      <p class="sl-foot">Syndic de copropriété · Gestion locative · Transaction · Paris 9<sup>e</sup></p>
    <?php break; case 'chiffres': ?>
      <div class="sl-kpis">
        <div><b><?= (int)$f['buildings'] ?></b><span>immeubles en syndic</span></div>
        <div><b><?= number_format($f['lots'], 0, ',', ' ') ?></b><span>lots principaux gérés</span></div>
        <div><b>≈ 7 500</b><span>copropriétaires</span></div>
        <div><b><?= (int)$f['staff'] ?></b><span>collaborateurs</span></div>
        <div><b>1983</b><span>immatriculation de la société actuelle</span></div>
        <div><b>50 ans +</b><span>d'expérience du cabinet</span></div>
      </div>
      <?php if ($hist): ?><p class="sl-note"><?= e(implode(' · ', $hist)) ?></p><?php endif; ?>
    <?php break; case 'metiers': ?>
      <div class="sl-cols">
        <div><span class="sl-ic">🏢</span><h3>Syndic de copropriété</h3><p>Le cœur du cabinet : gestion administrative, technique et comptable des immeubles, y compris des immeubles de grande hauteur, certains suivis depuis 40 ans.</p></div>
        <div><span class="sl-ic">🔑</span><h3>Gestion locative</h3><p>Gestion d'appartements pour le compte de propriétaires bailleurs.</p></div>
        <div><span class="sl-ic">🤝</span><h3>Transaction</h3><p>Accompagnement à la vente et à la location, dont des emplacements de parking.</p></div>
      </div>
    <?php break; case 'valeurs': ?>
      <div class="sl-vals"><?php foreach ($val as $v): ?><span><?= e($v) ?></span><?php endforeach; ?></div>
      <p class="sl-note">Un cabinet indépendant, à taille humaine, proche de ses copropriétaires.</p>
    <?php break; case 'gouv': ?>
      <div class="sl-people">
        <div><span class="ag-av">MC</span><b>Mehdi Chemini</b><small>Président</small></div>
        <div><span class="ag-av">CC</span><b>Céline Chemini</b><small>Directrice générale</small></div>
        <?php if (in_array('Dorothée Gleeson', $f['by']['Direction'] ?? [], true)): $dr = qa($A, 'dorothee'); ?><div><span class="ag-av">DG</span><b>Dorothée Gleeson</b><small><?= e($dr[0] ?? 'Direction') ?></small></div><?php endif; ?>
      </div>
      <p class="sl-note">GID, SAS au capital de 160 000 € · siège 2 place d'Estienne d'Orves, 75009 Paris</p>
    <?php break; case 'orga': ?>
      <div class="sl-flow">
        <div><b>Gestionnaire</b><span>Interlocuteur du conseil syndical, AG, travaux, sinistres</span></div><i>+</i>
        <div><b>Assistante</b><span>Suivi administratif, courrier, convocations, demandes</span></div><i>+</i>
        <div><b>Comptable</b><span>Comptes de l'immeuble, appels de fonds, factures, clôtures</span></div>
      </div>
      <p class="sl-note">Chaque immeuble est suivi par une équipe stable : <?= count($f['teams']) ?> pôles de gestion.</p>
      <?php if ($org): ?><ul class="sl-ul"><?php foreach ($org as $o): ?><li><?= e($o) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php break; case 'equipes': ?>
      <div class="sl-teams">
        <?php foreach (array_slice($f['teams'], 0, 8) as $t): ?>
        <div><b><?= e($t['gest']) ?></b><small><?= (int)$t['n'] ?> immeubles</small><span><?= e(implode(' · ', array_merge(array_keys(array_slice($t['assist'], 0, 1, true)), array_keys($t['compta'])))) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php break; case 'garanties': ?>
      <div class="sl-cols four">
        <div><h3>UNIS</h3><p>Membre de l'Union des syndicats de l'immobilier, accès à son médiateur.</p></div>
        <div><h3>GALIAN</h3><p>Garantie financière de 13,38 M€ en gestion et 120 000 € en transaction.</p></div>
        <div><h3>RCP</h3><p>Responsabilité civile professionnelle par contrat groupe MMA.</p></div>
        <div><h3>Carte pro</h3><p>Carte professionnelle délivrée par la CCI Paris Île-de-France.</p></div>
      </div>
    <?php break; case 'outils': ?>
      <div class="sl-vals sm"><?php foreach ($tools as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div>
      <p class="sl-note">Espace client des copropriétaires : gid.immoscope.fr</p>
    <?php break; case 'savoir': ?>
      <?php if ($sv): ?><ul class="sl-ul big"><?php foreach ($sv as $s): ?><li><?= e($s) ?></li><?php endforeach; ?></ul>
      <?php else: ?><p class="sl-note todo">À compléter avec le questionnaire de la page Accueil (visible par la Direction uniquement).</p><?php endif; ?>
    <?php break; case 'fin': ?>
      <div class="sl-logo">G</div>
      <p class="sl-sub">Bienvenue dans l'équipe.</p>
      <p class="sl-foot">Retrouvez l'organigramme, les informations générales et vos tâches dans Chemini.</p>
    <?php break; endswitch; ?>
  </section>
  <?php endforeach; ?>
  </div>
  <div class="deck-nav"><button type="button" class="deck-b" id="deckP" aria-label="Diapositive précédente">←</button><div class="deck-dots"><?php for ($i = 0; $i < $n; $i++): ?><button type="button" data-go="<?= $i ?>" aria-label="Diapositive <?= $i + 1 ?>"></button><?php endfor; ?></div><button type="button" class="deck-b" id="deckN" aria-label="Diapositive suivante">→</button></div>
</div>
<?php if ($isDir): ?><p class="muted small deck-hint">Première version : les visuels viendront ensuite. Les réponses au questionnaire de la page <a href="/accueil">Accueil</a> complètent automatiquement les diapositives.</p><?php endif; ?>
