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
<link rel="manifest" href="/assets/manifest.webmanifest">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Chemini">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Sans+3:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=68">
</head>
<body>
<div id="login">
  <aside class="brand-panel">
    <div class="monogram"><span class="seal">G</span><span class="mono-label">GID SAS · Chemini</span></div>
    <div class="brand-quote">
      <h1>Le cabinet,<br><em>en un regard.</em></h1>
      <p>Copropriétés, échéances, courriers et veille juridique réunis dans un espace privé, réservé à l'équipe.</p>
    </div>
    <div class="brand-foot">chemini.gidsas.fr · Accès restreint</div>
  </aside>
  <div class="login-side">
    <div class="login-theme"><div class="theme-switch" role="group" aria-label="Thème"><button type="button" data-theme-set="light">Clair</button><button type="button" data-theme-set="dark">Sombre</button><button type="button" data-theme-set="auto">Auto</button></div></div>
