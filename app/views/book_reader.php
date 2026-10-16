<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e($book['title']) ?> · <?= e(APP_NAME) ?></title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Literata:ital,opsz,wght@0,7..72,400;0,7..72,600;1,7..72,400&family=Source+Sans+3:wght@400;600&display=swap">
<link rel="stylesheet" href="/assets/reader.css?v=1">
</head>
<body class="rd" data-theme="white">
<script type="application/json" id="bookCfg"><?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<header class="rd-top" id="rdTop">
  <a class="rd-ic" href="/mes-livres" title="Bibliothèque" aria-label="Retour à la bibliothèque"><svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg></a>
  <button class="rd-ic" type="button" data-panel="toc" title="Sommaire, signets, notes" aria-label="Sommaire, signets et notes"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></svg></button>
  <div class="rd-title"><strong><?= e($book['title']) ?></strong><span id="rdChap"></span></div>
  <button class="rd-ic" type="button" data-panel="search" title="Rechercher dans le livre" aria-label="Rechercher"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="M20 20l-4.5-4.5"/></svg></button>
  <button class="rd-ic rd-aa" type="button" data-panel="aa" title="Affichage" aria-label="Réglages d'affichage">Aa</button>
  <button class="rd-ic" type="button" id="rdFull" title="Plein écran" aria-label="Plein écran"><svg viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg></button>
  <button class="rd-ic rd-bm" type="button" id="rdBm" title="Ajouter un signet" aria-label="Signet" aria-pressed="false"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"/></svg></button>
</header>

<main class="rd-stage" id="rdStage">
  <?php if ($book['kind'] === 'pdf'): ?>
  <iframe class="rd-pdf" src="<?= e($cfg['file']) ?>#view=FitH" title="<?= e($book['title']) ?>"></iframe>
  <?php else: ?>
  <iframe class="rd-frame" id="rdFrame" title="<?= e($book['title']) ?>"></iframe>
  <div class="rd-ribbon" id="rdRibbon" hidden></div>
  <div class="rd-loading" id="rdLoading"><span></span>Ouverture du livre…</div>
  <?php endif; ?>
</main>

<?php if ($book['kind'] === 'epub'): ?>
<footer class="rd-bot" id="rdBot">
  <span id="rdLeft"></span>
  <input type="range" id="rdSlider" min="0" max="1000" value="0" aria-label="Aller à la position">
  <span id="rdPct">0 %</span>
</footer>

<aside class="rd-panel" id="pnl-toc" hidden>
  <nav class="rd-tabs" role="tablist">
    <button type="button" class="on" data-tab="t-toc">Sommaire</button>
    <button type="button" data-tab="t-bm">Signets</button>
    <button type="button" data-tab="t-hl">Notes et surlignements</button>
  </nav>
  <div class="rd-tab" id="t-toc"></div>
  <div class="rd-tab" id="t-bm" hidden></div>
  <div class="rd-tab" id="t-hl" hidden></div>
</aside>

<aside class="rd-panel" id="pnl-search" hidden>
  <form id="rdSearch" class="rd-sform"><input type="search" id="rdQ" placeholder="Rechercher dans le livre" autocomplete="off"><button type="submit">OK</button></form>
  <div id="rdResults" class="rd-res"></div>
</aside>

<aside class="rd-pop" id="pnl-aa" hidden>
  <div class="rd-row"><span>Taille</span><button type="button" data-set="size" data-d="-1" aria-label="Réduire">A−</button><output id="oSize"></output><button type="button" data-set="size" data-d="1" aria-label="Agrandir">A+</button></div>
  <div class="rd-row"><span>Police</span><select id="sFont"><option value="orig">Éditeur</option><option value="literata">Literata</option><option value="lora">Lora</option><option value="georgia">Georgia</option><option value="sans">Source Sans</option><option value="verdana">Verdana</option></select></div>
  <div class="rd-row"><span>Interligne</span><button type="button" data-set="lh" data-v="1.3">Serré</button><button type="button" data-set="lh" data-v="1.55">Normal</button><button type="button" data-set="lh" data-v="1.85">Aéré</button></div>
  <div class="rd-row"><span>Marges</span><button type="button" data-set="mg" data-v="0">Étroites</button><button type="button" data-set="mg" data-v="1">Normales</button><button type="button" data-set="mg" data-v="2">Larges</button></div>
  <div class="rd-row"><span>Colonnes</span><button type="button" data-set="cols" data-v="auto">Auto</button><button type="button" data-set="cols" data-v="1">Une</button><button type="button" data-set="cols" data-v="2">Deux</button></div>
  <div class="rd-row"><span>Justifier</span><button type="button" data-set="just" data-v="1">Oui</button><button type="button" data-set="just" data-v="0">Non</button></div>
  <div class="rd-row rd-themes"><span>Thème</span><button type="button" data-set="theme" data-v="white" class="th-white" title="Blanc">Aa</button><button type="button" data-set="theme" data-v="sepia" class="th-sepia" title="Sépia">Aa</button><button type="button" data-set="theme" data-v="green" class="th-green" title="Vert">Aa</button><button type="button" data-set="theme" data-v="dark" class="th-dark" title="Noir">Aa</button></div>
</aside>

<div class="rd-sel" id="rdSel" hidden>
  <div class="rd-colors"><button type="button" data-hl="yellow" title="Surligner en jaune"></button><button type="button" data-hl="blue" title="Bleu"></button><button type="button" data-hl="pink" title="Rose"></button><button type="button" data-hl="orange" title="Orange"></button></div>
  <button type="button" data-act="note">Note</button><button type="button" data-act="copy">Copier</button><button type="button" data-act="define">Définition</button><button type="button" data-act="search">Rechercher</button><button type="button" data-act="del" hidden>Supprimer</button>
</div>

<dialog class="rd-dlg" id="rdNote"><form method="dialog"><h3>Note</h3><blockquote id="rdNoteQ"></blockquote><textarea id="rdNoteT" rows="5" maxlength="4000" placeholder="Votre note"></textarea>
<div class="rd-dlg-r"><button value="cancel" formnovalidate>Annuler</button><button value="ok" class="pri">Enregistrer</button></div></form></dialog>

<div class="rd-toast" id="rdToast" hidden></div>
<script src="/assets/vendor/jszip.min.js"></script>
<script src="/assets/epub-lib.js?v=1"></script>
<script src="/assets/reader.js?v=1"></script>
<?php endif; ?>
</body>
</html>
