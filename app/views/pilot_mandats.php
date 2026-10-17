<?php $eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €'; $gain = array_sum(array_map(fn($r) => $r['prop'] - $r['v'], $rows)); ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage">Pilotage</a> · mandats</div>
    <h1>Renouvellements de mandat</h1>
    <div class="date"><?= count($rows) ?> mandats échus ou à échéance sous 6 mois · proposition d'honoraires calculée (indexation 3 %, rattrapage vers 85 % de la médiane de <?= e($eur($med)) ?> par lot, retour à l'équilibre des immeubles non rentables, plafonné à +15 %) · gain potentiel <?= e($eur($gain)) ?> TTC / an</div>
  </div>
</header>
<?php if (!$rows): ?><div class="empty"><strong>Aucun mandat à renouveler dans les 6 mois.</strong>Source : planning des AG ImmOpen.</div>
<?php else: ?>
<div class="ag-group"><div class="tbl"><table class="sec-table ag-tbl" data-sortable>
  <thead><tr><th>Fin de mandat</th><th>Immeuble</th><th>Gestionnaire</th><th class="r">Lots</th><th class="r">Actuel</th><th class="r">Proposé</th><th class="r">Écart</th><th>Prochaine AG</th><th>Argument</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['expired'] ? 'ag-alert' : '' ?>">
      <td class="mono small" data-v="<?= e($r['fin']) ?>"><?= $r['expired'] ? '<span class="chip crit">Échu le ' . date('d/m/Y', strtotime($r['fin'])) . '</span>' : date('d/m/Y', strtotime($r['fin'])) ?></td>
      <td data-v="<?= e($r['imm']) ?>"><a href="/immeubles/<?= e($r['code']) ?>"><span class="mono muted"><?= e($r['code']) ?></span> <strong><?= e($r['imm']) ?></strong></a> <?php if ($r['color']): ?><span class="dot-fin <?= e($r['color']) ?>"></span><?php endif; ?></td>
      <td class="small" data-v="<?= e($r['gest']) ?>"><?= e($r['gest']) ?></td>
      <td class="r mono" data-v="<?= $r['lots'] ?>"><?= $r['lots'] ?></td>
      <td class="r mono" data-v="<?= $r['v'] ?>"><?= e($eur($r['v'])) ?></td>
      <td class="r mono" data-v="<?= $r['prop'] ?>"><strong><?= e($eur($r['prop'])) ?></strong></td>
      <td class="r mono" data-v="<?= $r['prop'] - $r['v'] ?>"><span class="chip ok">+<?= number_format(($r['prop'] / max(1, $r['v']) - 1) * 100, 1, ',', ' ') ?> %</span></td>
      <td class="mono small" data-v="<?= e($r['ag'] ?: '9') ?>"><?= $r['ag'] ? date('d/m/Y', strtotime($r['ag'])) : '<span class="chip warn">À planifier</span>' ?></td>
      <td class="small"><?= e($r['why']) ?></td>
      <td><a class="btn-link" href="/pilotage/ag/<?= e($r['code']) ?>">Préparer l'AG</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
