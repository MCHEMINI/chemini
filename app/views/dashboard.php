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
<?php if ($o['upcoming']): $jrs = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']; ?>
<section class="ag-next">
  <div class="sec-h"><h2>Prochaines AG</h2><span class="muted small"><?= count($o['upcoming']) ?> dans les 30 jours<?= $isDir ? ' · <a href="/planning-ag">Planning complet</a>' : '' ?></span></div>
  <div class="agn-grid">
    <?php foreach (array_slice($o['upcoming'], 0, 8) as $a): $ts = strtotime($a['ag_date']); $soon = $ts <= strtotime('+7 days'); $tag = $isDir ? 'a' : 'div'; ?>
    <<?= $tag ?> class="agn<?= $soon ? ' soon' : '' ?>"<?= $isDir ? ' href="/immeubles/' . e($a['code']) . '"' : '' ?>>
      <span class="agn-d"><small><?= e($jrs[(int)date('w', $ts)]) ?></small><b><?= date('d', $ts) ?></b><small><?= e(MOIS[(int)date('n', $ts) - 1]) ?></small></span>
      <span class="agn-b"><b><?= e($a['nom'] ?? '') ?></b><small><span class="mono"><?= e($a['code']) ?></span><?= !empty($a['ag_heure']) ? ' · ' . e($a['ag_heure']) : '' ?></small><small><?= e(trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($a['gest'] ?? '')))) ?></small><?php if (empty($a['convoc'])): ?><span class="chip warn">Convocation à envoyer</span><?php endif; ?></span>
    </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
  <?php if (count($o['upcoming']) > 8): ?><p class="muted small"><?= count($o['upcoming']) - 8 ?> autre<?= count($o['upcoming']) - 8 > 1 ? 's' : '' ?> AG dans les 30 jours<?= $isDir ? ' · <a href="/planning-ag">voir le planning</a>' : '' ?>.</p><?php endif; ?>
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
    <?php $dg = $digest && !empty($digest['items']) ? $digest : null; ?>
    <div class="sec-h"><h2>Dernières actualités</h2><span class="muted small">Synthèse du lundi au vendredi à 8 h<?= $dg ? ' · ' . e(date_short(strtotime($dg['date']))) : '' ?></span></div>
    <?php if ($dg): ?>
    <div class="nd">
      <?php foreach (array_slice($dg['items'], 0, 8) as $n): ?>
      <article>
        <span class="eyebrow"><?= e($n['source']) ?><?= $n['date'] ? ' · ' . e(date_short(strtotime($n['date']))) : '' ?></span>
        <h3><?= e($n['title']) ?></h3>
        <?php if ($n['summary']): ?><p><?= e($n['summary']) ?></p><?php endif; ?>
        <?php if ($n['why']): ?><p class="nd-why"><strong>Pour GID :</strong> <?= e($n['why']) ?></p><?php endif; ?>
        <a class="more" href="<?= e($n['url']) ?>" target="_blank" rel="noopener">Lire sur <?= e($n['source'] ?: 'le site') ?> →</a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$news): ?>
      <div class="empty"><strong>Actualités indisponibles.</strong>La synthèse arrive du lundi au vendredi à 8 h.</div>
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
