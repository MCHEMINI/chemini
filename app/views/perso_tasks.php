<header class="head">
  <div>
    <div class="eyebrow">Espace personnel · privé</div>
    <h1>Mes tâches</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
  <section class="col-12">
        <div class="panel">
      <form data-api="tasks/create" class="add-row">
        <label for="new-task" class="sr">Nouvelle tâche</label>
        <input class="input" id="new-task" name="title" maxlength="200" placeholder="Ajouter une tâche, puis Entrée" required>
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
          <button type="button" class="btn-link" data-api="tasks/delete" data-id="<?= (int)$t['id'] ?>" data-confirm="Supprimer ?">Supprimer</button>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </section>

</div>
