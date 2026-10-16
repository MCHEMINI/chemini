<?php $first = explode(' ', $u['name']); ?>
<header class="head">
  <div>
    <div class="eyebrow">Espace commun</div>
    <h1><?= greeting() ?>, <em><?= e($u['name']) ?></em>.</h1>
    <div class="date"><?= e(date_long(time())) ?></div>
  </div>
  <div class="head-actions">
    <button class="btn btn-ghost" type="button" data-open="dlgDeadline">Nouvelle échéance</button>
    <button class="btn btn-primary" type="button" data-open="dlgItem">Nouveau point</button>
  </div>
</header>

<div class="summary">
  <div><div class="n <?= $stats['crit'] ? 'crit' : '' ?>"><?= $stats['crit'] ?></div><div class="l">Points urgents</div></div>
  <div><div class="n"><?= $mailsCount === null ? '—' : (int)$mailsCount ?></div><div class="l">Mails importants non lus</div></div>
  <div><div class="n"><?= $stats['week'] ?></div><div class="l">Échéances sous 7 jours</div></div>
  <div><div class="n"><?= $stats['open'] ?></div><div class="l">Points ouverts</div></div>
</div>

<div class="grid">
  <section class="col-7">
    <div class="sec-h"><h2>Points importants à traiter</h2></div>
    <?php if (!$items): ?>
      <div class="empty"><strong>Rien en attente.</strong>Ajoutez un point avec « Nouveau point » : il apparaîtra ici pour vous deux.</div>
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

  <section class="col-7">
    <div class="sec-h"><h2>Mails importants</h2><?php if ($mails !== null): ?><a href="/espace">Tout traiter</a><?php endif; ?></div>
    <?php if ($mails === null): ?>
      <div class="empty"><strong>Analyse en cours de mise en place.</strong>Votre boîte est analysée toutes les heures : les mails importants apparaîtront ici et dans Mon espace.
      </div>
    <?php elseif (!$mails): ?>
      <div class="empty"><strong>Boîte à jour.</strong>Aucun mail important en attente de traitement.</div>
    <?php else: ?>
    <ul class="list mail">
      <?php foreach ($mails as $m): ?>
      <li>
        <i class="sev <?= e($m['level']) ?>"></i>
        <div>
          <div class="from"><?= e($m['from']) ?></div>
          <div class="subj"><?php if ($m['link']): ?><a href="<?= e($m['link']) ?>" target="_blank" rel="noopener"><?= e($m['subject']) ?></a><?php else: ?><?= e($m['subject']) ?><?php endif; ?></div>
          <div class="prev"><?= e($m['preview']) ?></div>
        </div>
        <div class="meta"><?= e(ago($m['date'])) ?></div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="col-5">
    <div class="sec-h"><h2>Veille juridique</h2><?php if ($veille): ?><span class="muted small">Mise à jour chaque lundi · <?= e(date_short((int)max(array_column($veille, 'created_at')))) ?></span><?php else: ?><a href="https://www.legifrance.gouv.fr/" target="_blank" rel="noopener">Légifrance</a><?php endif; ?></div>
    <?php if ($veille): ?>
    <div class="law veille">
      <?php foreach ($veille as $l): ?>
      <article class="<?= $l['important'] ? 'imp' : '' ?>">
        <span class="ref"><?= e($l['source'] ?: 'Veille') ?><?= $l['published_at'] ? ' · ' . e(date_short((int)$l['published_at'])) : '' ?><?php if ($l['important']): ?> <span class="chip crit">Important</span><?php endif; ?></span>
        <h3><?= e($l['title']) ?></h3>
        <p><?= nl2br(e($l['summary'])) ?></p>
        <?php if ($l['impact']): ?><p class="impact"><strong>Pour GID :</strong> <?= e($l['impact']) ?></p><?php endif; ?>
        <a class="more" href="<?= e($l['url']) ?>" target="_blank" rel="noopener">Lire l'article →</a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$legal): ?>
      <div class="empty"><strong>Veille indisponible.</strong>La synthèse hebdomadaire arrive lundi à 11 h.</div>
    <?php else: ?>
    <div class="law">
      <?php foreach ($legal as $l): ?>
      <article>
        <span class="ref"><?= e($l['source']) ?><?= $l['date'] ? ' · ' . e(date_short($l['date'])) : '' ?></span>
        <h3><a href="<?= e($l['link']) ?>" target="_blank" rel="noopener"><?= e($l['title']) ?></a></h3>
        <?php if ($l['summary']): ?><p><?= e($l['summary']) ?></p><?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="col-12">
    <div class="sec-h"><h2>Dernières actualités</h2></div>
    <?php if (!$news): ?>
      <div class="empty"><strong>Actualités indisponibles.</strong>Les sources n'ont pas répondu. Nouvel essai automatique dans l'heure.</div>
    <?php else: ?>
    <div class="news">
      <?php foreach ($news as $n): ?>
      <article>
        <span class="eyebrow"><?= e($n['source']) ?></span>
        <h3><a href="<?= e($n['link']) ?>" target="_blank" rel="noopener"><?= e($n['title']) ?></a></h3>
        <?php if ($n['summary']): ?><p><?= e($n['summary']) ?></p><?php endif; ?>
        <span class="src"><?= $n['date'] ? e(ago($n['date'])) : '' ?></span>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="col-12">
    <div class="slot"><div><strong>Emplacement libre</strong>Les prochains modules (trésorerie, sinistres, fournisseurs…) viendront se placer ici.</div><span class="chip neutral">À venir</span></div>
  </section>
</div>

<dialog id="dlgItem" class="dlg">
  <form data-api="items/create" class="stack">
    <div class="eyebrow">Espace commun</div>
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
    <div class="eyebrow">Espace commun</div>
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
