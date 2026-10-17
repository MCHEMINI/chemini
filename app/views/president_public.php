<?php $src = json_decode((string)$w['sources'], true) ?: []; ?><!doctype html>
<html lang="fr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<script src="/assets/theme.js?v=3"></script>
<title><?= e($w['title']) ?> · GID</title>
<meta property="og:title" content="<?= e($w['title']) ?>">
<meta property="og:description" content="<?= e((string)$w['chapo']) ?>">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Sans+3:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=62">
</head>
<body class="pw-public">
<div class="pw-pub-top"><span class="seal">G</span><span>GID · Administrateur de biens</span></div>
<main class="pw-pub">
  <article class="pw-art panel">
    <div class="pw-meta"><span class="eyebrow">Mot du Président · <?= e(pw_month_label($w['month'])) ?></span></div>
    <h1 class="pw-title"><?= e($w['title']) ?></h1>
    <?php if ($w['chapo']): ?><p class="pw-chapo"><?= e((string)$w['chapo']) ?></p><?php endif; ?>
    <div class="pw-body-r"><?= pw_render($w['body']) ?></div>
    <div class="pw-sign"><span class="ag-av">MC</span><span><b>Mehdi Chemini</b><small>Président de GID</small></span></div>
    <?php if ($src): ?><details class="pw-src"><summary>Sources (<?= count($src) ?>)</summary><ul><?php foreach ($src as $s): if (!preg_match('#^https?://#i', (string)($s['url'] ?? ''))) continue; ?><li><a href="<?= e($s['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer"><?= e($s['title'] ?? $s['url'] ?? '') ?></a></li><?php endforeach; ?></ul></details><?php endif; ?>
  </article>
  <p class="pw-pub-foot"><a href="https://www.gidsas.fr/">www.gidsas.fr</a></p>
</main>
</body>
</html>
