/* Chemini · Honoraires : bloc « Honoraires annexes » (tableau de bord + détail), colonne « Hon. travaux GID »
   dans l'onglet Immeubles, choix des colonnes affichées mémorisé pour chaque utilisateur.
   Exercice comptable : du 1er avril au 31 mars. Le script du document étant encapsulé, on travaille sur le tableau rendu. */
(function () {
  'use strict';
  var C = window.GID_C || {}, A = C.annexes, P = C.prefs || {};
  var EUR = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  var EUR2 = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 });
  var PCT = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1, minimumFractionDigits: 1 });
  var MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

  function savePref(k, v) {
    if (k === 'hono_order' && Array.isArray(v) && v.indexOf('_m1') < 0) v = v.concat('_m1'); // marqueur : fusion des colonnes travaux déjà faite
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
    ':root{--ha-ann:#C9A66B}',
    '.ha-topmore{display:grid;gap:6px;margin-top:6px;padding-top:12px;border-top:1px dashed var(--border)}',
    '.ha-tr{display:grid;grid-template-columns:24px minmax(0,1fr) 30% 74px;gap:10px;align-items:center;font-size:12.5px}',
    '.ha-tr .n{color:var(--text-2);font-variant-numeric:tabular-nums;text-align:right}.ha-tr .l{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}',
    '.ha-tr .t{height:8px;border-radius:3px;background:var(--surface-2);overflow:hidden}.ha-tr .t i{display:block;height:100%;background:var(--accent);opacity:.75}',
    '.ha-tr .v{text-align:right;font-weight:600;font-variant-numeric:tabular-nums}',
    '.ha-allbtn{justify-self:start;margin-top:10px;padding:8px 14px;border:1px solid var(--border);border-radius:6px;color:var(--accent);font-weight:600;font-size:13px;text-decoration:none}.ha-allbtn:hover{border-color:var(--accent)}',
    '.ha-pl{display:grid;gap:8px;margin-top:12px}',
    '.ha-pr{display:grid;grid-template-columns:34px minmax(0,1fr) 92px 56px 70px;gap:10px;align-items:center;font-size:13px}',
    '.ha-pr .p{font-weight:700}.ha-pr .t{display:flex;height:14px;border-radius:3px;background:var(--surface-2);overflow:hidden}.ha-pr .t i{display:block;height:100%}.ha-pr .t .h{background:var(--accent)}.ha-pr .t .a{background:var(--ha-ann)}',
    '.ha-pr .v{text-align:right;font-weight:600;font-variant-numeric:tabular-nums}.ha-pr .s{text-align:right;color:var(--text-2);font-size:12px;font-variant-numeric:tabular-nums}.ha-pr .l{text-align:right;color:var(--text-2);font-size:12px;font-variant-numeric:tabular-nums}',
    '.ha-pf{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)}',
    '.ha-pf div{display:flex;flex-direction:column;gap:2px;font-size:11.5px;color:var(--text-2)}.ha-pf b{font-size:15px;color:var(--text);font-variant-numeric:tabular-nums}.ha-pf .strong b{color:var(--accent)}',
    '.ha-pn{margin:10px 0 0;font-size:12px;color:var(--text-2)}',
    '@media (max-width:640px){.ha-pr{grid-template-columns:30px minmax(0,1fr) 84px 50px}.ha-pr .l{display:none}.ha-pf{grid-template-columns:1fr 1fr}}',
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
    '.ha-pop label{display:flex;gap:8px;align-items:center;padding:4px 2px;font-size:13.5px;cursor:pointer;color:var(--text)}',
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
    '.ha-i{display:inline-grid;place-items:center;width:16px;height:16px;margin-left:6px;border-radius:50%;border:1px solid var(--accent);color:var(--accent);background:none;font:italic 600 10px/1 Georgia,serif;cursor:pointer;vertical-align:1px;padding:0}.ha-i:hover{background:var(--accent);color:#fff}',
    '.tabpop{display:contents}.tabpop-btn{display:none}',
    '@media (max-width:820px){.tabpop{display:block;position:relative;margin:6px 0 10px}.tabpop-btn{display:flex;align-items:center;justify-content:space-between;width:100%;padding:11px 14px;border:1px solid var(--line,#ddd);border-radius:10px;background:var(--surface,#fff);color:var(--text,#111);font:inherit;font-weight:600;cursor:pointer}.tabpop-btn svg{width:18px!important;height:18px!important;flex:none}.tabpop.open .tabpop-btn svg{transform:rotate(180deg)}.tabpop>nav.tabs{display:none!important}.tabpop.open>nav.tabs{display:flex!important;flex-direction:column;position:absolute;z-index:50;top:calc(100% + 6px);left:0;right:0;padding:6px;border:1px solid var(--line,#ddd);border-radius:12px;background:var(--surface,#fff);box-shadow:0 14px 40px rgba(0,0,0,.18)}.tabpop.open>nav.tabs button{text-align:left;padding:12px;border:0;border-radius:8px;font-size:15px}.tabpop.open>nav.tabs button[aria-selected=true]{background:var(--line,#eee)}}',
    '.hd{margin:0 0 26px}.hd-h{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:18px}',
    '.hd-eye{font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--accent)}',
    '.hd-t{margin:6px 0 0;font:500 26px/1.2 "Lora",Georgia,serif;color:var(--text)}',
    '.hd-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:99px;font-weight:600;font-size:14px}.hd-badge.crit{background:rgba(192,57,43,.1);color:#b4412f}.hd-badge.ok{background:rgba(46,125,79,.1);color:#2e7d4f}',
    '.hd-k{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));border:1px solid var(--border);border-radius:12px;background:var(--surface);overflow:hidden}',
    '.hd-kc{padding:16px 18px;border-left:1px solid var(--border)}.hd-kc:first-child{border-left:0}',
    '.hd-kv{font:500 26px/1.1 "Lora",Georgia,serif;color:var(--text);font-variant-numeric:tabular-nums}.hd-kc.neg .hd-kv{color:#b4412f}.hd-kc.pos .hd-kv{color:#2e7d4f}',
    '.hd-kl{font-size:13px;font-weight:600;margin-top:6px;color:var(--text)}.hd-kn{font-size:12px;color:var(--text-2);margin-top:2px}',
    '.hd-st{margin:28px 0 10px;font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--text-2)}',
    '.hd-alerts{display:grid;gap:10px}',
    '.hd-al{border:1px solid var(--border);border-left:4px solid #d68a1c;border-radius:10px;background:var(--surface)}.hd-al.crit{border-left-color:#c0392b;background:linear-gradient(90deg,rgba(192,57,43,.05),transparent 40%) ,var(--surface)}',
    '.hd-al summary{display:flex;align-items:center;gap:12px;padding:14px 16px;cursor:pointer;list-style:none}.hd-al summary::-webkit-details-marker{display:none}',
    '.hd-al summary::after{content:"";margin-left:auto;width:9px;height:9px;border-right:2px solid var(--text-2);border-bottom:2px solid var(--text-2);transform:rotate(45deg);transition:transform .15s}.hd-al[open] summary::after{transform:rotate(-135deg)}',
    '.hd-al-t{display:flex;flex-direction:column;gap:2px}.hd-al.crit strong{color:#b4412f}.hd-al-n{font-size:13px;color:var(--text-2)}',
    '.hd-tri{width:20px;height:20px;flex:none}.hd-tri.crit{color:#c0392b}.hd-tri.warn{color:#d68a1c}',
    '.hd-todo{margin:0 16px 10px 48px;font-size:13.5px;color:var(--text-2)}',
    '.hd-al .hd-tbl{margin:0 16px 14px 48px;width:calc(100% - 64px)}',
    '.hd-tbl{border-collapse:collapse;font-size:13.5px;width:100%}.hd-tbl td,.hd-tbl th{padding:8px 10px;border-top:1px solid var(--grid,var(--border));text-align:left}',
    '.hd-tbl th{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text-2);border-top:0;background:var(--surface-2,transparent)}',
    '.hd-tbl .n{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.hd-tbl .c{color:var(--text-2);font-variant-numeric:tabular-nums;width:60px}.hd-tbl .p{color:var(--text-2);white-space:nowrap}.hd-tbl td.neg{color:#b4412f;font-weight:600}',
    '.hd-scroll{border:1px solid var(--border);border-radius:12px;background:var(--surface);overflow:auto}',
    '.hd-pill{display:inline-flex;align-items:center;gap:4px;margin-left:6px;font-weight:600;font-size:12.5px}.hd-pill .hd-tri{width:15px;height:15px}.hd-pill.crit{color:#b4412f}.hd-pill.warn{color:#b0701a}',
    '.hd-empty{color:var(--text-2);margin:0}.hd-foot{font-size:12.5px;color:var(--text-2);margin:12px 0 0}',
    '.hd-more{margin-top:26px;border-top:1px solid var(--border);padding-top:14px}.hd-more>summary{cursor:pointer;font-weight:600;color:var(--accent);padding:6px 0;margin-bottom:12px}',
    '.hm-h{margin-bottom:18px}.hm-s{margin:8px 0 0;color:var(--text-2)}',
    'main.wrap{padding-top:28px;padding-bottom:64px}',
    '.hm-day{border:1px solid var(--border);border-radius:12px;background:var(--surface);margin-bottom:14px;overflow:hidden}',
    '.hm-date{display:flex;justify-content:space-between;gap:10px;padding:12px 16px;background:var(--surface-2,transparent);border-bottom:1px solid var(--border)}.hm-date strong::first-letter{text-transform:uppercase}.hm-date span{font-size:12.5px;color:var(--text-2)}',
    '.hm-list{list-style:none;margin:0;padding:0}.hm-list li{display:grid;grid-template-columns:52px minmax(0,1fr) auto;gap:12px;align-items:baseline;padding:10px 16px;border-top:1px solid var(--grid,var(--border));font-size:14px}.hm-list li:first-child{border-top:0}',
    '.hm-time{color:var(--text-2);font-size:12.5px;font-variant-numeric:tabular-nums}.hm-tab{font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--accent)}',
    '.hm-field{display:block;color:var(--text-2);margin-top:2px}.hm-field del{color:#a6403a}.hm-field ins{color:#2e7d4f;text-decoration:none;font-weight:600}',
    '.hm-who{font-size:12.5px;color:var(--text-2);white-space:nowrap}.hm-who.auto{color:var(--accent)}',
    '.hm-more{display:inline-block;margin-top:6px;font-weight:600;color:var(--accent);text-decoration:none}.hm-more:hover{text-decoration:underline}',
    '@media (max-width:640px){.hm-list li{grid-template-columns:44px minmax(0,1fr)}.hm-who{grid-column:2}.hd-al .hd-tbl,.hd-todo{margin-left:16px;width:calc(100% - 32px)}.hd-t{font-size:22px}}',
    /* Fenêtres : même charte que les dialogues du site (sur-titre, titre Lora, boutons sobres) */
    '.ha-dtx{min-width:96px}.ha-dtx.ha-bad{outline:2px solid #c0392b;outline-offset:-2px}',
    '.ha-dlg-bd{position:fixed;inset:0;z-index:2000;background:rgba(10,13,18,.55);backdrop-filter:blur(2px);display:grid;place-items:center;padding:16px;animation:haIn .15s ease-out}',
    '@keyframes haIn{from{opacity:0}to{opacity:1}}',
    '.ha-dlg{position:relative;display:flex;flex-direction:column;background:var(--bg,var(--surface));color:var(--text);border:1px solid var(--border);border-radius:12px;width:min(500px,100%);max-height:88vh;box-shadow:0 24px 70px rgba(0,0,0,.28);overflow:hidden}',
    '.ha-dlg.wide{width:min(760px,100%)}',
    '.ha-dlg-h{padding:26px 64px 16px 28px;border-bottom:1px solid var(--border)}',
    '.ha-eyebrow{font-size:11px;font-weight:600;letter-spacing:.16em;text-transform:uppercase;color:var(--accent);margin-bottom:6px}',
    '.ha-dlg-h h3{margin:0;font:500 24px/1.2 "Lora",Georgia,serif;color:var(--text)}',
    '.ha-dlg-h .sub{margin:6px 0 0;font-size:13.5px;color:var(--text-2)}',
    '.ha-x{position:absolute;top:18px;right:18px;width:34px;height:34px;display:grid;place-items:center;border:1px solid transparent;border-radius:8px;background:none;color:var(--text-2);cursor:pointer;font-size:18px;line-height:1}',
    '.ha-x:hover{border-color:var(--border);color:var(--text)}',
    '.ha-dlg-b{padding:20px 28px;overflow-y:auto;overflow-x:hidden}',
    '.ha-dlg-b>p{margin:0 0 4px;color:var(--text-2);line-height:1.55}',
    '.ha-dlg-f{display:flex;gap:10px;justify-content:flex-end;padding:16px 28px;border-top:1px solid var(--border);background:var(--surface-2,transparent)}',
    '.ha-b{font:inherit;font-size:14px;font-weight:600;padding:9px 16px;border-radius:8px;border:1px solid var(--border-strong,var(--border));background:var(--surface);color:var(--text);cursor:pointer}',
    '.ha-b:hover{border-color:var(--text-2)}.ha-b.pri{background:var(--text);border-color:var(--text);color:var(--bg,#fff)}.ha-b:disabled{opacity:.5;cursor:default}',
    '.ha-danger{background:#a6403a!important;border-color:#a6403a!important;color:#fff!important}',
    '.ha-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:22px}',
    '.ha-kpis>div{padding:14px 16px;border-left:1px solid var(--border)}.ha-kpis>div:first-child{border-left:0}',
    '.ha-kpis .n{font:500 24px/1.1 "Lora",Georgia,serif;color:var(--text);font-variant-numeric:tabular-nums}.ha-kpis .l{font-size:12px;color:var(--text-2);margin-top:4px}',
    '.ha-ag{border:1px solid var(--border);border-radius:10px;margin-bottom:14px;overflow:hidden}',
    '.ha-ag-h{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:6px 14px;padding:12px 16px;background:var(--surface-2,transparent);border-bottom:1px solid var(--border)}',
    '.ha-ag-h strong{font-size:14.5px}.ha-ag-h .meta{font-size:12.5px;color:var(--text-2)}',
    '.ha-ag-h a{font-size:13px;font-weight:600;color:var(--accent);text-decoration:none;white-space:nowrap}.ha-ag-h a:hover{text-decoration:underline}',
    '.ha-res{width:100%;border-collapse:collapse;font-size:13.5px;table-layout:fixed}',
    '.ha-res th{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--text-2);text-align:left;padding:10px 16px 6px}',
    '.ha-res td{padding:9px 16px;border-top:1px solid var(--grid,var(--border));vertical-align:top;line-height:1.4}',
    '.ha-res .c-res{width:72px;color:var(--text-2);font-variant-numeric:tabular-nums}.ha-res .c-tx{white-space:normal;overflow-wrap:anywhere}',
    '.ha-res .c-tx small{display:block;color:var(--text-2);font-size:12px;margin-top:2px}',
    '.ha-res .c-n{width:110px;text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}',
    '.ha-res tfoot td{font-weight:600;border-top:1px solid var(--border)}',
    '.ha-empty{padding:14px 16px;color:var(--text-2);font-size:13.5px}',
    '@media (max-width:560px){.ha-dlg-bd{padding:0;place-items:end stretch}.ha-dlg,.ha-dlg.wide{width:100%;max-height:92vh;border-radius:14px 14px 0 0}.ha-dlg-h{padding:22px 56px 14px 20px}.ha-dlg-b{padding:16px 20px}.ha-dlg-f{padding:14px 20px}.ha-res .c-n.ht{display:none}.ha-res .c-res{width:60px}.ha-res th{padding:10px 10px 6px}.ha-res td{padding:9px 10px}.ha-kpis{grid-template-columns:repeat(3,1fr)}.ha-kpis>div{padding:12px}.ha-kpis .n{font-size:18px}.ha-kpis .l{font-size:11px}}',
    /* Menus déroulants (Colonnes, Archives) */
    '.ha-pop{position:absolute;z-index:50;top:calc(100% + 8px);right:0;width:340px;max-height:70vh;overflow:auto;background:var(--bg,var(--surface));border:1px solid var(--border);border-radius:12px;box-shadow:0 18px 50px rgba(0,0,0,.2);padding:6px 14px 12px;text-align:left}',
    '.ha-pop .r{display:flex;justify-content:space-between;align-items:center;gap:8px;border-bottom:1px solid var(--border);padding:10px 0;margin-bottom:6px;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--text-2)}',
    '.ha-pop{width:360px}.ha-pop .r>span{white-space:nowrap}',
    '@media (max-width:640px){.ha-pop{position:fixed;left:12px;right:12px;top:auto;bottom:12px;width:auto;max-height:72vh;box-shadow:0 -10px 60px rgba(0,0,0,.3)}}',
    '.ha-pop .r button{font:inherit;font-size:12.5px;font-weight:600;letter-spacing:0;text-transform:none;border:0;background:none;color:var(--accent);cursor:pointer;padding:0}',
    '.ha-arch{width:400px}.ha-arow{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}.ha-arow:last-child{border-bottom:0}.ha-arow strong{font-size:14px}',
    '.ha-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:2100;background:var(--text);color:var(--bg,#fff);padding:11px 18px;border-radius:10px;font-size:14px;box-shadow:0 10px 30px rgba(0,0,0,.2)}'
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
    portCard();
  }

  /* ---------- Top des immeubles : suite du classement + lien vers la page complète ---------- */
  function topMore() {
    var ch = document.getElementById('chTop'); if (!ch) return;
    var box = document.getElementById('haTopMore');
    if (!box) { box = h('div', 'ha-topmore'); box.id = 'haTopMore'; ch.parentNode.insertBefore(box, ch.nextSibling); var t = ch.parentNode.querySelector('h2'); if (t) t.textContent = 'Top 20 des immeubles'; }
    box.textContent = '';
    var L = getState().bld.filter(function (b) { return !(num(b.perdus) > 0 && num(b.h2627v) === 0) && num(b.h2627v) > 0; })
      .sort(function (a, b) { return num(b.h2627v) - num(a.h2627v); });
    var mx = L.length ? num(L[0].h2627v) : 1;
    L.slice(10, 20).forEach(function (b, i) {
      var r = h('div', 'ha-tr'); r.appendChild(h('span', 'n', String(i + 11)));
      r.appendChild(h('span', 'l', String(b.imm || '').trim()));
      var t = h('span', 't'), bar = h('i'); bar.style.width = (num(b.h2627v) / mx * 100).toFixed(1) + '%'; t.appendChild(bar); r.appendChild(t);
      r.appendChild(h('span', 'v', EUR.format(num(b.h2627v)))); box.appendChild(r);
    });
    var a = h('a', 'ha-allbtn', 'Voir tous les immeubles (' + L.length + ') →'); a.href = '/honoraires/classement'; a.target = '_top'; box.appendChild(a);
  }

  /* ---------- Honoraires TTC + annexes par portefeuille (à droite du bloc par portefeuille) ---------- */
  var pcard;
  function portCard() {
    var grid = document.querySelector('#tab-dash .grid.two'); if (!grid || !A || !YEARS.length) return;
    var ref = [].filter.call(grid.children, function (c) { var t = c.querySelector('h2'); return t && /^Honoraires TTC par portefeuille$/.test(t.textContent.trim()); })[0];
    if (!ref) return;
    if (!pcard) pcard = h('div', 'card ha-pcard');
    if (ref.nextSibling !== pcard) grid.insertBefore(pcard, ref.nextSibling);
    pcard.textContent = '';
    var y = curYear(), Y = A.years[y] || {}, B = Y.b || {}, part = !!Y.partial, k = part ? 12 / monthsElapsed(y) : 1;
    var st = getState(), P = {}, byCode = {};
    st.bld.forEach(function (b) {
      if (num(b.perdus) > 0 && num(b.h2627v) === 0) return;
      var port = String(b.port || '').trim().toUpperCase() || '—', c = code5(b.num);
      P[port] = P[port] || { port: port, hon: 0, ann: 0, lots: 0 };
      P[port].hon += num(b.h2627v); P[port].lots += num(b.lots); if (c) byCode[c] = port;
    });
    var orphan = 0;
    Object.keys(B).forEach(function (c) { var t = CATS.reduce(function (a, x, i) { return a + num(B[c][i]); }, 0) * k; var port = byCode[c]; if (port) P[port].ann += t; else orphan += t; });
    var L = Object.keys(P).map(function (p) { var o = P[p]; o.tot = o.hon + o.ann; return o; }).sort(function (a, b) { return b.tot - a.tot; });
    var tH = sum(L.map(function (o) { return o.hon; })), tA = sum(L.map(function (o) { return o.ann; })) + orphan, mx = Math.max.apply(null, L.map(function (o) { return o.tot; }).concat([1]));
    pcard.appendChild(h('h2', null, 'Honoraires TTC + annexes par portefeuille'));
    pcard.appendChild(h('p', 'desc', 'Honoraires 2026-2027 à voter + honoraires annexes ' + y + (part ? ' projetés sur 12 mois (facturé au ' + fdate(Y.end) + ')' : ' facturés')));
    var lg = h('div', 'legend'); [['var(--accent)', 'Honoraires à voter'], ['var(--ha-ann)', 'Honoraires annexes']].forEach(function (x) { var s2 = h('span'), i = h('i'); i.style.background = x[0]; s2.appendChild(i); s2.appendChild(document.createTextNode(x[1])); lg.appendChild(s2); }); pcard.appendChild(lg);
    var list = h('div', 'ha-pl');
    L.forEach(function (o) {
      var r = h('div', 'ha-pr'); r.title = (GN[o.port] || o.port) + ' · honoraires ' + EUR.format(o.hon) + ' + annexes ' + EUR.format(o.ann);
      r.appendChild(h('span', 'p', o.port));
      var t = h('span', 't'), a = h('i', 'h'), b2 = h('i', 'a'); a.style.width = (o.hon / mx * 100).toFixed(2) + '%'; b2.style.width = (o.ann / mx * 100).toFixed(2) + '%'; t.appendChild(a); t.appendChild(b2); r.appendChild(t);
      r.appendChild(h('span', 'v', EUR.format(o.tot)));
      r.appendChild(h('span', 's', '+' + PCT.format(o.hon ? o.ann / o.hon * 100 : 0) + ' %'));
      r.appendChild(h('span', 'l', o.lots ? EUR.format(o.tot / o.lots) + '/lot' : ''));
      list.appendChild(r);
    });
    pcard.appendChild(list);
    var ft = h('div', 'ha-pf');
    [['Honoraires à voter', EUR.format(tH)], ['Annexes' + (part ? ' (projection)' : ''), EUR.format(tA)], ['Total cabinet', EUR.format(tH + tA)], ['Part des annexes', PCT.format((tH + tA) ? tA / (tH + tA) * 100 : 0) + ' %']].forEach(function (x, i) { var d = h('div', i === 2 ? 'strong' : null); d.appendChild(h('span', null, x[0])); d.appendChild(h('b', null, x[1])); ft.appendChild(d); });
    pcard.appendChild(ft);
    if (orphan) pcard.appendChild(h('p', 'ha-pn', EUR.format(orphan) + ' d’annexes sur des immeubles absents du tableau (sortis ou non rattachés) : comptés dans le total, hors portefeuille.'));
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
  function exJul(d) { var y = d.getFullYear(), s = d.getMonth() >= 6 ? y : y - 1; return s + '-' + (s + 1); }
  var EX0 = exJul(new Date()), EX1 = (function () { var s = +EX0.split('-')[0] - 1; return s + '-' + (s + 1); })();
  var PV = C.pv || {};
  function pvSum(code, ex) { var r = PV[code] && PV[code][ex]; return r ? num(r.trav) : 0; }
  function travYear(code, ex) { if (!A || TRAV < 0 || !code) return null; var Y = A.years[ex]; if (!Y) return null; var B = Y.b || {}; return B[code] ? num(B[code][TRAV]) : 0; }
  /* HTV = honoraires travaux : montant facturé relevé dans ImmOpen (compte GID TRAVAUX) ; à défaut, total voté d'après les PV d'AG */
  /* HTV = honoraires du syndic sur travaux VOTÉS en AG (PV d'AG), exercice du 1er juillet au 30 juin */
  function pvFor(code, ex) { return pvSum(code, ex); }
  function hasHtv(code, ex) { return !!(PV[code] && PV[code][ex]); }
  function pvTitle(code, ex) { return hasHtv(code, ex) ? 'Cliquer sur i pour le détail' : ''; }
  /* Détail des honoraires travaux votés (HTV) : fenêtre ouverte par le bouton i de la cellule */
  /* Fenêtre modale commune : {eyebrow, title, sub, wide, role} → {body, foot, close} */
  function modal(o) {
    var bd = h('div', 'ha-dlg-bd'), dl = h('div', 'ha-dlg' + (o.wide ? ' wide' : '')); dl.setAttribute('role', o.role || 'dialog'); dl.setAttribute('aria-modal', 'true');
    var hd = h('div', 'ha-dlg-h'); if (o.eyebrow) hd.appendChild(h('div', 'ha-eyebrow', o.eyebrow));
    var t = h('h3', null, o.title); t.id = 'haDlgT' + Date.now(); dl.setAttribute('aria-labelledby', t.id); hd.appendChild(t);
    if (o.sub) hd.appendChild(h('p', 'sub', o.sub));
    var x = h('button', 'ha-x', '×'); x.type = 'button'; x.setAttribute('aria-label', 'Fermer');
    var body = h('div', 'ha-dlg-b'), foot = h('div', 'ha-dlg-f');
    dl.appendChild(hd); dl.appendChild(x); dl.appendChild(body); dl.appendChild(foot); bd.appendChild(dl);
    var prev = document.activeElement;
    function close() { bd.remove(); document.removeEventListener('keydown', kd, true); if (prev && prev.focus) try { prev.focus(); } catch (e) { /* rien */ } }
    function kd(e) { if (e.key === 'Escape') { e.stopPropagation(); close(); } }
    x.onclick = close; bd.addEventListener('click', function (e) { if (e.target === bd) close(); }); document.addEventListener('keydown', kd, true);
    setTimeout(function () { document.body.appendChild(bd); (foot.querySelector('.pri,.ha-danger') || foot.querySelector('button') || x).focus(); }, 0);
    return { body: body, foot: foot, close: close, el: dl };
  }
  function addressOf(code) { var tr = [].filter.call(document.querySelectorAll('#immTbl tbody tr'), function (r) { return rowCode(r) === code; })[0], i = tr && tr.querySelector('input[data-k="imm"]'); return i ? i.value : ''; }
  /* Détail des honoraires travaux votés (HTV) : fenêtre ouverte par le bouton i de la cellule */
  function pvDetail(code, ex) {
    var r = (PV[code] && PV[code][ex]) || { ags: [] }, ags = r.ags || [], nRes = ags.reduce(function (n, a) { return n + a.items.length; }, 0);
    var m = modal({ eyebrow: 'Honoraires travaux votés · ' + ex.replace('-', '–'), title: 'Immeuble ' + code + (addressOf(code) ? ' · ' + addressOf(code) : ''), sub: 'Résolutions « Honoraires du syndic » adoptées dans les PV des AG tenues du 1er juillet ' + ex.slice(0, 4) + ' au 30 juin ' + ex.slice(5) + '.', wide: true });
    var k = h('div', 'ha-kpis');
    [[EUR2.format(num(r.trav)), 'Total voté TTC'], [String(nRes), nRes > 1 ? 'Résolutions adoptées' : 'Résolution adoptée'], [String(ags.length), ags.length > 1 ? 'AG relevées' : 'AG relevée']].forEach(function (x) { var d = h('div'); d.appendChild(h('div', 'n', x[0])); d.appendChild(h('div', 'l', x[1])); k.appendChild(d); });
    m.body.appendChild(k);
    if (!ags.length) m.body.appendChild(h('p', null, 'Aucun PV d’AG relevé pour cet exercice.'));
    ags.forEach(function (a) {
      var card = h('section', 'ha-ag'), hd = h('div', 'ha-ag-h'), left = h('div');
      left.appendChild(h('strong', null, 'AG du ' + fdateLong(a.date)));
      var meta = []; if (a.pv) meta.push('PV remis le ' + fdate(a.pv)); if (a.gc) meta.push('gestion courante votée ' + EUR2.format(a.gc) + ' TTC');
      if (meta.length) left.appendChild(h('div', 'meta', meta.join(' · ')));
      hd.appendChild(left);
      if (a.link) { var lk = h('a', null, 'Ouvrir le mail du PV ↗'); lk.href = a.link; lk.target = '_blank'; lk.rel = 'noopener'; hd.appendChild(lk); }
      card.appendChild(hd);
      if (!a.items.length) { card.appendChild(h('div', 'ha-empty', 'Aucun honoraire sur travaux voté à cette AG.')); m.body.appendChild(card); return; }
      var tb = h('table', 'ha-res'), thead = h('thead'), trh = h('tr');
      [['Résol.', 'c-res'], ['Travaux', 'c-tx'], ['HT', 'c-n ht'], ['TTC', 'c-n']].forEach(function (x) { trh.appendChild(h('th', x[1], x[0])); });
      thead.appendChild(trh); tb.appendChild(thead);
      var tbody = h('tbody'), sh = 0, st = 0;
      a.items.forEach(function (t) {
        var tr = h('tr'); tr.appendChild(h('td', 'c-res', t.res));
        var tx = h('td', 'c-tx', t.objet); if (t.taux) tx.appendChild(h('small', null, 'Taux voté : ' + t.taux)); tr.appendChild(tx);
        tr.appendChild(h('td', 'c-n ht', t.ht ? EUR2.format(t.ht) : '–')); tr.appendChild(h('td', 'c-n', t.ttc ? EUR2.format(t.ttc) : '–'));
        sh += num(t.ht); st += num(t.ttc); tbody.appendChild(tr);
      });
      tb.appendChild(tbody);
      if (a.items.length > 1) { var tf = h('tfoot'), fr = h('tr'); fr.appendChild(h('td', 'c-res', '')); fr.appendChild(h('td', 'c-tx', 'Total de l’AG')); fr.appendChild(h('td', 'c-n ht', EUR2.format(sh))); fr.appendChild(h('td', 'c-n', EUR2.format(st))); tf.appendChild(fr); tb.appendChild(tf); }
      card.appendChild(tb); m.body.appendChild(card);
    });
    var ok = h('button', 'ha-b', 'Fermer'); ok.type = 'button'; ok.onclick = m.close; m.foot.appendChild(ok);
  }
  /* Mobile : onglets du tableau regroupés dans un menu déroulant */
  (function tabPop() {
    var list = document.querySelector('nav.tabs'); if (!list || list.parentNode.classList.contains('tabpop')) return;
    var wrap = h('div', 'tabpop'), btn = h('button', 'tabpop-btn'); btn.type = 'button'; btn.setAttribute('aria-haspopup', 'true');
    list.parentNode.insertBefore(wrap, list); wrap.appendChild(btn); wrap.appendChild(list);
    function sync() { var bs = [].slice.call(list.querySelectorAll('button')), t = bs.filter(function (b) { return b.getAttribute('aria-selected') === 'true'; })[0] || bs[0]; btn.textContent = t ? t.textContent.trim() : ''; btn.insertAdjacentHTML('beforeend', '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'); }
    btn.addEventListener('click', function (e) { e.stopPropagation(); wrap.classList.toggle('open'); });
    list.addEventListener('click', function () { setTimeout(function () { sync(); wrap.classList.remove('open'); }, 0); });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) wrap.classList.remove('open'); });
    new MutationObserver(sync).observe(list, { subtree: true, attributes: true, attributeFilter: ['aria-selected'] });
    sync();
  })();
  document.addEventListener('click', function (e) { var b = e.target.closest && e.target.closest('.ha-i'); if (!b) return; e.preventDefault(); e.stopPropagation(); pvDetail(b.dataset.code, b.dataset.ex); }, true);
  /* Colonnes ajoutées au tableau (calculées, non modifiables) */
  var EXTRA = [];
  if (false) EXTRA.push({ k: '_trav', label: travLabel, title: function () { return 'Honoraires de travaux facturés par GID (compte GID TRAVAUX), exercice ' + yInfo(curYear()); }, val: travFor, tip: function () { return ''; } });
  EXTRA.push({ k: '_pv0', ex: EX0, label: function () { return 'HTV ' + yShort(EX0); }, title: function () { return 'HTV : honoraires du syndic sur travaux votés en AG (PV des AG du 01/07/' + EX0.slice(0, 4) + ' au 30/06/' + EX0.slice(5) + '), détail avec le bouton i'; }, val: function (c) { return pvFor(c, EX0); }, tip: function (c) { return pvTitle(c, EX0); } });
  EXTRA.push({ k: '_pv1', ex: EX1, label: function () { return 'HTV ' + yShort(EX1); }, title: function () { return 'HTV : honoraires du syndic sur travaux votés en AG (PV des AG du 01/07/' + EX1.slice(0, 4) + ' au 30/06/' + EX1.slice(5) + '), détail avec le bouton i'; }, val: function (c) { return pvFor(c, EX1); }, tip: function (c) { return pvTitle(c, EX1); } });
  /* Fusion (oct. 2026) de « Hon. travaux GID » (_trav) dans HTV (_pv0) : on garde la position et la visibilité choisies pour la colonne qui portait les montants. Exécutée une fois (marqueur _m1). */
  (function migrateTrav() {
    var o = Array.isArray(P.hono_order) ? P.hono_order.slice() : [], hs = Array.isArray(P.hono_cols) ? P.hono_cols.slice() : [];
    if (o.indexOf('_m1') >= 0) return;
    var ti = o.indexOf('_trav');
    if (ti >= 0) { var pi = o.indexOf('_pv0'); if (pi >= 0) { o.splice(pi, 1); if (pi < ti) ti--; } o[ti] = '_pv0'; }
    var travHidden = hs.indexOf('_trav') >= 0;
    hs = hs.filter(function (k) { return k !== '_trav' && k !== '_pv0'; }); if (travHidden) hs.push('_pv0');
    o.push('_m1'); P.hono_order = o; P.hono_cols = hs; savePref('hono_order', o); savePref('hono_cols', hs);
  })();
  /* « AG tenue le » et « Remise PV » : complétés d'après le PV de l'AG annuelle (celle qui vote le contrat) de l'exercice HTV en cours,
     uniquement si la cellule est vide et si l'utilisateur peut modifier le tableau (la saisie passe par le tableau lui-même). */
  var filled = {};
  var IOX = {}; (Array.isArray(C.immopen) ? C.immopen : []).forEach(function (r) { IOX[r.code] = r; });
  var EX0_START = EX0.slice(0, 4) + '-07-01';
  function setCell(tr, k, v) {
    var inp = tr.querySelector('input[data-k="' + k + '"]'); if (!inp || v == null || v === '' || String(inp.value).trim() !== '') return;
    inp.value = v; inp.dispatchEvent(new Event('input', { bubbles: true })); syncTextDate(inp);
  }
  function fillAgDates(t) {
    if (C.ro) return;
    [].forEach.call(t.querySelectorAll('tbody tr'), function (tr) {
      var code = rowCode(tr); if (!code || filled[code]) return; filled[code] = 1;
      var r = PV[code] && PV[code][EX0], ag = r ? (r.ags || []).filter(function (a) { return a.gc != null; }).pop() : null;
      var io = IOX[code], last = io && io.last && io.last >= EX0_START ? io.last : null;
      setCell(tr, 'dateAG', ag ? ag.date : last);                    // AG annuelle : PV reçu, sinon dernière AG ImmOpen
      if (ag) { setCell(tr, 'datePV', ag.pv); if (ag.gc) setCell(tr, 'h2627e', String(ag.gc)); } // remise du PV et honoraires votés d'après le PV
    });
  }
  /* Dates en champ texte JJ/MM/AAAA (le champ calendrier d'origine reste caché et porte la valeur enregistrée) */
  function isoToFr(v) { var m = String(v || '').match(/^(\d{4})-(\d{2})-(\d{2})$/); return m ? m[3] + '/' + m[2] + '/' + m[1] : ''; }
  function frToIso(v) {
    v = String(v || '').trim(); if (!v) return '';
    var m = v.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{2,4})$/); if (m) { var y = m[3].length === 2 ? '20' + m[3] : m[3]; return y + '-' + ('0' + m[2]).slice(-2) + '-' + ('0' + m[1]).slice(-2); }
    m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/); return m ? v : null;
  }
  function syncTextDate(inp) { var tx = inp._tx; if (tx && document.activeElement !== tx) tx.value = isoToFr(inp.value); }
  function textDates(tr) {
    [].forEach.call(tr.querySelectorAll('input[type="date"]'), function (inp) {
      if (inp._tx) return;
      var tx = document.createElement('input'); tx.type = 'text'; tx.className = inp.className + ' ha-dtx'; tx.inputMode = 'numeric'; tx.maxLength = 10; tx.title = 'Date au format JJ/MM/AAAA';
      tx.value = isoToFr(inp.value); tx.readOnly = !!C.ro || inp.readOnly || inp.disabled; tx.style.cssText = inp.getAttribute('style') || '';
      inp._tx = tx; inp.style.display = 'none'; inp.parentNode.insertBefore(tx, inp.nextSibling);
      function commit() {
        var iso = frToIso(tx.value);
        if (iso === null) { tx.classList.add('ha-bad'); tx.title = 'Date attendue au format JJ/MM/AAAA'; return; }
        tx.classList.remove('ha-bad'); tx.title = '';
        if (iso !== inp.value) { inp.value = iso; inp.dispatchEvent(new Event('input', { bubbles: true })); }
        tx.value = isoToFr(iso);
      }
      tx.addEventListener('input', function (e) { e.stopPropagation(); }); // le tableau ne doit voir que le champ d'origine
      tx.addEventListener('change', function (e) { e.stopPropagation(); commit(); }); tx.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); commit(); tx.blur(); } });
    });
  }
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
        textDates(tr);
        var ref2 = tr.children[n] || null;
        EXTRA.forEach(function (x) { var v = x.val(code), td = h('td', 'ro r', v || (x.ex && hasHtv(code, x.ex)) ? EUR.format(v) : '–'); td.dataset.col = x.k; var tp = x.tip(code); if (tp) td.title = tp;
          if (x.ex && hasHtv(code, x.ex)) { var ib = h('button', 'ha-i', 'i'); ib.type = 'button'; ib.dataset.code = code; ib.dataset.ex = x.ex; ib.setAttribute('aria-label', 'Détail des honoraires travaux votés'); td.appendChild(ib); }
          tr.insertBefore(td, ref2); });
      }
      var f = document.getElementById('immFoot');
      if (f && f.children.length && !f.querySelector('[data-col]')) {
        for (var k = 0; k < n && k < f.children.length; k++) f.children[k].dataset.col = KEYS[k];
        var ref3 = f.children[n] || null;
        EXTRA.forEach(function (x, i) { var ft = h('td', null, EUR.format(tot[i])); ft.dataset.col = x.k; f.insertBefore(ft, ref3); });
      }
      applyOrder(); markRows();
    } finally { busy = false; }
    fillAgDates(t);
  }
  /* Ordre des colonnes : préférence propre à chaque utilisateur (hono_order) */
  function allKeys() { return KEYS.concat(EXTRA.map(function (x) { return x.k; })); }
  function colOrder() {
    var def = allKeys(), saved = Array.isArray(P.hono_order) ? P.hono_order.filter(function (k) { return def.indexOf(k) >= 0; }) : [];
    if (!saved.length) return def;
    // colonnes absentes de l'ordre enregistré : placées juste après leur voisine de gauche par défaut (sans décaler celles que l'utilisateur a placées)
    def.forEach(function (k, i) { if (saved.indexOf(k) >= 0) return; var j = i - 1; while (j >= 0 && saved.indexOf(def[j]) < 0) j--; saved.splice(j < 0 ? 0 : saved.indexOf(def[j]) + 1, 0, k); });
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
    var m = modal({ eyebrow: 'Confirmation', title: title, role: 'alertdialog' }), p = h('p', null, text); m.body.appendChild(p);
    var no = h('button', 'ha-b', 'Annuler'), ok = h('button', 'ha-b ha-danger', okLabel); no.type = ok.type = 'button';
    m.foot.appendChild(no); m.foot.appendChild(ok); no.onclick = m.close;
    ok.addEventListener('click', function () { ok.disabled = true; Promise.resolve(onOk()).then(m.close, function (e) { ok.disabled = false; p.textContent = e.message || 'Échec'; }); });
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
        if (!C.ro) { var rb = h('button', 'ha-b', 'Restaurer'); rb.type = 'button'; rb.addEventListener('click', function () { restore(a, rb); }); it.appendChild(rb); }
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

  /* ---------- État courant du tableau ---------- */
  function getState() {
    var st = null;
    try { st = JSON.parse(localStorage.getItem(C.key) || 'null'); } catch (e) { /* rien */ }
    if (!st || !st.bld) { try { st = JSON.parse((document.getElementById('seed') || {}).textContent || 'null'); } catch (e) { /* rien */ } }
    return st && st.bld ? st : { bld: [] };
  }
  function tri(lv) { var sv = document.createElementNS('http://www.w3.org/2000/svg', 'svg'); sv.setAttribute('viewBox', '0 0 24 24'); sv.setAttribute('class', 'hd-tri ' + lv); sv.setAttribute('aria-hidden', 'true'); sv.innerHTML = '<path d="M12 3 2 21h20L12 3z" fill="currentColor"/><path d="M12 10v5M12 18h.01" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>'; return sv; }
  var GN = { SR: 'Sébastien Raoult', SE: 'Sihame Eshaibi', LC: 'Laurent Chatelain', LD: 'Laurent Diez', KM: 'Kathy Maltez', NG: 'Namik Gadouche', AG: 'Angélique Grevet', ID: 'Ivana Debuire' };

  /* ---------- Optimisation & stratégie : tableau de décision ---------- */
  function decisionBoard() {
    var box = document.getElementById('tab-opti'); if (!box) return;
    var old = document.getElementById('hdBoard'); if (old) old.remove();
    var st = getState(), today = new Date().toISOString().slice(0, 10), M = C.mandats || {};
    var B = st.bld.map(function (b) { return { b: b, code: code5(b.num), lots: num(b.lots), perdus: num(b.perdus), h1: num(b.h2526), hv: num(b.h2627v), he: num(b.h2627e), ag: String(b.dateAG || ''), pv: String(b.datePV || ''), port: b.port || b.gest || '?' }; });
    var act = B.filter(function (x) { return !(x.perdus > 0 && !x.hv); }), lost = B.filter(function (x) { return x.perdus > 0 && !x.hv; });
    var sum = function (a, f) { return a.reduce(function (s, x) { return s + f(x); }, 0); };
    var tot1 = sum(act, function (x) { return x.h1; }), totv = sum(act, function (x) { return x.hv; });
    var held = act.filter(function (x) { return x.ag; }), voted = held.filter(function (x) { return x.he > 0; });
    var gapAll = sum(voted, function (x) { return x.he - x.hv; });
    var lotsP = act.filter(function (x) { return x.lots > 0 && x.hv > 0; }).map(function (x) { return x.hv / x.lots; }).sort(function (a, b) { return a - b; });
    var med = lotsP.length ? lotsP[Math.floor(lotsP.length / 2)] : 0;
    var days = function (d) { return Math.round((new Date(today) - new Date(d)) / 864e5); };
    var alerts = [
      { lv: 'crit', t: 'AG votées sous la proposition', items: voted.filter(function (x) { return x.he < x.hv - 1; }), v: function (x) { return x.he - x.hv; }, fmt: 'eur', todo: 'Comprendre la négociation et la reporter sur le contrat ; préparer la hausse au prochain renouvellement.' },
      { lv: 'crit', t: 'PV non remis plus de 21 jours après l’AG', items: held.filter(function (x) { return !x.pv && days(x.ag) > 21; }), v: function (x) { return days(x.ag); }, fmt: 'j', todo: 'Relancer le gestionnaire : le PV doit être diffusé dans le mois qui suit l’AG.' },
      { lv: 'crit', t: 'Mandats expirés ou expirant sous 60 jours', items: act.filter(function (x) { var m = M[x.code]; return m && m.fin_mandat && days(m.fin_mandat) > -60 && !(m.ag_date && m.ag_date < m.fin_mandat && m.ag_date >= today); }), v: function (x) { return M[x.code].fin_mandat; }, fmt: 'date', todo: 'Planifier l’AG de renouvellement avant la fin du mandat (risque de perte du mandat).' },
      { lv: 'crit', t: 'Convocations en retard', items: act.filter(function (x) { var m = M[x.code]; return m && m.limite_convoc && m.limite_convoc < today && !m.convoc && m.ag_date && m.ag_date >= today; }), v: function (x) { return M[x.code].ag_date; }, fmt: 'date', todo: 'Envoyer les convocations ou reporter l’AG.' },
      { lv: 'warn', t: 'Immeubles sans hausse 2026-2027', items: act.filter(function (x) { return x.hv > 0 && x.h1 > 0 && x.hv <= x.h1; }), v: function (x) { return x.hv - x.h1; }, fmt: 'eur', todo: 'Vérifier l’indexation et proposer une hausse au prochain vote.' },
      { lv: 'warn', t: 'Honoraires sous 70 % de la médiane au lot', items: act.filter(function (x) { return med && x.lots > 0 && x.hv > 0 && x.hv / x.lots < med * 0.7; }), v: function (x) { return Math.max(0, x.lots * med * 0.85 - x.hv); }, fmt: 'eur', todo: 'Potentiel de rattrapage à porter au prochain renouvellement (médiane : ' + EUR.format(med) + ' / lot).' }
    ].filter(function (a) { return a.items.length; });
    var nCrit = alerts.filter(function (a) { return a.lv === 'crit'; }).reduce(function (n, a) { return n + a.items.length; }, 0);

    var w = h('div', 'hd'); w.id = 'hdBoard';
    var hd = h('div', 'hd-h'); var l = h('div');
    l.appendChild(h('div', 'hd-eye', 'Tableau de décision · ' + new Date().toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })));
    l.appendChild(h('h2', 'hd-t', 'Honoraires 2026-2027 : où agir maintenant'));
    hd.appendChild(l);
    var badge = h('div', 'hd-badge ' + (nCrit ? 'crit' : 'ok')); if (nCrit) badge.appendChild(tri('crit')); badge.appendChild(document.createTextNode(nCrit ? nCrit + ' alerte' + (nCrit > 1 ? 's' : '') + ' à traiter' : 'Aucune alerte critique')); hd.appendChild(badge);
    w.appendChild(hd);
    var k = h('div', 'hd-k');
    [[EUR.format(totv), 'Honoraires 26-27 à voter', (tot1 ? (totv / tot1 * 100 - 100).toFixed(1).replace('.', ',') + ' % vs 25-26' : '')],
     [held.length + ' / ' + act.length, 'AG tenues', (act.length ? Math.round(held.length / act.length * 100) + ' % du portefeuille' : '')],
     [(gapAll >= 0 ? '+' : '') + EUR.format(gapAll), 'Voté vs proposé', voted.length + ' AG votée' + (voted.length > 1 ? 's' : ''), gapAll < 0 ? 'neg' : 'pos'],
     [String(lost.length), 'Immeubles perdus', EUR.format(sum(lost, function (x) { return x.h1; })) + ' d’honoraires 25-26', lost.length ? 'neg' : ''],
     [med ? EUR.format(med) : '–', 'Médiane au lot', 'honoraires 26-27 / lots']
    ].forEach(function (x) { var c = h('div', 'hd-kc ' + (x[3] || '')); c.appendChild(h('div', 'hd-kv', x[0])); c.appendChild(h('div', 'hd-kl', x[1])); if (x[2]) c.appendChild(h('div', 'hd-kn', x[2])); k.appendChild(c); });
    w.appendChild(k);

    w.appendChild(h('h3', 'hd-st', 'Alertes et points de vigilance'));
    if (!alerts.length) w.appendChild(h('p', 'hd-empty', 'Aucune alerte : toutes les vérifications sont au vert.'));
    var ag = h('div', 'hd-alerts');
    alerts.forEach(function (a) {
      var tot = a.fmt === 'eur' ? sum(a.items, a.v) : null;
      var d = h('details', 'hd-al ' + a.lv), sm = h('summary');
      sm.appendChild(tri(a.lv)); var tx = h('span', 'hd-al-t'); tx.appendChild(h('strong', null, a.t)); tx.appendChild(h('span', 'hd-al-n', a.items.length + ' immeuble' + (a.items.length > 1 ? 's' : '') + (tot != null ? ' · ' + (tot > 0 && a.lv === 'warn' && a.t.indexOf('médiane') > 0 ? 'potentiel ' : '') + EUR.format(tot) : ''))); sm.appendChild(tx);
      d.appendChild(sm); d.appendChild(h('p', 'hd-todo', a.todo));
      var tb = h('table', 'hd-tbl'); a.items.slice().sort(function (x, y) { var vx = a.v(x), vy = a.v(y); return typeof vx === 'number' ? (a.lv === 'crit' && a.fmt === 'eur' ? vx - vy : vy - vx) : String(vx).localeCompare(String(vy)); }).forEach(function (x) {
        var tr = h('tr'), v = a.v(x); tr.appendChild(h('td', 'c', x.code)); tr.appendChild(h('td', null, x.b.imm || '')); tr.appendChild(h('td', 'p', GN[x.port] || x.port));
        tr.appendChild(h('td', 'n', a.fmt === 'eur' ? EUR.format(v) : a.fmt === 'j' ? v + ' j' : fdate(v))); tb.appendChild(tr);
      });
      d.appendChild(tb); ag.appendChild(d);
    });
    w.appendChild(ag);

    w.appendChild(h('h3', 'hd-st', 'Par gestionnaire'));
    var ports = {}; act.forEach(function (x) { (ports[x.port] = ports[x.port] || []).push(x); });
    var alertBy = {}; alerts.forEach(function (a) { a.items.forEach(function (x) { var o = alertBy[x.port] = alertBy[x.port] || { crit: 0, warn: 0 }; o[a.lv]++; }); });
    var pt = h('table', 'hd-tbl hd-port'), th = h('tr');
    ['Gestionnaire', 'Immeubles', 'AG tenues', 'À voter 26-27', 'Évol.', 'Voté vs proposé', '€ / lot', 'Alertes'].forEach(function (x, i) { th.appendChild(h('th', i ? 'n' : null, x)); }); pt.appendChild(th);
    Object.keys(ports).sort(function (a, b) { return sum(ports[b], function (x) { return x.hv; }) - sum(ports[a], function (x) { return x.hv; }); }).forEach(function (p) {
      var a = ports[p], hv = sum(a, function (x) { return x.hv; }), h1 = sum(a, function (x) { return x.h1; }), lots = sum(a, function (x) { return x.lots; });
      var vo = a.filter(function (x) { return x.ag && x.he > 0; }), gp = sum(vo, function (x) { return x.he - x.hv; }), al = alertBy[p] || { crit: 0, warn: 0 };
      var tr = h('tr'); tr.appendChild(h('td', null, GN[p] || p)); tr.appendChild(h('td', 'n', String(a.length)));
      tr.appendChild(h('td', 'n', a.filter(function (x) { return x.ag; }).length + ' / ' + a.length)); tr.appendChild(h('td', 'n', EUR.format(hv)));
      tr.appendChild(h('td', 'n', h1 ? (hv / h1 * 100 - 100).toFixed(1).replace('.', ',') + ' %' : '–'));
      var g = h('td', 'n' + (gp < -1 ? ' neg' : ''), vo.length ? (gp >= 0 ? '+' : '') + EUR.format(gp) : '–'); tr.appendChild(g);
      tr.appendChild(h('td', 'n', lots ? EUR.format(hv / lots) : '–'));
      var ac = h('td', 'n'); if (al.crit) { var s1 = h('span', 'hd-pill crit'); s1.appendChild(tri('crit')); s1.appendChild(document.createTextNode(al.crit)); ac.appendChild(s1); } if (al.warn) { var s2 = h('span', 'hd-pill warn'); s2.appendChild(tri('warn')); s2.appendChild(document.createTextNode(al.warn)); ac.appendChild(s2); } if (!al.crit && !al.warn) ac.textContent = '—'; tr.appendChild(ac);
      pt.appendChild(tr);
    });
    var wrapT = h('div', 'hd-scroll'); wrapT.appendChild(pt); w.appendChild(wrapT);
    w.appendChild(h('p', 'hd-foot', 'Calculé à partir du tableau des honoraires, des PV d’AG relevés dans vos mails et du planning ImmOpen. Contrôle et mise à jour automatiques chaque lundi à 7 h.'));
    box.insertBefore(w, box.firstChild);
    // le reste de l'onglet (analyse détaillée, simulations) est replié sous le tableau de décision
    if (!document.getElementById('hdMore')) {
      var more = h('details', 'hd-more'); more.id = 'hdMore'; more.appendChild(h('summary', null, 'Analyse détaillée, simulations et tableaux complets'));
      [].slice.call(box.children).forEach(function (c) { if (c !== w && c.id !== 'gidAudit' && c !== more) more.appendChild(c); });
      box.appendChild(more);
    }
    var au = document.getElementById('gidAudit'); if (au && au.previousSibling !== w) box.insertBefore(au, w.nextSibling);
  }

  /* ---------- Onglet « Modifications » ---------- */
  function modifsTab() {
    var nav = document.querySelector('nav.tabs'), opti = document.getElementById('tab-opti'); if (!nav || !opti || document.getElementById('tab-modifs')) return;
    var btn = h('button', null, 'Modifications'); btn.setAttribute('role', 'tab'); btn.dataset.tab = 'modifs'; btn.setAttribute('aria-selected', 'false');
    var ob = nav.querySelector('[data-tab="opti"]'); nav.insertBefore(btn, ob ? ob.nextSibling : null);
    var sec = h('section', 'tab'); sec.id = 'tab-modifs'; opti.parentNode.insertBefore(sec, opti.nextSibling);
    btn.addEventListener('click', function () {
      [].forEach.call(nav.querySelectorAll('button'), function (x) { x.setAttribute('aria-selected', x === btn ? 'true' : 'false'); });
      [].forEach.call(document.querySelectorAll('section.tab'), function (x) { x.classList.toggle('active', x === sec); });
    });
    var L = Array.isArray(C.changes) ? C.changes : [], mk = function (t) { var d = new Date(t * 1000); return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2); };
    var cur = mk(Date.now() / 1000), show = L.filter(function (c) { return mk(c.at) === cur; }), label = cur;
    if (!show.length && L.length) { label = mk(L[0].at); show = L.filter(function (c) { return mk(c.at) === label; }); }
    var p = label.split('-'), hd = h('div', 'hm-h');
    hd.appendChild(h('div', 'hd-eye', 'Journal des modifications · ' + MOIS_L[+p[1] - 1] + ' ' + p[0]));
    hd.appendChild(h('h2', 'hd-t', 'Modifications du tableau des honoraires'));
    hd.appendChild(h('p', 'hm-s', 'Toutes les modifications des onglets Honoraires (saisies et mises à jour automatiques), regroupées par jour. ' + show.length + ' modification' + (show.length > 1 ? 's' : '') + ' ce mois-ci.'));
    sec.appendChild(hd);
    if (!show.length) sec.appendChild(h('p', 'hd-empty', 'Aucune modification enregistrée pour l’instant. Les prochaines saisies apparaîtront ici.'));
    var byDay = {}; show.forEach(function (c) { var d = new Date(c.at * 1000).toLocaleDateString('fr-CA'); (byDay[d] = byDay[d] || []).push(c); });
    Object.keys(byDay).sort().reverse().forEach(function (d) {
      var card = h('section', 'hm-day'), dt = new Date(d + 'T12:00:00');
      var top = h('div', 'hm-date'); top.appendChild(h('strong', null, dt.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))); top.appendChild(h('span', null, byDay[d].length + ' modification' + (byDay[d].length > 1 ? 's' : ''))); card.appendChild(top);
      var ul = h('ul', 'hm-list');
      byDay[d].forEach(function (c) {
        var li = h('li'); li.appendChild(h('span', 'hm-time', new Date(c.at * 1000).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })));
        var wh = h('span', 'hm-what'); wh.appendChild(h('span', 'hm-tab', c.tab)); if (c.ref) { wh.appendChild(document.createTextNode(' · ')); wh.appendChild(h('strong', null, c.ref)); }
        var f = h('span', 'hm-field', c.field); if (c.old !== '' || c.new !== '') { f.appendChild(document.createTextNode(' : ')); f.appendChild(h('del', null, c.old !== '' ? c.old : 'vide')); f.appendChild(document.createTextNode(' → ')); f.appendChild(h('ins', null, c.new !== '' ? c.new : 'vide')); } wh.appendChild(f);
        li.appendChild(wh); li.appendChild(h('span', 'hm-who' + (c.source === 'auto' ? ' auto' : ''), c.user)); ul.appendChild(li);
      });
      card.appendChild(ul); sec.appendChild(card);
    });
    var a = h('a', 'hm-more', 'Voir les mois précédents →'); a.href = '/honoraires/modifications'; a.target = '_top'; sec.appendChild(a);
    if (location.hash === '#modifs') btn.click();
  }
  var MOIS_L = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

  function init() {
    applyHidden(); colPicker(); archPicker(); decorate(); renderCard(); crossCheck(); modifsTab(); decisionBoard(); topMore();
    var cht = document.getElementById('chTop'); if (cht) new MutationObserver(function () { topMore(); }).observe(cht, { childList: true });
    window.addEventListener('load', function () { setTimeout(decisionBoard, 50); });
    var ob = document.querySelector('nav.tabs [data-tab="opti"]'); if (ob) ob.addEventListener('click', function () { setTimeout(decisionBoard, 0); });
    if (location.hash === '#annexes' && A) openDetail(true);
    var t = document.getElementById('immTbl');
    var ccT = null;
    if (t) new MutationObserver(function () { if (!busy) { decorate(); clearTimeout(ccT); ccT = setTimeout(crossCheck, 400); } }).observe(t, { childList: true, subtree: true });
    var g = document.querySelector('#tab-dash .grid.two');
    if (g) new MutationObserver(function () { if (card && !g.contains(card)) { card = null; renderCard(); } else if (pcard && !g.contains(pcard)) portCard(); }).observe(g, { childList: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
