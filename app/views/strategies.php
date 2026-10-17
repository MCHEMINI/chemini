<?php
$eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €';
$all = max(1, $tot['green'] + $tot['orange'] + $tot['red']);
$dots = fn(int $n) => str_repeat('●', $n) . '<span class="st-off">' . str_repeat('●', 3 - $n) . '</span>';
$card = function (array $l) use ($eur, $dots, $choices) { ob_start(); $on = !empty($choices[$l['k']]); ?>
  <article class="st-card <?= $on ? 'on' : '' ?>">
    <div class="st-ico" aria-hidden="true"><?= $l['ico'] ?></div>
    <h3><?= e($l['t']) ?></h3>
    <p><?= e($l['d']) ?></p>
    <dl class="st-meta">
      <div><dt>Gain estimé</dt><dd class="st-gain"><?= $l['gain'] !== null ? '+' . e($eur($l['gain'])) . '<small> TTC / an</small>' : '<small>' . e($l['hyp']) . '</small>' ?></dd></div>
      <div><dt>Immeubles</dt><dd><?= (int)$l['n'] ?></dd></div>
      <div><dt>Effort</dt><dd class="st-dots" title="Effort <?= (int)$l['effort'] ?>/3"><?= $dots((int)$l['effort']) ?></dd></div>
      <div><dt>Délai</dt><dd><?= e($l['delai']) ?></dd></div>
    </dl>
    <?php if ($l['gain'] !== null): ?><div class="st-hyp">Hypothèse : <?= e($l['hyp']) ?></div><?php endif; ?>
    <label class="st-pick"><input type="checkbox" data-strat="<?= e($l['k']) ?>" <?= $on ? 'checked' : '' ?>> À lancer</label>
  </article>
<?php return ob_get_clean(); };
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · Stratégies</div>
    <h1>Rentabilité et leviers</h1>
    <div class="date">Où gagner du temps et de la marge, en un coup d'œil · modèle de coût : <?= number_format(COST_FIXED, 0, ',', ' ') ?> € par immeuble + <?= COST_LOT ?> € par lot + <?= COST_HOUR ?> € par heure incluse (HT)</div>
  </div>
</header>

<section class="st-hero">
  <div class="st-gauge">
    <div class="st-bar"><i class="g" style="width:<?= round($tot['green'] / $all * 100, 1) ?>%"></i><i class="o" style="width:<?= round($tot['orange'] / $all * 100, 1) ?>%"></i><i class="r" style="width:<?= round($tot['red'] / $all * 100, 1) ?>%"></i></div>
    <div class="st-tiles">
      <div class="g"><b><?= (int)$tot['green'] ?></b><span>rentables</span><small><?= number_format($lots['green'], 0, ',', ' ') ?> lots</small></div>
      <div class="o"><b><?= (int)$tot['orange'] ?></b><span>fragiles</span><small><?= number_format($lots['orange'], 0, ',', ' ') ?> lots</small></div>
      <div class="r"><b><?= (int)$tot['red'] ?></b><span>non rentables</span><small><?= number_format($lots['red'], 0, ',', ' ') ?> lots</small></div>
      <div class="m"><b><?= e($eur($gapRed)) ?></b><span>manque à gagner</span><small>pour remettre les non rentables au vert</small></div>
    </div>
  </div>
</section>

<section class="st-sec">
  <div class="sec-h"><h2>Immeubles non rentables et fragiles</h2><span class="muted small">Cochez « À lancer » : vos choix sont enregistrés pour la Direction</span></div>
  <div class="st-grid"><?php foreach ($L['weak'] as $l) echo $card($l); ?></div>

  <?php if ($red): ?>
  <details class="st-list" open>
    <summary>Les <?= count($red) ?> immeubles non rentables, du plus grand écart au plus petit</summary>
    <div class="st-chips">
      <?php foreach ($red as $r): ?>
      <a class="st-chip" href="/immeubles/<?= e($r['code']) ?>" title="<?= e($r['imm']) ?> · marge estimée <?= round($r['margin'] * 100) ?> %">
        <span class="st-c-code mono"><?= e($r['code']) ?></span><span class="st-c-name"><?= e($r['imm']) ?></span>
        <span class="st-c-m"><?= round($r['margin'] * 100) ?> %</span><span class="st-c-gap">+<?= e($eur($r['gap'])) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </details>
  <?php endif; ?>
</section>

<section class="st-sec">
  <div class="sec-h"><h2>Pour l'ensemble des <?= (int)$nAll ?> immeubles</h2><span class="muted small">Médiane du cabinet : <?= e($eur($med)) ?> TTC par lot</span></div>
  <div class="st-grid"><?php foreach ($L['all'] as $l) echo $card($l); ?></div>
</section>
<p class="muted small">Gains indicatifs, calculés à partir du tableau des honoraires et des hypothèses affichées sur chaque carte. À affiner avec les gestionnaires.</p>
