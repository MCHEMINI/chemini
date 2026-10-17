<?php $eur = fn($v) => number_format((float)$v, 0, ',', ' ') . ' €'; $tot = array_sum(array_column($rows, 'v')); ?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/honoraires">Honoraires</a> · classement</div>
    <h1>Tous les immeubles</h1>
    <div class="date"><?= count($rows) ?> immeubles actifs classés par honoraires 2026-2027 à voter (TTC) · total <?= e($eur($tot)) ?> · cliquez sur un en-tête pour trier</div>
  </div>
  <a class="btn" href="/honoraires">← Retour aux honoraires</a>
</header>
<div class="ag-bar rk-bar"><label class="ag-search"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span class="sr">Rechercher</span><input type="search" id="rkQ" placeholder="Immeuble, numéro, portefeuille…"></label></div>
<div class="ag-group"><div class="tbl"><table class="sec-table ag-tbl rk-tbl" data-sortable>
  <thead><tr><th data-k="n" class="r">Rang</th><th data-k="code">N°</th><th data-k="imm">Immeuble</th><th data-k="port">Port.</th><th data-k="lots" class="r">Lots</th><th data-k="p" class="r">Hon. 25-26</th><th data-k="v" class="r">Hon. 26-27 à voter</th><th data-k="ev" class="r">Évol.</th><th data-k="pl" class="r">€ / lot</th><th data-k="c">Rentab.</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
    <tr class="row-link" data-href="/immeubles/<?= e($r['code']) ?>" data-q="<?= e(mb_strtolower($r['code'] . ' ' . $r['imm'] . ' ' . $r['port'])) ?>">
      <td class="r mono" data-v="<?= $i + 1 ?>"><?= $i + 1 ?></td>
      <td class="mono muted" data-v="<?= e($r['code']) ?>"><?= e($r['code']) ?></td>
      <td data-v="<?= e($r['imm']) ?>"><a class="row-a" href="/immeubles/<?= e($r['code']) ?>"><strong><?= e($r['imm']) ?></strong></a></td>
      <td data-v="<?= e($r['port']) ?>"><?= e($r['port']) ?></td>
      <td class="r mono" data-v="<?= $r['lots'] ?>"><?= $r['lots'] ?></td>
      <td class="r mono" data-v="<?= $r['p'] ?>"><?= e($eur($r['p'])) ?></td>
      <td class="r mono" data-v="<?= $r['v'] ?>"><strong><?= e($eur($r['v'])) ?></strong></td>
      <td class="r mono" data-v="<?= $r['ev'] ?? -999 ?>"><?= $r['ev'] === null ? '—' : '<span class="' . ($r['ev'] < 0 ? 'neg' : 'pos') . '">' . ($r['ev'] >= 0 ? '+' : '') . number_format($r['ev'], 1, ',', ' ') . ' %</span>' ?></td>
      <td class="r mono" data-v="<?= round($r['pl']) ?>"><?= e($eur($r['pl'])) ?></td>
      <td data-v="<?= e((string)$r['color']) ?>"><?php if ($r['color']): ?><span class="dot-fin <?= e($r['color']) ?>" title="<?= e($r['clabel']) ?>"></span><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
