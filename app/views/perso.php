<header class="head">
  <div>
    <div class="eyebrow">Espace personnel · privé</div>
    <h1>Mon espace</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
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

  <section class="col-12">
    <div class="sec-h"><h2>Mes points communs</h2><a href="/">Tableau de bord</a></div>
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
