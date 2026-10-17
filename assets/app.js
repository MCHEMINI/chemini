(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  let csrf = $('meta[name=csrf]')?.content || '';

  async function api(path, data = {}) {
    const res = await fetch('/api/' + path, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf },
      body: JSON.stringify(data),
    });
    const fresh = res.headers.get('X-CSRF-Token'); if (fresh) csrf = fresh; // jeton renouvelé après connexion
    let json = {};
    try { json = await res.json(); } catch (_) { /* réponse vide */ }
    if (!res.ok || json.error) throw new Error(json.error || 'Erreur réseau. Réessayez.');
    return json;
  }
  window.api = api;

  function showError(scope, msg) {
    const el = $('.form-error', scope);
    if (el) { el.textContent = msg; el.hidden = !msg; }
  }

  function toast(msg) {
    const t = $('#toast'); if (!t) return;
    t.textContent = msg; t.hidden = false;
    clearTimeout(t._h); t._h = setTimeout(() => (t.hidden = true), 2600);
  }

  /* ---------- Thème : clair / sombre / auto ---------- */
  const markTheme = () => { const t = document.documentElement.dataset.theme || 'light'; $$('[data-theme-set]').forEach(b => b.setAttribute('aria-pressed', b.dataset.themeSet === t)); };
  $$('[data-theme-set]').forEach(b => b.addEventListener('click', () => {
    document.documentElement.dataset.theme = b.dataset.themeSet;
    try { localStorage.setItem('gid_theme', b.dataset.themeSet); } catch (_) {}
    markTheme();
  }));
  markTheme();

  /* ---------- Taille du texte ---------- */
  const FS = [0.85, 0.9, 1, 1.1, 1.2, 1.3, 1.4];
  const fsGet = () => parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--fs')) || 1;
  const fsShow = () => $$('[data-fs-val]').forEach(el => (el.textContent = Math.round(fsGet() * 100) + ' %'));
  $$('[data-fs]').forEach(b => b.addEventListener('click', () => {
    const cur = fsGet(), i = FS.reduce((best, v, k) => Math.abs(v - cur) < Math.abs(FS[best] - cur) ? k : best, 0);
    const nx = FS[Math.min(FS.length - 1, Math.max(0, i + Number(b.dataset.fs)))];
    document.documentElement.style.setProperty('--fs', nx);
    try { localStorage.setItem('gid_fs', nx); } catch (_) {}
    fsShow();
  }));
  fsShow();

  /* ---------- Menu latéral masquable ---------- */
  $$('[data-rail-toggle]').forEach(b => b.addEventListener('click', () => {
    const closed = document.documentElement.dataset.rail === 'closed';
    if (closed) delete document.documentElement.dataset.rail; else document.documentElement.dataset.rail = 'closed';
    try { localStorage.setItem('gid_rail', closed ? 'open' : 'closed'); } catch (_) {}
  }));

  /* ---------- Mobile : menu burger ---------- */
  const burger = $('[data-burger]');
  if (burger) {
    const setNav = (open) => { document.documentElement.classList.toggle('nav-open', open); burger.setAttribute('aria-expanded', open ? 'true' : 'false'); burger.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu'); };
    burger.addEventListener('click', () => setNav(!document.documentElement.classList.contains('nav-open')));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && document.documentElement.classList.contains('nav-open')) setNav(false); });
    $$('.rail nav a').forEach(a => a.addEventListener('click', () => setNav(false)));
    matchMedia('(min-width:821px)').addEventListener('change', (m) => { if (m.matches) setNav(false); });
  }

  /* ---------- Mobile : onglets regroupés dans un menu déroulant ---------- */
  const mqTabs = matchMedia('(max-width:820px)');
  $$('main [role="tablist"]').forEach((list) => {
    const tabs = () => [...list.querySelectorAll('[role="tab"]')];
    if (tabs().length < 2) return;
    const wrap = document.createElement('div'); wrap.className = 'tabpop';
    const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'tabpop-btn'; btn.setAttribute('aria-haspopup', 'true'); btn.setAttribute('aria-expanded', 'false');
    list.parentNode.insertBefore(wrap, list); wrap.appendChild(btn); wrap.appendChild(list);
    const isOn = (t) => t.getAttribute('aria-selected') === 'true' || t.classList.contains('on') || t.classList.contains('active');
    const sync = () => { const t = tabs().find(isOn) || tabs()[0]; btn.textContent = ''; const l = document.createElement('span'); l.textContent = t.textContent.replace(/\s+/g, ' ').trim(); btn.appendChild(l); btn.insertAdjacentHTML('beforeend', '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'); };
    const close = () => { wrap.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); };
    btn.addEventListener('click', (e) => { e.stopPropagation(); const o = !wrap.classList.contains('open'); wrap.classList.toggle('open', o); btn.setAttribute('aria-expanded', o ? 'true' : 'false'); });
    list.addEventListener('click', (e) => { if (e.target.closest('[role="tab"]')) setTimeout(() => { sync(); close(); }, 0); });
    document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });
    new MutationObserver(sync).observe(list, { subtree: true, attributes: true, attributeFilter: ['class', 'aria-selected'] });
    sync();
    mqTabs.addEventListener('change', close);
  });

  /* ---------- Stratégies : leviers « À lancer » ---------- */
  $$('[data-strat]').forEach(cb => cb.addEventListener('change', async () => {
    cb.closest('.st-card')?.classList.toggle('on', cb.checked);
    try { await api('strat/set', { k: cb.dataset.strat, on: cb.checked }); } catch (e) { cb.checked = !cb.checked; }
  }));

  /* ---------- base64url <-> ArrayBuffer ---------- */
  const b64uToBuf = s => Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((s.length + 3) % 4)), c => c.charCodeAt(0)).buffer;
  const bufToB64u = b => btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  const webauthnOk = () => !!(window.PublicKeyCredential && navigator.credentials);

  /* ---------- Saisie OTP ---------- */
  function otpInputs(root) {
    const ins = $$('.otp input', root);
    ins.forEach((inp, i) => {
      inp.addEventListener('input', () => {
        const v = inp.value.replace(/\D/g, '');
        if (v.length > 1) { // collage ou remplissage automatique
          v.slice(0, 6).split('').forEach((c, k) => { if (ins[k]) ins[k].value = c; });
          ins[Math.min(v.length, 6) - 1]?.focus();
          root.dispatchEvent(new CustomEvent('otp-complete'));
          return;
        }
        inp.value = v;
        if (v && ins[i + 1]) ins[i + 1].focus();
        if (ins.every(x => x.value)) root.dispatchEvent(new CustomEvent('otp-complete'));
      });
      inp.addEventListener('keydown', e => { if (e.key === 'Backspace' && !inp.value && ins[i - 1]) ins[i - 1].focus(); });
    });
    return {
      value: () => ins.map(x => x.value).join(''),
      clear: () => { ins.forEach(x => (x.value = '')); ins[0]?.focus(); },
      focus: () => ins[0]?.focus(),
    };
  }

  function steps(form, name) {
    $$('[data-step]', form).forEach(s => (s.hidden = s.dataset.step !== name));
    const order = { main: 1, totp: 2, newpw: 2, enroll: 2, sent: 2, forgot: 2, forgotSent: 2, trust: 3, code: 2 };
    $$('.steps i', form).forEach((b, i) => b.classList.toggle('on', i < (order[name] || 1)));
  }

  async function finishTrust(btn) {
    btn.disabled = true;
    try { await api('trust', { trust: $('#trustDev').checked }); } catch (_) { /* non bloquant */ }
    location.href = btn.dataset.next || '/';
  }

  /* ---------- Page de connexion ---------- */
  const login = $('#loginFlow');
  if (login) {
    const otp = otpInputs($('[data-step=totp]', login));
    const emailIn = $('#email'), pwIn = $('#password'), mainBtn = $('#mainBtn');
    const MODES = {
      password: { btn: 'Se connecter', hint: 'Identifiez-vous avec votre adresse professionnelle et votre mot de passe.' },
      faceid:   { btn: 'Continuer avec Face ID', hint: 'Votre appareil vous reconnaît : aucun mot de passe à saisir.' },
      link:     { btn: 'Recevoir un lien de connexion', hint: 'Nous vous envoyons un lien valable 15 minutes.' },
    };
    let mode = 'password';
    const setMode = m => {
      if (m === 'faceid' && !webauthnOk()) { showError($('[data-step=main]', login), 'Ce navigateur ne prend pas en charge Face ID.'); return; }
      mode = m;
      $$('.mode', login).forEach(b => { const on = b.dataset.mode === m; b.classList.toggle('on', on); b.setAttribute('aria-checked', on); });
      $('#pwField').hidden = m !== 'password';
      mainBtn.textContent = MODES[m].btn; $('#modeHint').textContent = MODES[m].hint;
      showError($('[data-step=main]', login), '');
      try { localStorage.setItem('gid_mode', m); } catch (_) {}
    };
    $$('.mode', login).forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
    try { emailIn.value = localStorage.getItem('gid_email') || ''; const m = localStorage.getItem('gid_mode'); if (m && MODES[m]) setMode(m); } catch (_) {}
    steps(login, login.dataset.start || 'main');
    if ((login.dataset.start || 'main') === 'main') (emailIn.value ? (mode === 'password' ? pwIn : mainBtn) : emailIn).focus();

    const scopeMain = () => $('[data-step=main]', login);
    login.addEventListener('submit', async e => {
      e.preventDefault();
      if ($('[data-step]:not([hidden])', login).dataset.step !== 'main') return;
      const email = emailIn.value.trim().toLowerCase();
      if (!email) return emailIn.focus();
      try { localStorage.setItem('gid_email', email); } catch (_) {}
      showError(scopeMain(), '');
      mainBtn.disabled = true;
      try {
        if (mode === 'password') {
          if (!pwIn.value) { pwIn.focus(); return; }
          const r = await api('login/password', { email, password: pwIn.value });
          pwIn.value = ''; nextStep(r);
        } else if (mode === 'link') {
          await api('login/link', { email });
          steps(login, 'sent');
        } else {
          await api('login/start', { email });
          const opt = await api('login/passkey-options');
          opt.challenge = b64uToBuf(opt.challenge);
          opt.allowCredentials = (opt.allowCredentials || []).map(c => ({ ...c, id: b64uToBuf(c.id) }));
          const cred = await navigator.credentials.get({ publicKey: opt });
          await api('login/passkey', {
            id: cred.id,
            clientDataJSON: bufToB64u(cred.response.clientDataJSON),
            authenticatorData: bufToB64u(cred.response.authenticatorData),
            signature: bufToB64u(cred.response.signature),
          });
          steps(login, 'trust');
        }
      } catch (err) {
        showError(scopeMain(), err.name === 'NotAllowedError' ? 'Vérification annulée, ou aucun Face ID enregistré pour ce compte sur cet appareil.' : err.message);
      } finally { mainBtn.disabled = false; }
    });

    $$('[data-back]', login).forEach(b => b.addEventListener('click', () => steps(login, 'main')));
    function nextStep(r) {
      if (r.next === 'newpw') { steps(login, 'newpw'); $('#np1').focus(); }
      else if (r.next === 'trust') steps(login, 'trust');
      else { steps(login, 'totp'); otp.clear(); }
    }
    $('#npOk').addEventListener('click', async () => {
      const scope = $('[data-step=newpw]', login), a = $('#np1').value, b = $('#np2').value;
      if (a.length < 12) return showError(scope, 'Au moins 12 caractères.');
      if (a !== b) return showError(scope, 'Les deux saisies ne correspondent pas.');
      try { nextStep(await api('login/newpw', { password: a, confirm: b })); $('#np1').value = $('#np2').value = ''; }
      catch (err) { showError(scope, err.message); }
    });

    // Adresse conservée : mémorisée dès la saisie, reprise partout
    const keepEmail = v => { try { localStorage.setItem('gid_email', v.trim().toLowerCase()); } catch (_) {} };
    emailIn.addEventListener('input', () => keepEmail(emailIn.value));
    const forgotIn = $('#emailForgot');
    forgotIn.addEventListener('input', () => { emailIn.value = forgotIn.value; keepEmail(forgotIn.value); });
    $('#forgotLink').addEventListener('click', () => {
      forgotIn.value = emailIn.value.trim();
      showError($('[data-step=forgot]', login), '');
      steps(login, 'forgot');
      (forgotIn.value ? $('#forgotSend') : forgotIn).focus();
    });
    $('#forgotSend').addEventListener('click', async e => {
      const scope = $('[data-step=forgot]', login), btn = e.currentTarget;
      const email = forgotIn.value.trim().toLowerCase();
      if (!email) return forgotIn.focus();
      btn.disabled = true;
      try { await api('login/forgot', { email }); emailIn.value = email; keepEmail(email); steps(login, 'forgotSent'); }
      catch (err) { showError(scope, err.message); }
      finally { btn.disabled = false; }
    });
    forgotIn.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $('#forgotSend').click(); } });
    const verifyTotp = async () => {
      const scope = $('[data-step=totp]', login);
      const code = otp.value();
      if (code.length !== 6) return showError(scope, 'Saisissez les 6 chiffres.');
      try { await api('login/totp', { code }); steps(login, 'trust'); }
      catch (err) { showError(scope, err.message); otp.clear(); }
    };
    $('#totpOk').addEventListener('click', verifyTotp);
    $('[data-step=totp]', login).addEventListener('otp-complete', verifyTotp);
    $('#enter').addEventListener('click', e => finishTrust(e.currentTarget));
  }

  /* ---------- Activation ---------- */
  const enroll = $('#enrollFlow');
  if (enroll) {
    const otp = otpInputs(enroll);
    otp.focus();
    const go = async () => {
      const scope = $('[data-step=code]', enroll);
      const code = otp.value();
      if (code.length !== 6) return showError(scope, 'Saisissez les 6 chiffres.');
      const pw = $('#newPw').value, pw2 = $('#newPw2').value;
      if (pw.length < 12) return showError(scope, 'Choisissez un mot de passe d\'au moins 12 caractères.');
      if (pw !== pw2) return showError(scope, 'Les deux mots de passe ne correspondent pas.');
      try { await api('enroll/verify', { code, password: pw }); steps(enroll, 'trust'); }
      catch (err) { showError(scope, err.message); otp.clear(); }
    };
    $('#enrollOk').addEventListener('click', go);
    enroll.addEventListener('submit', e => e.preventDefault());
    $('#enter').addEventListener('click', e => finishTrust(e.currentTarget));
    $('#copySecret')?.addEventListener('click', () => copy($('#secret').textContent.replace(/\s/g, ''), $('#secret')));
  }

  /* ---------- Nouveau mot de passe ---------- */
  const reset = $('#resetFlow');
  if (reset) {
    $('#rp1').focus();
    reset.addEventListener('submit', async e => {
      e.preventDefault();
      const scope = $('[data-step=form]', reset);
      const a = $('#rp1').value, b = $('#rp2').value;
      if (a.length < 12) return showError(scope, 'Au moins 12 caractères.');
      if (a !== b) return showError(scope, 'Les deux saisies ne correspondent pas.');
      try {
        const r = await api('reset/confirm', { token: reset.dataset.token, password: a, confirm: b });
        try { if (r.email) localStorage.setItem('gid_email', r.email); localStorage.setItem('gid_mode', 'password'); } catch (_) {}
        $$('[data-step]', reset).forEach(s => (s.hidden = s.dataset.step !== 'done'));
      } catch (err) { showError(scope, err.message); }
    });
  }

  /* ---------- Sécurité : code à 6 chiffres optionnel ---------- */
  const totpBox = $('#totpSetup');
  if (totpBox) {
    const otpS = otpInputs(totpBox);
    $('#totpStart')?.addEventListener('click', async () => {
      try {
        const r = await api('totp/start');
        $('#tsSecret').textContent = r.secret; $('#tsUri').href = r.uri;
        totpBox.hidden = false; $('#totpStart').hidden = true; otpS.clear();
      } catch (err) { toast(err.message); }
    });
    const confirmTotp = async () => {
      const code = otpS.value();
      if (code.length !== 6) return showError(totpBox, 'Saisissez les 6 chiffres.');
      try { await api('totp/confirm', { code }); location.reload(); }
      catch (err) { showError(totpBox, err.message); otpS.clear(); }
    };
    $('#tsOk').addEventListener('click', confirmTotp);
    totpBox.addEventListener('otp-complete', confirmTotp);
  }

  /* ---------- Annuaire des immeubles ---------- */
  const immQ = $('#immQ');
  if (immQ) {
    const norm = t => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const gestSel = $('#immGest');
    let tab = 'syndic';
    try { const t = localStorage.getItem('gid_imm_tab'); if (t) tab = t; } catch (_) {}
    const rows = $$('[data-filter-table] tbody tr').map(tr => ({ tr, txt: norm(tr.textContent), gest: tr.dataset.gest || '', panel: tr.closest('[data-panel]').dataset.panel }));
    const cards = $$('[data-filter-card]').map(c => ({ c, txt: norm(c.textContent + ' ' + [...c.querySelectorAll('input')].map(i => i.value).join(' ')) }));
    const apply = () => {
      const q = norm(immQ.value.trim()).split(/\s+/).filter(Boolean), g = gestSel.value;
      let n = 0;
      rows.forEach(r => { const ok = q.every(w => r.txt.includes(w)) && (!g || r.gest === g || r.panel !== 'syndic'); r.tr.hidden = !ok; if (ok && r.panel === tab) n++; });
      cards.forEach(c => { c.c.hidden = !q.every(w => c.txt.includes(w)); });
      $$('[data-panel]').forEach(p => { const none = $('.imm-none', p); if (none) none.hidden = !!p.querySelector('tbody tr:not([hidden])'); });
      $('#immCount').textContent = (tab === 'syndic' || tab === 'autres') ? n + ' résultat' + (n > 1 ? 's' : '') : '';
    };
    const show = t => {
      tab = t;
      $$('.imm-tabs button').forEach(b => b.classList.toggle('on', b.dataset.tab === t));
      $$('[data-panel]').forEach(p => (p.hidden = p.dataset.panel !== t));
      gestSel.hidden = t !== 'syndic';
      try { localStorage.setItem('gid_imm_tab', t); } catch (_) {}
      apply();
    };
    $$('.imm-tabs button').forEach(b => b.addEventListener('click', () => show(b.dataset.tab)));
    immQ.addEventListener('input', apply); gestSel.addEventListener('change', apply);
    document.addEventListener('keydown', e => { if (e.key === '/' && document.activeElement !== immQ && !/INPUT|TEXTAREA/.test(document.activeElement.tagName)) { e.preventDefault(); immQ.focus(); } });
    show(tab); immQ.focus();
  }

  async function copy(text, selectEl) {
    try { await navigator.clipboard.writeText(text); toast('Copié'); }
    catch (_) {
      if (selectEl?.select) selectEl.select();
      else if (selectEl) { const r = document.createRange(); r.selectNodeContents(selectEl); getSelection().removeAllRanges(); getSelection().addRange(r); }
    }
  }

  /* ---------- Application : actions génériques ---------- */
  // Boutons / cases à cocher [data-api][data-id], confirmation en deux clics si data-confirm
  $$('[data-api][data-id], [data-api][data-k], button[data-api]:not(form [data-api])').forEach(el => {
    if (el.tagName === 'FORM') return;
    const ev = el.type === 'checkbox' ? 'change' : 'click';
    el.addEventListener(ev, async () => {
      if (el.dataset.confirm && !el._armed) {
        el._armed = true; el._label = el.textContent; el.textContent = el.dataset.confirm; el.classList.add('armed');
        setTimeout(() => { el._armed = false; el.textContent = el._label; el.classList.remove('armed'); }, 3000);
        return;
      }
      el.disabled = true;
      try { await api(el.dataset.api, el.dataset.k ? { k: el.dataset.k } : { id: Number(el.dataset.id || 0) }); location.reload(); }
      catch (err) { el.disabled = false; toast(err.message); }
    });
  });

  // Pastille Tâches IA (toutes les pages)
  const iaFab = $('#iaFab'), iaPop = $('#iaPop');
  if (iaFab && iaPop) {
    const setIa = open => { iaPop.hidden = !open; iaFab.setAttribute('aria-expanded', String(open)); if (open) $('#iaBody')?.focus(); };
    iaFab.addEventListener('click', () => setIa(iaPop.hidden));
    $('[data-ia-close]', iaPop)?.addEventListener('click', () => setIa(false));
    $$('[data-ia-open]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); setIa(true); }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !iaPop.hidden) { setIa(false); iaFab.focus(); } });
  }
  // Formulaires [data-api]
  $$('form[data-api]').forEach(f => f.addEventListener('submit', async e => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(f).entries());
    const btn = $('button:not([type=button])', f);
    if (btn) btn.disabled = true;
    showError(f.closest('.panel') || f, '');
    try {
      const r = await api(f.dataset.api, data);
      if ('showLink' in f.dataset && r.link) {
        const box = $('.invite-link', f); box.hidden = false; $('#iv-link').value = r.link;
        if (btn) btn.disabled = false;
        return;
      }
      if (f.dataset.toast && location.pathname !== '/assistant') {
        f.reset(); if (btn) btn.disabled = false;
        const pop = f.closest('[role=dialog]'); if (pop) { pop.hidden = true; $('#iaFab')?.setAttribute('aria-expanded', 'false'); }
        toast(f.dataset.toast); return;
      }
      location.reload();
    } catch (err) {
      showError(f.closest('.panel') || f, err.message);
      if (btn) btn.disabled = false;
    }
  }));
  $('#copyLink')?.addEventListener('click', () => copy($('#iv-link').value, $('#iv-link')));

  // Fenêtres de dialogue
  $$('[data-open]').forEach(b => b.addEventListener('click', () => {
    const d = document.getElementById(b.dataset.open);
    d?.showModal(); $('input', d)?.focus();
  }));
  $$('dialog [data-close]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));
  $$('dialog').forEach(d => d.addEventListener('click', e => { if (e.target === d) d.close(); }));

  // Bloc-notes enregistré automatiquement
  const note = $('[data-autosave]');
  if (note) {
    let h;
    const state = $('#noteState');
    note.addEventListener('input', () => {
      clearTimeout(h); state.textContent = 'Modification…';
      h = setTimeout(async () => {
        try { await api(note.dataset.autosave, { body: note.value, space: note.dataset.space || '' }); state.textContent = 'Enregistré'; }
        catch (err) { state.textContent = err.message; }
      }, 800);
    });
  }

  // Ajout d'une clé d'accès (Face ID / Windows Hello)
  const addPk = $('#addPasskey');
  if (addPk) {
    if (!webauthnOk()) { addPk.disabled = true; showError(addPk.closest('.panel'), 'Ce navigateur ne prend pas en charge les clés d\'accès.'); }
    if (new URLSearchParams(location.search).has('bienvenue')) { addPk.classList.add('btn-primary'); addPk.classList.remove('btn-ghost'); toast('Compte activé. Ajoutez Face ID pour vos prochaines connexions.'); }
    addPk.addEventListener('click', async () => {
      const scope = addPk.closest('.panel');
      showError(scope, '');
      try {
        const opt = await api('passkey/options');
        opt.challenge = b64uToBuf(opt.challenge);
        opt.user.id = b64uToBuf(opt.user.id);
        opt.excludeCredentials = (opt.excludeCredentials || []).map(c => ({ ...c, id: b64uToBuf(c.id) }));
        const cred = await navigator.credentials.create({ publicKey: opt });
        await api('passkey/register', {
          clientDataJSON: bufToB64u(cred.response.clientDataJSON),
          attestationObject: bufToB64u(cred.response.attestationObject),
        });
        location.href = '/securite';
      } catch (err) {
        showError(scope, err.name === 'InvalidStateError' ? 'Cet appareil est déjà enregistré.'
          : err.name === 'NotAllowedError' ? 'Opération annulée.' : err.message);
      }
    });
  }

  /* Planning des AG : recherche, gestionnaire, statut et filtres rapides (mémorisés) */
  (function(){ const G = document.getElementById('agG'), Q = document.getElementById('agQ'); if (!G || !Q) return;
    const segs = $$('.ag-seg [data-s]'), kpis = $$('.ag-kpi'), cnt = document.getElementById('agCount'), none = document.getElementById('agNone');
    let st = { g: '', s: '', k: '' };
    const apply = () => {
      const q = Q.value.trim().toLowerCase(); let tot = 0;
      segs.forEach(b => b.setAttribute('aria-pressed', String(b.dataset.s === st.s)));
      kpis.forEach(b => b.setAttribute('aria-pressed', String(b.dataset.k === st.k)));
      G.value = st.g;
      document.querySelectorAll('.ag-group').forEach(sec => {
        let n = 0;
        if (!st.g || sec.dataset.g === st.g) sec.querySelectorAll('tbody tr').forEach(tr => {
          const ok = (!st.s || (st.s === 'alerte' ? tr.dataset.a === '1' : tr.dataset.s === st.s)) && (!st.k || (' ' + tr.dataset.k + ' ').includes(' ' + st.k + ' ')) && (!q || tr.dataset.q.includes(q));
          tr.hidden = !ok; if (ok) n++; });
        sec.hidden = !n; const gc = sec.querySelector('[data-gc]'); if (gc) gc.textContent = n; tot += n; });
      if (cnt) cnt.textContent = tot + ' immeuble' + (tot > 1 ? 's' : '') + ' affiché' + (tot > 1 ? 's' : '') + (st.g || st.s || st.k || q ? ' · ' : '');
      if (cnt && (st.g || st.s || st.k || q)) { const r = document.createElement('button'); r.type = 'button'; r.className = 'btn-link'; r.textContent = 'Réinitialiser les filtres'; r.addEventListener('click', () => { st = { g: '', s: '', k: '' }; Q.value = ''; apply(); }); cnt.appendChild(r); }
      if (none) none.hidden = tot > 0;
      try { localStorage.setItem('gid_ag2', JSON.stringify(st)); } catch (e) {} };
    try { const v = JSON.parse(localStorage.getItem('gid_ag2') || '{}'); if (v && typeof v === 'object') st = Object.assign(st, v); if (st.g && ![...G.options].some(o => o.value === st.g)) st.g = ''; } catch (e) {}
    G.addEventListener('change', () => { st.g = G.value; apply(); });
    segs.forEach(b => b.addEventListener('click', () => { st.s = b.dataset.s; apply(); }));
    kpis.forEach(b => b.addEventListener('click', () => { st.k = st.k === b.dataset.k ? '' : b.dataset.k; if (st.k) st.s = ''; apply(); document.querySelector('.ag-bar')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }));
    let t; Q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(apply, 120); });
    apply(); })();

  /* Planning des AG : ordre et affichage des colonnes, tri par colonne (mémorisés pour chaque utilisateur) */
  (function(){
    const btn = document.getElementById('agColsBtn'), pop = document.getElementById('agColsPop'), tbls = $$('.ag-tbl'); if (!btn || !tbls.length) return;
    let P = {}; try { P = JSON.parse(document.getElementById('agPrefs').textContent || '{}') || {}; } catch (e) {}
    const save = (k, v) => { P[k] = v; api('prefs/set', { k, v }).catch(() => {}); };
    const head = tbls[0].querySelector('thead tr'), DEF = $$('th[data-col]', head).map(th => th.dataset.col);
    const LAB = {}; $$('th[data-col]', head).forEach(th => { LAB[th.dataset.col] = th.textContent.trim() || th.title || th.dataset.col; });
    const order = () => { const sv = Array.isArray(P.ag_order) ? P.ag_order.filter(k => DEF.includes(k)) : []; const o = sv.length ? sv : DEF.slice(); DEF.forEach((k, i) => { if (o.includes(k)) return; let j = i - 1; while (j >= 0 && !o.includes(DEF[j])) j--; o.splice(j < 0 ? 0 : o.indexOf(DEF[j]) + 1, 0, k); }); return o; };
    const hidden = () => Array.isArray(P.ag_cols) ? P.ag_cols : [];
    const css = document.createElement('style'); document.head.appendChild(css);
    const apply = () => {
      const o = order();
      tbls.forEach(t => t.querySelectorAll('tr').forEach(tr => { const by = {}; [...tr.children].forEach(c => { if (c.dataset.col) by[c.dataset.col] = c; }); o.forEach(k => { if (by[k]) tr.appendChild(by[k]); }); }));
      css.textContent = hidden().map(k => `.ag-tbl [data-col="${String(k).replace(/[^\w-]/g, '')}"]{display:none}`).join('\n');
      const so = P.ag_sort || {}; $$('.ag-tbl th[data-col]').forEach(th => { th.classList.toggle('sorted', th.dataset.col === so.k); th.dataset.dir = th.dataset.col === so.k ? (so.d < 0 ? '↓' : '↑') : ''; });
    };
    const sortRows = () => {
      const so = P.ag_sort; if (!so || !so.k) return;
      tbls.forEach(t => { const tb = t.tBodies[0]; const rows = [...tb.rows];
        const v = tr => { const c = tr.querySelector(`[data-col="${so.k}"]`); if (!c) return ''; const x = c.dataset.v ?? c.textContent.trim(); return x; };
        rows.sort((a, b) => { const x = v(a), y = v(b), nx = parseFloat(x), ny = parseFloat(y); const r = (!isNaN(nx) && !isNaN(ny) && /^-?[\d.]+$/.test(x) && /^-?[\d.]+$/.test(y)) ? nx - ny : String(x).localeCompare(String(y), 'fr', { numeric: true }); return r * (so.d || 1); });
        rows.forEach(r => tb.appendChild(r)); });
    };
    $$('.ag-tbl th[data-col]').forEach(th => { th.style.cursor = 'pointer'; th.title = (th.title ? th.title + ' · ' : '') + 'Cliquer pour trier'; th.addEventListener('click', () => { const so = P.ag_sort || {}; save('ag_sort', { k: th.dataset.col, d: so.k === th.dataset.col ? -(so.d || 1) : 1 }); sortRows(); apply(); }); });
    const build = () => {
      pop.textContent = '';
      const hd = document.createElement('div'); hd.className = 'ag-pop-h'; hd.innerHTML = '<span>Colonnes : affichage et ordre</span>';
      const rs = document.createElement('button'); rs.type = 'button'; rs.className = 'btn-link'; rs.textContent = 'Réinitialiser'; rs.addEventListener('click', () => { save('ag_cols', []); save('ag_order', []); save('ag_sort', {}); apply(); build(); }); hd.appendChild(rs); pop.appendChild(hd);
      const o = order();
      o.forEach((k, i) => {
        const row = document.createElement('div'); row.className = 'ag-pop-r';
        const lb = document.createElement('label'); const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !hidden().includes(k); if (k === 'imm') { cb.disabled = true; cb.checked = true; }
        cb.addEventListener('change', () => { const hs = hidden().filter(x => x !== k); if (!cb.checked) hs.push(k); save('ag_cols', hs); apply(); });
        lb.append(cb, ' ' + (k === 'fin' ? 'Rentabilité' : LAB[k])); row.appendChild(lb);
        [['↑', -1], ['↓', 1]].forEach(([t, d]) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = t; b.setAttribute('aria-label', (d < 0 ? 'Monter ' : 'Descendre ') + (LAB[k] || k)); b.disabled = (d < 0 && i === 0) || (d > 0 && i === o.length - 1);
          b.addEventListener('click', () => { const oo = order(), j = i + d; oo.splice(i, 1); oo.splice(j, 0, k); save('ag_order', oo); apply(); build(); const nb = pop.querySelectorAll('.ag-pop-r')[j]?.querySelectorAll('button')[d < 0 ? 0 : 1]; if (nb && !nb.disabled) nb.focus(); }); row.appendChild(b); });
        pop.appendChild(row);
      });
      const n = document.createElement('p'); n.className = 'muted small'; n.textContent = 'Cliquez sur un en-tête pour trier. Vos choix sont enregistrés pour votre compte.'; pop.appendChild(n);
    };
    pop.addEventListener('click', e => e.stopPropagation());
    btn.addEventListener('click', e => { e.stopPropagation(); const o = pop.hidden; if (o) build(); pop.hidden = !o; btn.setAttribute('aria-expanded', String(o)); });
    document.addEventListener('click', () => { if (!pop.hidden) { pop.hidden = true; btn.setAttribute('aria-expanded', 'false'); } });
    sortRows(); apply();
  })();

  /* Voyages : programme en accordéon, PDF (impression) et lien de partage */
  (function(){
    $$('[data-acc]').forEach(b => b.addEventListener('click', () => $$('.trip-day').forEach(d => { d.open = b.dataset.acc === 'open'; })));
    let opened = null;
    window.addEventListener('beforeprint', () => { opened = $$('.trip-day').map(d => d.open); $$('.trip-day').forEach(d => { d.open = true; }); });
    window.addEventListener('afterprint', () => { if (opened) $$('.trip-day').forEach((d, i) => { d.open = opened[i]; }); opened = null; });
    $$('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));
    const box = document.getElementById('tripShare'); if (!box) return;
    const tg = $('[data-share-toggle]'), row = $('.trip-share-row', box), inp = $('#shareUrl'), on = $('[data-share-on]', box), off = $('[data-share-off]', box);
    tg.addEventListener('click', () => { box.hidden = !box.hidden; tg.setAttribute('aria-expanded', String(!box.hidden)); if (!box.hidden && inp.value) inp.select(); });
    on.addEventListener('click', async () => { on.disabled = true;
      try { const r = await api('trips/share', { id: Number(box.dataset.trip), on: 1 }); inp.value = r.url; row.hidden = false; on.hidden = true; inp.select(); try { await navigator.clipboard.writeText(r.url); toast('Lien créé et copié'); } catch (e) { toast('Lien créé'); } }
      catch (e) { toast(e.message); } on.disabled = false; });
    $('[data-copy-share]', box).addEventListener('click', async () => { try { await navigator.clipboard.writeText(inp.value); toast('Lien copié'); } catch (e) { inp.select(); } });
    off.addEventListener('click', async () => {
      if (off.dataset.c !== '1') { off.dataset.c = '1'; off.textContent = 'Confirmer la désactivation'; setTimeout(() => { off.dataset.c = ''; off.textContent = 'Désactiver le lien'; }, 4000); return; }
      try { await api('trips/share', { id: Number(box.dataset.trip), on: 0 }); inp.value = ''; row.hidden = true; on.hidden = false; off.dataset.c = ''; off.textContent = 'Désactiver le lien'; toast('Lien désactivé : il ne fonctionne plus'); }
      catch (e) { toast(e.message); } });
  })();
})();

/* /honoraires#opti : transmet l'onglet demandé à l'outil (changement d'ancre, sans rechargement) */
(function(){ var f=document.querySelector('.doc-frame iframe'); if(!f) return;
  function pass(){ if(/^#[a-z]+$/.test(location.hash)) f.src = f.getAttribute('src').split('#')[0] + location.hash; }
  pass(); window.addEventListener('hashchange', pass); })();

/* Lignes cliquables (annuaire des immeubles) */
document.addEventListener('click', e => { const tr = e.target.closest('tr[data-href]'); if (!tr || e.target.closest('a,button,input,select,textarea')) return;
  if (e.metaKey || e.ctrlKey) window.open(tr.dataset.href, '_blank'); else location.href = tr.dataset.href; });

/* Comptabilité : une fiche par comptable, détail affiché sous les blocs */
(function(){ const tabs = [...document.querySelectorAll('[data-cp-tab]')]; if (!tabs.length) return;
  const show = (n, scroll) => { tabs.forEach(t => t.setAttribute('aria-selected', String(t.dataset.cpTab === n)));
    document.querySelectorAll('[data-cp-panel]').forEach(p => { p.hidden = p.dataset.cpPanel !== n; if (!p.hidden && scroll) p.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    try { localStorage.setItem('gid_cp', n); } catch (e) {} };
  let v = null; try { v = localStorage.getItem('gid_cp'); } catch (e) {}
  show(tabs.some(t => t.dataset.cpTab === v) ? v : tabs[0].dataset.cpTab, false);
  tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.cpTab, true))); })();

/* Sidebar : liste des personnes ayant accès à une section */
(function(){
  const close = () => document.querySelectorAll('.grp-n[aria-expanded=true]').forEach(b => { b.setAttribute('aria-expanded','false'); document.getElementById('grp-' + b.dataset.grp).hidden = true; });
  document.addEventListener('click', e => {
    const b = e.target.closest('.grp-n');
    if (!b) { if (!e.target.closest('.grp-pop')) close(); return; }
    const open = b.getAttribute('aria-expanded') === 'true'; close();
    if (!open) { b.setAttribute('aria-expanded','true'); document.getElementById('grp-' + b.dataset.grp).hidden = false; }
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
})();

/* Voir plus / Réduire : listes limitées à 10 lignes */
document.addEventListener('click', e => {
  const b = e.target.closest('[data-more]'); if (!b) return;
  const box = b.parentElement, list = box.querySelector('[data-more-list]'); if (!list) return;
  const open = b.dataset.open === '1';
  list.querySelectorAll('[data-more-x]').forEach(li => { li.hidden = open; });
  if (!b.dataset.more0) b.dataset.more0 = b.textContent;
  b.textContent = open ? b.dataset.more0 : (b.dataset.less || 'Réduire');
  b.dataset.open = open ? '0' : '1';
});

/* Jauge de robustesse sous chaque premier champ « nouveau mot de passe » */
(function(){
  const score = v => { if (!v) return 0; let c = (/[a-z]/.test(v)) + (/[A-Z]/.test(v)) + (/\d/.test(v)) + (/[^a-zA-Z\d]/.test(v)); let s = 0;
    if (v.length >= 12) s++; if (v.length >= 16) s++; if (c >= 3) s++; if (c === 4) s++; if (/(.)\1{3,}|1234|abcd|azer|qwer|password|motdepasse|azerty/i.test(v)) s = Math.min(s, 1); return Math.min(4, s); };
  const lab = ['Trop court', 'Faible', 'Moyen', 'Solide', 'Très solide'];
  document.querySelectorAll('form, .login-card, .stack').forEach(f => {
    const inp = f.querySelector('input[autocomplete="new-password"]'); if (!inp || inp.dataset.meter) return; inp.dataset.meter = '1';
    const m = document.createElement('div'); m.className = 'pw-meter'; m.innerHTML = '<i></i><i></i><i></i><i></i><span>12 caractères minimum, trois types de caractères</span>';
    inp.insertAdjacentElement('afterend', m);
    inp.addEventListener('input', () => { const s = inp.value ? Math.max(1, score(inp.value)) : 0; m.dataset.s = s; m.querySelector('span').textContent = inp.value ? lab[inp.value.length < 12 ? 0 : s] : '12 caractères minimum, trois types de caractères'; });
  });
})();

/* Tableau d'affichage : filtre par rubrique */
document.addEventListener('click', e => { const a = e.target.closest('[data-bd]'); if (!a) return; e.preventDefault();
  a.parentElement.querySelectorAll('[data-bd]').forEach(x => x.classList.toggle('on', x === a));
  document.querySelectorAll('.bd-card').forEach(c => { c.hidden = !!a.dataset.bd && c.dataset.cat !== a.dataset.bd; }); });

/* Questionnaire de la Direction (Accueil) : envoi des choix multiples */
(function(){ const f = document.getElementById('qcmForm'); if (!f) return;
  f.addEventListener('submit', async e => { e.preventDefault(); const d = {}; const btn = f.querySelector('button');
    f.querySelectorAll('input').forEach(i => { if (i.type === 'radio' || i.type === 'checkbox') { if (i.checked) (d[i.name] = d[i.name] || []).push(i.value); } else d[i.name] = i.value; });
    btn.disabled = true; try { await api('accueil/qcm', d); location.reload(); } catch (err) { btn.disabled = false; const p = f.querySelector('.form-error'); p.hidden = false; p.textContent = err.message; } });
})();

/* Présentation : diapositives (flèches, balayage, plein écran) */
(function(){ const d = document.getElementById('deck'); if (!d) return;
  const S = [...d.querySelectorAll('.sl')], dots = [...d.querySelectorAll('[data-go]')]; let i = 0;
  const go = n => { i = Math.max(0, Math.min(S.length - 1, n)); S.forEach((s, k) => s.hidden = k !== i); dots.forEach((b, k) => b.classList.toggle('on', k === i)); document.getElementById('deckI').textContent = i + 1; try { history.replaceState(null, '', '#' + (i + 1)); } catch (e) {} };
  document.getElementById('deckP').addEventListener('click', () => go(i - 1)); document.getElementById('deckN').addEventListener('click', () => go(i + 1));
  dots.forEach(b => b.addEventListener('click', () => go(+b.dataset.go)));
  document.addEventListener('keydown', e => { if (/INPUT|TEXTAREA/.test(document.activeElement?.tagName)) return; if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); go(i + 1); } if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); go(i - 1); } });
  let x0 = null; d.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true }); d.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) go(i + (dx < 0 ? 1 : -1)); x0 = null; });
  document.getElementById('deckFs').addEventListener('click', () => { if (document.fullscreenElement) document.exitFullscreen(); else d.requestFullscreen?.(); });
  go((parseInt(location.hash.slice(1), 10) || 1) - 1);
})();

/* Tableaux triables [data-sortable] + recherche du classement */
document.querySelectorAll('table[data-sortable]').forEach(t => {
  const tb = t.tBodies[0];
  t.querySelectorAll('th').forEach((th, ci) => { th.style.cursor = 'pointer'; th.addEventListener('click', () => {
    const d = th.dataset.dir === '1' ? -1 : 1; t.querySelectorAll('th').forEach(x => { x.dataset.dir = ''; x.classList.remove('sorted'); }); th.dataset.dir = String(d); th.classList.add('sorted');
    const rows = [...tb.rows]; rows.sort((a, b) => { const x = a.cells[ci].dataset.v ?? '', y = b.cells[ci].dataset.v ?? '', nx = parseFloat(x), ny = parseFloat(y);
      return ((!isNaN(nx) && !isNaN(ny)) ? nx - ny : x.localeCompare(y, 'fr', { numeric: true })) * d; }); rows.forEach(r => tb.appendChild(r)); }); });
});
(function(){ const q = document.getElementById('rkQ'); if (!q) return; q.addEventListener('input', () => { const v = q.value.trim().toLowerCase(); document.querySelectorAll('.rk-tbl tbody tr').forEach(tr => { tr.hidden = !!v && !tr.dataset.q.includes(v); }); }); })();

/* Registres du pilotage : modifier une fiche dans le formulaire */
(function(){ const box = document.getElementById('regForm'); if (!box) return; const f = box.querySelector('form'), t = document.getElementById('regFormT');
  const reset = () => { f.reset(); f.id.value = '0'; t.textContent = 'Nouvelle fiche'; };
  document.addEventListener('click', e => { const b = e.target.closest('[data-edit]'); if (!b) return; const d = JSON.parse(b.dataset.edit); reset();
    Object.keys(d).forEach(k => { const el = f.elements[k]; if (el) el.value = d[k] ?? ''; }); t.textContent = 'Modifier la fiche'; box.open = true; box.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
  document.getElementById('regReset').addEventListener('click', () => { reset(); box.open = false; });
})();

/* Application installable + pastille sur l'icône (tâches en cours) */
if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
(function(){ const n = document.querySelector('.nav-ia .nav-n'), t = [...document.querySelectorAll('.rail nav a')].find(a => a.getAttribute('href') === '/mes-taches')?.querySelector('.nav-n');
  const c = (parseInt(t?.textContent || '0', 10) || 0); if ('setAppBadge' in navigator) { try { c ? navigator.setAppBadge(c) : navigator.clearAppBadge(); } catch (e) {} } })();

/* Questionnaire des équipes : enregistrement automatique, limites de choix, envoi */
(function(){ const f = document.getElementById('svForm'); if (!f) return; const st = document.getElementById('svState');
  const data = () => { const d = {}; f.querySelectorAll('input,textarea').forEach(i => { if (i.type === 'radio' || i.type === 'checkbox') { if (i.checked) (d[i.name] = d[i.name] || []).push(i.value); } else if (i.value.trim()) d[i.name] = i.value; }); return d; };
  let t; const save = async (submit) => { try { await api('survey/save', Object.assign(data(), submit ? { submit: 1 } : {})); st.textContent = submit ? 'Merci, questionnaire envoyé.' : 'Enregistré · ' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }); return true; } catch (e) { st.textContent = e.message; return false; } };
  f.addEventListener('change', e => { const box = e.target.closest('[data-max]'); const mx = box ? +box.dataset.max : 0;
    if (mx && e.target.checked && box.querySelectorAll('input:checked').length > mx) { e.target.checked = false; st.textContent = mx + ' choix maximum pour cette question.'; return; }
    clearTimeout(t); t = setTimeout(() => save(false), 600); });
  f.addEventListener('input', e => { if (e.target.matches('textarea,input[type=text],.sv-o')) { clearTimeout(t); t = setTimeout(() => save(false), 1200); } });
  document.getElementById('svSend').addEventListener('click', async () => { if (await save(true)) location.href = '/mes-taches'; });
})();
