<?php
/* Historique complet des modifications du tableau des honoraires, par mois puis par jour. */
$months = [];
foreach ($changes as $c) {
    $mk = date('Y-m', $c['at']); $dk = date('Y-m-d', $c['at']);
    $months[$mk][$dk][] = $c;
}
$fmtMonth = fn(string $k) => ucfirst(MOIS_LONG[(int)substr($k, 5, 2) - 1]) . ' ' . substr($k, 0, 4);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · Honoraires</div>
    <h1>Modifications</h1>
    <div class="date">Historique complet des modifications apportées au tableau des honoraires · <?= count($changes) ?> modification<?= count($changes) > 1 ? 's' : '' ?></div>
  </div>
  <a class="btn" href="/honoraires">← Retour aux honoraires</a>
</header>

<?php if (!$changes): ?>
<div class="empty"><strong>Aucune modification enregistrée.</strong>Les modifications du tableau des honoraires apparaîtront ici dès la prochaine saisie.</div>
<?php endif; ?>

<?php foreach ($months as $mk => $days): $nm = array_sum(array_map('count', $days)); ?>
<section class="hc-month">
  <div class="sec-h"><h2><?= e($fmtMonth($mk)) ?></h2><span class="muted small"><?= $nm ?> modification<?= $nm > 1 ? 's' : '' ?></span></div>
  <?php foreach ($days as $dk => $list): ?>
  <div class="hc-day">
    <div class="hc-date"><?= e(date_long(strtotime($dk))) ?></div>
    <ul class="hc-list">
      <?php foreach ($list as $c): ?>
      <li>
        <span class="hc-time mono"><?= date('H:i', $c['at']) ?></span>
        <span class="hc-what"><span class="hc-tab"><?= e($c['tab']) ?></span><?php if ($c['ref']): ?> · <strong><?= e($c['ref']) ?></strong><?php endif; ?>
          <span class="hc-field"><?= e($c['field']) ?><?php if ($c['old'] !== '' || $c['new'] !== ''): ?> : <del><?= e($c['old'] !== '' ? $c['old'] : 'vide') ?></del> → <ins><?= e($c['new'] !== '' ? $c['new'] : 'vide') ?></ins><?php endif; ?></span></span>
        <span class="hc-who <?= $c['source'] === 'auto' ? 'auto' : '' ?>"><?= e($c['user']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
