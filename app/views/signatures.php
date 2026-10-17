<?php
$img = BASE_URL . '/signature/banniere'; $lnk = BASE_URL . '/signature/lien';
$def = null; foreach ($list as $b) if ($b['is_default']) { $def = $b; break; }
$hits = json_decode((string)q("SELECT v FROM kv WHERE k='sig_hits'")->fetchColumn(), true) ?: [];
$linked = !empty($hits['ext']) && $hits['ext'] > time() - 30 * 86400;
$fd = fn(?string $d) => $d ? date('d/m/Y', strtotime($d)) : '…';
// Ordre : en cours, programmés (par date), défaut, inactifs, terminés
$rank = fn($b) => ['En cours d\'envoi' => 0, 'Programmé' => 1, 'Par défaut' => 2, 'Inactif' => 3, 'Terminé' => 4][sig_state($b)[0]] ?? 5;
usort($list, fn($a, $b) => [$rank($a), $a['start'] ?? '', -$a['created_at']] <=> [$rank($b), $b['start'] ?? '', -$b['created_at']]);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · e-mails</div>
    <h1>Signatures</h1>
    <div class="date">Seul le visuel du bas des signatures change. Ajoutez un visuel avec sa période : il part automatiquement dans les mails de toute l'équipe aux dates choisies, puis le bandeau par défaut reprend.</div>
  </div>
</header>

<section class="sg-now">
  <div class="panel sg-today">
    <div class="sg-today-h"><div class="eyebrow">Envoyé aujourd'hui</div>
      <span class="chip <?= $linked ? 'ok' : 'warn' ?>" title="<?= $linked ? 'Dernière lecture par Exclaimer ou une messagerie le ' . e(date('d/m/Y à H:i', (int)$hits['ext'])) : 'Exclaimer n\'a pas encore lu le bandeau' ?>"><?= $linked ? 'Exclaimer relié · lu le ' . e(date('d/m à H:i', (int)$hits['ext'])) : 'Exclaimer pas encore relié' ?></span></div>
    <div class="sg-ban"><?php if ($cur): ?><img src="/signature/banniere?v=<?= (int)$cur['id'] ?>-<?= date('Ymd') ?>" alt="<?= e($cur['title']) ?>" width="600" height="200"><?php else: ?><div class="empty"><strong>Aucun bandeau.</strong>Ajoutez un premier visuel : il deviendra le bandeau par défaut.</div><?php endif; ?></div>
    <?php if ($cur): ?><p class="small"><b><?= e($cur['title']) ?></b> · <?= $cur['is_default'] && !$cur['start'] ? 'bandeau par défaut' : 'jusqu\'au ' . e($fd($cur['end'])) . ($def && (int)$def['id'] !== (int)$cur['id'] ? ', puis retour à « ' . e($def['title']) . ' »' : '') ?></p><?php endif; ?>
    <details class="sg-tech"><summary>Réglage Exclaimer (une seule fois)</summary>
      <p class="small muted">Dans la signature Exclaimer, bandeau › Source de l'image › Lier à une image, puis Lien hypertexte :</p>
      <input class="input mono" readonly value="<?= e($img) ?>" onclick="this.select()" aria-label="Adresse de l'image">
      <input class="input mono" readonly value="<?= e($lnk) ?>" onclick="this.select()" aria-label="Adresse du lien">
    </details>
  </div>
</section>

<section class="sg-work">
  <form class="panel sg-add" id="sgF">
    <h2>Ajouter un visuel</h2>
    <label class="sg-drop" for="sg-i">
      <input id="sg-i" type="file" accept="image/png,image/jpeg,image/gif" required>
      <span class="sg-drop-in"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-8 8"/></svg><b>Choisir l'image générée</b><small>PNG ou JPG · 1200 × 400 px idéalement · 2 Mo au plus</small></span>
      <img id="sgPrev" alt="" hidden>
    </label>
    <label class="bd-pin sg-defbox"><input type="checkbox" name="is_default" value="1" id="sg-d" checked> <span><b>Bandeau par défaut</b> (hors périodes programmées)</span></label>
    <fieldset class="sg-period" id="sgPer">
      <legend>Période de diffusion</legend>
      <div class="sg-dates"><div class="field"><label for="sg-s">Du</label><input class="input" id="sg-s" name="start" type="date"></div>
      <div class="field"><label for="sg-e">Au</label><input class="input" id="sg-e" name="end" type="date"></div></div>
      <p class="muted small">Décochez « Bandeau par défaut » pour programmer une période. À la fin de la période, le bandeau par défaut<?= $def ? ' (« ' . e($def['title']) . ' »)' : '' ?> reprend automatiquement.</p>
    </fieldset>
    <details class="sg-opt"><summary>Nom (facultatif)</summary>
      <div class="field"><label for="sg-t">Nom</label><input class="input" id="sg-t" name="title" maxlength="120" placeholder="Rempli automatiquement si vide"></div>
    </details>
    <p class="muted small">Au clic, le bandeau ouvre toujours https://www.gidsas.fr/</p>
    <p class="form-error" hidden></p>
    <button class="btn btn-primary">Ajouter le visuel</button>
  </form>

  <section class="panel sg-make">
    <h2>Générer le visuel avec Gemini</h2>
    <ol class="sg-steps">
      <li><b>Copiez les consignes techniques</b> ci-dessous.</li>
      <li><b>Dans Gemini</b>, décrivez le visuel souhaité (thème, message, ambiance) puis collez les consignes à la suite.</li>
      <li><b>Téléchargez l'image</b> obtenue (format 3:1, 1200 × 400 px).</li>
      <li><b>Ajoutez-la</b> dans « Ajouter un visuel » avec sa période.</li>
    </ol>
    <textarea class="input bd-ta sg-prompt" id="sgPrompt" rows="12" readonly aria-label="Consignes techniques pour Gemini"></textarea>
    <div class="sg-mk-a"><button type="button" class="btn btn-primary" id="sgCopy">Copier les consignes</button><a class="btn btn-ghost" href="https://gemini.google.com/app" target="_blank" rel="noopener">Ouvrir Gemini</a></div>
    <p class="muted small">Format envoyé dans les mails : 600 × 200 px. Si l'image n'est pas exactement en 3:1, Chemini la recadre au centre : gardez le texte loin des bords.</p>
  </section>
</section>

<?php if ($list): ?>
<section>
  <div class="sec-h"><h2>Bandeaux</h2><span class="muted small">Statuts recalculés chaque jour selon les dates</span></div>
  <div class="sg-grid">
    <?php foreach ($list as $b): [$st, $c] = sig_state($b); $isPer = $b['start'] || $b['end']; ?>
    <article class="panel sg-card<?= $c === 'ok' ? ' on' : '' ?>">
      <div class="sg-thumb"><img src="/signatures/image/<?= (int)$b['id'] ?>" alt="" loading="lazy"></div>
      <div class="sg-meta"><b><?= e($b['title']) ?></b><span class="chip <?= $c ?>"><?= e($st) ?></span></div>
      <p class="muted small"><?= $isPer ? 'Du ' . $fd($b['start']) . ' au ' . $fd($b['end']) : ($b['is_default'] ? 'Bandeau par défaut, hors périodes' : 'Sans période') ?></p>
      <div class="sg-act">
        <button type="button" class="btn-link" data-sg-edit="<?= (int)$b['id'] ?>">Modifier</button>
        <?php if (!$b['is_default'] && !$isPer): ?><button type="button" class="btn-link" data-api="sig/default" data-id="<?= (int)$b['id'] ?>">Définir par défaut</button><?php endif; ?>
        <?php if (!$b['is_default']): ?><button type="button" class="btn-link" data-api="sig/delete" data-id="<?= (int)$b['id'] ?>" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?>
      </div>
      <form class="sg-edit" data-sg-form="<?= (int)$b['id'] ?>" hidden>
        <div class="field"><label for="se-t<?= (int)$b['id'] ?>">Nom</label><input class="input" id="se-t<?= (int)$b['id'] ?>" name="title" maxlength="120" value="<?= e($b['title']) ?>"></div>
        <label class="bd-pin"><input type="checkbox" name="is_default" value="1" data-sg-def<?= $b['is_default'] ? ' checked' : '' ?>> Bandeau par défaut (hors périodes programmées)</label>
        <div class="sg-dates" data-sg-per><div class="field"><label for="se-s<?= (int)$b['id'] ?>">Du</label><input class="input" id="se-s<?= (int)$b['id'] ?>" name="start" type="date" value="<?= e((string)$b['start']) ?>"></div>
          <div class="field"><label for="se-e<?= (int)$b['id'] ?>">Au</label><input class="input" id="se-e<?= (int)$b['id'] ?>" name="end" type="date" value="<?= e((string)$b['end']) ?>"></div></div>
        <div class="field"><label for="se-i<?= (int)$b['id'] ?>">Remplacer l'image (facultatif)</label><input class="input" id="se-i<?= (int)$b['id'] ?>" type="file" accept="image/png,image/jpeg,image/gif"></div>
        <p class="form-error" hidden></p>
        <div class="sg-mk-a"><button class="btn btn-primary">Enregistrer</button><button type="button" class="btn btn-ghost" data-sg-cancel>Annuler</button></div>
      </form>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
