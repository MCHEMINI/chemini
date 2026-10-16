<header class="head">
  <div>
    <div class="eyebrow">Espace Famille</div>
    <h1>Voyages</h1>
    <div class="date">Guides préparés par Claude : meilleure période, vols Air France ou Transavia, météo, préparatifs et programme jour par jour</div>
  </div>
</header>

<?php if (!$trips): ?>
  <div class="empty"><strong>Aucun voyage pour l'instant.</strong>
    Demandez à Claude, par exemple : « Voyage : Lisbonne, 5 jours » ou « Voyage : Japon, 2 semaines en famille ». Le guide complet apparaîtra ici.</div>
<?php else: ?>
  <div class="trip-grid">
    <?php foreach ($trips as $t): $d = $t['d']; ?>
    <a class="panel trip-card" href="/voyages/<?= (int)$t['id'] ?>">
      <span class="eyebrow"><?= e($t['duration']) ?></span>
      <h2><?= e($t['destination']) ?></h2>
      <?php if (!empty($d['best_period']['when'])): ?><p class="trip-when">Meilleure période : <strong><?= e($d['best_period']['when']) ?></strong></p><?php endif; ?>
      <?php if (!empty($d['summary'])): ?><p class="muted"><?= e(mb_strimwidth((string)$d['summary'], 0, 180, '…')) ?></p><?php endif; ?>
      <span class="muted small">Mis à jour <?= e(mb_strtolower(ago((int)$t['updated_at']))) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
