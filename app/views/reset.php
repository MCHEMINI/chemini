<?php require __DIR__ . '/_head.php'; ?>
<form class="login-card" id="resetFlow" data-token="<?= e($token) ?>" novalidate>
  <div class="stack lg" data-step="form">
    <div><div class="eyebrow">Mot de passe oublié</div><h2>Nouveau mot de passe</h2><p class="sub">10 caractères minimum. Votre code à 6 chiffres restera demandé à la connexion.</p></div>
    <div class="field"><label for="rp1">Nouveau mot de passe</label><input class="input" id="rp1" type="password" autocomplete="new-password" minlength="10"></div>
    <div class="field"><label for="rp2">Confirmation</label><input class="input" id="rp2" type="password" autocomplete="new-password" minlength="10"></div>
    <p class="form-error" hidden></p>
    <button type="submit" class="btn btn-primary">Enregistrer</button>
  </div>
  <div class="stack lg" data-step="done" hidden>
    <div><div class="eyebrow">C'est fait</div><h2>Mot de passe modifié</h2><p class="sub">Vous pouvez vous connecter avec votre nouveau mot de passe.</p></div>
    <a class="btn btn-primary" href="/connexion">Se connecter</a>
  </div>
</form>
<?php require __DIR__ . '/_foot.php'; ?>
