<?php
$eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €'; $fd = fn($d) => $d ? date('d/m/Y', strtotime((string)$d)) : '—';
$ag = $r['ag_date'] ?? ''; $gest = trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($r['gest'] ?? $b['gest'] ?? '')));
$checks = [
    ['Convocation envoyée', !empty($r['convoc']), !empty($r['limite_convoc']) ? 'à envoyer avant le ' . $fd($r['limite_convoc']) : ''],
    ['Comptes de l\'exercice clos le ' . $fd($r['fin_ex'] ?? ''), false, 'approbation des comptes'],
    ['Budget prévisionnel ' . (isset($r['fin_ex']) ? date('Y', strtotime($r['fin_ex'] . ' +1 year')) : ''), false, 'vote du budget'],
    ['Honoraires du syndic', false, 'proposition : ' . $eur($mand['prop'] ?? $b['v'] * 1.03)],
    ['Renouvellement du mandat de syndic', !$mand, $mand ? 'mandat ' . ($mand['expired'] ? 'échu le ' : 'jusqu\'au ') . $fd($mand['fin']) : 'mandat en cours'],
    ['Conseil syndical', false, 'élection ou renouvellement'],
    ['Plan pluriannuel de travaux / fonds de travaux', false, 'obligatoire pour les copropriétés de plus de 15 ans'],
];
?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage/ag">Préparation des AG</a> · <span class="mono"><?= e($code) ?></span></div>
    <h1><?= e($b['imm']) ?></h1>
    <div class="date">AG <?= $ag ? 'du ' . e(date_long(strtotime($ag))) . (!empty($r['ag_heure']) ? ' à ' . e($r['ag_heure']) : '') : 'à planifier' ?> · <?= (int)$b['lots'] ?> lots · gestionnaire <?= e($gest) ?><?= !empty($b['compta']) ? ' · comptable ' . e($b['compta']) : '' ?></div>
  </div>
  <button type="button" class="btn btn-ghost" data-print>Imprimer la fiche</button>
</header>
<div class="ac-grid">
  <article class="panel ac-card">
    <h3>Chiffres clés</h3>
    <ul class="ac-ul">
      <li>Honoraires 2025-2026 : <b><?= e($eur($b['p'])) ?></b> · 2026-2027 à voter : <b><?= e($eur($b['v'])) ?></b></li>
      <li>Prix au lot : <?= $b['lots'] ? e($eur($b['v'] / $b['lots'])) : '—' ?></li>
      <?php if ($f): ?><li>Rentabilité : <span class="dot-fin <?= e($f['color']) ?>"></span> <?= e($f['label']) ?><?= $f['color'] !== 'lost' ? ' · marge estimée ' . round($f['margin'] * 100) . ' %' : '' ?></li><?php endif; ?>
      <li>Fin d'exercice : <?= e($fd($r['fin_ex'] ?? '')) ?> · fin de mandat : <?= e($fd($r['fin_mandat'] ?? '')) ?></li>
      <?php if (!empty($r['last'])): ?><li>Dernière AG : <?= e($fd($r['last'])) ?></li><?php endif; ?>
    </ul>
  </article>
  <article class="panel ac-card">
    <h3>Ordre du jour : points à préparer</h3>
    <ul class="pa-checks">
      <?php foreach ($checks as [$t, $ok, $d]): ?><li class="<?= $ok ? 'ok' : '' ?>"><span class="pa-b"><?= $ok ? '✓' : '' ?></span><span><b><?= e($t) ?></b><?php if ($d): ?><small><?= e($d) ?></small><?php endif; ?></span></li><?php endforeach; ?>
    </ul>
  </article>
  <?php if ($mand): ?>
  <article class="panel ac-card">
    <h3>Proposition d'honoraires</h3>
    <p class="ac-p"><b><?= e($eur($mand['prop'])) ?> TTC</b> contre <?= e($eur($mand['v'])) ?> aujourd'hui (+<?= number_format(($mand['prop'] / max(1, $mand['v']) - 1) * 100, 1, ',', ' ') ?> %).</p>
    <p class="ac-p muted"><?= e(ucfirst($mand['why'])) ?>.</p>
  </article>
  <?php endif; ?>
  <article class="panel ac-card">
    <h3>Travaux votés précédemment</h3>
    <?php if (!$tr): ?><p class="muted small">Aucune résolution de travaux relevée dans les PV.</p>
    <?php else: ?><ul class="ac-ul"><?php foreach ($tr as $t): ?><li><?= e($t['d']['objet'] ?? '') ?> · <?= e($t['d']['statut'] ?? 'À lancer') ?></li><?php endforeach; ?></ul><?php endif; ?>
    <a class="more" href="/pilotage/travaux">Suivi des travaux →</a>
  </article>
  <?php foreach (['contrats' => 'Contrats fournisseurs', 'echeances' => 'Échéances réglementaires', 'sinistres' => 'Sinistres', 'impayes' => 'Impayés'] as $k => $l): ?>
  <article class="panel ac-card">
    <h3><?= e($l) ?></h3>
    <?php if (!$regs[$k]): ?><p class="muted small">Rien d'enregistré pour cet immeuble.</p>
    <?php else: ?><ul class="ac-ul"><?php foreach ($regs[$k] as $x): $al = reg_alert($k, $x['d']); $d = $x['d']; ?><li><?= e(implode(' · ', array_filter([$d['objet'] ?? $d['type'] ?? $d['nature'] ?? '', $d['fournisseur'] ?? '', isset($d['montant']) && $d['montant'] !== '' ? $eur($d['montant']) : '']))) ?><?= $al[1] ? ' <span class="chip ' . ($al[0] === 'crit' ? 'crit' : ($al[0] === 'warn' ? 'warn' : 'neutral')) . '">' . e($al[1]) . '</span>' : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
    <a class="more" href="/pilotage/<?= e($k) ?>">Registre →</a>
  </article>
  <?php endforeach; ?>
  <?php if (!empty($info['president'])): ?>
  <article class="panel ac-card"><h3>Conseil syndical</h3><p class="ac-p">Président : <?= e($info['president']) ?><?= !empty($info['president_phone']) ? ' · ' . e($info['president_phone']) : '' ?></p></article>
  <?php endif; ?>
</div>
