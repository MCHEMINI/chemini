<?php
$ini = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: ''));
$total = array_sum(array_map(fn($s) => $s['open'], $stat));
?>
<header class="head">
  <div>
    <div class="eyebrow">Équipe · tâches</div>
    <h1>Collaborateurs</h1>
    <div class="date"><?= count($people) ?> personnes par service · <?= $total ?> tâche<?= $total > 1 ? 's' : '' ?> en cours<?= $isDir ? '' : ' (les vôtres et celles que vous avez affectées)' ?></div>
  </div>
</header>

<p class="tm-note"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8h.01M11 12h1v5h1"/></svg>
<?= $isDir ? 'Chaque collaborateur ne voit que ses propres tâches et celles qu\'il a affectées : les tâches affectées par la Direction ne sont pas visibles des autres collaborateurs.' : 'Vous voyez les tâches qui vous sont affectées et celles que vous avez affectées. Ouvrez la fiche d\'un collaborateur pour lui affecter une tâche.' ?></p>

<form class="panel tm-quick" data-api="team/add">
  <label class="sr" for="tq-p">Collaborateur</label>
  <select id="tq-p" name="person" class="tm-in" required>
    <?php foreach ($groups as $g => $list): ?><optgroup label="<?= e($g) ?>"><?php foreach ($list as $p): ?><option value="<?= e($p['key']) ?>"<?= $p['key'] === $me ? ' selected' : '' ?>><?= e($p['name']) ?><?= $p['key'] === $me ? ' (moi)' : '' ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>
  </select>
  <label class="sr" for="tq-t">Tâche</label><input id="tq-t" name="title" class="tm-in tm-grow" maxlength="300" placeholder="Nouvelle tâche…" required>
  <label class="sr" for="tq-d">Échéance</label><input id="tq-d" name="due" type="date" class="tm-in" title="Échéance (facultative)">
  <button class="btn btn-primary">Affecter</button>
  <p class="form-error" hidden></p>
</form>

<?php foreach ($groups as $g => $list): $gOpen = array_sum(array_map(fn($p) => $stat[$p['key']]['open'] ?? 0, $list)); ?>
<section class="tm-group">
  <div class="sec-h"><h2><?= e($g) ?></h2><span class="muted small"><?= count($list) ?> personne<?= count($list) > 1 ? 's' : '' ?> · <?= $gOpen ?> tâche<?= $gOpen > 1 ? 's' : '' ?> en cours</span></div>
  <div class="tm-grid">
    <?php foreach ($list as $p): $s = $stat[$p['key']] ?? ['open' => 0, 'late' => 0, 'today' => 0, 'done' => 0]; $mine = $p['key'] === $me; ?>
    <a class="tm-card<?= $mine ? ' me' : '' ?>" href="/equipe/<?= e($p['key']) ?>">
      <span class="tm-top"><span class="ag-av"><?= e($ini($p['name'])) ?></span><span class="tm-id"><b><?= e($p['name']) ?></b><small><?= e($p['role']) ?><?= $mine ? ' · vous' : '' ?></small></span></span>
      <span class="tm-n"><b><?= (int)$s['open'] ?></b><small>tâche<?= $s['open'] > 1 ? 's' : '' ?> en cours<?= !$isDir && !$mine ? ' affectée' . ($s['open'] > 1 ? 's' : '') . ' par vous' : '' ?></small></span>
      <span class="tm-chips"><?php if ($s['late']): ?><span class="chip crit"><?= (int)$s['late'] ?> en retard</span><?php endif; ?><?php if ($s['today']): ?><span class="chip warn"><?= (int)$s['today'] ?> aujourd'hui</span><?php endif; ?><?php if (!$s['late'] && !$s['today']): ?><span class="muted small"><?= $s['done'] ? (int)$s['done'] . ' faite' . ($s['done'] > 1 ? 's' : '') : 'Aucune échéance' ?></span><?php endif; ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="tm-today">
  <div class="sec-h"><h2>Tâches du jour</h2><span class="muted small">Échéance aujourd'hui ou dépassée · <?= count($todayList) ?></span></div>
  <?php if (!$todayList): ?><div class="empty"><strong>Rien à traiter aujourd'hui.</strong>Les tâches dont l'échéance est aujourd'hui ou dépassée apparaissent ici.</div>
  <?php else: ?>
  <div class="panel"><ul class="tt-list" data-more-list><?php $showWho = true; foreach ($todayList as $i => $t) { $hidden = $i >= 10; include __DIR__ . '/_team_task.php'; } ?></ul>
  <?php if (count($todayList) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($todayList) - 10 ?>) ↓</button><?php endif; ?></div>
  <?php endif; ?>
</section>
