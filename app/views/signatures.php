<?php $img = BASE_URL . '/signature/banniere'; $lnk = BASE_URL . '/signature/lien'; ?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · e-mails</div>
    <h1>Signatures</h1>
    <div class="date">Le bandeau en bas des signatures e-mail de toute l'équipe : programmez-en un nouveau, il part automatiquement aux dates choisies. Le reste de la signature ne change pas.</div>
  </div>
</header>

<section class="sg-now">
  <div class="sg-mail panel">
    <div class="eyebrow">Signature envoyée aujourd'hui</div>
    <div class="sg-sig">
      <?php if ($tpl && !empty($tpl['html'])): ?><div class="sg-tpl"><?= $tpl['html'] ?></div>
      <?php else: ?>
      <p class="sg-fake"><b>Prénom Nom</b><br>Fonction · GID<br>2 place d'Estienne d'Orves, 75009 Paris · 01 42 66 20 88<br><span class="muted">(partie fixe de la signature Exclaimer, inchangée)</span></p>
      <?php endif; ?>
      <div class="sg-ban"><?php if ($cur): ?><a href="<?= e($cur['link'] ?: '#') ?>" target="_blank" rel="noopener"><img src="/signature/banniere?v=<?= (int)$cur['id'] ?>" alt="<?= e($cur['title']) ?>"></a><?php else: ?><div class="empty"><strong>Aucun bandeau.</strong>Ajoutez-en un ci-dessous.</div><?php endif; ?></div>
    </div>
    <?php if ($cur): ?><p class="muted small">« <?= e($cur['title']) ?> »<?= $cur['link'] ? ' · lien : ' . e($cur['link']) : '' ?><?= $cur['end'] ? ' · jusqu\'au ' . e(date('d/m/Y', strtotime($cur['end']))) : '' ?></p><?php endif; ?>
  </div>
  <div class="panel sg-int">
    <h3>Mise à jour automatique avec Exclaimer</h3>
    <p class="small">Dans Exclaimer, le bandeau de la signature pointe une fois pour toutes vers ces deux adresses. Ensuite, tout se règle ici : le bon bandeau s'affiche selon les dates, sans retoucher Exclaimer.</p>
    <label class="small muted">Image du bandeau</label><input class="input mono" readonly value="<?= e($img) ?>" onclick="this.select()">
    <label class="small muted">Lien au clic</label><input class="input mono" readonly value="<?= e($lnk) ?>" onclick="this.select()">
  </div>
</section>

<details class="panel bd-new" id="sgForm"<?= $list ? '' : ' open' ?>>
  <summary><span class="btn btn-primary">Nouveau bandeau</span><span class="muted small">PNG, JPG ou GIF, 2 Mo au plus · largeur conseillée 600 px</span></summary>
  <form class="pl-form" id="sgF">
    <div class="field pl-wide"><label for="sg-t">Nom</label><input class="input" id="sg-t" name="title" maxlength="120" required placeholder="Ex. Vœux 2027"></div>
    <div class="field pl-wide"><label for="sg-l">Lien au clic (facultatif)</label><input class="input" id="sg-l" name="link" maxlength="500" placeholder="https://www.gidsas.fr/…"></div>
    <div class="field"><label for="sg-s">Diffusé à partir du</label><input class="input" id="sg-s" name="start" type="date"></div>
    <div class="field"><label for="sg-e">Jusqu'au</label><input class="input" id="sg-e" name="end" type="date"></div>
    <div class="field pl-wide"><label for="sg-i">Image</label><input class="input" id="sg-i" type="file" accept="image/png,image/jpeg,image/gif" required></div>
    <label class="bd-pin"><input type="checkbox" name="is_default" value="1"> Bandeau par défaut (hors périodes programmées)</label>
    <div class="sg-prev" id="sgPrev" hidden><img alt=""></div>
    <p class="form-error" hidden></p>
    <div class="pl-act"><button class="btn btn-primary">Enregistrer</button></div>
  </form>
</details>

<?php if ($list): ?>
<div class="sg-grid">
  <?php foreach ($list as $b): [$st, $c] = sig_state($b); ?>
  <article class="panel sg-card">
    <div class="sg-thumb"><img src="/signatures/image/<?= (int)$b['id'] ?>" alt="" loading="lazy"></div>
    <div class="sg-meta"><b><?= e($b['title']) ?></b><span class="chip <?= $c ?>"><?= e($st) ?></span></div>
    <p class="muted small"><?= $b['start'] || $b['end'] ? 'Du ' . ($b['start'] ? date('d/m/Y', strtotime($b['start'])) : '…') . ' au ' . ($b['end'] ? date('d/m/Y', strtotime($b['end'])) : '…') : 'Sans période' ?><?= $b['is_default'] ? ' · par défaut' : '' ?> · <?= (int)$b['w'] ?>×<?= (int)$b['h'] ?> px</p>
    <?php if ($b['link']): ?><p class="small sg-l"><?= e($b['link']) ?></p><?php endif; ?>
    <div class="sg-act"><?php if (!$b['is_default']): ?><button type="button" class="btn-link" data-api="sig/default" data-id="<?= (int)$b['id'] ?>">Définir par défaut</button><?php endif; ?> <button type="button" class="btn-link" data-api="sig/delete" data-id="<?= (int)$b['id'] ?>" data-confirm="Supprimer ?">Supprimer</button></div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
