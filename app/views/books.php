<header class="head">
  <div>
    <div class="eyebrow">Perso · privé</div>
    <h1>Mes livres</h1>
    <div class="date">Votre bibliothèque, visible uniquement par <?= e($u['name']) ?> · EPUB et PDF</div>
  </div>
  <div class="bk-actions">
    <label class="btn btn-primary bk-add">Ajouter un livre<input type="file" id="bkFile" accept=".epub,.pdf,application/epub+zip,application/pdf" multiple hidden></label>
  </div>
</header>

<div id="bkUploads" class="bk-uploads" aria-live="polite"></div>

<?php if (!$books): ?>
<div class="empty bk-empty"><strong>Votre bibliothèque est vide.</strong>Ajoutez un livre au format EPUB (recommandé : mise en page adaptée à l'écran, surlignements, notes) ou PDF. Vous pouvez aussi déposer les fichiers sur cette page.</div>
<?php endif; ?>

<ul class="bk-grid" id="bkGrid">
  <?php foreach ($books as $b): $pct = (int)round((float)($b['percent'] ?? 0)); ?>
  <li class="bk-item" data-id="<?= (int)$b['id'] ?>" data-title="<?= e($b['title']) ?>" data-author="<?= e((string)$b['author']) ?>" data-kind="<?= e($b['kind']) ?>" data-cover="<?= $b['cover'] ? '1' : '' ?>">
    <a class="bk-cover" href="/mes-livres/<?= (int)$b['id'] ?>" title="Lire « <?= e($b['title']) ?> »">
      <?php if ($b['cover']): ?><img src="<?= e($b['cover']) ?>" alt=""><?php else: ?><span class="bk-gen"><span><?= e($b['title']) ?></span><small><?= e((string)$b['author']) ?></small></span><?php endif; ?>
      <span class="bk-kind"><?= strtoupper(e($b['kind'])) ?></span>
    </a>
    <div class="bk-meta">
      <a class="bk-title" href="/mes-livres/<?= (int)$b['id'] ?>"><?= e($b['title']) ?></a>
      <?php if ($b['author']): ?><div class="bk-author"><?= e($b['author']) ?></div><?php endif; ?>
      <div class="bk-prog" title="<?= $pct ?> % lu"><i style="width:<?= $pct ?>%"></i></div>
      <div class="bk-foot"><span><?= $pct ? $pct . ' % lu' : 'Nouveau' ?></span>
        <span class="bk-menu"><button type="button" class="btn-link" data-bk-edit>Modifier</button> · <button type="button" class="btn-link" data-bk-del>Supprimer</button></span></div>
    </div>
  </li>
  <?php endforeach; ?>
</ul>

<dialog class="dlg" id="bkEdit">
  <form method="dialog" class="stack">
    <div class="eyebrow">Mes livres</div>
    <h2>Modifier le livre</h2>
    <div class="field"><label for="bkT">Titre</label><input class="input" id="bkT" maxlength="200" required></div>
    <div class="field"><label for="bkA">Auteur</label><input class="input" id="bkA" maxlength="200"></div>
    <div class="dlg-actions"><button class="btn btn-ghost" value="cancel" formnovalidate>Annuler</button><button class="btn btn-primary" value="ok">Enregistrer</button></div>
  </form>
</dialog>
<script src="/assets/vendor/jszip.min.js"></script>
<script src="/assets/epub-lib.js?v=1"></script>
<script src="/assets/books.js?v=1"></script>
