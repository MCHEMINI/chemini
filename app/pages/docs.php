<?php
declare(strict_types=1);
/*
 * Outils HTML partagés (ex. tableau des honoraires).
 * Le fichier est importé depuis la plateforme et stocké dans la base (hors dépôt, hors web).
 * Ses enregistrements, prévus pour le navigateur, sont redirigés vers le serveur :
 * toute l'équipe voit et modifie les mêmes données.
 */

const DOCS = ['honoraires' => 'Honoraires'];
/** Seules ces personnes peuvent modifier le document ; les autres membres de l'espace le consultent en lecture seule. */
const DOC_EDITORS = ['honoraires' => ['mchemini@gidsas.fr']];

function doc_can_edit(array $u, string $slug): bool {
    return !isset(DOC_EDITORS[$slug]) || in_array(strtolower($u['email']), DOC_EDITORS[$slug], true);
}

/** Données complémentaires du tableau des honoraires : archives, planning ImmOpen (contrôle croisé), honoraires votés d'après les PV d'AG. */
function hono_extra_cfg(): array {
    $arch = array_map(fn($a) => ['id' => (int)$a['id'], 'num' => $a['num'], 'port' => $a['port'], 'imm' => $a['imm'], 'data' => json_decode($a['data'], true) ?: [], 'at' => (int)$a['created_at'], 'by' => $a['by_name']],
        q('SELECT a.*, u.name AS by_name FROM hono_archive a LEFT JOIN users u ON u.id=a.created_by WHERE a.restored_at IS NULL ORDER BY a.created_at DESC')->fetchAll());
    immopen_sync();
    $ag = json_decode((string)q("SELECT v FROM kv WHERE k='ag_planning'")->fetchColumn(), true) ?: [];
    $io = array_map(fn($r) => ['code' => (string)($r['code'] ?? ''), 'nom' => (string)($r['nom'] ?? ''), 'lots' => (int)($r['lots'] ?? 0), 'gest' => trim(preg_replace('/^(M\.|Mme|Melle|Mlle)\s*/u', '', (string)($r['gest'] ?? ''))),
        'last' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($r['last'] ?? '')) ? $r['last'] : null], $ag['rows'] ?? []);
    $pv = [];
    foreach (q('SELECT code, exercice, ag_date, travaux_ttc, gc_ttc, detail, link, pv_date FROM pv_votes ORDER BY ag_date')->fetchAll() as $r) {
        $det = json_decode((string)$r['detail'], true) ?: [];
        // Exercice des honoraires travaux votés (HTV) : AG tenues du 1er juillet au 30 juin (consigne de M. Chemini)
        $y = (int)substr($r['ag_date'], 0, 4); $sy = (int)substr($r['ag_date'], 5, 2) >= 7 ? $y : $y - 1;
        $o = &$pv[$r['code']][$sy . '-' . ($sy + 1)];
        $o['trav'] = round(($o['trav'] ?? 0) + (float)$r['travaux_ttc'], 2);
        $o['date'] = $r['ag_date'];
        $o['ags'][] = ['date' => $r['ag_date'], 'pv' => $r['pv_date'], 'gc' => $r['gc_ttc'] !== null ? (float)$r['gc_ttc'] : null, 'link' => preg_match('#^https://outlook\.(office365|office|live)\.com/#', (string)$r['link']) ? $r['link'] : '',
            'items' => array_map(fn($t) => ['res' => (string)($t['res'] ?? ''), 'objet' => (string)($t['objet'] ?? ''), 'taux' => (string)($t['taux'] ?? ''), 'ht' => (float)($t['ht'] ?? 0), 'ttc' => (float)($t['ttc'] ?? 0)], $det)];
        unset($o);
    }
    return ['archive' => $arch, 'immopen' => $io, 'immopen_date' => $ag['date'] ?? null, 'pv' => $pv];
}

function doc_get(string $slug): ?array {
    return q('SELECT d.*, u.name AS by_name FROM docs d LEFT JOIN users u ON u.id=d.updated_by WHERE slug=?', [$slug])->fetch() ?: null;
}

function doc_space(?array $doc): string { return $doc['space'] ?? 'famille'; }

function page_doc(array $u, string $slug): never {
    $doc = doc_get($slug);
    if (!can_space($u, doc_space($doc))) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Cette section est réservée à un autre espace.'], false); }
    $state = $doc ? q('SELECT s.updated_at, u.name AS by_name FROM doc_state s LEFT JOIN users u ON u.id=s.updated_by WHERE slug=?', [$slug])->fetch() : null;
    render('doc', ['title' => DOCS[$slug], 'wide' => true, 'spaceName' => SPACES[doc_space($doc)], 'nav' => $slug, 'u' => $u, 'slug' => $slug, 'doc' => $doc, 'state' => $state ?: null]);
}

function doc_import(array $u, string $slug): never {
    if (!can_space($u, doc_space(doc_get($slug))) || !doc_can_edit($u, $slug)) { http_response_code(403); exit; }
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) redirect("/$slug?erreur=session");
    $f = $_FILES['file'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 8_000_000) redirect("/$slug?erreur=fichier");
    $html = (string)file_get_contents($f['tmp_name']);
    if (!preg_match('/<html[\s>]/i', $html)) redirect("/$slug?erreur=format");
    $key = preg_match('/STORE_KEY\s*=\s*["\']([^"\']+)["\']/', $html, $m) ? $m[1] : null;
    $title = preg_match('/<title>(.*?)<\/title>/is', $html, $m) ? trim(html_entity_decode(strip_tags($m[1]))) : DOCS[$slug];
    q('INSERT INTO docs(slug,title,html,store_key,updated_at,updated_by) VALUES(?,?,?,?,?,?)
       ON CONFLICT(slug) DO UPDATE SET title=excluded.title, html=excluded.html, store_key=excluded.store_key, updated_at=excluded.updated_at, updated_by=excluded.updated_by',
        [$slug, mb_substr($title, 0, 120), $html, $key, time(), $u['id']]);
    q('DELETE FROM doc_state WHERE slug=?', [$slug]); // nouveau fichier = nouvelles données de départ
    audit((int)$u['id'], "doc_import:$slug");
    redirect("/$slug");
}

/** Sert le fichier dans un cadre, avec le relais d'enregistrement partagé injecté en tête. */
function doc_view(array $u, string $slug): never {
    $doc = doc_get($slug);
    if (!$doc) { http_response_code(404); exit; }
    if (!can_space($u, doc_space($doc))) { http_response_code(403); exit; }
    $state = q('SELECT json FROM doc_state WHERE slug=?', [$slug])->fetchColumn();
    $cfg = json_encode([
        'slug' => $slug, 'key' => $doc['store_key'], 'state' => $state === false ? null : $state, 'csrf' => $_SESSION['csrf'], 'ro' => !doc_can_edit($u, $slug),
        'audit' => $slug === 'honoraires' ? (($a = audit_latest('honoraires')) ? $a['d'] + ['_at' => (int)$a['created_at']] : null) : null,
        'annexes' => $slug === 'honoraires' ? annexes_data() : null,
        'prefs' => user_prefs((int)$u['id']),
    ] + ($slug === 'honoraires' ? hono_extra_cfg() : []) + [
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    $shim = <<<JS
<script>
(function(){
  var C = $cfg;
  function theme(){ var t='light'; try{ t=localStorage.getItem('gid_theme')||'light'; }catch(e){}
    if (t==='auto') document.documentElement.removeAttribute('data-theme'); else document.documentElement.setAttribute('data-theme', t==='dark'?'dark':'light'); }
  function fs(){ var z=1; try{ z=parseFloat(localStorage.getItem('gid_fs'))||1; }catch(e){} document.documentElement.style.zoom = (z>=0.8&&z<=1.5)?z:1; }
  theme(); fs(); window.addEventListener('storage', function(e){ if(e.key==='gid_theme') theme(); if(e.key==='gid_fs') fs(); });
  /* Ouverture directe d'un onglet : /honoraires#opti */
  function goTab(){ var h=(location.hash||'').slice(1); if(!/^[a-z]+$/.test(h)) return; var b=document.querySelector('nav.tabs button[data-tab="'+h+'"]'); if(b) b.click(); }
  window.addEventListener('hashchange', goTab);
  /* Dernier audit mensuel, en tête de l'onglet Optimisation & stratégie (texte uniquement, jamais de HTML) */
  function el(tag, cls, txt){ var x=document.createElement(tag); if(cls) x.className=cls; if(txt!=null) x.textContent=String(txt); return x; }
  function tri(lv){ var s=document.createElementNS('http://www.w3.org/2000/svg','svg'); s.setAttribute('viewBox','0 0 24 24'); s.setAttribute('class','ga-tri '+lv); s.setAttribute('aria-label',lv==='crit'?'Alerte':'Attention'); s.innerHTML='<path d="M12 3 2 21h20L12 3z" fill="currentColor"/><path d="M12 10v5M12 18h.01" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>'; return s; }
  function renderAudit(){
    var A=C.audit, box=document.getElementById('tab-opti'); if(!A||!box||document.getElementById('gidAudit')) return;
    var M=['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    var p=String(A.period||'').split('-'), w=el('div','ga'); w.id='gidAudit';
    var hd=el('div','ga-h'); hd.appendChild(el('div','ga-eye',(A.kind==='hebdo'||p.length>2?'Contrôle hebdomadaire · ':'Audit · ')+(p.length>2?'semaine du '+(+p[2])+' ':'')+(M[(+p[1]||1)-1]||'')+' '+(p[0]||'')));
    var nCrit=(A.findings||[]).filter(function(x){return x.level==='crit';}).length; if(nCrit){ var al=el('div','ga-alert'); al.appendChild(tri('crit')); al.appendChild(document.createTextNode(nCrit+' point'+(nCrit>1?'s':'')+' d’alerte à traiter')); hd.appendChild(al); }
    hd.appendChild(el('h2','ga-t',A.headline)); if(A.summary) hd.appendChild(el('p','ga-s',A.summary)); w.appendChild(hd);
    if(A.kpis&&A.kpis.length){ var k=el('div','ga-k'); A.kpis.forEach(function(x){ var c=el('div','ga-kc'); c.appendChild(el('div','ga-kl',x.label)); c.appendChild(el('div','ga-kv',x.value)); if(x.note) c.appendChild(el('div','ga-kn',x.note)); k.appendChild(c); }); w.appendChild(k); }
    if(A.actions&&A.actions.length){ w.appendChild(el('h3','ga-st','Actions prioritaires du mois')); var ol=el('ol','ga-a'); A.actions.forEach(function(x){ var li=el('li',x.level==='crit'?'lv-crit':''); var t=el('strong'); if(x.level==='crit'||x.level==='warn') t.appendChild(tri(x.level)); t.appendChild(document.createTextNode(x.title)); li.appendChild(t); if(x.detail) li.appendChild(el('div','ga-d',x.detail)); var m=[x.gain?('Gain estimé : '+x.gain):'',x.owner?('Pilote : '+x.owner):''].filter(Boolean).join(' · '); if(m) li.appendChild(el('div','ga-m',m)); ol.appendChild(li); }); w.appendChild(ol); }
    if(A.findings&&A.findings.length){ w.appendChild(el('h3','ga-st','Constats')); var ul=el('ul','ga-f'); A.findings.slice().sort(function(a,b){ var o={crit:0,warn:1,ok:2}; return (o[a.level]||2)-(o[b.level]||2); }).forEach(function(x){ var li=el('li','lv-'+(x.level||'ok')); var t=el('strong'); if(x.level==='crit'||x.level==='warn') t.appendChild(tri(x.level)); t.appendChild(document.createTextNode(x.title)); li.appendChild(t); if(x.detail) li.appendChild(el('div','ga-d',x.detail)); ul.appendChild(li); }); w.appendChild(ul); }
    if(A.gestionnaires&&A.gestionnaires.length){ w.appendChild(el('h3','ga-st','Par gestionnaire')); var g=el('div','ga-g'); A.gestionnaires.forEach(function(x){ var c=el('div','ga-gc'); c.appendChild(el('div','ga-gn',(x.name||x.code)+(x.code&&x.name?' · '+x.code:''))); if(x.constat) c.appendChild(el('p','ga-d',x.constat)); if(x.actions&&x.actions.length){ var u=el('ul'); x.actions.forEach(function(a){ u.appendChild(el('li',null,a)); }); c.appendChild(u); } g.appendChild(c); }); w.appendChild(g); }
    if(A._at) w.appendChild(el('div','ga-m','Contrôle du '+new Date(A._at*1000).toLocaleDateString('fr-FR')+' à '+new Date(A._at*1000).toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})+' · prochain contrôle lundi à 7 h. L’analyse détaillée permanente suit ci-dessous.'));
    var st=document.createElement('style'); st.textContent='.ga{margin:0 0 28px;padding:22px 24px;border:1px solid var(--line,#ddd);border-left:3px solid var(--accent,#8C6A3F);border-radius:10px;background:var(--surface,var(--bg))}.ga-eye{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--accent,#8C6A3F);font-weight:600}.ga-t{margin:6px 0 6px;font-size:1.45em}.ga-s{margin:0;color:var(--text-2)}.ga-k{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:18px 0 6px}.ga-kc{border:1px solid var(--line,#ddd);border-radius:8px;padding:12px 14px}.ga-kl{font-size:.85em;color:var(--text-2)}.ga-kv{font-size:1.35em;font-weight:600;margin-top:4px}.ga-kn{font-size:.85em;color:var(--text-2);margin-top:2px}.ga-st{margin:20px 0 8px;font-size:1.05em}.ga-a,.ga-f{margin:0;padding-left:20px}.ga-a li,.ga-f li{margin:0 0 10px}.ga-f{list-style:none;padding:0}.ga-f li{padding-left:12px;border-left:3px solid #9aa}.ga-f li.lv-crit{border-color:#b4412f;background:rgba(180,65,47,.06)}.ga-f li.lv-crit>strong,.ga-a li.lv-crit>strong{color:#b4412f}.ga-f li{padding:8px 12px;border-radius:0 6px 6px 0}.ga-tri{width:16px;height:16px;vertical-align:-3px;margin-right:7px;flex:none}.ga-tri.crit{color:#c0392b}.ga-tri.warn{color:#d68a1c}.ga-alert{display:inline-flex;align-items:center;margin-top:12px;padding:6px 12px;border-radius:99px;background:rgba(192,57,43,.1);color:#b4412f;font-weight:600;font-size:.92em}.ga-f li.lv-warn{border-color:#c08a2c}.ga-f li.lv-ok{border-color:#4f7a5a}.ga-d{color:var(--text-2);margin-top:2px}.ga-m{font-size:.85em;color:var(--text-2);margin-top:4px}.ga-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}.ga-gc{border:1px solid var(--line,#ddd);border-radius:8px;padding:12px 14px}.ga-gc ul{margin:6px 0 0;padding-left:18px}.ga-gn{font-weight:600}';
    w.appendChild(st); box.insertBefore(w, box.firstChild);
  }
  window.addEventListener('load', function(){ renderAudit(); setTimeout(goTab, 0); });
  window.GID_C = C;
  /* Lecture seule : champs verrouillés, boutons d'édition masqués, aucun enregistrement envoyé */
  if (C.ro) {
    var css=document.createElement('style'); css.textContent='#addImm,#addContr,button.del,#menuPop [data-act=import],#menuPop [data-act=reset],#importFile,#saveState{display:none!important}#immTbl input,#contrTbl input{pointer-events:none;background:transparent!important;border-color:transparent!important}.gid-ro{margin:0 0 12px;padding:9px 14px;border:1px solid var(--line,#ddd);border-radius:8px;font-size:13px;color:var(--text-2)}';
    document.head.appendChild(css);
    var lock=function(){ document.querySelectorAll('#immTbl input,#contrTbl input').forEach(function(i){ i.readOnly=true; i.tabIndex=-1; }); };
    window.addEventListener('DOMContentLoaded', function(){
      lock(); new MutationObserver(lock).observe(document.body,{childList:true,subtree:true});
      var t=document.querySelector('nav.tabs'); if(t){ var b=document.createElement('div'); b.className='gid-ro'; b.textContent='Lecture seule : seul Mehdi Chemini peut modifier ce tableau.'; t.parentNode.insertBefore(b,t); }
    });
  }
  if(!C.key) return;
  var S = Storage.prototype, set = S.setItem, del = S.removeItem, t = null;
  try { if (C.state !== null) set.call(localStorage, C.key, C.state); else del.call(localStorage, C.key); } catch(e) {}
  function send(body){
    fetch('/api/doc/state', {method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/json','X-CSRF':C.csrf}, body: JSON.stringify(body)})
    .then(function(r){ if(!r.ok) throw 0; }).catch(function(){
      var el = document.getElementById('saveState'); if (el) { el.textContent = 'Échec de l’enregistrement partagé'; el.className = 'save-state dirty'; }
    });
  }
  S.setItem = function(k, v){ set.call(this, k, v); if (this === localStorage && k === C.key && !C.ro) { clearTimeout(t); t = setTimeout(function(){ send({slug:C.slug, json:String(v)}); }, 600); } };
  S.removeItem = function(k){ del.call(this, k); if (this === localStorage && k === C.key && !C.ro) send({slug:C.slug, reset:true}); };
})();
</script>
JS;
    $html = $doc['html'];
    if ($slug === 'honoraires') { // honoraires annexes + colonnes au choix : script exécuté après celui du document
        $extra = '<script>' . str_replace('</', '<\\/', (string)file_get_contents(APP_DIR . '/pages/hono_extra.js')) . '</script>';
        $i = strripos($html, '</body>');
        $html = $i !== false ? substr_replace($html, $extra, $i, 0) : $html . $extra;
    }
    $html = str_replace(['dans ce navigateur', 'Effacer toutes vos modifications'], ['pour toute l\'équipe', 'Effacer les modifications de toute l\'équipe'], $html);
    // Exercice de GID : du 1er avril au 31 mars (et non du 1er juillet au 30 juin)
    $html = preg_replace_callback('#Exercice 01/07/(\d{4}) – 30/06/(\d{4})#u', fn($m) => 'Exercice 01/04/' . $m[1] . ' – 31/03/' . $m[2], $html);
    $html = preg_replace_callback('#AG tenues depuis le 01/07/(\d{4})#u', fn($m) => 'AG tenues depuis le 01/04/' . $m[1], $html);
    $html = preg_match('/<head[^>]*>/i', $html) ? preg_replace('/<head[^>]*>/i', '$0' . $shim, $html, 1) : $shim . $html;

    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'");
    echo $html;
    exit;
}
