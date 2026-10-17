<?php $title = $archive ? 'Archives des actualités' : ($cat ? NEWS_CATS[$cat][0] : 'Actualités du métier'); ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/actualites">Actualités</a><?= $cat ? ' · ' . e(NEWS_CATS[$cat][0]) : ($archive ? ' · archives' : '') ?></div>
    <h1><?= e($title) ?></h1>
    <div class="date"><?= $archive ? 'Articles de plus de 30 jours, classés par date.' : ($cat ? e(NEWS_CATS[$cat][1]) . ' · articles des 30 derniers jours, les plus importants en premier.' : 'Syndic, copropriété, achat-vente et gestion locative · synthèse du lundi au vendredi à 8 h, articles des 30 derniers jours.') ?></div>
  </div>
  <div class="head-actions"><a class="btn btn-ghost" href="/actualites/archives">Archives<?= $archN ? ' (' . $archN . ')' : '' ?></a></div>
</header>
<?php if (!$archive): $cur = $cat; require __DIR__ . '/_news_blocks.php'; endif; ?>
<form class="nw-search" method="get" action="">
  <label class="gd-search" for="nwQ"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="nwQ" name="q" type="search" value="<?= e($q) ?>" placeholder="Rechercher dans les <?= $archive ? 'archives' : 'actualités' ?>…"></label>
  <?php if ($archive): ?><select class="input" name="cat" aria-label="Rubrique"><option value="">Toutes les rubriques</option><?php foreach (NEWS_CATS as $k => $c): ?><option value="<?= $k ?>"<?= $acat === $k ? ' selected' : '' ?>><?= e($c[0]) ?></option><?php endforeach; ?></select><?php endif; ?>
  <button class="btn btn-ghost">Rechercher</button>
</form>
<?php if (!$items): ?><div class="empty"><strong><?= $q !== '' ? 'Aucun article trouvé.' : ($archive ? 'Aucun article archivé pour l\'instant.' : 'Aucun article dans cette rubrique ce mois-ci.') ?></strong><?= $archive ? '' : 'La synthèse arrive du lundi au vendredi à 8 h.' ?></div><?php endif; ?>
<div class="nw-list">
  <?php $lastM = ''; foreach ($items as $n): $m = substr($n['date'], 0, 7);
    if ($archive && $m !== $lastM): $lastM = $m; ?><h2 class="nw-month"><?= e(ucfirst(MOIS_LONG[(int)substr($m, 5) - 1]) . ' ' . substr($m, 0, 4)) ?></h2><?php endif; ?>
  <article class="panel nw-a<?= $n['important'] ? ' imp' : '' ?>">
    <div class="nw-a-h"><span class="eyebrow"><?= e(NEWS_CATS[$n['cat']][0] ?? '') ?> · <?= e($n['source'] ?: 'Presse') ?> · <?= e(date('d/m/Y', strtotime($n['date']))) ?></span><?php if ($n['important']): ?><span class="chip crit nw-pill">Important</span><?php endif; ?></div>
    <h3><?= e($n['title']) ?></h3>
    <?php if ($n['summary']): ?><p><?= nl2br(e($n['summary'])) ?></p><?php endif; ?>
    <?php if ($n['why']): ?><p class="nd-why"><strong>Pour GID :</strong> <?= e($n['why']) ?></p><?php endif; ?>
    <a class="btn btn-ghost nw-read" href="<?= e($n['url']) ?>" target="_blank" rel="noopener">Lire l'article complet ↗</a>
  </article>
  <?php endforeach; ?>
</div>
