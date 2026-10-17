<?php
/* Une tâche d'équipe : $t, $u, $isDir, $showWho (bool), $people, $hidden */
$today = date('Y-m-d'); $tDone = !empty($t["done_at"]); $can = team_can_edit($u, $t);
$late = !$tDone && $t['due'] && $t['due'] < $today; $isToday = !$tDone && $t['due'] === $today;
$fd = fn($d) => date('d/m/Y', is_numeric($d) ? (int)$d : strtotime($d));
?>
<li class="tt<?= $tDone ? ' done' : '' ?><?= $late ? ' late' : '' ?>"<?= !empty($hidden) ? ' hidden data-more-x' : '' ?>>
  <input type="checkbox" id="tt<?= (int)$t['id'] ?>" <?= $tDone ? 'checked' : '' ?> <?= $can ? 'data-api="team/toggle" data-id="' . (int)$t['id'] . '"' : 'disabled' ?> aria-label="<?= $tDone ? 'Marquer à faire' : 'Marquer comme faite' ?>">
  <label for="tt<?= (int)$t['id'] ?>">
    <span class="tt-t"><?= e($t['title']) ?></span>
    <span class="tt-m">
      <?php if (!empty($showWho)): ?><a href="/equipe/<?= e($t['person']) ?>"><?= e($people[$t['person']]['name'] ?? $t['person']) ?></a> · <?php endif; ?>
      <?php if ((int)($t['priv'] ?? 0)): ?>Ajoutée le <?= $fd($t['created_at']) ?><?php else: ?>Demandée le <?= $fd($t['created_at']) ?><?= $t['by_name'] ? ' par ' . e($t['by_name']) : '' ?><?php endif; ?><?= (int)$t['by_dir'] ? ' <span class="tt-dir">Direction</span>' : '' ?><?= (int)($t['priv'] ?? 0) ? ' <span class="tt-dir tt-priv">Perso</span>' : '' ?>
      <?php if ($tDone): ?> · traitée le <?= $fd($t['done_at']) ?><?= $t['done_name'] && !(int)($t['priv'] ?? 0) ? ' par ' . e($t['done_name']) : '' ?><?php endif; ?>
    </span>
  </label>
  <span class="tt-r">
    <?php if ($t['due'] && !$tDone): ?><span class="chip <?= $late ? 'crit' : ($isToday ? 'warn' : 'neutral') ?>"><?= $late ? 'En retard · ' . date('d/m', strtotime($t['due'])) : ($isToday ? 'Aujourd\'hui' : 'Pour le ' . date('d/m', strtotime($t['due']))) ?></span><?php endif; ?>
    <?php if ((int)$t['created_by'] === (int)$u['id'] || ($isDir && !(int)($t['priv'] ?? 0))): ?><button type="button" class="tt-del" data-api="team/delete" data-id="<?= (int)$t['id'] ?>" data-confirm="Supprimer ?" aria-label="Supprimer la tâche">×</button><?php endif; ?>
  </span>
</li>
