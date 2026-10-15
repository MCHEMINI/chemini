<?php
$byName = [];
foreach ($staff as $s) $byName[$s['name']] = $s;
$syndic = array_values(array_filter($buildings, fn($b) => $b['kind'] === 'syndic'));
$autres = array_values(array_filter($buildings, fn($b) => $b['kind'] !== 'syndic'));
$noAssist = count(array_filter($syndic, fn($b) => !$b['assist']));
$noEmail = array_filter($staff, fn($s) => !$s['email']);
$gests = array_values(array_unique(array_filter(array_column($syndic, 'gest'))));
sort($gests);
$person = function (?string $name) use ($byName): string {
    if (!$name) return '<span class="muted">—</span>';
    $s = $byName[$name] ?? null;
    $mail = $s['email'] ?? null;
    return '<span class="pp"><span class="pp-n">' . e($name) . '</span>'
        . ($mail ? '<span class="pp-m mono">' . e($mail) . '</span>' : '<span class="pp-m warn-t">e-mail inconnu</span>') . '</span>';
};
?>
<header class="head imm-head">
  <div>
    <div class="eyebrow">Espace GID · Annuaire des immeubles</div>
    <h1>Immeubles</h1>
    <div class="date"><?= count($syndic) ?> immeubles en syndic · <?= count($autres) ?> autres fiches · source ImmOpen, relevé du 16/10/2026</div>
  </div>
</header>

<div class="imm-bar">
  <div class="imm-search">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <label for="immQ" class="sr">Rechercher</label>
    <input id="immQ" type="search" placeholder="Code, adresse, ville ou nom d'un collaborateur…" autocomplete="off">
    <span class="imm-count mono" id="immCount"></span>
  </div>
  <label for="immGest" class="sr">Gestionnaire</label>
  <select id="immGest" class="input imm-select">
    <option value="">Tous les gestionnaires</option>
    <?php foreach ($gests as $g): ?><option value="<?= e($g) ?>"><?= e($g) ?></option><?php endforeach; ?>
  </select>
  <nav class="imm-tabs" role="tablist">
    <button type="button" role="tab" class="on" data-tab="syndic">Syndic <span><?= count($syndic) ?></span></button>
    <button type="button" role="tab" data-tab="autres">Autres <span><?= count($autres) ?></span></button>
    <button type="button" role="tab" data-tab="equipe">Équipe <span><?= count($staff) ?></span></button>
    <button type="button" role="tab" data-tab="routage">Routage</button>
  </nav>
</div>

<section class="imm-panel" data-panel="syndic">
  <div class="tbl imm-tbl"><table class="imm-table" data-filter-table>
    <thead><tr><th>Code</th><th>Immeuble</th><th>Ville</th><th>Gestionnaire</th><th>Comptable</th><th>Assistant(e)</th><th>Exercice</th></tr></thead>
    <tbody>
    <?php foreach ($syndic as $b): ?>
      <tr data-gest="<?= e($b['gest']) ?>">
        <td class="mono code"><?= e($b['code']) ?></td>
        <td><strong><?= e($b['label']) ?></strong><?php if ($b['addr2']): ?><br><span class="muted small"><?= e($b['addr2']) ?></span><?php endif; ?></td>
        <td class="nowrap"><span class="mono"><?= e($b['cp']) ?></span> <?= e($b['ville']) ?></td>
        <td><?= $person($b['gest']) ?></td>
        <td><?= $person($b['compta']) ?></td>
        <td><?= $person($b['assist']) ?></td>
        <td><?= $b['exercice'] ? '<span class="chip warn">' . e($b['exercice']) . '</span>' : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="empty imm-none" hidden><strong>Aucun immeuble trouvé.</strong>Essayez avec un numéro de rue, un code postal ou le nom d'un collaborateur.</p>
</section>

<section class="imm-panel" data-panel="autres" hidden>
  <p class="muted small" style="margin:0 0 12px">Fiches visibles dans ImmOpen hors syndic (gérance, transaction, anciens dossiers). Responsables non relevés.</p>
  <div class="tbl imm-tbl"><table class="imm-table" data-filter-table>
    <thead><tr><th>Code</th><th>Immeuble</th><th>Ville</th></tr></thead>
    <tbody>
    <?php foreach ($autres as $b): ?>
      <tr><td class="mono code"><?= e($b['code']) ?></td><td><strong><?= e($b['label']) ?></strong><?php if ($b['addr2']): ?><br><span class="muted small"><?= e($b['addr2']) ?></span><?php endif; ?></td><td class="nowrap"><span class="mono"><?= e($b['cp']) ?></span> <?= e($b['ville']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="empty imm-none" hidden><strong>Aucune fiche trouvée.</strong></p>
</section>

<section class="imm-panel" data-panel="equipe" hidden>
  <div class="staff-grid">
    <?php foreach ($staff as $s): ?>
    <form class="panel staff" data-api="staff/update" data-filter-card>
      <input type="hidden" name="name" value="<?= e($s['name']) ?>">
      <div class="staff-top"><div><strong><?= e($s['name']) ?></strong><div class="muted small"><?= e($s['role']) ?></div></div><span class="chip neutral"><?= (int)($count[$s['name']] ?? 0) ?> imm.</span></div>
      <?php if ($canEdit): ?>
        <div class="field"><label for="se-<?= md5($s['name']) ?>">E-mail</label><input class="input" id="se-<?= md5($s['name']) ?>" name="email" type="email" value="<?= e($s['email']) ?>" placeholder="à compléter"></div>
        <div class="field"><label for="sp-<?= md5($s['name']) ?>">Ligne directe</label><input class="input mono" id="sp-<?= md5($s['name']) ?>" name="phone" value="<?= e($s['phone']) ?>" placeholder="à compléter"></div>
        <p class="form-error" hidden></p>
        <button class="btn btn-ghost">Enregistrer</button>
      <?php else: ?>
        <div class="mono small"><?= $s['email'] ? e($s['email']) : '<span class="warn-t">e-mail inconnu</span>' ?></div>
        <div class="mono small"><?= $s['phone'] ? e($s['phone']) : '<span class="muted">ligne non renseignée</span>' ?></div>
      <?php endif; ?>
    </form>
    <?php endforeach; ?>
  </div>
</section>

<section class="imm-panel" data-panel="routage" hidden>
  <div class="grid">
    <div class="col-6 panel stack">
      <div class="sec-h flat"><h2>contact@gidsas.fr</h2><span class="chip warn">À brancher</span></div>
      <ol class="route-steps">
        <li><strong>Détection de l'immeuble</strong> dans l'objet ou le corps du mail : code ImmOpen (5 chiffres), adresse ou nom de résidence, ou adresse de l'expéditeur déjà connue.</li>
        <li><strong>Envoi au gestionnaire</strong> de l'immeuble, avec l'assistant(e) en copie. Les sujets comptables (charges, appels de fonds, relevés) vont au comptable.</li>
        <li><strong>Sans immeuble reconnu</strong> : le mail reste dans une file « À répartir » visible par la Direction.</li>
      </ol>
      <p class="muted small">Nécessite l'accès Microsoft 365 à la boîte contact@ (lecture et transfert).</p>
    </div>
    <div class="col-6 panel stack">
      <div class="sec-h flat"><h2>Téléphonie</h2><span class="chip warn">À brancher</span></div>
      <ol class="route-steps">
        <li><strong>Accueil vocal</strong> : « Tapez le code de votre immeuble » (5 chiffres, figurant sur les appels de fonds).</li>
        <li><strong>Transfert</strong> vers la ligne de l'assistant(e) de l'immeuble, puis du gestionnaire en cas de non-réponse.</li>
        <li><strong>Code inconnu ou sans réponse</strong> : accueil, avec la fiche de l'immeuble affichée ici pour la personne qui décroche.</li>
      </ol>
      <p class="muted small">Les lignes directes se saisissent dans l'onglet Équipe. L'opérateur téléphonique reste à choisir.</p>
    </div>
    <div class="col-12">
      <div class="sec-h"><h2>À compléter avant branchement</h2></div>
      <ul class="list">
        <li><i class="sev <?= $noEmail ? 'warn' : 'ok' ?>"></i><div><div class="t"><?= count($noEmail) ?> collaborateur(s) sans e-mail</div><div class="d"><?= e(implode(', ', array_column($noEmail, 'name'))) ?: 'Tous les e-mails sont connus.' ?></div></div><div class="meta"></div></li>
        <li><i class="sev warn"></i><div><div class="t"><?= $noAssist ?> immeubles sans assistant(e)</div><div class="d">Le transfert téléphonique ira directement au gestionnaire.</div></div><div class="meta"></div></li>
        <li><i class="sev warn"></i><div><div class="t">Lignes directes</div><div class="d"><?= count(array_filter($staff, fn($s) => !$s['phone'])) ?> collaborateur(s) sans ligne renseignée.</div></div><div class="meta"></div></li>
        <li><i class="sev ok"></i><div><div class="t"><?= count($autres) ?> fiches hors syndic</div><div class="d">Responsables non relevés : elles seront routées vers l'accueil.</div></div><div class="meta"></div></li>
      </ul>
    </div>
  </div>
</section>
