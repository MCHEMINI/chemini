<header class="head">
  <div>
    <div class="eyebrow">Espace GID · toute l'équipe</div>
    <h1><?= greeting() ?>, <em><?= e(explode(' ', $u['name'])[0]) ?></em>.</h1>
    <div class="date"><?= e(date_long(time())) ?></div>
  </div>
  <div class="head-actions">
    <a class="btn btn-ghost" href="/immeubles">Annuaire des immeubles</a>
    <?php if ($isDir): ?><a class="btn btn-primary" href="/direction">Tableau de bord Direction</a><?php endif; ?>
  </div>
</header>

<div class="summary">
  <div><div class="n"><?= (int)$stats['syndic'] ?></div><div class="l"><a href="/immeubles">Immeubles en syndic</a></div></div>
  <div><div class="n"><?= (int)$stats['staff'] ?></div><div class="l">Collaborateurs</div></div>
  <div><div class="n <?= $stats['veilleImp'] ? 'crit' : '' ?>"><?= (int)$stats['veilleImp'] ?></div><div class="l">Points de veille importants cette semaine</div></div>
  <div><div class="n"><?= (int)$stats['tasks'] ?></div><div class="l"><a href="/gid">Tâches de l'équipe en cours</a></div></div>
</div>

<div class="grid">
  <section class="col-7">
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


  <section class="col-5">
    <div class="sec-h"><h2>Espace GID</h2><a href="/gid">Ouvrir</a></div>
    <div class="panel">
      <?php if (!empty($gidNote['body'])): ?><p class="gid-note"><?= nl2br(e(mb_strimwidth($gidNote['body'], 0, 600, '…'))) ?></p><?php else: ?><p class="muted">Le bloc-notes partagé de l'équipe est vide.</p><?php endif; ?>
      <?php if ($gidTasks): ?><ul class="check compact"><?php foreach ($gidTasks as $t): ?><li><span><?= e($t['title']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
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

</div>
