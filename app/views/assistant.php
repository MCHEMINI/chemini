<header class="head">
  <div>
    <div class="eyebrow"><a href="/espace#assistant">Mon espace</a> · Assistant IA</div>
    <h1>Consignes faites</h1>
    <div class="date"><?= (int)$total ?> consigne<?= $total > 1 ? 's' : '' ?> traitée<?= $total > 1 ? 's' : '' ?> · visibles uniquement par <?= e($u['name']) ?></div>
  </div>
</header>
<?php if (!$rows): ?>
  <div class="empty"><strong>Aucune consigne faite pour l'instant.</strong>Les consignes traitées par l'assistant apparaîtront ici.</div>
<?php else: ?>
<div class="panel"><ul class="list ia-list"><?php foreach ($rows as $c) include __DIR__ . '/_ia_item.php'; ?></ul></div>
<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Pages">
  <?php if ($page > 1): ?><a class="btn" href="/assistant?p=<?= $page - 1 ?>">← Plus récentes</a><?php endif; ?>
  <span class="muted small">Page <?= $page ?> sur <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn" href="/assistant?p=<?= $page + 1 ?>">Plus anciennes →</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
