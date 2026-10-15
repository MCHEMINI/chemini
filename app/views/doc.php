<?php $err = $_GET['erreur'] ?? null; ?>
<header class="head doc-head">
  <div>
    <div class="eyebrow">Espace commun</div>
    <h1><?= e($title) ?></h1>
    <div class="date">
      <?php if ($doc): ?>
        <?= e($doc['title']) ?> · importé le <?= e(date_short((int)$doc['updated_at'])) ?><?= $doc['by_name'] ? ' par ' . e($doc['by_name']) : '' ?>
        <?php if ($state): ?> · dernière modification <?= e(mb_strtolower(ago((int)$state['updated_at']))) ?><?= $state['by_name'] ? ' par ' . e($state['by_name']) : '' ?><?php endif; ?>
      <?php else: ?>Partagé entre toute l'équipe<?php endif; ?>
    </div>
  </div>
  <?php if ($doc): ?>
  <div class="head-actions">
    <a class="btn btn-ghost" href="/<?= e($slug) ?>/vue" target="_blank" rel="noopener">Plein écran</a>
    <button class="btn btn-ghost" type="button" data-open="dlgImport">Remplacer le fichier</button>
  </div>
  <?php endif; ?>
</header>

<?php if ($err): ?><p class="form-error" style="margin-bottom:16px">
  <?= $err === 'format' ? 'Ce fichier n\'est pas une page HTML.' : ($err === 'session' ? 'Session expirée, réessayez.' : 'Fichier refusé (8 Mo maximum).') ?></p><?php endif; ?>

<?php if ($doc): ?>
  <div class="doc-frame"><iframe src="/<?= e($slug) ?>/vue" title="<?= e($title) ?>"></iframe></div>
  <p class="muted small" style="margin-top:10px">Les modifications sont enregistrées pour toute l'équipe. Si deux personnes modifient en même temps, la dernière enregistrée l'emporte.</p>
<?php else: ?>
  <div class="empty"><strong>Aucun tableau importé.</strong>Importez le fichier HTML du tableau des honoraires : il sera visible et modifiable par toute l'équipe.
    <form class="import-form" method="post" action="/<?= e($slug) ?>/import" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
      <label for="file-<?= e($slug) ?>" class="sr">Fichier HTML</label>
      <input id="file-<?= e($slug) ?>" type="file" name="file" accept=".html,.htm,text/html" required>
      <button class="btn btn-primary">Importer</button>
    </form>
  </div>
<?php endif; ?>

<dialog id="dlgImport" class="dlg">
  <form class="stack" method="post" action="/<?= e($slug) ?>/import" enctype="multipart/form-data">
    <div class="eyebrow">Espace commun</div>
    <h2>Remplacer le fichier</h2>
    <p class="muted">Le nouveau fichier devient la référence pour toute l'équipe. Les modifications faites sur la version actuelle seront remplacées par ses données.</p>
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <label for="file-r-<?= e($slug) ?>" class="sr">Fichier HTML</label>
    <input id="file-r-<?= e($slug) ?>" type="file" name="file" accept=".html,.htm,text/html" required>
    <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-close>Annuler</button><button class="btn btn-primary">Remplacer</button></div>
  </form>
</dialog>
