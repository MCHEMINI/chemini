<header class="head">
  <div>
    <div class="eyebrow">Perso · privé</div>
    <h1>Mails à traiter</h1>
    <div class="date">Visible uniquement par <?= e($u['name']) ?></div>
  </div>
</header>

<div class="grid">
  <section class="col-12">
    <?php
      $mRun = (int)q('SELECT v FROM kv WHERE k=?', ['mails_run:' . (int)$u['id']])->fetchColumn() ?: (int)q('SELECT MAX(created_at) FROM mail_items WHERE user_id=?', [(int)$u['id']])->fetchColumn();
      $now = time(); $h = (int)date('G', $now); // prochaine relève : chaque heure pile de 8 h à 20 h
      $next = $h < 8 ? strtotime('today 08:00') : ($h >= 20 ? strtotime('tomorrow 08:00') : strtotime(date('Y-m-d H:00', $now)) + 3600);
      $fmt = fn(int $t) => (date('Y-m-d', $t) === date('Y-m-d') ? 'aujourd\'hui' : (date('Y-m-d', $t) === date('Y-m-d', strtotime('tomorrow')) ? 'demain' : (date('Y-m-d', $t) === date('Y-m-d', strtotime('yesterday')) ? 'hier' : 'le ' . date('d/m', $t)))) . ' à ' . date('G:i', $t); ?>
    <div class="sec-h"><h2>Mails à traiter</h2><span class="muted small">Cochez un mail une fois traité</span></div>
    <div class="mail-run" role="status">
      <span class="mr-i" aria-hidden="true">🕗</span>
      <div><b>Relève automatique tous les jours, week-end compris, toutes les heures de 8 h à 20 h.</b>
      <span>Chaque relève lit les mails reçus depuis la précédente ; celle de 8 h reprend ceux arrivés depuis 20 h la veille. Un mail auquel vous avez répondu sort seul de la liste.</span>
      <span class="mr-t"><?= $mRun ? 'Dernière relève ' . e($fmt($mRun)) : 'Aucune relève pour l\'instant' ?> · prochaine <?= e($fmt($next)) ?></span></div>
    </div>
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
