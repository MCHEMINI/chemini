<?php require __DIR__ . '/_head.php'; ?>
<form class="login-card" id="loginFlow" data-start="<?= e($startStep ?? 'main') ?>" novalidate>
  <div class="steps" aria-hidden="true"><i class="on"></i><i></i><i></i></div>

  <div data-step="main" class="stack lg">
    <div><div class="eyebrow">Connexion</div><h2><?= greeting() ?>.</h2><p class="sub" id="modeHint">Identifiez-vous avec votre adresse professionnelle et votre mot de passe.</p></div>
    <div class="field"><label for="email">Adresse e-mail</label><input class="input" id="email" name="email" type="email" autocomplete="username webauthn" required></div>
    <div class="field" id="pwField"><label for="password">Mot de passe</label><input class="input" id="password" name="password" type="password" autocomplete="current-password"></div>
    <p class="form-error" hidden></p>
    <button type="submit" class="btn btn-primary" id="mainBtn">Se connecter</button>

    <div class="modes" role="radiogroup" aria-label="Mode de connexion">
      <button type="button" class="mode on" data-mode="password" role="radio" aria-checked="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M15 8l2 2"/></svg>
        <span>Mot de passe</span>
      </button>
      <button type="button" class="mode" data-mode="faceid" role="radio" aria-checked="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M9 9v1M15 9v1M12 9v4h-1M9.5 16a3.5 3.5 0 0 0 5 0"/></svg>
        <span>Face ID</span>
      </button>
      <button type="button" class="mode" data-mode="link" role="radio" aria-checked="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
        <span>Lien par e-mail</span>
      </button>
    </div>
  </div>

  <div data-step="totp" class="stack lg" hidden>
    <div><div class="eyebrow">Double vérification</div><h2>Code à 6 chiffres</h2><p class="sub">Ouvrez votre application d'authentification.</p></div>
    <div class="otp">
      <?php for ($i = 1; $i <= 6; $i++): ?><input id="o<?= $i ?>" inputmode="numeric" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" maxlength="<?= $i === 1 ? 6 : 1 ?>" aria-label="Chiffre <?= $i ?>"><?php endfor; ?>
    </div>
    <p class="form-error" hidden></p>
    <button type="button" class="btn btn-primary" id="totpOk">Vérifier</button>
    <button type="button" class="btn-link" data-back>Retour</button>
  </div>

  <div data-step="sent" class="stack lg" hidden>
    <div><div class="eyebrow">Lien envoyé</div><h2>Consultez votre boîte mail</h2><p class="sub" id="sentTo">Si un compte correspond à cette adresse, un lien de connexion vient d'être envoyé. Il est valable 15 minutes.</p></div>
    <button type="button" class="btn-link" data-back>Utiliser un autre mode</button>
  </div>

  <div data-step="trust" class="stack lg" hidden>
    <div><div class="eyebrow">Dernière étape</div><h2>Cet appareil est-il le vôtre ?</h2><p class="sub">Un appareil de confiance n'a plus besoin de vérification à chaque visite.</p></div>
    <label class="trust" for="trustDev"><input type="checkbox" id="trustDev" checked><span><strong>Faire confiance à cet appareil</strong><small>Session maintenue <?= TRUST_DAYS ?> jours. Révocable à tout moment depuis Sécurité.</small></span></label>
    <button type="button" class="btn btn-primary" id="enter">Entrer dans l'espace</button>
  </div>
</form>
<?php require __DIR__ . '/_foot.php'; ?>
