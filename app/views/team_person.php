<?php
$ini = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: ''));
$mine = $p['key'] === $me; $people = team_people(); $showWho = false;
?>
<header class="head tp-head">
  <div class="tp-id">
    <span class="ag-av tp-av"><?= e($ini($p['name'])) ?></span>
    <div>
      <div class="eyebrow"><a href="/organigramme">Collaborateurs</a> · <?= e($p['role']) ?></div>
      <h1><?= e($p['name']) ?><?= $mine ? ' <small class="muted">(vous)</small>' : '' ?></h1>
      <div class="date"><?= count($open) ?> à faire · <?= count($done) ?> faite<?= count($done) > 1 ? 's' : '' ?><?= $p['email'] ? ' · <a href="mailto:' . e($p['email']) . '">' . e($p['email']) . '</a>' : '' ?></div>
    </div>
  </div>
</header>

<?php if ($isDir && !$mine): ?><p class="tm-note"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8h.01M11 12h1v5h1"/></svg>Les tâches que vous affectez ici ne sont visibles que par <?= e($p['name']) ?> et la Direction, pas par les autres collaborateurs.</p>
<?php elseif (!$isDir && !$mine): ?><p class="tm-note"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8h.01M11 12h1v5h1"/></svg>Vous voyez uniquement les tâches que vous avez affectées à <?= e($p['name']) ?>.</p><?php endif; ?>

<form class="panel tp-add" data-api="team/add">
  <input type="hidden" name="person" value="<?= e($p['key']) ?>">
  <label class="sr" for="tp-t">Tâche</label><input id="tp-t" name="title" class="tm-in tm-grow" maxlength="300" placeholder="<?= $mine ? 'Ajouter une tâche pour moi…' : 'Affecter une tâche à ' . e(strtok($p['name'], ' ')) . '…' ?>" required>
  <label class="tp-due"><span>Échéance</span><input name="due" type="date" class="tm-in"></label>
  <button class="btn btn-primary"><?= $mine ? 'Ajouter' : 'Affecter' ?></button>
  <p class="form-error" hidden></p>
</form>

<section class="panel tp-sec">
  <div class="sec-h flat"><h2>À faire</h2><span class="chip <?= $open ? 'warn' : 'ok' ?>"><?= count($open) ?></span></div>
  <?php if (!$open): ?><p class="muted small">Aucune tâche en cours.</p>
  <?php else: ?><ul class="tt-list" data-more-list><?php foreach ($open as $i => $t) { $hidden = $i >= 10; include __DIR__ . '/_team_task.php'; } ?></ul>
  <?php if (count($open) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($open) - 10 ?>) ↓</button><?php endif; ?><?php endif; ?>
</section>

<section class="panel tp-sec">
  <div class="sec-h flat"><h2>Fait</h2><span class="chip ok"><?= count($done) ?></span></div>
  <?php if (!$done): ?><p class="muted small">Les tâches cochées apparaissent ici, barrées, avec la date de la demande et celle du traitement.</p>
  <?php else: ?><ul class="tt-list" data-more-list><?php foreach ($done as $i => $t) { $hidden = $i >= 10; include __DIR__ . '/_team_task.php'; } ?></ul>
  <?php if (count($done) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($done) - 10 ?>) ↓</button><?php endif; ?><?php endif; ?>
</section>
