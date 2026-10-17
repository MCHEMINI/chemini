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
<meta name="apple-mobile-web-app-title" content="Dashboard">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Source+Sans+3:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/assets/app.css?v=80">
</head>
<body>
<div id="app">
  <button type="button" class="rail-open" data-rail-toggle aria-label="Afficher le menu" title="Afficher le menu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M13 10l2 2-2 2"/></svg></button>
  <aside class="rail">
    <div class="rail-top"><a class="monogram" href="/"><span class="seal">G</span><span class="mono-label">Dashboard</span></a>
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
      <?= $grp('Perso', 'perso', [$u]) ?>
      <a class="<?= $cls('home') ?>" href="/"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('perso') ?>" href="/espace"><i class="dot"></i>Mails à traiter<?php $meK = team_me($u); [$nM, $nT, $nIa] = array_map('intval', q("SELECT (SELECT COUNT(*) FROM mail_items WHERE user_id=:u AND done=0), (SELECT COUNT(*) FROM team_tasks WHERE person=:p AND done_at IS NULL), (SELECT COUNT(*) FROM consignes WHERE user_id=:u AND status<>'done')", [':u' => (int)$u['id'], ':p' => (string)$meK])->fetch(PDO::FETCH_NUM)); if ($nM): ?> <span class="nav-n crit"><?= $nM ?></span><?php endif; ?></a>
      <a class="<?= $cls('mot') ?>" href="/mot-du-president"><i class="dot"></i>Mot du Président</a>
      <a class="<?= in_array($n, ['tasks', 'ia'], true) ? 'active' : '' ?>" href="/mes-taches"><i class="dot"></i>Tâches<?php if ($nT + $nIa): ?> <span class="nav-n"><?= $nT + $nIa ?></span><?php endif; ?></a>
      <a class="<?= in_array($n, ['notes', 'books'], true) ? 'active' : '' ?>" href="/bloc-notes"><i class="dot"></i>Notes et livres</a>
      <?php if (in_array('direction', $sp, true)): ?>
      <?= $grp('Direction', 'direction', space_members('direction')) ?>
      <a class="<?= $cls('space-direction') ?>" href="/direction"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= in_array($n, ['immeubles', 'ag'], true) ? 'active' : '' ?>" href="/immeubles"><i class="dot"></i>Immeubles</a>
      <a class="<?= in_array($n, ['honoraires', 'strategies'], true) ? 'active' : '' ?>" href="/honoraires"><i class="dot"></i>Honoraires</a>
      <a class="<?= $cls('compta') ?>" href="/comptabilite"><i class="dot"></i>Comptabilité</a>
      <a class="<?= in_array($n, ['pilot', 'survey'], true) ? 'active' : '' ?>" href="/pilotage"><i class="dot"></i>Pilotage</a>
      <a class="<?= $cls('sig') ?>" href="/signatures"><i class="dot"></i>Signatures</a>
      <?php endif; ?>
      <?= $grp('Équipe', 'gid', space_members('gid')) ?>
      <a class="<?= $cls('dash') ?>" href="/tableau-de-bord"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= in_array($n, ['space-gid', 'accueil'], true) ? 'active' : '' ?>" href="/gid"><i class="dot"></i>Informations</a>
      <a class="<?= $cls('orga') ?><?= ($nav ?? '') === 'team' ? ' active' : '' ?>" href="/organigramme"><i class="dot"></i>Collaborateurs</a>
      <a class="<?= $cls('guides') ?>" href="/guides"><i class="dot"></i>Guides</a>
      <?php if (in_array('famille', $sp, true)): ?>
      <?= $grp('Famille', 'famille', space_members('famille')) ?>
      <a class="<?= $cls('space-famille') ?>" href="/famille"><i class="dot"></i>Tableau de bord</a>
      <a class="<?= $cls('voyages') ?>" href="/voyages"><i class="dot"></i>Voyages</a>
      <?php endif; ?>
      <?php if (wp_poster($u)): $wpV = wp_viewer($u); ?>
      <?= $grp('Site Web', 'siteweb', array_values(array_filter(users_all(), fn($x) => wp_poster($x)))) ?>
      <a class="<?= $cls('site-stats') ?>" href="/site-web"><i class="dot"></i>Statistiques</a>
      <a class="<?= in_array($n, ['site-ann', 'site-new'], true) ? 'active' : '' ?>" href="/site-web/annonces"><i class="dot"></i>Annonces</a>
      <a class="<?= $cls('site-pages') ?>" href="/site-web/pages"><i class="dot"></i>Pages et SEO</a>
      <?php if ($wpV): ?><a class="<?= $cls('site-conn') ?>" href="/site-web/connexion"><i class="dot"></i>Connexion</a><?php endif; ?>
      <?php endif; ?>
    </nav>
    <div class="rail-picto" role="group" aria-label="Raccourcis">
      <a href="/securite" class="<?= $n === 'secu' ? 'on' : '' ?>" title="Sécurité" aria-label="Sécurité"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></a>
      <a href="/versioning" class="<?= $n === 'versioning' ? 'on' : '' ?>" title="Versioning" aria-label="Versioning"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></a>
      <a href="/installer" class="<?= $n === 'install' ? 'on' : '' ?>" title="Installer sur iPhone" aria-label="Installer sur iPhone"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg></a>
      <span class="rp-sep" aria-hidden="true"></span>
      <span class="rp-theme" role="group" aria-label="Thème">
        <button type="button" data-theme-set="light" title="Mode clair" aria-label="Mode clair"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button>
        <button type="button" data-theme-set="dark" title="Mode sombre" aria-label="Mode sombre"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg></button>
        <button type="button" data-theme-set="auto" title="Automatique (selon l'appareil)" aria-label="Thème automatique"><svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg></button>
      </span>
    </div>
    <div class="rail-fs fs-switch" role="group" aria-label="Taille du texte"><span class="rail-fs-l">Taille du texte</span><button type="button" data-fs="-1" aria-label="Réduire le texte">A−</button><span data-fs-val>100 %</span><button type="button" data-fs="1" aria-label="Agrandir le texte">A+</button></div>
    <div class="me"><?= avatar($u) ?><div class="me-id"><?= e($u['name']) ?><small><?= e($u['email']) ?></small></div><form method="post" action="/deconnexion" class="me-out-f"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>"><button type="submit" class="me-out" title="Se déconnecter" aria-label="Se déconnecter"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg></button></form></div>
  </aside>
  <main class="<?= !empty($wide) ? 'wide' : '' ?>"><div class="page-tools"><?php if (in_array($nav ?? '', ['home', 'dash', 'space-direction', 'space-gid', 'space-famille', 'news', 'search'], true)): ?><form class="gs" action="/recherche" method="get" role="search"><label class="gd-search gs-box" for="gsQ"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="gsQ" name="q" type="search" value="<?= ($nav ?? '') === 'search' ? e((string)($q ?? '')) : '' ?>" placeholder="Rechercher sur toute la plateforme…" autocomplete="off" aria-label="Rechercher sur toute la plateforme" aria-controls="gsDrop" aria-expanded="false"></label><div class="gs-drop" id="gsDrop" hidden></div></form><?php endif; ?><div class="fs-switch" role="group" aria-label="Taille du texte"><button type="button" data-fs="-1" aria-label="Réduire le texte">A−</button><span data-fs-val>100 %</span><button type="button" data-fs="1" aria-label="Agrandir le texte">A+</button></div></div><?php /* Pages regroupées en onglets : une entrée de menu, plusieurs pages (toutes les adresses restent valables) */
    $isDirU = in_array('direction', user_spaces($u), true);
    $TABS = [
      [['tasks', '/mes-taches', 'Mes tâches'], ['ia', '/assistant', 'Tâches IA']],
      [['notes', '/bloc-notes', 'Bloc-notes'], ['books', '/mes-livres', 'Mes livres']],
      $isDirU ? [['immeubles', '/immeubles', 'Immeubles'], ['ag', '/planning-ag', 'Planning des AG']] : [],
      $isDirU ? [['honoraires', '/honoraires', 'Honoraires'], ['strategies', '/strategies', 'Stratégies']] : [],
      $isDirU ? [['pilot', '/pilotage', 'Pilotage'], ['survey', '/enquete/resultats', 'Enquête']] : [],
      [['space-gid', '/gid', 'Informations'], ['accueil', '/accueil', 'Accueil']],
    ];
    foreach ($TABS as $T) { if (!in_array($n, array_column($T, 0), true)) continue;
      echo '<nav class="page-tabs" aria-label="Onglets de la page">';
      foreach ($T as [$k, $href, $lab]) echo '<a href="' . e($href) . '"' . ($k === $n ? ' class="on" aria-current="page"' : '') . '>' . e($lab) . '</a>';
      echo '</nav>'; break; } ?><?= $content ?></main>
</div>
<div class="ia-fab-wrap">
  <div class="ia-pop panel" id="iaPop" hidden role="dialog" aria-labelledby="iaPopT">
    <div class="fab-h"><h3 id="iaPopT">Que voulez-vous lancer ?</h3><button type="button" class="btn-link" data-ia-close aria-label="Fermer">✕</button></div>
    <div class="fab-tabs" role="tablist">
      <button type="button" role="tab" aria-selected="true" data-fab-tab="ia"><span class="fab-emo">✨</span><b>Tâche IA</b><small>l'assistant s'en charge</small></button>
      <button type="button" role="tab" aria-selected="false" data-fab-tab="team"><span class="fab-emo">🤝</span><b>Tâche équipe</b><small>pour vous ou un collègue</small></button>
    </div>
    <form data-api="consignes/create" data-toast="✨ Consigne envoyée : l'assistant la traite au prochain passage." class="ia-form fab-pane" data-fab-pane="ia">
      <label for="iaBody" class="sr">Consigne</label>
      <textarea class="input" id="iaBody" name="body" maxlength="4000" rows="4" placeholder="Ex. Prépare un courrier de relance pour l'immeuble 00421…" required></textarea>
      <div class="ia-form-row">
        <label for="iaPrio" class="small muted">Priorité</label>
        <select class="input" id="iaPrio" name="priority"><?php foreach (CONSIGNE_PRIO as $k => $l): ?><option value="<?= e($k) ?>"<?= $k === 'normale' ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
        <label for="iaSpace" class="small muted">Espace</label>
        <select class="input" id="iaSpace" name="space"><?php foreach (consigne_spaces($u) as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select>
        <button class="btn btn-primary">Envoyer</button>
      </div>
      <p class="muted small">Visible par vous seul · <a href="/assistant">voir mes Tâches IA</a></p>
    </form>
    <form class="fab-pane fab-team" id="fabTeam" data-fab-pane="team" hidden>
      <?php $fp = team_people(); $fme = team_me($u); $ini2 = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: '')); ?>
      <div class="fab-lbl" id="fabWhoL">Pour qui ?</div>
      <?php $fmeP = $fme && isset($fp[$fme]) ? $fp[$fme] : null; ?>
      <button type="button" class="fab-who" id="fabWho" aria-haspopup="listbox" aria-expanded="false" aria-controls="fabPList" aria-labelledby="fabWhoL fabWhoN">
        <span class="ag-av" data-who-av><?= $fmeP ? 'Moi' : '?' ?></span><span class="fab-who-t"><b id="fabWhoN" data-who-n><?= $fmeP ? 'Moi' : 'Choisir une personne' ?></b><small data-who-r><?= $fmeP ? 'Mes tâches' : '' ?></small></span>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
      <div class="fab-plist" id="fabPList" hidden>
        <input type="search" class="fab-psearch" placeholder="Rechercher un collègue…" aria-label="Rechercher un collègue" autocomplete="off">
        <div class="fab-people" role="radiogroup" aria-label="Personne">
          <?php if ($fmeP): ?><label class="fab-p me" data-first="Moi" data-role="Mes tâches" data-s="moi <?= e(mb_strtolower($fmeP['name'])) ?>"><input type="radio" name="person" value="<?= e($fme) ?>" checked><span class="ag-av">Moi</span><span class="fab-p-n"><b>Moi</b><small>Mes tâches</small></span></label><?php endif; ?>
          <?php $fpA = $fp; $fkey = fn($x) => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $x['name']) ?: $x['name']); uasort($fpA, fn($a, $b) => strcmp($fkey($a), $fkey($b))); // ordre alphabétique (accents ignorés)
          foreach ($fpA as $pk => $pp): if ($pk === $fme) continue; ?><label class="fab-p" data-first="<?= e(strtok($pp['name'], ' ')) ?>" data-role="<?= e($pp['role']) ?>" data-s="<?= e(mb_strtolower($pp['name'] . ' ' . $pp['role'])) ?>"><input type="radio" name="person" value="<?= e($pk) ?>"><span class="ag-av"><?= e($ini2($pp['name'])) ?></span><span class="fab-p-n"><b><?= e($pp['name']) ?></b><small><?= e($pp['role']) ?></small></span></label><?php endforeach; ?>
          <p class="muted small fab-pnone" hidden>Aucun collègue trouvé.</p>
        </div>
      </div>
      <label for="fabT" class="fab-lbl">Quoi ?</label>
      <input class="input" id="fabT" name="title" maxlength="300" placeholder="Ex. Rappeler le président du conseil syndical" required>
      <div class="fab-lbl">Pour quand ?</div>
      <div class="fab-due" role="radiogroup" aria-label="Échéance">
        <?php foreach (['' => 'Sans date', date('Y-m-d') => 'Aujourd\'hui', date('Y-m-d', strtotime('+1 day')) => 'Demain', date('Y-m-d', strtotime('friday this week')) => 'Vendredi', date('Y-m-d', strtotime('+7 days')) => 'Dans 7 jours'] as $dv => $dl): ?><label><input type="radio" name="due" value="<?= e($dv) ?>"<?= $dv === '' ? ' checked' : '' ?>><span><?= e($dl) ?></span></label><?php endforeach; ?>
        <label class="fab-due-other"><input type="radio" name="due" value="autre"><span data-due-other>Autre date…</span></label><input type="date" class="fab-date" name="due_other" min="<?= date('Y-m-d') ?>" aria-label="Choisir une date" tabindex="-1">
      </div>
      <p class="form-error" hidden></p>
      <div class="fab-foot"><a href="/organigramme" class="small">Voir les collaborateurs</a><button class="btn btn-primary">Affecter 🚀</button></div>
    </form>
  </div>
  <button type="button" class="ia-fab" id="iaFab" aria-expanded="false" aria-controls="iaPop" title="Créer une tâche IA ou une tâche d'équipe"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg><?php if (!empty($nIa)): ?><span class="ia-fab-n"><?= (int)$nIa ?></span><?php endif; ?></button>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>
<script src="/assets/app.js?v=56"></script>
</body>
</html>
