/* Honoraires › Immeubles : vues de colonnes, colonnes figées, alertes, filtres rapides, fiche immeuble et cartes sur mobile.
   Toutes les saisies passent par les cellules du tableau d'origine (même enregistrement partagé, même journal des modifications). */
(function () {
  var C = window.GID_C || {}, P = C.prefs || {};
  var MOBILE = window.matchMedia('(max-width: 760px)');
  function h(tag, cls, txt) { var x = document.createElement(tag); if (cls) x.className = cls; if (txt != null) x.textContent = String(txt); return x; }
  function num(v) { return parseFloat(String(v == null ? '' : v).replace(/\s|€/g, '').replace(',', '.')) || 0; }
  var EUR = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });
  function eur(v) { return v ? EUR.format(Math.round(v)) + ' €' : '–'; }
  function fdate(s) { var m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? m[3] + '/' + m[2] + '/' + m[1] : ''; }
  function code5(v) { var x = Math.round(num(v)); return x > 0 ? String(x).padStart(5, '0') : ''; }
  function savePref(k, v) { P[k] = v; try { fetch('/api/prefs/set', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': C.csrf }, body: JSON.stringify({ k: k, v: v }) }); } catch (e) {} }
  var TODAY = new Date().toISOString().slice(0, 10);
  function days(a, b) { return Math.round((new Date(b) - new Date(a)) / 864e5); }

  /* ---------- Vues ---------- */
  var VIEWS = [
    ['ess', 'Essentiel', ['port', 'num', 'imm', 'lots', 'h2526', 'h2627v', 'h2627e', '_evol', '_lot', 'dateAG']],
    ['ag', 'AG et prestations', ['port', 'num', 'imm', 'plage', 'fin', 'cs1', 'cs2', 'visite', 'coutAG', 'dateAG', 'datePV']],
    ['bud', 'Budgets', ['port', 'num', 'imm', 'h2627v', 'h2728', 'h2829', '_evol']],
    ['htv', 'Travaux votés', ['port', 'num', 'imm', 'dateAG', '_pv0', '_pv1']],
    ['all', 'Tout', null]
  ];
  var GROUP = { h2526: 'g1', h2627v: 'g1', h2627e: 'g1', _evol: 'g1', _lot: 'g1', plage: 'g2', fin: 'g2', cs1: 'g2', cs2: 'g2', visite: 'g2', coutAG: 'g2', dateAG: 'g2', datePV: 'g2', h2728: 'g3', h2829: 'g3', _pv0: 'g4', _pv1: 'g4' };
  var view = (VIEWS.some(function (v) { return v[0] === P.hono_view; }) ? P.hono_view : 'ess');
  var viewCss = h('style'); viewCss.id = 'hvViewCss'; document.head.appendChild(viewCss);
  function applyView() {
    var v = VIEWS.filter(function (x) { return x[0] === view; })[0], keep = v && v[2];
    document.querySelectorAll('.hv-views button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.v === view)); });
    var cb0 = document.querySelector('.ha-colbtn'); if (cb0) cb0.hidden = !!keep;
    if (!keep) { viewCss.textContent = ''; requestAnimationFrame(stick); return; }
    var cols = {}; document.querySelectorAll('#immTbl thead th[data-col]').forEach(function (th) { cols[th.dataset.col] = 1; });
    viewCss.textContent = Object.keys(cols).filter(function (k) { return keep.indexOf(k) < 0; }).map(function (k) { return '#immTbl [data-col="' + k.replace(/[^\w-]/g, '') + '"]{display:none!important}'; }).join('\n');
    document.querySelectorAll('.hv-views button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.v === view)); });
    var cb = document.querySelector('.ha-colbtn'); if (cb) cb.hidden = view !== 'all';
    requestAnimationFrame(stick);
  }

  /* ---------- Colonnes figées à gauche (n°, immeuble) ---------- */
  function stick() {
    var t = document.getElementById('immTbl'); if (!t) return;
    t.querySelectorAll('.hv-stick').forEach(function (c) { c.classList.remove('hv-stick', 'hv-stick-last'); c.style.left = ''; });
    if (MOBILE.matches) return;
    var hr = t.querySelector('thead tr'); if (!hr) return;
    var vis = [].filter.call(hr.children, function (th) { return th.dataset.col && th.offsetParent !== null && getComputedStyle(th).display !== 'none'; });
    var lead = []; for (var i = 0; i < vis.length; i++) { if (['port', 'num', 'imm', 'gest'].indexOf(vis[i].dataset.col) >= 0) lead.push(vis[i].dataset.col); else break; }
    if (lead.indexOf('imm') < 0) return;
    lead = lead.slice(0, lead.indexOf('imm') + 1);
    var left = 0, widths = {};
    lead.forEach(function (k) { var th = hr.querySelector('[data-col="' + k + '"]'); widths[k] = th.getBoundingClientRect().width; });
    lead.forEach(function (k, j) {
      t.querySelectorAll('[data-col="' + k + '"]').forEach(function (c) { c.classList.add('hv-stick'); if (j === lead.length - 1) c.classList.add('hv-stick-last'); c.style.left = left + 'px'; });
      left += widths[k];
    });
  }

  /* ---------- Alertes par immeuble ---------- */
  function rowData(tr) {
    var d = {}; tr.querySelectorAll('input[data-k]').forEach(function (i) { d[i.dataset.k] = i.value; });
    d._i = +tr.dataset.i; d._code = code5(d.num);
    ['_pv0', '_pv1', '_evol', '_lot'].forEach(function (k) { var td = tr.querySelector('td[data-col="' + k + '"]'); d[k] = td ? td.textContent.replace(/i$/, '').trim() : ''; });
    return d;
  }
  function flags(d) {
    var f = [], hv = num(d.h2627v), he = num(d.h2627e), h1 = num(d.h2526), lost = num(d.perdus) > 0 && !hv;
    if (lost) return [['lost', 'Perdu']];
    if (d.dateAG) f.push(['ok', 'AG ' + fdate(d.dateAG).slice(0, 5)]);
    if (d.dateAG && !d.datePV && days(d.dateAG, TODAY) > 21) f.push(['crit', 'PV en retard']);
    else if (d.dateAG && !d.datePV) f.push(['warn', 'PV à remettre']);
    if (he > 0 && he < hv - 1) f.push(['crit', 'Voté ' + EUR.format(Math.round(he - hv)) + ' €']);
    if (hv && h1 && hv <= h1) f.push(['warn', 'Sans hausse']);
    return f;
  }
  var FILTERS = [
    ['under', 'Voté sous le proposé', 'crit', function (d) { var hv = num(d.h2627v), he = num(d.h2627e); return he > 0 && he < hv - 1; }],
    ['pv', 'PV en retard', 'crit', function (d) { return d.dateAG && !d.datePV && days(d.dateAG, TODAY) > 21; }],
    ['flat', 'Sans hausse', 'warn', function (d) { var hv = num(d.h2627v), h1 = num(d.h2526); return hv && h1 && hv <= h1; }],
    ['noag', 'AG à tenir', '', function (d) { return !d.dateAG && !(num(d.perdus) > 0 && !num(d.h2627v)); }]
  ];
  var only = '';

  var busy = false;
  function decorateRows() {
    var t = document.getElementById('immTbl'); if (!t) return;
    busy = true;
    try {
      var counts = {}; FILTERS.forEach(function (f) { counts[f[0]] = 0; });
      t.querySelectorAll('tbody tr').forEach(function (tr) {
        var d = rowData(tr), fl = flags(d);
        FILTERS.forEach(function (f) { if (f[3](d)) counts[f[0]]++; });
        var cell = tr.querySelector('td[data-col="imm"]');
        if (cell) {
          var box = cell.querySelector('.hv-flags'); if (!box) { box = h('span', 'hv-flags'); cell.appendChild(box); }
          var html = fl.map(function (x) { return x[0] + x[1]; }).join('|');
          if (box.dataset.k !== html) { box.dataset.k = html; box.textContent = ''; fl.forEach(function (x) { box.appendChild(h('span', 'hv-tag ' + x[0], x[1])); }); }
          if (!cell.querySelector('.hv-open')) { var b = h('button', 'hv-open', '›'); b.type = 'button'; b.title = 'Ouvrir la fiche de l’immeuble'; b.setAttribute('aria-label', 'Ouvrir la fiche'); cell.appendChild(b); }
        }
        tr.classList.toggle('hv-crit', fl.some(function (x) { return x[0] === 'crit'; }));
        var show = !only || FILTERS.filter(function (f) { return f[0] === only; })[0][3](d);
        tr.classList.toggle('hv-off', !show);
      });
      renderChips(counts);
    } finally { busy = false; }
    if (MOBILE.matches) renderCards();
    requestAnimationFrame(stick);
  }
  function renderChips(counts) {
    var bar = document.querySelector('.hv-chips'); if (!bar) return;
    bar.textContent = '';
    var all = h('button', 'hv-chip', 'Tous'); all.type = 'button'; all.setAttribute('aria-pressed', String(!only)); all.addEventListener('click', function () { only = ''; decorateRows(); }); bar.appendChild(all);
    FILTERS.forEach(function (f) {
      var b = h('button', 'hv-chip ' + f[2]); b.type = 'button'; b.setAttribute('aria-pressed', String(only === f[0]));
      b.appendChild(h('b', null, counts[f[0]])); b.appendChild(document.createTextNode(' ' + f[1]));
      b.addEventListener('click', function () { only = only === f[0] ? '' : f[0]; decorateRows(); }); bar.appendChild(b);
    });
  }

  /* ---------- Fiche immeuble (panneau latéral, plein écran sur mobile) ---------- */
  var drawer = null, curI = null;
  var SECTIONS = [
    ['Honoraires', [['h2627v', 'Hon. 26-27 à voter', 'eur'], ['h2627e', 'Hon. 26-27 voté', 'eur'], ['h2526', 'Hon. 25-26 voté', 'eur']]],
    ['Assemblée générale', [['dateAG', 'AG tenue le', 'date'], ['datePV', 'Remise PV', 'date'], ['plage', 'Plage horaire AG', 'txt'], ['fin', 'Heure fin AG', 'txt'], ['coutAG', 'Coût AG supp. TTC', 'eur']]],
    ['Prestations incluses', [['cs1', 'CS 1h', 'num'], ['cs2', 'CS 2h', 'num'], ['visite', 'Visites 1h', 'num']]],
    ['Budgets', [['h2728', 'Budget 27-28', 'eur'], ['h2829', 'Budget 28-29', 'eur']]],
    ['Immeuble', [['imm', 'Adresse', 'txt'], ['num', 'N°', 'num'], ['port', 'Portefeuille', 'txt'], ['gest', 'Gestionnaire', 'txt'], ['lots', 'Lots', 'num'], ['perdus', 'Lots perdus', 'num'], ['archive', 'Archive', 'txt']]]
  ];
  function rowByI(i) { return document.querySelector('#immTbl tbody tr[data-i="' + i + '"]'); }
  function setField(i, k, v) {
    var tr = rowByI(i); if (!tr) return false;
    var inp = tr.querySelector('input[data-k="' + k + '"]'); if (!inp || inp.readOnly) return false;
    inp.value = v; inp.dispatchEvent(new Event('input', { bubbles: true }));
    if (inp._tx) inp._tx.value = fdate(v);
    return true;
  }
  function frToIso(v) { v = String(v || '').trim(); if (!v) return ''; var m = v.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2,4})$/); if (m) { var y = m[3].length === 2 ? '20' + m[3] : m[3]; return y + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[1]).slice(-2); } return /^\d{4}-\d{2}-\d{2}$/.test(v) ? v : null; }
  function openFiche(i) {
    var tr = rowByI(i); if (!tr) return;
    curI = i; var d = rowData(tr), fl = flags(d);
    if (!drawer) {
      drawer = h('aside', 'hv-drawer'); drawer.setAttribute('role', 'dialog'); drawer.setAttribute('aria-modal', 'false');
      document.body.appendChild(drawer);
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && drawer.classList.contains('open') && !e.target.closest('#immTbl')) closeFiche(); });
    }
    drawer.textContent = '';
    var hd = h('div', 'hv-dh'), tt = h('div');
    tt.appendChild(h('div', 'hv-eye', (d._code || 'Sans n°') + ' · ' + (d.port || '?') + ' · ' + (num(d.lots) || 0) + ' lots'));
    tt.appendChild(h('h2', null, String(d.imm || '').trim() || 'Immeuble'));
    var x = h('button', 'hv-x', MOBILE.matches ? '‹ Immeubles' : '✕'); x.type = 'button'; x.setAttribute('aria-label', 'Fermer la fiche'); x.addEventListener('click', closeFiche);
    if (MOBILE.matches) { hd.appendChild(x); hd.appendChild(tt); } else { hd.appendChild(tt); hd.appendChild(x); }
    drawer.appendChild(hd);
    var body = h('div', 'hv-db');
    var hv = num(d.h2627v), h1 = num(d.h2526), he = num(d.h2627e), mx = Math.max(hv, h1, he, 1);
    var top = h('div', 'hv-big'); top.appendChild(h('b', null, eur(hv)));
    if (h1) { var ev = (hv / h1 - 1) * 100; top.appendChild(h('span', 'hv-pill ' + (ev > 0.05 ? 'up' : ev < -0.05 ? 'dn' : 'flat'), (ev > 0 ? '+' : '') + ev.toFixed(1).replace('.', ',') + ' %')); }
    top.appendChild(h('small', null, 'à voter 2026-2027' + (d._lot && d._lot !== '–' ? ' · ' + d._lot + ' / lot' : '')));
    body.appendChild(top);
    var bars = h('div', 'hv-bars');
    [['25-26 voté', h1, ''], ['26-27 à voter', hv, ''], ['26-27 voté', he, 'v']].forEach(function (b) { if (!b[1] && b[2]) return; var r = h('div', 'hv-bar ' + b[2]); r.appendChild(h('span', null, b[0])); var tr2 = h('span', 'hv-tr'), fi = h('i'); fi.style.width = (b[1] / mx * 100) + '%'; tr2.appendChild(fi); r.appendChild(tr2); r.appendChild(h('em', null, eur(b[1]))); bars.appendChild(r); });
    body.appendChild(bars);
    if (fl.length) { var fb = h('div', 'hv-flags big'); fl.forEach(function (f) { fb.appendChild(h('span', 'hv-tag ' + f[0], f[1])); }); body.appendChild(fb); }
    if ((d._pv0 && d._pv0 !== '–') || (d._pv1 && d._pv1 !== '–')) { var hp = h('p', 'hv-htv'); hp.textContent = 'Honoraires travaux votés : ' + [d._pv0 && d._pv0 !== '–' ? d._pv0 + ' (exercice en cours)' : '', d._pv1 && d._pv1 !== '–' ? d._pv1 + ' (exercice précédent)' : ''].filter(Boolean).join(' · '); body.appendChild(hp); }
    SECTIONS.forEach(function (s) {
      var sec = h('section', 'hv-sec'); sec.appendChild(h('h3', null, s[0]));
      var g = h('div', 'hv-fields');
      s[1].forEach(function (f) {
        var k = f[0], lab = h('label', 'hv-f'), id = 'hvf-' + k;
        lab.htmlFor = id; lab.appendChild(h('span', null, f[1]));
        var inp = h('input'); inp.id = id; inp.dataset.k = k;
        var v = d[k] || ''; inp.value = f[2] === 'date' ? fdate(v) : (f[2] === 'eur' && v !== '' ? String(Math.round(num(v) * 100) / 100) : v);
        if (f[2] === 'num' || f[2] === 'eur') inp.inputMode = 'decimal'; if (f[2] === 'date') { inp.inputMode = 'numeric'; inp.placeholder = 'JJ/MM/AAAA'; }
        if (k === 'imm') lab.classList.add('wide');
        if (C.ro) inp.readOnly = true;
        inp.addEventListener('change', function () {
          var val = inp.value.trim();
          if (f[2] === 'date') { var iso = frToIso(val); if (iso === null) { inp.classList.add('bad'); return; } inp.classList.remove('bad'); val = iso; }
          if (setField(curI, k, val)) { inp.classList.add('saved'); setTimeout(function () { inp.classList.remove('saved'); }, 900); refreshHead(); }
        });
        inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        lab.appendChild(inp); g.appendChild(lab);
      });
      sec.appendChild(g); body.appendChild(sec);
    });
    body.appendChild(h('p', 'hv-note', C.ro ? 'Lecture seule.' : 'Chaque modification est enregistrée pour toute l’équipe et apparaît dans l’onglet Modifications.'));
    drawer.appendChild(body);
    drawer.classList.add('open'); document.body.classList.add('hv-has-drawer');
    document.querySelectorAll('#immTbl tbody tr.hv-sel').forEach(function (r) { r.classList.remove('hv-sel'); }); tr.classList.add('hv-sel');
  }
  function refreshHead() { if (curI == null) return; var y = drawer.querySelector('.hv-db').scrollTop; openFiche(curI); drawer.querySelector('.hv-db').scrollTop = y; }
  function closeFiche() { if (!drawer) return; drawer.classList.remove('open'); document.body.classList.remove('hv-has-drawer'); curI = null; document.querySelectorAll('#immTbl tbody tr.hv-sel').forEach(function (r) { r.classList.remove('hv-sel'); }); }

  /* ---------- Cartes sur mobile (lues dans les lignes du tableau : aucune donnée dupliquée) ---------- */
  function renderCards() {
    var host = document.getElementById('hvCards'); if (!host) return;
    host.textContent = '';
    var trs = [].filter.call(document.querySelectorAll('#immTbl tbody tr'), function (tr) { return !tr.classList.contains('hv-off'); });
    trs.forEach(function (tr) {
      var d = rowData(tr), fl = flags(d), hv = num(d.h2627v), h1 = num(d.h2526);
      var c = h('button', 'hv-card' + (fl.some(function (x) { return x[0] === 'crit'; }) ? ' crit' : '')); c.type = 'button'; c.dataset.i = d._i;
      var l1 = h('span', 'hv-c1'); var t = h('span'); var b = h('b'); b.appendChild(h('span', 'hv-code', d._code)); b.appendChild(document.createTextNode(String(d.imm || '').trim())); t.appendChild(b);
      t.appendChild(h('small', null, (d.port || '?') + ' · ' + (num(d.lots) || 0) + ' lots' + (d._lot && d._lot !== '–' ? ' · ' + d._lot + ' / lot' : ''))); l1.appendChild(t); l1.appendChild(h('span', 'hv-go', '›')); c.appendChild(l1);
      var l2 = h('span', 'hv-c2'); l2.appendChild(h('strong', null, eur(hv)));
      if (h1) { var ev = (hv / h1 - 1) * 100; l2.appendChild(h('span', 'hv-pill ' + (ev > 0.05 ? 'up' : ev < -0.05 ? 'dn' : 'flat'), (ev > 0 ? '+' : '') + ev.toFixed(1).replace('.', ',') + ' %')); }
      l2.appendChild(h('small', null, 'à voter 26-27')); c.appendChild(l2);
      var fb = h('span', 'hv-flags'); (fl.length ? fl : [['', 'AG à tenir']]).forEach(function (f) { fb.appendChild(h('span', 'hv-tag ' + f[0], f[1])); }); c.appendChild(fb);
      c.addEventListener('click', function () { openFiche(d._i); });
      host.appendChild(c);
    });
    if (!trs.length) host.appendChild(h('p', 'hv-note', 'Aucun immeuble ne correspond.'));
  }

  /* ---------- Mise en place ---------- */
  function css() {
    var s = h('style');
    s.textContent = [
      '.hv-views{display:inline-flex;gap:3px;padding:3px;border:1px solid var(--border);border-radius:10px;background:var(--surface-2);overflow-x:auto;max-width:100%}',
      '.hv-views button{border:0;background:none;color:var(--text-2);font:inherit;font-size:13px;font-weight:600;padding:6px 12px;border-radius:7px;cursor:pointer;white-space:nowrap}',
      '.hv-views button[aria-pressed="true"]{background:var(--text);color:var(--surface)}',
      '.hv-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 10px}',
      '.hv-chip{border:1px solid var(--border);background:var(--surface);color:var(--text);border-radius:99px;padding:4px 12px;font:inherit;font-size:12.5px;font-weight:600;cursor:pointer}',
      '.hv-chip b{font-variant-numeric:tabular-nums}.hv-chip.crit{border-color:#c0392b;color:#b4412f}.hv-chip.warn{border-color:#c08a2c;color:#a8741d}',
      '.hv-chip[aria-pressed="true"]{background:var(--text);color:var(--surface);border-color:var(--text)}',
      '#immTbl thead th[data-col].hv-g1{box-shadow:inset 0 3px 0 #6f8fb8}#immTbl thead th[data-col].hv-g2{box-shadow:inset 0 3px 0 #c9a66b}#immTbl thead th[data-col].hv-g3{box-shadow:inset 0 3px 0 #5f9a77}#immTbl thead th[data-col].hv-g4{box-shadow:inset 0 3px 0 #a07bb0}',
      '#immTbl .hv-stick{position:sticky;z-index:1;background:var(--surface)}#immTbl thead .hv-stick{z-index:3;background:var(--surface-2)}#immTbl tfoot .hv-stick{background:var(--surface-2)}',
      '#immTbl .hv-stick-last{box-shadow:6px 0 8px -6px rgba(0,0,0,.18)}',
      '#immTbl td[data-col="imm"]:not(.hv-stick){position:relative}#immTbl td[data-col="imm"] input{padding-right:30px}',
      '.hv-flags{display:flex;flex-wrap:wrap;gap:3px;margin:2px 0 0}.hv-flags:empty{display:none}',
      '.hv-tag{font-size:10.5px;font-weight:700;padding:1px 6px;border-radius:5px;white-space:nowrap;background:var(--surface-2);color:var(--text-2)}',
      '.hv-tag.ok{background:rgba(79,122,90,.14);color:#3e7a52}.hv-tag.warn{background:rgba(192,138,44,.15);color:#a8741d}.hv-tag.crit{background:rgba(192,57,43,.13);color:#b4412f}.hv-tag.lost{background:var(--surface-2);color:var(--muted)}',
      '.hv-open{position:absolute;right:4px;top:6px;width:22px;height:22px;border-radius:6px;border:1px solid var(--border);background:var(--surface);color:var(--text-2);font-size:15px;line-height:1;cursor:pointer;display:grid;place-items:center}',
      '.hv-open:hover{border-color:var(--text);color:var(--text)}',
      '#immTbl tr.hv-off{display:none}#immTbl tr.hv-crit td:first-child{box-shadow:inset 3px 0 0 #c0392b}#immTbl tr.hv-sel td{background:var(--accent-soft)!important}',
      '.hv-drawer{position:fixed;top:0;right:0;bottom:0;width:min(420px,100%);z-index:60;background:var(--surface);border-left:1px solid var(--border);box-shadow:-14px 0 34px rgba(0,0,0,.16);transform:translateX(105%);transition:transform .22s;display:flex;flex-direction:column}',
      '.hv-drawer.open{transform:none}@media (prefers-reduced-motion:reduce){.hv-drawer{transition:none}}',
      '.hv-dh{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:18px 20px 14px;border-bottom:1px solid var(--border)}',
      '.hv-dh h2{margin:2px 0 0;font-size:1.25em}.hv-eye{font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums}',
      '.hv-x{border:0;background:none;color:var(--text-2);font-size:18px;cursor:pointer;padding:2px 4px}',
      '.hv-db{flex:1;overflow:auto;padding:16px 20px 30px;display:flex;flex-direction:column;gap:14px}',
      '.hv-big{display:flex;align-items:baseline;flex-wrap:wrap;gap:8px}.hv-big b{font-size:1.9em;font-weight:600;font-variant-numeric:tabular-nums}.hv-big small{color:var(--muted);flex-basis:100%;font-size:12.5px}',
      '.hv-pill{font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:99px}.hv-pill.up{background:rgba(79,122,90,.15);color:#3e7a52}.hv-pill.dn{background:rgba(192,57,43,.13);color:#b4412f}.hv-pill.flat{background:var(--surface-2);color:var(--muted)}',
      '.hv-bars{display:flex;flex-direction:column;gap:6px}.hv-bar{display:grid;grid-template-columns:96px 1fr 84px;gap:8px;align-items:center;font-size:12.5px;color:var(--text-2)}',
      '.hv-tr{height:8px;border-radius:99px;background:var(--surface-2);overflow:hidden}.hv-tr i{display:block;height:100%;background:#c9a66b}.hv-bar.v .hv-tr i{background:#4f7a5a}.hv-bar em{font-style:normal;text-align:right;color:var(--text);font-variant-numeric:tabular-nums}',
      '.hv-flags.big .hv-tag{font-size:12px;padding:3px 9px}.hv-htv{margin:0;font-size:13px;color:var(--text-2)}',
      '.hv-sec{border:1px solid var(--border);border-radius:12px;padding:12px 14px}.hv-sec h3{margin:0 0 10px;font-size:11.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}',
      '.hv-fields{display:grid;grid-template-columns:1fr 1fr;gap:10px}.hv-f{display:flex;flex-direction:column;gap:4px;font-size:12px;color:var(--text-2)}.hv-f.wide{grid-column:1/-1}',
      '.hv-f input{font:inherit;font-size:14.5px;font-weight:600;color:var(--text);background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:8px 10px;width:100%;font-variant-numeric:tabular-nums;transition:border-color .2s,box-shadow .2s}',
      '.hv-f input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}.hv-f input.saved{border-color:#4f7a5a;box-shadow:0 0 0 3px rgba(79,122,90,.2)}.hv-f input.bad{border-color:#c0392b}',
      '.hv-note{margin:0;font-size:12.5px;color:var(--muted)}',
      '#hvCards{display:none}',
      '@media (max-width:760px){',
      '#tab-imm .tbl-wrap,#tab-imm>.note,.hv-views{display:none!important}#hvCards{display:flex;flex-direction:column;gap:10px}',
      '#tab-imm .toolbar{position:sticky;top:0;z-index:5;background:var(--bg);padding:8px 0}.hv-chips{flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px}.hv-chip{flex:none}',
      '.hv-card{display:flex;flex-direction:column;gap:8px;text-align:left;width:100%;padding:14px;border:1px solid var(--border);border-radius:16px;background:var(--surface);color:var(--text);font:inherit;cursor:pointer}',
      '.hv-card.crit{box-shadow:inset 3px 0 0 #c0392b}.hv-c1{display:flex;justify-content:space-between;gap:8px}.hv-c1 b{font-size:15px;font-weight:600;line-height:1.3;display:block}',
      '.hv-code{font-size:12px;color:var(--muted);margin-right:6px;font-variant-numeric:tabular-nums}.hv-c1 small{display:block;color:var(--muted);font-size:12.5px;margin-top:2px}.hv-go{color:var(--muted);font-size:22px;line-height:1}',
      '.hv-c2{display:flex;align-items:baseline;gap:8px}.hv-c2 strong{font-size:1.45em;font-weight:600;font-variant-numeric:tabular-nums}.hv-c2 small{color:var(--muted);font-size:12px}',
      '.hv-drawer{width:100%;border-left:0;transform:translateY(100%)}.hv-dh{flex-direction:column;gap:6px;padding:calc(12px + env(safe-area-inset-top,0px)) 16px 12px}.hv-x{font-size:15px;font-weight:700;color:var(--accent);padding:0}',
      '.hv-db{padding:14px 16px calc(30px + env(safe-area-inset-bottom,0px))}.hv-fields{grid-template-columns:1fr 1fr}.hv-f input{font-size:16px}',
      '}'
    ].join('\n');
    document.head.appendChild(s);
  }
  function setup() {
    var tab = document.getElementById('tab-imm'), bar = tab && tab.querySelector('.toolbar'), t = document.getElementById('immTbl');
    if (!bar || !t || document.querySelector('.hv-views')) return;
    css();
    var vs = h('div', 'hv-views'); vs.setAttribute('role', 'group'); vs.setAttribute('aria-label', 'Vue du tableau');
    VIEWS.forEach(function (v) { var b = h('button', null, v[1]); b.type = 'button'; b.dataset.v = v[0]; b.addEventListener('click', function () { view = v[0]; savePref('hono_view', view); applyView(); }); vs.appendChild(b); });
    var sp = bar.querySelector('.spacer'); bar.insertBefore(vs, sp || null);
    var chips = h('div', 'hv-chips'); bar.parentNode.insertBefore(chips, bar.nextSibling);
    var cards = h('div'); cards.id = 'hvCards'; var wrap = tab.querySelector('.tbl-wrap'); wrap.parentNode.insertBefore(cards, wrap.nextSibling);
    t.addEventListener('click', function (e) { var b = e.target.closest('.hv-open'); if (b) { e.preventDefault(); openFiche(+b.closest('tr').dataset.i); } });
    var tm = null;
    new MutationObserver(function () { if (busy) return; clearTimeout(tm); tm = setTimeout(function () { groupHeads(); applyView(); decorateRows(); }, 60); }).observe(t, { childList: true, subtree: true });
    t.addEventListener('input', function () { clearTimeout(tm); tm = setTimeout(decorateRows, 250); });
    MOBILE.addEventListener ? MOBILE.addEventListener('change', function () { decorateRows(); applyView(); }) : 0;
    window.addEventListener('resize', function () { requestAnimationFrame(stick); });
    var navB = document.querySelector('nav.tabs [data-tab="imm"]'); if (navB) navB.addEventListener('click', function () { setTimeout(function () { applyView(); stick(); }, 30); });
    groupHeads(); applyView(); decorateRows();
  }
  function groupHeads() { document.querySelectorAll('#immTbl thead th[data-col]').forEach(function (th) { var g = GROUP[th.dataset.col]; if (g) th.classList.add('hv-' + g); }); }
  function start() { setTimeout(setup, 120); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();
