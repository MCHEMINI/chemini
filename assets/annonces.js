/* Appli « Annonces » de Chemini : créer, voir, modifier et supprimer les annonces du site, même sans réseau.
   Les modifications faites hors connexion sont gardées sur le téléphone (IndexedDB) et envoyées dès que le réseau revient. */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
  let csrf = $('meta[name=csrf]')?.content || '';
  const toast = (m, ms = 3000) => { const t = $('#toast'); t.textContent = m; t.hidden = false; clearTimeout(t._h); t._h = setTimeout(() => (t.hidden = true), ms); };

  /* ---------- Stockage local ---------- */
  const DB = new Promise((ok, ko) => { const r = indexedDB.open('gid-annonces', 1); r.onupgradeneeded = () => { r.result.createObjectStore('kv'); r.result.createObjectStore('queue', { keyPath: 'qid', autoIncrement: true }); }; r.onsuccess = () => ok(r.result); r.onerror = () => ko(r.error); });
  const tx = async (store, mode, fn) => { const db = await DB; return new Promise((ok, ko) => { const t = db.transaction(store, mode); const st = t.objectStore(store); const res = fn(st); t.oncomplete = () => ok(res && 'result' in res ? res.result : res); t.onerror = () => ko(t.error); }); };
  const kvGet = k => tx('kv', 'readonly', s => s.get(k)), kvSet = (k, v) => tx('kv', 'readwrite', s => s.put(v, k));
  const qAll = () => tx('queue', 'readonly', s => s.getAll()), qAdd = op => tx('queue', 'readwrite', s => s.add(op)), qDel = id => tx('queue', 'readwrite', s => s.delete(id));

  /* ---------- Réseau ---------- */
  class Offline extends Error {}
  async function api(path, data = {}) {
    if (!navigator.onLine) throw new Offline('hors ligne');
    let res;
    try { res = await fetch('/api/' + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf }, body: JSON.stringify(data) }); }
    catch (e) { throw new Offline('réseau'); }
    const fresh = res.headers.get('X-CSRF-Token'); if (fresh) csrf = fresh;
    let j = {}; try { j = await res.json(); } catch (_) {}
    if (res.status === 403 && /Session expirée/.test(j.error || '')) { if (await refreshCsrf()) return api(path, data); }
    if (res.status === 401) { const e = new Error('Session expirée : reconnectez-vous à Chemini.'); e.auth = true; throw e; }
    if (!res.ok || j.error) throw new Error(j.error || 'Erreur ' + res.status);
    return j;
  }
  async function refreshCsrf() { try { const h = await (await fetch('/annonces/', { credentials: 'same-origin', cache: 'no-store' })).text(); const m = h.match(/name="csrf" content="([^"]+)"/); if (m) { csrf = m[1]; return true; } } catch (_) {} return false; }

  /* ---------- État ---------- */
  let S = { items: [], tax: {}, at: 0 }, filter = '', editing = null, photos = [];
  const net = $('#aaNet');
  function showNet() { const on = navigator.onLine; net.classList.toggle('off', !on); net.querySelector('b').textContent = on ? 'En ligne' : 'Hors ligne'; }
  addEventListener('online', () => { showNet(); sync(); }); addEventListener('offline', showNet);

  async function showQueue() { const q = await qAll(); const b = $('#aaQueue'); b.hidden = !q.length; b.textContent = q.length ? (q.length + ' modification' + (q.length > 1 ? 's' : '') + ' en attente d\'envoi' + (navigator.onLine ? ' · envoi en cours…' : ' · elles partiront dès que le réseau revient')) : ''; return q; }

  let syncing = false;
  async function sync(fresh = false) {
    if (syncing) return; syncing = true;
    try {
      for (const op of await qAll()) {
        if (!navigator.onLine) break;
        try {
          if (op.kind === 'delete') await api('site/annonce-delete', { id: op.id });
          else { const r = await api('site/annonce-save', op.data); toast('✅ « ' + (op.data.title || 'Annonce') + ' » ' + (r.status === 'publish' ? 'est en ligne' : 'est enregistrée')); }
          await qDel(op.qid);
        } catch (e) { if (e instanceof Offline) break; if (e.auth) { toast(e.message, 6000); break; } toast('Non envoyé : ' + (op.data?.title || 'annonce') + ' · ' + e.message, 7000); op.error = e.message; await tx('queue', 'readwrite', s => s.put(op)); break; }
      }
      await showQueue();
      if (navigator.onLine) { try { const r = await api('site/ann-list', { fresh }); S = { items: r.items, tax: r.tax, at: r.at }; await kvSet('state', S); } catch (e) { if (!(e instanceof Offline)) $('#aaSync').textContent = e.message; } }
    } finally { syncing = false; render(); }
  }

  /* ---------- Liste ---------- */
  const tone = s => ['En vente', 'A louer', 'À louer'].includes(s) ? 'ok' : s === 'Sous promesse' ? 'warn' : ['Vendu', 'Loué'].includes(s) ? 'neutral' : 'todo';
  async function render() {
    const q = await qAll(); const pend = {}; q.forEach(o => { if (o.id) pend[o.id] = o.kind; });
    const news = q.filter(o => o.kind === 'save' && !o.id);
    $('#aaSync').textContent = S.at ? S.items.length + ' annonce' + (S.items.length > 1 ? 's' : '') + ' · à jour du ' + new Date(S.at * 1000).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : (navigator.onLine ? 'Chargement…' : 'Hors ligne : aucune donnée encore enregistrée sur ce téléphone');
    const counts = {}; S.items.forEach(a => counts[a.statut] = (counts[a.statut] || 0) + 1);
    const chips = $('#aaChips'); chips.textContent = '';
    [['', 'Toutes', S.items.length], ...Object.entries(counts).map(([k, v]) => [k, k, v])].forEach(([k, l, n]) => { const b = document.createElement('button'); b.type = 'button'; b.className = 'hv-chip'; b.setAttribute('aria-pressed', String(filter === k)); b.innerHTML = '<span></span> <b></b>'; b.firstChild.textContent = l; b.lastChild.textContent = n; b.onclick = () => { filter = k; render(); }; chips.appendChild(b); });
    const term = ($('#aaQ').value || '').trim().toLowerCase();
    const box = $('#aaCards'); box.textContent = '';
    const card = (a, local) => {
      const el = document.createElement('article'); el.className = 'aa-card' + (local ? ' local' : '');
      const ph = document.createElement('div'); ph.className = 'aa-ph'; if (a.photo) { const im = document.createElement('img'); im.loading = 'lazy'; im.alt = ''; im.onerror = () => { im.remove(); ph.textContent = '🏠'; }; im.src = a.photo; ph.appendChild(im); } else ph.textContent = '🏠';
      const bd = document.createElement('div'); bd.className = 'aa-bd';
      const t = document.createElement('b'); t.textContent = a.title; const m = document.createElement('small'); m.textContent = [a.loc, a.date ? 'publiée le ' + new Date(a.date).toLocaleDateString('fr-FR') : ''].filter(Boolean).join(' · ');
      const cs = document.createElement('div'); cs.className = 'aa-cs';
      const chip = (txt, c) => { const s = document.createElement('span'); s.className = 'chip ' + c; s.textContent = txt; cs.appendChild(s); };
      if (local) chip('En attente d\'envoi', 'warn'); else { chip(a.statut, tone(a.statut)); chip(a.status === 'publish' ? 'En ligne' : 'Brouillon', a.status === 'publish' ? 'ok' : 'todo'); if (pend[a.id]) chip(pend[a.id] === 'delete' ? 'Suppression en attente' : 'Modification en attente', 'warn'); }
      bd.append(t, m, cs); el.append(ph, bd);
      el.onclick = () => local ? openForm(null, local) : (go('modifier-' + a.id));
      return el;
    };
    news.forEach(o => box.appendChild(card({ title: o.data.title, loc: '', photo: typeof o.data.photos?.[0] === 'string' ? o.data.photos[0] : '' }, o)));
    let n = news.length;
    S.items.filter(a => (!filter || a.statut === filter) && (!term || (a.title + ' ' + a.loc + ' ' + a.statut).toLowerCase().includes(term))).forEach(a => { box.appendChild(card(a)); n++; });
    $('#aaEmpty').hidden = n > 0;
  }
  $('#aaQ').addEventListener('input', render);

  /* ---------- Formulaire ---------- */
  const f = $('#naF'), err = f.querySelector('.form-error'), thumbs = $('#naThumbs'), prev = $('#naPrev');
  const val = n => { const el = f.querySelector('[name="' + n + '"]:checked') || f.querySelector('[name="' + n + '"]:not([type=radio]):not([type=checkbox])'); return el ? el.value.trim() : ''; };
  const on = n => !!f.querySelector('[name="' + n + '"]:checked');
  const num = n => parseFloat(val(n).replace(/\s/g, '').replace(',', '.')) || 0;
  const eur = (v, d = 0) => v.toLocaleString('fr-FR', { minimumFractionDigits: d, maximumFractionDigits: d }) + ' €';
  const flags = () => ({ vente: val('tx') === 'vente', location: val('tx') === 'location', copro: on('copro'), acquereur: val('hono_charge') === 'acquereur', encadre: on('encadre'), procedure: on('procedure'), dpe: val('dpe_cas') === 'dpe', logement: on('logement') });
  const shown = el => { let x = el; while (x && x !== f) { if (x.hidden) return false; x = x.parentElement; } return true; };
  function syncForm() {
    const F = flags();
    $$('[data-show]', f).forEach(el => { el.hidden = !el.dataset.show.split(' ').every(t => F[t]); });
    const h = $('#naHors'); if (h) { const p = num('prix'), pc = num('hono_pct'); h.textContent = p && pc ? 'Prix hors honoraires : ' + eur(Math.round(p / (1 + pc / 100))) : ''; }
    const L = [];
    if (F.location) { if (num('loyer')) L.push('Loyer ' + eur(num('loyer')) + '/mois charges comprises, dont ' + eur(num('charges')) + ' de charges'); if (num('depot')) L.push('Dépôt de garantie : ' + eur(num('depot'))); if (num('hono_loc')) L.push('Honoraires locataire : ' + eur(num('hono_loc')) + ' dont ' + eur(num('hono_edl')) + ' d\'état des lieux'); L.push(on('meuble') ? 'Location meublée' : 'Location vide'); if (F.encadre && num('loyer_ref')) L.push('Encadrement : référence ' + eur(num('loyer_ref'), 2) + '/m², majoré ' + eur(num('loyer_maj'), 2) + '/m²'); }
    else if (num('prix')) L.push(F.acquereur ? 'Prix ' + eur(num('prix')) + ' honoraires inclus · ' + (num('hono_pct') || '?') + ' % TTC acquéreur' : 'Prix ' + eur(num('prix')) + ' · honoraires vendeur');
    if (num('surface')) L.push('Surface habitable ' + val('surface') + ' m²' + (F.vente && F.copro && val('carrez') ? ' · Carrez ' + val('carrez') + ' m²' : ''));
    if (F.copro && num('lots')) L.push('Copropriété de ' + num('lots') + ' lots · charges annuelles ' + eur(num('charges_copro')));
    if (F.dpe && val('dpe')) { L.push('CLASSE ÉNERGIE ' + val('dpe') + ' · CLASSE CLIMAT ' + (val('ges') || '?')); if (F.logement && ['F', 'G'].includes(val('dpe'))) L.push('Logement à consommation énergétique excessive : classe ' + val('dpe')); }
    L.push('Risques : www.georisques.gouv.fr');
    prev.textContent = ''; L.forEach(t => { const li = document.createElement('li'); li.textContent = t; prev.appendChild(li); });
  }
  f.addEventListener('input', syncForm); f.addEventListener('change', syncForm);

  const resize = file => new Promise((ok, ko) => { const rd = new FileReader(); rd.onerror = () => ko(new Error('Photo illisible')); rd.onload = () => { const img = new Image(); img.onload = () => { const s = Math.min(1, 1600 / Math.max(img.width, img.height)); const c = document.createElement('canvas'); c.width = Math.round(img.width * s); c.height = Math.round(img.height * s); c.getContext('2d').drawImage(img, 0, 0, c.width, c.height); ok(c.toDataURL('image/jpeg', 0.82)); }; img.onerror = () => ko(new Error('Format de photo non pris en charge (JPEG ou PNG)')); img.src = rd.result; }; rd.readAsDataURL(file); });
  function drawThumbs() { thumbs.textContent = ''; photos.forEach((p, i) => { const d = document.createElement('div'); d.className = 'na-th' + (i === 0 ? ' main' : ''); const im = document.createElement('img'); im.src = typeof p === 'string' ? p : p.url; im.alt = ''; d.appendChild(im);
      const t = document.createElement('span'); t.className = 'na-th-t'; t.textContent = i === 0 ? 'Principale' : 'Mettre en premier'; if (i) t.onclick = () => { photos.unshift(photos.splice(i, 1)[0]); drawThumbs(); }; d.appendChild(t);
      const x = document.createElement('button'); x.type = 'button'; x.textContent = '✕'; x.setAttribute('aria-label', 'Retirer la photo'); x.onclick = () => { photos.splice(i, 1); drawThumbs(); }; d.appendChild(x); thumbs.appendChild(d); }); }
  $('#na-ph').addEventListener('change', async e => { for (const file of [...e.target.files]) { if (photos.length >= 15) break; try { photos.push(await resize(file)); } catch (x) { toast(x.message); } } e.target.value = ''; drawThumbs(); });

  function fill(d) {
    f.reset(); err.hidden = true;
    Object.entries(d || {}).forEach(([k, v]) => {
      if (k === 'photos' || v === null || v === undefined) return;
      const els = $$('[name="' + k + '"]', f); if (!els.length) return;
      if (k === 'features') { els.forEach(el => el.checked = (v || []).includes(el.value)); return; }
      els.forEach(el => { if (el.type === 'radio') el.checked = el.value === String(v); else if (el.type === 'checkbox') el.checked = v === '1' || v === true; else el.value = v; });
    });
    if (d && !('logement' in d)) { const l = f.querySelector('[name=logement]'); if (l) l.checked = true; }
    if (d && d.status) { const ps = f.querySelector('[name=publier]'); if (ps) ps.checked = d.status === 'publish'; }
    photos = (d?.photos || []).slice(); drawThumbs(); syncForm();
  }
  function collect() { const o = {}; new FormData(f).forEach((v, k) => { if (k === 'features') (o.features = o.features || []).push(v); else o[k] = v; });
    ['logement', 'meuble', 'encadre', 'copro', 'procedure', 'publier'].forEach(k => { if (!(k in o)) o[k] = ''; });
    o.photos = photos.map(p => typeof p === 'string' ? p : { id: p.id }); if (!o.id) delete o.id; return o; }

  async function openForm(id, localOp) {
    editing = { id: id || null, op: localOp || null };
    $('#aaList').hidden = true; $('#aaForm').hidden = false; window.scrollTo(0, 0);
    $('#aaFormH').textContent = id ? 'Modifier l\'annonce' : (localOp ? 'Annonce en attente' : 'Nouvelle annonce');
    $$('[data-edit-only]', f).forEach(el => el.hidden = !id); $('#aaDel').hidden = !(id || localOp);
    f.querySelector('.na-bar .na-check').hidden = false;
    if (localOp) { fill(localOp.data); return; }
    if (!id) { fill({ copro: '1', encadre: '1', logement: '1', publier: '1', tx: 'vente', dpe_cas: 'dpe', charges_mode: 'provision' }); return; }
    const cached = await kvGet('item-' + id);
    if (cached) fill(cached);
    try { const r = await api('site/ann-get', { id }); await kvSet('item-' + id, r.item); if (editing?.id === id) fill(r.item); }
    catch (e) { if (!cached) { toast(e instanceof Offline ? 'Hors ligne : cette annonce n\'a pas encore été ouverte sur ce téléphone.' : e.message, 5000); go(''); } }
  }
  function closeForm() { editing = null; $('#aaForm').hidden = true; $('#aaList').hidden = false; render(); }

  f.addEventListener('submit', async e => {
    e.preventDefault(); err.hidden = true;
    const miss = [];
    $$('label', f).forEach(l => { if (!/\*\s*$/.test(l.textContent) || !shown(l)) return; const inp = l.htmlFor ? document.getElementById(l.htmlFor) : null; if (inp && !inp.value.trim()) miss.push(l.textContent.replace(/\s*\*\s*$/, '')); });
    $$('.na-l', f).forEach(s => { if (!/\*\s*$/.test(s.textContent) || !shown(s)) return; const g = s.parentElement.querySelector('[role=radiogroup]'); if (g && !g.querySelector('input:checked')) miss.push(s.textContent.replace(/\s*\*\s*$/, '')); });
    if (!photos.length) miss.push('au moins une photo');
    if (miss.length) { err.hidden = false; err.textContent = 'À compléter : ' + miss.join(', ') + '.'; err.scrollIntoView({ block: 'center', behavior: 'smooth' }); return; }
    const data = collect(); const b = $('#naGo'); b.disabled = true; b.textContent = 'Enregistrement…';
    try {
      if (editing?.op) await qDel(editing.op.qid);
      const r = await api('site/annonce-save', data);
      if (data.id) await kvSet('item-' + data.id, null);
      toast(r.status === 'publish' ? '✅ Annonce en ligne' + (r.meta_ok ? '' : ' (prix et caractéristiques à compléter dans WordPress)') : '✅ Annonce enregistrée en brouillon', 5000);
      go(''); sync();
    } catch (x) {
      if (x instanceof Offline) { await qAdd({ kind: 'save', id: data.id || null, data, ts: Date.now() }); toast('📴 Pas de réseau : l\'annonce est gardée sur le téléphone et partira automatiquement.', 6000); go(''); await showQueue(); }
      else { err.hidden = false; err.textContent = x.message; }
    } finally { b.disabled = false; b.textContent = 'Enregistrer l\'annonce'; }
  });

  const del = $('#aaDel');
  del.addEventListener('click', async () => {
    if (!del._armed) { del._armed = true; del.textContent = 'Confirmer la suppression'; del.classList.add('armed'); setTimeout(() => { del._armed = false; del.textContent = 'Supprimer'; del.classList.remove('armed'); }, 3500); return; }
    del._armed = false; del.textContent = 'Supprimer';
    if (editing?.op && !editing.id) { await qDel(editing.op.qid); toast('Annonce en attente supprimée'); go(''); return; }
    const id = editing.id;
    try { await api('site/annonce-delete', { id }); toast('🗑 Annonce mise à la corbeille (récupérable dans WordPress)'); }
    catch (x) { if (x instanceof Offline) { await qAdd({ kind: 'delete', id, ts: Date.now() }); toast('📴 Suppression enregistrée : elle sera faite dès que le réseau revient.'); } else { toast(x.message, 5000); return; } }
    go(''); sync();
  });

  /* ---------- Navigation ---------- */
  function go(h) { if (location.hash.slice(1) === h) route(); else location.hash = h; }
  document.addEventListener('click', e => { const g = e.target.closest('[data-go]'); if (!g) return; e.preventDefault(); go(g.dataset.go === 'new' ? 'nouvelle' : ''); });
  function route() { const h = location.hash.slice(1); if (h === 'nouvelle') openForm(null); else if (/^modifier-\d+$/.test(h)) openForm(Number(h.split('-')[1])); else closeForm(); }
  addEventListener('hashchange', route);

  /* ---------- Installation (Android / ordinateur) ---------- */
  let deferred = null; const ib = $('#aaInstall');
  addEventListener('beforeinstallprompt', e => { e.preventDefault(); deferred = e; ib.hidden = false; });
  ib.addEventListener('click', async () => { if (!deferred) return; deferred.prompt(); await deferred.userChoice; deferred = null; ib.hidden = true; });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/annonces/sw.js', { scope: '/annonces/' }).catch(() => {});

  (async () => { showNet(); const st = await kvGet('state'); if (st) S = st; route(); render(); showQueue(); sync(true); })();
})();
