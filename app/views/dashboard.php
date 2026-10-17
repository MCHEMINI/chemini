<header class="head">
  <div>
    <div class="eyebrow">Équipe · toute la société</div>
    <h1><?= greeting() ?>, <em><?= e(explode(' ', $u['name'])[0]) ?></em>.</h1>
    <div class="date"><?= e(date_long(time())) ?></div>
  </div>
  <div class="head-actions">
    <?php if ($isDir): ?><a class="btn btn-primary" href="/direction">Tableau de bord Direction</a><?php endif; ?>
  </div>
</header>

<?php $o = $ops; $L = fn($href, $txt) => $isDir ? '<a href="' . $href . '">' . $txt . '</a>' : $txt; ?>
<div class="ov-grid">
  <div class="ov-card">
    <div class="ov-h"><span class="ov-eye">Assemblées générales</span><?php if ($isDir): ?><a class="ov-go" href="/planning-ag">Planning →</a><?php endif; ?></div>
    <div class="ov-n"><?= count($o['upcoming']) ?></div>
    <div class="ov-l">AG dans les 30 prochains jours · <?= (int)$o['week'] ?> sous 14 jours</div>
    <ul class="ov-rows">
      <li class="<?= count($o['convoc']) ? 'crit' : 'ok' ?>"><span>Convocations en retard</span><b><?= count($o['convoc']) ?></b></li>
      <li class="<?= count($o['nopv']) ? 'crit' : 'ok' ?>"><span>PV à diffuser (+21 jours)</span><b><?= count($o['nopv']) ?></b></li>
    </ul>
  </div>
  <div class="ov-card">
    <div class="ov-h"><span class="ov-eye">Veille juridique</span><span class="ov-go">lundi 9 h</span></div>
    <div class="ov-n <?= $stats['veilleImp'] ? 'crit' : '' ?>"><?= (int)$stats['veilleImp'] ?></div>
    <div class="ov-l">point<?= $stats['veilleImp'] > 1 ? 's' : '' ?> important<?= $stats['veilleImp'] > 1 ? 's' : '' ?> cette semaine<?= $veille ? ' · ' . count($veille) . ' informations' : '' ?></div>
  </div>
  <a class="ov-card" href="/gid">
    <div class="ov-h"><span class="ov-eye">Équipe</span><span class="ov-go">Espace équipe →</span></div>
    <div class="ov-n"><?= (int)$stats['tasks'] ?></div>
    <div class="ov-l">tâches partagées en cours · <?= (int)$stats['syndic'] ?> immeubles en syndic · <?= (int)$stats['staff'] ?> collaborateurs</div>
  </a>
</div>
<?php if ($o['upcoming']): ?>
<section class="ov-lists">
  <div class="sec-h"><h2>Prochaines AG</h2><?php if ($isDir): ?><a href="/planning-ag">Planning complet</a><?php endif; ?></div>
  <ul class="ov-ag">
    <?php foreach (array_slice($o['upcoming'], 0, 6) as $a): $ts = strtotime($a['ag_date']); ?>
    <li><div class="cal <?= $ts <= strtotime('+7 days') ? 'soon' : '' ?>"><b><?= date('d', $ts) ?></b><span><?= e(MOIS[(int)date('n', $ts) - 1]) ?></span></div>
      <div><?= $L('/immeubles/' . e($a['code']), '<span class="mono muted">' . e($a['code']) . '</span> ' . e($a['nom'] ?? '')) ?><div class="d"><?= e(trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($a['gest'] ?? '')))) ?><?= !empty($a['ag_heure']) ? ' · ' . e($a['ag_heure']) : '' ?></div></div></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<div class="grid">
  <section class="col-7">
    <div class="sec-h"><h2>Veille juridique</h2><?php if ($veille): ?><span class="muted small">Mise à jour chaque lundi à 9 h · <?= e(date_short((int)max(array_column($veille, 'created_at')))) ?></span><?php else: ?><a href="https://www.legifrance.gouv.fr/" target="_blank" rel="noopener">Légifrance</a><?php endif; ?></div>
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
      <div class="empty"><strong>Veille indisponible.</strong>La synthèse hebdomadaire arrive lundi à 9 h.</div>
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
    <div class="sec-h"><h2>Dernières actualités</h2><span class="muted small">Mise à jour chaque lundi à 9 h</span></div>
    <?php if (!$news): ?>
      <div class="empty"><strong>Actualités indisponibles.</strong>Les sources n'ont pas répondu. Nouvel essai automatique dans l'heure.</div>
    <?php else: ?>
    <div class="news one">
      <?php foreach (array_slice($news, 0, 8) as $n): ?>
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
