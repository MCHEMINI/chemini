<?php
$k = fn($v) => number_format($v / 1000, 0, ',', ' ') . ' k€';
$exp = count(array_filter($mand, fn($m) => $m['expired'])); $soon = count($mand) - $exp;
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · pilotage</div>
    <h1>Pilotage du cabinet</h1>
    <div class="date">Finances, AG, relation copropriétaires et conformité : chaque bloc mène au détail</div>
  </div>
</header>

<section class="pl-sec">
  <div class="sec-h"><h2>Finances et équipes</h2></div>
  <div class="ov-grid">
    <a class="ov-card" href="/pilotage/mandats">
      <div class="ov-h"><span class="ov-eye">Renouvellements de mandat</span><span class="ov-go">Détail →</span></div>
      <div class="ov-n <?= $exp ? 'crit' : '' ?>"><?= count($mand) ?></div>
      <div class="ov-l">mandats échus ou à échéance sous 6 mois · proposition d'honoraires chiffrée</div>
      <ul class="ov-rows"><li class="<?= $exp ? 'crit' : 'ok' ?>"><span>Déjà échus</span><b><?= $exp ?></b></li><li><span>À échéance sous 6 mois</span><b><?= $soon ?></b></li></ul>
    </a>
    <a class="ov-card" href="/pilotage/encaissements">
      <div class="ov-h"><span class="ov-eye">Prévision d'encaissement</span><span class="ov-go">Détail →</span></div>
      <div class="ov-n"><?= e($k($cash['hono'] + $cash['ann'])) ?></div>
      <div class="ov-l">attendus sur l'exercice <?= e(str_replace('-', '–', $cash['ex'])) ?> · <?= e($k(($cash['hono'] + $cash['ann']) / 12)) ?> par mois</div>
      <ul class="ov-rows"><li><span>Honoraires de gestion</span><b><?= e($k($cash['hono'])) ?></b></li><li><span>Honoraires annexes (projection)</span><b><?= e($k($cash['ann'])) ?></b></li></ul>
    </a>
    <a class="ov-card" href="/pilotage/equipes">
      <div class="ov-h"><span class="ov-eye">Gestionnaires et charge</span><span class="ov-go">Détail →</span></div>
      <div class="ov-n"><?= (int)q("SELECT COUNT(DISTINCT gest) FROM buildings WHERE kind='syndic' AND gest<>''")->fetchColumn() ?></div>
      <div class="ov-l">gestionnaires : honoraires, rentabilité, AG, retards · charge de chaque collaborateur</div>
    </a>
    <a class="ov-card" href="/pilotage/impayes">
      <div class="ov-h"><span class="ov-eye">Impayés par immeuble</span><span class="ov-go">Détail →</span></div>
      <div class="ov-n <?= $cards['impayes']['crit'] ? 'crit' : '' ?>"><?= (int)$cards['impayes']['n'] ?></div>
      <div class="ov-l">immeubles suivis<?= $cards['impayes']['n'] ? '' : ' · relevé ImmOpen à venir (le 1er du mois)' ?></div>
      <ul class="ov-rows"><li class="<?= $cards['impayes']['crit'] ? 'crit' : 'ok' ?>"><span>Impayés élevés</span><b><?= (int)$cards['impayes']['crit'] ?></b></li></ul>
    </a>
  </div>
</section>

<section class="pl-sec">
  <div class="sec-h"><h2>Assemblées générales</h2></div>
  <div class="ov-grid">
    <a class="ov-card" href="/pilotage/ag">
      <div class="ov-h"><span class="ov-eye">Préparation des AG</span><span class="ov-go">Fiches →</span></div>
      <div class="ov-n"><?= count($ops['upcoming']) ?></div>
      <div class="ov-l">AG dans les 30 jours · fiche de préparation pré-remplie par immeuble · agenda iPhone</div>
    </a>
    <a class="ov-card" href="/pilotage/travaux">
      <div class="ov-h"><span class="ov-eye">Travaux votés</span><span class="ov-go">Suivi →</span></div>
      <div class="ov-n <?= $cards['travaux']['crit'] ? 'crit' : '' ?>"><?= (int)$cards['travaux']['n'] ?></div>
      <div class="ov-l">résolutions relevées dans les PV, de « à lancer » à « réceptionné »</div>
      <ul class="ov-rows"><li class="<?= $cards['travaux']['warn'] + $cards['travaux']['crit'] ? 'warn' : 'ok' ?>"><span>En retard ou non lancés</span><b><?= $cards['travaux']['warn'] + $cards['travaux']['crit'] ?></b></li></ul>
    </a>
    <div class="ov-card">
      <div class="ov-h"><span class="ov-eye">PV à diffuser</span></div>
      <div class="ov-n <?= count($ops['nopv']) ? 'crit' : '' ?>"><?= count($ops['nopv']) ?></div>
      <div class="ov-l">PV non remis plus de 21 jours après l'AG</div>
      <?php if ($ops['nopv']): ?><button type="button" class="btn btn-ghost pl-btn" data-api="pilot/pvtasks">Créer les tâches de relance</button><p class="muted small">Une tâche pour le gestionnaire concerné, échéance à 3 jours (visible dans Collaborateurs).</p><?php endif; ?>
    </div>
  </div>
</section>

<section class="pl-sec">
  <div class="sec-h"><h2>Registres</h2><span class="muted small">Saisie libre, alertes automatiques</span></div>
  <div class="pl-regs">
    <?php foreach (['sinistres', 'contrats', 'demandes', 'echeances', 'conformite'] as $r): $R = REGS[$r]; $c = $cards[$r]; ?>
    <a class="pl-reg" href="/pilotage/<?= $r ?>">
      <span class="pl-ic" aria-hidden="true"><?= $R[1] ?></span>
      <span class="pl-rt"><b><?= e($R[0]) ?></b><small><?= e($R[2]) ?></small></span>
      <span class="pl-rn"><b><?= (int)$c['n'] ?></b><?php if ($c['crit']): ?><span class="chip crit"><?= (int)$c['crit'] ?> urgent<?= $c['crit'] > 1 ? 's' : '' ?></span><?php elseif ($c['warn']): ?><span class="chip warn"><?= (int)$c['warn'] ?> à surveiller</span><?php endif; ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
