<?php /* Tableau de bord Direction : section « Candidats par métier » (formulaire Recrutement du site). */
$CC = cand_counts(); ?>
<section class="cdd" aria-labelledby="cddT">
  <div class="sec-h"><h2 id="cddT">Candidats par métier <?php if ($CC['new']): ?><span class="aiq-n" title="Nouvelles candidatures"><?= $CC['new'] ?></span><?php endif; ?></h2>
    <span class="cdd-links"><a href="/candidats/base">Base de recrutement · <?= $CC['starred'] ?> validé<?= $CC['starred'] > 1 ? 's' : '' ?></a><a href="/candidats">Tout voir →</a></span></div>
  <?php if (!$CC['total']): ?>
  <div class="empty"><strong>Aucune candidature pour l'instant.</strong>Les candidatures envoyées depuis la page Recrutement de gidsas.fr arrivent ici, classées par métier, avec leurs fichiers contrôlés par l'anti-virus.</div>
  <?php else: ?>
  <div class="cdd-grid">
    <?php foreach (CAND_JOBS as $k => $lab): $j = $CC['jobs'][$k] ?? null; if (!$j) continue; ?>
    <a class="panel cdd-tile" href="/candidats?metier=<?= e($k) ?>">
      <span class="cdd-n"><?= $j['n'] ?></span>
      <span class="cdd-l"><?= e($lab) ?></span>
      <span class="cdd-s"><?php if ($j['new']): ?><b><?= $j['new'] ?> nouveau<?= $j['new'] > 1 ? 'x' : '' ?></b><?php endif; ?><?php if ($j['starred']): ?><span class="cdd-st"><svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6L12 16.7 6.6 19.6l1.1-6-4.5-4.2 6.1-.8z"/></svg><?= $j['starred'] ?></span><?php endif; ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($CC['threat']): ?><p class="cdd-warn"><?= $CC['threat'] ?> candidature<?= $CC['threat'] > 1 ? 's' : '' ?> avec un fichier bloqué par le contrôle anti-virus (en quarantaine).</p><?php endif; ?>
  <?php endif; ?>
</section>
