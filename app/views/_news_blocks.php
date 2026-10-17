<?php /* Blocs des quatre rubriques d'actualité : $blocks (news_blocks()), $cur (rubrique affichée ou null) */ $cur = $cur ?? null; ?>
<div class="nw-blocks">
  <?php foreach (NEWS_CATS as $k => [$lab, $desc, $ic]): $b = $blocks[$k]; ?>
  <a class="nw-b<?= $cur === $k ? ' on' : '' ?>" href="/actualites/<?= $k ?>">
    <span class="nw-b-h"><span class="nw-ic" aria-hidden="true"><?= $ic ?></span><b><?= e($lab) ?></b><span class="nw-n" title="Articles des 30 derniers jours"><?= (int)$b['n'] ?></span></span>
    <?php if ($b['imp']): ?><span class="nw-imp"><i></i><?= (int)$b['imp'] ?> important<?= $b['imp'] > 1 ? 's' : '' ?></span><?php endif; ?>
    <?php if ($b['top']): ?><span class="nw-top"><?php foreach ($b['top'] as $t): ?><span><?= $t['important'] ? '<i class="nw-dot" aria-label="Important"></i>' : '' ?><?= e(mb_strimwidth((string)$t['title'], 0, 95, '…')) ?></span><?php endforeach; ?></span>
    <?php else: ?><span class="nw-top muted"><span>Aucun article ce mois-ci.</span></span><?php endif; ?>
    <span class="nw-go">Voir les articles →</span>
  </a>
  <?php endforeach; ?>
</div>
