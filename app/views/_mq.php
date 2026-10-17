<?php $MQ = mq_all(); if ($MQ): $pend = count(array_filter($MQ, fn($x) => $x['status'] === 'pending')); ?>
<section class="mq" aria-labelledby="mqT">
  <div class="sec-h"><h2 id="mqT">Maquettes à valider <?php if ($pend): ?><span class="aiq-n"><?= $pend ?></span><?php endif; ?></h2><a href="/assistant">Tâches IA</a></div>
  <div class="mq-grid">
  <?php foreach ($MQ as $m): [$sl, $sc] = MQ_STATUS[$m['status']] ?? [$m['status'], 'todo']; ?>
    <article class="panel mq-card" data-id="<?= (int)$m['id'] ?>">
      <div class="mq-h"><b><?= e($m['title']) ?></b><span class="chip <?= $sc ?>"><?= e($sl) ?></span></div>
      <?php if ($m['description']): ?><p class="mq-d"><?= e($m['description']) ?></p><?php endif; ?>
      <a class="btn btn-ghost mq-open" href="/maquettes/<?= e($m['slug']) ?>" target="_blank" rel="noopener">Voir la maquette ↗</a>
      <?php if ($m['status'] === 'pending'): ?>
      <div class="mq-act">
        <button type="button" class="btn btn-primary" data-mq="validated">Valider</button>
        <button type="button" class="btn btn-ghost" data-mq="changes">Demander des modifications</button>
        <button type="button" class="btn-link" data-mq="refused">Refuser</button>
      </div>
      <div class="mq-form" hidden>
        <label class="sr" for="mqc<?= (int)$m['id'] ?>">Commentaire</label>
        <textarea class="input" id="mqc<?= (int)$m['id'] ?>" rows="3" maxlength="3000" placeholder=""></textarea>
        <p class="muted small mq-hint"></p>
        <div class="mq-act"><button type="button" class="btn btn-primary" data-mq-send>Confirmer</button><button type="button" class="btn-link" data-mq-cancel>Annuler</button></div>
      </div>
      <p class="form-error" hidden></p>
      <?php else: ?>
      <p class="muted small"><?= e($sl) ?><?= $m['by_name'] ? ' par ' . e(explode(' ', $m['by_name'])[0]) : '' ?><?= $m['decided_at'] ? ' le ' . date('d/m/Y', (int)$m['decided_at']) : '' ?><?= $m['consigne_id'] ? ' · Tâche IA ' . e(CONSIGNE_STATUS[$m['c_status']] ?? 'supprimée') : ($m['status'] === 'refused' ? ' · aucune tâche créée' : '') ?><?= $m['comment'] ? ' · « ' . e(mb_strimwidth($m['comment'], 0, 140, '…')) . ' »' : '' ?></p>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
