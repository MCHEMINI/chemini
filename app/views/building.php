<?php
$fmt = fn($v) => number_format((float)$v, 0, ',', ' ');
$addr = trim($b['label'] . ($b['addr2'] ? ', ' . $b['addr2'] : '') . ', ' . $b['cp'] . ' ' . $b['ville']);
$ref = $b['code'] . ' - ' . $b['label'];
$tel = fn(?string $p) => $p ? '<a class="mono" href="tel:' . e(preg_replace('/[^0-9+]/', '', $p)) . '">' . e($p) . '</a>' : '';
$person = function (?string $name, string $role) use ($staff, $ref, $tel): string {
    if (!$name) return '<div class="bd-person muted"><span class="bd-role">' . e($role) . '</span>—</div>';
    $s = $staff[$name] ?? [];
    $out = '<div class="bd-person"><span class="bd-role">' . e($role) . '</span><strong>' . e($name) . '</strong>';
    if (!empty($s['email'])) $out .= '<a href="mailto:' . e($s['email']) . '?subject=' . rawurlencode($ref) . '">' . e($s['email']) . '</a>';
    if (!empty($s['phone'])) $out .= $tel($s['phone']);
    return $out . '</div>';
};
$h = $fin['b'] ?? null;
$evIcon = ['mail' => 'Mail', 'note' => 'Note', 'ag' => 'AG', 'immopen' => 'ImmOpen', 'finance' => 'Finances'];
?>
<header class="head bd-head">
  <div>
    <div class="eyebrow"><a href="/immeubles">Immeubles</a> · <span class="mono"><?= e($b['code']) ?></span><?= $b['kind'] !== 'syndic' ? ' · hors syndic' : '' ?></div>
    <h1><?= e($b['label']) ?></h1>
    <div class="date"><?= e(trim(($b['addr2'] ? $b['addr2'] . ' · ' : '') . $b['cp'] . ' ' . $b['ville'])) ?>
      · <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($addr) ?>" target="_blank" rel="noopener">Plan</a></div>
  </div>
  <?php if ($fin): ?>
  <div class="bd-badge <?= e($fin['color']) ?>"><i></i><div><strong><?= e($fin['label']) ?></strong><span><?= $fin['color'] === 'lost' ? 'Mandat non renouvelé' : 'Marge estimée ' . round($fin['margin'] * 100) . ' %' ?></span></div></div>
  <?php endif; ?>
</header>

<div class="bd-kpis">
  <div><span>Lots</span><b><?= $h ? $fmt($fin['lots']) : '—' ?></b><?php if ($h && $fin['perdus']): ?><em><?= $fmt($fin['perdus']) ?> perdus</em><?php endif; ?></div>
  <div><span>Contrat</span><b><?= !empty($fin['contr']['contrat']) ? 'N° ' . e((string)$fin['contr']['contrat']) : '—' ?></b><?php if (!empty($fin['contr']['ht'])): ?><em><?= $fmt($fin['contr']['ht']) ?> € HT / an</em><?php endif; ?></div>
  <div><span>Dernière AG</span><b><?= !empty($h['dateAG']) ? e(date('d/m/Y', strtotime($h['dateAG']))) : 'À tenir' ?></b><?php if (!empty($h['datePV'])): ?><em>PV remis le <?= e(date('d/m/Y', strtotime($h['datePV']))) ?></em><?php endif; ?></div>
  <div><span>Exercice</span><b><?= e($b['exercice'] ?: 'En cours') ?></b><?php if (!empty($h['plage'])): ?><em>Plage AG <span class="nowrap"><?= e($h['plage']) ?></span></em><?php endif; ?></div>
</div>

<div class="grid">
  <section class="col-7">
    <div class="sec-h"><h2>Conseil syndical et accès</h2><?php if (!empty($info['updated_at'])): ?><span class="muted small">Mis à jour <?= e(mb_strtolower(ago((int)$info['updated_at']))) ?><?= !empty($info['by_name']) ? ' par ' . e($info['by_name']) : '' ?></span><?php endif; ?></div>
    <div class="panel">
      <?php if (!empty($info['president']) || !empty($info['president_phone']) || !empty($info['president_email'])): ?>
      <div class="bd-pres">
        <span class="bd-role">Président du conseil syndical</span>
        <strong><?= e($info['president'] ?? '') ?></strong>
        <div class="bd-contact"><?= $tel($info['president_phone'] ?? null) ?><?php if (!empty($info['president_email'])): ?><a href="mailto:<?= e($info['president_email']) ?>?subject=<?= rawurlencode($ref) ?>"><?= e($info['president_email']) ?></a><?php endif; ?></div>
      </div>
      <?php else: ?>
      <p class="muted">Président du conseil syndical non renseigné. Complétez la fiche ci-dessous : elle est partagée avec toute l'équipe.</p>
      <?php endif; ?>
      <?php if (!empty($info['cs_members'])): ?><div class="bd-block"><span class="bd-role">Membres du conseil</span><p><?= nl2br(e($info['cs_members'])) ?></p></div><?php endif; ?>
      <?php if (!empty($info['gardien'])): ?><div class="bd-block"><span class="bd-role">Gardien · prestataires</span><p><?= nl2br(e($info['gardien'])) ?></p></div><?php endif; ?>
      <?php if (!empty($info['acces'])): ?><div class="bd-block"><span class="bd-role">Accès</span><p><?= nl2br(e($info['acces'])) ?></p></div><?php endif; ?>
      <?php if (!empty($info['notes'])): ?><div class="bd-block"><span class="bd-role">Points d'attention</span><p><?= nl2br(e($info['notes'])) ?></p></div><?php endif; ?>
      <details class="bd-edit">
        <summary>Modifier les informations</summary>
        <form class="stack" data-api="building/save">
          <input type="hidden" name="code" value="<?= e($b['code']) ?>">
          <div class="two">
            <div class="field"><label for="bi-p">Président du conseil syndical</label><input class="input" id="bi-p" name="president" maxlength="120" value="<?= e($info['president'] ?? '') ?>"></div>
            <div class="field"><label for="bi-pt">Téléphone</label><input class="input mono" id="bi-pt" name="president_phone" maxlength="40" value="<?= e($info['president_phone'] ?? '') ?>"></div>
          </div>
          <div class="field"><label for="bi-pe">E-mail du président</label><input class="input" id="bi-pe" name="president_email" type="email" maxlength="190" value="<?= e($info['president_email'] ?? '') ?>"></div>
          <div class="field"><label for="bi-cs">Membres du conseil (nom, lot, contact)</label><textarea class="input" id="bi-cs" name="cs_members" rows="3" maxlength="2000"><?= e($info['cs_members'] ?? '') ?></textarea></div>
          <div class="field"><label for="bi-g">Gardien, entreprise de ménage, ascensoriste…</label><textarea class="input" id="bi-g" name="gardien" rows="2" maxlength="2000"><?= e($info['gardien'] ?? '') ?></textarea></div>
          <div class="field"><label for="bi-a">Accès (clés, badges, caves, locaux techniques)</label><textarea class="input" id="bi-a" name="acces" rows="2" maxlength="2000"><?= e($info['acces'] ?? '') ?></textarea></div>
          <div class="field"><label for="bi-n">Points d'attention (litiges, travaux, copropriétaires sensibles)</label><textarea class="input" id="bi-n" name="notes" rows="3" maxlength="4000"><?= e($info['notes'] ?? '') ?></textarea></div>
          <p class="form-error" hidden></p>
          <button class="btn btn-primary">Enregistrer</button>
        </form>
      </details>
    </div>

    <div class="sec-h" style="margin-top:32px"><h2>Historique</h2><span class="muted small">Mails, AG et notes de l'équipe</span></div>
    <form class="add-row panel" data-api="building/note">
      <input type="hidden" name="code" value="<?= e($b['code']) ?>">
      <label for="bn" class="sr">Ajouter une note</label>
      <input class="input" id="bn" name="title" maxlength="300" placeholder="Ajouter une note (appel du président, décision, incident…)" required>
      <button class="btn btn-ghost">Ajouter</button>
    </form>
    <p class="form-error" hidden></p>
    <?php
    $timeline = $events;
    if (!empty($h['dateAG'])) $timeline[] = ['at' => strtotime($h['dateAG']), 'kind' => 'ag', 'title' => 'Assemblée générale tenue', 'detail' => !empty($h['h2627e']) ? 'Honoraires 2026-2027 votés : ' . $fmt($h['h2627e']) . ' € TTC (proposé : ' . $fmt($h['h2627v']) . ' €)' : null, 'link' => null, 'by_name' => null];
    usort($timeline, fn($a, $c) => $c['at'] <=> $a['at']);
    ?>
    <?php if (!$timeline): ?>
      <div class="empty"><strong>Pas encore d'historique.</strong>Les mails liés à cet immeuble (objet « <?= e($b['code'] !== '' ? ltrim($b['code'], '0') : '') ?> - … ») sont ajoutés automatiquement chaque mois.</div>
    <?php else: ?>
    <ul class="bd-tl">
      <?php foreach ($timeline as $ev): ?>
      <li class="k-<?= e($ev['kind']) ?>">
        <span class="bd-tl-d mono"><?= e(date('d/m/Y', (int)$ev['at'])) ?></span>
        <div>
          <span class="chip neutral"><?= e($evIcon[$ev['kind']] ?? $ev['kind']) ?></span>
          <?php if (!empty($ev['link'])): ?><a href="<?= e($ev['link']) ?>" target="_blank" rel="noopener"><?= e($ev['title']) ?></a><?php else: ?><strong><?= e($ev['title']) ?></strong><?php endif; ?>
          <?php if (!empty($ev['detail'])): ?><p><?= e($ev['detail']) ?></p><?php endif; ?>
          <?php if (!empty($ev['by_name'])): ?><span class="muted small"><?= e($ev['by_name']) ?></span><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <section class="col-5">
    <div class="sec-h"><h2>Équipe GID</h2></div>
    <div class="panel bd-team">
      <?= $person($b['gest'], 'Gestionnaire') ?>
      <?= $person($b['assist'], 'Assistant(e)') ?>
      <?= $person($b['compta'], 'Comptable') ?>
    </div>

    <?php if ($isDir): ?>
    <div class="sec-h" style="margin-top:32px"><h2>Rapport financier</h2><span class="muted small">Direction uniquement</span></div>
    <?php if (!$fin): ?>
      <div class="empty"><strong>Pas de données financières.</strong>Cet immeuble ne figure pas dans le tableau des honoraires.</div>
    <?php else: ?>
    <div class="panel bd-fin">
      <div class="bd-badge <?= e($fin['color']) ?> big"><i></i><div><strong><?= e($fin['label']) ?></strong><span><?= $fin['color'] === 'lost' ? 'Mandat non renouvelé' : 'Marge estimée ' . round($fin['margin'] * 100) . ' %' ?></span></div></div>
      <h3>Pourquoi cette couleur</h3>
      <ul class="bd-why"><?php foreach ($fin['reasons'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul>
      <?php if ($fin['todo']): ?><h3>Que faire</h3><ul class="bd-why todo"><?php foreach ($fin['todo'] as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <h3>Honoraires (TTC)</h3>
      <table class="bd-tbl">
        <?php foreach ($fin['years'] as $k => $v): if (!$v) continue; ?><tr><td><?= e($k) ?></td><td class="mono r"><?= $fmt($v) ?> €</td></tr><?php endforeach; ?>
        <?php if ($fin['color'] !== 'lost'): ?>
        <tr><td>Prix au lot</td><td class="mono r"><?= $fmt($fin['perLot']) ?> € <span class="muted">(médiane <?= $fmt($fin['median']) ?> €)</span></td></tr>
        <tr><td>Coût de gestion estimé</td><td class="mono r"><?= $fmt($fin['cost']) ?> € HT</td></tr>
        <tr><td>Prestations incluses</td><td class="r"><?= (int)($h['cs1'] ?? 0) ?> CS 1 h · <?= (int)($h['cs2'] ?? 0) ?> CS 2 h · <?= (int)($h['visite'] ?? 0) ?> visite<?= (int)($h['visite'] ?? 0) > 1 ? 's' : '' ?></td></tr>
        <?php if (!empty($h['coutAG'])): ?><tr><td>AG supplémentaire</td><td class="mono r"><?= $fmt($h['coutAG']) ?> € TTC</td></tr><?php endif; ?>
        <?php endif; ?>
      </table>
      <p class="muted small bd-hyp">Couleur : vert si la marge estimée atteint 20 %, orange entre 0 et 20 %, rouge si elle est négative. Coût de gestion estimé = <?= COST_FIXED ?> € HT par immeuble + <?= COST_LOT ?> € par lot + <?= COST_HOUR ?> € par heure de conseil syndical ou de visite incluse. Hypothèses à ajuster avec vos coûts réels.</p>
      <a class="btn btn-ghost" href="/honoraires#imm">Ouvrir le tableau des honoraires</a>
    </div>
    <?php endif; endif; ?>
    <p class="muted small" style="margin-top:24px">Source annuaire : ImmOpen, relevé du <?= e($immopenAt ? date('d/m/Y', strtotime($immopenAt)) : '—') ?> · mise à jour le 1er de chaque mois.</p>
  </section>
</div>
