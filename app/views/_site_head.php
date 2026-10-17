<?php /* En-tête commun des pages Site Web : $h1, $lead ; $snap, $conn */
$host = $conn ? parse_url($conn['url'], PHP_URL_HOST) : parse_url(WP_DEFAULT_URL, PHP_URL_HOST);
$err = $snap['_error'] ?? ($snap['_stale'] ?? null);
?>
<header class="head">
  <div>
    <div class="eyebrow">Site Web · <?= e((string)$host) ?><?= $host && str_starts_with((string)$host, 'preprod.') ? ' · préproduction' : '' ?></div>
    <h1><?= e($h1) ?></h1>
    <div class="date"><?= e($lead) ?><?php if (!empty($snap['at'])): ?> · données du <?= e(date('d/m/Y à H:i', (int)$snap['at'])) ?><?php endif; ?></div>
  </div>
  <?php if ($conn): ?><div class="head-actions"><?php if ($snap !== null): ?><a class="btn btn-ghost" href="?actualiser" title="Relire les données du site maintenant">Actualiser</a><?php endif; ?><a class="btn btn-ghost" href="<?= e(rtrim($conn['url'], '/')) ?>/wp-admin/" target="_blank" rel="noopener">Ouvrir WordPress ↗</a></div><?php endif; ?>
</header>
<?php if (!$conn): ?>
<div class="panel ws-empty">
  <h2>Le site n'est pas encore relié à Dashboard</h2>
  <p class="muted">Dashboard lit et met à jour le site WordPress avec un <b>mot de passe d'application</b> : une clé dédiée, que l'on peut retirer à tout moment sans toucher au mot de passe de connexion.</p>
  <?php if ($admin): ?><a class="btn btn-primary" href="/site-web/connexion">Relier le site</a><?php else: ?><p class="muted small">La liaison est faite par l'administrateur de Dashboard.</p><?php endif; ?>
</div>
<?php elseif ($err): ?>
<div class="pw-alert"><b>Le site n'a pas répondu :</b> <?= e((string)$err) ?>. <?= isset($snap['_stale']) ? 'Les dernières données connues sont affichées.' : '' ?> <?php if ($admin): ?><a href="/site-web/connexion">Vérifier la connexion</a><?php endif; ?></div>
<?php endif; ?>
