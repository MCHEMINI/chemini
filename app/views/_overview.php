<?php
/* Vue d'ensemble Direction : un bloc par section, chiffres utiles et lien direct. */
$k = fn($v) => number_format($v / 1000, 0, ',', ' ') . ' k€';
$eur = fn($v) => number_format($v, 0, ',', ' ') . ' €';
$h = $ov['hono']; $r = $ov['rent']; $o = $ov['ops']; $c = $ov['compta'];
$tri = '<svg class="ov-tri" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 2 21h20L12 3z" fill="currentColor"/><path d="M12 10v5M12 18h.01" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
$row = function (string $label, string $val, string $lv = '', string $href = '') use ($tri) {
    $v = ($lv === 'crit' ? $tri : '') . e($val);
    return '<li class="' . $lv . '"><span>' . ($href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : e($label)) . '</span><b>' . $v . '</b></li>';
};
?>
<div class="ov-grid">
  <a class="ov-card" href="/honoraires#opti">
    <div class="ov-h"><span class="ov-eye">Honoraires</span><span class="ov-go">Optimisation →</span></div>
    <div class="ov-n"><?= e($k($h['ttc'])) ?> <small class="<?= $h['evol'] >= 0 ? 'up' : 'down' ?>"><?= ($h['evol'] >= 0 ? '+' : '') . number_format($h['evol'], 1, ',', ' ') ?> %</small></div>
    <div class="ov-l">à voter en 2026-2027 sur <?= (int)$h['act'] ?> immeubles</div>
    <ul class="ov-rows">
      <?= $row('AG tenues depuis le 1er juillet', $h['held'] . ' / ' . $h['act']) ?>
      <?= $row('AG votées sous la proposition', $h['under'] ? $h['under'] . ' · ' . $eur($h['underGap']) : '0', $h['under'] ? 'crit' : 'ok') ?>
      <?= $row('Immeubles perdus', $h['lost'] ? $h['lost'] . ' · −' . $eur($h['lostH']) : '0', $h['lost'] ? 'warn' : 'ok') ?>
    </ul>
  </a>
  <a class="ov-card" href="/planning-ag">
    <div class="ov-h"><span class="ov-eye">Planning des AG</span><span class="ov-go">Planning →</span></div>
    <div class="ov-n"><?= count($o['upcoming']) ?></div>
    <div class="ov-l">AG dans les 30 prochains jours · <?= (int)$o['week'] ?> sous 14 jours</div>
    <ul class="ov-rows">
      <?= $row('Convocations en retard', (string)count($o['convoc']), count($o['convoc']) ? 'crit' : 'ok') ?>
      <?= $row('Mandats expirant sous 60 jours', (string)count($o['mandat']), count($o['mandat']) ? 'crit' : 'ok') ?>
      <?= $row('PV non remis (+21 jours)', (string)count($o['nopv']), count($o['nopv']) ? 'crit' : 'ok') ?>
    </ul>
  </a>
  <a class="ov-card" href="/strategies">
    <div class="ov-h"><span class="ov-eye">Rentabilité des immeubles</span><span class="ov-go">Stratégies →</span></div>
    <div class="ov-n <?= $r['red'] ? 'crit' : '' ?>"><?= (int)$r['red'] ?></div>
    <div class="ov-l">immeubles non rentables · manque à gagner <?= e($eur($r['gapRed'])) ?> TTC / an</div>
    <div class="ov-bar" title="<?= (int)$r['green'] ?> rentables, <?= (int)$r['orange'] ?> fragiles, <?= (int)$r['red'] ?> non rentables">
      <?php $tt = max(1, $r['green'] + $r['orange'] + $r['red']); ?>
      <i class="g" style="width:<?= round($r['green'] / $tt * 100, 1) ?>%"></i><i class="o" style="width:<?= round($r['orange'] / $tt * 100, 1) ?>%"></i><i class="r" style="width:<?= round($r['red'] / $tt * 100, 1) ?>%"></i>
    </div>
    <div class="ov-legend"><span class="g"><?= (int)$r['green'] ?> rentables</span><span class="o"><?= (int)$r['orange'] ?> fragiles</span><span class="r"><?= (int)$r['red'] ?> non rentables</span></div>
  </a>
  <a class="ov-card" href="/honoraires#annexes">
    <div class="ov-h"><span class="ov-eye">Honoraires annexes</span><span class="ov-go">Détail →</span></div>
    <?php if ($h['annexes']): ?>
    <div class="ov-n"><?= e($k($h['annexes']['total'])) ?></div>
    <div class="ov-l">facturés sur l'exercice <?= e(str_replace('-', '–', $h['annexes']['year'])) ?><?= $h['annexes']['end'] ? ' (au ' . e(date('d/m/Y', strtotime($h['annexes']['end']))) . ')' : '' ?></div>
    <?php else: ?><div class="ov-n">—</div><div class="ov-l">relevé ImmOpen le 1er du mois</div><?php endif; ?>
  </a>
  <a class="ov-card" href="/comptabilite">
    <div class="ov-h"><span class="ov-eye">Comptabilité</span><span class="ov-go">Contrôle →</span></div>
    <div class="ov-n <?= $c && $c['anom'] ? 'crit' : '' ?>"><?= $c ? (int)$c['anom'] : '—' ?></div>
    <div class="ov-l">incohérences à traiter<?= $c ? ' · contrôle du ' . e(date('d/m', $c['at'])) : ' · contrôle le vendredi à 19 h' ?></div>
    <ul class="ov-rows"><?= $row('Factures manquantes', $c ? (string)$c['fact'] : '—', $c && $c['fact'] ? 'warn' : 'ok') ?></ul>
  </a>
  <div class="ov-card">
    <div class="ov-h"><span class="ov-eye">Mon activité</span></div>
    <ul class="ov-rows big">
      <?= $row('Mails à traiter', (string)$ov['mails'], $ov['mails'] ? 'warn' : 'ok', '/espace') ?>
      <?= $row('Tâches IA en cours', (string)$ov['ia'], '', '/assistant') ?>
      <?= $row('Modifications des honoraires (7 j)', (string)count($ov['changes']) . (count($ov['changes']) >= 5 ? '+' : ''), '', '/honoraires/modifications') ?>
    </ul>
  </div>
</div>

<div class="grid ov-lists">
  <section class="col-7">
    <div class="sec-h"><h2>AG des 30 prochains jours</h2><a href="/planning-ag">Planning complet</a></div>
    <?php if (!$o['upcoming']): ?><div class="empty"><strong>Aucune AG planifiée dans les 30 jours.</strong>Le planning est relevé dans ImmOpen le 1er du mois<?= $o['date'] ? ' (dernier relevé le ' . e(date('d/m/Y', strtotime($o['date']))) . ')' : '' ?>.</div>
    <?php else: ?>
    <ul class="ov-ag">
      <?php foreach (array_slice($o['upcoming'], 0, 10) as $a): $ts = strtotime($a['ag_date']); ?>
      <li><div class="cal <?= $ts <= strtotime('+7 days') ? 'soon' : '' ?>"><b><?= date('d', $ts) ?></b><span><?= e(MOIS[(int)date('n', $ts) - 1]) ?></span></div>
        <div><a href="/immeubles/<?= e($a['code']) ?>"><span class="mono muted"><?= e($a['code']) ?></span> <?= e($a['nom'] ?? '') ?></a><div class="d"><?= e(trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($a['gest'] ?? '')))) ?><?= !empty($a['ag_heure']) ? ' · ' . e($a['ag_heure']) : '' ?><?= empty($a['convoc']) ? ' · <span class="chip warn">convocation à envoyer</span>' : '' ?></div></div></li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($o['upcoming']) > 10): ?><p class="muted small"><a href="/planning-ag"><?= count($o['upcoming']) - 10 ?> autres AG →</a></p><?php endif; ?>
    <?php endif; ?>
  </section>
  <section class="col-5">
    <div class="sec-h"><h2>Dernières modifications</h2><a href="/honoraires/modifications">Historique</a></div>
    <?php if (!$ov['changes']): ?><div class="empty"><strong>Aucune modification cette semaine.</strong>Saisies et mises à jour automatiques du tableau des honoraires.</div>
    <?php else: ?>
    <ul class="ov-chg">
      <?php foreach ($ov['changes'] as $ch): ?>
      <li><span class="mono muted"><?= e(date('d/m H:i', $ch['at'])) ?></span><div><span class="hc-tab"><?= e($ch['tab']) ?></span><?= $ch['ref'] ? ' · <strong>' . e($ch['ref']) . '</strong>' : '' ?><div class="d"><?= e($ch['field']) ?><?= $ch['new'] !== '' ? ' → ' . e($ch['new']) : '' ?></div></div></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>
</div>
