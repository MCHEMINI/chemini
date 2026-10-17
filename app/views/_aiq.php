<?php $AQ = aiq_all(); $open = array_values(array_filter($AQ, fn($x) => $x['status'] === 'open')); $done = array_values(array_filter($AQ, fn($x) => $x['status'] !== 'open'));
if ($AQ): ?>
<section class="aiq" aria-labelledby="aiqT">
  <div class="sec-h"><h2 id="aiqT">Questions de l'IA <?php if ($open): ?><span class="aiq-n"><?= count($open) ?></span><?php endif; ?></h2><a href="/assistant">Tâches IA</a></div>
  <?php if ($open): ?>
  <div class="aiq-deck" data-total="<?= count($open) ?>">
    <?php foreach ($open as $i => $x): $opts = json_decode((string)$x['options'], true) ?: []; ?>
    <form class="panel aiq-card" data-id="<?= (int)$x['id'] ?>"<?= $i ? ' hidden' : '' ?>>
      <div class="aiq-top"><span class="eyebrow">Question <?= $i + 1 ?> sur <?= count($open) ?></span><div class="aiq-bar" aria-hidden="true"><i style="width:<?= round(($i + 1) / count($open) * 100) ?>%"></i></div></div>
      <h3><?= e($x['question']) ?></h3>
      <?php if ($x['context']): ?><p class="aiq-ctx"><?= e($x['context']) ?></p><?php endif; ?>
      <div class="aiq-opts" role="<?= $x['multi'] ? 'group' : 'radiogroup' ?>" aria-label="Réponses possibles">
        <?php foreach ($opts as $k => $o): ?><label class="aiq-opt"><input type="<?= $x['multi'] ? 'checkbox' : 'radio' ?>" name="c" value="<?= e($o) ?>"><span><?= e($o) ?></span></label><?php endforeach; ?>
        <?php if ($x['other']): ?><label class="aiq-opt aiq-other"><span class="sr">Autre réponse</span><input type="text" name="other" maxlength="300" placeholder="<?= $opts ? 'Autre réponse…' : 'Votre réponse…' ?>"></label><?php endif; ?>
      </div>
      <p class="form-error" hidden></p>
      <div class="aiq-act"><button class="btn btn-primary">Valider et créer la Tâche IA</button><button type="button" class="btn-link" data-aiq-skip>Passer</button><?php if ($x['multi']): ?><span class="muted small">Plusieurs réponses possibles</span><?php endif; ?></div>
    </form>
    <?php endforeach; ?>
    <div class="panel aiq-end" hidden><strong>Merci, c'est noté.</strong><span>Les corrections sont dans <a href="/assistant">Tâches IA</a> et seront traitées automatiquement.</span></div>
  </div>
  <?php endif; ?>
  <?php if ($done): ?>
  <details class="aiq-done"<?= $open ? '' : ' open' ?>><summary><?= count($done) ?> question<?= count($done) > 1 ? 's' : '' ?> traitée<?= count($done) > 1 ? 's' : '' ?></summary>
    <ul><?php foreach (array_reverse($done) as $x): ?><li><b><?= e($x['question']) ?></b><span><?= $x['status'] === 'skipped' ? 'Passée' : 'Réponse : ' . e((string)$x['answer']) . ($x['by_name'] ? ' · ' . e(explode(' ', $x['by_name'])[0]) : '') ?><?php if ($x['consigne_id']): ?> · Tâche IA <?= e(CONSIGNE_STATUS[$x['c_status']] ?? 'supprimée') ?><?php endif; ?></span></li><?php endforeach; ?></ul>
  </details>
  <?php endif; ?>
</section>
<?php endif; ?>
