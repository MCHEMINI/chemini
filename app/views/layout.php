<!doctype html>
<html lang="fr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="<?= e($_SESSION['csrf']) ?>">
<meta name="theme-color" content="#121720">
<script src="/assets/theme.js?v=3"></script>
<title><?= e($title ?? '') ?> · <?= e(APP_NAME) ?></title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Sans+3:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=14">
</head>
<body>
<div id="app">
  <button type="button" class="rail-open" data-rail-toggle aria-label="Afficher le menu" title="Afficher le menu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M13 10l2 2-2 2"/></svg></button>
  <aside class="rail">
    <div class="rail-top"><a class="monogram" href="/"><span class="seal">G</span><span class="mono-label">Chemini</span></a>
      <button type="button" class="rail-toggle" data-rail-toggle aria-label="Masquer le menu" title="Masquer le menu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 10l-2 2 2 2"/></svg></button></div>
    <nav>
      <?php $n = $nav ?? ''; $sp = user_spaces($u); $cls = fn($k) => $n === $k ? 'active' : ''; ?>
      <div class="group">GID</div>
      <a class="<?= $cls('dash') ?>" href="/"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('immeubles') ?>" href="/immeubles"><i class="dot"></i>Immeubles</a>
      <a class="<?= $cls('space-gid') ?>" href="/gid"><i class="dot"></i>Espace GID</a>
      <?php if (in_array('direction', $sp, true)): ?>
      <div class="group">Direction</div>
      <a class="<?= $cls('space-direction') ?>" href="/direction"><i class="dot"></i>Espace Direction</a>
      <a class="<?= $cls('honoraires') ?>" href="/honoraires"><i class="dot"></i>Honoraires</a>
      <a class="<?= $cls('compta') ?>" href="/comptabilite"><i class="dot"></i>Comptabilité</a>
      <?php endif; ?>
      <?php if (in_array('famille', $sp, true)): ?>
      <div class="group">Famille</div>
      <a class="<?= $cls('space-famille') ?>" href="/famille"><i class="dot"></i>Espace Famille</a>
      <a class="<?= $cls('voyages') ?>" href="/voyages"><i class="dot"></i>Voyages</a>
      <?php endif; ?>
      <div class="group">Personnel</div>
      <a class="<?= $cls('perso') ?>" href="/espace"><?= avatar($u, 'sm') ?>Mon espace</a>
      <div class="group">Compte</div>
      <a class="<?= $cls('secu') ?>" href="/securite"><i class="dot"></i>Sécurité</a>
      <a href="/deconnexion" class="logout-link"><i class="dot"></i>Déconnexion</a>
    </nav>
    <div class="theme-switch" role="group" aria-label="Thème"><button type="button" data-theme-set="light">Clair</button><button type="button" data-theme-set="dark">Sombre</button><button type="button" data-theme-set="auto">Auto</button></div>
    <div class="me"><?= avatar($u) ?><div><?= e($u['name']) ?><small><?= e($u['email']) ?></small></div></div>
  </aside>
  <main class="<?= !empty($wide) ? 'wide' : '' ?>"><div class="page-tools"><div class="fs-switch" role="group" aria-label="Taille du texte"><button type="button" data-fs="-1" aria-label="Réduire le texte">A−</button><span data-fs-val>100 %</span><button type="button" data-fs="1" aria-label="Agrandir le texte">A+</button></div></div><?= $content ?></main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<script src="/assets/app.js?v=11"></script>
</body>
</html>
