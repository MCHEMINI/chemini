<?php
$kinds = ['' => 'Tout', 'ia' => 'Tâches IA', 'evo' => 'Évolutions de la plateforme'] + ($dir ? ['hono' => 'Honoraires'] : []);
$spaceLbl = ['all' => 'Tous', 'perso' => 'Perso', 'famille' => 'Famille', 'direction' => 'Direction', 'gid' => 'Équipe'];
$byMonth = [];
foreach ($ev as $x) $byMonth[date('Y-m', $x['at'])][date('Y-m-d', $x['at'])][] = $x;
$short = fn(string $s, int $n) => mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n)) . '…' : $s;
?>
<header class="head">
  <div>
    <div class="eyebrow">Perso · selon vos espaces</div>
    <h1>Versioning</h1>
    <div class="date">Tout ce qui a été fait depuis le lancement de la plateforme : Tâches IA traitées, évolutions de l'outil<?= $dir ? ', mises à jour des honoraires' : '' ?>. Mis à jour en direct et chaque matin à 8 h. Vos tâches Perso ne sont visibles que par vous ; celles de Famille et de Direction par les membres de ces espaces.</div>
  </div>
</header>

<nav class="vs-filters" aria-label="Filtrer">
  <?php foreach ($kinds as $k => $l): ?>
  <a href="/versioning<?= $k ? '?f=' . $k : '' ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= e($l) ?> <span><?= (int)($count[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if (!$ev): ?>
  <div class="empty"><strong>Rien pour l'instant.</strong>Les tâches traitées et les évolutions apparaîtront ici.</div>
<?php endif; ?>

<?php foreach ($byMonth as $ym => $days): $ts = strtotime($ym . '-01'); $nM = array_sum(array_map('count', $days)); ?>
<section class="vs-month">
  <div class="sec-h"><h2><?= e(ucfirst(MOIS_LONG[(int)date('n', $ts) - 1]) . ' ' . date('Y', $ts)) ?></h2><span class="muted small"><?= $nM ?> élément<?= $nM > 1 ? 's' : '' ?></span></div>
  <?php foreach ($days as $d => $list): $dt = strtotime($d); $n = count($list); ?>
  <article class="vs-day panel">
    <header><b><?= (int)date('j', $dt) ?></b><span><?= e(ucfirst(JOURS[(int)date('w', $dt)])) ?><small><?= $n ?> élément<?= $n > 1 ? 's' : '' ?></small></span></header>
    <ul data-more-list>
      <?php foreach ($list as $i => $x): ?>
      <li class="vs-<?= $x['kind'] ?>"<?= $i >= 10 ? ' hidden data-more-x' : '' ?>>
        <time class="mono"><?= date('H:i', $x['at']) ?></time>
        <div>
          <div class="vs-tags"><span class="vs-k"><?= $x['kind'] === 'ia' ? 'Tâche IA' : ($x['kind'] === 'evo' ? 'Évolution' : 'Honoraires') ?></span><span class="chip ia-sp"><?= e($spaceLbl[$x['space']] ?? $x['space']) ?></span><?php if (!empty($x['who'])): ?><span class="muted small">par <?= e($x['who']) ?></span><?php endif; ?></div>
          <?php if ($x['kind'] === 'ia'): $c = $x['c']; ?>
            <div class="vs-t"><?= e($short((string)$c['body'], 240)) ?></div>
            <?php if (!empty($c['result'])): ?><details class="vs-res"><summary>Résultat</summary><div><?= nl2br(e((string)$c['result'])) ?></div></details><?php endif; ?>
          <?php elseif ($x['kind'] === 'evo'): ?>
            <div class="vs-t"><?= e($x['t']) ?></div><?php if ($x['x']): ?><div class="vs-x"><?= e($x['x']) ?></div><?php endif; ?>
          <?php else: ?>
            <div class="vs-t"><a href="/honoraires/modifications"><?= $x['n'] ?> modification<?= $x['n'] > 1 ? 's' : '' ?> du tableau des honoraires</a></div>
            <div class="vs-x"><?= $x['auto'] ? (int)$x['auto'] . ' mise' . ($x['auto'] > 1 ? 's' : '') . ' à jour automatique' . ($x['auto'] > 1 ? 's' : '') . ' (PV d\'AG, ImmOpen)' : 'Saisies manuelles' ?></div>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php if ($n > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= $n - 10 ?>) ↓</button><?php endif; ?>
  </article>
  <?php endforeach; ?>
</section>
<?php endforeach; ?>
