<?php
$tax = $snap['tax'] ?? [];
$opt = function (string $tx) use ($tax) { $o = ''; $l = $tax[$tx] ?? []; asort($l); foreach ($l as $id => $nm) $o .= '<option value="' . (int)$id . '">' . e($nm) . '</option>'; return $o; };
$host = $conn ? parse_url($conn['url'], PHP_URL_HOST) : '';
$radio = function (string $name, array $vals, string $cur = '') { $o = '<div class="na-seg" role="radiogroup">'; foreach ($vals as $v => $l) $o .= '<label><input type="radio" name="' . $name . '" value="' . e((string)$v) . '"' . ((string)$v === $cur ? ' checked' : '') . '><span>' . e($l) . '</span></label>'; return $o . '</div>'; };
?>
<header class="head">
  <div>
    <div class="eyebrow"><a href="/site-web/annonces">Annonces</a> · <?= e((string)$host) ?></div>
    <h1>Nouvelle annonce</h1>
    <div class="date">Remplissez la fiche depuis votre téléphone : les mentions obligatoires sont demandées selon le bien et ajoutées automatiquement à l'annonce.</div>
  </div>
</header>
<?php if (!$conn): ?>
<div class="panel ws-empty"><h2>Le site n'est pas encore relié à Chemini</h2><p class="muted">L'administrateur doit d'abord relier le site WordPress (Site Web › Connexion).</p></div>
<?php else: ?>
<form id="naF" class="na-form" novalidate>
  <section class="panel na-sec">
    <h2><span>1</span> Le bien</h2>
    <div class="field"><span class="na-l">Transaction</span><?= $radio('tx', ['vente' => 'Vente', 'location' => 'Location'], 'vente') ?></div>
    <div class="na-g2">
      <div class="field"><label for="na-type">Type de bien *</label><select class="input" id="na-type" name="type" required><option value="">Choisir…</option><?= $opt('types') ?></select></div>
      <div class="field"><label for="na-emp">Secteur *</label><select class="input" id="na-emp" name="emplacement" required><option value="">Choisir…</option><?= $opt('emplacement') ?></select></div>
    </div>
    <div class="field"><label for="na-addr">Adresse (rue) *</label><input class="input" id="na-addr" name="address" required maxlength="200" placeholder="Ex. rue du Poteau" autocomplete="off"></div>
    <div class="field"><label for="na-title">Titre de l'annonce *</label><input class="input" id="na-title" name="title" required maxlength="160" placeholder="Ex. Paris 18e – Jules Joffrin, 2 pièces lumineux"></div>
    <div class="field"><label for="na-desc">Description</label><textarea class="input" id="na-desc" name="description" rows="6" maxlength="8000" placeholder="Situation, état, points forts, transports…"></textarea></div>
    <label class="na-check"><input type="checkbox" name="logement" value="1" checked> Il s'agit d'un logement (pas d'un box, parking, local…)</label>
  </section>

  <section class="panel na-sec">
    <h2><span>2</span> Photos *</h2>
    <label class="na-drop" for="na-ph"><input id="na-ph" type="file" accept="image/*" multiple><span>📷 Prendre ou choisir des photos</span><small>La première sera la photo principale · 15 au maximum</small></label>
    <div class="na-thumbs" id="naThumbs"></div>
  </section>

  <section class="panel na-sec">
    <h2><span>3</span> Caractéristiques</h2>
    <div class="na-g2">
      <div class="field"><label for="na-surf">Surface habitable (m²) *</label><input class="input" id="na-surf" name="surface" inputmode="decimal" required></div>
      <div class="field" data-show="vente copro"><label for="na-carrez">Superficie loi Carrez (m²) *</label><input class="input" id="na-carrez" name="carrez" inputmode="decimal"></div>
      <div class="field"><label for="na-p">Pièces</label><select class="input" id="na-p" name="pieces"><option value="">—</option><?php for ($i = 1; $i <= 8; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="na-ch">Chambres</label><select class="input" id="na-ch" name="chambres"><option value="">—</option><?php for ($i = 0; $i <= 9; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="na-sdb">Salles de bain</label><select class="input" id="na-sdb" name="sdb"><option value="">—</option><?php for ($i = 0; $i <= 5; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
      <div class="field"><label for="na-et">Étage</label><select class="input" id="na-et" name="etage"><option value="">—</option><option value="rdc">Rez-de-chaussée</option><?php for ($i = 1; $i <= 10; $i++): ?><option><?= $i ?></option><?php endfor; ?></select></div>
    </div>
    <div class="na-feat"><?php foreach (['balcon' => 'Balcon', 'terrasse' => 'Terrasse', 'jardin' => 'Jardin', 'ascenseur' => 'Ascenseur', 'cave' => 'Cave', 'parking' => 'Parking', 'garage' => 'Garage', 'box' => 'Box', 'gardien' => 'Gardien', 'piscine' => 'Piscine', 'wc' => 'WC séparé'] as $k => $l): ?><label><input type="checkbox" name="features" value="<?= $k ?>"><span><?= $l ?></span></label><?php endforeach; ?></div>
  </section>

  <section class="panel na-sec" data-show="vente">
    <h2><span>4</span> Prix et honoraires</h2>
    <div class="field"><label for="na-prix">Prix affiché (€) *</label><input class="input" id="na-prix" name="prix" inputmode="decimal"><small class="muted">Honoraires inclus s'ils sont à la charge de l'acquéreur.</small></div>
    <div class="field"><span class="na-l">Honoraires à la charge de *</span><?= $radio('hono_charge', ['vendeur' => 'Vendeur', 'acquereur' => 'Acquéreur']) ?></div>
    <div class="field" data-show="acquereur"><label for="na-pct">Honoraires TTC (% du prix hors honoraires) *</label><input class="input" id="na-pct" name="hono_pct" inputmode="decimal" placeholder="Ex. 5"><small class="muted" id="naHors"></small></div>
  </section>

  <section class="panel na-sec" data-show="location">
    <h2><span>4</span> Loyer et honoraires</h2>
    <div class="na-g2">
      <div class="field"><label for="na-loyer">Loyer mensuel charges comprises (€) *</label><input class="input" id="na-loyer" name="loyer" inputmode="decimal"></div>
      <div class="field"><label for="na-chg">Dont charges (€/mois) *</label><input class="input" id="na-chg" name="charges" inputmode="decimal"></div>
    </div>
    <div class="field"><span class="na-l">Charges</span><?= $radio('charges_mode', ['provision' => 'Provision (régularisation)', 'forfait' => 'Forfait'], 'provision') ?></div>
    <div class="na-g2">
      <div class="field"><label for="na-dep">Dépôt de garantie (€) *</label><input class="input" id="na-dep" name="depot" inputmode="decimal"></div>
      <div class="field"><label for="na-hl">Honoraires locataire TTC (€) *</label><input class="input" id="na-hl" name="hono_loc" inputmode="decimal"></div>
      <div class="field"><label for="na-edl">Dont état des lieux (€) *</label><input class="input" id="na-edl" name="hono_edl" inputmode="decimal"></div>
    </div>
    <label class="na-check"><input type="checkbox" name="meuble" value="1"> Location meublée</label>
    <label class="na-check"><input type="checkbox" name="encadre" value="1" checked> Zone soumise à l'encadrement des loyers (Paris, Plaine Commune, Est Ensemble…)</label>
    <div class="na-g2" data-show="encadre">
      <div class="field"><label for="na-lr">Loyer de référence (€/m²) *</label><input class="input" id="na-lr" name="loyer_ref" inputmode="decimal"></div>
      <div class="field"><label for="na-lm">Loyer de référence majoré (€/m²) *</label><input class="input" id="na-lm" name="loyer_maj" inputmode="decimal"></div>
      <div class="field"><label for="na-cl">Complément de loyer (€/mois)</label><input class="input" id="na-cl" name="complement" inputmode="decimal" placeholder="0"></div>
      <div class="field"><label for="na-clw">Justification du complément</label><input class="input" id="na-clw" name="complement_why" maxlength="200"></div>
    </div>
    <p class="muted small" data-show="encadre"><a href="https://www.referenceloyer.drihl.ile-de-france.developpement-durable.gouv.fr/paris/" target="_blank" rel="noopener">Trouver les loyers de référence de l'adresse ↗</a></p>
  </section>

  <section class="panel na-sec">
    <h2><span>5</span> Copropriété</h2>
    <label class="na-check"><input type="checkbox" name="copro" value="1" checked> Le bien est un lot de copropriété</label>
    <div class="na-g2" data-show="copro">
      <div class="field"><label for="na-lots">Nombre de lots de la copropriété *</label><input class="input" id="na-lots" name="lots" inputmode="numeric"></div>
      <div class="field"><label for="na-cc">Charges courantes annuelles du lot (€) *</label><input class="input" id="na-cc" name="charges_copro" inputmode="decimal"></div>
    </div>
    <label class="na-check" data-show="copro"><input type="checkbox" name="procedure" value="1"> Procédure en cours contre le syndicat (plan de sauvegarde, administration provisoire…)</label>
    <div class="field" data-show="procedure"><label for="na-proc">Nature de la procédure</label><input class="input" id="na-proc" name="procedure_txt" maxlength="200"></div>
  </section>

  <section class="panel na-sec">
    <h2><span>6</span> Performance énergétique</h2>
    <div class="field"><span class="na-l">DPE</span><?= $radio('dpe_cas', ['dpe' => 'DPE réalisé', 'vierge' => 'DPE vierge', 'encours' => 'En cours', 'non' => 'Non soumis'], 'dpe') ?></div>
    <div data-show="dpe">
      <div class="field"><span class="na-l">Classe énergie *</span><div class="na-dpe" role="radiogroup"><?php foreach (str_split('ABCDEFG') as $c): ?><label class="c<?= $c ?>"><input type="radio" name="dpe" value="<?= $c ?>"><span><?= $c ?></span></label><?php endforeach; ?></div></div>
      <div class="field"><span class="na-l">Classe climat (GES) *</span><div class="na-dpe ges" role="radiogroup"><?php foreach (str_split('ABCDEFG') as $c): ?><label class="g<?= $c ?>"><input type="radio" name="ges" value="<?= $c ?>"><span><?= $c ?></span></label><?php endforeach; ?></div></div>
      <div class="na-g2">
        <div class="field"><label for="na-conso">Consommation (kWh/m²/an)</label><input class="input" id="na-conso" name="conso" inputmode="decimal"></div>
        <div class="field"><label for="na-emi">Émissions (kg CO₂/m²/an)</label><input class="input" id="na-emi" name="emission" inputmode="decimal"></div>
      </div>
      <div class="na-g3" data-show="logement">
        <div class="field"><label for="na-emin">Dépenses énergie min (€/an) *</label><input class="input" id="na-emin" name="ener_min" inputmode="decimal"></div>
        <div class="field"><label for="na-emax">Dépenses énergie max (€/an) *</label><input class="input" id="na-emax" name="ener_max" inputmode="decimal"></div>
        <div class="field"><label for="na-ean">Année des prix *</label><input class="input" id="na-ean" name="ener_annee" inputmode="numeric" maxlength="4" placeholder="<?= date('Y') - 1 ?>"></div>
      </div>
      <p class="muted small" data-show="logement">Ces trois montants figurent sur le DPE (« Estimation des coûts annuels d'énergie »).</p>
    </div>
  </section>

  <section class="panel na-sec na-prev">
    <h2><span>7</span> Mentions ajoutées à l'annonce</h2>
    <p class="muted small">Générées automatiquement à partir de la fiche ; Géorisques et honoraires inclus.</p>
    <ul id="naPrev"></ul>
  </section>

  <div class="na-bar">
    <p class="form-error" hidden></p>
    <label class="na-check"><input type="checkbox" name="publier" value="1" checked> Publier tout de suite sur le site</label>
    <button class="btn btn-primary" id="naGo">Envoyer l'annonce</button>
  </div>
</form>
<div class="panel na-done" id="naDone" hidden>
  <h2>✅ Annonce envoyée</h2>
  <p id="naDoneTxt"></p>
  <div class="ws-row"><a class="btn btn-primary" id="naView" target="_blank" rel="noopener" href="#">Voir l'annonce ↗</a><a class="btn btn-ghost" id="naEdit" target="_blank" rel="noopener" href="#">Compléter dans WordPress ↗</a><a class="btn-link" href="/site-web/annonces/nouvelle">Nouvelle annonce</a></div>
</div>
<?php endif; ?>
