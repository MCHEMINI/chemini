<?php
declare(strict_types=1);
/*
 * Outils HTML partagés (ex. tableau des honoraires).
 * Le fichier est importé depuis la plateforme et stocké dans la base (hors dépôt, hors web).
 * Ses enregistrements, prévus pour le navigateur, sont redirigés vers le serveur :
 * toute l'équipe voit et modifie les mêmes données.
 */

const DOCS = ['honoraires' => 'Honoraires'];

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
    if (!can_space($u, doc_space(doc_get($slug)))) { http_response_code(403); exit; }
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
        'slug' => $slug, 'key' => $doc['store_key'], 'state' => $state === false ? null : $state, 'csrf' => $_SESSION['csrf'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    $shim = <<<JS
<script>
(function(){
  var C = $cfg;
  function theme(){ var t='light'; try{ t=localStorage.getItem('gid_theme')||'light'; }catch(e){}
    if (t==='auto') document.documentElement.removeAttribute('data-theme'); else document.documentElement.setAttribute('data-theme', t==='dark'?'dark':'light'); }
  function fs(){ var z=1; try{ z=parseFloat(localStorage.getItem('gid_fs'))||1; }catch(e){} document.documentElement.style.zoom = (z>=0.8&&z<=1.5)?z:1; }
  theme(); fs(); window.addEventListener('storage', function(e){ if(e.key==='gid_theme') theme(); if(e.key==='gid_fs') fs(); });
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
  S.setItem = function(k, v){ set.call(this, k, v); if (this === localStorage && k === C.key) { clearTimeout(t); t = setTimeout(function(){ send({slug:C.slug, json:String(v)}); }, 600); } };
  S.removeItem = function(k){ del.call(this, k); if (this === localStorage && k === C.key) send({slug:C.slug, reset:true}); };
})();
</script>
JS;
    $html = $doc['html'];
    $html = str_replace(['dans ce navigateur', 'Effacer toutes vos modifications'], ['pour toute l\'équipe', 'Effacer les modifications de toute l\'équipe'], $html);
    $html = preg_match('/<head[^>]*>/i', $html) ? preg_replace('/<head[^>]*>/i', '$0' . $shim, $html, 1) : $shim . $html;

    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'");
    echo $html;
    exit;
}
