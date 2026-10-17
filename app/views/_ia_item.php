<?php $st = $c['status']; ?>
<li class="ia-<?= e($st) ?>"<?= !empty($hidden) ? ' hidden data-more-x' : '' ?>>
  <i class="ia-dot" aria-hidden="true"></i>
  <div class="ia-body">
    <div class="t"><?= nl2br(e($c['body'])) ?></div>
    <div class="d"><span class="chip ia-sp"><?= e(CONSIGNE_SPACES[$c['space'] ?? 'perso'] ?? 'Perso') ?></span> <span class="ia-prio ia-p-<?= e($c['priority']) ?>"><?= e(CONSIGNE_PRIO[$c['priority']] ?? '') ?></span> <?= !empty($c['owner_label']) ? '· ' . e($c['owner_label']) . ' ' : '' ?>· déposée <?= e(mb_strtolower(ago((int)$c['created_at']))) ?><?= $st === 'done' && $c['done_at'] ? ' · faite ' . e(mb_strtolower(ago((int)$c['done_at']))) : '' ?></div>
    <?php if (!empty($c['result'])): ?><div class="ia-result"><?= nl2br(e($c['result'])) ?></div><?php endif; ?>
    <?php if ($st !== 'doing'): ?><button type="button" class="btn-link small" data-api="consignes/delete" data-id="<?= (int)$c['id'] ?>" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?>
  </div>
</li>
