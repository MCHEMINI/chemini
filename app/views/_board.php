<?php
/* Tableau d'affichage de la société : $posts, $isDir */
$cats = BOARD_CATS;
$used = array_unique(array_column($posts, 'cat'));
?>
<section class="bd">
  <a class="sv-cta" href="/enquete"><span class="pl-ic" aria-hidden="true">📝</span><span><b>Questionnaire de fonctionnement</b><small>10 minutes pour dire comment vous travaillez et ce que vous aimeriez automatiser</small></span><span class="btn btn-primary">Répondre</span></a>
  <?php if ($isDir): ?>
  <details class="panel bd-new">
    <summary><span class="btn btn-primary">Publier une information</span><span class="muted small">Visible immédiatement par toute l'équipe</span></summary>
    <form class="stack" data-api="board/add">
      <div class="bd-row">
        <div class="field bd-grow"><label for="bd-t">Titre</label><input class="input" id="bd-t" name="title" maxlength="160" required placeholder="Ex. Fermeture du cabinet le 11 novembre"></div>
        <div class="field"><label for="bd-c">Rubrique</label><select class="input" id="bd-c" name="cat"><?php foreach ($cats as $k => $c): ?><option value="<?= e($k) ?>"><?= e($c[0]) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label for="bd-b">Message</label><textarea class="input bd-ta" id="bd-b" name="body" maxlength="4000" rows="4" placeholder="Le détail de l'information…"></textarea></div>
      <div class="bd-row">
        <div class="field bd-grow"><label for="bd-l">Lien (facultatif)</label><input class="input" id="bd-l" name="link" type="url" maxlength="500" placeholder="https://…"></div>
        <div class="field"><label for="bd-u">Afficher jusqu'au (facultatif)</label><input class="input" id="bd-u" name="until" type="date"></div>
        <label class="bd-pin"><input type="checkbox" name="pinned" value="1"> Épingler en haut</label>
      </div>
      <p class="form-error" hidden></p>
      <div><button class="btn btn-primary">Publier</button></div>
    </form>
  </details>
  <?php endif; ?>

  <?php if ($posts): ?>
  <nav class="vs-filters bd-f" aria-label="Rubriques">
    <a href="#" class="on" data-bd="">Tout <span><?= count($posts) ?></span></a>
    <?php foreach ($cats as $k => $c): if (!in_array($k, $used, true)) continue; ?><a href="#" data-bd="<?= e($k) ?>"><?= e($c[0]) ?> <span><?= count(array_filter($posts, fn($p) => $p['cat'] === $k)) ?></span></a><?php endforeach; ?>
  </nav>
  <div class="bd-grid">
    <?php foreach ($posts as $p): $c = $cats[$p['cat']] ?? $cats['annonce']; ?>
    <article class="bd-card<?= $p['pinned'] ? ' pin' : '' ?>" data-cat="<?= e($p['cat']) ?>" style="--bc:<?= e($c[1]) ?>">
      <header><span class="bd-cat"><?= e($c[0]) ?></span><?php if ($p['pinned']): ?><span class="bd-pinned" title="Épinglé">📌</span><?php endif; ?></header>
      <h3><?= e($p['title']) ?></h3>
      <?php if ($p['body'] !== ''): ?><p><?= nl2br(e($p['body'])) ?></p><?php endif; ?>
      <?php if ($p['link']): ?><a class="more" href="<?= e($p['link']) ?>" target="_blank" rel="noopener">Ouvrir le lien →</a><?php endif; ?>
      <footer><span><?= e(date_short((int)$p['created_at'])) ?><?= $p['by_name'] ? ' · ' . e($p['by_name']) : '' ?><?= $p['until'] ? ' · jusqu\'au ' . e(date('d/m', strtotime($p['until']))) : '' ?></span>
        <?php if ($isDir): ?><span><button type="button" class="btn-link" data-api="board/pin" data-id="<?= (int)$p['id'] ?>"><?= $p['pinned'] ? 'Désépingler' : 'Épingler' ?></button> <button type="button" class="btn-link" data-api="board/delete" data-id="<?= (int)$p['id'] ?>" data-confirm="Retirer ?">Retirer</button></span><?php endif; ?></footer>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty"><strong>Aucune information publiée.</strong><?= $isDir ? 'Publiez la première : organisation, congés, procédures, événements…' : 'Les informations de la Direction s\'afficheront ici.' ?></div>
  <?php endif; ?>
</section>
