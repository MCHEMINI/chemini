<?php require __DIR__ . '/_head.php'; $sec = $enroll['secret']; ?>
<form class="login-card" id="enrollFlow" novalidate>
  <div class="stack lg" data-step="code">
    <div><div class="eyebrow">Activation du compte</div><h2>Bienvenue, <?= e($enroll['name']) ?>.</h2>
      <p class="sub">Associez votre application d'authentification à <strong><?= e($enroll['email']) ?></strong>. Elle servira de secours à Face ID.</p></div>

    <ol class="enroll-steps">
      <li><strong>Sur iPhone</strong> : touchez le bouton ci-dessous, puis « Ajouter » dans Mots de passe ou votre application.
        <a class="btn btn-ghost mt" href="<?= e(totp_uri($sec, $enroll['email'])) ?>">Ajouter à mon application</a></li>
      <li><strong>Sinon</strong>, dans Google Authenticator ou Microsoft Authenticator, choisissez « Saisir une clé » et copiez :
        <div class="secret mono" id="secret"><?= e(trim(chunk_split($sec, 4, ' '))) ?></div>
        <button type="button" class="btn-link" id="copySecret">Copier la clé</button></li>
      <li>Choisissez votre mot de passe (10 caractères minimum) :
        <div class="field mt"><label for="newPw">Mot de passe</label><input class="input" id="newPw" type="password" autocomplete="new-password" minlength="10"></div>
        <div class="field mt"><label for="newPw2">Confirmation</label><input class="input" id="newPw2" type="password" autocomplete="new-password" minlength="10"></div></li>
      <li>Saisissez le code à 6 chiffres affiché par l'application :</li>
    </ol>

    <div class="otp">
      <?php for ($i = 1; $i <= 6; $i++): ?><input id="o<?= $i ?>" inputmode="numeric" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" maxlength="<?= $i === 1 ? 6 : 1 ?>" aria-label="Chiffre <?= $i ?>"><?php endfor; ?>
    </div>
    <p class="form-error" hidden></p>
    <button type="button" class="btn btn-primary" id="enrollOk">Activer mon compte</button>
  </div>
  <div data-step="trust" class="stack lg" hidden>
    <div><div class="eyebrow">Compte activé</div><h2>Cet appareil est-il le vôtre ?</h2><p class="sub">Un appareil de confiance n'a plus besoin de vérification à chaque visite. Vous pourrez ensuite activer Face ID.</p></div>
    <label class="trust" for="trustDev"><input type="checkbox" id="trustDev" checked><span><strong>Faire confiance à cet appareil</strong><small>Session maintenue <?= TRUST_DAYS ?> jours. Révocable à tout moment depuis Sécurité.</small></span></label>
    <button type="button" class="btn btn-primary" id="enter" data-next="/securite?bienvenue=1">Continuer</button>
  </div>
</form>
<?php require __DIR__ . '/_foot.php'; ?>
