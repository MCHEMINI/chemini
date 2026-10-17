<?php
$img = BASE_URL . '/signature/banniere'; $lnk = BASE_URL . '/signature/lien';
$def = null; foreach ($list as $b) if ($b['is_default']) { $def = $b; break; }
$hits = json_decode((string)q("SELECT v FROM kv WHERE k='sig_hits'")->fetchColumn(), true) ?: [];
$linked = !empty($hits['ext']) && $hits['ext'] > time() - 30 * 86400;
$cal = sig_calendar(12); $nextMonth = [];
foreach ($cal as $m => $bs) foreach ($bs as $x) if (!isset($nextMonth[$x['id']])) $nextMonth[$x['id']] = ($m === date('Y-m') ? 'ce mois-ci' : 'en ' . MOIS_LONG[(int)substr($m, 5) - 1] . ' ' . substr($m, 0, 4));
$fd = fn(?string $d) => $d ? date('d/m/Y', strtotime($d)) : '…';
// Ordre : en cours, programmés (par date), défaut, inactifs, terminés
$rank = fn($b) => ['Activé · en cours d\'envoi' => 0, 'Activé' => 0, 'En cours d\'envoi' => 0, 'Programmé' => 1, 'Par défaut' => 2, 'Rotation mensuelle' => 2, 'Brouillon' => 3, 'Terminé' => 4][sig_state($b)[0]] ?? 5;
usort($list, fn($a, $b) => [$rank($a), $a['start'] ?? '', -$a['created_at']] <=> [$rank($b), $b['start'] ?? '', -$b['created_at']]);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · e-mails</div>
    <h1>Signatures</h1>
    <div class="date">Seul le visuel du bas des signatures change. Un bandeau par défaut est toujours prévu ; les visuels programmés partent automatiquement aux dates choisies, les brouillons quand vous les activez.</div>
  </div>
</header>

<section class="sg-now">
  <div class="panel sg-today">
    <div class="sg-today-h"><div class="eyebrow">Envoyé aujourd'hui</div>
      <span class="chip <?= $linked ? 'ok' : 'warn' ?>" title="<?= $linked ? 'Dernière lecture par Exclaimer ou une messagerie le ' . e(date('d/m/Y à H:i', (int)$hits['ext'])) : 'Exclaimer n\'a pas encore lu le bandeau' ?>"><?= $linked ? 'Exclaimer relié · lu le ' . e(date('d/m à H:i', (int)$hits['ext'])) : 'Exclaimer pas encore relié' ?></span></div>
    <div class="sg-ban"><?php if ($cur): ?><img src="/signature/banniere?v=<?= (int)$cur['id'] ?>-<?= date('Ymd') ?>" alt="<?= e($cur['title']) ?>" width="600" height="200"><?php else: ?><div class="empty"><strong>Aucun bandeau.</strong>Ajoutez un premier visuel : il deviendra le bandeau par défaut.</div><?php endif; ?></div>
    <?php if ($cur): ?><p class="small"><b><?= e($cur['title']) ?></b> · <?= $cur['is_default'] && !$cur['start'] ? 'bandeau par défaut' : ($cur['active_at'] ? 'brouillon activé, jusqu\'à ce que vous le désactiviez' : 'jusqu\'au ' . e($fd($cur['end'])) . ($def && (int)$def['id'] !== (int)$cur['id'] ? ', puis retour à « ' . e($def['title']) . ' »' : '')) ?></p><?php endif; ?>
    <details class="sg-tech"><summary>Réglage Exclaimer (une seule fois)</summary>
      <p class="small muted">Dans la signature Exclaimer, bandeau › Source de l'image › Lier à une image, puis Lien hypertexte :</p>
      <input class="input mono" readonly value="<?= e($img) ?>" data-select aria-label="Adresse de l'image">
      <input class="input mono" readonly value="<?= e($lnk) ?>" data-select aria-label="Adresse du lien">
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
    <div class="sg-modes" role="radiogroup" aria-label="Utilisation du visuel">
      <label class="sg-mode"><input type="radio" name="mode" value="default" checked> <span><b>Bandeau par défaut</b><small>filet de sécurité : affiché quand aucun autre visuel ne s'applique</small></span></label>
      <label class="sg-mode"><input type="radio" name="mode" value="period"> <span><b>Période programmée</b><small>fêtes, fermeture… diffusé du… au…</small></span></label>
      <label class="sg-mode"><input type="radio" name="mode" value="rotation"> <span><b>Visuel du mois</b><small>rotation : un visuel différent chaque mois, hors périodes programmées</small></span></label>
      <label class="sg-mode"><input type="radio" name="mode" value="draft"> <span><b>Brouillon</b><small>sans période, à activer ou désactiver quand vous voulez</small></span></label>
    </div>
    <fieldset class="sg-period" id="sgPer">
      <legend>Période de diffusion</legend>
      <div class="sg-dates"><div class="field"><label for="sg-s">Du</label><input class="input" id="sg-s" name="start" type="date"></div>
      <div class="field"><label for="sg-e">Au</label><input class="input" id="sg-e" name="end" type="date"></div></div>
      <p class="muted small">À la fin de la période, le bandeau par défaut<?= $def ? ' (« ' . e($def['title']) . ' »)' : '' ?> reprend automatiquement.</p>
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
      <li><b>Complétez le message</b> et la ligne secondaire : le prompt se met à jour.</li>
      <li><b>Copiez le prompt</b> et collez-le dans Gemini (ajoutez-y l'ambiance souhaitée si besoin).</li>
      <li><b>Téléchargez l'image</b> obtenue (format 3:1, 1200 × 400 px).</li>
      <li><b>Ajoutez-la</b> dans « Ajouter un visuel » avec sa période.</li>
    </ol>
    <div class="field sg-todo"><label for="sgTheme">Message du bandeau <span class="sg-req">À compléter</span></label><input class="input" id="sgTheme" placeholder="Ex. Joyeuses fêtes de fin d'année"></div>
    <div class="field sg-todo"><label for="sgSub">Ligne secondaire <span class="sg-req">À compléter</span> <span class="muted">(facultatif)</span></label><input class="input" id="sgSub" placeholder="Ex. Le cabinet sera fermé du 24 au 26 décembre"></div>
    <textarea class="input bd-ta sg-prompt" id="sgPrompt" rows="12" readonly aria-label="Prompt pour Gemini"></textarea>
    <div class="sg-mk-a"><button type="button" class="btn btn-primary" id="sgCopy">Copier le prompt</button><a class="btn btn-ghost" href="https://gemini.google.com/app" target="_blank" rel="noopener">Ouvrir Gemini</a></div>
    <p class="muted small">Format envoyé dans les mails : 600 × 200 px. Si l'image n'est pas exactement en 3:1, Dashboard la recadre au centre : gardez le texte loin des bords.</p>
  </section>
</section>

<section class="panel sg-cal">
  <div class="sec-h flat"><h2>Les 12 prochains mois</h2><span class="muted small">Priorité : brouillon activé › période programmée › visuel du mois › bandeau par défaut</span></div>
  <ol class="sg-months">
    <?php foreach ($cal as $m => $bs): ?>
    <li<?= $m === date('Y-m') ? ' class="now"' : '' ?>><b><?= e(ucfirst(MOIS_LONG[(int)substr($m, 5) - 1])) ?> <small><?= substr($m, 0, 4) ?></small></b>
      <?php foreach ($bs as $x): $md = sig_mode($x); ?><span class="sg-mi <?= $md ?>"><img src="/signatures/image/<?= (int)$x['id'] ?>" alt="" loading="lazy"><em><?= e($x['title']) ?><?= $md === 'period' ? ' · ' . e(date('d/m', strtotime($x['start']))) . '–' . e(date('d/m', strtotime($x['end']))) : '' ?></em></span><?php endforeach; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<?php if ($list): ?>
<section>
  <div class="sec-h"><h2>Bandeaux</h2><span class="muted small">Statuts recalculés chaque jour selon les dates</span></div>
  <div class="sg-grid">
    <?php foreach ($list as $b): [$st, $c] = sig_state($b); $isPer = $b['start'] || $b['end']; ?>
    <article class="panel sg-card<?= $c === 'ok' ? ' on' : '' ?>">
      <div class="sg-thumb"><img src="/signatures/image/<?= (int)$b['id'] ?>" alt="" loading="lazy"></div>
      <div class="sg-meta"><b><?= e($b['title']) ?></b><span class="chip <?= $c ?>"><?= e($st) ?></span></div>
      <p class="muted small"><?= $isPer ? 'Du ' . $fd($b['start']) . ' au ' . $fd($b['end']) : ($b['is_default'] ? 'Bandeau par défaut, hors périodes' : (sig_mode($b) === 'rotation' ? 'Visuel du mois' . (($nx = $nextMonth[$b['id']] ?? null) ? ' · ' . $nx : ' · pas de mois libre sur 12 mois') : ($b['active_at'] ? 'Brouillon activé le ' . date('d/m/Y', (int)$b['active_at']) . ' : remplace les autres jusqu\'à désactivation' : 'Brouillon, sans période'))) ?></p>
      <div class="sg-act">
        <button type="button" class="btn-link" data-sg-edit="<?= (int)$b['id'] ?>">Modifier</button>
        <?php if (sig_mode($b) === 'draft'): ?><button type="button" class="btn-link sg-tog" data-api="sig/<?= $b['active_at'] ? 'deactivate' : 'activate' ?>" data-id="<?= (int)$b['id'] ?>"><?= $b['active_at'] ? 'Désactiver' : 'Activer' ?></button>
        <button type="button" class="btn-link" data-api="sig/default" data-id="<?= (int)$b['id'] ?>">Définir par défaut</button><?php endif; ?>
        <?php if (!$b['is_default']): ?><button type="button" class="btn-link" data-api="sig/delete" data-id="<?= (int)$b['id'] ?>" data-confirm="Supprimer ?">Supprimer</button><?php endif; ?>
      </div>
      <form class="sg-edit" data-sg-form="<?= (int)$b['id'] ?>" hidden>
        <div class="field"><label for="se-t<?= (int)$b['id'] ?>">Nom</label><input class="input" id="se-t<?= (int)$b['id'] ?>" name="title" maxlength="120" value="<?= e($b['title']) ?>"></div>
        <?php $md = sig_mode($b); ?><div class="sg-modes sm" role="radiogroup" aria-label="Utilisation"><?php foreach (['default' => 'Par défaut', 'period' => 'Période', 'rotation' => 'Visuel du mois', 'draft' => 'Brouillon'] as $mv => $ml): ?><label class="sg-mode"><input type="radio" name="mode" value="<?= $mv ?>" data-sg-mode<?= $md === $mv ? ' checked' : '' ?>> <span><b><?= $ml ?></b></span></label><?php endforeach; ?></div>
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
