<?php require __DIR__ . '/_head.php'; ?>
<div class="login-card">
  <div><div class="eyebrow">Erreur</div><h2><?= e($title ?? 'Page introuvable') ?></h2>
  <p class="sub"><?= e($message ?? 'Cette adresse ne correspond à aucune page.') ?></p></div>
  <a class="btn btn-primary" href="/">Retour à l'accueil</a>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
