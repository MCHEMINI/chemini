<?php require __DIR__ . '/_head.php'; ?>
<div class="login-card">
  <div><div class="eyebrow">Lien de connexion</div><h2>Se connecter à Dashboard</h2>
  <p class="sub">Confirmez pour ouvrir votre session sur cet appareil. Le lien ne servira qu'une fois.</p></div>
  <form method="post" action="/connexion/lien"><input type="hidden" name="t" value="<?= e($token) ?>"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
  <button type="submit" class="btn btn-primary btn-block">Se connecter</button></form>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
