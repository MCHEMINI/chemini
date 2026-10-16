<header class="head">
  <div>
    <div class="eyebrow">Espace personnel · privé</div>
    <h1>Mon espace</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
  <section class="col-12" id="assistant">
    <div class="sec-h"><h2>Assistant IA</h2><span class="muted small">Consignes vérifiées et traitées toutes les heures · <span class="chip todo">À faire</span> <span class="chip warn">En cours</span> <span class="chip ok">Fait</span></span></div>
    <div class="panel">
      <form data-api="consignes/create" class="ia-form">
        <label for="new-consigne" class="sr">Nouvelle consigne</label>
        <textarea class="input" id="new-consigne" name="body" maxlength="4000" rows="3" placeholder="Décrivez ce que l'assistant doit faire…" required></textarea>
        <div class="ia-form-row">
          <label for="new-prio" class="small muted">Priorité</label>
          <select class="input" id="new-prio" name="priority">
            <?php foreach (CONSIGNE_PRIO as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === 'normale' ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-primary">Envoyer</button>
        </div>
      </form>
      <p class="form-error" hidden></p>
      <?php if (!$consignes): ?>
        <p class="muted">Aucune consigne pour l'instant.</p>
      <?php else: ?>
      <ul class="list ia-list">
        <?php foreach ($consignes as $c): $st = $c['status']; ?>
        <li class="ia-<?= e($st) ?>">
          <i class="ia-dot" aria-hidden="true"></i>
          <div>
            <div class="t"><?= nl2br(e($c['body'])) ?></div>
            <div class="d">Priorité <?= e(mb_strtolower(CONSIGNE_PRIO[$c['priority']] ?? '')) ?> · déposée : <?= e(mb_strtolower(ago((int)$c['created_at']))) ?><?= $st === 'done' && $c['done_at'] ? ' · faite : ' . e(mb_strtolower(ago((int)$c['done_at']))) : '' ?></div>
            <?php if (!empty($c['result'])): ?><div class="ia-result"><?= nl2br(e($c['result'])) ?></div><?php endif; ?>
          </div>
          <div class="meta"><span class="chip <?= $st === 'done' ? 'ok' : ($st === 'doing' ? 'warn' : 'todo') ?>"><?= e(CONSIGNE_STATUS[$st] ?? $st) ?></span>
            <?php if ($st !== 'doing'): ?><button type="button" class="btn-link" data-api="consignes/delete" data-id="<?= (int)$c['id'] ?>" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
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
