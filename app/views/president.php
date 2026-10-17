<?php
$st = fn($x) => pw_published($x) ? ['Publié', 'ok'] : ($x['status'] === 'validated' ? ['Validé · publication le 1er ' . MOIS_LONG[(int)substr($x['month'], 5) - 1], 'ok'] : ['Brouillon', 'warn']);
$src = $w ? (json_decode((string)$w['sources'], true) ?: []) : [];
?>
<header class="head">
  <div>
    <div class="eyebrow">Perso · chaque 1er du mois</div>
    <h1>Mot du Président</h1>
    <div class="date">L'actualité du mois écoulé, l'analyse de nos trois métiers (syndic, gestion locative, transaction) et les tendances à venir.<?= $ed ? ' Rédigé en brouillon à partir des actualités du mois ; il n\'est publié qu\'après votre validation.' : '' ?></div>
  </div>
</header>

<?php if ($ed && ($al = pw_alert())): ?>
<div class="pw-alert"><b><?= $al['word'] ? 'Le mot de ' . e(pw_month_label($al['month'])) . ' attend votre validation.' : 'Le mot de ' . e(pw_month_label($al['month'])) . ' n\'est pas encore rédigé.' ?></b> <?= $al['word'] ? 'Relisez-le, modifiez-le si besoin, puis cliquez sur « Valider la publication ». Sans validation, il reste en brouillon.' : 'Il sera préparé automatiquement à partir des actualités du mois.' ?></div>
<?php endif; ?>

<div class="pw-wrap">
  <article class="pw-art panel">
    <?php if (!$w): ?>
      <div class="empty"><strong>Aucun mot publié pour l'instant.</strong>Le premier paraîtra le 1er du mois.</div>
    <?php else: [$sl, $sc] = $st($w); ?>
      <div class="pw-meta"><span class="eyebrow"><?= e(pw_month_label($w['month'])) ?></span><?php if ($ed): ?><span class="chip <?= $sc ?>"><?= e($sl) ?></span><?php endif; ?></div>
      <?php if ($ed): ?><div class="pw-tools" id="pwTools" hidden role="toolbar" aria-label="Mise en forme">
        <button type="button" data-fmt="h2" title="Intertitre">Intertitre</button><button type="button" data-fmt="p" title="Paragraphe">Paragraphe</button><button type="button" data-fmt="bold" title="Gras"><b>G</b></button><button type="button" data-fmt="ul" title="Liste à puces">• Liste</button>
        <span class="pw-tools-h">Vous modifiez l'article tel qu'il sera publié</span><button type="button" data-act="cancel">Annuler</button><button type="button" data-act="save" class="pw-tsave">Enregistrer</button></div><?php endif; ?>
      <div id="pwDoc" data-month="<?= e($w['month']) ?>"<?= $edit ? ' data-edit' : '' ?>>
      <h2 class="pw-title" data-f="title"><?= e($w['title']) ?></h2>
      <p class="pw-chapo" data-f="chapo"><?= e((string)$w['chapo']) ?></p>
      <div class="pw-body-r" data-f="body"><?= pw_render($w['body']) ?></div>
      </div>
      <div class="pw-sign"><span class="ag-av">MC</span><span><b>Mehdi Chemini</b><small>Président de GID</small></span></div>
      <?php if ($src): ?><details class="pw-src"><summary>Sources (<?= count($src) ?>)</summary><ul><?php foreach ($src as $s): ?><li><a href="<?= e($s['url'] ?? '#') ?>" target="_blank" rel="noopener"><?= e($s['title'] ?? $s['url'] ?? '') ?></a></li><?php endforeach; ?></ul></details><?php endif; ?>
      <?php if ($ed): ?>
      <div class="pw-act">
        <button type="button" class="btn btn-ghost" id="pwEdit">Modifier</button>
        <button type="button" class="btn btn-primary" id="pwSave" hidden>Enregistrer</button><button type="button" class="btn btn-ghost" id="pwCancel" hidden>Annuler</button><span class="form-error" id="pwErr" hidden></span>
        <?php if ($w['status'] !== 'validated'): ?><button type="button" class="btn btn-primary" data-api="president/validate" data-k="<?= e($w['month']) ?>" data-confirm="Valider la publication ?">Valider la publication</button>
        <?php else: ?><button type="button" class="btn-link" data-api="president/unvalidate" data-k="<?= e($w['month']) ?>" data-confirm="Repasser en brouillon ?">Repasser en brouillon</button><?php endif; ?>
      </div>
      <p class="muted small"><?= $w['status'] === 'validated' ? 'Validé le ' . e(date('d/m/Y à H:i', (int)$w['validated_at'])) . ($w['month'] > date('Y-m') ? ' · visible par toute l\'équipe à partir du 1er ' . e(MOIS_LONG[(int)substr($w['month'], 5) - 1]) : ' · visible par toute l\'équipe') : 'Brouillon : visible par vous seul tant qu\'il n\'est pas validé.' ?></p>
      <?php endif; ?>
    <?php endif; ?>
  </article>
  <aside class="pw-side">
    <div class="panel"><h3>Tous les mots</h3>
      <?php if (!$list): ?><p class="muted small">Aucun pour l'instant.</p><?php else: ?>
      <ul class="pw-list"><?php foreach ($list as $x): [$sl, $sc] = $st($x); ?><li class="<?= $w && $x['id'] === $w['id'] ? 'on' : '' ?>"><a href="/mot-du-president/<?= e($x['month']) ?>"><b><?= e(pw_month_label($x['month'])) ?></b><span><?= e($x['title']) ?></span></a><?php if ($ed): ?><span class="chip <?= $sc ?>"><?= e(explode(' ·', $sl)[0]) ?></span><?php endif; ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
    <?php if ($ed && $w): $tk = $w['share_token'] ?? null; ?><div class="panel pw-share" id="pwShare" data-k="<?= e($w['month']) ?>">
      <h3>Partager par lien</h3>
      <p class="muted small">Lien non répertorié : seules les personnes qui le reçoivent peuvent lire ce mot, sans compte. Il n'apparaît nulle part et peut être désactivé à tout moment.</p>
      <div class="pw-share-on"<?= $tk ? '' : ' hidden' ?>>
        <div class="pw-share-row"><input type="text" readonly id="pwShareUrl" value="<?= $tk ? e('/mot/' . $tk) : '' ?>" aria-label="Lien de partage"><button type="button" class="btn btn-primary" data-sh="copy">Copier</button></div>
        <?php if (!pw_published($w)): ?><p class="small pw-share-warn">Ce mot n'est pas encore publié pour l'équipe : le lien montre la version actuelle.</p><?php endif; ?>
        <button type="button" class="btn-link" data-sh="off">Désactiver le lien</button>
      </div>
      <div class="pw-share-off"<?= $tk ? ' hidden' : '' ?>><button type="button" class="btn btn-ghost" data-sh="on">Créer le lien</button></div>
      <span class="form-error" hidden></span>
    </div><?php endif; ?>
    <?php if ($ed): ?><div class="panel pw-how"><h3>Fonctionnement</h3><ol><li>Le 25 de chaque mois, un brouillon est rédigé d'après les actualités du mois.</li><li>Vous le relisez et le modifiez si besoin.</li><li>« Valider la publication » : il paraît le 1er pour toute l'équipe.</li><li>Sans validation, il reste en brouillon et une alerte s'affiche sur votre tableau de bord.</li></ol></div><?php endif; ?>
  </aside>
</div>
