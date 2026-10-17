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
      <div class="date"><?= count($open) ?> à faire · <?= count($done) ?> faite<?= count($done) > 1 ? 's' : '' ?></div>
    </div>
  </div>
</header>

<section class="panel tp-contact" aria-label="Coordonnées">
  <a class="tp-c<?= $p['phone'] ? '' : ' off' ?>"<?= $p['phone'] ? ' href="tel:' . e(phone_tel($p['phone'])) . '"' : '' ?>>
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>
    <span><small>Ligne directe</small><b class="mono"><?= $p['phone'] ? e($p['phone']) : 'Non renseignée' ?></b></span>
  </a>
  <div class="tp-c<?= $p['ext'] ? '' : ' off' ?>">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M9 6h6M9 10h.01M12 10h.01M15 10h.01M9 14h.01M12 14h.01M15 14h.01M9 18h6"/></svg>
    <span><small>Raccourci interne</small><b class="mono"><?= $p['ext'] ? e($p['ext']) : 'Non renseigné' ?></b></span>
  </div>
  <a class="tp-c<?= $p['email'] ? '' : ' off' ?>"<?= $p['email'] ? ' href="mailto:' . e($p['email']) . '" data-outlook' : '' ?>>
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></svg>
    <span><small>E-mail · ouvre Outlook</small><b><?= $p['email'] ? e($p['email']) : 'Non renseigné' ?></b></span>
  </a>
  <?php if ($isDir && $p['email']): ?>
  <details class="tp-edit">
    <summary>Modifier</summary>
    <form data-api="team/contact">
      <input type="hidden" name="person" value="<?= e($p['key']) ?>">
      <label><span>Ligne directe</span><input name="phone" class="tm-in" inputmode="tel" maxlength="30" value="<?= e($p['phone']) ?>" placeholder="01 42 66 00 00"></label>
      <label><span>Raccourci</span><input name="ext" class="tm-in" inputmode="numeric" maxlength="8" value="<?= e($p['ext']) ?>" placeholder="4 derniers chiffres"></label>
      <button class="btn btn-primary">Enregistrer</button>
      <p class="form-error" hidden></p>
    </form>
  </details>
  <?php endif; ?>
</section>

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
