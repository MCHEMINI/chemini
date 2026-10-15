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
<link rel="stylesheet" href="/assets/app.css?v=3">
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
