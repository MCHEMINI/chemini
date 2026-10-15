<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="<?= e($_SESSION['csrf']) ?>">
<meta name="theme-color" content="#121720">
<title><?= e($title ?? '') ?> · <?= e(APP_NAME) ?></title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;1,6..96,400&family=Manrope:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=5">
</head>
<body>
<div id="app">
  <aside class="rail">
    <a class="monogram" href="/"><span class="seal">G</span><span class="mono-label">Chemini</span></a>
    <nav>
      <?php $n = $nav ?? ''; $sp = user_spaces($u); $cls = fn($k) => $n === $k ? 'active' : ''; ?>
      <div class="group">GID</div>
      <a class="<?= $cls('dash') ?>" href="/"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('immeubles') ?>" href="/immeubles"><i class="dot"></i>Immeubles</a>
      <a class="<?= $cls('space-gid') ?>" href="/gid"><i class="dot"></i>Espace GID</a>
      <?php if (in_array('direction', $sp, true)): ?>
      <div class="group">Direction</div>
      <a class="<?= $cls('space-direction') ?>" href="/direction"><i class="dot"></i>Espace Direction</a>
      <?php endif; ?>
      <?php if (in_array('famille', $sp, true)): ?>
      <div class="group">Famille</div>
      <a class="<?= $cls('space-famille') ?>" href="/famille"><i class="dot"></i>Espace Famille</a>
      <a class="<?= $cls('honoraires') ?>" href="/honoraires"><i class="dot"></i>Honoraires</a>
      <?php endif; ?>
      <div class="group">Personnel</div>
      <a class="<?= $cls('perso') ?>" href="/espace"><?= avatar($u, 'sm') ?>Mon espace</a>
      <div class="group">Compte</div>
      <a class="<?= $cls('secu') ?>" href="/securite"><i class="dot"></i>Sécurité</a>
      <a href="/deconnexion" class="logout-link"><i class="dot"></i>Déconnexion</a>
    </nav>
    <div class="me"><?= avatar($u) ?><div><?= e($u['name']) ?><small><?= e($u['email']) ?></small></div></div>
  </aside>
  <main class="<?= !empty($wide) ? 'wide' : '' ?>"><?= $content ?></main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<script src="/assets/app.js?v=4"></script>
</body>
</html>
