<?php $eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €'; $mxL = max(1, ...array_values(array_map(fn($p) => $p['lots'], $P ?: [['lots' => 1]]))); ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage">Pilotage</a> · équipes</div>
    <h1>Gestionnaires et charge</h1>
    <div class="date">Honoraires, rentabilité et suivi des AG par gestionnaire · charge de chaque collaborateur (immeubles, lots, AG des 90 prochains jours, tâches)</div>
  </div>
</header>
<section class="pl-sec">
  <div class="sec-h"><h2>Par gestionnaire</h2></div>
  <div class="ag-group"><div class="tbl"><table class="sec-table ag-tbl" data-sortable>
    <thead><tr><th>Gestionnaire</th><th class="r">Immeubles</th><th class="r">Lots</th><th class="r">Hon. 26-27</th><th class="r">Évol.</th><th class="r">€ / lot</th><th>Rentabilité</th><th class="r">AG tenues</th><th class="r">PV à diffuser</th><th class="r">Convoc. en retard</th></tr></thead>
    <tbody>
    <?php foreach ($G as $g): $tot = max(1, $g['green'] + $g['orange'] + $g['red']); ?>
      <tr>
        <td data-v="<?= e($g['name']) ?>"><a href="/equipe/<?= e(team_slug($g['name'])) ?>"><strong><?= e($g['name']) ?></strong></a></td>
        <td class="r mono" data-v="<?= $g['imm'] ?>"><?= $g['imm'] ?></td>
        <td class="r mono" data-v="<?= $g['lots'] ?>"><?= number_format($g['lots'], 0, ',', ' ') ?></td>
        <td class="r mono" data-v="<?= $g['v'] ?>"><strong><?= e($eur($g['v'])) ?></strong></td>
        <td class="r mono" data-v="<?= $g['p'] ? $g['v'] / $g['p'] : 0 ?>"><?= $g['p'] ? ($g['v'] >= $g['p'] ? '+' : '') . number_format(($g['v'] / $g['p'] - 1) * 100, 1, ',', ' ') . ' %' : '—' ?></td>
        <td class="r mono" data-v="<?= $g['lots'] ? round($g['v'] / $g['lots']) : 0 ?>"><?= $g['lots'] ? e($eur($g['v'] / $g['lots'])) : '—' ?></td>
        <td data-v="<?= $g['red'] ?>"><div class="ov-bar pe-bar" title="<?= $g['green'] ?> rentables, <?= $g['orange'] ?> fragiles, <?= $g['red'] ?> non rentables"><i class="g" style="width:<?= round($g['green'] / $tot * 100) ?>%"></i><i class="o" style="width:<?= round($g['orange'] / $tot * 100) ?>%"></i><i class="r" style="width:<?= round($g['red'] / $tot * 100) ?>%"></i></div><small class="muted"><?= $g['red'] ?> non rentable<?= $g['red'] > 1 ? 's' : '' ?></small></td>
        <td class="r mono" data-v="<?= $g['held'] ?>"><?= $g['held'] ?> / <?= $g['imm'] ?></td>
        <td class="r" data-v="<?= $g['nopv'] ?>"><?= $g['nopv'] ? '<span class="chip crit">' . $g['nopv'] . '</span>' : '0' ?></td>
        <td class="r" data-v="<?= $g['convoc'] ?>"><?= $g['convoc'] ? '<span class="chip ' . ($g['convoc'] > 5 ? 'crit' : 'warn') . '">' . $g['convoc'] . '</span>' : '0' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div>
</section>
<section class="pl-sec">
  <div class="sec-h"><h2>Charge par collaborateur</h2><span class="muted small">Barre : lots suivis, comparés au plus gros portefeuille</span></div>
  <div class="pe-grid">
    <?php foreach ($P as $p): ?>
    <a class="pe-card" href="/equipe/<?= e(team_slug($p['name'])) ?>">
      <span class="pe-h"><b><?= e($p['name']) ?></b><small><?= e($p['role']) ?></small></span>
      <span class="pe-l"><i style="width:<?= round($p['lots'] / $mxL * 100) ?>%"></i></span>
      <span class="pe-n"><span><b><?= $p['imm'] ?></b> imm.</span><span><b><?= number_format($p['lots'], 0, ',', ' ') ?></b> lots</span><span><b><?= $p['ag90'] ?></b> AG / 90 j</span><span class="<?= $p['late'] ? 'crit' : '' ?>"><b><?= $p['tasks'] ?></b> tâche<?= $p['tasks'] > 1 ? 's' : '' ?><?= $p['late'] ? ' · ' . $p['late'] . ' en retard' : '' ?></span></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
