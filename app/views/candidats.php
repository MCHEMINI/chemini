<?php
/* Candidats (Direction) : candidatures reçues par le formulaire Recrutement du site, par métier ; base de recrutement = candidats validés (étoile). */
$fmtSize = fn(int $b) => $b >= 1048576 ? number_format($b / 1048576, 1, ',', ' ') . ' Mo' : max(1, (int)round($b / 1024)) . ' Ko';
$byJob = [];
foreach ($list as $c) $byJob[$c['job']][] = $c;
$order = array_keys(CAND_JOBS);
uksort($byJob, fn($a, $b) => array_search($a, $order, true) <=> array_search($b, $order, true));
$qs = fn(array $x) => '?' . http_build_query(array_filter($x + ['q' => $f['q'], 'metier' => $f['job']], fn($v) => $v !== '' && $v !== null));
$self = $base ? '/candidats/base' : '/candidats';
$nShown = count($list);
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · Recrutement</div>
    <h1><?= $base ? 'Base de recrutement' : 'Candidats par métier' ?></h1>
    <div class="date"><?= $base
      ? $counts['starred'] . ' candidat' . ($counts['starred'] > 1 ? 's' : '') . ' validé' . ($counts['starred'] > 1 ? 's' : '') . ' · recherche dans les noms, messages, notes et le texte des CV et lettres'
      : $counts['total'] . ' candidature' . ($counts['total'] > 1 ? 's' : '') . ' reçue' . ($counts['total'] > 1 ? 's' : '') . ' par le formulaire Recrutement de gidsas.fr · ' . $counts['starred'] . ' validée' . ($counts['starred'] > 1 ? 's' : '') ?></div>
  </div>
</header>

<?php if ($counts['threat'] && !$base): ?>
<div class="cd-alert" role="status"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 3 19h18L12 3z"/><path d="M12 10v4M12 17h.01"/></svg>
  <span><b><?= $counts['threat'] ?> candidature<?= $counts['threat'] > 1 ? 's ont' : ' a' ?> un fichier refusé</b> par le contrôle anti-virus : il reste en quarantaine, chiffré, et ne peut pas être ouvert. Le reste de la candidature est consultable.</span></div>
<?php endif; ?>

<form class="cd-bar" method="get" action="<?= $self ?>" role="search">
  <label class="gd-search cd-q"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= $base ? 'Compétence, logiciel, ville, nom… (ex. : ImmOpen, comptabilité copropriété)' : 'Rechercher un candidat, un mot du CV…' ?>" aria-label="Rechercher"></label>
  <?php if ($f['job']): ?><input type="hidden" name="metier" value="<?= e($f['job']) ?>"><?php endif; ?>
  <button class="btn btn-primary" type="submit">Rechercher</button>
  <?php if ($f['q'] !== '' || $f['job'] !== ''): ?><a class="btn-link" href="<?= $self ?>">Tout afficher</a><?php endif; ?>
</form>

<nav class="cd-jobs" aria-label="Métiers">
  <a href="<?= $self . $qs(['metier' => null]) ?>" class="<?= $f['job'] === '' ? 'on' : '' ?>">Tous <b><?= $base ? $counts['starred'] : $counts['total'] ?></b></a>
  <?php foreach (CAND_JOBS as $k => $lab): $j = $counts['jobs'][$k] ?? null; $n = $j ? ($base ? $j['starred'] : $j['n']) : 0; if (!$n && $f['job'] !== $k) continue; ?>
  <a href="<?= $self . $qs(['metier' => $k]) ?>" class="<?= $f['job'] === $k ? 'on' : '' ?>"><?= e($lab) ?> <b><?= $n ?></b><?php if (!$base && $j && $j['new']): ?><span class="cd-new-dot" title="<?= $j['new'] ?> nouvelle<?= $j['new'] > 1 ? 's' : '' ?>"></span><?php endif; ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($base): ?>
<section class="panel cd-ask">
  <form data-api="candidats/ask" data-toast="Question envoyée aux Tâches IA">
    <label for="cdAsk"><b>Interroger la base avec l'IA</b><span class="muted small">Ex. : « Qui pourrait remplacer un gestionnaire de copropriété dès janvier, avec une expérience ImmOpen ? » La réponse arrive dans les Tâches IA.</span></label>
    <div class="cd-ask-row"><textarea class="input" id="cdAsk" name="question" rows="2" maxlength="1000" required placeholder="Votre question sur les candidats validés…"></textarea><button class="btn btn-primary" type="submit">Envoyer</button></div>
    <p class="form-error" hidden></p>
  </form>
</section>
<?php endif; ?>

<?php if (!$list): ?>
<div class="empty"><strong><?= $f['q'] !== '' ? 'Aucun candidat ne correspond à « ' . e($f['q']) . ' ».' : ($base ? 'La base de recrutement est vide.' : 'Aucune candidature pour l\'instant.') ?></strong>
  <?= $base ? 'Validez un candidat avec l\'étoile dans « Candidats par métier » : il rejoint la base de recrutement.' : 'Les candidatures envoyées depuis la page Recrutement de gidsas.fr apparaîtront ici, classées par métier.' ?></div>
<?php endif; ?>

<?php foreach ($byJob as $jk => $rows): ?>
<section class="cd-group" aria-labelledby="cdj-<?= e($jk) ?>">
  <div class="sec-h"><h2 id="cdj-<?= e($jk) ?>"><?= e(CAND_JOBS[$jk] ?? $jk) ?></h2><span class="muted small"><?= count($rows) ?> candidat<?= count($rows) > 1 ? 's' : '' ?></span></div>
  <div class="cd-list">
  <?php foreach ($rows as $c): $id = (int)$c['id']; $new = !$c['seen_at']; ?>
    <article class="panel cd-card<?= $c['starred'] ? ' is-star' : '' ?>" id="cand-<?= $id ?>">
      <button type="button" class="cd-star" data-cand-star data-id="<?= $id ?>" aria-pressed="<?= $c['starred'] ? 'true' : 'false' ?>" title="<?= $c['starred'] ? 'Validé : dans la base de recrutement (cliquer pour retirer)' : 'Valider ce candidat (ajout à la base de recrutement)' ?>" aria-label="<?= $c['starred'] ? 'Retirer de la base de recrutement' : 'Valider le candidat' ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6L12 16.7 6.6 19.6l1.1-6-4.5-4.2 6.1-.8z"/></svg>
      </button>
      <div class="cd-id">
        <div class="cd-name"><b><?= e($c['first_name'] . ' ' . mb_strtoupper($c['last_name'])) ?></b>
          <?php if ($new): ?><span class="chip todo">Nouveau</span><?php endif; ?>
          <?php if (($c['site_flag'] ?? '') === 'spam'): ?><span class="chip warn" title="L'anti-robot du site a jugé cet envoi douteux : vérifiez avant de répondre">Signalé indésirable</span><?php endif; ?>
          <?php if (($c['site_flag'] ?? '') === 'mail_failed'): ?><span class="chip neutral" title="Le site n'a pas pu envoyer son e-mail habituel : la candidature n'est qu'ici">E-mail du site non envoyé</span><?php endif; ?>
          <?php if ($c['times'] > 1): ?><span class="chip neutral" title="Même adresse e-mail"><?= $c['times'] ?> candidatures</span><?php endif; ?>
          <?php if ($c['job'] === 'autre' || $c['job_label'] !== (CAND_JOBS[$c['job']] ?? '')): ?><span class="chip neutral"><?= e($c['job_label']) ?></span><?php endif; ?>
        </div>
        <div class="cd-meta"><span title="Reçue le <?= date('d/m/Y à H:i', (int)$c['created_at']) ?>"><?= date('d/m/Y', (int)$c['created_at']) ?></span>
          <a href="mailto:<?= e($c['email']) ?>" data-outlook><?= e($c['email']) ?></a>
          <?php if ($c['phone']): ?><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $c['phone'])) ?>"><?= e($c['phone']) ?></a><?php endif; ?>
          <?php if ($c['starred'] && $c['starred_name']): ?><span>validé par <?= e(explode(' ', $c['starred_name'])[0]) ?></span><?php endif; ?></div>
      </div>
      <ul class="cd-files">
        <?php if (!$c['files']): ?><li class="none">Aucun fichier</li><?php endif; ?>
        <?php foreach ($c['files'] as $fl): $ok = (int)$fl['scan_ok']; $ext = strtoupper(pathinfo($fl['name'], PATHINFO_EXTENSION)) ?: 'DOC'; $tip = $fl['name'] . ' · ' . $fmtSize((int)$fl['size']) . ' — ' . $fl['scan_detail']; ?>
        <li class="<?= $ok ? 'ok' : 'bad' ?>">
          <?php if ($ok): ?><a href="/candidats/fichier/<?= (int)$fl['id'] ?>" target="_blank" rel="noopener" title="<?= e($tip) ?>"><?php else: ?><span title="<?= e($tip) ?>"><?php endif; ?>
            <span class="cd-ext"><?= e($ext) ?></span>
            <span class="cd-fn"><b><?= e(CAND_FILE_KINDS[$fl['kind']] ?? 'Document') ?></b><small><?php if ($ok): ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3z"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg><?= $fmtSize((int)$fl['size']) ?> · sans menace<?php else: ?><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6l-8-3z"/><path d="m9.5 9.5 5 5M14.5 9.5l-5 5"/></svg>Bloqué : <?= e(explode(' · ', (string)$fl['scan_detail'])[0]) ?><?php endif; ?></small></span>
          <?php if ($ok): ?></a><?php else: ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="cd-act">
        <button type="button" class="btn-link" data-cand-note-open aria-expanded="<?= $c['note'] ? 'true' : 'false' ?>" aria-controls="cdn-<?= $id ?>"><?= $c['note'] ? 'Modifier la note' : 'Ajouter une note' ?></button>
        <button type="button" class="btn-link cd-del" data-api="candidats/delete" data-id="<?= $id ?>" data-confirm="Confirmer la suppression">Supprimer</button>
      </div>
      <div class="cd-row2">
        <?php if ($c['snip']): ?><p class="cd-snip"><?= $c['snip'] ?></p><?php endif; ?>
        <?php if ($c['message'] !== ''): ?><p class="cd-msg" title="Message du candidat"><span class="cd-lab">Message</span><?= nl2br(e($c['message'])) ?></p><?php endif; ?>
        <form class="cd-note" id="cdn-<?= $id ?>" data-cand-note data-id="<?= $id ?>"<?= $c['note'] ? '' : ' hidden' ?>><label class="cd-lab" for="cdt-<?= $id ?>">Note</label><textarea class="input" id="cdt-<?= $id ?>" name="note" rows="1" maxlength="2000" placeholder="Impression, entretien prévu, disponibilité…"><?= e((string)$c['note']) ?></textarea><button class="btn btn-ghost" type="submit">Enregistrer</button></form>
        <p class="cd-purge"><?= (int)$c['needs_text'] && array_filter($c['files'], fn($x) => (int)$x['scan_ok']) ? 'Texte du CV en cours de lecture pour la recherche · ' : '' ?>Suppression automatique le <?= date('d/m/Y', $c['purge_at']) ?> (RGPD)</p>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>
