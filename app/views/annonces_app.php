<!doctype html>
<html lang="fr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf" content="<?= e($_SESSION['csrf'] ?? '') ?>">
<meta name="theme-color" content="#121720">
<script src="/assets/theme.js?v=3"></script>
<title>Annonces · GID</title>
<link rel="manifest" href="/annonces/manifest.webmanifest">
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Annonces GID">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="stylesheet" href="/assets/app.css?v=<?= (int)$cssV ?>">
</head>
<body class="aa">
<header class="aa-top">
  <a class="aa-brand" href="/annonces/" data-go="list"><span class="seal">G</span><span>Annonces</span></a>
  <span class="aa-net" id="aaNet" title="État de la connexion"><i></i><b>En ligne</b></span>
  <button type="button" class="aa-btn" id="aaInstall" hidden>Installer</button>
  <a class="aa-btn ghost" href="/site-web/annonces" title="Revenir à Chemini">Chemini</a>
</header>
<main class="aa-main">
  <div class="aa-banner" id="aaQueue" hidden></div>
  <section id="aaList" class="aa-view">
    <div class="aa-h"><div><h1>Annonces</h1><p class="muted small" id="aaSync">…</p></div><button type="button" class="btn btn-primary" data-go="new">+ Nouvelle</button></div>
    <label class="gd-search" for="aaQ"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="aaQ" type="search" placeholder="Rechercher une annonce…" autocomplete="off"></label>
    <div class="ws-chips" id="aaChips" role="group" aria-label="Filtrer par statut"></div>
    <div class="aa-cards" id="aaCards"></div>
    <p class="empty" id="aaEmpty" hidden><strong>Aucune annonce.</strong>Touchez « + Nouvelle » pour en créer une.</p>
    <details class="aa-help"><summary>Installer l'appli sur le téléphone</summary>
      <p class="small"><b>iPhone :</b> dans Safari, touchez Partager puis « Sur l'écran d'accueil ». <b>Android :</b> bouton « Installer » en haut, ou menu ⋮ › « Installer l'application ». L'appli fonctionne sans réseau : les annonces saisies hors connexion partent dès que le réseau revient.</p>
    </details>
  </section>
  <section id="aaForm" class="aa-view" hidden>
    <div class="aa-h"><div><button type="button" class="btn-link" data-go="list">‹ Annonces</button><h1 id="aaFormH">Nouvelle annonce</h1></div><button type="button" class="btn-link aa-del" id="aaDel" hidden>Supprimer</button></div>
    <?php require __DIR__ . '/_annonce_form.php'; ?>
  </section>
</main>
<div id="toast" class="toast" role="status" hidden></div>
<script src="/assets/annonces.js?v=<?= (int)$jsV ?>"></script>
</body>
</html>
