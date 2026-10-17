<?php
$fd = fn(?string $d) => $d ? date('d/m/Y', strtotime($d)) : '—';
$jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
$st = ['planifiee' => ['Planifiée', 'ok'], 'aplanifier' => ['À planifier', 'warn'], 'tenue' => ['Tenue', 'neutral']];
$stc = $stc ?? [];
?>
<header class="head">
  <div>
    <div class="eyebrow">Direction · source ImmOpen<?= $date ? ' · relevé du ' . e($fd($date)) : '' ?></div>
    <h1>Planning des AG</h1>
    <div class="date"><?= (int)$total ?> immeubles répartis entre <?= count($by) ?> gestionnaires · lots, échéances et rentabilité de chaque immeuble</div>
  </div>
</header>

<script type="application/json" id="agPrefs"><?= json_encode($prefs ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<?php if (!$by): ?>
  <div class="empty"><strong>Planning non disponible.</strong>Le planning est relevé dans ImmOpen (A.G. › Planning AG).</div>
<?php else: ?>
<div class="ag-kpis" role="group" aria-label="Filtres rapides">
  <?php foreach ([['mois', $alerts['mois'], 'AG dans les 30 jours', ''], ['mandat', $alerts['mandat'], 'Mandats expirés ou à renouveler', 'crit'], ['convoc', $alerts['convoc'], 'Convocations en retard', 'crit'], ['aplanifier', $alerts['aplanif'], 'AG à planifier', 'warn']] as [$k, $n, $l, $lv]): ?>
  <button type="button" class="ag-kpi <?= $n && $lv ? $lv : '' ?>" data-k="<?= $k ?>" aria-pressed="false">
    <span class="n"><?php if ($n && $lv === 'crit'): ?><svg class="ag-tri" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 21h20L12 3z" fill="currentColor"/><path d="M12 10v5M12 18h.01" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg><?php endif; ?><?= (int)$n ?></span>
    <span class="l"><?= e($l) ?></span><span class="go">Afficher →</span>
  </button>
  <?php endforeach; ?>
</div>

<div class="ag-bar">
  <label class="ag-search"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span class="sr">Rechercher</span><input type="search" id="agQ" placeholder="Immeuble, code, comptable…" autocomplete="off"></label>
  <label class="ag-sel"><span>Gestionnaire</span><select id="agG"><option value="">Tous (<?= (int)$total ?>)</option><?php foreach ($by as $g => $rows): ?><option value="<?= e($g) ?>"><?= e($g) ?> (<?= count($rows) ?>)</option><?php endforeach; ?></select></label>
  <div class="ag-seg" role="group" aria-label="Statut">
    <button type="button" data-s="" aria-pressed="true">Tous</button>
    <button type="button" data-s="planifiee" aria-pressed="false">Planifiées <span><?= (int)($stc['planifiee'] ?? 0) ?></span></button>
    <button type="button" data-s="aplanifier" aria-pressed="false">À planifier <span><?= (int)($stc['aplanifier'] ?? 0) ?></span></button>
    <button type="button" data-s="tenue" aria-pressed="false">Tenues <span><?= (int)($stc['tenue'] ?? 0) ?></span></button>
    <button type="button" data-s="alerte" aria-pressed="false">Alertes <span><?= (int)($nAlert ?? 0) ?></span></button>
  </div>
</div>
<div class="ag-meta"><span id="agCount" class="muted small"></span><span class="ag-meta-r"><span class="ag-legend muted small"><span class="dot-fin green"></span>rentable <span class="dot-fin orange"></span>fragile <span class="dot-fin red"></span>non rentable <span class="dot-fin lost"></span>perdu</span><span class="ag-cols-wrap"><button type="button" class="btn btn-ghost ag-colbtn" id="agColsBtn" aria-expanded="false"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 4v16"/></svg>Colonnes</button><div class="ag-pop" id="agColsPop" hidden></div></span></span></div>
<div class="empty" id="agNone" hidden><strong>Aucun immeuble ne correspond.</strong>Modifiez la recherche ou les filtres.</div>

<?php foreach ($by as $g => $rows): $n = count(array_filter($rows, fn($r) => $r['status'] === 'planifiee')); $na = count(array_filter($rows, fn($r) => (bool)array_filter($r['flags'], fn($f) => $f[0] === 'crit'))); ?>
<section class="ag-group" data-g="<?= e($g) ?>">
  <header class="ag-gh">
    <div class="ag-gn"><span class="ag-av"><?= e(mb_strtoupper(mb_substr($g, 0, 1) . (mb_substr(strrchr(' ' . $g, ' '), 1, 1) ?: ''))) ?></span><h2><?= e($g) ?></h2></div>
    <div class="ag-gs"><span><b data-gc><?= count($rows) ?></b> immeubles</span><span><b><?= $n ?></b> AG planifiées</span><span><b><?= number_format(array_sum(array_map(fn($r) => (int)($r['lots'] ?? 0), $rows)), 0, ',', ' ') ?></b> lots</span><?php if ($na): ?><span class="crit"><b><?= $na ?></b> en alerte</span><?php endif; ?></div>
  </header>
  <div class="tbl"><table class="sec-table ag-tbl">
    <thead><tr><th data-col="date">Date de l'AG</th><th data-col="fin" title="Rentabilité"></th><th data-col="imm">Immeuble</th><th data-col="lots" class="r">Lots</th><th data-col="compta">Comptable</th><th data-col="finex">Fin d'exercice</th><th data-col="mandat">Fin de mandat</th><th data-col="statut">Statut</th><th data-col="flags">À surveiller</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $s = $st[$r['status']]; $alert = (bool)array_filter($r['flags'], fn($f) => $f[0] === 'crit'); ?>
      <tr data-href="/immeubles/<?= e($r['code']) ?>" class="row-link<?= $alert ? ' ag-alert' : '' ?>" data-s="<?= e($r['status']) ?>" data-a="<?= $alert ? '1' : '0' ?>" data-k="<?= e(implode(' ', $r['keys'] ?? [])) ?>" data-q="<?= e(mb_strtolower($r['code'] . ' ' . $r['nom'] . ' ' . $r['compta'])) ?>">
        <td class="nowrap" data-col="date" data-v="<?= e($r['next'] ?: ($r['status'] === 'tenue' ? '9' . ($r['last'] ?? '') : '8')) ?>"><?php if ($r['next']): ?><span class="ag-d"><b><?= e($jours[(int)date('w', strtotime($r['next']))] . ' ' . $fd($r['next'])) ?></b><?= $r['ag_heure'] ? '<small>' . e($r['ag_heure']) . '</small>' : '' ?></span><?php elseif ($r['status'] === 'tenue'): ?><span class="muted">Tenue le <?= e($fd($r['last'])) ?></span><?php else: ?><span class="muted">À fixer</span><?php endif; ?></td>
        <td data-col="fin" data-v="<?= e((string)($r['margin'] ?? '')) ?>"><?php if ($r['color']): ?><span class="dot-fin <?= e($r['color']) ?>" title="<?= e($r['colorLabel'] . ($r['margin'] !== null && $r['color'] !== 'lost' ? ' · marge estimée ' . round($r['margin'] * 100) . ' %' : '')) ?>"></span><?php endif; ?></td>
        <td data-col="imm" data-v="<?= e($r['code']) ?>"><a class="row-a" href="/immeubles/<?= e($r['code']) ?>"><span class="mono muted"><?= e($r['code']) ?></span> <strong><?= e($r['nom']) ?></strong></a></td>
        <td class="r mono" data-col="lots" data-v="<?= (int)($r['lots'] ?? 0) ?>"><?= $r['lots'] !== null ? (int)$r['lots'] : '—' ?></td>
        <td class="small" data-col="compta"><?= e($r['compta']) ?></td>
        <td class="mono small" data-col="finex" data-v="<?= e((string)($r['fin_ex'] ?? '')) ?>"><?= e($fd($r['fin_ex'])) ?></td>
        <td class="mono small" data-col="mandat" data-v="<?= e((string)($r['fin_mandat'] ?? '')) ?>"><?= e($fd($r['fin_mandat'])) ?></td>
        <td data-col="statut"><span class="chip <?= e($s[1]) ?>"><?= e($s[0]) ?></span></td>
        <td class="small" data-col="flags" data-v="<?= count(array_filter($r['flags'], fn($f) => $f[0] === 'crit')) * 10 + count($r['flags']) ?>"><?php foreach ($r['flags'] as $fl): ?><span class="ag-flag <?= e($fl[0]) ?>"><?= e($fl[1]) ?></span><?php endforeach; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php endforeach; ?>
<?php endif; ?>
