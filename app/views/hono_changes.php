<?php
/* Historique complet des modifications du tableau des honoraires, par mois puis par jour. */
$months = [];
foreach ($changes as $c) {
    $mk = date('Y-m', $c['at']); $dk = date('Y-m-d', $c['at']);
    $months[$mk][$dk][] = $c;
}
$devIco = function (string $d): string {
    if (!isset(HONO_DEVICES[$d])) return '';
    $svg = in_array($d, ['iphone', 'android'], true) ? '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18.5h2"/>'
        : (in_array($d, ['ipad', 'tablette'], true) ? '<rect x="4" y="2" width="16" height="20" rx="2.5"/><path d="M11 18.5h2"/>' : '<rect x="2.5" y="4" width="19" height="12.5" rx="1.5"/><path d="M8 20.5h8M12 16.5v4"/>');
    $t = 'Saisi depuis ' . ($d === 'tablette' ? 'une ' : 'un ') . HONO_DEVICES[$d];
    return '<span class="hc-dev" title="' . e($t) . '" aria-label="' . e($t) . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">' . $svg . '</svg></span>';
};
$fmtMonth = fn(string $k) => ucfirst(MOIS_LONG[(int)substr($k, 5, 2) - 1]) . ' ' . substr($k, 0, 4);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · Honoraires</div>
    <h1>Modifications</h1>
    <div class="date">Historique complet des modifications apportées au tableau des honoraires · saisies enregistrées en direct, mise à jour automatique chaque matin à 8 h · <?= count($changes) ?> modification<?= count($changes) > 1 ? 's' : '' ?></div>
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
        <span class="hc-who <?= $c['source'] === 'auto' ? 'auto' : '' ?>"><?= $devIco($c['device'] ?? '') ?><?= e($c['user']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
