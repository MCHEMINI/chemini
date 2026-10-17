<?php
$h1 = 'Pages et référencement'; $lead = 'Les pages du site, leur titre et leur description dans Google (Yoast SEO)';
require __DIR__ . '/_site_head.php';
if ($conn && isset($snap['pages'])):
$P = $snap['pages']; usort($P, fn($a, $b) => [$a['status'] !== 'publish', $a['title']] <=> [$b['status'] !== 'publish', $b['title']]);
$wpS = ['publish' => ['Publiée', 'ok'], 'draft' => ['Brouillon', 'todo'], 'pending' => ['En attente', 'warn'], 'private' => ['Privée', 'todo'], 'future' => ['Programmée', 'warn']];
?>
<div class="panel tbl">
  <table class="ws-tbl">
    <thead><tr><th>Page</th><th>État</th><th>Titre dans Google</th><th>Description dans Google</th><th>Modifiée</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($P as $p): [$sl, $sc] = $wpS[$p['status']] ?? [$p['status'], 'todo']; $dl = mb_strlen($p['seo_desc']); ?>
      <tr>
        <td><b><?= e($p['title'] ?: '(sans titre)') ?></b></td>
        <td><span class="chip <?= $sc ?>"><?= e($sl) ?></span><?= $p['noindex'] ? ' <span class="chip warn" title="Exclue des moteurs de recherche">noindex</span>' : '' ?></td>
        <td class="ws-seo"><?= $p['seo_title'] !== '' ? e($p['seo_title']) : '<span class="muted">—</span>' ?></td>
        <td class="ws-seo"><?php if ($dl === 0): ?><span class="chip warn">À rédiger</span><?php else: ?><?= e($p['seo_desc']) ?> <span class="chip <?= $dl < 70 || $dl > 160 ? 'warn' : 'ok' ?>" title="Longueur idéale : 120 à 160 caractères"><?= $dl ?> car.</span><?php endif; ?></td>
        <td class="mono small"><?= e(date('d/m/Y', strtotime($p['modified']))) ?></td>
        <td class="ws-act"><a class="btn-link" href="<?= e($p['link']) ?>" target="_blank" rel="noopener">Voir ↗</a> <a class="btn-link" href="<?= e(wp_edit_link($p['id'], true)) ?>" target="_blank" rel="noopener">Modifier ↗</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="muted small">Le titre et la description se règlent dans WordPress, en bas de chaque page (encadré Yoast SEO). Une description de 120 à 160 caractères s'affiche entière dans Google.</p>
<?php endif; ?>
