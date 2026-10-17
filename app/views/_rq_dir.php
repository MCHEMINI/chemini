<?php $RD = rq_for_direction(); if ($RD): $todo = count(array_filter($RD, fn($x) => $x['status'] !== 'answered')); ?>
<section class="rqd" aria-labelledby="rqdT">
  <div class="sec-h"><h2 id="rqdT">Demandes des collaborateurs <?php if ($todo): ?><span class="aiq-n"><?= $todo ?></span><?php endif; ?></h2><span class="muted small">Les réponses préparées par l'IA ne sont jamais visibles du collaborateur</span></div>
  <div class="rqd-list">
  <?php foreach ($RD as $x): ?>
    <article class="panel rqd-card" data-id="<?= (int)$x['id'] ?>">
      <div class="rq-h"><div><b><?= e($x['subject']) ?></b><small><?= e($x['user_name']) ?> · <?= e(RQ_CATS[$x['cat']] ?? $x['cat']) ?> · le <?= date('d/m/Y à H:i', (int)$x['created_at']) ?></small></div>
        <span class="chip <?= $x['status'] === 'answered' ? 'ok' : ($x['status'] === 'drafted' ? 'warn' : 'todo') ?>"><?= $x['status'] === 'answered' ? ($x['decision'] === 'accept' ? 'Acceptée' : ($x['decision'] === 'refuse' ? 'Refusée' : 'Répondue')) : ($x['status'] === 'drafted' ? 'Réponses prêtes' : 'Préparation par l\'IA') ?></span></div>
      <blockquote class="rqd-q"><?= nl2br(e($x['body'])) ?></blockquote>
      <?php if ($x['status'] === 'answered'): ?>
        <div class="rq-ans rq-<?= e((string)$x['decision']) ?>"><span class="eyebrow">Réponse publiée<?= $x['by_name'] ? ' par ' . e(explode(' ', $x['by_name'])[0]) : '' ?> · le <?= date('d/m/Y', (int)$x['answered_at']) ?><?= $x['seen_at'] ? ' · lue' : '' ?></span><p><?= nl2br(e((string)$x['answer'])) ?></p></div>
      <?php else: ?>
        <?php if ($x['ai_analysis']): ?><details class="rqd-an"><summary>Analyse de l'IA</summary><p><?= nl2br(e($x['ai_analysis'])) ?></p></details><?php endif; ?>
        <?php if ($x['status'] === 'new'): ?><p class="muted small">L'IA prépare une réponse « accepter » et une réponse « refuser » argumentées. Vous pouvez aussi répondre directement.</p><?php endif; ?>
        <div class="rqd-pick" role="radiogroup" aria-label="Réponse à publier">
          <?php foreach (['accept' => ['Accepter', $x['ai_accept']], 'refuse' => ['Refuser', $x['ai_refuse']], 'other' => ['Autre réponse', '']] as $k => [$lab, $txt]): if ($k !== 'other' && !$txt) continue; ?>
          <label class="aiq-opt"><input type="radio" name="d<?= (int)$x['id'] ?>" value="<?= $k ?>" data-text="<?= e((string)$txt) ?>"><span><?= $lab ?></span></label>
          <?php endforeach; ?>
        </div>
        <div class="rqd-edit" hidden>
          <label class="sr" for="rqa<?= (int)$x['id'] ?>">Réponse au collaborateur</label>
          <textarea class="input" id="rqa<?= (int)$x['id'] ?>" rows="8" maxlength="6000"></textarea>
          <p class="muted small">Personnalisez la réponse avant de la publier : elle sera visible par <?= e(explode(' ', $x['user_name'])[0]) ?> dans son espace.</p>
          <p class="form-error" hidden></p>
          <button type="button" class="btn btn-primary" data-rq-post>Publier la réponse</button>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
