<?php
$tri = '<svg class="sa-tri" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 21h20L12 3z" fill="currentColor"/><path d="M12 10v5M12 18h.01" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
$okI = '<svg class="sa-ok" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="m7.5 12.5 3 3 6-6.5" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$methods = (int)!empty($u['password_hash']) + (int)!empty($passkeys) + (int)!empty($u['totp_enabled']);
$sa = $secAudits[0] ?? null;
$next = (int)date('N') === 6 && (int)date('G') < 12 ? strtotime('today 12:00') : strtotime('next saturday 12:00');
?>
<header class="head">
  <div>
    <div class="eyebrow">Compte</div>
    <h1>Sécurité</h1>
    <div class="date">Vos méthodes de connexion et appareils<?= $u['role'] === 'admin' ? ', l\'audit hebdomadaire de la plateforme et l\'équipe' : '' ?></div>
  </div>
</header>

<?php if ($u['role'] === 'admin'): ?>
<section class="sa">
  <div class="sec-h"><h2>Audit de la plateforme</h2><span class="muted small">Fonctionnement et sécurité · chaque samedi à 12 h · prochain le <?= e(date_short($next)) ?></span></div>
  <?php if (!$sa): ?>
  <div class="empty"><strong>Premier audit en cours de préparation.</strong>Il s'exécute automatiquement chaque samedi à 12 h. <button type="button" class="btn-link" data-api="sec/run">Lancer maintenant</button></div>
  <?php else: $d = $sa['d']; $sc = (int)($d['score'] ?? 0); $lv = $sc >= 90 ? 'ok' : ($sc >= 70 ? 'warn' : 'crit'); ?>
  <div class="sa-wrap">
    <div class="sa-score <?= $lv ?>">
      <div class="sa-ring" style="--p:<?= $sc ?>"><b><?= $sc ?></b><small>/ 100</small></div>
      <div class="sa-head"><?= ($d['n']['crit'] ?? 0) ? $tri : '' ?><?= e($sa['headline']) ?></div>
      <ul class="sa-counts">
        <li class="crit"><b><?= (int)($d['n']['crit'] ?? 0) ?></b>critiques</li>
        <li class="warn"><b><?= (int)($d['n']['warn'] ?? 0) ?></b>à surveiller</li>
        <li class="ok"><b><?= (int)($d['n']['ok'] ?? 0) ?></b>conformes</li>
      </ul>
      <div class="muted small">Audit du <?= e(date('d/m/Y à H:i', (int)$sa['created_at'])) ?></div>
      <button type="button" class="btn btn-ghost" data-api="sec/run">Relancer l'audit</button>
      <?php if (count($secAudits) > 1): ?>
      <div class="sa-hist" aria-label="Audits précédents">
        <?php foreach (array_reverse($secAudits) as $h): $hs = (int)($h['d']['score'] ?? 0); ?>
        <span class="<?= $hs >= 90 ? 'ok' : ($hs >= 70 ? 'warn' : 'crit') ?>" title="<?= e(date('d/m/Y', strtotime($h['period'])) . ' · ' . $h['headline']) ?>"><i style="height:<?= max(8, $hs) ?>%"></i><small><?= e(date('d/m', strtotime($h['period']))) ?></small></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php $ord = ['crit' => 0, 'warn' => 1, 'ok' => 2]; foreach (['sec' => 'Sécurité', 'run' => 'Fonctionnement'] as $g => $gl): $list = array_values(array_filter($d['checks'] ?? [], fn($c) => $c['g'] === $g)); usort($list, fn($a, $b) => $ord[$a['lv']] <=> $ord[$b['lv']]); ?>
    <div class="sa-col panel">
      <h3><?= e($gl) ?> <span class="muted small"><?= count(array_filter($list, fn($c) => $c['lv'] === 'ok')) ?>/<?= count($list) ?> conformes</span></h3>
      <ul class="sa-list">
        <?php foreach ($list as $c): ?>
        <li class="<?= e($c['lv']) ?>"><?= $c['lv'] === 'ok' ? $okI : $tri ?><div><b><?= e($c['t']) ?></b><span><?= e($c['d']) ?></span><?php if (!empty($c['fix'])): ?><em>→ <?= e($c['fix']) ?></em><?php endif; ?></div></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="sc">
  <div class="sec-h"><h2>Mon compte</h2><span class="muted small"><?= $methods ?> méthode<?= $methods > 1 ? 's' : '' ?> de connexion active<?= $methods > 1 ? 's' : '' ?> sur 3</span></div>
  <div class="sc-grid">
    <div class="panel sc-box">
      <h3>Méthodes de connexion</h3>
      <details class="sc-row">
        <summary><span class="sc-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
          <span class="sc-t"><b>Mot de passe</b><small>10 caractères minimum</small></span><span class="chip <?= $u['password_hash'] ? 'ok' : 'warn' ?>"><?= $u['password_hash'] ? 'Actif' : 'À définir' ?></span><span class="sc-act"><?= $u['password_hash'] ? 'Changer' : 'Définir' ?></span></summary>
        <form class="stack sc-body" data-api="password/set">
          <?php if ($u['password_hash']): ?><div class="field"><label for="pw-cur">Mot de passe actuel</label><input class="input" id="pw-cur" name="current" type="password" autocomplete="current-password" required></div><?php endif; ?>
          <div class="two">
            <div class="field"><label for="pw-new">Nouveau mot de passe</label><input class="input" id="pw-new" name="password" type="password" autocomplete="new-password" minlength="10" required></div>
            <div class="field"><label for="pw-conf">Confirmation</label><input class="input" id="pw-conf" name="confirm" type="password" autocomplete="new-password" minlength="10" required></div>
          </div>
          <p class="form-error" hidden></p>
          <button class="btn btn-primary"><?= $u['password_hash'] ? 'Changer le mot de passe' : 'Définir le mot de passe' ?></button>
        </form>
      </details>
      <details class="sc-row"<?= !$passkeys ? '' : '' ?>>
        <summary><span class="sc-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M9 9v1M15 9v1M12 9v4h-1M9.5 15.5a3.5 3.5 0 0 0 5 0"/></svg></span>
          <span class="sc-t"><b>Face ID · clés d'accès</b><small>Reconnaissance faciale (iPhone, Mac) ou Windows Hello</small></span><span class="chip <?= $passkeys ? 'ok' : 'warn' ?>"><?= $passkeys ? count($passkeys) . ' clé' . (count($passkeys) > 1 ? 's' : '') : 'À configurer' ?></span><span class="sc-act">Gérer</span></summary>
        <div class="stack sc-body">
          <?php if ($passkeys): ?>
          <ul class="sc-list">
            <?php foreach ($passkeys as $p): ?>
            <li><span><b><?= e($p['label']) ?></b><small>Ajoutée le <?= e(date_short((int)$p['created_at'])) ?><?= $p['last_used_at'] ? ' · utilisée ' . e(mb_strtolower(ago((int)$p['last_used_at']))) : '' ?></small></span>
              <button type="button" class="btn-link" data-api="passkey/delete" data-id="<?= (int)$p['id'] ?>" data-confirm="Retirer cette clé ?">Retirer</button></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <button class="btn btn-primary" type="button" id="addPasskey">Ajouter cet appareil (Face ID)</button>
          <p class="form-error" id="pkError" hidden></p>
        </div>
      </details>
      <details class="sc-row">
        <summary><span class="sc-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 18h4M9 8h.01M12 8h.01M15 8h.01M9 11h.01M12 11h.01M15 11h.01"/></svg></span>
          <span class="sc-t"><b>Code à 6 chiffres</b><small>Demandé après le mot de passe, sauf sur un appareil de confiance</small></span><span class="chip <?= $u['totp_enabled'] ? 'ok' : 'neutral' ?>"><?= $u['totp_enabled'] ? 'Activé' : 'Optionnel' ?></span><span class="sc-act"><?= $u['totp_enabled'] ? 'Gérer' : 'Activer' ?></span></summary>
        <div class="stack sc-body">
        <?php if ($u['totp_enabled']): ?>
          <form class="stack" data-api="totp/disable">
            <div class="field"><label for="td-pw">Mot de passe (pour désactiver)</label><input class="input" id="td-pw" name="password" type="password" autocomplete="current-password" required></div>
            <p class="form-error" hidden></p>
            <button class="btn btn-ghost">Désactiver le code</button>
          </form>
        <?php else: ?>
          <button class="btn btn-primary" type="button" id="totpStart">Activer le code à 6 chiffres</button>
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
      </details>
    </div>

    <div class="sc-side">
      <div class="panel sc-box">
        <h3>Appareils de confiance <span class="muted small"><?= count($devices) ?></span></h3>
        <?php if (!$devices): ?>
          <p class="muted small">Aucun appareil. Cochez « Faire confiance à cet appareil » à la connexion pour ne plus vous identifier pendant <?= TRUST_DAYS ?> jours.</p>
        <?php else: ?>
        <ul class="sc-list">
          <?php foreach ($devices as $d): $cur = (int)$d['id'] === $currentDevice; ?>
          <li><span><b><?= e($d['label']) ?></b><small>Actif le <?= date('d/m/Y à H:i', (int)$d['last_seen_at']) ?> · expire le <?= date('d/m/Y', (int)$d['expires_at']) ?></small></span>
            <?php if ($cur): ?><span class="chip neutral">Cet appareil</span><?php else: ?><button class="btn-link" type="button" data-api="device/revoke" data-id="<?= (int)$d['id'] ?>" data-confirm="Révoquer ?">Révoquer</button><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <div class="panel sc-box">
        <h3>Messagerie Outlook <span class="chip <?= $msConnected ? 'ok' : 'warn' ?>"><?= $msConnected ? 'Connectée' : 'Non connectée' ?></span></h3>
        <?php if ($msError): ?><p class="form-error">La connexion à Microsoft n'a pas abouti. Réessayez.</p><?php endif; ?>
        <?php if (!$msEnabled): ?>
          <p class="muted small">L'application Microsoft 365 n'est pas encore configurée sur le serveur.</p>
        <?php elseif ($msConnected): ?>
          <p class="muted small">Lecture seule : la plateforme affiche vos mails importants, sans jamais en envoyer ni en supprimer.</p>
          <button class="btn btn-ghost" type="button" data-api="microsoft/disconnect" data-confirm="Déconnecter Outlook ?">Déconnecter</button>
        <?php else: ?>
          <a class="btn btn-ghost" href="/microsoft/connexion">Connecter Outlook</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($u['role'] === 'admin'): ?>
<section class="sc">
  <div class="sec-h"><h2>Équipe</h2><span class="muted small"><?= count($team) ?> compte<?= count($team) > 1 ? 's' : '' ?><?= $invites ? ' · ' . count($invites) . ' invitation' . (count($invites) > 1 ? 's' : '') . ' en attente' : '' ?></span></div>
  <div class="panel sc-team">
    <ul class="sc-list">
      <?php foreach ($team as $m): ?><li><span class="inline"><?= avatar($m, 'sm') ?> <span><b><?= e($m['name']) ?></b><small class="mono"><?= e($m['email']) ?></small></span></span><?php if ($m['role'] === 'admin'): ?><span class="chip neutral">Administrateur</span><?php endif; ?></li><?php endforeach; ?>
      <?php foreach ($invites as $i): ?><li><span class="inline muted"><?= e($i['name']) ?></span><span class="chip warn">Invitation en attente</span></li><?php endforeach; ?>
    </ul>
    <form class="stack sc-inv" data-api="invite" data-show-link>
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
</section>
<?php endif; ?>
