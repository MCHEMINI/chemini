<?php if (!empty($audit)): $am = (int)substr($audit['period'], 5, 2); $ay = substr($audit['period'], 0, 4); ?>
<div class="audit-alert" role="status">
  <i class="sev warn"></i>
  <div>
    <div class="eyebrow">Audit mensuel · honoraires · <?= e(['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'][$am - 1] . ' ' . $ay) ?></div>
    <div class="t"><?= e($audit['headline']) ?></div>
    <?php if (!empty($audit['d']['actions'][0]['title'])): ?><div class="d">Priorité : <?= e($audit['d']['actions'][0]['title']) ?></div><?php endif; ?>
  </div>
  <div class="audit-actions">
    <a class="btn btn-primary" href="/honoraires#opti">Voir l'analyse</a>
    <button type="button" class="btn-link" data-api="audit/read" data-id="<?= (int)$audit['id'] ?>">Marquer comme lu</button>
  </div>
</div>
<?php endif; ?>


<?php require __DIR__ . '/_aiq.php'; ?>
<?php require __DIR__ . '/_mq.php'; ?>

<?php require __DIR__ . '/_overview.php'; ?>

<div class="dir-actions"><button class="btn btn-ghost" type="button" data-open="dlgDeadline">Nouvelle échéance</button><button class="btn btn-primary" type="button" data-open="dlgItem">Nouveau point</button></div>

<?php if ($items || $deadlines || $overdue): ?>
<div class="grid">
  <section class="col-7">
    <div class="sec-h"><h2>Points importants à traiter</h2></div>
    <?php if (!$items): ?>
      <div class="empty"><strong>Rien en attente.</strong>Ajoutez un point avec « Nouveau point » : il apparaîtra ici pour l'équipe de direction.</div>
    <?php else: ?>
    <ul class="list">
      <?php foreach ($items as $i): ?>
      <li>
        <i class="sev <?= e($i['severity']) ?>"></i>
        <div>
          <div class="t"><?= e($i['title']) ?></div>
          <?php if ($i['copro'] || $i['detail']): ?><div class="d"><?= e(trim($i['copro'] . ($i['copro'] && $i['detail'] ? ' · ' : '') . $i['detail'])) ?></div><?php endif; ?>
          <?php if ($i['owner_name']): ?><div class="owner"><?= avatar(['id' => $i['oid'], 'initials' => $i['owner_initials']], 'xs') ?><?= e($i['owner_name']) ?></div><?php endif; ?>
        </div>
        <div class="meta">
          <span class="chip <?= e($i['severity']) ?>"><?= e(SEVERITY[$i['severity']] ?? '') ?></span>
          <div class="row-actions">
            <button type="button" class="btn-link" data-api="items/done" data-id="<?= (int)$i['id'] ?>">Traité</button>
            <button type="button" class="btn-link" data-api="items/delete" data-id="<?= (int)$i['id'] ?>" data-confirm="Supprimer ?">Supprimer</button>
          </div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="col-5">
    <div class="sec-h"><h2>Prochaines échéances</h2></div>
    <?php if (!$deadlines && !$overdue): ?>
      <div class="empty"><strong>Aucune échéance.</strong>AG, appels de fonds, fins de contrat : ajoutez-les avec « Nouvelle échéance ».</div>
    <?php else: ?>
    <ul class="timeline">
      <?php foreach (array_merge($overdue, $deadlines) as $d): $ts = strtotime($d['due_date']); $late = $d['due_date'] < date('Y-m-d'); $soon = !$late && $ts <= strtotime('+7 days'); ?>
      <li>
        <div class="cal <?= $late ? 'late' : ($soon ? 'soon' : '') ?>"><b><?= date('d', $ts) ?></b><span><?= e(MOIS[(int)date('n', $ts) - 1]) ?></span></div>
        <div>
          <div class="t"><?= e($d['title']) ?><?php if ($late): ?> <span class="chip crit">En retard</span><?php endif; ?></div>
          <?php if ($d['copro'] || $d['detail']): ?><div class="d"><?= e(trim($d['copro'] . ($d['copro'] && $d['detail'] ? ' · ' : '') . $d['detail'])) ?></div><?php endif; ?>
          <div class="row-actions left">
            <button type="button" class="btn-link" data-api="deadlines/done" data-id="<?= (int)$d['id'] ?>">Fait</button>
            <button type="button" class="btn-link" data-api="deadlines/delete" data-id="<?= (int)$d['id'] ?>" data-confirm="Supprimer ?">Supprimer</button>
          </div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

</div>
<?php endif; ?>

<dialog id="dlgItem" class="dlg">
  <form data-api="items/create" class="stack">
    <div class="eyebrow">Direction</div>
    <h2>Nouveau point à traiter</h2>
    <div class="field"><label for="it-title">Intitulé</label><input class="input" id="it-title" name="title" required maxlength="200" placeholder="Infiltration toiture, bât. B"></div>
    <div class="field"><label for="it-copro">Copropriété</label><input class="input" id="it-copro" name="copro" maxlength="120" placeholder="Résidence…"></div>
    <div class="field"><label for="it-detail">Détail</label><input class="input" id="it-detail" name="detail" maxlength="400" placeholder="Ce qu'il faut faire, et pour quand"></div>
    <div class="two">
      <div class="field"><label for="it-sev">Priorité</label><select class="input" id="it-sev" name="severity"><option value="crit">Urgent</option><option value="warn" selected>Cette semaine</option><option value="ok">À suivre</option></select></div>
      <div class="field"><label for="it-owner">Responsable</label><select class="input" id="it-owner" name="owner_id"><?php foreach ($users as $x): ?><option value="<?= (int)$x['id'] ?>" <?= $x['id'] == $u['id'] ? 'selected' : '' ?>><?= e($x['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <p class="form-error" hidden></p>
    <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary">Ajouter le point</button></div>
  </form>
</dialog>

<dialog id="dlgDeadline" class="dlg">
  <form data-api="deadlines/create" class="stack">
    <div class="eyebrow">Direction</div>
    <h2>Nouvelle échéance</h2>
    <div class="field"><label for="dl-title">Intitulé</label><input class="input" id="dl-title" name="title" required maxlength="200" placeholder="Convocation AG à envoyer"></div>
    <div class="two">
      <div class="field"><label for="dl-date">Date</label><input class="input" id="dl-date" name="due_date" type="date" required></div>
      <div class="field"><label for="dl-copro">Copropriété</label><input class="input" id="dl-copro" name="copro" maxlength="120"></div>
    </div>
    <div class="field"><label for="dl-detail">Détail</label><input class="input" id="dl-detail" name="detail" maxlength="400" placeholder="21 jours minimum avant la date d'AG"></div>
    <p class="form-error" hidden></p>
    <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary">Ajouter l'échéance</button></div>
  </form>
</dialog>
