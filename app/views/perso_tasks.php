<?php $people = team_people(); $showWho = false; ?>
<header class="head">
  <div>
    <div class="eyebrow">Espace personnel</div>
    <h1>Mes tâches</h1>
    <div class="date">Vos tâches personnelles (visibles par vous seul) et celles qui vous sont affectées · aussi dans <a href="/equipe<?= $me ? '/' . e($me) : '' ?>">Collaborateurs</a></div>
  </div>
</header>
<?php if (!$me): ?>
<div class="empty"><strong>Compte absent de l'annuaire.</strong>Votre adresse e-mail doit figurer dans l'annuaire des collaborateurs pour utiliser les tâches.</div>
<?php else: ?>
<form class="panel tp-add" data-api="tasks/create">
  <label class="sr" for="mt-t">Tâche</label><input id="mt-t" name="title" class="tm-in tm-grow" maxlength="300" placeholder="Ajouter une tâche personnelle…" required>
  <label class="tp-due"><span>Échéance</span><input name="due" type="date" class="tm-in"></label>
  <button class="btn btn-primary">Ajouter</button>
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
  <?php if (!$done): ?><p class="muted small">Les tâches cochées apparaissent ici, barrées.</p>
  <?php else: ?><ul class="tt-list" data-more-list><?php foreach ($done as $i => $t) { $hidden = $i >= 10; include __DIR__ . '/_team_task.php'; } ?></ul>
  <?php if (count($done) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($done) - 10 ?>) ↓</button><?php endif; ?><?php endif; ?>
</section>
<?php endif; ?>
