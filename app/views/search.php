<?php $n = array_sum(array_map(fn($g) => count($g['items']), $groups)); ?>
<header class="head"><div><div class="eyebrow">Toute la plateforme</div><h1>Recherche</h1><div class="date"><?= $q === '' ? 'Tapez un mot dans la barre ci-dessus : pages, guides, tâches, mails, notes, actualités, immeubles, documents…' : ($n ? $n . ' résultat' . ($n > 1 ? 's' : '') . ' pour « ' . e($q) . ' »' : 'Aucun résultat pour « ' . e($q) . ' »') ?></div></div></header>
<?php if ($groups): ?>
<nav class="gs-jump" aria-label="Types de résultats"><?php foreach ($groups as $g): ?><a href="#gs-<?= e($g['k']) ?>"><?= $g['icon'] ?> <?= e($g['label']) ?> <b><?= count($g['items']) ?></b></a><?php endforeach; ?></nav>
<?php foreach ($groups as $g): ?>
<section class="panel gs-group" id="gs-<?= e($g['k']) ?>">
  <h2><?= $g['icon'] ?> <?= e($g['label']) ?> <span class="muted small"><?= count($g['items']) ?></span></h2>
  <ul class="gs-list"><?php foreach ($g['items'] as $it): ?>
    <li><a href="<?= e($it['url']) ?>"><b><?= e($it['title']) ?></b><?php if ($it['meta']): ?><small><?= e($it['meta']) ?></small><?php endif; ?><?php if ($it['snip']): ?><span class="gs-snip"><?= $it['snip'] ?></span><?php endif; ?></a></li>
  <?php endforeach; ?></ul>
</section>
<?php endforeach; ?>
<?php elseif ($q !== ''): ?>
<div class="empty"><strong>Rien trouvé.</strong>Essayez un autre mot, un nom, un code immeuble ou une partie du titre.</div>
<?php endif; ?>
<p class="muted small">La recherche ne montre que ce que vous avez le droit de voir : vos propres mails, notes et tâches IA, les sections auxquelles vous avez accès. Les livres et documents importés sont lus en entier ; les PDF numérisés sont passés en reconnaissance de texte (OCR) pour être retrouvés eux aussi.</p>
