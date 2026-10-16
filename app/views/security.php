<header class="head">
  <div>
    <div class="eyebrow">Compte</div>
    <h1>Sécurité</h1>
    <div class="date">Méthodes de connexion, appareils de confiance et messagerie</div>
  </div>
</header>

<div class="grid">
  <section class="col-6">
    <div class="panel stack">
      <div class="sec-h flat"><h2>Face ID · clés d'accès</h2><span class="chip <?= $passkeys ? 'ok' : 'warn' ?>"><?= $passkeys ? 'Active' : 'À configurer' ?></span></div>
      <p class="muted">Connexion sans mot de passe par reconnaissance faciale (iPhone, Mac) ou Windows Hello.</p>
      <?php if ($passkeys): ?>
      <ul class="docs">
        <?php foreach ($passkeys as $p): ?>
        <li><span><?= e($p['label']) ?><br><small class="muted">Ajoutée le <?= e(date_short((int)$p['created_at'])) ?><?= $p['last_used_at'] ? ' · utilisée ' . e(mb_strtolower(ago((int)$p['last_used_at']))) : '' ?></small></span>
          <button type="button" class="btn-link" data-api="passkey/delete" data-id="<?= (int)$p['id'] ?>" data-confirm="Retirer cette clé ?">Retirer</button></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <button class="btn btn-ghost" type="button" id="addPasskey">Ajouter cet appareil (Face ID)</button>
      <p class="form-error" id="pkError" hidden></p>
    </div>
  </section>

  <section class="col-6">
    <div class="panel stack">
      <div class="sec-h flat"><h2>Mot de passe</h2><span class="chip <?= $u['password_hash'] ? 'ok' : 'warn' ?>"><?= $u['password_hash'] ? 'Actif' : 'À définir' ?></span></div>
      <form class="stack" data-api="password/set">
        <?php if ($u['password_hash']): ?><div class="field"><label for="pw-cur">Mot de passe actuel</label><input class="input" id="pw-cur" name="current" type="password" autocomplete="current-password" required></div><?php endif; ?>
        <div class="two">
          <div class="field"><label for="pw-new">Nouveau mot de passe</label><input class="input" id="pw-new" name="password" type="password" autocomplete="new-password" minlength="10" required></div>
          <div class="field"><label for="pw-conf">Confirmation</label><input class="input" id="pw-conf" name="confirm" type="password" autocomplete="new-password" minlength="10" required></div>
        </div>
        <p class="form-error" hidden></p>
        <button class="btn btn-ghost"><?= $u['password_hash'] ? 'Changer le mot de passe' : 'Définir le mot de passe' ?></button>
      </form>
    </div>
    <div class="panel stack" style="margin-top:24px">
      <div class="sec-h flat"><h2>Code à 6 chiffres</h2><span class="chip <?= $u['totp_enabled'] ? 'ok' : 'neutral' ?>"><?= $u['totp_enabled'] ? 'Activé' : 'Optionnel' ?></span></div>
      <p class="muted">Double vérification : après le mot de passe, un code de votre application d'authentification est demandé. Sur un appareil de confiance, il n'est plus demandé.</p>
      <?php if ($u['totp_enabled']): ?>
        <form class="stack" data-api="totp/disable">
          <div class="field"><label for="td-pw">Mot de passe (pour désactiver)</label><input class="input" id="td-pw" name="password" type="password" autocomplete="current-password" required></div>
          <p class="form-error" hidden></p>
          <button class="btn btn-ghost">Désactiver le code</button>
        </form>
      <?php else: ?>
        <button class="btn btn-ghost" type="button" id="totpStart">Activer le code à 6 chiffres</button>
        <div class="stack" id="totpSetup" hidden>
          <ol class="enroll-steps">
            <li><strong>Sur iPhone</strong> : <a class="btn btn-ghost mt" id="tsUri" href="#">Ajouter à mon application</a></li>
            <li><strong>Sinon</strong>, dans Google ou Microsoft Authenticator, « Saisir une clé » : <div class="secret mono" id="tsSecret"></div></li>
            <li>Saisissez le code affiché :</li>
          </ol>
          <div class="otp"><?php for ($i = 1; $i <= 6; $i++): ?><input id="ts<?= $i ?>" inputmode="numeric" autocomplete="<?= $i === 1 ? 'one-time-code' : 'off' ?>" maxlength="<?= $i === 1 ? 6 : 1 ?>" aria-label="Chiffre <?= $i ?>"><?php endfor; ?></div>
          <p class="form-error" hidden></p>
          <button class="btn btn-primary" type="button" id="tsOk">Activer</button>
        </div>
      <?php endif; ?>
    </div>
    <div class="panel stack" style="margin-top:24px">
      <div class="sec-h flat"><h2>Messagerie Outlook</h2><span class="chip <?= $msConnected ? 'ok' : 'warn' ?>"><?= $msConnected ? 'Connectée' : 'Non connectée' ?></span></div>
      <?php if ($msError): ?><p class="form-error">La connexion à Microsoft n'a pas abouti. Réessayez.</p><?php endif; ?>
      <?php if (!$msEnabled): ?>
        <p class="muted">L'application Microsoft 365 n'est pas encore configurée sur le serveur.</p>
      <?php elseif ($msConnected): ?>
        <p class="muted">Lecture seule : la plateforme affiche vos mails importants, sans jamais en envoyer ni en supprimer.</p>
        <button class="btn btn-ghost" type="button" data-api="microsoft/disconnect" data-confirm="Déconnecter Outlook ?">Déconnecter</button>
      <?php else: ?>
        <a class="btn btn-ghost" href="/microsoft/connexion">Connecter Outlook</a>
      <?php endif; ?>
    </div>
  </section>

  <section class="col-12">
    <div class="sec-h"><h2>Appareils de confiance</h2></div>
    <?php if (!$devices): ?>
      <div class="empty"><strong>Aucun appareil de confiance.</strong>Cochez « Faire confiance à cet appareil » à la connexion pour ne plus avoir à vous identifier pendant <?= TRUST_DAYS ?> jours.</div>
    <?php else: ?>
    <div class="tbl"><table class="sec-table">
      <thead><tr><th>Appareil</th><th>Dernière activité</th><th>Expire le</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($devices as $d): ?>
        <tr>
          <td><strong><?= e($d['label']) ?></strong><?php if ((int)$d['id'] === $currentDevice): ?><br><span class="muted small">Cet appareil</span><?php endif; ?></td>
          <td class="mono"><?= date('d/m/Y H:i', (int)$d['last_seen_at']) ?></td>
          <td class="mono"><?= date('d/m/Y', (int)$d['expires_at']) ?></td>
          <td><?php if ((int)$d['id'] === $currentDevice): ?><span class="chip neutral">Actuel</span><?php else: ?><button class="btn-link" type="button" data-api="device/revoke" data-id="<?= (int)$d['id'] ?>" data-confirm="Révoquer ?">Révoquer</button><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>

  <?php if ($u['role'] === 'admin'): ?>
  <section class="col-12">
    <div class="sec-h"><h2>Équipe</h2></div>
    <div class="grid inner">
      <div class="col-6">
        <ul class="docs panel">
          <?php foreach ($team as $m): ?><li><span class="inline"><?= avatar($m, 'sm') ?> <?= e($m['name']) ?></span><span class="mono"><?= e($m['email']) ?></span></li><?php endforeach; ?>
          <?php foreach ($invites as $i): ?><li><span class="inline muted"><?= e($i['name']) ?></span><span class="chip warn">Invitation en attente</span></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-6">
        <form class="panel stack" data-api="invite" data-show-link>
          <h3>Inviter une personne</h3>
          <div class="two">
            <div class="field"><label for="iv-name">Prénom / nom</label><input class="input" id="iv-name" name="name" maxlength="60" placeholder="Céline" required></div>
            <div class="field"><label for="iv-ini">Initiales</label><input class="input" id="iv-ini" name="initials" maxlength="3" placeholder="C"></div>
          </div>
          <div class="field"><label for="iv-email">Adresse e-mail</label><input class="input" id="iv-email" name="email" type="email" required placeholder="prenom@gidsas.fr"></div>
          <p class="form-error" hidden></p>
          <button class="btn btn-primary">Créer le lien d'invitation</button>
          <div class="invite-link" hidden><p class="muted small">Lien valable 7 jours, à usage unique. Transmettez-le directement à la personne.</p><input class="input mono" id="iv-link" readonly><button type="button" class="btn btn-ghost" id="copyLink">Copier le lien</button></div>
        </form>
      </div>
    </div>
  </section>
  <?php endif; ?>
</div>
