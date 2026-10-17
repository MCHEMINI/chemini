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
<link rel="stylesheet" href="/assets/app.css?v=40">
</head>
<body>
<div id="app">
  <button type="button" class="rail-open" data-rail-toggle aria-label="Afficher le menu" title="Afficher le menu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M13 10l2 2-2 2"/></svg></button>
  <aside class="rail">
    <div class="rail-top"><a class="monogram" href="/"><span class="seal">G</span><span class="mono-label">Chemini</span></a>
      <button type="button" class="rail-toggle" data-rail-toggle aria-label="Masquer le menu" title="Masquer le menu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 10l-2 2 2 2"/></svg></button>
      <button type="button" class="burger" data-burger aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="railNav"><span></span><span></span><span></span></button></div>
    <nav id="railNav">
      <?php $n = $nav ?? ''; $sp = user_spaces($u); $cls = fn($k) => $n === $k ? 'active' : '';
      /* Titre de section + nombre de personnes ayant accès (clic : liste en fenêtre) */
      $grp = function (string $label, string $id, array $members): string {
          $c = count($members);
          $h = '<div class="group"><span>' . e($label) . '</span><button type="button" class="grp-n" data-grp="' . e($id) . '" aria-expanded="false" aria-controls="grp-' . e($id) . '" title="' . $c . ' personne' . ($c > 1 ? 's' : '') . ' ont accès">' . $c . '</button></div>';
          $h .= '<div class="grp-pop" id="grp-' . e($id) . '" role="dialog" aria-label="Accès ' . e($label) . '" hidden><div class="grp-pop-h">' . e($label) . ' · ' . $c . ' accès</div><ul>';
          foreach ($members as $m) $h .= '<li>' . avatar($m, 'sm') . '<div><strong>' . e($m['name']) . '</strong><span>' . e($m['email'] ?? '') . '</span></div></li>';
          return $h . '</ul></div>';
      }; ?>
      <?= $grp('Équipe', 'gid', space_members('gid')) ?>
      <a class="<?= $cls('dash') ?>" href="/"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('space-gid') ?>" href="/gid"><i class="dot"></i>Informations</a>
      <a class="<?= $cls('team') ?>" href="/equipe"><i class="dot"></i>Collaborateurs</a>
      <a class="<?= $cls('orga') ?>" href="/organigramme"><i class="dot"></i>Organigramme</a>
      <a class="<?= $cls('accueil') ?>" href="/accueil"><i class="dot"></i>Accueil</a>
      <?php if (in_array('direction', $sp, true)): ?>
      <?= $grp('Direction', 'direction', space_members('direction')) ?>
      <a class="<?= $cls('space-direction') ?>" href="/direction"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('immeubles') ?>" href="/immeubles"><i class="dot"></i>Immeubles</a>
      <a class="<?= $cls('ag') ?>" href="/planning-ag"><i class="dot"></i>Planning des AG</a>
      <a class="<?= $cls('honoraires') ?>" href="/honoraires"><i class="dot"></i>Honoraires</a>
      <a class="<?= $cls('compta') ?>" href="/comptabilite"><i class="dot"></i>Comptabilité</a>
      <a class="<?= $cls('strategies') ?>" href="/strategies"><i class="dot"></i>Stratégies</a>
      <a class="<?= $cls('pilot') ?>" href="/pilotage"><i class="dot"></i>Pilotage</a>
      <a class="<?= $cls('survey') ?>" href="/enquete/resultats"><i class="dot"></i>Enquête</a>
      <a class="<?= $cls('sig') ?>" href="/signatures"><i class="dot"></i>Signatures</a>
      <?php endif; ?>
      <?php if (in_array('famille', $sp, true)): ?>
      <?= $grp('Famille', 'famille', space_members('famille')) ?>
      <a class="<?= $cls('space-famille') ?>" href="/famille"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('voyages') ?>" href="/voyages"><i class="dot"></i>Voyages</a>
      <?php endif; ?>
      <?= $grp('Personnel', 'perso', [$u]) ?>
      <a class="<?= $cls('perso') ?>" href="/espace"><?= avatar($u, 'sm') ?>Mon espace</a>
      <a class="<?= $cls('tasks') ?>" href="/mes-taches"><i class="dot"></i>Mes tâches<?php $nT = ($meK = team_me($u)) ? (int)q('SELECT COUNT(*) FROM team_tasks WHERE person=? AND done_at IS NULL', [$meK])->fetchColumn() : 0; if ($nT): ?> <span class="nav-n"><?= $nT ?></span><?php endif; ?></a>
      <a class="<?= $cls('notes') ?>" href="/bloc-notes"><i class="dot"></i>Bloc-notes</a>
      <a class="<?= $cls('books') ?>" href="/mes-livres"><i class="dot"></i>Mes livres</a>
      <a href="/assistant" class="nav-ia <?= $cls('ia') ?>"><i class="dot"></i>Tâches IA<?php $nIa = (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status<>'done'", [(int)$u['id']])->fetchColumn(); if ($nIa): ?> <span class="nav-n"><?= $nIa ?></span><?php endif; ?></a>
      <a class="<?= $cls('versioning') ?>" href="/versioning"><i class="dot"></i>Versioning</a>
    </nav>
    <div class="theme-switch" role="group" aria-label="Thème"><button type="button" data-theme-set="light">Clair</button><button type="button" data-theme-set="dark">Sombre</button><button type="button" data-theme-set="auto">Auto</button></div>
    <div class="me"><?= avatar($u) ?><div class="me-id"><?= e($u['name']) ?><small><?= e($u['email']) ?></small></div><a href="/securite" class="me-out<?= $n === 'secu' ? ' on' : '' ?>" title="Sécurité du compte" aria-label="Sécurité du compte"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></a><a href="/deconnexion" class="me-out" title="Se déconnecter" aria-label="Se déconnecter"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></a></div>
  </aside>
  <main class="<?= !empty($wide) ? 'wide' : '' ?>"><div class="page-tools"><div class="fs-switch" role="group" aria-label="Taille du texte"><button type="button" data-fs="-1" aria-label="Réduire le texte">A−</button><span data-fs-val>100 %</span><button type="button" data-fs="1" aria-label="Agrandir le texte">A+</button></div></div><?= $content ?></main>
</div>
<div class="ia-fab-wrap">
  <div class="ia-pop panel" id="iaPop" hidden role="dialog" aria-labelledby="iaPopT">
    <div class="sec-h flat"><h3 id="iaPopT">Nouvelle consigne</h3><button type="button" class="btn-link" data-ia-close aria-label="Fermer">✕</button></div>
    <form data-api="consignes/create" data-toast="Consigne envoyée : l'assistant la traitera au prochain passage horaire." class="ia-form">
      <label for="iaBody" class="sr">Consigne</label>
      <textarea class="input" id="iaBody" name="body" maxlength="4000" rows="4" placeholder="Décrivez ce que l'assistant doit faire…" required></textarea>
      <div class="ia-form-row">
        <label for="iaPrio" class="small muted">Priorité</label>
        <select class="input" id="iaPrio" name="priority"><?php foreach (CONSIGNE_PRIO as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === 'normale' ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <label for="iaSpace" class="small muted">Espace</label>
        <select class="input" id="iaSpace" name="space"><?php foreach (consigne_spaces($u) as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary">Envoyer</button>
      </div>
      <p class="muted small">Visible par vous seul · <a href="/assistant">voir mes Tâches IA</a></p>
    </form>
  </div>
  <button type="button" class="ia-fab" id="iaFab" aria-expanded="false" aria-controls="iaPop" title="Tâches IA : donner une consigne"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg><?php if (!empty($nIa)): ?><span class="ia-fab-n"><?= (int)$nIa ?></span><?php endif; ?></button>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<script src="/assets/app.js?v=28"></script>
</body>
</html>
