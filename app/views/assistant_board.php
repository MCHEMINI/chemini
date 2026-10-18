<?php
$ST = ['pending' => ['À valider', 'todo'], 'todo' => ['En attente', 'todo'], 'doing' => ['En cours', 'warn'], 'done' => ['Fait', 'ok']];
$all = array_merge($ia_todo, $ia_doing, $ia_done);
$cnt = ['pending' => 0, 'todo' => 0, 'doing' => 0, 'done' => 0]; foreach ($all as $c) $cnt[$c['status']] = ($cnt[$c['status']] ?? 0) + 1;
$iaTtl = fn(string $b) => mb_strimwidth(trim(strtok(str_replace("\r", '', $b), "\n")) ?: $b, 0, 110, '…');
$mois = MOIS_LONG[(int)date('n') - 1] ?? '';
?>
<header class="head">
  <div>
    <div class="eyebrow">Perso · visible par vous seul</div>
    <h1>Tâches IA</h1>
    <div class="date">Consignes vérifiées et traitées à chaque passage de l'assistant<?= can_space($u, 'direction') ? '' : ' · les vôtres sont d\'abord validées par la Direction' ?></div>
  </div>
  <div class="head-actions"><button type="button" class="btn btn-primary" data-ia-open>Nouvelle consigne</button></div>
</header>

<?php if (!empty($ia_pending)): ?>
<section class="ia-pend" aria-labelledby="iaPendT">
  <div class="sec-h"><h2 id="iaPendT">Consignes de l'équipe à valider <span class="aiq-n"><?= count($ia_pending) ?></span></h2><span class="muted small">Validée : traitée au prochain passage · refusée : supprimée</span></div>
  <ul class="ia-rows">
    <?php foreach ($ia_pending as $c): ?>
    <li class="ia-row" data-cid="<?= (int)$c['id'] ?>">
      <details>
        <summary><i class="ia-dot2 st-pending" aria-hidden="true"></i><span class="ia-tt"><?= e($iaTtl($c['body'])) ?></span><span class="ia-who"><?= e($c['asker']) ?></span><span class="ia-when"><?= e(mb_strtolower(ago((int)$c['created_at']))) ?></span></summary>
        <div class="ia-det"><div class="ia-full"><?= nl2br(e($c['body'])) ?></div><div class="ia-meta"><?= e(CONSIGNE_SPACES[$c['space'] ?? 'perso'] ?? 'Perso') ?> · priorité <?= e(mb_strtolower(CONSIGNE_PRIO[$c['priority']] ?? '')) ?></div></div>
      </details>
      <div class="ia-pend-a"><button type="button" class="btn btn-primary" data-ia-dec="approve">Valider</button><button type="button" class="btn btn-ghost" data-ia-dec="reject">Refuser</button></div>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="ia-wrap">
  <div class="ia-filters ws-chips" role="group" aria-label="Filtrer par statut">
    <button type="button" class="hv-chip" data-iaf="" aria-pressed="true">Toutes <b><?= count($all) ?></b></button>
    <?php foreach ($ST as $k => [$l]): if (!$cnt[$k]) continue; ?><button type="button" class="hv-chip" data-iaf="<?= $k ?>" aria-pressed="false"><?= e($k === 'done' ? 'Fait en ' . $mois : $l) ?> <b><?= $cnt[$k] ?></b></button><?php endforeach; ?>
  </div>
  <?php if (!$all): ?>
    <div class="empty"><strong>Aucune consigne.</strong>Cliquez sur « Nouvelle consigne » pour confier une tâche à l'assistant.</div>
  <?php else: ?>
  <ul class="ia-rows" id="iaRows">
    <?php foreach ($all as $i => $c): $st = $c['status']; [$sl, $sc] = $ST[$st] ?? [$st, 'todo']; ?>
    <li class="ia-row" data-st="<?= e($st) ?>"<?= $i >= 15 ? ' hidden data-more-x' : '' ?>>
      <details>
        <summary><i class="ia-dot2 st-<?= e($st) ?>" aria-hidden="true"></i><span class="ia-tt"><?= e($iaTtl($c['body'])) ?></span><span class="chip ia-sp"><?= e(CONSIGNE_SPACES[$c['space'] ?? 'perso'] ?? 'Perso') ?></span><span class="ia-when"><?= e(mb_strtolower(ago((int)($st === 'done' && $c['done_at'] ? $c['done_at'] : $c['created_at'])))) ?></span><span class="chip <?= $sc ?>"><?= e($sl) ?></span></summary>
        <div class="ia-det">
          <div class="ia-full"><?= nl2br(e($c['body'])) ?></div>
          <div class="ia-meta">Priorité <?= e(mb_strtolower(CONSIGNE_PRIO[$c['priority']] ?? '')) ?> · déposée <?= e(mb_strtolower(ago((int)$c['created_at']))) ?><?= $st === 'done' && $c['done_at'] ? ' · faite ' . e(mb_strtolower(ago((int)$c['done_at']))) : '' ?><?= $st === 'pending' ? ' · en attente de validation par la Direction' : '' ?></div>
          <?php if (!empty($c['result'])): ?><div class="ia-result"><b>Résultat</b><br><?= nl2br(e($c['result'])) ?></div><?php endif; ?>
          <?php if ($st !== 'doing'): ?><button type="button" class="btn-link small" data-api="consignes/delete" data-id="<?= (int)$c['id'] ?>" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?>
        </div>
      </details>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php if (count($all) > 15): ?><button type="button" class="ia-more" id="iaMore">Voir plus (<?= count($all) - 15 ?>) ↓</button><?php endif; ?>
  <?php endif; ?>
  <?php if ($ia_done_all > 0): ?><a class="ia-more" href="/versioning?f=ia">Historique complet (<?= (int)$ia_done_all ?>) →</a><?php endif; ?>
</section>
