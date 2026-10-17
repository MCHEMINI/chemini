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
    <?php elseif ($edit): ?>
      <form id="pwF" class="pw-form" data-month="<?= e($w['month']) ?>">
        <div class="eyebrow">Modifier · <?= e(pw_month_label($w['month'])) ?></div>
        <div class="field"><label for="pw-t">Titre</label><input class="input" id="pw-t" name="title" maxlength="200" value="<?= e($w['title']) ?>" required></div>
        <div class="field"><label for="pw-c">Chapeau</label><textarea class="input" id="pw-c" name="chapo" rows="3" maxlength="800"><?= e((string)$w['chapo']) ?></textarea></div>
        <div class="field"><label for="pw-b">Texte <span class="muted">(« ## » pour un intertitre, « - » pour une liste, **gras**)</span></label><textarea class="input pw-body" id="pw-b" name="body" rows="26" required><?= e($w['body']) ?></textarea></div>
        <p class="form-error" hidden></p>
        <div class="pw-act"><button class="btn btn-primary">Enregistrer le brouillon</button><a class="btn btn-ghost" href="/mot-du-president/<?= e($w['month']) ?>">Annuler</a></div>
      </form>
    <?php else: [$sl, $sc] = $st($w); ?>
      <div class="pw-meta"><span class="eyebrow"><?= e(pw_month_label($w['month'])) ?></span><?php if ($ed): ?><span class="chip <?= $sc ?>"><?= e($sl) ?></span><?php endif; ?></div>
      <h2 class="pw-title"><?= e($w['title']) ?></h2>
      <?php if ($w['chapo']): ?><p class="pw-chapo"><?= e($w['chapo']) ?></p><?php endif; ?>
      <div class="pw-body-r"><?= pw_render($w['body']) ?></div>
      <div class="pw-sign"><span class="ag-av">MC</span><span><b>Mehdi Chemini</b><small>Président de GID</small></span></div>
      <?php if ($src): ?><details class="pw-src"><summary>Sources (<?= count($src) ?>)</summary><ul><?php foreach ($src as $s): ?><li><a href="<?= e($s['url'] ?? '#') ?>" target="_blank" rel="noopener"><?= e($s['title'] ?? $s['url'] ?? '') ?></a></li><?php endforeach; ?></ul></details><?php endif; ?>
      <?php if ($ed): ?>
      <div class="pw-act">
        <a class="btn btn-ghost" href="/mot-du-president/<?= e($w['month']) ?>?modifier">Modifier</a>
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
    <?php if ($ed): ?><div class="panel pw-how"><h3>Fonctionnement</h3><ol><li>Le 25 de chaque mois, un brouillon est rédigé d'après les actualités du mois.</li><li>Vous le relisez et le modifiez si besoin.</li><li>« Valider la publication » : il paraît le 1er pour toute l'équipe.</li><li>Sans validation, il reste en brouillon et une alerte s'affiche sur votre tableau de bord.</li></ol></div><?php endif; ?>
  </aside>
</div>
