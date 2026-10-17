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
  <section class="panel ws-wide ws-visits">
    <div class="sec-h"><h2>Fréquentation</h2><span class="chip warn">Aucune mesure installée</span></div>
    <p class="muted">Le site n'a pas encore d'outil de mesure d'audience : visites, pages vues, provenance des visiteurs, demandes de contact. Pour afficher ces chiffres ici, il faut en installer un sur WordPress, en accord avec le bandeau de consentement Axeptio déjà en place :</p>
    <ul class="ws-opts">
      <li><b>Site Kit by Google</b> · Google Analytics 4 et Search Console (recherches qui amènent sur le site) : le plus complet.</li>
      <li><b>Matomo</b> ou <b>WP Statistics</b> · mesure hébergée sur le site, sans transfert à Google, compatible RGPD sans consentement.</li>
    </ul>
  </section>
</div>
<?php endif; ?>
