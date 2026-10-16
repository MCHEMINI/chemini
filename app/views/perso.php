<header class="head">
  <div>
    <div class="eyebrow">Espace personnel · privé</div>
    <h1>Mon espace</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
  <section class="col-12" id="assistant">
    <div class="sec-h"><h2>Tâches IA</h2><span class="muted small">Vos consignes, visibles par vous seul · vérifiées et traitées toutes les heures · nouvelle consigne : pastille en bas à droite</span></div>
    <div class="ia-board">
      <?php foreach ([['todo', 'En attente', $ia_todo], ['doing', 'En cours', $ia_doing], ['done', 'Fait', $ia_done]] as [$k, $label, $list]): ?>
      <div class="panel ia-col ia-col-<?= $k ?>">
        <div class="sec-h flat"><h3><i class="ia-pt" aria-hidden="true"></i><?= $label ?></h3><span class="chip <?= $k === 'done' ? 'ok' : ($k === 'doing' ? 'warn' : 'todo') ?>"><?= $k === 'done' ? $ia_done_n : count($list) ?></span></div>
        <?php if (!$list): ?><p class="muted small"><?= $k === 'todo' ? 'Aucune consigne en attente.' : ($k === 'doing' ? 'Rien en cours.' : 'Aucune consigne faite.') ?></p>
        <?php else: ?><ul class="list ia-list"><?php foreach ($list as $c) include __DIR__ . '/_ia_item.php'; ?></ul><?php endif; ?>
        <?php if ($k === 'done' && $ia_done_n > 10): ?><a class="btn-link ia-more" href="/assistant">Voir plus (<?= $ia_done_n - 10 ?> plus anciennes) →</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="col-12">
    <div class="sec-h"><h2>Mails à traiter</h2><span class="muted small">Analyse automatique de votre boîte toutes les heures · cochez une fois traité</span></div>
    <?php if (!$mails): ?>
      <div class="empty"><strong>Rien à traiter.</strong>Les mails importants détectés dans votre boîte apparaîtront ici.</div>
    <?php else: ?>
    <ul class="list mail-todo">
      <?php foreach ($mails as $m): ?>
      <li class="<?= $m['done'] ? 'done' : '' ?>">
        <input type="checkbox" id="m<?= (int)$m['id'] ?>" data-api="mail/toggle" data-id="<?= (int)$m['id'] ?>" <?= $m['done'] ? 'checked' : '' ?> aria-label="Marquer comme traité">
        <label for="m<?= (int)$m['id'] ?>">
          <div class="t"><?= e($m['subject']) ?></div>
          <div class="d"><?= e($m['from_name'] ?: $m['from_email']) ?><?= $m['summary'] ? ' · ' . e($m['summary']) : '' ?></div>
          <?php if ($m['reason']): ?><div class="why"><?= e($m['reason']) ?></div><?php endif; ?>
        </label>
        <div class="meta"><span class="chip <?= e($m['priority']) ?>"><?= $m['priority'] === 'crit' ? 'Urgent' : ($m['priority'] === 'ok' ? 'À lire' : 'À traiter') ?></span>
          <span><?= e(ago((int)$m['received_at'])) ?></span>
          <?php if ($m['link']): ?><a href="<?= e($m['link']) ?>" target="_blank" rel="noopener">Ouvrir</a><?php endif; ?></div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

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
