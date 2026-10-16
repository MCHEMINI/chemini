<header class="head">
  <div>
    <div class="eyebrow">Personnel · visible par vous seul</div>
    <h1>Tâches IA</h1>
    <div class="date">Consignes vérifiées et traitées toutes les heures · nouvelle consigne : pastille en bas à droite</div>
  </div>
  <div class="head-actions"><button type="button" class="btn btn-primary" data-ia-open>Nouvelle consigne</button></div>
</header>

<div class="grid">
  <section class="col-12">
        <div class="ia-board">
      <?php foreach ([['todo', 'En attente', $ia_todo], ['doing', 'En cours', $ia_doing], ['done', 'Fait', $ia_done]] as [$k, $label, $list]): ?>
      <div class="panel ia-col ia-col-<?= $k ?>">
        <div class="sec-h flat"><h3><i class="ia-pt" aria-hidden="true"></i><?= $label ?></h3><span class="chip <?= $k === 'done' ? 'ok' : ($k === 'doing' ? 'warn' : 'todo') ?>"><?= $k === 'done' ? $ia_done_n : count($list) ?></span></div>
        <?php if (!$list): ?><p class="muted small"><?= $k === 'todo' ? 'Aucune consigne en attente.' : ($k === 'doing' ? 'Rien en cours.' : 'Aucune consigne faite.') ?></p>
        <?php else: ?><ul class="list ia-list"><?php foreach ($list as $c) include __DIR__ . '/_ia_item.php'; ?></ul><?php endif; ?>
        <?php if ($k === 'done' && $ia_done_n > 10): ?><a class="btn-link ia-more" href="/assistant/historique">Voir plus (<?= $ia_done_n - 10 ?> plus anciennes) →</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>
