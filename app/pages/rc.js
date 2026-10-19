/* GID · formulaires du site gidsas.fr (devis, contact, recrutement…) : champs bien visibles, case d'accord encadrée,
   champs obligatoires oubliés en rouge avec un message, bouton toujours actif, message de résultat bien visible.
   Formulaire Recrutement en plus : pièces jointes modernes (glisser-déposer) et transmission de la candidature au tableau de bord Direction. */
(function () {
  'use strict';
  if (window.__gidRc) return; window.__gidRc = true;
  var ENDPOINT = '__BASE__/candidature', MAX = 5 * 1024 * 1024, OK = /\.(pdf|docx?)$/i, T0 = Date.now();
  var FORMS = [].slice.call(document.querySelectorAll('form.wpcf7-form'));
  if (!FORMS.length) return;

  var css = [
    '.gidf-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;width:100%;margin:10px 0 8px}',
    '.gidf-row>div{min-width:0}.gidf-row p{margin:0}',
    '.gidf-lab{display:block;font:600 14px/1.3 Roboto,sans-serif;color:#1D1953;margin:0 0 8px}.gidf-lab em{font-style:normal;font-weight:400;color:#6B7280}',
    '.gidf-drop{position:relative;display:flex;align-items:center;gap:14px;min-height:92px;padding:16px 18px;border:1.5px dashed #B3BBCA;border-radius:10px;background:#F7F7FB;transition:border-color .15s,background .15s,box-shadow .15s;box-sizing:border-box}',
    '.gidf-drop:hover,.gidf-drop.is-over{border-color:#E8540D;background:#FFF5EF}',
    '.gidf-drop:focus-within{box-shadow:0 0 0 3px rgba(232,84,13,.25);border-color:#E8540D}',
    '.gidf-drop input[type=file]{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:1;font-size:0}',
    '.gidf-ico{flex:none;width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#fff;color:#E8540D;box-shadow:0 1px 4px rgba(29,25,83,.12)}',
    '.gidf-txt{display:grid;gap:2px;min-width:0;font:400 14px/1.4 Roboto,sans-serif;color:#212A3A}',
    '.gidf-txt b{font-weight:600;color:#1D1953}form.gidf-on .gidf-txt u{color:#E8540D!important;text-decoration:underline;text-underline-offset:2px;font-weight:600}',
    '.gidf-txt small{font-size:12.5px;color:#6B7280}',
    '.gidf-file{display:none;align-items:center;gap:12px;width:100%;min-width:0}',
    '.gidf-drop.is-filled{border-style:solid;border-color:#1D1953;background:#fff}',
    '.gidf-drop.is-filled .gidf-empty{display:none}.gidf-drop.is-filled .gidf-file{display:flex}.gidf-drop.is-filled input[type=file]{pointer-events:none}',
    '.gidf-empty{display:flex;align-items:center;gap:14px;min-width:0}',
    '.gidf-ext{flex:none;width:40px;height:48px;border-radius:6px;display:grid;place-items:center;font:700 11px Roboto,sans-serif;letter-spacing:.04em;color:#fff;background:#1D1953;position:relative}',
    '.gidf-ext.pdf{background:#E8540D}',
    '.gidf-meta{display:grid;min-width:0;flex:1}.gidf-meta b{font:600 14px/1.35 Roboto,sans-serif;color:#1D1953;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.gidf-meta small{font:400 12.5px Roboto,sans-serif;color:#2E7D4F;display:flex;align-items:center;gap:4px}',
    '.gidf-act{display:flex;gap:6px;flex:none;position:relative;z-index:2}',
    // le thème du site met en forme tous les <button> (orange, majuscules) : on repart de zéro pour ces petits boutons
    'form.gidf-on .gidf-drop button.gidf-btn{all:unset;box-sizing:border-box;border:1px solid #D5D9E2;background:#fff;color:#1D1953;border-radius:8px;height:36px;padding:0 12px;font:500 13px/1 Roboto,sans-serif;text-transform:none;letter-spacing:0;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap}',
    'form.gidf-on .gidf-drop button.gidf-btn:hover{border-color:#1D1953;background:#F7F7FB;color:#1D1953}',
    'form.gidf-on .gidf-drop button.gidf-btn.x{width:36px;padding:0}',
    'form.gidf-on .gidf-drop button.gidf-btn:focus-visible{outline:2px solid #E8540D;outline-offset:2px}',
    'form.gidf-on .gidf-err{display:none;margin:8px 0 0;font:500 13px/1.4 Roboto,sans-serif;color:#C0392B!important}.gidf-on .gidf-err.on{display:block}',
    '.gidf-drop.is-error{border-color:#C0392B;background:#FDF2F1}',
    '.gidf-row .wpcf7-not-valid-tip{margin-top:6px;font-size:13px}',
    'form.gidf-on .wpcf7-submit{min-height:48px;padding:12px 28px!important;border-radius:8px!important;letter-spacing:.02em;cursor:pointer}',
    'form.gidf-on .wpcf7-submit:disabled{opacity:.55;cursor:not-allowed}',
    'form.gidf-on .gidf-submit{display:flex;align-items:center;gap:14px;flex-wrap:wrap}',
    'form.gidf-on .gidf-submit .wpcf7-spinner{margin:0}',
    // message de résultat bien visible (le thème l'affiche en tout petit) et état « Envoi en cours »
    'form.gidf-on .wpcf7-response-output{display:none!important}',
    '.gidf-status{display:flex;gap:12px;align-items:flex-start;margin:18px 0 0;padding:14px 16px;border-radius:10px;font:500 15px/1.45 Roboto,sans-serif;border:1px solid transparent}',
    '.gidf-status svg{flex:none;margin-top:1px}',
    '.gidf-status.ok{background:#EAF6EE;border-color:#B9E0C5;color:#1F6B3A}.gidf-status.err{background:#FDF2F1;border-color:#F2C4BF;color:#A93226}.gidf-status.wait{background:#F3F3F9;border-color:#DCDCEA;color:#1D1953}',
    '.gidf-spin{width:18px;height:18px;border:2.5px solid rgba(29,25,83,.2);border-top-color:#1D1953;border-radius:50%;animation:gidfspin .8s linear infinite;flex:none}',
    '@keyframes gidfspin{to{transform:rotate(360deg)}}',
    // champs oubliés en rouge
    'form.gidf-on .gidf-bad,form.gidf-on .wpcf7-not-valid{border-color:#C0392B!important;background-color:#FDF2F1!important;box-shadow:0 0 0 3px rgba(192,57,43,.14)!important}',
    'form.gidf-on .wpcf7-not-valid-tip,form.gidf-on .gidf-tip{display:block;color:#C0392B!important;font:500 13px/1.4 Roboto,sans-serif!important;margin:5px 0 0}',
    // case d'accord bien visible
    'form.gidf-on label.gidf-consent{display:flex!important;gap:12px;align-items:flex-start;margin:4px 0 6px;padding:14px 16px;border:1.5px solid #C9CEDA;border-radius:10px;background:#F7F7FB;cursor:pointer;font:400 14px/1.5 Roboto,sans-serif!important;color:#212A3A;transition:border-color .15s,background .15s}',
    'form.gidf-on label.gidf-consent:hover{border-color:#1D1953}',
    'form.gidf-on label.gidf-consent input[type=checkbox]{flex:none;width:22px;height:22px;margin:1px 0 0;accent-color:#E8540D;cursor:pointer}',
    'form.gidf-on label.gidf-consent:has(input:checked){border-color:#E8540D;background:#FFF5EF}',
    'form.gidf-on label.gidf-consent.is-bad{border-color:#C0392B;background:#FDF2F1;box-shadow:0 0 0 3px rgba(192,57,43,.14)}',
    'form.gidf-on label.gidf-consent .wpcf7-list-item-label{font-weight:500}',
    'form.gidf-on .wpcf7-acceptance .wpcf7-list-item{margin:0;display:block}',
    // champs bien délimités (le thème les dessine presque invisibles)
    'form.gidf-on :where(input.wpcf7-form-control:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=file]),select.wpcf7-form-control,textarea.wpcf7-form-control){border:1.5px solid #C9CEDA!important;border-radius:8px!important;background-color:#fff!important;color:#1D1953;transition:border-color .15s,box-shadow .15s}',
    'form.gidf-on :where(input.wpcf7-form-control:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=file]),select.wpcf7-form-control,textarea.wpcf7-form-control):hover{border-color:#8F96A8!important}',
    'form.gidf-on :where(input.wpcf7-form-control:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=file]),select.wpcf7-form-control,textarea.wpcf7-form-control):focus{border-color:#E8540D!important;box-shadow:0 0 0 3px rgba(232,84,13,.18)!important;outline:none}',
    'form.gidf-on ::placeholder{color:#6B7280;opacity:1}',
    'form.gidf-on .gidf-bad.gidf-bad,form.gidf-on .wpcf7-not-valid.wpcf7-not-valid{border-color:#C0392B!important;background-color:#FDF2F1!important;box-shadow:0 0 0 3px rgba(192,57,43,.14)!important}',
    // structure commune à tous les formulaires : grille 2 colonnes, libellé visible au-dessus de chaque champ, hauteurs identiques
    'form.gidf-lay .cf-container{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px;margin:0!important}',
    'form.gidf-lay .cf-container>*{width:auto!important;max-width:none!important;flex:none!important;padding:0!important;margin:0!important;min-width:0}',
    'form.gidf-lay .cf-container>.cf-col-12,form.gidf-lay .cf-container>.gidf-row{grid-column:1/-1}',
    'form.gidf-lay .cf-container p{margin:0!important;padding:0!important}',
    'form.gidf-lay label.gid-field{display:block!important;margin:0}',
    '.gidf-flab,.gidf-lab{display:block;font:600 14px/1.3 Roboto,sans-serif!important;color:#1D1953!important;margin:0 0 7px!important;text-transform:none;letter-spacing:0}',
    '.gidf-flab em,.gidf-lab em{font-style:normal;font-weight:400;color:#6B7280}.gidf-flab em.req,.gidf-lab em.req{color:#E8540D;font-weight:700}',
    'form.gidf-lay :where(input.wpcf7-form-control:not([type=checkbox]):not([type=radio]):not([type=submit]):not([type=file]),select.wpcf7-form-control){height:48px!important;min-height:48px;padding:0 14px!important;font:400 15px/1.2 Roboto,sans-serif!important;box-sizing:border-box!important;width:100%!important;margin:0!important}',
    'form.gidf-lay :where(textarea.wpcf7-form-control){height:150px!important;min-height:110px;padding:12px 14px!important;font:400 15px/1.5 Roboto,sans-serif!important;box-sizing:border-box!important;width:100%!important;margin:0!important;resize:vertical}',
    // menu déroulant : même hauteur que les autres champs, vraie flèche
    'form.gidf-lay select.wpcf7-form-control{-webkit-appearance:none!important;appearance:none!important;padding-right:42px!important;cursor:pointer;background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%231D1953%27 stroke-width=%272.4%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27m6 9 6 6 6-6%27/%3E%3C/svg%3E")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;background-size:16px!important}',
    'form.gidf-lay select.wpcf7-form-control.gidf-empty-sel{color:#6B7280!important}',
    'form.gidf-lay .gidf-row{margin:0!important;gap:20px}',
    '@media (max-width:767px){form.gidf-lay .cf-container{grid-template-columns:minmax(0,1fr);gap:16px}}',
    '@media (max-width:767px){.gidf-row{grid-template-columns:minmax(0,1fr);gap:16px}form.gidf-on .wpcf7-submit{width:100%}.gidf-drop{min-height:84px}}'
  ].join('');
  var st = document.createElement('style'); st.id = 'gidf-css'; st.textContent = css; document.head.appendChild(st);

  var ICON_UP = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>';
  var ICON_X = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  var ICON_SW = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.3-4.9L4 8"/><path d="M4 3v5h5"/><path d="M4 13a8 8 0 0 0 14.3 4.9L20 16"/><path d="M20 21v-5h-5"/></svg>';
  var ICON_OK = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>';
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function size(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko'; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  function enhance(input, title, optional) {
    var wrap = input.closest('.wpcf7-form-control-wrap');
    if (!wrap || input.__gidf) return null; input.__gidf = true;
    // libellé du site qui entoure le champ (« <label>Votre CV* … [champ]</label> ») : on sort le champ et on retire ce libellé
    var outer = wrap.parentNode && wrap.parentNode.closest ? wrap.parentNode.closest('label') : null;
    if (outer && outer.contains(wrap)) { outer.parentNode.insertBefore(wrap, outer); outer.remove(); }
    var col = wrap.closest('[class*="cf-col"]'), p = wrap.parentNode;
    // libellé d'origine (« Votre CV* (PDF, Word – 5 Mo maximum) ») remplacé par un libellé net, au-dessus de la zone
    if (p) [].slice.call(p.querySelectorAll('label')).forEach(function (l) { if (!l.contains(input)) l.remove(); });
    [].slice.call((p || wrap).childNodes).forEach(function (n) { if (n.nodeType === 3 && !n.textContent.trim()) n.remove(); if (n.tagName === 'BR') n.remove(); });
    var id = 'gidf-' + input.name; input.id = input.id || id;
    var lab = el('label', 'gidf-lab', esc(title) + (optional ? ' <em>(facultatif)</em>' : ' <em class="req">*</em>')); lab.htmlFor = input.id;
    var drop = el('div', 'gidf-drop');
    var empty = el('div', 'gidf-empty', '<span class="gidf-ico">' + ICON_UP + '</span><span class="gidf-txt"><span>Glissez votre fichier ici ou <u>parcourez</u></span><small>PDF ou Word (.doc, .docx) · 5 Mo maximum</small></span>');
    var file = el('div', 'gidf-file', '<span class="gidf-ext"></span><span class="gidf-meta"><b></b><small>' + ICON_OK + '<span></span></small></span><span class="gidf-act"><button type="button" class="gidf-btn x" data-r aria-label="Remplacer le fichier" title="Remplacer le fichier">' + ICON_SW + '</button><button type="button" class="gidf-btn x" data-x aria-label="Retirer le fichier" title="Retirer le fichier">' + ICON_X + '</button></span>');
    var err = el('p', 'gidf-err'); err.setAttribute('role', 'alert'); err.id = id + '-err';
    input.setAttribute('aria-describedby', err.id);
    wrap.insertBefore(drop, input); drop.appendChild(input); drop.appendChild(empty); drop.appendChild(file);
    wrap.parentNode.insertBefore(lab, wrap); wrap.appendChild(err);
    function show() {
      var f = input.files && input.files[0];
      drop.classList.toggle('is-filled', !!f); drop.classList.remove('is-error'); err.classList.remove('on'); err.textContent = '';
      if (!f) return;
      var ext = (f.name.split('.').pop() || '').toUpperCase();
      var x = file.querySelector('.gidf-ext'); x.textContent = ext; x.className = 'gidf-ext ' + ext.toLowerCase();
      file.querySelector('b').textContent = f.name; file.querySelector('small span').textContent = size(f.size) + ' · prêt';
    }
    function fail(msg) { input.value = ''; show(); drop.classList.add('is-error'); err.textContent = msg; err.classList.add('on'); }
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (f && !OK.test(f.name)) return fail('Ce format n’est pas accepté : choisissez un fichier PDF ou Word (.doc, .docx).');
      if (f && f.size > MAX) return fail('Ce fichier fait ' + size(f.size) + ' : la taille maximale est de 5 Mo.');
      if (f && !f.size) return fail('Ce fichier est vide.');
      show();
    });
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('is-over'); }); });
    file.querySelector('[data-r]').addEventListener('click', function () { input.click(); });
    file.querySelector('[data-x]').addEventListener('click', function () { input.value = ''; input.dispatchEvent(new Event('change', { bubbles: true })); input.focus(); });
    input.form && input.form.addEventListener('reset', function () { setTimeout(show, 0); });
    return col;
  }

  FORMS.forEach(function (f) { try { setup(f); } catch (e) {} });

  var HINTS = { email: 'exemple@domaine.fr', tel: '06 12 34 56 78' };
  /* Structure commune : libellé visible au-dessus de chaque champ (repris du libellé caché pour les lecteurs d'écran ou du texte d'exemple) */
  function structure(form) {
    form.classList.add('gidf-lay');
    [].slice.call(form.querySelectorAll('.wpcf7-form-control-wrap > input, .wpcf7-form-control-wrap > select, .wpcf7-form-control-wrap > textarea')).forEach(function (elm) {
      if (elm.__gidl || /^(hidden|file|checkbox|radio|submit)$/.test(elm.type)) return; elm.__gidl = true;
      var lab = elm.closest('label'), sr = lab && lab.querySelector('.screen-reader-text');
      var first = elm.tagName === 'SELECT' && elm.options[0] && !elm.options[0].value ? elm.options[0] : null;
      var raw = (sr && sr.textContent) || elm.getAttribute('placeholder') || (first && first.text) || elm.name;
      var opt = /\(facultatif\)/i.test(raw), req = elm.getAttribute('aria-required') === 'true';
      var text = raw.replace(/\*/g, '').replace(/\s*\(facultatif\)/i, '').replace(/^[—–-]+|[—–-]+$/g, '').trim();
      if (/veuillez choisir/i.test(text)) text = 'Votre choix';
      elm.dataset.gidLabel = text;
      var span = el('span', 'gidf-flab', esc(text) + (req ? ' <em class="req" aria-hidden="true">*</em>' : opt ? ' <em>(facultatif)</em>' : ''));
      if (sr) sr.replaceWith(span);
      else { var wrap = elm.closest('.wpcf7-form-control-wrap'); elm.id = elm.id || 'gidf-' + elm.name + '-' + Math.random().toString(36).slice(2, 6); var l = el('label', 'gidf-flab', span.innerHTML); l.htmlFor = elm.id; wrap.parentNode.insertBefore(l, wrap); }
      // le texte d'exemple ne répète plus le libellé
      if (elm.tagName === 'SELECT') { if (first) { first.text = 'Choisissez…'; var upd = function () { elm.classList.toggle('gidf-empty-sel', !elm.value); }; elm.addEventListener('change', upd); upd(); } }
      else if (elm.tagName === 'TEXTAREA') { if ((elm.getAttribute('placeholder') || '').replace(/\*|\(facultatif\)/gi, '').trim() === text) elm.setAttribute('placeholder', 'Votre message…'); }
      else elm.setAttribute('placeholder', HINTS[elm.type] || (/code postal/i.test(text) ? '75009' : ''));
    });
  }

  function setup(form) {
  if (form.__gidf) return; form.__gidf = true; form.classList.add('gidf-on');
  try { structure(form); } catch (e) {}
  var REC = !!form.querySelector('input[type=file][name="your-cv"]'); // formulaire Recrutement
  var NOUN = REC ? 'candidature' : 'demande';
  var FID = (form.querySelector('input[name="_wpcf7"]') || {}).value || 'x';
  var cv = form.querySelector('input[type=file][name="your-cv"]'), lt = form.querySelector('input[type=file][name="your-lettre"]');
  var c1 = cv ? enhance(cv, 'Votre CV', false) : null, c2 = lt ? enhance(lt, 'Lettre de motivation', true) : null;
  // les deux zones côte à côte, alignées (l'une sous l'autre sur téléphone)
  if (c1 && c2 && c1.parentNode === c2.parentNode) {
    var row = el('div', 'gidf-row ' + c1.className.replace(/\bcf-col-\d+\b/, '').trim());
    c1.parentNode.insertBefore(row, c1); row.appendChild(c1); row.appendChild(c2);
  }
  var sb = form.querySelector('.wpcf7-submit'); if (sb && sb.parentNode) sb.parentNode.classList.add('gidf-submit');

  /* Message de résultat, visible, sous le bouton */
  var stat = el('div', 'gidf-status'); stat.hidden = true; stat.setAttribute('role', 'status'); stat.setAttribute('aria-live', 'polite');
  var out = form.querySelector('.wpcf7-response-output'); (out ? out.parentNode : form).insertBefore(stat, out || null);
  var ICON_DONE = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.7 2.7L16 10"/></svg>';
  var ICON_WARN = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5h.01"/></svg>';
  function say(kind, html) { stat.hidden = false; stat.className = 'gidf-status ' + kind; stat.innerHTML = (kind === 'wait' ? '<span class="gidf-spin" aria-hidden="true"></span>' : kind === 'ok' ? ICON_DONE : ICON_WARN) + '<span>' + html + '</span>'; }
  var sbLabel = sb ? sb.value : '';
  function busy(on) { if (!sb) return; sending = on; sb.value = on ? 'Envoi en cours…' : sbLabel; }

  /* Suivi anonyme du résultat (sans aucune donnée personnelle), pour diagnostiquer un envoi qui n'arrive pas */
  function ping(s) { try { var p = new FormData(); p.append('_gid_ping', 'f' + FID + ':' + s); fetch(ENDPOINT, { method: 'POST', body: p, mode: 'cors', credentials: 'omit', keepalive: true }).catch(function () {}); } catch (e) {} }

  /* Transmission au tableau de bord Direction : copie des données au moment de l'envoi, transmise une fois la candidature acceptée par le site
     (ou si le site l'a jugée indésirable ou n'a pas pu envoyer son e-mail : elle arrive alors avec une mention, pour ne perdre aucun candidat). */
  var snap = null, box = form.closest('.wpcf7') || form;
  /* Validation dans la page : champs obligatoires, e-mail, CV et case d'accord ; tout oubli passe en rouge avec un message */
  var consent = form.querySelector('.wpcf7-acceptance input[type=checkbox]') || form.querySelector('input[type=checkbox][name="your-consent"]'), consentLab = consent && consent.closest('label');
  if (consentLab) { consentLab.classList.add('gidf-consent'); var ws = consentLab.closest('small'); if (ws) ws.style.fontSize = 'inherit'; }
  // le bouton reste actif : sinon, case non cochée, rien ne se passe et on ne sait pas pourquoi
  if (sb) { sb.disabled = false; new MutationObserver(function () { if (sb.disabled && !sending) sb.disabled = false; }).observe(sb, { attributes: true, attributeFilter: ['disabled'] }); }
  var sending = false;
  var NAMES = { 'your-name': 'votre nom', 'your-firstname': 'votre prénom', 'your-email': 'votre e-mail', 'your-phone': 'votre téléphone', 'your-poste': 'le poste souhaité' };
  function tip(elm, msg) {
    var w = elm.closest('.wpcf7-form-control-wrap') || elm.parentNode, t = w.querySelector('.gidf-tip');
    if (!msg) { elm.classList.remove('gidf-bad'); elm.removeAttribute('aria-invalid'); if (t) t.remove(); return; }
    elm.classList.add('gidf-bad'); elm.setAttribute('aria-invalid', 'true');
    if (w.querySelector('.wpcf7-not-valid-tip')) return;
    if (!t) { t = el('span', 'gidf-tip'); t.setAttribute('role', 'alert'); w.appendChild(t); }
    t.textContent = msg;
  }
  function label(elm) {
    if (elm.dataset && elm.dataset.gidLabel) return elm.dataset.gidLabel;
    var t = elm.getAttribute('placeholder') || (elm.tagName === 'SELECT' && elm.options[0] ? elm.options[0].text : '') || elm.getAttribute('aria-label') || '';
    if (!t) { var l = elm.id && form.querySelector('label[for="' + elm.id + '"]'); t = l ? l.textContent : 'ce champ'; }
    return t.replace(/\*|\(.*?\)/g, '').trim() || 'ce champ';
  }
  function checkField(elm) {
    var v = (elm.value || '').trim();
    if (!v) return NAMES[elm.name] ? 'Merci d’indiquer ' + NAMES[elm.name] + '.' : 'Merci de renseigner « ' + label(elm) + ' ».';
    if ((elm.type === 'email' || elm.name === 'your-email') && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return 'Cette adresse e-mail ne semble pas valide.';
    if ((elm.type === 'tel' || elm.name === 'your-phone') && v.replace(/\D/g, '').length < 9) return 'Ce numéro de téléphone semble incomplet.';
    if (/code postal/i.test(label(elm)) && !/^\d{5}$/.test(v.replace(/\s/g, ''))) return 'Le code postal doit comporter 5 chiffres.';
    return '';
  }
  var req = [].slice.call(form.querySelectorAll('input[aria-required="true"]:not([type=file]):not([type=checkbox]),select[aria-required="true"],textarea[aria-required="true"]'));
  req.forEach(function (elm) { ['input', 'change', 'blur'].forEach(function (t) { elm.addEventListener(t, function () { if (elm.classList.contains('gidf-bad') || (t === 'blur' && elm.value)) tip(elm, checkField(elm)); }); }); });
  if (consent) consent.addEventListener('change', function () { if (consent.checked) { consentLab.classList.remove('is-bad'); var t = consentLab.parentNode.querySelector('.gidf-tip'); if (t) t.remove(); } });
  function validate() {
    var bad = [];
    req.forEach(function (elm) { var m = checkField(elm); tip(elm, m); if (m) bad.push(elm); });
    if (cv && !(cv.files && cv.files[0])) {
      var d = cv.closest('.gidf-drop'), er = d && d.parentNode.querySelector('.gidf-err');
      if (d) d.classList.add('is-error'); if (er) { er.textContent = 'Merci de joindre votre CV (PDF ou Word).'; er.classList.add('on'); }
      bad.push(cv);
    }
    if (consent && !consent.checked) {
      consentLab.classList.add('is-bad');
      var w = consentLab.parentNode, t = w.querySelector('.gidf-tip');
      if (!t) { t = el('span', 'gidf-tip'); t.setAttribute('role', 'alert'); consentLab.insertAdjacentElement('afterend', t); }
      t.textContent = 'Merci de cocher cette case pour pouvoir envoyer votre ' + NOUN + '.';
      bad.push(consent);
    }
    return bad;
  }
  form.addEventListener('submit', function (e) {
    var bad = validate();
    if (!bad.length) return;
    e.preventDefault(); e.stopImmediatePropagation();
    var n = bad.length;
    say('err', n === 1 && bad[0] === consent ? 'Il reste à cocher la case d’accord (en rouge) pour envoyer votre ' + NOUN + '.' : 'Il manque ' + n + ' élément' + (n > 1 ? 's' : '') + ' (en rouge) : complétez-' + (n > 1 ? 'les' : 'le') + ' puis renvoyez votre ' + NOUN + '.');
    var first = bad[0], target = first === cv ? first.closest('.gidf-drop') : first === consent ? consentLab : first;
    try { target.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (x) {}
    setTimeout(function () { try { first.focus({ preventScroll: true }); } catch (x) {} }, 350);
  }, true);

  var started = false, guard = null;
  form.addEventListener('submit', function () {
    try { snap = new FormData(form); } catch (e) { snap = null; }
    started = false; busy(true); say('wait', 'Envoi de votre ' + NOUN + ' en cours… Merci de patienter quelques secondes.');
    // champs incomplets repérés dans la page même : le site n'envoie rien, on rend la main tout de suite
    clearTimeout(guard); guard = setTimeout(function () { if (!started) { busy(false); snap = null; say('err', 'Certains champs sont à compléter ou à corriger (indiqués en rouge). Vérifiez-les puis renvoyez votre ' + NOUN + '.'); } }, 1500);
  }, true);
  box.addEventListener('wpcf7beforesubmit', function () { started = true; });
  function relay(status) {
    if (!REC || !snap) return; var src = snap; snap = null;
    var fd = new FormData();
    ['your-name', 'your-firstname', 'your-email', 'your-phone', 'your-poste', 'your-message', 'your-consent'].forEach(function (k) { var v = src.get(k); if (v != null) fd.append(k, v); });
    ['your-cv', 'your-lettre'].forEach(function (k) { var f = src.get(k); if (f && f.name && f.size) fd.append(k, f, f.name); });
    fd.append('_gid_hp', ''); fd.append('_gid_t', String(Date.now() - T0)); fd.append('_gid_page', location.pathname); fd.append('_gid_status', status);
    var tries = 0;
    (function go() {
      fetch(ENDPOINT, { method: 'POST', body: fd, mode: 'cors', credentials: 'omit' })
        .then(function (r) { if (r.ok) return; if (r.status >= 500 && ++tries < 3) return setTimeout(go, 1500 * tries); ping('relay-' + r.status); })
        .catch(function () { if (++tries < 3) setTimeout(go, 1500 * tries); else ping('relay-reseau'); });
    })();
  }
  var MSG = !REC ? {
    mail_sent: ['ok', '<b>Merci, votre demande a bien été envoyée.</b> Notre équipe vous répondra dans les meilleurs délais.'],
    mail_failed: ['err', 'Votre demande n’a pas pu être envoyée pour le moment. Réessayez dans quelques minutes, ou écrivez-nous à contact@gidsas.fr ou appelez le 01 42 66 20 88.'],
    validation_failed: ['err', 'Certains champs sont à compléter ou à corriger (indiqués en rouge). Vérifiez-les puis renvoyez votre demande.'],
    acceptance_missing: ['err', 'Merci de cocher la case d’accord avant d’envoyer.'],
    spam: ['err', 'Votre envoi n’a pas pu être vérifié par notre protection anti-robots. Réessayez, ou écrivez-nous à contact@gidsas.fr ou appelez le 01 42 66 20 88.']
  } : {
    mail_sent: ['ok', '<b>Merci, votre candidature a bien été envoyée.</b> Vous allez recevoir un accusé de réception par e-mail. Nous l’étudierons avec attention.'],
    mail_failed: ['ok', '<b>Merci, votre candidature a bien été reçue.</b> L’accusé de réception par e-mail n’a pas pu partir, mais votre dossier nous est bien parvenu.'],
    validation_failed: ['err', 'Certains champs sont à compléter ou à corriger (indiqués en rouge). Vérifiez-les puis renvoyez votre candidature.'],
    acceptance_missing: ['err', 'Merci de cocher la case d’accord sur l’utilisation de vos informations avant d’envoyer.'],
    spam: ['err', 'Votre envoi n’a pas pu être vérifié par notre protection anti-robots. Nous l’avons tout de même transmis à notre équipe ; si vous n’avez pas de nouvelles sous quelques jours, écrivez-nous à contact@gidsas.fr ou appelez le 01 42 66 20 88.']
  };
  box.addEventListener('wpcf7submit', function (e) {
    busy(false);
    var st = (e.detail && (e.detail.status || (e.detail.apiResponse && e.detail.apiResponse.status))) || 'inconnu';
    ping('site-' + st);
    if (st === 'mail_sent' || st === 'mail_failed' || st === 'spam') relay(st); else snap = null;
    var m = MSG[st] || ['err', (e.detail && e.detail.apiResponse && e.detail.apiResponse.message ? String(e.detail.apiResponse.message).replace(/[<>&]/g, '') : 'L’envoi n’a pas abouti.') + ' Vous pouvez aussi écrire à contact@gidsas.fr.'];
    say(m[0], m[1]);
    if (m[0] === 'ok' || st === 'spam') try { stat.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (x) {}
  });
  // le site ne répond pas (coupure réseau, serveur) : le bouton ne reste pas bloqué
  box.addEventListener('wpcf7aborted', function () { busy(false); ping('site-aborted'); say('err', 'L’envoi n’a pas abouti (connexion interrompue). Vérifiez votre connexion puis réessayez, ou écrivez à contact@gidsas.fr.'); });
  }
})();
