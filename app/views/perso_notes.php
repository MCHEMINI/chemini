<header class="head">
  <div>
    <div class="eyebrow">Perso · privé</div>
    <h1>Bloc-notes</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?> · enregistré automatiquement</div>
  </div>
</header>

<div class="grid">
  <section class="col-12">
    <div class="sec-h"><span class="muted small" id="noteState"><?= $note ? 'Enregistré · ' . e(ago((int)$note['updated_at'])) : '' ?></span></div>
    <div class="panel">
      <label for="note" class="sr">Bloc-notes</label>
      <textarea id="note" class="notepad notepad-full" data-autosave="note/save" placeholder="Notes personnelles, enregistrées automatiquement."><?= e($note['body'] ?? '') ?></textarea>
    </div>

  </section>
</div>
