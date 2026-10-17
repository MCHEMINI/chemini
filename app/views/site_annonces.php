<?php
$h1 = 'Annonces'; $lead = 'Les biens en vente ou en location publiés sur le site : changez un statut ou mettez une annonce en ligne sans ouvrir WordPress';
require __DIR__ . '/_site_head.php';
if ($conn && isset($snap['annonces'])):
$tax = $snap['tax'] ?? []; $A = $snap['annonces'];
usort($A, fn($a, $b) => strcmp($b['date'], $a['date']));
$stOrder = ['En vente', 'A louer', 'À louer', 'Sous promesse', 'Vendu', 'Loué'];
$statuts = $tax['statuts'] ?? []; uasort($statuts, fn($a, $b) => (array_search($a, $stOrder) === false ? 9 : array_search($a, $stOrder)) <=> (array_search($b, $stOrder) === false ? 9 : array_search($b, $stOrder)));
$tone = fn(string $s) => match (true) { in_array($s, ['En vente', 'A louer', 'À louer'], true) => 'ok', $s === 'Sous promesse' => 'warn', in_array($s, ['Vendu', 'Loué'], true) => 'neutral', default => 'todo' };
$wpS = ['publish' => ['En ligne', 'ok'], 'draft' => ['Brouillon', 'todo'], 'pending' => ['En attente', 'warn'], 'private' => ['Privée', 'todo'], 'future' => ['Programmée', 'warn']];
$counts = []; foreach ($A as $a) { $k = wp_ann_statut($a, $tax); $counts[$k] = ($counts[$k] ?? 0) + 1; }
?>
<div class="ws-row ws-new"><a class="btn btn-primary" href="/annonces/#nouvelle">+ Nouvelle annonce</a><a class="btn btn-ghost" href="/annonces/">📲 Installer l'appli Annonces</a><span class="muted small">Sur le téléphone, même sans réseau : créer, modifier, supprimer.</span></div>
<div class="ws-bar-f">
  <label class="gd-search" for="wsQ"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="wsQ" type="search" placeholder="Rechercher une annonce : rue, arrondissement…" autocomplete="off"></label>
  <div class="ws-chips" role="group" aria-label="Filtrer par statut"><button type="button" class="hv-chip" data-ws-f="" aria-pressed="true">Toutes <b><?= count($A) ?></b></button><?php foreach ($statuts as $id => $nm): if (empty($counts[$nm])) continue; ?><button type="button" class="hv-chip" data-ws-f="<?= e($nm) ?>" aria-pressed="false"><?= e($nm) ?> <b><?= (int)$counts[$nm] ?></b></button><?php endforeach; ?></div>
</div>
<?php if (!$A): ?><div class="empty"><strong>Aucune annonce sur le site.</strong></div><?php endif; ?>
<div class="ws-list" id="wsList">
  <?php foreach ($A as $a): $st = wp_ann_statut($a, $tax); [$wl, $wc] = $wpS[$a['status']] ?? [$a['status'], 'todo'];
    $loc = ''; foreach ($a['emplacement'] as $id) if (isset($tax['emplacement'][$id])) { $loc = $tax['emplacement'][$id]; break; }
    $typ = []; foreach ($a['types'] as $id) if (isset($tax['types'][$id])) $typ[] = $tax['types'][$id]; ?>
  <article class="panel ws-ann" data-id="<?= $a['id'] ?>" data-st="<?= e($st) ?>" data-s="<?= e(mb_strtolower($a['title'] . ' ' . $loc . ' ' . implode(' ', $typ) . ' ' . $st)) ?>">
    <div class="ws-ann-ph"><?php if (!empty($a['photo_url'])): ?><img src="<?= e($a['photo_url']) ?>" alt="" loading="lazy"><?php else: ?><span aria-hidden="true">🏠</span><?php endif; ?></div>
    <div class="ws-ann-in">
    <div class="ws-ann-m">
      <div class="ws-ann-t"><b><?= e($a['title']) ?></b><small><?= e(implode(' · ', array_filter([$loc, implode(', ', $typ), 'publiée le ' . date('d/m/Y', strtotime($a['date'])), $a['modified'] !== $a['date'] ? 'modifiée le ' . date('d/m/Y', strtotime($a['modified'])) : '']))) ?></small></div>
      <div class="ws-ann-c"><span class="chip <?= $tone($st) ?>" data-ws-chip><?= e($st) ?></span><span class="chip <?= $wc ?>" data-ws-pub><?= e($wl) ?></span></div>
    </div>
    <div class="ws-ann-a">
      <label class="ws-sel"><span class="sr-only">Statut de l'annonce</span><select data-ws-statut aria-label="Statut de l'annonce"><?php foreach ($statuts as $id => $nm): ?><option value="<?= (int)$id ?>"<?= $nm === $st ? ' selected' : '' ?>><?= e($nm) ?></option><?php endforeach; ?></select></label>
      <button type="button" class="btn btn-ghost" data-ws-toggle="<?= $a['status'] === 'publish' ? 'draft' : 'publish' ?>"><?= $a['status'] === 'publish' ? 'Retirer du site' : 'Mettre en ligne' ?></button>
      <a class="btn-link" href="<?= e($a['link']) ?>" target="_blank" rel="noopener">Voir ↗</a>
      <a class="btn-link" href="/annonces/#modifier-<?= (int)$a['id'] ?>">Modifier la fiche</a>
      <a class="btn-link" href="<?= e(wp_edit_link($a['id'])) ?>" target="_blank" rel="noopener">WordPress ↗</a>
    </div>
    </div>
  </article>
  <?php endforeach; ?>
  <p class="empty ws-none" hidden><strong>Aucune annonce trouvée.</strong></p>
</div>
<p class="muted small">« Modifier la fiche » rouvre l'annonce dans l'appli Annonces (texte, photos, prix, mentions obligatoires). Chaque changement est visible aussitôt sur le site.</p>
<?php endif; ?>
