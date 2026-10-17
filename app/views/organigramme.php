<?php
$ini = fn(string $n) => mb_strtoupper(mb_substr($n, 0, 1) . (mb_substr(strrchr(' ' . $n, ' '), 1, 1) ?: ''));
$key = fn(string $n) => team_slug($n);
$roleDir = ['Mehdi Chemini' => 'Président', 'Céline Chemini' => 'Directrice générale'];
$dor = qa($A, 'dorothee');
$person = function (string $name, string $role, string $cls = '') use ($ini, $key, $people) {
    $k = $key($name); $has = isset($people[$k]);
    $tag = $has ? 'a' : 'span';
    return '<' . $tag . ' class="og-p ' . $cls . '"' . ($has ? ' href="/equipe/' . e($k) . '"' : '') . '><span class="ag-av">' . e($ini($name)) . '</span><span><b>' . e($name) . '</b><small>' . e($role) . '</small></span></' . $tag . '>';
};
$comptas = [];
foreach ($f['teams'] as $t) foreach ($t['compta'] as $c => $n) $comptas[$c][$t['gest']] = $n;
ksort($comptas);
?>
<header class="head">
  <div>
    <div class="eyebrow">Équipe · qui travaille avec qui</div>
    <h1>Organigramme</h1>
    <div class="date"><?= (int)$f['staff'] ?> personnes · équipes relevées dans ImmOpen (gestionnaire, assistante et comptables de chaque immeuble)</div>
  </div>
</header>

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
