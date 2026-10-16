/* Chemini · Honoraires : bloc « Honoraires annexes » (tableau de bord + détail), colonne « Hon. travaux GID »
   dans l'onglet Immeubles, choix des colonnes affichées mémorisé pour chaque utilisateur.
   Exercice comptable : du 1er avril au 31 mars. Le script du document étant encapsulé, on travaille sur le tableau rendu. */
(function () {
  'use strict';
  var C = window.GID_C || {}, A = C.annexes, P = C.prefs || {};
  var EUR = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  var PCT = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1, minimumFractionDigits: 1 });
  var MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

  function savePref(k, v) {
    P[k] = v;
    try {
      fetch('/api/prefs/set', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': C.csrf }, body: JSON.stringify({ k: k, v: v }) });
    } catch (e) {}
  }
  function h(tag, cls, txt) { var x = document.createElement(tag); if (cls) x.className = cls; if (txt != null) x.textContent = String(txt); return x; }
  function num(v) { return typeof v === 'number' && isFinite(v) ? v : (parseFloat(String(v == null ? '' : v).replace(/\s/g, '').replace(',', '.')) || 0); }
  function code5(v) { var x = Math.round(num(v)); return x > 0 ? String(x).padStart(5, '0') : ''; }
  function fdate(s) { var m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? m[3] + '/' + m[2] + '/' + m[1] : ''; }
  function fdateLong(s) { var m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? (+m[3] === 1 ? '1er' : +m[3]) + ' ' + MOIS[+m[2] - 1] + ' ' + m[1] : ''; }

  /* ---------- styles ---------- */
  var css = h('style');
  css.textContent = [
    '.ha-card{grid-column:1/-1;cursor:pointer;transition:border-color .15s}',
    '.ha-card:hover{border-color:var(--accent)}',
    '.ha-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}',
    '.ha-years{display:flex;gap:6px;flex-wrap:wrap}',
    '.ha-years button{font:inherit;font-size:13px;padding:5px 11px;border-radius:99px;border:1px solid var(--border-strong);background:var(--surface);color:var(--text-2);cursor:pointer}',
    '.ha-years button[aria-pressed=true]{background:var(--accent);border-color:var(--accent);color:var(--surface)}',
    '.ha-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:16px 0}',
    '.ha-kpi{border:1px solid var(--border);border-radius:8px;padding:12px 14px;background:var(--surface)}',
    '.ha-kpi .l{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em}',
    '.ha-kpi .v{font-size:22px;font-weight:600;margin-top:4px;font-variant-numeric:tabular-nums}',
    '.ha-kpi .s{font-size:12.5px;color:var(--text-2);margin-top:2px}',
    '.ha-bars{display:grid;gap:7px}',
    '.ha-bar{display:grid;grid-template-columns:150px minmax(0,1fr) 100px 52px;gap:10px;align-items:center;font-size:13.5px}',
    '.ha-bar .t{height:12px;border-radius:3px;background:var(--surface-2);overflow:hidden}',
    '.ha-bar .t i{display:block;height:100%;background:var(--accent);border-radius:3px}',
    '.ha-bar .a{text-align:right;font-variant-numeric:tabular-nums;font-weight:600}',
    '.ha-bar .p{text-align:right;color:var(--muted);font-variant-numeric:tabular-nums;font-size:12.5px}',
    '.ha-foot{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;font-size:12.5px;color:var(--muted)}',
    '.ha-link{color:var(--accent);font-weight:600;font-size:13.5px}',
    '.ha-ov{position:fixed;inset:0;z-index:1000;background:var(--bg);overflow:auto;outline:none}',
    'body.ha-full{overflow:hidden}',
    '.ha-panel{color:var(--text);max-width:1400px;margin:0 auto;padding:20px 28px 40px}',
    '.ha-back{font:inherit;font-size:14px;font-weight:600;color:var(--accent);background:none;border:1px solid var(--border-strong);border-radius:7px;padding:7px 12px;cursor:pointer;margin:0 0 18px}',
    '.ha-back:hover{background:var(--surface)}',
    '.ha-panel h2{margin:0;font-size:1.35em}.ha-panel h3{margin:22px 0 10px;font-size:1.02em}',
    '.ha-x{font:inherit;font-size:20px;line-height:1;border:0;background:none;color:var(--text-2);cursor:pointer;padding:4px 8px}',
    '.ha-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12.5px;color:var(--text-2);margin:4px 0 8px}',
    '.ha-legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px;vertical-align:-1px}',
    '.ha-svg text{fill:var(--text-2);font-size:12px;font-family:inherit}',
    '.ha-svg .val{fill:var(--text);font-variant-numeric:tabular-nums}',
    '.ha-grid2{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:24px}',
    '@media(max-width:900px){.ha-grid2{grid-template-columns:minmax(0,1fr)}.ha-bar{grid-template-columns:110px minmax(0,1fr) 88px 46px}}',
    '.ha-tools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:6px 0 10px}',
    '.ha-tools input,.ha-tools select{font:inherit;font-size:13px;padding:6px 9px;border:1px solid var(--border-strong);border-radius:7px;background:var(--surface);color:var(--text)}',
    '.ha-tbl-wrap{overflow:auto;max-height:62vh;border:1px solid var(--border);border-radius:8px}',
    '.ha-tbl{border-collapse:collapse;width:100%;font-size:13px}',
    '.ha-tbl th,.ha-tbl td{padding:7px 10px;border-bottom:1px solid var(--grid);white-space:nowrap}',
    '.ha-tbl th{position:sticky;top:0;background:var(--surface-2);text-align:right;cursor:pointer;font-weight:600;color:var(--text-2);user-select:none}',
    '.ha-tbl th.l,.ha-tbl td.l{text-align:left}.ha-tbl td{text-align:right;font-variant-numeric:tabular-nums}',
    '.ha-tbl th.on{color:var(--accent)}.ha-tbl td.z{color:var(--muted)}.ha-tbl td.tot,.ha-tbl th.tot{font-weight:600}',
    '.ha-tbl tfoot td{position:sticky;bottom:0;background:var(--surface-2);font-weight:600}',
    '.ha-note{font-size:12.5px;color:var(--muted);margin-top:10px}',
    '.ha-colbtn{position:relative}',
    '.ha-pop{position:absolute;z-index:50;top:calc(100% + 6px);right:0;width:330px;max-height:70vh;overflow:auto;background:var(--surface);border:1px solid var(--border-strong);border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.18);padding:10px 12px;text-align:left}',
    '.ha-pop label{display:flex;gap:8px;align-items:center;padding:4px 2px;font-size:13.5px;cursor:pointer;color:var(--text)}',
    '.ha-pop .r{display:flex;justify-content:space-between;gap:8px;border-bottom:1px solid var(--border);padding-bottom:8px;margin-bottom:6px;font-size:12.5px;color:var(--muted)}',
    '.ha-pop .r button{font:inherit;font-size:12.5px;border:0;background:none;color:var(--accent);cursor:pointer;padding:0}',
    '.ha-row{display:flex;align-items:center;justify-content:space-between;gap:6px;border-radius:6px;padding:0 2px;cursor:grab}',
    '.ha-row.dragging{opacity:.4}.ha-row.over{box-shadow:inset 0 2px 0 var(--accent)}',
    '.ha-row label{flex:1;min-width:0}',
    '.ha-row .mv{display:flex;gap:2px}.ha-row .mv button{font:inherit;font-size:13px;width:24px;height:24px;border:1px solid var(--border);border-radius:5px;background:var(--surface);color:var(--text-2);cursor:pointer;padding:0}',
    '.ha-row .mv button:disabled{opacity:.3;cursor:default}',
    '#immTbl td[data-col^="_"]{text-align:right;font-variant-numeric:tabular-nums;padding-right:10px;white-space:nowrap}',
    '#immTbl td.ha-flag{box-shadow:inset 3px 0 0 #c08a2c}',
    '.ha-check{margin:0 0 14px;border:1px solid var(--border);border-radius:8px;background:var(--surface);padding:10px 14px}',
    '.ha-check summary{cursor:pointer;display:flex;gap:12px;align-items:center;flex-wrap:wrap}',
    '.ha-chip{font-size:12px;font-weight:600;border-radius:99px;padding:2px 9px}.ha-chip.warn{background:#f5e6cc;color:#7a5410}.ha-chip.ok{background:#dfeee4;color:#2d5e3d}',
    '.ha-check-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-top:12px}',
    '.ha-check-c h4{margin:0 0 6px;font-size:13.5px}.ha-check-c ul{margin:0;padding-left:18px;font-size:13px;display:grid;gap:3px;max-height:220px;overflow:auto}',
    '.ha-dlg-bd{position:fixed;inset:0;z-index:2000;background:rgba(10,12,16,.45);display:grid;place-items:center;padding:16px}',
    '.ha-dlg{background:var(--surface);color:var(--text);border-radius:10px;padding:22px 24px;width:min(460px,100%);box-shadow:0 20px 60px rgba(0,0,0,.3)}',
    '.ha-dlg h3{margin:0 0 8px;font-size:1.1em}.ha-dlg p{margin:0 0 18px;color:var(--text-2)}.ha-dlg-r{display:flex;gap:10px;justify-content:flex-end}',
    '.ha-danger{background:#a6403a!important;border-color:#a6403a!important;color:#fff!important}',
    '.ha-arch{width:380px}.ha-arow{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)}',
    '.ha-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:2100;background:var(--text);color:var(--bg);padding:10px 16px;border-radius:8px;font-size:14px}'
  ].join('\n');
  document.head.appendChild(css);
  var hideCss = h('style'); document.head.appendChild(hideCss);

  /* ---------- données annexes ---------- */
  var YEARS = A && A.years ? Object.keys(A.years).sort() : [];
  var CATS = A && A.cats ? A.cats : [];
  var TRAV = CATS.findIndex(function (c) { return c.k === 'travaux'; });
  function curYear() { return YEARS.indexOf(P.ha_year) >= 0 ? P.ha_year : YEARS[YEARS.length - 1]; }
  function yShort(y) { var p = String(y).split('-'); return p.length === 2 ? p[0].slice(2) + '-' + p[1].slice(2) : y; }
  function yInfo(y) {
    var Y = A.years[y] || {}, p = String(y).split('-');
    return Y.partial ? 'du 1er avr. ' + p[0] + ' au ' + fdate(Y.end) + ' (exercice en cours)' : 'du 1er avr. ' + p[0] + ' au 31 mars ' + p[1];
  }
  function namesMap() {
    var m = {};
    document.querySelectorAll('#immTbl tbody tr').forEach(function (tr) { var a = tr.querySelector('input[data-k="num"]'), b = tr.querySelector('input[data-k="imm"]'); var c = a ? code5(a.value) : ''; if (c && b && !m[c]) m[c] = String(b.value || '').trim(); });
    var o = (A && A.names) || {};
    Object.keys(o).forEach(function (c) { if (!m[c]) m[c] = o[c]; });
    return m;
  }
  function catTotals(y) {
    var t = CATS.map(function () { return 0; }), B = (A.years[y] || {}).b || {};
    Object.keys(B).forEach(function (code) { Object.keys(B[code]).forEach(function (i) { t[+i] += num(B[code][i]); }); });
    return t;
  }
  function rowsFor(y) {
    var B = (A.years[y] || {}).b || {}, N = namesMap();
    return Object.keys(B).map(function (code) {
      var v = CATS.map(function (c, i) { return num(B[code][i]); });
      return { code: code, name: N[code] || '', v: v, total: v.reduce(function (a, b) { return a + b; }, 0) };
    });
  }
  function sum(a) { return a.reduce(function (x, y) { return x + y; }, 0); }
  function monthsElapsed(y) { var Y = A.years[y]; if (!Y || !Y.partial) return 12; var m = String(Y.end).match(/^(\d{4})-(\d{2})-(\d{2})/); if (!m) return 12; var s = +String(y).split('-')[0]; return Math.max(0.5, ((+m[1] - s) * 12 + (+m[2] - 4)) + (+m[3]) / 30); }

  /* ---------- tableau de bord : bloc ---------- */
  var card;
  function renderCard() {
    var grid = document.querySelector('#tab-dash .grid.two');
    if (!grid || !A || !YEARS.length) return;
    if (!card) { card = h('div', 'card ha-card'); card.setAttribute('role', 'button'); card.tabIndex = 0; card.title = 'Voir le détail par immeuble'; grid.insertBefore(card, grid.firstChild);
      card.addEventListener('click', function (e) { if (e.target.closest('.ha-years')) return; openDetail(); });
      card.addEventListener('keydown', function (e) { if ((e.key === 'Enter' || e.key === ' ') && !e.target.closest('.ha-years')) { e.preventDefault(); openDetail(); } }); }
    card.textContent = '';
    var y = curYear(), yi = YEARS.indexOf(y), prev = yi > 0 ? YEARS[yi - 1] : null;
    var t = catTotals(y), tot = sum(t), rows = rowsFor(y).filter(function (r) { return r.total; });
    var head = h('div', 'ha-head'), ht = h('div');
    ht.appendChild(h('h2', null, 'Honoraires annexes'));
    ht.appendChild(h('p', 'desc', 'Facturé aux copropriétés par les comptes GID (travaux, mutations, relances, sinistres…) · exercice ' + yInfo(y)));
    head.appendChild(ht); head.appendChild(yearButtons());
    card.appendChild(head);
    var k = h('div', 'ha-kpis');
    function kpi(l, v, s) { var c = h('div', 'ha-kpi'); c.appendChild(h('div', 'l', l)); c.appendChild(h('div', 'v', v)); if (s) c.appendChild(h('div', 's', s)); k.appendChild(c); }
    var sub = '';
    if (prev) {
      var pt = sum(catTotals(prev));
      if (A.years[y].partial && pt) sub = PCT.format(tot / pt * 100) + ' % de ' + prev + ' en ' + PCT.format(monthsElapsed(y)).replace(',0', '') + ' mois';
      else if (pt) sub = (tot >= pt ? '+' : '') + PCT.format((tot / pt - 1) * 100) + ' % par rapport à ' + prev;
    }
    kpi('Total facturé', EUR.format(tot), sub);
    kpi('Immeubles facturés', String(rows.length), rows.length ? 'Moyenne ' + EUR.format(tot / rows.length) + ' par immeuble' : '');
    var best = t.map(function (v, i) { return [v, i]; }).sort(function (a, b) { return b[0] - a[0]; })[0];
    if (best && best[0]) kpi('Premier poste', CATS[best[1]].l, EUR.format(best[0]) + ' · ' + PCT.format(best[0] / tot * 100) + ' %');
    if (A.years[y].partial) kpi('Projection sur 12 mois', EUR.format(tot / monthsElapsed(y) * 12), 'Au rythme actuel, à titre indicatif');
    card.appendChild(k);
    var bars = h('div', 'ha-bars'), mx = Math.max.apply(null, t.concat([1]));
    t.map(function (v, i) { return [v, i]; }).filter(function (a) { return a[0]; }).sort(function (a, b) { return b[0] - a[0]; }).forEach(function (a) {
      var r = h('div', 'ha-bar'); r.appendChild(h('span', null, CATS[a[1]].l));
      var tr = h('span', 't'), i = h('i'); i.style.width = (a[0] / mx * 100).toFixed(1) + '%'; tr.appendChild(i); r.appendChild(tr);
      r.appendChild(h('span', 'a', EUR.format(a[0]))); r.appendChild(h('span', 'p', PCT.format(a[0] / tot * 100) + ' %')); bars.appendChild(r);
    });
    card.appendChild(bars);
    var f = h('div', 'ha-foot'); f.appendChild(h('span', null, 'Source : ImmOpen, balance fournisseurs (crédits par immeuble) · relevé du ' + fdate(A.at)));
    f.appendChild(h('span', 'ha-link', 'Voir le détail par immeuble →')); card.appendChild(f);
  }
  function yearButtons() {
    var w = h('div', 'ha-years'); w.setAttribute('role', 'group'); w.setAttribute('aria-label', 'Exercice');
    YEARS.forEach(function (y) {
      var b = h('button', null, y + (A.years[y].partial ? ' (en cours)' : '')); b.type = 'button'; b.setAttribute('aria-pressed', String(y === curYear()));
      b.addEventListener('click', function (e) { e.stopPropagation(); if (y === curYear()) return; savePref('ha_year', y); renderCard(); refreshImm(); if (ov) renderDetail(); });
      w.appendChild(b);
    });
    return w;
  }

  /* ---------- détail : graphique + tableau ---------- */
  var ov = null, sortK = 'total', sortD = -1, q = '', catF = '';
  /* Détail en pleine page (et non en fenêtre) : bouton Retour + retour arrière du navigateur (#annexes) */
  function openDetail(noPush) {
    if (ov) return;
    ov = h('div', 'ha-ov'); ov.tabIndex = -1; ov.setAttribute('role', 'region'); ov.setAttribute('aria-label', 'Honoraires annexes par immeuble');
    document.body.appendChild(ov); document.body.classList.add('ha-full'); document.addEventListener('keydown', escClose);
    if (!noPush && location.hash !== '#annexes') { try { history.pushState({ ha: 1 }, '', '#annexes'); } catch (e) {} }
    renderDetail(); window.scrollTo(0, 0); ov.focus();
  }
  function escClose(e) { if (e.key === 'Escape') back(); }
  function back() { if (location.hash === '#annexes') { try { history.back(); return; } catch (e) {} } closeDetail(); }
  function closeDetail() { if (ov) { ov.remove(); ov = null; document.body.classList.remove('ha-full'); document.removeEventListener('keydown', escClose); if (card) { card.scrollIntoView({ block: 'center' }); card.focus(); } } }
  window.addEventListener('popstate', function () { if (location.hash === '#annexes') openDetail(true); else closeDetail(); });
  function renderDetail() {
    if (!ov) return;
    var scroll = ov.querySelector('.ha-tbl-wrap'), st = scroll ? scroll.scrollTop : 0;
    ov.textContent = '';
    var y = curYear(), p = h('div', 'ha-panel');
    var hd = h('div', 'ha-head'), ht = h('div'); ht.appendChild(h('h2', null, 'Honoraires annexes · ' + y));
    ht.appendChild(h('p', 'desc', 'Exercice ' + yInfo(y) + ' · crédits des comptes fournisseurs GID par immeuble'));
    hd.appendChild(ht);
    var r = h('div', 'ha-head'); r.style.alignItems = 'center'; r.appendChild(yearButtons());
    hd.appendChild(r);
    var bk = h('button', 'ha-back', '← Retour au tableau de bord'); bk.type = 'button'; bk.addEventListener('click', back);
    p.appendChild(bk); p.appendChild(hd);

    var g = h('div', 'ha-grid2'), c1 = h('div'), c2 = h('div');
    c1.appendChild(h('h3', null, 'Par catégorie, sur les trois derniers exercices'));
    c1.appendChild(chartCats()); c2.appendChild(h('h3', null, 'Les 10 immeubles les plus facturés · ' + y)); c2.appendChild(chartTop(y));
    g.appendChild(c1); g.appendChild(c2); p.appendChild(g);

    p.appendChild(h('h3', null, 'Détail par immeuble'));
    var tools = h('div', 'ha-tools');
    var s = h('input'); s.type = 'search'; s.placeholder = 'Rechercher un immeuble, un n°…'; s.value = q; s.setAttribute('aria-label', 'Rechercher');
    var sel = h('select'); sel.setAttribute('aria-label', 'Catégorie');
    var o0 = h('option', null, 'Toutes les catégories'); o0.value = ''; sel.appendChild(o0);
    CATS.forEach(function (c, i) { var o = h('option', null, c.l); o.value = String(i); if (String(i) === catF) o.selected = true; sel.appendChild(o); });
    var cnt = h('span', 'ha-note'); cnt.style.margin = '0 0 0 auto';
    tools.appendChild(s); tools.appendChild(sel); tools.appendChild(cnt); p.appendChild(tools);
    var wrap = h('div', 'ha-tbl-wrap'), tbl = h('table', 'ha-tbl'); wrap.appendChild(tbl); p.appendChild(wrap);
    p.appendChild(h('p', 'ha-note', 'Montants TTC facturés (crédits) sur l’exercice, par immeuble ; les avoirs saisis en facture négative sont déduits. Source : ImmOpen › Suivi comptable › Balance fournisseurs, comptes GID Comptabilité, Location de salles, Mutations, Photocopies, Procédures, Relances, Sinistres, Travaux, Vacations AG/CS et Vacations. Relevé du ' + fdate(A.at) + '.'));
    ov.appendChild(p);

    function fill() {
      var rows = rowsFor(y), used = CATS.map(function (c, i) { return i; }).filter(function (i) { return rows.some(function (r) { return r.v[i]; }); });
      if (catF !== '') used = used.filter(function (i) { return String(i) === catF; });
      var ql = q.trim().toLowerCase();
      rows = rows.filter(function (r) {
        if (catF !== '' ? !r.v[+catF] : !r.total) return false;
        return !ql || (r.code + ' ' + r.name).toLowerCase().indexOf(ql) >= 0;
      });
      var val = function (r) { return sortK === 'total' ? (catF !== '' ? r.v[+catF] : r.total) : sortK === 'code' ? r.code : sortK === 'name' ? r.name : r.v[+sortK]; };
      rows.sort(function (a, b) { var u = val(a), w = val(b); return (typeof u === 'string' ? u.localeCompare(w, 'fr', { numeric: true }) : u - w) * sortD; });
      tbl.textContent = '';
      var th = h('thead'), tr = h('tr');
      function head(k, l, cls) { var c = h('th', cls || '', l); c.dataset.k = k; if (String(sortK) === String(k)) { c.classList.add('on'); c.textContent += sortD < 0 ? ' ↓' : ' ↑'; } tr.appendChild(c); }
      head('code', 'N°', 'l'); head('name', 'Immeuble', 'l');
      if (catF === '') used.forEach(function (i) { head(i, CATS[i].l); });
      head('total', catF === '' ? 'Total' : CATS[+catF].l, 'tot');
      th.appendChild(tr); tbl.appendChild(th);
      var tb = h('tbody'), T = CATS.map(function () { return 0; }), TT = 0;
      rows.forEach(function (r) {
        var row = h('tr'); row.appendChild(h('td', 'l', r.code)); row.appendChild(h('td', 'l', r.name || '—'));
        if (catF === '') used.forEach(function (i) { row.appendChild(h('td', r.v[i] ? '' : 'z', r.v[i] ? EUR.format(r.v[i]) : '–')); T[i] += r.v[i]; });
        var tv = catF === '' ? r.total : r.v[+catF]; TT += tv; row.appendChild(h('td', 'tot', EUR.format(tv))); tb.appendChild(row);
      });
      tbl.appendChild(tb);
      var tf = h('tfoot'), fr = h('tr'); fr.appendChild(h('td', 'l', 'Total')); fr.appendChild(h('td', 'l', rows.length + ' immeuble' + (rows.length > 1 ? 's' : '')));
      if (catF === '') used.forEach(function (i) { fr.appendChild(h('td', null, EUR.format(T[i]))); });
      fr.appendChild(h('td', 'tot', EUR.format(TT))); tf.appendChild(fr); tbl.appendChild(tf);
      cnt.textContent = rows.length + ' immeuble' + (rows.length > 1 ? 's' : '') + ' · ' + EUR.format(TT);
    }
    tbl.addEventListener('click', function (e) { var c = e.target.closest('th[data-k]'); if (!c) return; var k = c.dataset.k; if (String(sortK) === k) sortD = -sortD; else { sortK = k; sortD = (k === 'code' || k === 'name') ? 1 : -1; } fill(); });
    s.addEventListener('input', function () { q = s.value; fill(); });
    sel.addEventListener('change', function () { catF = sel.value; if (sortK !== 'code' && sortK !== 'name') sortK = 'total'; fill(); });
    fill();
    var sc = ov.querySelector('.ha-tbl-wrap'); if (sc) sc.scrollTop = st;
  }
  var NS = 'http://www.w3.org/2000/svg';
  function sv(tag, at, txt) { var e = document.createElementNS(NS, tag); for (var k in at) e.setAttribute(k, at[k]); if (txt != null) e.textContent = txt; return e; }
  function chartCats() {
    var ys = YEARS.slice(-3), tots = ys.map(catTotals);
    var idx = CATS.map(function (c, i) { return i; }).filter(function (i) { return tots.some(function (t) { return t[i]; }); })
      .sort(function (a, b) { return tots[tots.length - 1][b] - tots[tots.length - 1][a] || tots[0][b] - tots[0][a]; });
    var colors = ['var(--prev)', 'color-mix(in srgb, var(--accent) 55%, var(--prev))', 'var(--accent)'].slice(-ys.length);
    var W = 620, L = 132, R = 78, rowH = 14 * ys.length + 14, H = idx.length * rowH + 6;
    var mx = Math.max.apply(null, [1].concat(tots.reduce(function (a, t) { return a.concat(t); }, [])));
    var wrap = h('div'), lg = h('div', 'ha-legend');
    ys.forEach(function (y, j) { var s = h('span'), i = h('i'); i.style.background = colors[j]; s.appendChild(i); s.appendChild(document.createTextNode(y + (A.years[y].partial ? ' (au ' + fdate(A.years[y].end) + ')' : ''))); lg.appendChild(s); });
    wrap.appendChild(lg);
    var svg = sv('svg', { viewBox: '0 0 ' + W + ' ' + H, width: '100%', 'class': 'ha-svg', role: 'img', 'aria-label': 'Honoraires annexes par catégorie et par exercice' });
    idx.forEach(function (ci, r) {
      var y0 = r * rowH + 4;
      svg.appendChild(sv('text', { x: L - 10, y: y0 + rowH / 2, 'text-anchor': 'end', 'dominant-baseline': 'middle' }, CATS[ci].l));
      ys.forEach(function (y, j) {
        var v = tots[j][ci], w = Math.max(v ? 2 : 0, (W - L - R) * v / mx), yy = y0 + j * 14;
        var rc = sv('rect', { x: L, y: yy, width: w.toFixed(1), height: 11, rx: 2 }); rc.style.fill = colors[j];
        rc.appendChild(sv('title', {}, CATS[ci].l + ' · ' + y + ' : ' + EUR.format(v))); svg.appendChild(rc);
        if (j === ys.length - 1 || v) svg.appendChild(sv('text', { x: L + w + 6, y: yy + 6, 'dominant-baseline': 'middle', 'class': j === ys.length - 1 ? 'val' : '' }, v ? EUR.format(v) : ''));
      });
    });
    wrap.appendChild(svg); return wrap;
  }
  function chartTop(y) {
    var rows = rowsFor(y).filter(function (r) { return r.total; }).sort(function (a, b) { return b.total - a.total; }).slice(0, 10);
    var W = 520, L = 190, R = 80, rowH = 24, H = rows.length * rowH + 4, mx = rows.length ? rows[0].total : 1;
    var svg = sv('svg', { viewBox: '0 0 ' + W + ' ' + H, width: '100%', 'class': 'ha-svg', role: 'img', 'aria-label': 'Top 10 des immeubles' });
    rows.forEach(function (r, i) {
      var yy = i * rowH + 4, nm = (r.code + ' ' + (r.name || '')).trim(); if (nm.length > 28) nm = nm.slice(0, 27) + '…';
      svg.appendChild(sv('text', { x: L - 10, y: yy + 8, 'text-anchor': 'end', 'dominant-baseline': 'middle' }, nm));
      var w = Math.max(2, (W - L - R) * r.total / mx), rc = sv('rect', { x: L, y: yy + 1, width: w.toFixed(1), height: 14, rx: 2 }); rc.style.fill = 'var(--accent)';
      rc.appendChild(sv('title', {}, r.code + ' ' + r.name + ' : ' + EUR.format(r.total))); svg.appendChild(rc);
      svg.appendChild(sv('text', { x: L + w + 6, y: yy + 8, 'dominant-baseline': 'middle', 'class': 'val' }, EUR.format(r.total)));
    });
    return svg;
  }

  /* ---------- onglet Immeubles : colonne travaux + choix des colonnes ----------
     Le script du document est encapsulé : on lit le tableau rendu (th[data-k], input[data-k="num"]). */
  function travFor(code) { if (!A || TRAV < 0 || !code) return 0; var B = (A.years[curYear()] || {}).b || {}; return B[code] ? num(B[code][TRAV]) : 0; }
  function rowCode(tr) { var a = tr.querySelector('input[data-k="num"]'); return a ? code5(a.value) : ''; }
  function travLabel() { return 'Hon. travaux GID ' + yShort(curYear()); }
  /* Exercice GID (1er avril → 31 mars) contenant aujourd'hui, et le précédent */
  function exOf(d) { var y = d.getFullYear(), m = d.getMonth(); var s = m >= 3 ? y : y - 1; return s + '-' + (s + 1); }
  var EX0 = exOf(new Date()), EX1 = (function () { var s = +EX0.split('-')[0] - 1; return s + '-' + (s + 1); })();
  var PV = C.pv || {};
  function pvFor(code, ex) { var r = PV[code] && PV[code][ex]; return r ? num(r.trav) : 0; }
  function pvTitle(code, ex) { var r = PV[code] && PV[code][ex]; if (!r) return 'Aucun PV d’AG reçu pour cet exercice'; return 'AG du ' + fdate(r.date) + ' · ' + (r.n || 0) + ' résolution(s) d’honoraires travaux adoptée(s)' + (r.objets ? ' : ' + r.objets : ''); }
  /* Colonnes ajoutées au tableau (calculées, non modifiables) */
  var EXTRA = [];
  if (A && TRAV >= 0) EXTRA.push({ k: '_trav', label: travLabel, title: function () { return 'Honoraires de travaux facturés par GID (compte GID TRAVAUX), exercice ' + yInfo(curYear()); }, val: travFor, tip: function () { return ''; } });
  EXTRA.push({ k: '_pv0', label: function () { return 'Hon. travaux votés AG ' + yShort(EX0); }, title: function () { return 'Honoraires du syndic sur travaux votés en AG (d’après les PV reçus), exercice ' + EX0; }, val: function (c) { return pvFor(c, EX0); }, tip: function (c) { return pvTitle(c, EX0); } });
  EXTRA.push({ k: '_pv1', label: function () { return 'Hon. travaux votés AG ' + yShort(EX1); }, title: function () { return 'Honoraires du syndic sur travaux votés en AG (d’après les PV reçus), exercice ' + EX1; }, val: function (c) { return pvFor(c, EX1); }, tip: function (c) { return pvTitle(c, EX1); } });
  function hidden() { return Array.isArray(P.hono_cols) ? P.hono_cols : []; }
  function applyHidden() { hideCss.textContent = hidden().map(function (k) { return '#immTbl [data-col="' + String(k).replace(/[^\w-]/g, '') + '"]{display:none}'; }).join('\n'); }
  var busy = false, KEYS = [], LABELS = {};
  function decorate() {
    var t = document.getElementById('immTbl'); if (!t) return;
    busy = true;
    try {
      var hr = t.querySelector('thead tr');
      if (hr && !hr.querySelector('[data-col]')) {
        KEYS = []; [].forEach.call(hr.children, function (th) { if (th.dataset.k) { th.dataset.col = th.dataset.k; KEYS.push(th.dataset.k); LABELS[th.dataset.k] = th.textContent.replace(/[↑↓▲▼]/g, '').trim(); } });
        var ref = hr.children[KEYS.length] || null;
        EXTRA.forEach(function (x) { var th = h('th', null, x.label()); th.dataset.col = x.k; th.style.textAlign = 'right'; th.title = x.title(); hr.insertBefore(th, ref); });
      }
      var n = KEYS.length, trs = t.querySelectorAll('tbody tr'), tot = EXTRA.map(function () { return 0; });
      for (var r = 0; r < trs.length; r++) {
        var tr = trs[r], code = rowCode(tr);
        EXTRA.forEach(function (x, i) { tot[i] += x.val(code); });
        if (tr.querySelector('[data-col]')) continue;
        for (var j = 0; j < n && j < tr.children.length; j++) tr.children[j].dataset.col = KEYS[j];
        var ref2 = tr.children[n] || null;
        EXTRA.forEach(function (x) { var v = x.val(code), td = h('td', 'ro r', v ? EUR.format(v) : '–'); td.dataset.col = x.k; var tp = x.tip(code); if (tp) td.title = tp; tr.insertBefore(td, ref2); });
      }
      var f = document.getElementById('immFoot');
      if (f && f.children.length && !f.querySelector('[data-col]')) {
        for (var k = 0; k < n && k < f.children.length; k++) f.children[k].dataset.col = KEYS[k];
        var ref3 = f.children[n] || null;
        EXTRA.forEach(function (x, i) { var ft = h('td', null, EUR.format(tot[i])); ft.dataset.col = x.k; f.insertBefore(ft, ref3); });
      }
      applyOrder(); markRows();
    } finally { busy = false; }
  }
  /* Ordre des colonnes : préférence propre à chaque utilisateur (hono_order) */
  function allKeys() { return KEYS.concat(EXTRA.map(function (x) { return x.k; })); }
  function colOrder() {
    var def = allKeys(), saved = Array.isArray(P.hono_order) ? P.hono_order.filter(function (k) { return def.indexOf(k) >= 0; }) : [];
    if (!saved.length) return def;
    def.forEach(function (k, i) { if (saved.indexOf(k) < 0) saved.splice(Math.min(i, saved.length), 0, k); });
    return saved;
  }
  function applyOrder() {
    var t = document.getElementById('immTbl'); if (!t) return;
    var ord = colOrder(), want = ord.join('|');
    t.querySelectorAll('tr').forEach(function (tr) {
      var cells = [].filter.call(tr.children, function (c) { return c.dataset.col; });
      if (!cells.length || cells.map(function (c) { return c.dataset.col; }).join('|') === want) return;
      var by = {}; cells.forEach(function (c) { by[c.dataset.col] = c; });
      var anchor = cells[cells.length - 1].nextSibling; // cellules hors colonnes (bouton de suppression) conservées en fin de ligne
      ord.forEach(function (k) { if (by[k]) tr.insertBefore(by[k], anchor); });
    });
  }
  function moveCol(k, d) {
    var o = colOrder(), i = o.indexOf(k), j = i + d; if (i < 0 || j < 0 || j >= o.length) return;
    o.splice(i, 1); o.splice(j, 0, k); savePref('hono_order', o);
    busy = true; try { applyOrder(); } finally { busy = false; }
  }
  function refreshImm() { // changement d'exercice : mise à jour des cellules existantes
    busy = true;
    try {
      EXTRA.forEach(function (x, i) {
        var th = document.querySelector('#immTbl thead th[data-col="' + x.k + '"]'); if (th) { th.textContent = x.label(); th.title = x.title(); }
        var total = 0;
        document.querySelectorAll('#immTbl tbody tr').forEach(function (tr) { var c = rowCode(tr), v = x.val(c), td = tr.querySelector('td[data-col="' + x.k + '"]'); total += v; if (td) td.textContent = v ? EUR.format(v) : '–'; });
        var ft = document.querySelector('#immFoot [data-col="' + x.k + '"]'); if (ft) ft.textContent = EUR.format(total);
      });
    } finally { busy = false; }
  }

  /* ---------- Contrôle croisé avec ImmOpen (planning des AG) ---------- */
  var IO = Array.isArray(C.immopen) ? C.immopen : [];
  var PORT_GEST = { SR: 'RAOULT', LD: 'DIEZ', LC: 'CHATELAIN', KM: 'MALTEZ', NG: 'GADOUCHE', AG: 'GREVET', ID: 'DEBUIRE', SE: 'ESHAIBI' };
  var ISSUES = {};
  function tableRows() { // toutes les lignes (y compris filtrées à l'écran) : état partagé du tableau
    var st = null;
    try { st = JSON.parse(localStorage.getItem(C.key) || 'null'); } catch (e) {}
    if (!st || !st.bld) { try { st = JSON.parse((document.getElementById('seed') || {}).textContent || 'null'); } catch (e) {} }
    return ((st && st.bld) || []).map(function (b) {
      return { code: code5(b.num), imm: String(b.imm || '').trim(), lots: num(b.lots), port: String(b.port || '').trim().toUpperCase(), lost: num(b.perdus) > 0 && num(b.h2627v) === 0 };
    });
  }
  function crossCheck() {
    var box = document.getElementById('haCheck'); if (!IO.length) { if (box) box.remove(); return; }
    var io = {}; IO.forEach(function (r) { io[r.code] = r; });
    var rows = tableRows(), seen = {}; ISSUES = {};
    var missingIn = [], notIO = [], gestDiff = [], lostInIO = [];
    rows.forEach(function (r) {
      if (!r.code) return; seen[r.code] = 1; var o = io[r.code];
      if (!o) { if (!r.lost) { notIO.push(r); ISSUES[r.code] = 'Absent du planning ImmOpen (immeuble sorti ?)'; } return; }
      if (r.lost) { lostInIO.push([r, o]); ISSUES[r.code] = 'Perdu ici mais encore présent dans ImmOpen'; return; }
      var gk = PORT_GEST[r.port]; if (gk && o.gest && String(o.gest).toUpperCase().indexOf(gk) < 0) { gestDiff.push([r, o]); ISSUES[r.code] = (ISSUES[r.code] ? ISSUES[r.code] + ' · ' : '') + 'Gestionnaire ImmOpen : ' + o.gest; }
    });
    IO.forEach(function (o) { if (!seen[o.code]) missingIn.push(o); });
    if (!box) { box = h('details', 'ha-check'); box.id = 'haCheck'; var tw = document.querySelector('#tab-imm .tbl-wrap'); if (!tw) return; tw.parentNode.insertBefore(box, tw); }
    var total = missingIn.length + notIO.length + gestDiff.length + lostInIO.length;
    box.textContent = '';
    var sm = h('summary'); sm.appendChild(h('strong', null, 'Contrôle avec ImmOpen')); sm.appendChild(h('span', 'ha-chip ' + (total ? 'warn' : 'ok'), total ? total + ' écart' + (total > 1 ? 's' : '') : 'Aucun écart'));
    sm.appendChild(h('span', 'ha-note', 'Planning des AG ImmOpen relevé le ' + fdate(C.immopen_date) + ' · ' + IO.length + ' immeubles · lots non comparés (ImmOpen compte tous les lots, ce tableau les lots principaux)'));
    box.appendChild(sm);
    var g = h('div', 'ha-check-g');
    function block(title, list, fmt, empty) {
      var c = h('div', 'ha-check-c'); c.appendChild(h('h4', null, title + ' (' + list.length + ')'));
      if (!list.length) { c.appendChild(h('p', 'ha-note', empty)); g.appendChild(c); return; }
      var ul = h('ul'); list.slice(0, 60).forEach(function (x) { ul.appendChild(h('li', null, fmt(x))); }); if (list.length > 60) ul.appendChild(h('li', 'ha-note', '… et ' + (list.length - 60) + ' autres')); c.appendChild(ul); g.appendChild(c);
    }
    block('Dans ImmOpen, absents de ce tableau', missingIn, function (o) { return o.code + ' ' + o.nom + ' · ' + (o.lots || '?') + ' lots · ' + (o.gest || ''); }, 'Tous les immeubles ImmOpen figurent ici.');
    block('Dans ce tableau, absents d’ImmOpen', notIO, function (r) { return r.code + ' ' + r.imm + ' (' + r.port + ') : à archiver ?'; }, 'Aucun.');
    block('Perdus ici, encore dans ImmOpen', lostInIO, function (p) { return p[0].code + ' ' + p[0].imm + ' : à clôturer dans ImmOpen ou à archiver ici'; }, 'Aucun.');
    block('Gestionnaire différent', gestDiff, function (p) { return p[0].code + ' ' + p[0].imm + ' : portefeuille ' + p[0].port + ', ImmOpen ' + p[1].gest; }, 'Aucun.');
    box.appendChild(g);
    markRows();
  }
  function markRows() {
    document.querySelectorAll('#immTbl tbody tr').forEach(function (tr) {
      var c = rowCode(tr), td = tr.querySelector('td[data-col="num"]'); if (!td) return;
      var m = ISSUES[c]; td.classList.toggle('ha-flag', !!m); td.title = m || '';
    });
  }

  /* ---------- Suppression avec confirmation et archivage ---------- */
  var ARCH = Array.isArray(C.archive) ? C.archive : [];
  function api(path, body) {
    return fetch('/api/' + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': C.csrf }, body: JSON.stringify(body) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok || j.error) throw new Error(j.error || 'Erreur'); return j; }); });
  }
  function rowData(tr) { var d = {}; tr.querySelectorAll('input[data-k]').forEach(function (i) { d[i.dataset.k] = i.value; }); return d; }
  function dialog(title, text, okLabel, onOk) {
    var bd = h('div', 'ha-dlg-bd'), dl = h('div', 'ha-dlg'); dl.setAttribute('role', 'alertdialog'); dl.setAttribute('aria-modal', 'true');
    dl.appendChild(h('h3', null, title)); var p = h('p', null, text); dl.appendChild(p);
    var row = h('div', 'ha-dlg-r'), no = h('button', 'btn', 'Annuler'), ok = h('button', 'btn ha-danger', okLabel); no.type = ok.type = 'button';
    row.appendChild(no); row.appendChild(ok); dl.appendChild(row); bd.appendChild(dl); document.body.appendChild(bd); ok.focus();
    function close() { bd.remove(); document.removeEventListener('keydown', esc, true); }
    function esc(e) { if (e.key === 'Escape') { e.stopPropagation(); close(); } }
    document.addEventListener('keydown', esc, true);
    no.addEventListener('click', close); bd.addEventListener('click', function (e) { if (e.target === bd) close(); });
    ok.addEventListener('click', function () { ok.disabled = true; Promise.resolve(onOk()).then(close, function (e) { ok.disabled = false; p.textContent = e.message || 'Échec'; }); });
  }
  var bypass = false;
  document.addEventListener('click', function (e) {
    var b = e.target.closest && e.target.closest('#immTbl button.del'); if (!b || bypass || C.ro) return;
    e.preventDefault(); e.stopImmediatePropagation();
    var tr = b.closest('tr'), d = rowData(tr), name = (d.imm || 'cet immeuble').trim();
    dialog('Supprimer « ' + name + ' » ?', 'L’immeuble est retiré du tableau et placé dans les archives, avec toutes ses données. Vous pourrez le restaurer depuis le bouton « Archives ».', 'Supprimer et archiver', function () {
      return api('hono/archive', { num: d.num, port: d.port, imm: name, data: d }).then(function (r) {
        ARCH.unshift(r.item); var oc = window.confirm; window.confirm = function () { return true; }; bypass = true;
        try { b.click(); } finally { bypass = false; window.confirm = oc; }
        updArchBtn(); toast('« ' + name + ' » archivé');
      });
    });
  }, true);
  function toast(m) { var t = h('div', 'ha-toast', m); document.body.appendChild(t); setTimeout(function () { t.remove(); }, 3500); }
  var archBtn;
  function updArchBtn() { if (archBtn) archBtn.textContent = 'Archives' + (ARCH.length ? ' (' + ARCH.length + ')' : ''); }
  function archPicker() {
    var bar = document.querySelector('#tab-imm .toolbar'); if (!bar || bar.querySelector('.ha-archbtn')) return;
    var wrap = h('span', 'ha-colbtn ha-archbtn'); archBtn = h('button', 'btn', 'Archives'); archBtn.type = 'button'; updArchBtn();
    var pop = h('div', 'ha-pop ha-arch'); pop.hidden = true; pop.addEventListener('click', function (e) { e.stopPropagation(); });
    function build() {
      pop.textContent = ''; pop.appendChild(h('div', 'r', 'Immeubles archivés'));
      if (!ARCH.length) { pop.appendChild(h('p', 'ha-note', 'Aucun immeuble archivé.')); return; }
      ARCH.forEach(function (a) {
        var it = h('div', 'ha-arow'); var tx = h('div'); tx.appendChild(h('strong', null, (a.num ? a.num + ' · ' : '') + a.imm));
        tx.appendChild(h('div', 'ha-note', (a.port || '') + ' · archivé le ' + new Date(a.at * 1000).toLocaleDateString('fr-FR') + (a.by ? ' par ' + a.by : ''))); it.appendChild(tx);
        if (!C.ro) { var rb = h('button', 'btn', 'Restaurer'); rb.type = 'button'; rb.addEventListener('click', function () { restore(a, rb); }); it.appendChild(rb); }
        pop.appendChild(it);
      });
    }
    archBtn.addEventListener('click', function (e) { e.stopPropagation(); var o = pop.hidden; if (o) build(); pop.hidden = !o; });
    document.addEventListener('click', function (e) { if (!pop.hidden && !wrap.contains(e.target)) pop.hidden = true; });
    wrap.appendChild(archBtn); wrap.appendChild(pop);
    var sp = bar.querySelector('.spacer'); bar.insertBefore(wrap, sp || null);
    function restore(a, rb) {
      rb.disabled = true;
      var add = document.getElementById('addImm'); if (!add) return;
      ['#immSearch'].forEach(function (s) { var i = document.querySelector(s); if (i) i.value = ''; });
      add.click();
      setTimeout(function () {
        var inp = document.activeElement, tr = inp && inp.closest ? inp.closest('tr') : null; if (!tr) { rb.disabled = false; return; }
        Object.keys(a.data || {}).forEach(function (k) {
          var i = tr.querySelector('input[data-k="' + k + '"]'); if (!i) return; i.value = a.data[k]; i.dispatchEvent(new Event('input', { bubbles: true }));
        });
        api('hono/unarchive', { id: a.id }).then(function () { ARCH = ARCH.filter(function (x) { return x.id !== a.id; }); updArchBtn(); build(); toast('« ' + a.imm + ' » restauré'); });
      }, 80);
    }
  }
  function colPicker() {
    var bar = document.querySelector('#tab-imm .toolbar'); if (!bar || bar.querySelector('.ha-colbtn')) return;
    var wrap = h('span', 'ha-colbtn'), btn = h('button', 'btn', 'Colonnes'); btn.type = 'button'; btn.setAttribute('aria-expanded', 'false');
    btn.title = 'Choisir les colonnes affichées et leur ordre (mémorisé pour votre compte)';
    var pop = h('div', 'ha-pop'); pop.hidden = true; pop.addEventListener('click', function (e) { e.stopPropagation(); });
    function build() {
      pop.textContent = '';
      var hd = h('div', 'r'); hd.appendChild(h('span', null, 'Colonnes : affichage et ordre'));
      var all = h('button', null, 'Réinitialiser'); all.type = 'button'; all.title = 'Toutes les colonnes, dans l\u2019ordre d\u2019origine';
      all.addEventListener('click', function () { savePref('hono_cols', []); savePref('hono_order', []); applyHidden(); busy = true; try { applyOrder(); } finally { busy = false; } build(); }); hd.appendChild(all); pop.appendChild(hd);
      var drag = null;
      colOrder().forEach(function (k, i, arr) {
        var ex = EXTRA.filter(function (x) { return x.k === k; })[0], lab = ex ? ex.label() : (LABELS[k] || k);
        var row = h('div', 'ha-row'); row.draggable = true; row.dataset.k = k;
        var lb = h('label'), cb = h('input'); cb.type = 'checkbox'; cb.checked = hidden().indexOf(k) < 0;
        if (k === 'imm') { cb.disabled = true; cb.checked = true; cb.title = 'Toujours affichée'; }
        cb.addEventListener('change', function () { var hs = hidden().filter(function (x) { return x !== k; }); if (!cb.checked) hs.push(k); savePref('hono_cols', hs); applyHidden(); });
        lb.appendChild(cb); lb.appendChild(document.createTextNode(lab)); row.appendChild(lb);
        var g = h('span', 'mv');
        [['↑', -1, 'Monter'], ['↓', 1, 'Descendre']].forEach(function (m) {
          var b = h('button', null, m[0]); b.type = 'button'; b.setAttribute('aria-label', m[2] + ' « ' + lab + ' »');
          if ((m[1] < 0 && i === 0) || (m[1] > 0 && i === arr.length - 1)) b.disabled = true;
          b.addEventListener('click', function (e) { e.preventDefault(); moveCol(k, m[1]); build(); var nb = pop.querySelector('.ha-row[data-k="' + k + '"] .mv button:' + (m[1] < 0 ? 'first' : 'last') + '-child'); if (nb && !nb.disabled) nb.focus(); });
          g.appendChild(b);
        });
        row.appendChild(g);
        row.addEventListener('dragstart', function (e) { drag = k; row.classList.add('dragging'); try { e.dataTransfer.setData('text/plain', k); e.dataTransfer.effectAllowed = 'move'; } catch (x) {} });
        row.addEventListener('dragend', function () { row.classList.remove('dragging'); drag = null; });
        row.addEventListener('dragover', function (e) { if (drag && drag !== k) { e.preventDefault(); row.classList.add('over'); } });
        row.addEventListener('dragleave', function () { row.classList.remove('over'); });
        row.addEventListener('drop', function (e) {
          e.preventDefault(); row.classList.remove('over'); if (!drag || drag === k) return;
          var o = colOrder(), from = o.indexOf(drag); o.splice(from, 1); o.splice(o.indexOf(k) + (from <= i ? 1 : 0), 0, drag);
          savePref('hono_order', o); busy = true; try { applyOrder(); } finally { busy = false; } build();
        });
        pop.appendChild(row);
      });
      pop.appendChild(h('div', 'ha-note', 'Cochez pour afficher, glissez ou utilisez ↑ ↓ pour changer l\u2019ordre. Enregistré pour votre compte et retrouvé à chaque connexion.'));
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); var o = pop.hidden; if (o) build(); pop.hidden = !o; btn.setAttribute('aria-expanded', String(o)); });
    document.addEventListener('click', function (e) { if (!pop.hidden && !wrap.contains(e.target)) { pop.hidden = true; btn.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !pop.hidden) { pop.hidden = true; btn.setAttribute('aria-expanded', 'false'); btn.focus(); } });
    wrap.appendChild(btn); wrap.appendChild(pop);
    var sp = bar.querySelector('.spacer'); bar.insertBefore(wrap, sp || null);
  }

  function init() {
    applyHidden(); colPicker(); archPicker(); decorate(); renderCard(); crossCheck();
    if (location.hash === '#annexes' && A) openDetail(true);
    var t = document.getElementById('immTbl');
    var ccT = null;
    if (t) new MutationObserver(function () { if (!busy) { decorate(); clearTimeout(ccT); ccT = setTimeout(crossCheck, 400); } }).observe(t, { childList: true, subtree: true });
    var g = document.querySelector('#tab-dash .grid.two');
    if (g) new MutationObserver(function () { if (card && !g.contains(card)) { card = null; renderCard(); } }).observe(g, { childList: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
