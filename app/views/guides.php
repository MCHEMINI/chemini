<?php $by = []; foreach ($all as $g) $by[$g['cat']][] = $g; ?>
<header class="head">
  <div>
    <div class="eyebrow">Équipe · procédures</div>
    <h1>Guides</h1>
    <div class="date">Les procédures du cabinet, pas à pas : cochez chaque étape au fur et à mesure. Les guides s'enrichissent avec vos retours<?= $ed ? ' ; vous pouvez les modifier ou en ajouter' : ' : signalez une étape à corriger à la Direction' ?>.</div>
  </div>
  <?php if ($ed): ?><a class="btn btn-primary" href="/guides/nouveau">Nouveau guide</a><?php endif; ?>
</header>
<div class="gd-bar">
  <label class="gd-search" for="gdQ"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="gdQ" type="search" placeholder="Rechercher un guide : export, AG, factures…" autocomplete="off"></label>
</div>
<?php if (!$all): ?><div class="empty"><strong>Aucun guide pour l'instant.</strong></div><?php endif; ?>
<?php foreach ($by as $cat => $gs): ?>
<section class="gd-cat">
  <div class="sec-h"><h2><?= e($cat) ?></h2><span class="muted small"><?= count($gs) ?> guide<?= count($gs) > 1 ? 's' : '' ?></span></div>
  <div class="gd-grid">
    <?php foreach ($gs as $g): $ns = guide_steps($g['body']); ?>
    <a class="gd-card" href="/guides/<?= e($g['slug']) ?>" data-gd="<?= e($g['slug']) ?>" data-steps="<?= $ns ?>" data-s="<?= e(mb_strtolower($g['title'] . ' ' . $g['summary'] . ' ' . $g['cat'] . ' ' . strip_tags($g['body']))) ?>">
      <span class="gd-ic" aria-hidden="true"><?= e($g['icon'] ?: '📘') ?></span>
      <b><?= e($g['title']) ?></b>
      <span class="gd-sum"><?= e((string)$g['summary']) ?></span>
      <span class="gd-meta"><span>⏱ <?= e((string)$g['duration']) ?></span><span><?= e((string)$g['level']) ?></span><span><?= $ns ?> étapes</span></span>
      <span class="gd-prog" aria-hidden="true"><i></i></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>
<p class="empty gd-none" hidden><strong>Aucun guide trouvé.</strong>Essayez un autre mot.</p>
