<?php
$d = $t['d'];
$list = fn($v) => array_values(array_filter(is_array($v) ? $v : [], fn($x) => $x !== null && $x !== ''));
$url = fn($s) => preg_match('#^https://#', (string)$s) ? (string)$s : null;
$txt = function ($v): string { return is_array($v) ? implode(' · ', array_map('strval', array_filter($v, 'is_scalar'))) : (string)$v; };
?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/voyages">Voyages</a> · <?= e($t['duration']) ?></div>
    <h1><?= e($t['destination']) ?></h1>
    <?php if (!empty($d['summary'])): ?><div class="date" style="max-width:70ch"><?= e($d['summary']) ?></div><?php endif; ?>
  </div>
</header>

<div class="grid">
  <?php if (!empty($d['best_period'])): ?>
  <section class="col-6 panel stack">
    <div class="sec-h flat"><h2>Meilleure période</h2></div>
    <p class="trip-big"><?= e($d['best_period']['when'] ?? '') ?></p>
    <?php if (!empty($d['best_period']['why'])): ?><p class="muted"><?= e($txt($d['best_period']['why'])) ?></p><?php endif; ?>
    <?php if (!empty($d['best_period']['avoid'])): ?><p class="small"><strong>À éviter :</strong> <?= e($txt($d['best_period']['avoid'])) ?></p><?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($w = $list($d['weather'] ?? [])): ?>
  <section class="col-6 panel stack">
    <div class="sec-h flat"><h2>Météo</h2></div>
    <div class="tbl"><table class="sec-table"><thead><tr><th>Période</th><th>Températures</th><th>Pluie</th><th>À savoir</th></tr></thead><tbody>
      <?php foreach ($w as $r): ?><tr><td><strong><?= e($r['period'] ?? '') ?></strong></td><td class="mono"><?= e($r['temp'] ?? '') ?></td><td><?= e($r['rain'] ?? '') ?></td><td class="muted"><?= e($r['note'] ?? '') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <?php endif; ?>

  <?php if ($f = $list($d['flights'] ?? [])): ?>
  <section class="col-12">
    <div class="sec-h"><h2>Vols</h2><span class="muted small">Air France, ou Transavia en court-courrier. Prix relevés à titre indicatif.</span></div>
    <div class="tbl"><table class="sec-table"><thead><tr><th>Compagnie</th><th>Trajet</th><th>Dates</th><th>Durée</th><th>Prix</th><th>Remarque</th><th></th></tr></thead><tbody>
      <?php foreach ($f as $r): ?><tr>
        <td><strong><?= e($r['airline'] ?? '') ?></strong></td><td><?= e($r['route'] ?? '') ?></td><td><?= e($r['dates'] ?? '') ?></td>
        <td class="mono"><?= e($r['duration'] ?? '') ?></td><td class="mono"><strong><?= e($r['price'] ?? '') ?></strong></td><td class="muted"><?= e($r['note'] ?? '') ?></td>
        <td><?php if ($l = $url($r['link'] ?? '')): ?><a class="btn-link" href="<?= e($l) ?>" target="_blank" rel="noopener">Réserver</a><?php endif; ?></td>
      </tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <?php endif; ?>

  <?php if ($days = $list($d['days'] ?? [])): ?>
  <section class="col-8">
    <div class="sec-h"><h2>Programme jour par jour</h2></div>
    <ol class="trip-days">
      <?php foreach ($days as $i => $day): ?>
      <li><div class="cal"><b><?= (int)($day['day'] ?? $i + 1) ?></b><span>jour</span></div>
        <div><div class="t"><?= e($day['title'] ?? '') ?></div>
          <ul><?php foreach ($list($day['items'] ?? []) as $it): ?><li><?= e($txt($it)) ?></li><?php endforeach; ?></ul></div></li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

  <section class="col-4 stack" style="gap:24px">
    <?php if ($m = $list($d['musts'] ?? [])): ?>
    <div class="panel stack"><div class="sec-h flat"><h2>Immanquables</h2></div><ul class="trip-ul"><?php foreach ($m as $x): ?><li><?= e($txt($x)) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($b = $list($d['budget'] ?? [])): ?>
    <div class="panel stack"><div class="sec-h flat"><h2>Budget indicatif</h2></div>
      <ul class="docs"><?php foreach ($b as $r): ?><li><span><?= e($r['label'] ?? '') ?></span><span class="mono"><?= e($r['amount'] ?? '') ?></span></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
  </section>

  <?php if ($bf = $list($d['before'] ?? [])): ?>
  <section class="col-12">
    <div class="sec-h"><h2>À prévoir avant de partir</h2></div>
    <div class="trip-before">
      <?php foreach ($bf as $g): ?><div class="panel"><h3><?= e($g['title'] ?? '') ?></h3><ul class="trip-ul"><?php foreach ($list($g['items'] ?? []) as $x): ?><li><?= e($txt($x)) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($tips = $list($d['tips'] ?? [])): ?>
  <section class="col-12 panel stack"><div class="sec-h flat"><h2>Bon à savoir</h2></div><ul class="trip-ul cols"><?php foreach ($tips as $x): ?><li><?= e($txt($x)) ?></li><?php endforeach; ?></ul></section>
  <?php endif; ?>

  <?php if ($src = $list($d['sources'] ?? [])): ?>
  <section class="col-12"><p class="muted small">Sources :
    <?php foreach ($src as $i => $s): if (!$l = $url($s['url'] ?? '')) continue; ?><?= $i ? ' · ' : '' ?><a href="<?= e($l) ?>" target="_blank" rel="noopener"><?= e($s['title'] ?? $l) ?></a><?php endforeach; ?></p></section>
  <?php endif; ?>
</div>
