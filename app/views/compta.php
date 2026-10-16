<?php
$fmt = fn($v) => number_format((float)$v, 2, ',', ' ');
$fmt0 = fn($v) => number_format((float)$v, 0, ',', ' ');
$anom = $d['anomalies'] ?? [];
$fact = $d['factures'] ?? [];
$openA = array_filter($anom, fn($a) => !isset($done[$a['k']]));
$openF = array_filter($fact, fn($f) => !isset($done[$f['k']]));
$names = array_values(array_unique(array_merge(array_column($comptables, 'name'), array_column($anom, 'comptable'), array_column($fact, 'comptable'))));
sort($names);
$sev = ['crit' => 'Bloquant', 'warn' => 'À corriger', 'ok' => 'À vérifier'];
$byC = [];
foreach ($fact as $f) $byC[$f['comptable'] ?: 'Non attribué'][] = $f;
ksort($byC);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · contrôle ImmOpen</div>
    <h1>Comptabilité</h1>
    <div class="date"><?= $run ? 'Dernière vérification le ' . e(date('d/m/Y à H:i', (int)$run['created_at'])) . ' · ' . e($d['period'] ?? '') : 'Vérification automatique chaque vendredi à 19 h' ?></div>
  </div>
  <?php if ($names): ?>
  <div class="head-actions">
    <label for="cpF" class="sr">Comptable</label>
    <select id="cpF" class="input"><option value="">Tous les comptables</option><?php foreach ($names as $n): ?><option value="<?= e($n) ?>"><?= e($n) ?></option><?php endforeach; ?></select>
  </div>
  <?php endif; ?>
</header>

<?php if (!$run): ?>
<div class="empty"><strong>Première vérification en attente.</strong>Chaque vendredi à 19 h, les écritures ImmOpen sont contrôlées : doublons, écritures déséquilibrées, imputations incohérentes, factures sans pièce. Le résultat s'affiche ici, comptable par comptable.</div>
<div class="sec-h" style="margin-top:32px"><h2>Comptables (annuaire ImmOpen)</h2></div>
<div class="tbl"><table class="sec-table">
  <thead><tr><th>Comptable</th><th>E-mail</th><th class="r">Immeubles suivis</th></tr></thead>
  <tbody><?php foreach ($comptables as $c): ?><tr><td><strong><?= e($c['name']) ?></strong></td><td class="mono"><?= e($emails[$c['name']] ?? '—') ?></td><td class="r mono"><?= (int)$c['n'] ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<?php else: ?>

<div class="summary">
  <div><div class="n <?= count($openA) ? 'crit' : '' ?>"><?= count($openA) ?></div><div class="l">Incohérences à traiter</div></div>
  <div><div class="n"><?= count($openF) ?></div><div class="l">Factures manquantes</div></div>
  <div><div class="n"><?= $fmt0(array_sum(array_filter(array_column($openF, 'montant'), 'is_numeric'))) ?> €</div><div class="l">Montant des factures manquantes</div></div>
  <div><div class="n"><?= count($d['by_comptable'] ?? []) ?></div><div class="l">Comptables contrôlés</div></div>
</div>
<?php if (!empty($d['summary'])): ?><p class="cp-sum"><?= nl2br(e($d['summary'])) ?></p><?php endif; ?>

<section>
  <div class="sec-h"><h2>Synthèse par comptable</h2></div>
  <div class="tbl"><table class="sec-table cp-tbl">
    <thead><tr><th>Comptable</th><th class="r">Immeubles</th><th class="r">Écritures contrôlées</th><th class="r">Incohérences</th><th class="r">Factures manquantes</th><th class="r">Montant manquant</th><th>Observation</th></tr></thead>
    <tbody>
    <?php foreach ($d['by_comptable'] ?? [] as $c): ?>
      <tr data-cp="<?= e($c['name']) ?>"><td><strong><?= e($c['name']) ?></strong><?php if (!empty($emails[$c['name']])): ?><br><a class="small" href="mailto:<?= e($emails[$c['name']]) ?>"><?= e($emails[$c['name']]) ?></a><?php endif; ?></td>
        <td class="r mono"><?= $fmt0($c['immeubles']) ?></td><td class="r mono"><?= $fmt0($c['ecritures']) ?></td>
        <td class="r mono"><?php if ($c['anomalies']): ?><span class="chip crit"><?= $fmt0($c['anomalies']) ?></span><?php else: ?>0<?php endif; ?></td>
        <td class="r mono"><?php if ($c['factures_manquantes']): ?><span class="chip warn"><?= $fmt0($c['factures_manquantes']) ?></span><?php else: ?>0<?php endif; ?></td>
        <td class="r mono"><?= $fmt0($c['montant_manquant']) ?> €</td><td class="small"><?= e($c['note']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<section style="margin-top:36px">
  <div class="sec-h"><h2>Incohérences dans les écritures</h2><span class="muted small">Cochez une fois corrigée</span></div>
  <?php if (!$anom): ?><div class="empty"><strong>Aucune incohérence détectée.</strong>Les écritures contrôlées sont cohérentes.</div><?php else: ?>
  <div class="tbl"><table class="sec-table cp-tbl">
    <thead><tr><th></th><th>Gravité</th><th>Comptable</th><th>Immeuble</th><th>Date</th><th>Pièce / compte</th><th class="r">Montant</th><th>Incohérence</th></tr></thead>
    <tbody>
    <?php foreach ($anom as $a): $isDone = isset($done[$a['k']]); ?>
      <tr data-cp="<?= e($a['comptable']) ?>" class="<?= $isDone ? 'done' : '' ?>">
        <td><input type="checkbox" data-api="compta/done" data-k="<?= e($a['k']) ?>" <?= $isDone ? 'checked' : '' ?> aria-label="Corrigée"></td>
        <td><span class="chip <?= e($a['severity'] ?: 'warn') ?>"><?= e($sev[$a['severity']] ?? 'À corriger') ?></span></td>
        <td><strong><?= e($a['comptable'] ?: 'Non attribué') ?></strong></td>
        <td><?php if ($a['code']): ?><a href="/immeubles/<?= e($a['code']) ?>"><span class="mono"><?= e($a['code']) ?></span> <?= e($a['immeuble']) ?></a><?php else: ?><?= e($a['immeuble']) ?><?php endif; ?></td>
        <td class="mono nowrap"><?= e($a['date']) ?></td>
        <td class="mono small"><?= e(trim($a['piece'] . ($a['piece'] && $a['compte'] ? ' · ' : '') . $a['compte'])) ?></td>
        <td class="r mono nowrap"><?= $a['montant'] !== '' ? $fmt($a['montant']) . ' €' : '' ?></td>
        <td><strong><?= e($a['type']) ?></strong><?php if ($a['detail']): ?><br><span class="muted small"><?= e($a['detail']) ?></span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section style="margin-top:36px">
  <div class="sec-h"><h2>Factures manquantes par comptable</h2><span class="muted small">Cochez une fois saisie dans ImmOpen</span></div>
  <?php if (!$fact): ?><div class="empty"><strong>Aucune facture manquante.</strong></div><?php else: ?>
  <div class="cp-groups">
    <?php foreach ($byC as $name => $rows): $left = count(array_filter($rows, fn($f) => !isset($done[$f['k']]))); ?>
    <div class="panel cp-group" data-cp="<?= e($name) ?>">
      <div class="sec-h flat"><h3><?= e($name) ?></h3><span class="chip <?= $left ? 'warn' : 'ok' ?>"><?= $left ?> à traiter</span></div>
      <ul class="list mail-todo">
        <?php foreach ($rows as $f): $isDone = isset($done[$f['k']]); ?>
        <li class="<?= $isDone ? 'done' : '' ?>">
          <input type="checkbox" id="f<?= e(substr($f['k'], 0, 12)) ?>" data-api="compta/done" data-k="<?= e($f['k']) ?>" <?= $isDone ? 'checked' : '' ?> aria-label="Saisie">
          <label for="f<?= e(substr($f['k'], 0, 12)) ?>">
            <div class="t"><?= e($f['fournisseur'] ?: 'Fournisseur inconnu') ?><?= $f['reference'] ? ' · ' . e($f['reference']) : '' ?></div>
            <div class="d"><?= $f['code'] ? '<span class="mono">' . e($f['code']) . '</span> ' : '' ?><?= e($f['immeuble']) ?><?= $f['detail'] ? ' · ' . e($f['detail']) : '' ?></div>
            <?php if ($f['source']): ?><div class="why"><?= e($f['source']) ?></div><?php endif; ?>
          </label>
          <div class="meta"><span class="mono"><?= $f['montant'] !== '' ? $fmt($f['montant']) . ' €' : '' ?></span><span><?= e($f['date']) ?></span></div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php if (!empty($d['source'])): ?><p class="muted small" style="margin-top:24px">Source : <?= e($d['source']) ?></p><?php endif; ?>
<?php endif; ?>
