<?php $jrs = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.']; ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage">Pilotage</a> · assemblées générales</div>
    <h1>Préparation des AG</h1>
    <div class="date"><?= count($rows) ?> AG dans les 75 prochains jours · une fiche pré-remplie par immeuble (chiffres, mandat, travaux votés, contrats, échéances)</div>
  </div>
</header>
<details class="panel bd-new">
  <summary><span class="btn btn-ghost">📅 Ajouter les AG à l'agenda de l'iPhone</span><span class="muted small">Abonnement personnel, mis à jour automatiquement</span></summary>
  <ol class="ac-ul">
    <li>Sur l'iPhone, touchez ce lien : <a href="<?= e(str_replace('https://', 'webcal://', $ical)) ?>">s'abonner au calendrier des AG</a></li>
    <li>Ou, dans Réglages › Calendrier › Comptes › Ajouter un compte › Autre › Ajouter un calendrier avec abonnement, collez : <input class="input mono" readonly value="<?= e($ical) ?>" onclick="this.select()"></li>
    <li>Ce lien est personnel : ne le partagez pas.</li>
  </ol>
</details>
<?php if (!$rows): ?><div class="empty"><strong>Aucune AG planifiée dans les 75 jours.</strong>Source : planning ImmOpen.</div>
<?php else: ?>
<div class="agn-grid">
  <?php foreach ($rows as $a): $ts = strtotime($a['ag_date']); $b = $B[$a['code']]; ?>
  <a class="agn<?= $ts <= strtotime('+14 days') ? ' soon' : '' ?>" href="/pilotage/ag/<?= e($a['code']) ?>">
    <span class="agn-d"><small><?= e($jrs[(int)date('w', $ts)]) ?></small><b><?= date('d', $ts) ?></b><small><?= e(MOIS[(int)date('n', $ts) - 1]) ?></small></span>
    <span class="agn-b"><b><?= e($b['imm']) ?></b><small><span class="mono"><?= e($a['code']) ?></span><?= !empty($a['ag_heure']) ? ' · ' . e($a['ag_heure']) : '' ?> · <?= (int)$b['lots'] ?> lots</small><small><?= e(trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($a['gest'] ?? '')))) ?></small><?php if (empty($a['convoc'])): ?><span class="chip warn">Convocation à envoyer</span><?php else: ?><span class="chip ok">Convoquée</span><?php endif; ?></span>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
