<?php
$ini = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: ''));
$key = fn(string $n) => team_slug($n);
$roleDir = ['Mehdi Chemini' => 'Président', 'Céline Chemini' => 'Directrice générale'];
$dor = qa($A, 'dorothee');
$shown = [];
$person = function (string $name, string $role, string $cls = '') use ($ini, $key, $people, $stat, &$shown, $me) {
    $k = $key($name); $has = isset($people[$k]); $shown[$k] = true;
    $tag = $has ? 'a' : 'span'; $s = $stat[$k] ?? null;
    $s = $s ?? ['open' => 0, 'late' => 0];
    $badge = $has ? '<span class="og-t' . ($s['late'] ? ' late' : ($s['open'] ? '' : ' zero')) . '" title="' . ($s['open'] ? $s['open'] . ' tâche' . ($s['open'] > 1 ? 's' : '') . ' en cours' . ($s['late'] ? ', dont ' . $s['late'] . ' en retard' : '') : 'Aucune tâche en cours') . '">' . $s['open'] . '</span>' : '';
    $ext = $has ? ($people[$k]['ext'] ?? '') : '';
    if ($ext) $role .= ' · ☎ ' . $ext;
    return '<' . $tag . ' class="og-p ' . $cls . ($k === $me ? ' me' : '') . '"' . ($has ? ' href="/equipe/' . e($k) . '"' : '') . '><span class="ag-av">' . e($ini($name)) . '</span><span class="og-id"><b>' . e($name) . '</b><small>' . e($role) . '</small></span>' . $badge . '</' . $tag . '>';
};
$comptas = [];
foreach ($f['teams'] as $t) foreach ($t['compta'] as $c => $n) $comptas[$c][$t['gest']] = $n;
ksort($comptas);
?>
<header class="head">
  <div>
    <div class="eyebrow">Équipe · organigramme et tâches</div>
    <h1>Collaborateurs</h1>
    <div class="date"><?= (int)$f['staff'] ?> personnes · qui travaille avec qui (équipes relevées dans ImmOpen) · la pastille indique les tâches en cours ; cliquez sur une personne pour lui affecter une tâche</div>
  </div>
</header>

<p class="tm-note"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8h.01M11 12h1v5h1"/></svg><?= $isDir ? 'Pastilles : toutes les tâches en cours de chaque personne (hors tâches perso). Chaque collaborateur ne voit que ses propres tâches et celles qu\'il a affectées : les tâches affectées par la Direction ne sont pas visibles des autres.' : 'Pastilles : vos tâches et celles que vous avez affectées à chaque personne.' ?></p>
<section class="og">
  <div class="og-level">
    <div class="og-lbl">Direction</div>
    <div class="og-row">
      <?php foreach ($f['by']['Direction'] ?? [] as $n): ?><?= $person($n, $roleDir[$n] ?? ($n === 'Dorothée Gleeson' && $dor ? $dor[0] : 'Direction'), 'dir') ?><?php endforeach; ?>
    </div>
  </div>
  <div class="og-line" aria-hidden="true"></div>
  <div class="og-level">
    <div class="og-lbl">Pôles de gestion <span class="muted small">gestionnaire · assistante · comptables</span></div>
    <div class="og-grid">
      <?php foreach ($f['teams'] as $t): ?>
      <article class="og-team">
        <header><?= $person($t['gest'], 'Gestionnaire', 'lead') ?><span class="og-n"><b><?= (int)$t['n'] ?></b> imm.</span></header>
        <ul>
          <?php foreach ($t['assist'] as $a => $n): ?><li><?= $person($a, 'Assistante') ?></li><?php endforeach; ?>
          <?php if (!$t['assist']): ?><li class="muted small og-none">Pas d'assistante rattachée dans ImmOpen</li><?php endif; ?>
          <?php foreach ($t['compta'] as $c => $n): ?><li><?= $person($c, 'Comptable · ' . $n . ' imm.') ?></li><?php endforeach; ?>
        </ul>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="og-level">
    <div class="og-lbl">Comptabilité <span class="muted small">avec quels gestionnaires</span></div>
    <div class="og-grid sm">
      <?php foreach ($comptas as $c => $gs): arsort($gs); ?>
      <article class="og-team">
        <header><?= $person($c, 'Comptable', 'lead') ?><span class="og-n"><b><?= array_sum($gs) ?></b> imm.</span></header>
        <p class="og-with"><?php foreach ($gs as $g => $n): ?><span class="chip neutral"><?= e($g) ?> · <?= (int)$n ?></span> <?php endforeach; ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php $others = array_filter($people, fn($p) => !isset($shown[$p['key']])); if ($others): ?>
<section class="og-level">
  <div class="og-lbl">Autres collaborateurs</div>
  <div class="og-grid sm"><?php foreach ($others as $p): ?><article class="og-team"><?= $person($p['name'], $p['role'], 'lead') ?></article><?php endforeach; ?></div>
</section>
<?php endif; ?>
<section class="tm-today">
  <div class="sec-h"><h2>Tâches du jour</h2><span class="muted small">Échéance aujourd'hui ou dépassée · <?= count($todayList) ?></span></div>
  <?php if (!$todayList): ?><div class="empty"><strong>Rien à traiter aujourd'hui.</strong>Les tâches dont l'échéance est aujourd'hui ou dépassée apparaissent ici.</div>
  <?php else: ?>
  <div class="panel"><ul class="tt-list" data-more-list><?php $showWho = true; foreach ($todayList as $i => $t) { $hidden = $i >= 10; include __DIR__ . '/_team_task.php'; } ?></ul>
  <?php if (count($todayList) > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= count($todayList) - 10 ?>) ↓</button><?php endif; ?></div>
  <?php endif; ?>
</section>
