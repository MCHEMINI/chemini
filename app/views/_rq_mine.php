<?php $RQ = rq_mine((int)$u['id']); $unseen = count(array_filter($RQ, fn($x) => $x['status'] === 'answered' && !$x['seen_at'])); ?>
<section class="col-12 rq-mine" aria-labelledby="rqT"<?= $unseen ? ' data-rq-unseen' : '' ?>>
  <div class="sec-h"><h2 id="rqT">Mes demandes à la Direction <?php if ($unseen): ?><span class="aiq-n"><?= $unseen ?></span><?php endif; ?></h2><span class="muted small">Visibles uniquement par vous et la Direction</span></div>
  <details class="panel rq-new"<?= $RQ ? '' : ' open' ?>>
    <summary>+ Nouvelle demande</summary>
    <form class="rq-form">
      <div class="rq-row">
        <label class="field"><span>Sujet</span><select class="input" name="cat"><?php foreach (RQ_CATS as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
        <label class="field rq-subj"><span>Objet</span><input class="input" name="subject" maxlength="160" required placeholder="Ex. : congés du 22 au 26 décembre"></label>
      </div>
      <label class="field"><span>Votre demande</span><textarea class="input" name="body" rows="4" maxlength="4000" required placeholder="Expliquez votre demande et son contexte : dates, motif, ce que vous proposez…"></textarea></label>
      <p class="form-error" hidden></p>
      <div class="rq-act"><button class="btn btn-primary">Envoyer à la Direction</button><span class="muted small">Vous recevrez la réponse ici.</span></div>
    </form>
  </details>
  <?php if ($RQ): ?>
  <ul class="rq-list">
    <?php foreach ($RQ as $x): [$sl, $sc] = RQ_STATUS[$x['status']] ?? [$x['status'], 'todo']; if ($x['status'] === 'drafted') [$sl, $sc] = RQ_STATUS['drafted']; ?>
    <li class="panel rq-item<?= $x['status'] === 'answered' && !$x['seen_at'] ? ' rq-fresh' : '' ?>">
      <div class="rq-h"><div><b><?= e($x['subject']) ?></b><small><?= e(RQ_CATS[$x['cat']] ?? $x['cat']) ?> · envoyée le <?= date('d/m/Y', (int)$x['created_at']) ?></small></div><span class="chip <?= $sc ?>"><?= e($x['status'] === 'new' ? 'En cours d\'examen' : $sl) ?></span></div>
      <details class="rq-body"><summary>Ma demande</summary><p><?= nl2br(e($x['body'])) ?></p></details>
      <?php if ($x['status'] === 'answered'): ?><div class="rq-ans rq-<?= e((string)$x['decision']) ?>"><span class="eyebrow"><?= $x['decision'] === 'accept' ? 'Demande acceptée' : ($x['decision'] === 'refuse' ? 'Demande refusée' : 'Réponse de la Direction') ?> · le <?= date('d/m/Y', (int)$x['answered_at']) ?></span><p><?= nl2br(e((string)$x['answer'])) ?></p></div>
      <?php elseif ($x['status'] !== 'withdrawn'): ?><button type="button" class="btn-link" data-rq-withdraw="<?= (int)$x['id'] ?>">Retirer ma demande</button><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</section>
