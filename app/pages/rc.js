/* GID · page Recrutement de gidsas.fr : pièces jointes modernes (glisser-déposer, aperçu du fichier, contrôle du type et de la taille)
   et transmission de la candidature au tableau de bord Direction une fois qu'elle a été acceptée par le formulaire du site. */
(function () {
  'use strict';
  if (window.__gidRc) return; window.__gidRc = true;
  var ENDPOINT = '__BASE__/candidature', MAX = 5 * 1024 * 1024, OK = /\.(pdf|docx?)$/i, T0 = Date.now();
  var form = (function () { var i = document.querySelector('form.wpcf7-form input[type=file][name="your-cv"]'); return i && i.form; })();
  if (!form) return;

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
    '@media (max-width:767px){.gidf-row{grid-template-columns:minmax(0,1fr);gap:16px}form.gidf-on .wpcf7-submit{width:100%}.gidf-drop{min-height:84px}}'
  ].join('');
  var st = document.createElement('style'); st.id = 'gidf-css'; st.textContent = css; document.head.appendChild(st);
  form.classList.add('gidf-on');

  var ICON_UP = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>';
  var ICON_X = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  var ICON_SW = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.3-4.9L4 8"/><path d="M4 3v5h5"/><path d="M4 13a8 8 0 0 0 14.3 4.9L20 16"/><path d="M20 21v-5h-5"/></svg>';
  var ICON_OK = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>';
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) e.className = cls; if (html != null) e.innerHTML = html; return e; }
  function size(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' Mo' : Math.max(1, Math.round(b / 1024)) + ' Ko'; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  function enhance(input, title, optional) {
    var wrap = input.closest('.wpcf7-form-control-wrap'), col = wrap && wrap.closest('[class*="cf-col"]'), p = wrap && wrap.parentNode;
    if (!wrap || input.__gidf) return null; input.__gidf = true;
    // libellé d'origine (« Votre CV* (PDF, Word – 5 Mo maximum) ») remplacé par un libellé net, au-dessus de la zone
    if (p) [].slice.call(p.querySelectorAll('label')).forEach(function (l) { if (!l.contains(input)) l.remove(); });
    [].slice.call((p || wrap).childNodes).forEach(function (n) { if (n.nodeType === 3 && !n.textContent.trim()) n.remove(); if (n.tagName === 'BR') n.remove(); });
    var id = 'gidf-' + input.name; input.id = input.id || id;
    var lab = el('label', 'gidf-lab', esc(title) + (optional ? ' <em>(facultatif)</em>' : ' <em>*</em>')); lab.htmlFor = input.id;
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
    form.addEventListener('reset', function () { setTimeout(show, 0); });
    return col;
  }

  var cv = form.querySelector('input[type=file][name="your-cv"]'), lt = form.querySelector('input[type=file][name="your-lettre"]');
  var c1 = enhance(cv, 'Votre CV', false), c2 = lt ? enhance(lt, 'Lettre de motivation', true) : null;
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
  function busy(on) { if (!sb) return; sb.value = on ? 'Envoi en cours…' : sbLabel; }

  /* Suivi anonyme du résultat (sans aucune donnée personnelle), pour diagnostiquer un envoi qui n'arrive pas */
  function ping(s) { try { var p = new FormData(); p.append('_gid_ping', s); fetch(ENDPOINT, { method: 'POST', body: p, mode: 'cors', credentials: 'omit', keepalive: true }).catch(function () {}); } catch (e) {} }

  /* Transmission au tableau de bord Direction : copie des données au moment de l'envoi, transmise une fois la candidature acceptée par le site
     (ou si le site l'a jugée indésirable ou n'a pas pu envoyer son e-mail : elle arrive alors avec une mention, pour ne perdre aucun candidat). */
  var snap = null, box = form.closest('.wpcf7') || form;
  var started = false, guard = null;
  form.addEventListener('submit', function () {
    try { snap = new FormData(form); } catch (e) { snap = null; }
    started = false; busy(true); say('wait', 'Envoi de votre candidature en cours… Merci de patienter quelques secondes.');
    // champs incomplets repérés dans la page même : le site n'envoie rien, on rend la main tout de suite
    clearTimeout(guard); guard = setTimeout(function () { if (!started) { busy(false); snap = null; say('err', 'Certains champs sont à compléter ou à corriger (indiqués en rouge). Vérifiez-les puis renvoyez votre candidature.'); } }, 1500);
  }, true);
  box.addEventListener('wpcf7beforesubmit', function () { started = true; });
  function relay(status) {
    if (!snap) return; var src = snap; snap = null;
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
  var MSG = {
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
})();
