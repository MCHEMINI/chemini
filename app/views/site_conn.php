<?php
$h1 = 'Connexion au site'; $lead = 'Relier Chemini au site WordPress de GID pour l\'administrer depuis l\'espace';
$snap = null; require __DIR__ . '/_site_head.php';
$base = $conn ? rtrim($conn['url'], '/') : WP_DEFAULT_URL;
?>
<?php if ($conn): ?>
<div class="panel ws-conn-ok">
  <div><span class="chip ok">Relié</span> <b><?= e($conn['url']) ?></b></div>
  <p class="muted small">Compte WordPress <b><?= e($conn['user']) ?></b> · relié le <?= e(date('d/m/Y à H:i', (int)$conn['at'])) ?>. La clé est chiffrée sur le serveur de Chemini et n'est jamais réaffichée.</p>
  <?php if ($admin): ?><div class="ws-row"><button type="button" class="btn btn-ghost" data-api="site/test">Tester la connexion</button><button type="button" class="btn-link" data-api="site/disconnect" data-confirm="Délier le site ? Chemini n'y aura plus accès jusqu'à une nouvelle liaison.">Délier le site</button></div><?php endif; ?>
</div>
<?php endif; ?>
<?php if (!$admin): ?>
<p class="muted">Seul l'administrateur de Chemini peut relier ou délier le site.</p>
<?php else: ?>
<div class="ws-conn">
  <section class="panel">
    <h2><?= $conn ? 'Changer de site ou de clé' : 'Relier le site' ?></h2>
    <form id="wsConn" class="ws-form" autocomplete="off">
      <div class="field"><label for="ws-url">Adresse du site</label><input class="input" id="ws-url" name="url" type="url" required value="<?= e($base) ?>" placeholder="https://www.gidsas.fr"></div>
      <div class="field"><label for="ws-user">Identifiant WordPress</label><input class="input" id="ws-user" name="user" required value="<?= e((string)($conn['user'] ?? '')) ?>" autocomplete="off" autocapitalize="none" spellcheck="false"></div>
      <div class="field"><label for="ws-pass">Mot de passe d'application</label><input class="input mono" id="ws-pass" name="pass" type="password" required autocomplete="off" data-lpignore="true" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"></div>
      <p class="form-error" hidden></p>
      <button class="btn btn-primary">Tester et relier</button>
    </form>
  </section>
  <section class="panel ws-how">
    <h2>Créer le mot de passe d'application</h2>
    <ol>
      <li>Dans WordPress, ouvrez <b>Comptes › Profil</b>. <a class="gd-btn" href="<?= e($base) ?>/wp-admin/profile.php#application-passwords-section" target="_blank" rel="noopener">Ouvrir mon profil WordPress →</a></li>
      <li>Tout en bas, section <b>Mots de passe d'application</b> : nommez-le « Chemini » puis cliquez sur <b>Ajouter</b>.</li>
      <li>Copiez la clé affichée (24 caractères) et collez-la ici avec votre identifiant WordPress.</li>
    </ol>
    <p class="muted small">Cette clé ne permet pas de se connecter à l'écran de WordPress ; elle se révoque à tout moment depuis le même profil. Utilisez un compte administrateur pour gérer les annonces et lire l'état des extensions.</p>
  </section>
</div>
<?php endif; ?>
<?php if ($admin): $pos = wp_posters(); ?>
<section class="panel ws-posters">
  <h2>Accès à la section Site Web</h2>
  <p class="muted small">En plus de la Direction, ces personnes ont accès à toute la section Site Web (statistiques, fréquentation, annonces et appli Annonces, pages et SEO), sauf la connexion au site. Une personne qui n'apparaît pas ici n'a pas encore de compte Chemini : invitez-la depuis Sécurité › Équipe et accès.</p>
  <form id="wsPos" class="ws-pos">
    <?php foreach (users_all() as $x): if (wp_viewer($x)) continue; ?><label class="na-check"><input type="checkbox" name="ids" value="<?= (int)$x['id'] ?>"<?= in_array((int)$x['id'], $pos, true) ? ' checked' : '' ?>> <?= e($x['name']) ?> <span class="muted small"><?= e((string)$x['email']) ?></span></label><?php endforeach; ?>
    <button class="btn btn-ghost">Enregistrer les accès</button>
  </form>
</section>
<?php endif; ?>
