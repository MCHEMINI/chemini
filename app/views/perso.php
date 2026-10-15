<header class="head">
  <div>
    <div class="eyebrow">Espace personnel · privé</div>
    <h1>Mon espace</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
  <section class="col-7">
    <div class="sec-h"><h2>Mes tâches</h2></div>
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

  <section class="col-5">
    <div class="sec-h"><h2>Bloc-notes</h2><span class="muted small" id="noteState"><?= $note ? 'Enregistré · ' . e(ago((int)$note['updated_at'])) : '' ?></span></div>
    <div class="panel">
      <label for="note" class="sr">Bloc-notes</label>
      <textarea id="note" class="notepad" data-autosave="note/save" placeholder="Notes personnelles, enregistrées automatiquement."><?= e($note['body'] ?? '') ?></textarea>
    </div>

    <div class="sec-h" style="margin-top:32px"><h2>Mes points communs</h2><a href="/">Tableau de bord</a></div>
    <?php if (!$mine): ?>
      <div class="empty"><strong>Aucun point assigné.</strong>Les points du tableau de bord dont vous êtes responsable apparaissent ici.</div>
    <?php else: ?>
    <ul class="list">
      <?php foreach ($mine as $i): ?>
      <li><i class="sev <?= e($i['severity']) ?>"></i><div><div class="t"><?= e($i['title']) ?></div><?php if ($i['copro']): ?><div class="d"><?= e($i['copro']) ?></div><?php endif; ?></div><div class="meta"><span class="chip <?= e($i['severity']) ?>"><?= e(SEVERITY[$i['severity']] ?? '') ?></span></div></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
