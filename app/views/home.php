<?php
$M = $D['mails']; $T = $D['tasks']; $IA = $D['ia']; $O = $D['ops']; $first = strtok($u['name'], ' ');
$h = (int)date('G'); $hello = $h < 18 ? 'Bonjour' : 'Bonsoir';
$k = fn($v) => number_format((float)$v / 1000, 0, ',', ' ') . ' k€';
$ico = [
 'mail' => '<path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/>',
 'task' => '<path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
 'ia' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/>',
 'ag' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
 'euro' => '<path d="M17 6a7 7 0 1 0 0 12"/><path d="M4 10h10M4 14h10"/>',
 'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 'calc' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M12 11h2M16 11h0M8 15h2M12 15h2M8 18h2M12 18h4"/>',
 'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
 'pen' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
 'news' => '<path d="M4 4h13v16H6a2 2 0 0 1-2-2z"/><path d="M17 8h3v10a2 2 0 0 1-2 2M8 8h5M8 12h5M8 16h3"/>',
 'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
 'plane' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
 'survey' => '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/>',
 'sig' => '<rect x="3" y="7" width="18" height="10" rx="2"/><path d="M7 12h6"/>',
];
$svg = fn($n) => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $ico[$n] . '</svg>';
?>
<header class="head hm-head">
  <div>
    <div class="eyebrow">Perso · <?= e(ucfirst(JOURS[(int)date('w')])) ?> <?= (int)date('j') ?> <?= e(MOIS_LONG[(int)date('n') - 1]) ?></div>
    <h1><?= $hello ?> <?= e($first) ?></h1>
    <div class="date">L'essentiel de la plateforme en un coup d'œil<?= $dir ? ' : vos priorités, le pilotage du cabinet et la vie de l\'équipe' : ' : vos priorités et la vie de l\'équipe' ?>. Cliquez sur un bloc pour ouvrir la section.</div>
  </div>
</header>

<?php if (!empty($D['pwAlert'])): $al = $D['pwAlert']; ?>
<?php [$alT, $alD] = pw_alert_text($al); ?><a class="hm-alert" href="/mot-du-president/<?= e($al['month']) ?>"><span class="hm-alert-i">!</span><span><b><?= e($alT) ?></b><small><?= e($alD) ?></small></span><span class="hm-go">Ouvrir →</span></a>
<?php endif; ?>

<section class="hm-kpis" aria-label="Mes priorités">
  <a class="hm-kpi hm-mail<?= $M['n'] ? ' hot' : '' ?>" href="/espace">
    <span class="hm-ic"><?= $svg('mail') ?></span>
    <span class="hm-l">Mails à traiter</span>
    <b class="hm-n" data-live="mails"><?= (int)$M['n'] ?></b>
    <span class="hm-s"><span data-live="crit"><?= (int)$M['crit'] ?></span> urgent<?= $M['crit'] > 1 ? 's' : '' ?><?= $M['at'] ? ' · analysés à ' . e(date('H:i', $M['at'])) : '' ?></span>
    <?php if ($M['top']): ?><ul class="hm-mini"><?php foreach ($M['top'] as $m): ?><li class="<?= e($m['priority']) ?>"><?= e(mb_strimwidth((string)$m['subject'], 0, 60, '…')) ?></li><?php endforeach; ?></ul><?php endif; ?>
  </a>
  <a class="hm-kpi" href="/mes-taches">
    <span class="hm-ic"><?= $svg('task') ?></span>
    <span class="hm-l">Mes tâches</span>
    <b class="hm-n" data-live="tasks"><?= (int)($T['n'] ?? 0) ?></b>
    <span class="hm-s"><?= $T ? ($T['late'] ? '<em class="crit">' . (int)$T['late'] . ' en retard</em> · ' : '') . (int)$T['today'] . ' pour aujourd\'hui' : 'Aucune tâche' ?></span>
    <?php if ($T && $T['top']): ?><ul class="hm-mini"><?php foreach ($T['top'] as $t): ?><li class="<?= $t['due'] && $t['due'] < date('Y-m-d') ? 'crit' : '' ?>"><?= e(mb_strimwidth($t['title'] === SURVEY_TASK ? 'Répondre au questionnaire' : (string)$t['title'], 0, 60, '…')) ?><?= $t['due'] ? ' · ' . e(date('d/m', strtotime($t['due']))) : '' ?></li><?php endforeach; ?></ul><?php endif; ?>
  </a>
  <a class="hm-kpi" href="/assistant">
    <span class="hm-ic"><?= $svg('ia') ?></span>
    <span class="hm-l">Tâches IA</span>
    <b class="hm-n" data-live="ia"><?= (int)$IA['n'] ?></b>
    <span class="hm-s">en cours · <?= (int)$IA['done'] ?> traitée<?= $IA['done'] > 1 ? 's' : '' ?> ce mois-ci</span>
  </a>
  <a class="hm-kpi" href="<?= $dir ? '/planning-ag' : '/tableau-de-bord' ?>">
    <span class="hm-ic"><?= $svg('ag') ?></span>
    <span class="hm-l">AG des 14 prochains jours</span>
    <b class="hm-n"><?= (int)($O['week'] ?? 0) ?></b>
    <span class="hm-s"><?= count($O['upcoming'] ?? []) ?> sur 30 jours<?= !empty($O['convoc']) ? ' · <em class="crit">' . count($O['convoc']) . ' convocation' . (count($O['convoc']) > 1 ? 's' : '') . ' en retard</em>' : '' ?></span>
  </a>
</section>

<?php if ($dir && ($ov = $D['over'] ?? null)): $rent = $ov['rent'] ?? []; $C = $ov['compta'] ?? null; $R = $D['regs'] ?? null; $S = $D['sig'] ?? null; $SV = $D['survey'] ?? null; ?>
<section class="hm-sec">
  <div class="sec-h"><h2>Pilotage du cabinet</h2><span class="muted small">Direction</span></div>
  <div class="hm-grid">
    <a class="hm-card" href="/honoraires"><span class="hm-ic"><?= $svg('euro') ?></span><span class="hm-l">Honoraires 2026-2027</span><b class="hm-v"><?= $k($ov['hono']['ttc']) ?></b>
      <span class="hm-trend <?= $ov['hono']['evol'] >= 0 ? 'up' : 'down' ?>"><?= $ov['hono']['evol'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs($ov['hono']['evol']), 1, ',', ' ') ?> % sur un an</span>
      <span class="hm-s"><?= (int)$ov['hono']['held'] ?> AG tenues sur <?= (int)$ov['hono']['act'] ?> immeubles<?= $ov['hono']['under'] ? ' · <em class="warn">' . (int)$ov['hono']['under'] . ' votés sous le proposé</em>' : '' ?></span></a>
    <a class="hm-card" href="/strategies"><span class="hm-ic"><?= $svg('chart') ?></span><span class="hm-l">Rentabilité des immeubles</span>
      <span class="hm-dots"><span class="g"><b><?= (int)($rent['green'] ?? 0) ?></b>rentables</span><span class="o"><b><?= (int)($rent['orange'] ?? 0) ?></b>à surveiller</span><span class="r"><b><?= (int)($rent['red'] ?? 0) ?></b>déficitaires</span></span>
      <?php $tot = max(1, (int)($rent['green'] ?? 0) + (int)($rent['orange'] ?? 0) + (int)($rent['red'] ?? 0)); ?>
      <span class="hm-bar"><i class="g" style="width:<?= round(($rent['green'] ?? 0) / $tot * 100) ?>%"></i><i class="o" style="width:<?= round(($rent['orange'] ?? 0) / $tot * 100) ?>%"></i><i class="r" style="width:<?= round(($rent['red'] ?? 0) / $tot * 100) ?>%"></i></span></a>
    <a class="hm-card" href="/comptabilite"><span class="hm-ic"><?= $svg('calc') ?></span><span class="hm-l">Comptabilité</span>
      <?php if ($C): ?><span class="hm-pair"><span class="<?= $C['anom'] ? 'crit' : 'ok' ?>"><b><?= (int)$C['anom'] ?></b>incohérences</span><span class="<?= $C['fact'] ? 'warn' : 'ok' ?>"><b><?= (int)$C['fact'] ?></b>factures manquantes</span></span><span class="hm-s">Contrôle du <?= e(date('d/m', $C['at'])) ?></span>
      <?php else: ?><span class="hm-s">Premier contrôle vendredi à 19 h</span><?php endif; ?></a>
    <a class="hm-card" href="/pilotage"><span class="hm-ic"><?= $svg('shield') ?></span><span class="hm-l">Registres et échéances</span>
      <?php if ($R): ?><span class="hm-pair"><span class="<?= $R['crit'] ? 'crit' : 'ok' ?>"><b><?= (int)$R['crit'] ?></b>urgent<?= $R['crit'] > 1 ? 's' : '' ?></span><span class="<?= $R['warn'] ? 'warn' : 'ok' ?>"><b><?= (int)$R['warn'] ?></b>à surveiller</span></span><?php endif; ?>
      <span class="hm-s">Travaux, sinistres, contrats, conformité, impayés</span></a>
    <a class="hm-card" href="/planning-ag"><span class="hm-ic"><?= $svg('ag') ?></span><span class="hm-l">Suivi des AG</span>
      <span class="hm-pair"><span class="<?= !empty($O['nopv']) ? 'warn' : 'ok' ?>"><b><?= count($O['nopv'] ?? []) ?></b>PV non remis</span><span class="<?= !empty($O['mandat']) ? 'warn' : 'ok' ?>"><b><?= count($O['mandat'] ?? []) ?></b>mandats à renouveler</span></span>
      <span class="hm-s">Sous 60 jours, hors AG déjà prévue</span></a>
    <a class="hm-card hm-sig" href="/signatures"><span class="hm-ic"><?= $svg('sig') ?></span><span class="hm-l">Signature e-mail</span>
      <?php if ($S && $S['cur']): ?><img src="/signature/banniere?v=<?= (int)$S['cur']['id'] ?>-<?= date('Ymd') ?>" alt="" loading="lazy"><span class="hm-s"><?= e($S['cur']['title']) ?> · <em class="<?= $S['linked'] ? 'ok' : 'warn' ?>"><?= $S['linked'] ? 'Exclaimer relié' : 'Exclaimer pas encore relié' ?></em></span><?php endif; ?></a>
    <?php if ($SV): ?><a class="hm-card" href="/enquete/resultats"><span class="hm-ic"><?= $svg('survey') ?></span><span class="hm-l">Questionnaire équipe</span><b class="hm-v"><?= (int)$SV['n'] ?> <small>/ <?= (int)$SV['staff'] ?></small></b>
      <span class="hm-bar"><i class="g" style="width:<?= $SV['staff'] ? min(100, round($SV['n'] / $SV['staff'] * 100)) : 0 ?>%"></i></span><span class="hm-s">réponses reçues</span></a><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="hm-sec">
  <div class="sec-h"><h2>Vie de l'équipe</h2><a href="/tableau-de-bord">Tableau de bord Équipe →</a></div>
  <div class="hm-grid">
    <a class="hm-card hm-wide" href="<?= $dir ? '/planning-ag' : '/tableau-de-bord' ?>"><span class="hm-ic"><?= $svg('pin') ?></span><span class="hm-l">Prochaines AG</span>
      <?php if (!empty($O['upcoming'])): ?><ul class="hm-ag"><?php foreach (array_slice($O['upcoming'], 0, 4) as $r): ?><li><b><?= e(date('d/m', strtotime($r['ag_date']))) ?></b><span><?= e(mb_strimwidth((string)($r['nom'] ?? $r['code']), 0, 38, '…')) ?></span><small><?= e(trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($r['gest'] ?? '')))) ?></small></li><?php endforeach; ?></ul>
      <?php else: ?><span class="hm-s">Aucune AG dans les 30 prochains jours</span><?php endif; ?></a>
    <a class="hm-card hm-pw" href="/mot-du-president"><span class="hm-ic"><?= $svg('pen') ?></span><span class="hm-l">Mot du Président</span>
      <?php if ($pw = $D['pw']): ?><b class="hm-t"><?= e($pw['title']) ?></b><span class="hm-s"><?= e(pw_month_label($pw['month'])) ?></span><?php else: ?><span class="hm-s">Premier mot le 1er novembre</span><?php endif; ?></a>
    <a class="hm-card" href="/gid"><span class="hm-ic"><?= $svg('news') ?></span><span class="hm-l">Informations</span>
      <?php if ($D['board']): ?><ul class="hm-mini"><?php foreach ($D['board'] as $b): ?><li><?= e(mb_strimwidth((string)$b['title'], 0, 60, '…')) ?></li><?php endforeach; ?></ul><?php else: ?><span class="hm-s">Aucune information</span><?php endif; ?></a>
    <?php $nb = news_blocks(); $nImp = array_sum(array_column($nb, 'imp')); ?>
    <a class="hm-card hm-wide" href="/actualites"><span class="hm-ic"><?= $svg('news') ?></span><span class="hm-l">Actualités du métier<?= $nImp ? ' · <span class="nw-imp"><i></i>' . $nImp . ' important' . ($nImp > 1 ? 's' : '') . '</span>' : '' ?></span>
      <span class="hm-news"><?php foreach (NEWS_CATS as $k => $c): ?><span><b><?= (int)$nb[$k]['n'] ?></b><?= e($c[0]) ?><?= $nb[$k]['imp'] ? ' <i class="nw-dot" aria-label="Important"></i>' : '' ?></span><?php endforeach; ?></span></a>
  </div>
</section>

<?php if ($fam): ?>
<section class="hm-sec">
  <div class="sec-h"><h2>Famille</h2></div>
  <div class="hm-grid">
    <a class="hm-card" href="/voyages"><span class="hm-ic"><?= $svg('plane') ?></span><span class="hm-l">Voyages</span><?php if (!empty($D['trip'])): ?><b class="hm-t"><?= e($D['trip']['destination']) ?></b><span class="hm-s"><?= e((string)$D['trip']['duration']) ?></span><?php else: ?><span class="hm-s">Aucun voyage préparé</span><?php endif; ?></a>
    <a class="hm-card" href="/famille"><span class="hm-ic"><?= $svg('chart') ?></span><span class="hm-l">Tableau de bord Famille</span><span class="hm-s">Honoraires et suivi familial</span></a>
  </div>
</section>
<?php endif; ?>
