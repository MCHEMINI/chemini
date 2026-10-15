(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name=csrf]')?.content || '';

  async function api(path, data = {}) {
    const res = await fetch('/api/' + path, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf },
      body: JSON.stringify(data),
    });
    let json = {};
    try { json = await res.json(); } catch (_) { /* réponse vide */ }
    if (!res.ok || json.error) throw new Error(json.error || 'Erreur réseau. Réessayez.');
    return json;
  }

  function showError(scope, msg) {
    const el = $('.form-error', scope);
    if (el) { el.textContent = msg; el.hidden = !msg; }
  }

  function toast(msg) {
    const t = $('#toast'); if (!t) return;
    t.textContent = msg; t.hidden = false;
    clearTimeout(t._h); t._h = setTimeout(() => (t.hidden = true), 2600);
  }

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
    const order = { main: 1, totp: 2, sent: 2, forgot: 2, forgotSent: 2, trust: 3, code: 2 };
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
    const otp = otpInputs(login);
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
          await api('login/password', { email, password: pwIn.value });
          pwIn.value = ''; steps(login, 'totp'); otp.clear();
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
    login.addEventListener('otp-complete', verifyTotp);
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
      if (pw.length < 10) return showError(scope, 'Choisissez un mot de passe d\'au moins 10 caractères.');
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
      if (a.length < 10) return showError(scope, 'Au moins 10 caractères.');
      if (a !== b) return showError(scope, 'Les deux saisies ne correspondent pas.');
      try {
        const r = await api('reset/confirm', { token: reset.dataset.token, password: a, confirm: b });
        try { if (r.email) localStorage.setItem('gid_email', r.email); localStorage.setItem('gid_mode', 'password'); } catch (_) {}
        $$('[data-step]', reset).forEach(s => (s.hidden = s.dataset.step !== 'done'));
      } catch (err) { showError(scope, err.message); }
    });
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
  $$('[data-api][data-id], button[data-api]:not(form [data-api])').forEach(el => {
    if (el.tagName === 'FORM') return;
    const ev = el.type === 'checkbox' ? 'change' : 'click';
    el.addEventListener(ev, async () => {
      if (el.dataset.confirm && !el._armed) {
        el._armed = true; el._label = el.textContent; el.textContent = el.dataset.confirm; el.classList.add('armed');
        setTimeout(() => { el._armed = false; el.textContent = el._label; el.classList.remove('armed'); }, 3000);
        return;
      }
      el.disabled = true;
      try { await api(el.dataset.api, { id: Number(el.dataset.id || 0) }); location.reload(); }
      catch (err) { el.disabled = false; toast(err.message); }
    });
  });

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
        try { await api(note.dataset.autosave, { body: note.value }); state.textContent = 'Enregistré'; }
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
})();
