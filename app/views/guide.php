<?php $ns = $g ? guide_steps($g['body']) : 0; ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/guides">Guides</a><?= $g ? ' · ' . e($g['cat']) : '' ?></div>
    <h1><?= $g ? '<span class="gd-h-ic" aria-hidden="true">' . e($g['icon'] ?: '📘') . '</span> ' . e($g['title']) : 'Nouveau guide' ?></h1>
    <?php if ($g && !$edit): ?><div class="date"><?= e((string)$g['summary']) ?></div><?php endif; ?>
  </div>
</header>
<?php if ($edit): ?>
<form class="panel gd-form" id="gdF" data-id="<?= (int)($g['id'] ?? 0) ?>">
  <div class="gd-fgrid">
    <div class="field gd-wide"><label for="gd-t">Titre</label><input class="input" id="gd-t" name="title" maxlength="120" required value="<?= e((string)($g['title'] ?? '')) ?>" placeholder="Ex. Saisir une facture fournisseur"></div>
    <div class="field"><label for="gd-i">Icône</label><input class="input" id="gd-i" name="icon" maxlength="4" value="<?= e((string)($g['icon'] ?? '📘')) ?>"></div>
    <div class="field"><label for="gd-c">Catégorie</label><input class="input" id="gd-c" name="cat" maxlength="40" list="gdCats" required value="<?= e((string)($g['cat'] ?? 'ImmOpen')) ?>"><datalist id="gdCats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></div>
    <div class="field"><label for="gd-d">Durée</label><input class="input" id="gd-d" name="duration" maxlength="20" value="<?= e((string)($g['duration'] ?? '5 min')) ?>"></div>
    <div class="field"><label for="gd-l">Niveau</label><select class="input" id="gd-l" name="level"><?php foreach (['Débutant', 'Intermédiaire', 'Avancé'] as $lv): ?><option<?= ($g['level'] ?? 'Débutant') === $lv ? ' selected' : '' ?>><?= $lv ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="gd-a">Visible par</label><select class="input" id="gd-a" name="audience"><?php foreach (GUIDE_AUD as $k => $lb): ?><option value="<?= $k ?>"<?= ($g['audience'] ?? 'tous') === $k ? ' selected' : '' ?>><?= e($lb) ?></option><?php endforeach; ?></select></div>
    <div class="field gd-wide"><label for="gd-s">Résumé</label><input class="input" id="gd-s" name="summary" maxlength="240" value="<?= e((string)($g['summary'] ?? '')) ?>"></div>
  </div>
  <div class="field"><label for="gd-b">Procédure</label>
    <p class="muted small gd-help">« ## Titre » pour une partie · « 1. » pour une étape à cocher · « - » pour une liste · « &gt; Astuce : … » · « ! » pour un avertissement · **gras** · `nom de fichier` · Menu › Sous-menu · [[Libellé|/adresse]] pour un bouton vers une page</p>
    <textarea class="input gd-ta" id="gd-b" name="body" rows="22" required><?= e((string)($g['body'] ?? "## Préparer\n1. **Première étape.** Détail.\n2. **Deuxième étape.**\n\n> Astuce : …")) ?></textarea></div>
  <p class="form-error" hidden></p>
  <div class="gd-act"><button class="btn btn-primary">Enregistrer</button><a class="btn btn-ghost" href="<?= $g ? '/guides/' . e($g['slug']) : '/guides' ?>">Annuler</a>
    <?php if ($g): ?><button type="button" class="btn-link gd-del" data-api="guides/delete" data-id="<?= (int)$g['id'] ?>" data-confirm="Supprimer ce guide ?">Supprimer le guide</button><?php endif; ?></div>
</form>
<?php else: ?>
<div class="gd-wrap" data-guide="<?= e($g['slug']) ?>" data-steps="<?= $ns ?>">
  <article class="panel gd-art">
    <div class="gd-top">
      <span class="gd-chip">⏱ <?= e((string)$g['duration']) ?></span><span class="gd-chip"><?= e((string)$g['level']) ?></span><span class="gd-chip"><?= $ns ?> étapes</span><?php if (($g['audience'] ?? 'tous') !== 'tous'): ?><span class="gd-chip gd-aud"><?= e(GUIDE_AUD[$g['audience']] ?? '') ?></span><?php endif; ?>
      <div class="gd-pbar" role="progressbar" aria-label="Progression" aria-valuemin="0" aria-valuemax="<?= $ns ?>" aria-valuenow="0"><i></i></div><span class="gd-pt mono">0 / <?= $ns ?></span>
    </div>
    <div class="gd-body"><?= guide_render($g['body'], $g['slug']) ?></div>
    <div class="gd-done" hidden>🎉 <b>Bravo, guide terminé !</b> <button type="button" class="btn-link" data-gd-reset>Recommencer</button></div>
  </article>
  <aside class="gd-side">
    <div class="panel"><h3>À propos</h3><p class="muted small">Mis à jour le <?= e(date('d/m/Y', (int)$g['updated_at'])) ?><?= $g['by_name'] ? ' par ' . e($g['by_name']) : '' ?>.</p>
      <?php if ($ed): ?><a class="btn btn-ghost" href="/guides/<?= e($g['slug']) ?>?modifier">Modifier le guide</a><?php else: ?><p class="muted small">Une étape a changé ? Signalez-le à la Direction via la bulle « Tâche équipe ».</p><?php endif; ?></div>
  </aside>
</div>
<?php endif; ?>
