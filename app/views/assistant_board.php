<header class="head">
  <div>
    <div class="eyebrow">Perso · visible par vous seul</div>
    <h1>Tâches IA</h1>
    <div class="date">Consignes vérifiées et traitées toutes les heures · nouvelle consigne : pastille en bas à droite</div>
  </div>
  <div class="head-actions"><button type="button" class="btn btn-primary" data-ia-open>Nouvelle consigne</button></div>
</header>

<div class="grid">
  <section class="col-12">
        <div class="ia-board">
      <?php $mois = MOIS_LONG[(int)date('n') - 1] ?? '';
      foreach ([['todo', 'En attente', $ia_todo], ['doing', 'En cours', $ia_doing], ['done', 'Fait en ' . $mois, $ia_done]] as [$k, $label, $list]): $n = count($list); ?>
      <div class="panel ia-col ia-col-<?= $k ?>">
        <div class="sec-h flat"><h3><i class="ia-pt" aria-hidden="true"></i><?= e($label) ?></h3><span class="chip <?= $k === 'done' ? 'ok' : ($k === 'doing' ? 'warn' : 'todo') ?>"><?= $n ?></span></div>
        <?php if (!$list): ?><p class="muted small"><?= $k === 'todo' ? 'Aucune consigne en attente.' : ($k === 'doing' ? 'Rien en cours.' : 'Aucune consigne faite ce mois-ci.') ?></p>
        <?php else: ?><ul class="list ia-list" data-more-list><?php foreach ($list as $i => $c) { $hidden = $i >= 10; include __DIR__ . '/_ia_item.php'; } ?></ul>
        <?php if ($n > 10): ?><button type="button" class="ia-more" data-more data-less="Réduire ↑">Voir plus (<?= $n - 10 ?>) ↓</button><?php endif; ?>
        <?php endif; ?>
        <?php if ($k === 'done' && $ia_done_all > 0): ?><a class="ia-more" href="/versioning?f=ia">Historique complet (<?= (int)$ia_done_all ?>) →</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>
