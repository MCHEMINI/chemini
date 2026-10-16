<header class="head">
  <div>
    <div class="eyebrow"><?= $space === 'gid' ? 'Espace partagé · toute la société' : 'Tableau de bord · espace privé' ?></div>
    <h1><?= $space === 'gid' ? 'Espace' : 'Tableau de bord' ?> <em><?= e(SPACES[$space]) ?></em></h1>
    <div class="date">Visible par <?= e(implode(', ', array_map(fn($m) => explode(' ', $m['name'])[0], $members))) ?></div>
  </div>
  <div class="head-actions members"><?php foreach ($members as $m): ?><?= avatar($m) ?><?php endforeach; ?></div>
</header>

<?php if ($space === 'direction') require __DIR__ . '/_dir_board.php'; ?>
<?php if ($space === 'famille'): ?>
<section class="fam-trips">
  <div class="sec-h"><h2>Voyages</h2><a href="/voyages">Tous les voyages</a></div>
  <?php if (empty($trips)): ?><div class="empty"><strong>Aucun voyage préparé.</strong>Demandez « Voyage, destination, durée » à Claude : le guide complet apparaîtra ici.</div>
  <?php else: ?><div class="trip-mini"><?php foreach ($trips as $t): ?><a class="panel" href="/voyages/<?= (int)$t['id'] ?>"><span class="eyebrow"><?= e($t['duration']) ?></span><strong><?= e($t['destination']) ?></strong><?php if (!empty($t['d']['best_period']['when'])): ?><span class="muted small"><?= e($t['d']['best_period']['when']) ?></span><?php endif; ?></a><?php endforeach; ?></div><?php endif; ?>
</section>
<?php endif; ?>

<div class="grid" style="margin-top:32px">
  <section class="col-7">
    <div class="sec-h"><h2>Tâches partagées</h2></div>
    <div class="panel">
      <form data-api="tasks/create" class="add-row">
        <input type="hidden" name="space" value="<?= e($space) ?>">
        <label for="new-task-<?= e($space) ?>" class="sr">Nouvelle tâche</label>
        <input class="input" id="new-task-<?= e($space) ?>" name="title" maxlength="200" placeholder="Ajouter une tâche, puis Entrée" required>
        <button class="btn btn-primary">Ajouter</button>
      </form>
      <p class="form-error" hidden></p>
      <?php if (!$tasks): ?>
        <p class="muted">Aucune tâche pour l'instant.</p>
      <?php else: ?>
      <ul class="check">
        <?php foreach ($tasks as $t): ?>
        <li class="<?= $t['done'] ? 'done' : '' ?>">
          <input type="checkbox" id="t<?= (int)$t['id'] ?>" data-api="tasks/toggle" data-id="<?= (int)$t['id'] ?>" <?= $t['done'] ? 'checked' : '' ?>>
          <label for="t<?= (int)$t['id'] ?>"><span><?= e($t['title']) ?></span></label>
          <?php if ($t['initials']): ?><?= avatar(['id' => $t['uid'], 'initials' => $t['initials']], 'xs') ?><?php endif; ?>
          <button type="button" class="btn-link" data-api="tasks/delete" data-id="<?= (int)$t['id'] ?>" data-confirm="Supprimer ?">Supprimer</button>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

  <section class="col-5">
    <div class="sec-h"><h2>Bloc-notes commun</h2><span class="muted small" id="noteState"><?= $note ? 'Enregistré · ' . e(ago((int)$note['updated_at'])) . ($note['by_name'] ? ' par ' . e(explode(' ', $note['by_name'])[0]) : '') : '' ?></span></div>
    <div class="panel">
      <label for="note" class="sr">Bloc-notes commun</label>
      <textarea id="note" class="notepad" data-autosave="space/note" data-space="<?= e($space) ?>" placeholder="Notes partagées avec les membres de l'espace, enregistrées automatiquement."><?= e($note['body'] ?? '') ?></textarea>
    </div>
    <?php if ($docs): ?>
    <div class="sec-h" style="margin-top:32px"><h2>Outils de l'espace</h2></div>
    <ul class="docs panel">
      <?php foreach ($docs as $d): ?><li><a href="/<?= e($d['slug']) ?>"><?= e($d['title']) ?></a><span class="mono"><?= e(date_short((int)$d['updated_at'])) ?></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
