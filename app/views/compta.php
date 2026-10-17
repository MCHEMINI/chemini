<?php
$fmt = fn($v) => number_format((float)$v, 2, ',', ' ');
$fmt0 = fn($v) => number_format((float)$v, 0, ',', ' ');
$anom = $d['anomalies'] ?? [];
$fact = $d['factures'] ?? [];
$openA = array_filter($anom, fn($a) => !isset($done[$a['k']]));
$openF = array_filter($fact, fn($f) => !isset($done[$f['k']]));
$sev = ['crit' => 'Bloquant', 'warn' => 'À corriger', 'ok' => 'À vérifier'];
$sevO = ['crit' => 0, 'warn' => 1, 'ok' => 2];
$nm = fn($x) => trim((string)$x) !== '' ? trim((string)$x) : 'Non attribué';
// Une fiche par comptable : chiffres du contrôle + éléments ouverts
$C = [];
foreach ($d['by_comptable'] ?? [] as $c) $C[$nm($c['name'])] = ['info' => $c, 'a' => [], 'f' => []];
foreach ($anom as $a) { $C[$nm($a['comptable'])]['a'][] = $a; }
foreach ($fact as $f) { $C[$nm($f['comptable'])]['f'][] = $f; }
foreach ($C as $k => &$c) {
    $c['info'] = ($c['info'] ?? []) + ['immeubles' => 0, 'ecritures' => 0, 'note' => ''];
    usort($c['a'], fn($x, $y) => [isset($done[$x['k']]), $sevO[$x['severity']] ?? 1] <=> [isset($done[$y['k']]), $sevO[$y['severity']] ?? 1]);
    usort($c['f'], fn($x, $y) => [isset($done[$x['k']]), -(float)$x['montant']] <=> [isset($done[$y['k']]), -(float)$y['montant']]);
    $c['oa'] = count(array_filter($c['a'], fn($x) => !isset($done[$x['k']])));
    $c['crit'] = count(array_filter($c['a'], fn($x) => !isset($done[$x['k']]) && ($x['severity'] ?? '') === 'crit'));
    $c['of'] = count(array_filter($c['f'], fn($x) => !isset($done[$x['k']])));
    $c['mf'] = array_sum(array_map(fn($x) => isset($done[$x['k']]) || !is_numeric($x['montant']) ? 0 : (float)$x['montant'], $c['f']));
}
unset($c);
uksort($C, fn($x, $y) => [$C[$y]['oa'] + $C[$y]['of'], $x] <=> [$C[$x]['oa'] + $C[$x]['of'], $y]);
$maxLoad = max(1, 0, ...array_values(array_map(fn($c) => $c["oa"] + $c["of"], $C)));
$ini = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: ''));
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · contrôle ImmOpen</div>
    <h1>Comptabilité</h1>
    <div class="date"><?= $run ? 'Dernière vérification le ' . e(date('d/m/Y à H:i', (int)$run['created_at'])) . ($d['period'] ?? '' ? ' · ' . e($d['period']) : '') . ' · contrôle automatique chaque vendredi à 19 h' : 'Vérification automatique chaque vendredi à 19 h' ?></div>
  </div>
</header>

<?php if (!$run): ?>
<div class="empty"><strong>Première vérification en attente.</strong>Chaque vendredi à 19 h, les écritures ImmOpen sont contrôlées : doublons, écritures déséquilibrées, imputations incohérentes, factures sans pièce. Le résultat s'affiche ici, comptable par comptable.</div>
<?php else: ?>

<section>
  <div class="sec-h"><h2>Par comptable</h2><span class="muted small">Cliquez sur un comptable pour afficher son détail</span></div>
  <div class="cp-cards" role="tablist" aria-label="Comptables">
    <?php foreach ($C as $name => $c): $load = $c['oa'] + $c['of']; ?>
    <button type="button" role="tab" class="cp-card" data-cp-tab="<?= e($name) ?>" aria-selected="false">
      <span class="cp-ch"><span class="ag-av"><?= e($ini($name)) ?></span><span class="cp-cn"><b><?= e($name) ?></b><small><?= $fmt0($c['info']['immeubles']) ?> immeubles · <?= $fmt0($c['info']['ecritures']) ?> écritures</small></span></span>
      <span class="cp-nums">
        <span class="<?= $c['oa'] ? 'crit' : 'ok' ?>"><b><?= $fmt0($c['oa']) ?></b>incohérences<?= $c['crit'] ? '<em>dont ' . $fmt0($c['crit']) . ' bloquantes</em>' : '' ?></span>
        <span class="<?= $c['of'] ? 'warn' : 'ok' ?>"><b><?= $fmt0($c['of']) ?></b>factures manquantes<?= $c['mf'] ? '<em>' . $fmt0($c['mf']) . ' €</em>' : '' ?></span>
      </span>
      <span class="cp-load" title="Charge relative"><i style="width:<?= round($load / $maxLoad * 100) ?>%"></i></span>
      <?php if ($c['info']['note']): ?><span class="cp-note"><?= e($c['info']['note']) ?></span><?php endif; ?>
    </button>
    <?php endforeach; ?>
  </div>
</section>

<?php foreach ($C as $name => $c): ?>
<section class="cp-detail" data-cp-panel="<?= e($name) ?>" role="tabpanel" hidden>
  <header class="cp-dh">
    <div><div class="eyebrow">Détail du comptable</div><h2><?= e($name) ?></h2></div>
    <?php if (!empty($emails[$name])): ?><a class="btn btn-ghost" href="mailto:<?= e($emails[$name]) ?>"><?= e($emails[$name]) ?></a><?php endif; ?>
  </header>
  <div class="cp-cols">
    <div class="cp-box">
      <div class="sec-h flat"><h3>Incohérences dans les écritures</h3><span class="chip <?= $c['oa'] ? 'crit' : 'ok' ?>"><?= $c['oa'] ?> à traiter</span></div>
      <?php if (!$c['a']): ?><p class="muted small">Aucune incohérence détectée.</p><?php else: ?>
      <ul class="cp-list" data-more-list>
        <?php foreach ($c['a'] as $i => $a): $isDone = isset($done[$a['k']]); $id = 'a' . substr($a['k'], 0, 12); ?>
        <li class="<?= $isDone ? 'done' : '' ?>"<?= $i >= 10 ? ' hidden data-more-x' : '' ?>>
          <input type="checkbox" id="<?= e($id) ?>" data-api="compta/done" data-k="<?= e($a['k']) ?>" <?= $isDone ? 'checked' : '' ?> aria-label="Corrigée">
          <label for="<?= e($id) ?>">
            <span class="cp-t"><span class="chip <?= e($a['severity'] ?: 'warn') ?>"><?= e($sev[$a['severity']] ?? 'À corriger') ?></span> <?= e($a['type']) ?></span>
            <span class="cp-d"><?php if ($a['code']): ?><span class="mono"><?= e($a['code']) ?></span> <?php endif; ?><?= e($a['immeuble']) ?><?= trim($a['piece'] . $a['compte']) !== '' ? ' · <span class="mono">' . e(trim($a['piece'] . ($a['piece'] && $a['compte'] ? ' · ' : '') . $a['compte'])) . '</span>' : '' ?></span>
            <?php if ($a['detail']): ?><span class="cp-why"><?= e($a['detail']) ?></span><?php endif; ?>
          </label>
          <span class="cp-m"><b class="mono"><?= $a['montant'] !== '' ? $fmt($a['montant']) . ' €' : '' ?></b><small class="mono"><?= e($a['date']) ?></small></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($c['a']) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($c['a']) - 10 ?>) ↓</button><?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="cp-box">
      <div class="sec-h flat"><h3>Factures manquantes</h3><span class="chip <?= $c['of'] ? 'warn' : 'ok' ?>"><?= $c['of'] ?> à saisir</span></div>
      <?php if (!$c['f']): ?><p class="muted small">Aucune facture manquante.</p><?php else: ?>
      <ul class="cp-list" data-more-list>
        <?php foreach ($c['f'] as $i => $f): $isDone = isset($done[$f['k']]); $id = 'f' . substr($f['k'], 0, 12); ?>
        <li class="<?= $isDone ? 'done' : '' ?>"<?= $i >= 10 ? ' hidden data-more-x' : '' ?>>
          <input type="checkbox" id="<?= e($id) ?>" data-api="compta/done" data-k="<?= e($f['k']) ?>" <?= $isDone ? 'checked' : '' ?> aria-label="Saisie">
          <label for="<?= e($id) ?>">
            <span class="cp-t"><?= e($f['fournisseur'] ?: 'Fournisseur inconnu') ?><?= $f['reference'] ? ' <span class="muted">· ' . e($f['reference']) . '</span>' : '' ?></span>
            <span class="cp-d"><?= $f['code'] ? '<span class="mono">' . e($f['code']) . '</span> ' : '' ?><?= e($f['immeuble']) ?><?= $f['detail'] ? ' · ' . e($f['detail']) : '' ?></span>
            <?php if ($f['source']): ?><span class="cp-why"><?= e($f['source']) ?></span><?php endif; ?>
          </label>
          <span class="cp-m"><b class="mono"><?= $f['montant'] !== '' ? $fmt($f['montant']) . ' €' : '' ?></b><small class="mono"><?= e($f['date']) ?></small></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if (count($c['f']) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($c['f']) - 10 ?>) ↓</button><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<section class="cp-general">
  <div class="sec-h"><h2>Informations générales</h2></div>
  <div class="summary">
    <div><div class="n <?= count($openA) ? 'crit' : '' ?>"><?= $fmt0(count($openA)) ?></div><div class="l">Incohérences à traiter</div></div>
    <div><div class="n <?= count($openF) ? 'warn' : '' ?>"><?= $fmt0(count($openF)) ?></div><div class="l">Factures manquantes</div></div>
    <div><div class="n"><?= $fmt0(array_sum(array_filter(array_column($openF, 'montant'), 'is_numeric'))) ?> €</div><div class="l">Montant des factures manquantes</div></div>
    <div><div class="n"><?= count($C) ?></div><div class="l">Comptables contrôlés</div></div>
  </div>
  <div class="grid">
    <?php if (!empty($d['summary'])): ?><div class="col-7 panel"><h3 class="cp-h3">Synthèse du contrôle</h3><p class="cp-sum"><?= nl2br(e($d['summary'])) ?></p><?php if (!empty($d['source'])): ?><p class="muted small">Source : <?= e($d['source']) ?></p><?php endif; ?></div><?php endif; ?>
    <div class="<?= !empty($d['summary']) ? 'col-5' : 'col-12' ?> panel"><h3 class="cp-h3">Annuaire des comptables</h3>
      <ul class="cp-dir"><?php foreach ($comptables as $c): ?><li><span><b><?= e($c['name']) ?></b><?php if (!empty($emails[$c['name']])): ?><a class="small" href="mailto:<?= e($emails[$c['name']]) ?>"><?= e($emails[$c['name']]) ?></a><?php endif; ?></span><span class="mono muted"><?= (int)$c['n'] ?> imm.</span></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>
<?php endif; ?>
