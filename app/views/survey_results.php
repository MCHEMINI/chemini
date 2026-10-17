<?php
$n = count($done); $mx = max(1, ...array_map(fn($t) => $t['score'], $tools ?: [['score' => 1]]));
$count = function (string $k) use ($done) { $c = []; foreach ($done as $r) foreach ((array)($r['d'][$k] ?? []) as $v) $c[$v] = ($c[$v] ?? 0) + 1; arsort($c); return $c; };
$texts = function (string $k) use ($done) { $o = []; foreach ($done as $r) { $t = $r['d'][$k] ?? ''; if (is_string($t) && $t !== '') $o[] = [$t, $r['d']['role'][0] ?? '']; foreach (['o_' . $k] as $ok) if (!empty($r['d'][$ok])) $o[] = [$r['d'][$ok], $r['d']['role'][0] ?? '']; } return $o; };
$eff = ['', 'rapide', 'moyen', 'important'];
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · enquête</div>
    <h1>Enquête des équipes</h1>
    <div class="date"><?= $n ?> questionnaire<?= $n > 1 ? 's' : '' ?> reçu<?= $n > 1 ? 's' : '' ?> sur <?= (int)$staff ?> collaborateurs · <?= count($pending) ?> en attente · les outils à développer se classent automatiquement selon les réponses</div>
  </div>
  <div class="head-actions"><a class="btn btn-ghost" href="/enquete">Voir le questionnaire</a><button type="button" class="btn btn-ghost" data-print>Imprimer / partager</button></div>
</header>

<div class="sv-part">
  <div class="sv-gauge"><i style="width:<?= $staff ? round($n / $staff * 100) : 0 ?>%"></i></div>
  <span><b><?= $staff ? round($n / $staff * 100) : 0 ?> %</b> de participation</span>
  <?php if ($pending): ?><details class="sv-pend"><summary><?= count($pending) ?> en attente</summary><p class="muted small"><?= e(implode(', ', array_map(fn($k) => $people[$k]['name'] ?? $k, $pending))) ?></p><p class="muted small">Chacun a la tâche « <?= e(SURVEY_TASK) ?> » dans Collaborateurs. Les collaborateurs sans compte Dashboard doivent d'abord être invités (Sécurité › Équipe).</p></details><?php endif; ?>
</div>

<section class="pl-sec">
  <div class="sec-h"><h2>Outils à développer</h2><span class="muted small">Classés par nombre de réponses concordantes · effort estimé</span></div>
  <?php if (!$n): ?><div class="empty"><strong>Pas encore de réponse.</strong>Le classement s'affichera dès les premiers questionnaires. En attendant, voici les outils candidats.</div><?php endif; ?>
  <ol class="sv-tools">
    <?php foreach ($tools as $i => $t): ?>
    <li>
      <span class="sv-rank"><?= $i + 1 ?></span>
      <div class="sv-tb"><b><?= e($t['name']) ?></b><p><?= e($t['desc']) ?></p>
        <span class="sv-meta"><?= $t['people'] ?> personne<?= $t['people'] > 1 ? 's' : '' ?> concernée<?= $t['people'] > 1 ? 's' : '' ?><?= $t['roles'] ? ' · ' . e(implode(', ', $t['roles'])) : '' ?> · effort <?= e($eff[$t['effort']]) ?></span></div>
      <span class="sv-score"><i style="width:<?= round($t['score'] / $mx * 100) ?>%"></i></span>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<?php if ($n): ?>
<section class="pl-sec">
  <div class="sec-h"><h2>Détail des réponses</h2></div>
  <div class="ac-grid">
    <?php foreach (SURVEY as [$g, $qs]) foreach ($qs as $k => [$label, $type]): if ($type === 'text') continue; $c = $count($k); if (!$c) continue; $m = max($c); ?>
    <article class="panel ac-card"><h3 class="sv-q"><?= e($label) ?></h3>
      <ul class="sv-bars"><?php foreach (array_slice($c, 0, 8, true) as $o => $v): ?><li><span><?= e($o) ?></span><i style="width:<?= round($v / $m * 100) ?>%"></i><b><?= $v ?></b></li><?php endforeach; ?></ul>
      <?php if ($o2 = $texts($k)): ?><p class="muted small">Autres : <?= e(implode(' · ', array_column($o2, 0))) ?></p><?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<section class="pl-sec">
  <div class="sec-h"><h2>Ce qu'ils disent</h2></div>
  <div class="ac-grid">
    <?php foreach (['recurrent_long' => 'La tâche qui prend le plus de temps', 'immopen_pain' => 'Ce qui est long dans ImmOpen', 'outil' => 'L\'outil rêvé', 'remarque' => 'Remarques et idées'] as $k => $l): $t = $texts($k); if (!$t) continue; ?>
    <article class="panel ac-card"><h3><?= e($l) ?></h3><ul class="sv-quotes"><?php foreach ($t as [$q, $role]): ?><li>« <?= e($q) ?> »<?php if ($role): ?><small><?= e($role) ?></small><?php endif; ?></li><?php endforeach; ?></ul></article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
