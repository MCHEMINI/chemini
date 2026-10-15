<?php require __DIR__ . '/_head.php'; ?>
<form class="login-card" id="loginFlow" novalidate>
  <div class="steps" aria-hidden="true"><i class="on"></i><i></i><i></i></div>

  <div data-step="email" class="stack lg">
    <div><div class="eyebrow">Connexion</div><h2><?= greeting() ?>.</h2><p class="sub">Identifiez-vous avec votre adresse professionnelle.</p></div>
    <div class="field"><label for="email">Adresse e-mail</label><input class="input" id="email" name="email" type="email" autocomplete="username webauthn" required></div>
    <button type="submit" class="btn btn-primary">Continuer</button>
    <div class="secure-note"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Aucun mot de passe : Face ID ou application d'authentification.</div>
  </div>

  <div data-step="method" class="stack lg" hidden>
    <div><div class="eyebrow">Vérification</div><h2>Comment confirmer ?</h2><p class="sub" id="methodEmail"></p></div>
    <div class="stack">
      <button type="button" class="btn btn-primary" id="useFace" hidden>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M9 9v1M15 9v1M12 9v4h-1M9.5 16a3.5 3.5 0 0 0 5 0"/></svg>
        Continuer avec Face ID
      </button>
      <div class="divider" id="orDivider" hidden>ou</div>
      <button type="button" class="btn btn-ghost" id="useTotp">Code d'authentification (6 chiffres)</button>
    </div>
    <p class="form-error" hidden></p>
    <button type="button" class="btn-link" data-back>Changer d'adresse</button>
  </div>

  <div data-step="totp" class="stack lg" hidden>
    <div><div class="eyebrow">Vérification</div><h2>Code à 6 chiffres</h2><p class="sub">Ouvrez votre application d'authentification.</p></div>
    <div class="otp">
      <?php for ($i = 1; $i <= 6; $i++): ?><input id="o<?= $i ?>" inputmode="numeric" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" maxlength="<?= $i === 1 ? 6 : 1 ?>" aria-label="Chiffre <?= $i ?>"><?php endfor; ?>
    </div>
    <p class="form-error" hidden></p>
    <button type="button" class="btn btn-primary" id="totpOk">Vérifier</button>
    <button type="button" class="btn-link" data-back>Utiliser un autre moyen</button>
  </div>

  <div data-step="trust" class="stack lg" hidden>
    <div><div class="eyebrow">Dernière étape</div><h2>Cet appareil est-il le vôtre ?</h2><p class="sub">Un appareil de confiance n'a plus besoin de vérification à chaque visite.</p></div>
    <label class="trust" for="trustDev"><input type="checkbox" id="trustDev" checked><span><strong>Faire confiance à cet appareil</strong><small>Session maintenue <?= TRUST_DAYS ?> jours. Révocable à tout moment depuis Sécurité.</small></span></label>
    <button type="button" class="btn btn-primary" id="enter">Entrer dans l'espace</button>
  </div>
</form>
<?php require __DIR__ . '/_foot.php'; ?>
