<?php
$list = function (array $items) { return $items ? '<ul class="ac-ul">' . implode('', array_map(fn($x) => '<li>' . nl2br(e($x)) . '</li>', $items)) . '</ul>' : ''; };
$todo = fn() => $isDir ? '<p class="ac-todo">À compléter avec le questionnaire ci-dessous.</p>' : '';
$val = qa($A, 'valeurs') ?: ['Rigueur', 'Confiance', 'Transparence'];
$sections = [
    ['Organisation', array_merge(
        ($h = qa($A, 'horaires')) ? ['Horaires de bureau : ' . implode(', ', $h)] : [],
        ($p = qa($A, 'accueil_public')) ? ['Accueil du public et standard : ' . implode(', ', $p)] : [],
        ($t = qa($A, 'teletravail')) ? ['Télétravail : ' . implode(', ', $t)] : [],
        ($b = qa($A, 'binomes')) ? ['Équipes : ' . implode(', ', $b)] : [])],
    ['Savoir-vivre', array_merge(($t = qa($A, 'tenue')) ? ['Tenue : ' . implode(', ', $t)] : [], qa($A, 'relation'), qa($A, 'vie'))],
    ['Vie du cabinet', array_merge(qa($A, 'rituels'), ($d = qa($A, 'dejeuner')) ? ['Déjeuner : ' . implode(', ', $d)] : [])],
    ['Vos outils', qa($A, 'outils') ?: ['ImmOpen : gestion des immeubles et de la comptabilité', 'Outlook / Microsoft 365 : messagerie et agenda', 'Dashboard : tâches, informations, organigramme']],
    ['Vos premiers jours', array_merge(['Recevoir votre accès à Dashboard (lien d\'invitation) et activer Face ID ou le code à 6 chiffres', 'Découvrir votre équipe dans l\'organigramme', 'Lire les informations générales'], qa($A, 'integration'))],
    ['Contacts utiles', qa($A, 'contacts')],
];
$nAns = count($A); $nQ = count($Q);
?>
<header class="head">
  <div>
    <div class="eyebrow">Équipe · bienvenue chez GID</div>
    <h1>Bienvenue <em>chez GID</em></h1>
    <div class="date">Tout ce qu'il faut savoir pour bien démarrer : la société, l'organisation, les équipes et les bonnes pratiques</div>
  </div>
</header>

<section class="ac-hero">
  <div class="ac-hero-t">
    <span class="eyebrow">Présentation de la société</span>
    <h2>GID, administrateur de biens indépendant depuis plus de 50 ans</h2>
    <p>Syndic de copropriété, gestion locative et transaction à Paris, depuis le 2 place d'Estienne d'Orves (9<sup>e</sup>). <?= e(implode(' · ', $val)) ?>.</p>
    <a class="btn btn-primary" href="/accueil/presentation">Lancer la présentation →</a>
  </div>
  <div class="ac-kpis">
    <div><b><?= (int)$f['buildings'] ?></b><span>immeubles en syndic</span></div>
    <div><b><?= number_format($f['lots'], 0, ',', ' ') ?></b><span>lots principaux</span></div>
    <div><b>≈ 7 500</b><span>copropriétaires</span></div>
    <div><b><?= (int)$f['staff'] ?></b><span>collaborateurs</span></div>
  </div>
</section>

<div class="ac-grid">
  <article class="panel ac-card">
    <h3>La société</h3>
    <ul class="ac-ul">
      <li>GID, SAS au capital de 160 000 €, immatriculée en 1983 (SIREN 328 620 737)</li>
      <li>Président : Mehdi Chemini · Directrice générale : Céline Chemini</li>
      <li>Siège : 2 place d'Estienne d'Orves, 75009 Paris · 01 42 66 20 88 · contact@gidsas.fr</li>
      <li>Carte professionnelle CCI Paris Île-de-France · membre de l'UNIS · garantie financière GALIAN</li>
    </ul>
    <?php if ($h = qa($A, 'histoire')): ?><p class="ac-p"><?= nl2br(e(implode("\n", $h))) ?></p><?php endif; ?>
  </article>
  <article class="panel ac-card">
    <h3>Les équipes</h3>
    <ul class="ac-ul">
      <?php foreach (['Direction' => 'Direction', 'Gestionnaire' => 'Gestionnaires', 'Comptable' => 'Comptables', 'Assistant(e)' => 'Assistantes'] as $r => $l): if (empty($f['by'][$r])) continue; ?>
      <li><b><?= e($l) ?> (<?= count($f['by'][$r]) ?>)</b> : <?= e(implode(', ', $f['by'][$r])) ?></li>
      <?php endforeach; ?>
    </ul>
    <a class="more" href="/organigramme">Voir l'organigramme →</a>
  </article>
  <?php foreach ($sections as [$t, $items]): if (!$items && !$isDir) continue; ?>
  <article class="panel ac-card">
    <h3><?= e($t) ?></h3>
    <?= $items ? $list($items) : $todo() ?>
  </article>
  <?php endforeach; ?>
</div>

<?php if ($isDir): ?>
<section class="ac-qcm">
  <details class="panel" <?= $nAns < $nQ / 2 ? 'open' : '' ?>>
    <summary><span><b>Compléter la présentation</b><small>Questionnaire visible par la Direction uniquement · <?= $nAns ?>/<?= $nQ ?> questions renseignées · les réponses alimentent cette page et la présentation</small></span><span class="sc-act">Ouvrir</span></summary>
    <form id="qcmForm" class="stack">
      <?php $grp = ''; foreach ($Q as $k => [$g, $label, $type, $opts]): $cur = $A[$k] ?? ['c' => [], 'o' => '']; ?>
      <?php if ($g !== $grp): $grp = $g; ?><h4 class="ac-g"><?= e($g) ?></h4><?php endif; ?>
      <fieldset class="ac-q">
        <legend><?= e($label) ?></legend>
        <?php if ($type !== 'text'): ?><div class="ac-opts"><?php foreach ($opts as $o): ?><label class="ac-opt"><input type="<?= $type === 'radio' ? 'radio' : 'checkbox' ?>" name="q_<?= e($k) ?>" value="<?= e($o) ?>" <?= in_array($o, (array)$cur['c'], true) ? 'checked' : '' ?>> <?= e($o) ?></label><?php endforeach; ?></div><?php endif; ?>
        <input class="input" name="o_<?= e($k) ?>" maxlength="1500" value="<?= e((string)$cur['o']) ?>" placeholder="<?= $type === 'text' ? 'Votre réponse' : 'Autre / précision (facultatif)' ?>">
      </fieldset>
      <?php endforeach; ?>
      <p class="form-error" hidden></p>
      <div><button class="btn btn-primary">Enregistrer les réponses</button></div>
    </form>
  </details>
</section>
<?php endif; ?>
