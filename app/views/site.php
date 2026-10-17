<?php
$h1 = 'Statistiques du site'; $lead = 'Annonces, contenu, référencement et santé technique du site gidsas.fr';
require __DIR__ . '/_site_head.php';
if ($conn && !empty($snap['annonces'])):
$tax = $snap['tax'] ?? []; $A = $snap['annonces'];
$online = array_values(array_filter($A, fn($a) => $a['status'] === 'publish'));
$byStat = []; foreach ($online as $a) { $k = wp_ann_statut($a, $tax); $byStat[$k] = ($byStat[$k] ?? 0) + 1; } arsort($byStat);
$byLoc = []; foreach ($online as $a) { $l = 'Non renseigné'; foreach ($a['emplacement'] as $id) if (isset($tax['emplacement'][$id])) { $l = $tax['emplacement'][$id]; break; }
    // « Paris - 75009 », « PARIS 9 », « Paris – 75009 » : un seul libellé par arrondissement
    if (preg_match('/(?:75|PARIS\s*)0?(\d{1,2})\b/iu', $l, $m) && stripos($l, 'paris') !== false) $l = 'Paris ' . (int)$m[1] . ((int)$m[1] === 1 ? 'er' : 'e');
    $byLoc[$l] = ($byLoc[$l] ?? 0) + 1; } arsort($byLoc);
$months = []; for ($i = 11; $i >= 0; $i--) $months[date('Y-m', strtotime("first day of -$i month"))] = 0;
foreach ($A as $a) { $m = substr($a['date'], 0, 7); if (isset($months[$m])) $months[$m]++; }
$sold = ($byStat['Vendu'] ?? 0) + ($byStat['Loué'] ?? 0); $active = ($byStat['En vente'] ?? 0) + ($byStat['A louer'] ?? 0) + ($byStat['À louer'] ?? 0); $prom = $byStat['Sous promesse'] ?? 0;
$P = $snap['pages'] ?? []; $pubP = array_values(array_filter($P, fn($p) => $p['status'] === 'publish'));
$noDesc = array_values(array_filter($pubP, fn($p) => trim($p['seo_desc']) === '')); $noIdx = array_values(array_filter($pubP, fn($p) => $p['noindex']));
$plA = count(array_filter($snap['plugins'] ?? [], fn($p) => $p['status'] === 'active')); $plI = count($snap['plugins'] ?? []) - $plA;
$last = $A ? max(array_column($A, 'modified')) : null;
$bar = function (array $rows, string $cls = '') { $max = max($rows ?: [1]) ?: 1; $o = '<div class="ws-bars ' . $cls . '">';
    foreach ($rows as $k => $v) $o .= '<div class="ws-bar"><span>' . e((string)$k) . '</span><i><b style="width:' . round($v / $max * 100) . '%"></b></i><em>' . $v . '</em></div>'; return $o . '</div>'; };
?>
<div class="ws-freq">
  <section class="panel ws-visits" id="frequentation">
    <?php $pd = in_array((int)($_GET['p'] ?? 30), [7, 30, 90], true) ? (int)($_GET['p'] ?? 30) : 30; $R = stats_report($pd); $snip = '<script defer src="' . BASE_URL . '/t.js"></script>'; ?>
    <div class="sec-h"><h2>Fréquentation</h2><span class="ws-per"><?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $k => $l): ?><a href="?p=<?= $k ?>#frequentation" class="<?= $pd === $k ? 'on' : '' ?>"><?= $l ?></a><?php endforeach; ?></span></div>
    <?php if (!$R['total']): ?>
      <p class="muted">La mesure d'audience de Chemini n'est pas encore installée sur le site. Elle fonctionne <b>sans cookie ni service extérieur</b> (aucun bandeau de consentement supplémentaire) : visiteurs, pages et annonces vues, provenance, appareils, appels, e-mails et formulaires envoyés.</p>
      <ol class="ws-how-l">
        <li>Dans WordPress : <b>Elementor › Code personnalisé › Ajouter</b>, emplacement « &lt;head&gt; », puis collez cette ligne et publiez :</li>
      </ol>
      <div class="ws-snip"><input class="input mono" readonly value="<?= e($snip) ?>" data-select aria-label="Code de mesure à coller"><button type="button" class="btn btn-ghost" data-copy="<?= e($snip) ?>">Copier</button></div>
      <p class="muted small">Les visites des personnes connectées à WordPress ne sont pas comptées. Les premiers chiffres apparaissent ici dès la première visite.</p>
    <?php else: $evo = $R['prev_visitors'] ? round(($R['visitors'] / $R['prev_visitors'] - 1) * 100) : null; $ev = $R['events']; ?>
      <div class="ws-kpis">
        <div><b><?= number_format($R['visitors'], 0, ',', ' ') ?></b><span>Visiteurs</span><?php if ($evo !== null): ?><small class="<?= $evo >= 0 ? 'up' : 'dn' ?>"><?= $evo >= 0 ? '+' : '' ?><?= $evo ?> % vs période précédente</small><?php endif; ?></div>
        <div><b><?= number_format($R['views'], 0, ',', ' ') ?></b><span>Pages vues</span><small><?= $R['visitors'] ? number_format($R['views'] / $R['visitors'], 1, ',', '') : '0' ?> par visite</small></div>
        <div><b><?= $R['contacts'] ?></b><span>Contacts</span><small><?= (int)($ev['tel'] ?? 0) ?> appel<?= ($ev['tel'] ?? 0) > 1 ? 's' : '' ?> · <?= (int)($ev['mail'] ?? 0) ?> e-mail<?= ($ev['mail'] ?? 0) > 1 ? 's' : '' ?> · <?= (int)($ev['form'] ?? 0) ?> formulaire<?= ($ev['form'] ?? 0) > 1 ? 's' : '' ?></small></div>
        <div><b><?= $R['bounce'] ?> %</b><span>Visites d'une seule page</span><small>taux de rebond</small></div>
      </div>
      <div class="ws-cols ws-daily" style="grid-template-columns:repeat(<?= count($R['daily']) ?>,minmax(0,1fr))"><?php $mx = max(array_column($R['daily'], 'v') ?: [1]) ?: 1; foreach ($R['daily'] as $dday => $x): ?><div class="ws-col" title="<?= e(date('d/m', strtotime($dday))) ?> : <?= $x['v'] ?> visiteur<?= $x['v'] > 1 ? 's' : '' ?>, <?= $x['p'] ?> pages vues"><i style="height:<?= max(2, round($x['v'] / $mx * 100)) ?>%"></i><?php if ($pd <= 30 && ((int)date('N', strtotime($dday)) === 1 || $pd === 7)): ?><span><?= e(date('d/m', strtotime($dday))) ?></span><?php endif; ?></div><?php endforeach; ?></div>
      <div class="ws-grid ws-sub">
        <div><h3>Pages les plus vues</h3><?= $bar(array_combine(array_map(fn($x) => ($x['title'] ? preg_replace('/\s[–|-]\s.*$/u', '', $x['title']) : $x['path']) . ' ', $R['pages']) ?: [], array_map('intval', array_column($R['pages'], 'n')) ?: [])) ?></div>
        <div><h3>Annonces les plus vues</h3><?= $R['annonces'] ? $bar(array_combine(array_map(fn($x) => ($x['title'] ? preg_replace('/\s[–|-]\s(GID|Gid).*$/u', '', $x['title']) : basename($x['path'])) . ' ', $R['annonces']), array_map('intval', array_column($R['annonces'], 'n')))) : '<p class="muted small">Aucune annonce vue sur la période.</p>' ?></div>
        <div><h3>Provenance des visiteurs</h3><?= $bar($R['channels']) ?><?php if ($R['sources']): ?><p class="muted small">Principaux sites : <?= e(implode(', ', array_map(fn($k, $v) => $k . ' (' . $v . ')', array_keys($R['sources']), $R['sources']))) ?></p><?php endif; ?></div>
        <div><h3>Appareils</h3><?= $bar($R['devices']) ?>
          <?php if ($R['last']): ?><h3>Derniers contacts</h3><ul class="ws-last"><?php foreach ($R['last'] as $l): ?><li><span class="chip neutral"><?= e(STATS_KINDS[$l['kind']] ?? $l['kind']) ?></span> <?= e(date('d/m H:i', (int)$l['ts'])) ?> · <?= e($l['path']) ?></li><?php endforeach; ?></ul><?php endif; ?></div>
      </div>
      <p class="muted small">Mesure Chemini, sans cookie · dernière visite enregistrée le <?= e(date('d/m/Y à H:i', $R['last_hit'])) ?> · <a href="#" data-copy="<?= e($snip) ?>">copier le code de mesure</a></p>
    <?php endif; ?>
  </section>
</div>
<div class="st-tiles ws-tiles">
  <div class="m"><b><?= count($online) ?></b><span>Annonces en ligne</span><small><?= count($A) - count($online) ?> en brouillon ou privées</small></div>
  <div class="g"><b><?= $active ?></b><span>À vendre ou à louer</span><small>biens disponibles</small></div>
  <div class="o"><b><?= $prom ?></b><span>Sous promesse</span><small>en cours de signature</small></div>
  <div class="r ws-sold"><b><?= $sold ?></b><span>Vendus ou loués</span><small><?= count($online) ? round($sold / count($online) * 100) : 0 ?> % des annonces en ligne</small></div>
</div>

<div class="ws-grid">
  <section class="panel">
    <div class="sec-h"><h2>Annonces par statut</h2><a href="/site-web/annonces">Gérer les annonces</a></div>
    <?= $bar($byStat) ?>
  </section>
  <section class="panel">
    <div class="sec-h"><h2>Annonces par secteur</h2><span class="muted small">en ligne</span></div>
    <?= $bar(array_slice($byLoc, 0, 10, true)) ?>
  </section>
  <section class="panel ws-wide">
    <div class="sec-h"><h2>Annonces publiées par mois</h2><span class="muted small">12 derniers mois · dernière mise à jour le <?= $last ? e(date('d/m/Y', strtotime($last))) : '…' ?></span></div>
    <div class="ws-cols"><?php $mx = max($months ?: [1]) ?: 1; foreach ($months as $m => $v): ?><div class="ws-col" title="<?= e(MOIS_LONG[(int)substr($m, 5) - 1] . ' ' . substr($m, 0, 4)) ?> : <?= $v ?> annonce<?= $v > 1 ? 's' : '' ?>"><em><?= $v ?: '' ?></em><i style="height:<?= max(3, round($v / $mx * 100)) ?>%"></i><span><?= e(mb_substr(MOIS[(int)substr($m, 5) - 1], 0, 4)) ?></span></div><?php endforeach; ?></div>
  </section>
  <section class="panel">
    <div class="sec-h"><h2>Référencement (SEO)</h2><a href="/site-web/pages">Voir les pages</a></div>
    <ul class="ws-checks">
      <li class="<?= $noDesc ? 'warn' : 'ok' ?>"><b><?= count($pubP) - count($noDesc) ?> / <?= count($pubP) ?></b> pages publiées avec une méta-description<?= $noDesc ? ' · à compléter : ' . e(implode(', ', array_slice(array_column($noDesc, 'title'), 0, 4))) . (count($noDesc) > 4 ? '…' : '') : '' ?></li>
      <li class="<?= $noIdx ? 'warn' : 'ok' ?>"><b><?= count($noIdx) ?></b> page<?= count($noIdx) > 1 ? 's' : '' ?> exclue<?= count($noIdx) > 1 ? 's' : '' ?> des moteurs de recherche (noindex)</li>
      <li class="<?= !empty($snap['settings']) && empty($snap['settings']['blog_public']) ? 'warn' : 'ok' ?>"><?= !empty($snap['settings']) && empty($snap['settings']['blog_public']) ? 'Le site demande aux moteurs de ne pas l\'indexer (normal en préproduction, à lever à la mise en ligne)' : 'Le site est ouvert aux moteurs de recherche' ?></li>
    </ul>
  </section>
  <section class="panel">
    <div class="sec-h"><h2>Santé technique</h2><span class="muted small">WordPress · contrôles du site</span></div>
    <ul class="ws-checks">
      <?php foreach ($snap['health'] ?? [] as $h): ?><li class="<?= $h['status'] === 'good' ? 'ok' : ($h['status'] === 'critical' ? 'crit' : 'warn') ?>"><b><?= e($h['label']) ?></b> · <?= e($h['text']) ?></li><?php endforeach; ?>
      <li class="ok"><b><?= $plA ?></b> extensions actives<?= $plI ? ' · ' . $plI . ' inactive' . ($plI > 1 ? 's' : '') . ' (à supprimer si inutile)' : '' ?></li>
      <li class="ok"><b><?= (int)$snap['media'] ?></b> médias · <b><?= count($pubP) ?></b> pages publiées · <b><?= (int)$snap['users'] ?></b> compte<?= (int)$snap['users'] > 1 ? 's' : '' ?> WordPress<?= !empty($snap['forms']) ? ' · formulaires : ' . e(implode(', ', $snap['forms'])) : '' ?></li>
    </ul>
  </section>

</div>
<?php endif; ?>
