<?php $sub = $row && $row['submitted_at']; ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/gid">Informations</a> · questionnaire</div>
    <h1>Comment travaillez-vous ?</h1>
    <div class="date">10 minutes pour nous dire vos tâches récurrentes, les demandes des copropriétaires, vos recherches dans ImmOpen et ce que vous aimeriez automatiser. Vos réponses servent à choisir les prochains outils de Dashboard.</div>
  </div>
</header>
<?php if ($sub): ?><p class="tm-note"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Merci, votre questionnaire a été envoyé le <?= e(date('d/m/Y', (int)$row['submitted_at'])) ?>. Vous pouvez encore modifier vos réponses.</p><?php endif; ?>
<form id="svForm" class="sv">
  <?php $i = 0; foreach (SURVEY as [$g, $qs]): $i++; ?>
  <section class="panel sv-sec">
    <div class="sv-h"><span class="sv-n"><?= $i ?></span><h2><?= e($g) ?></h2></div>
    <?php foreach ($qs as $k => [$label, $type, $opts]): $cur = $A[$k] ?? ($type === 'text' ? '' : []); ?>
    <fieldset class="ac-q">
      <legend><?= e($label) ?></legend>
      <?php if ($type === 'text'): ?><textarea class="input bd-ta" name="<?= e($k) ?>" rows="3" maxlength="3000"><?= e((string)$cur) ?></textarea>
      <?php else: ?><div class="ac-opts" data-max="<?= $type === 'check3' ? 3 : ($type === 'check5' ? 5 : 0) ?>"><?php foreach ($opts as $o): ?><label class="ac-opt"><input type="<?= $type === 'radio' ? 'radio' : 'checkbox' ?>" name="<?= e($k) ?>" value="<?= e($o) ?>" <?= in_array($o, (array)$cur, true) ? 'checked' : '' ?>> <?= e($o) ?></label><?php endforeach; ?></div>
      <?php if ($type !== 'radio'): ?><input class="input sv-o" name="o_<?= e($k) ?>" maxlength="500" value="<?= e((string)($A['o_' . $k] ?? '')) ?>" placeholder="Autre (facultatif)"><?php endif; ?><?php endif; ?>
    </fieldset>
    <?php endforeach; ?>
  </section>
  <?php endforeach; ?>
  <div class="sv-bar">
    <span class="muted small" id="svState">Vos réponses sont enregistrées automatiquement.</span>
    <button type="button" class="btn btn-primary" id="svSend"><?= $sub ? 'Mettre à jour mes réponses' : 'Envoyer le questionnaire' ?></button>
  </div>
  <p class="form-error" hidden></p>
</form>
