<?php
[$title, $ico, $desc, $F] = $R;
$fmt = function ($k, $v) use ($F, $B, $people) {
    $t = $F[$k][1];
    if ($v === '' || $v === null) return '<span class="muted">—</span>';
    if ($t === 'money') return number_format((float)$v, 0, ',', ' ') . ' €';
    if ($t === 'date') return date('d/m/Y', strtotime((string)$v));
    if ($t === 'bld') return '<a href="/immeubles/' . e($v) . '"><span class="mono muted">' . e($v) . '</span> ' . e($B[$v]['imm'] ?? '') . '</a>';
    if ($t === 'person') return e($people[$v]['name'] ?? $v);
    return e((string)$v);
};
$nCrit = count(array_filter($items, fn($i) => $i['al'][0] === 'crit')); $nWarn = count(array_filter($items, fn($i) => $i['al'][0] === 'warn'));
?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/pilotage">Pilotage</a> · registre</div>
    <h1><?= e($title) ?></h1>
    <div class="date"><?= e($desc) ?> · <?= count($items) ?> fiche<?= count($items) > 1 ? 's' : '' ?><?= $nCrit ? ' · ' . $nCrit . ' urgente' . ($nCrit > 1 ? 's' : '') : '' ?><?= $nWarn ? ' · ' . $nWarn . ' à surveiller' : '' ?></div>
  </div>
</header>

<details class="panel bd-new" id="regForm"<?= $items ? '' : ' open' ?>>
  <summary><span class="btn btn-primary" id="regFormT">Nouvelle fiche</span><span class="muted small"><?= $reg === 'travaux' ? 'Les résolutions des PV relevés sont ajoutées automatiquement' : ($reg === 'impayes' ? 'Relevé automatique dans ImmOpen le 1er du mois, saisie possible' : 'Les alertes se calculent automatiquement') ?></span></summary>
  <form class="pl-form" data-api="reg/save">
    <input type="hidden" name="reg" value="<?= e($reg) ?>"><input type="hidden" name="id" value="0">
    <?php foreach ($F as $k => [$label, $t]): ?>
    <div class="field<?= in_array($t, ['text'], true) ? ' pl-wide' : '' ?>"><label for="rf-<?= e($k) ?>"><?= e($label) ?></label>
      <?php if ($t === 'bld'): ?><select class="input" id="rf-<?= e($k) ?>" name="<?= e($k) ?>"><option value="">—</option><?php foreach ($B as $c => $b): ?><option value="<?= e($c) ?>"><?= e($c . ' · ' . $b['imm']) ?></option><?php endforeach; ?></select>
      <?php elseif ($t === 'person'): ?><select class="input" id="rf-<?= e($k) ?>" name="<?= e($k) ?>"><option value="">—</option><?php foreach ($people as $p): ?><option value="<?= e($p['key']) ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select>
      <?php elseif (is_array($t)): ?><select class="input" id="rf-<?= e($k) ?>" name="<?= e($k) ?>"><option value="">—</option><?php foreach ($t as $o): ?><option><?= e($o) ?></option><?php endforeach; ?></select>
      <?php else: ?><input class="input" id="rf-<?= e($k) ?>" name="<?= e($k) ?>" type="<?= $t === 'date' ? 'date' : ($t === 'int' || $t === 'money' ? 'number' : 'text') ?>"<?= $t === 'money' ? ' step="0.01"' : '' ?>><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <p class="form-error" hidden></p>
    <div class="pl-act"><button class="btn btn-primary">Enregistrer</button> <button type="button" class="btn-link" id="regReset">Annuler</button></div>
  </form>
</details>

<?php if (!$items): ?>
<div class="empty"><strong>Aucune fiche pour l'instant.</strong>Ajoutez la première avec le formulaire ci-dessus.</div>
<?php else: ?>
<div class="ag-bar rk-bar"><label class="ag-search"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span class="sr">Rechercher</span><input type="search" id="rkQ" placeholder="Rechercher…"></label></div>
<div class="ag-group"><div class="tbl"><table class="sec-table ag-tbl rk-tbl" data-sortable>
  <thead><tr><th>Alerte</th><?php foreach ($F as $k => [$label]): ?><th><?= e($label) ?></th><?php endforeach; ?><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $it): [$lv, $lab] = $it['al']; ?>
    <tr class="<?= $lv === 'crit' ? 'ag-alert' : '' ?>" data-q="<?= e(mb_strtolower(implode(' ', array_map('strval', $it['d'])) . ' ' . ($B[$it['code']]['imm'] ?? ''))) ?>">
      <td data-v="<?= ['crit' => 0, 'warn' => 1, '' => 2, 'ok' => 3][$lv] ?? 2 ?>"><?php if ($lab !== ''): ?><span class="chip <?= $lv === 'crit' ? 'crit' : ($lv === 'warn' ? 'warn' : ($lv === 'ok' ? 'ok' : 'neutral')) ?>"><?= e($lab) ?></span><?php endif; ?></td>
      <?php foreach ($F as $k => [$label, $t]): $v = $it['d'][$k] ?? ''; if ($reg === 'echeances' && $k === 'prochaine' && $v === '') $v = reg_next($it['d']); ?>
      <td class="<?= in_array($t, ['money', 'int'], true) ? 'r mono' : ($t === 'date' ? 'mono small' : '') ?>" data-v="<?= e((string)$v) ?>"><?= $fmt($k, $v) ?></td>
      <?php endforeach; ?>
      <td class="nowrap"><button type="button" class="btn-link" data-edit='<?= e(json_encode(['id' => (int)$it['id']] + $it['d'], JSON_UNESCAPED_UNICODE)) ?>'>Modifier</button> <button type="button" class="btn-link" data-api="reg/delete" data-id="<?= (int)$it['id'] ?>" data-confirm="Supprimer ?">Supprimer</button></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>
<?php endif; ?>
