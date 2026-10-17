<?php $eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €'; $mx = max(1, ...array_map(fn($r) => $r['h'] + $r['a'], $rows)); $tot = $hono + $ann; ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage">Pilotage</a> · trésorerie</div>
    <h1>Prévision d'encaissement</h1>
    <div class="date">Exercice <?= e(str_replace('-', '–', $ex)) ?> (1er avril – 31 mars) · honoraires de gestion répartis par mois · honoraires annexes projetés au rythme actuel (<?= e($eur($annDone)) ?> déjà facturés)</div>
  </div>
</header>
<div class="ag-kpis">
  <div class="ag-kpi" style="cursor:default"><span class="n"><?= e($eur($tot)) ?></span><span class="l">attendus sur l'exercice</span></div>
  <div class="ag-kpi" style="cursor:default"><span class="n"><?= e($eur($tot / 12)) ?></span><span class="l">par mois en moyenne</span></div>
  <div class="ag-kpi" style="cursor:default"><span class="n"><?= e($eur($hono)) ?></span><span class="l">honoraires de gestion</span></div>
  <div class="ag-kpi" style="cursor:default"><span class="n"><?= e($eur($ann)) ?></span><span class="l">honoraires annexes (projection)</span></div>
</div>
<div class="panel">
  <div class="cs-legend"><span><i class="h"></i>Honoraires de gestion</span><span><i class="a"></i>Honoraires annexes</span></div>
  <div class="cs-chart">
    <?php foreach ($rows as $r): ?>
    <div class="cs-col<?= $r['past'] ? ' past' : '' ?>" title="<?= e(ucfirst(MOIS_LONG[(int)date('n', $r['m']) - 1]) . ' : ' . $eur($r['h'] + $r['a']) . ' · cumul ' . $eur($r['cum'])) ?>">
      <span class="cs-v"><?= number_format(($r['h'] + $r['a']) / 1000, 0, ',', ' ') ?> k€</span>
      <span class="cs-bar"><i class="h" style="height:<?= round($r['h'] / $mx * 100, 1) ?>%"></i><i class="a" style="height:<?= round($r['a'] / $mx * 100, 1) ?>%"></i></span>
      <span class="cs-m"><?= e(MOIS[(int)date('n', $r['m']) - 1]) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="muted small">Estimation indicative : la facturation réelle dépend de l'échéancier des appels d'honoraires de chaque immeuble. Mois écoulés en clair.</p>
</div>
