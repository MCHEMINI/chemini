<?php
$fd = fn(?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
$st = ['planifiee' => ['Planifiée', 'ok'], 'aplanifier' => ['À planifier', 'warn'], 'tenue' => ['Tenue', 'neutral']];
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · source ImmOpen<?= $date ? ' · relevé du ' . e($fd($date)) : '' ?></div>
    <h1>Planning des AG</h1>
    <div class="date"><?= (int)$total ?> immeubles · par gestionnaire, avec le nombre de lots et la rentabilité</div>
  </div>
  <div class="head-actions">
    <label for="agS" class="sr">Statut</label>
    <select id="agS" class="input"><option value="">Tous les statuts</option><option value="planifiee">Planifiées</option><option value="aplanifier">À planifier</option><option value="tenue">Tenues</option><option value="alerte">Avec alerte</option></select>
  </div>
</header>

<?php if (!$by): ?>
  <div class="empty"><strong>Planning non disponible.</strong>Le planning est relevé dans ImmOpen (A.G. › Planning AG).</div>
<?php else: ?>
<div class="summary">
  <div><div class="n"><?= (int)$alerts['mois'] ?></div><div class="l">AG dans les 30 jours</div></div>
  <div><div class="n <?= $alerts['mandat'] ? 'crit' : '' ?>"><?= (int)$alerts['mandat'] ?></div><div class="l">Mandats expirés ou à renouveler</div></div>
  <div><div class="n <?= $alerts['convoc'] ? 'crit' : '' ?>"><?= (int)$alerts['convoc'] ?></div><div class="l">Convocations en retard</div></div>
  <div><div class="n"><?= (int)$alerts['aplanif'] ?></div><div class="l">AG à planifier</div></div>
</div>
<div class="ag-tabs" role="tablist" aria-label="Gestionnaires">
  <button type="button" role="tab" class="ag-tab" data-g="" aria-selected="true">Tous <span><?= (int)$total ?></span></button>
  <?php foreach ($by as $g => $rows): ?><button type="button" role="tab" class="ag-tab" data-g="<?= e($g) ?>" aria-selected="false"><?= e($g) ?> <span><?= count($rows) ?></span></button><?php endforeach; ?>
</div>
<p class="muted small ag-legend"><span class="dot-fin green"></span> rentable <span class="dot-fin orange"></span> fragile <span class="dot-fin red"></span> non rentable <span class="dot-fin lost"></span> perdu — marge estimée, détail dans la fiche de l'immeuble.</p>

<?php foreach ($by as $g => $rows): $n = count(array_filter($rows, fn($r) => $r['status'] === 'planifiee')); ?>
<section class="ag-group" data-g="<?= e($g) ?>">
  <div class="sec-h"><h2><?= e($g) ?></h2><span class="muted small"><?= count($rows) ?> immeubles · <?= $n ?> AG planifiées · <?= number_format(array_sum(array_map(fn($r) => (int)($r['lots'] ?? 0), $rows)), 0, ',', ' ') ?> lots</span></div>
  <div class="tbl"><table class="sec-table ag-tbl">
    <thead><tr><th>Date de l'AG</th><th></th><th>Immeuble</th><th class="r">Lots</th><th>Comptable</th><th>Fin d'exercice</th><th>Fin de mandat</th><th>Statut</th><th>À surveiller</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $s = $st[$r['status']]; $alert = (bool)array_filter($r['flags'], fn($f) => $f[0] === 'crit'); ?>
      <tr data-href="/immeubles/<?= e($r['code']) ?>" class="row-link<?= $alert ? ' ag-alert' : '' ?>" data-s="<?= e($r['status']) ?>" data-a="<?= $alert ? '1' : '0' ?>">
        <td class="nowrap"><?php if ($r['next']): ?><strong><?= e($jours[(int)date('w', strtotime($r['next']))] . ' ' . $fd($r['next'])) ?></strong><?= $r['ag_heure'] ? ' <span class="muted">' . e($r['ag_heure']) . '</span>' : '' ?><?php elseif ($r['status'] === 'tenue'): ?><span class="muted">Tenue le <?= e($fd($r['last'])) ?></span><?php else: ?><span class="muted">À fixer</span><?php endif; ?></td>
        <td><?php if ($r['color']): ?><span class="dot-fin <?= e($r['color']) ?>" title="<?= e($r['colorLabel'] . ($r['margin'] !== null && $r['color'] !== 'lost' ? ' · marge estimée ' . round($r['margin'] * 100) . ' %' : '')) ?>"></span><?php endif; ?></td>
        <td><a class="row-a" href="/immeubles/<?= e($r['code']) ?>"><span class="mono muted"><?= e($r['code']) ?></span> <strong><?= e($r['nom']) ?></strong></a></td>
        <td class="r mono"><?= $r['lots'] !== null ? (int)$r['lots'] : '—' ?></td>
        <td class="small"><?= e($r['compta']) ?></td>
        <td class="mono small"><?= e($fd($r['fin_ex'])) ?></td>
        <td class="mono small"><?= e($fd($r['fin_mandat'])) ?></td>
        <td><span class="chip <?= e($s[1]) ?>"><?= e($s[0]) ?></span></td>
        <td class="small"><?php foreach ($r['flags'] as $fl): ?><span class="ag-flag <?= e($fl[0]) ?>"><?= e($fl[1]) ?></span><?php endforeach; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endforeach; ?>
<?php endif; ?>
