/* Liseuse EPUB — pagination, réglages d'affichage, sommaire, signets, surlignements et notes, recherche, synchronisation de la position. */
(function () {
  'use strict';
  function $(id) { return document.getElementById(id); }
  function el(t, c, x) { var e = document.createElement(t); if (c) e.className = c; if (x != null) e.textContent = x; return e; }
  var CFG = JSON.parse($('bookCfg').textContent);
  var frame = $('rdFrame'), body = document.body;
  var api = function (a, d, keep) {
    return fetch('/api/' + a, { method: 'POST', credentials: 'same-origin', keepalive: !!keep, headers: { 'Content-Type': 'application/json', 'X-CSRF': CFG.csrf }, body: JSON.stringify(d) })
      .then(function (r) { return r.json(); }).catch(function () { return {}; });
  };

  /* ---------- Réglages (mémorisés dans ce navigateur) ---------- */
  var DEF = { size: 100, font: 'literata', lh: 1.55, mg: 1, cols: 'auto', just: 1, theme: 'white' };
  var P = (function () { try { return Object.assign({}, DEF, JSON.parse(localStorage.getItem('rd_prefs') || '{}')); } catch (e) { return Object.assign({}, DEF); } })();
  function savePrefs() { try { localStorage.setItem('rd_prefs', JSON.stringify(P)); } catch (e) { /* stockage indisponible */ } }
  var FONTS = { literata: '"Literata", Georgia, serif', lora: '"Lora", Georgia, serif', georgia: 'Georgia, "Times New Roman", serif', sans: '"Source Sans 3", "Segoe UI", sans-serif', verdana: 'Verdana, Geneva, sans-serif' };
  var THEMES = { white: ['#ffffff', '#1d1d1f', '#8a5a14'], sepia: ['#f4ecd8', '#5b4636', '#8a5a14'], green: ['#dcebd8', '#24321f', '#2e5d2a'], dark: ['#141414', '#d7d7d2', '#d9a85a'] };
  var HLC = { yellow: 'rgba(243,211,74,.55)', blue: 'rgba(127,180,240,.5)', pink: 'rgba(243,154,192,.5)', orange: 'rgba(245,167,91,.55)' };

  /* ---------- État ---------- */
  var book, toc = [], CH = [], cum = [], total = 1;
  var cur = { ch: -1, page: 0, pages: 1, W: 1, off: 0 }, doc = null, nodes = [], rendering = null;
  var BM = Array.isArray(CFG.bookmarks) ? CFG.bookmarks : [], HL = Array.isArray(CFG.highlights) ? CFG.highlights : [];
  var blobs = {};

  function norm(s) { var o = ''; for (var i = 0; i < s.length; i++) { var c = s.charAt(i), n = c.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); o += n.charAt(0) || c; } return o; }
  function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }
  function toast(m) { var t = $('rdToast'); t.textContent = m; t.hidden = false; clearTimeout(toast.t); toast.t = setTimeout(function () { t.hidden = true; }, 1800); }

  /* ---------- Chargement ---------- */
  function prepare(i) {
    // Chapitre i : document XHTML nettoyé (scripts retirés, images et liens résolus). Renvoie {body, css}
    var it = book.spine[i], f = book.zip.file(it.href);
    if (!f) return Promise.resolve({ body: document.implementation.createHTMLDocument('').body, css: '' });
    return f.async('string').then(function (txt) {
      var d = EpubLib.parseXml(txt), bd = d.body || d.getElementsByTagName('body')[0] || d.documentElement;
      [].slice.call(d.querySelectorAll('script,iframe,object,embed,form,noscript')).forEach(function (n) { n.remove(); });
      [].slice.call(bd.getElementsByTagName('*')).forEach(function (n) {
        [].slice.call(n.attributes).forEach(function (a) { if (/^on/i.test(a.name) || /^\s*javascript:/i.test(a.value)) n.removeAttribute(a.name); });
      });
      return { doc: d, body: bd, path: it.href };
    });
  }
  function blobUrl(path) {
    if (blobs[path]) return Promise.resolve(blobs[path]);
    var f = book.zip.file(path); if (!f) return Promise.resolve('');
    var m = (book.byHref[path] || {}).type || '';
    return f.async('blob').then(function (b) { return (blobs[path] = URL.createObjectURL(m ? new Blob([b], { type: m }) : b)); });
  }
  function cssFix(css, base) {
    var urls = [], re = /url\(\s*(['"]?)([^'")]+)\1\s*\)/g, m;
    while ((m = re.exec(css))) if (!/^(data:|blob:|https?:)/i.test(m[2])) urls.push(m[2]);
    return Promise.all(urls.map(function (u) { return blobUrl(EpubLib.resolve(base, u)).then(function (b) { return [u, b]; }); })).then(function (pairs) {
      pairs.forEach(function (p) { if (p[1]) css = css.split(p[0]).join(p[1]); });
      return css.replace(/@import[^;]+;/g, '');
    });
  }
  function bookCss(pc) {
    var d = pc.doc, jobs = [];
    [].slice.call(d.getElementsByTagName('link')).forEach(function (l) {
      if (!/stylesheet/i.test(l.getAttribute('rel') || '')) return;
      var p = EpubLib.resolve(pc.path, l.getAttribute('href')), f = book.zip.file(p);
      if (f) jobs.push(f.async('string').then(function (t) { return cssFix(t, p); }));
    });
    [].slice.call(d.getElementsByTagName('style')).forEach(function (s) { jobs.push(cssFix(s.textContent, pc.path)); });
    return Promise.all(jobs).then(function (a) { return a.join('\n'); });
  }
  function resolveMedia(pc) {
    var jobs = [];
    [].slice.call(pc.body.getElementsByTagName('*')).forEach(function (n) {
      var ln = (n.localName || '').toLowerCase();
      if (ln === 'img' && n.getAttribute('src')) jobs.push(blobUrl(EpubLib.resolve(pc.path, n.getAttribute('src'))).then(function (u) { n.setAttribute('src', u); n.removeAttribute('srcset'); }));
      if (ln === 'image') {
        var h = n.getAttribute('href') || n.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
        if (h) jobs.push(blobUrl(EpubLib.resolve(pc.path, h)).then(function (u) { n.setAttribute('href', u); n.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', u); }));
      }
      if (ln === 'a' && n.getAttribute('href')) {
        var href = n.getAttribute('href');
        if (/^(https?:|mailto:)/i.test(href)) n.setAttribute('data-ext', href);
        else n.setAttribute('data-int', EpubLib.resolve(pc.path, href) + (href.indexOf('#') >= 0 ? '#' + href.split('#')[1] : ''));
        n.setAttribute('href', '#');
      }
    });
    return Promise.all(jobs);
  }

  function indexAll() {
    // Texte de chaque chapitre : pourcentage, temps restant et recherche
    return book.spine.reduce(function (p, it, i) {
      return p.then(function () { return prepare(i).then(function (pc) { var t = pc.body.textContent || ''; CH[i] = { path: it.href, text: t, norm: null, len: t.length }; }); });
    }, Promise.resolve()).then(function () {
      var c = 0; cum = CH.map(function (x) { var v = c; c += x.len; return v; }); total = Math.max(1, c);
    });
  }

  /* ---------- Rendu d'un chapitre ---------- */
  function skeleton(css) {
    return '<!doctype html><html><head><meta charset="utf-8">' +
      '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Literata:ital,opsz,wght@0,7..72,400;0,7..72,600;1,7..72,400&family=Source+Sans+3:wght@400;600&display=swap">' +
      '<style id="bk">' + css.replace(/<\/style/gi, '') + '</style><style id="rd"></style></head><body></body></html>';
  }
  function render(i) {
    if (rendering && rendering.i === i) return rendering.p;
    var p = prepare(i).then(function (pc) {
      return Promise.all([bookCss(pc), resolveMedia(pc)]).then(function (r) {
        return new Promise(function (ok) {
          frame.onload = function () {
            doc = frame.contentDocument;
            [].slice.call(pc.body.attributes).forEach(function (a) { if (a.name !== 'style') try { doc.body.setAttribute(a.name, a.value); } catch (e) { /* attribut XML */ } });
            [].slice.call(pc.body.childNodes).forEach(function (n) { doc.body.appendChild(doc.importNode(n, true)); });
            cur.ch = i; hookDoc(); styleDoc(); applyHL(); buildNodes();
            var imgs = [].slice.call(doc.images).filter(function (im) { return !im.complete; });
            Promise.race([Promise.all(imgs.map(function (im) { return new Promise(function (r2) { im.onload = im.onerror = r2; }); })), new Promise(function (r3) { setTimeout(r3, 1500); })])
              .then(function () { return doc.fonts ? Promise.race([doc.fonts.ready, new Promise(function (r4) { setTimeout(r4, 1200); })]) : null; })
              .then(function () { measure(); ok(); });
          };
          frame.srcdoc = skeleton(r[0]);
        });
      });
    });
    rendering = { i: i, p: p.then(function () { rendering = null; }, function (e) { rendering = null; throw e; }) };
    return rendering.p;
  }
  function geometry() {
    var W = frame.clientWidth, H = frame.clientHeight;
    var cols = P.cols === 'auto' ? (W >= 920 && W > H * 1.1 ? 2 : 1) : (+P.cols === 2 && W >= 640 ? 2 : 1);
    var pad = [[14, 24], [22, 48], [30, 84]][+P.mg] || [22, 48];
    var padH = W < 520 ? Math.round(pad[0] * 0.9) : pad[1] * (cols === 2 ? 0.8 : 1);
    return { W: W, H: H, cols: cols, padH: Math.round(padH), padV: W < 520 ? 18 : 30 };
  }
  function styleDoc() {
    var g = geometry(), th = THEMES[P.theme] || THEMES.white, ff = FONTS[P.font];
    var css = 'html{height:100%!important;overflow:hidden!important;font-size:' + P.size + '%!important;background:' + th[0] + '!important}' +
      'body{margin:0!important;height:100%!important;max-width:none!important;width:auto!important;box-sizing:border-box!important;padding:' + g.padV + 'px ' + g.padH + 'px!important;' +
      'column-count:' + g.cols + '!important;column-gap:' + (2 * g.padH) + 'px!important;column-fill:auto!important;overflow:visible!important;' +
      'background:' + th[0] + '!important;color:' + th[1] + '!important;-webkit-hyphens:auto;hyphens:auto;overflow-wrap:break-word;' + (ff ? 'font-family:' + ff + '!important;' : '') + '}' +
      'body *:not(mark){color:inherit!important;background-color:transparent!important;' + (ff ? 'font-family:inherit!important;' : '') + '}' +
      'body,p,li,div,blockquote,dd,dt,td{line-height:' + P.lh + '!important}' +
      (P.just ? 'p{text-align:justify!important}' : 'p{text-align:start!important}') +
      'img,svg,video{max-width:100%!important;max-height:' + (g.H - 2 * g.padV - 4) + 'px!important;height:auto;object-fit:contain;break-inside:avoid}' +
      'a{color:' + th[2] + '!important;text-decoration:none}' +
      'mark.hl{color:inherit!important;border-radius:2px;cursor:pointer;padding:0}' +
      Object.keys(HLC).map(function (k) { return 'mark.hl-' + k + '{background:' + HLC[k] + '!important}'; }).join('') +
      'mark.hl.note{border-bottom:2px dotted currentColor}mark.hl-search{background:rgba(232,140,40,.55)!important;color:inherit!important}' +
      '::selection{background:rgba(120,160,230,.45)}';
    doc.getElementById('rd').textContent = css;
    body.dataset.theme = P.theme;
  }
  function measure() {
    var g = geometry(); cur.W = g.W;
    doc.scrollingElement.scrollLeft = 0;
    cur.pages = Math.max(1, Math.round(doc.scrollingElement.scrollWidth / g.W));
  }

  /* ---------- Correspondance texte ↔ position ---------- */
  function buildNodes() {
    nodes = []; var w = doc.createTreeWalker(doc.body, NodeFilter.SHOW_TEXT), n, o = 0;
    while ((n = w.nextNode())) { nodes.push({ n: n, s: o }); o += n.data.length; }
  }
  function offsetOf(container, offset) {
    var r = doc.createRange(); r.setStart(doc.body, 0);
    try { r.setEnd(container, offset); } catch (e) { return 0; }
    return r.toString().length;
  }
  function locate(off) { // {node, offset} pour un décalage de caractères
    if (!nodes.length) return null;
    var lo = 0, hi = nodes.length - 1;
    while (lo < hi) { var mid = (lo + hi + 1) >> 1; if (nodes[mid].s <= off) lo = mid; else hi = mid - 1; }
    var x = nodes[lo]; return { node: x.n, offset: Math.max(0, Math.min(x.n.data.length, off - x.s)) };
  }
  function pageOfOffset(off) {
    var L = locate(off); if (!L) return 0;
    var r = doc.createRange(), n = L.node, o = Math.min(L.offset, Math.max(0, n.data.length - 1));
    r.setStart(n, o); r.setEnd(n, Math.min(n.data.length, o + 1));
    var rc = r.getClientRects()[0] || r.getBoundingClientRect();
    if (!rc || (!rc.width && !rc.height && !rc.left)) { var pe = n.parentElement; rc = pe ? pe.getBoundingClientRect() : null; }
    if (!rc) return 0;
    return Math.max(0, Math.min(cur.pages - 1, Math.floor((rc.left + doc.scrollingElement.scrollLeft + 1) / cur.W)));
  }
  function pageOfNode(elm) { var rc = elm.getBoundingClientRect(); return Math.max(0, Math.min(cur.pages - 1, Math.floor((rc.left + doc.scrollingElement.scrollLeft + 1) / cur.W))); }
  function pageStart(p) { // premier caractère de la page p (recherche dichotomique sur les nœuds de texte puis sur les caractères)
    if (p <= 0 || !nodes.length) return 0;
    var lo = 0, hi = nodes.length - 1, ans = nodes.length;
    while (lo <= hi) { var mid = (lo + hi) >> 1; if (pageOfOffset(nodes[mid].s + nodes[mid].n.data.length - 1) >= p) { ans = mid; hi = mid - 1; } else lo = mid + 1; }
    if (ans >= nodes.length) return CH[cur.ch].len;
    var a = nodes[ans].s, b = nodes[ans].s + nodes[ans].n.data.length - 1;
    while (a < b) { var m = (a + b) >> 1; if (pageOfOffset(m) >= p) b = m; else a = m + 1; }
    return a;
  }

  /* ---------- Navigation ---------- */
  function setPage(p, silent) {
    cur.page = Math.max(0, Math.min(cur.pages - 1, p));
    doc.scrollingElement.scrollLeft = cur.page * cur.W;
    cur.off = pageStart(cur.page);
    clearFlash(); hideSel(); ui();
    if (!silent) savePos();
  }
  function place(t) {
    t = t || {};
    if (t.end) return setPage(cur.pages - 1);
    if (t.frag) { var e = doc.getElementById(t.frag); return setPage(e ? pageOfNode(e) : 0); }
    if (t.off != null) { setPage(pageOfOffset(Math.min(t.off, Math.max(0, CH[cur.ch].len - 1)))); if (t.flash) flash(t.flash[0], t.flash[1]); return; }
    setPage(t.page || 0);
  }
  function goTo(ch, t) {
    ch = Math.max(0, Math.min(book.spine.length - 1, ch));
    if (ch === cur.ch && doc) return Promise.resolve(place(t));
    return render(ch).then(function () { place(t); });
  }
  function next() { if (cur.page < cur.pages - 1) setPage(cur.page + 1); else if (cur.ch < book.spine.length - 1) goTo(cur.ch + 1, { page: 0 }); else toast('Fin du livre'); }
  function prev() { if (cur.page > 0) setPage(cur.page - 1); else if (cur.ch > 0) goTo(cur.ch - 1, { end: true }); }
  function goPercent(pct) {
    var target = Math.max(0, Math.min(total - 1, pct / 100 * total)), i = 0;
    while (i < CH.length - 1 && cum[i + 1] <= target) i++;
    goTo(i, { off: Math.round(target - cum[i]) });
  }
  function percent() { return CH.length ? Math.min(100, (cum[cur.ch] + cur.off) / total * 100) : 0; }

  var posT;
  function savePos(now) {
    clearTimeout(posT);
    var send = function () { api('books/state', { id: CFG.id, pos: { ch: cur.ch, off: cur.off }, percent: Math.round(percent() * 10) / 10 }, now); };
    try { localStorage.setItem('rd_pos_' + CFG.id, JSON.stringify({ ch: cur.ch, off: cur.off, at: Date.now() })); } catch (e) { /* facultatif */ }
    if (now) send(); else posT = setTimeout(send, 1200);
  }
  function saveMarks() { api('books/state', { id: CFG.id, bookmarks: BM, highlights: HL }); }

  /* ---------- Interface ---------- */
  function chapterTitle(ch) {
    var before = '', first = '', inCh = '';
    toc.forEach(function (x) {
      if (x.ch < 0 || x.ch > ch) return;
      if (x.ch < ch) { before = x.label; return; }
      if (!first) first = x.label;
      if (doc && ch === cur.ch) { var e = x.frag ? doc.getElementById(x.frag) : null; if ((e ? pageOfNode(e) : 0) <= cur.page) inCh = x.label; }
    });
    return inCh || first || before;
  }
  function pageRange() { return [cur.off, cur.page < cur.pages - 1 ? pageStart(cur.page + 1) : CH[cur.ch].len]; }
  function currentBm() { var r = pageRange(); return BM.filter(function (b) { return b.ch === cur.ch && b.off >= r[0] && b.off < Math.max(r[1], r[0] + 1); })[0]; }
  function ui() {
    var pc = percent(), left = Math.max(0, CH[cur.ch].len - cur.off), mins = Math.round(left / 6 / 230);
    $('rdPct').textContent = Math.floor(pc) + ' %';
    $('rdSlider').value = Math.round(pc * 10);
    $('rdLeft').textContent = 'Page ' + (cur.page + 1) + ' sur ' + cur.pages + ' · ' + (mins < 1 ? 'moins d’une minute' : mins + ' min') + ' restantes dans le chapitre';
    $('rdChap').textContent = chapterTitle(cur.ch);
    var bm = !!currentBm(); $('rdBm').setAttribute('aria-pressed', bm ? 'true' : 'false'); $('rdRibbon').hidden = !bm;
    [].forEach.call(document.querySelectorAll('#t-toc a'), function (a) { a.classList.toggle('cur', +a.dataset.ch === cur.ch); });
  }
  function chrome(on) { body.classList.toggle('chrome-off', on === undefined ? !body.classList.contains('chrome-off') : !on); }

  function openPanel(name) {
    ['toc', 'search', 'aa'].forEach(function (n) { var p = $('pnl-' + n); var show = n === name && p.hidden; p.hidden = !show; var b = document.querySelector('[data-panel="' + n + '"]'); if (b) b.setAttribute('aria-expanded', show ? 'true' : 'false'); });
    if (name === 'toc' && !$('pnl-toc').hidden) { fillBm(); fillHl(); }
    if (name === 'search' && !$('pnl-search').hidden) $('rdQ').focus();
    if (name) chrome(true);
  }
  function closePanels() { ['toc', 'search', 'aa'].forEach(function (n) { $('pnl-' + n).hidden = true; }); [].forEach.call(document.querySelectorAll('[data-panel]'), function (b) { b.setAttribute('aria-expanded', 'false'); }); }

  function fillToc() {
    var box = $('t-toc'); box.textContent = '';
    if (!toc.length) toc = book.spine.map(function (it, i) { return { label: 'Section ' + (i + 1), ch: i, frag: '', depth: 0 }; });
    toc.forEach(function (x) {
      if (x.ch < 0) return;
      var a = el('a', null, x.label || '—'); a.href = '#'; a.dataset.ch = x.ch; a.style.paddingLeft = (18 + 16 * x.depth) + 'px';
      a.onclick = function (e) { e.preventDefault(); closePanels(); goTo(x.ch, x.frag ? { frag: x.frag } : { page: 0 }); };
      box.appendChild(a);
    });
  }
  function fmtWhere(ch, off) { return (chapterTitle(ch) || 'Chapitre ' + (ch + 1)) + ' · ' + Math.floor((cum[ch] + off) / total * 100) + ' %'; }
  function fillBm() {
    var box = $('t-bm'); box.textContent = '';
    if (!BM.length) { box.appendChild(el('p', 'rd-empty', 'Aucun signet. Touchez le ruban en haut à droite pour marquer la page.')); return; }
    BM.slice().sort(function (a, b) { return a.ch - b.ch || a.off - b.off; }).forEach(function (b) {
      var it = el('div', 'rd-item'), row = el('div'); it.appendChild(el('span', null, b.label || '…')); it.appendChild(el('small', null, fmtWhere(b.ch, b.off)));
      var x = el('button', 'rd-x', 'Supprimer'); x.type = 'button'; x.style.cssText = 'border:0;background:none;color:var(--mut);cursor:pointer;font-size:12px;padding:4px 0';
      x.onclick = function (e) { e.stopPropagation(); BM = BM.filter(function (y) { return y.id !== b.id; }); saveMarks(); fillBm(); ui(); };
      row.appendChild(x); it.appendChild(row);
      it.onclick = function () { closePanels(); goTo(b.ch, { off: b.off }); };
      box.appendChild(it);
    });
  }
  function fillHl() {
    var box = $('t-hl'); box.textContent = '';
    if (!HL.length) { box.appendChild(el('p', 'rd-empty', 'Aucun surlignement. Sélectionnez un passage pour le surligner ou ajouter une note.')); return; }
    var tools = el('div', 'rd-tools'), ex = el('button', null, 'Exporter (.txt)'); ex.type = 'button'; ex.onclick = exportNotes; tools.appendChild(ex); box.appendChild(tools);
    HL.slice().sort(function (a, b) { return a.ch - b.ch || a.s - b.s; }).forEach(function (h) {
      var it = el('div', 'rd-item'), q = el('q', null, h.text); q.style.setProperty('--hc', HLC[h.c] || HLC.yellow);
      it.appendChild(q); if (h.note) it.appendChild(el('span', 'nt', '✎ ' + h.note)); it.appendChild(el('small', null, fmtWhere(h.ch, h.s)));
      it.onclick = function () { closePanels(); goTo(h.ch, { off: h.s }); };
      box.appendChild(it);
    });
  }
  function exportNotes() {
    var out = [CFG.title + (CFG.author ? ' — ' + CFG.author : ''), ''];
    HL.slice().sort(function (a, b) { return a.ch - b.ch || a.s - b.s; }).forEach(function (h) { out.push('« ' + h.text + ' »'); if (h.note) out.push('Note : ' + h.note); out.push('(' + fmtWhere(h.ch, h.s) + ')', ''); });
    var a = el('a'); a.href = URL.createObjectURL(new Blob([out.join('\n')], { type: 'text/plain;charset=utf-8' })); a.download = (CFG.title || 'livre').replace(/[\\/:*?"<>|]+/g, ' ') + ' - notes.txt'; document.body.appendChild(a); a.click(); a.remove();
  }

  /* ---------- Surlignements ---------- */
  function wrap(s, e, cls, id) {
    buildNodes();
    nodes.filter(function (x) { return x.s < e && x.s + x.n.data.length > s; }).forEach(function (x) {
      var n = x.n, a = Math.max(0, s - x.s), b = Math.min(n.data.length, e - x.s);
      if (b <= a || !n.data.slice(a, b).trim() && b - a < 2) return;
      if (b < n.data.length) n.splitText(b);
      var mid = a > 0 ? n.splitText(a) : n;
      var m = doc.createElement('mark'); m.className = cls; if (id) m.dataset.id = id;
      mid.parentNode.insertBefore(m, mid); m.appendChild(mid);
    });
    buildNodes();
  }
  function unwrap(sel) { [].slice.call(doc.querySelectorAll(sel)).forEach(function (m) { var p = m.parentNode; while (m.firstChild) p.insertBefore(m.firstChild, m); p.removeChild(m); p.normalize(); }); buildNodes(); }
  function applyHL() { HL.filter(function (h) { return h.ch === cur.ch; }).forEach(function (h) { wrap(h.s, h.e, 'hl hl-' + (h.c || 'yellow') + (h.note ? ' note' : ''), h.id); }); }
  function flash(s, e) { wrap(s, e, 'hl-search'); }
  function clearFlash() { if (doc && doc.querySelector('mark.hl-search')) unwrap('mark.hl-search'); }

  var selState = null; // {s, e, text, id?}
  function hideSel() { $('rdSel').hidden = true; selState = null; }
  function showSel(rect, st) {
    selState = st; var box = $('rdSel'), fr = frame.getBoundingClientRect();
    box.querySelector('[data-act="del"]').hidden = !st.id; box.hidden = false;
    var x = fr.left + rect.left + rect.width / 2 - box.offsetWidth / 2, y = fr.top + rect.top - box.offsetHeight - 10;
    if (y < 60) y = fr.top + rect.bottom + 10;
    box.style.left = Math.max(8, Math.min(innerWidth - box.offsetWidth - 8, x)) + 'px'; box.style.top = Math.max(8, y) + 'px';
  }
  function checkSel() {
    var s = doc.getSelection(); if (!s || s.isCollapsed || !s.rangeCount) return false;
    var r = s.getRangeAt(0), txt = r.toString(); if (!txt.trim()) return false;
    var a = offsetOf(r.startContainer, r.startOffset), b = offsetOf(r.endContainer, r.endOffset);
    var rects = r.getClientRects(), rc = rects[0] || r.getBoundingClientRect();
    showSel(rc, { s: Math.min(a, b), e: Math.max(a, b), text: txt.replace(/\s+/g, ' ').trim() });
    return true;
  }
  function selAction(act, color) {
    var st = selState; if (!st) return;
    var h = st.id ? HL.filter(function (x) { return x.id === st.id; })[0] : null;
    if (color) {
      if (h) { h.c = color; } else { h = { id: uid(), ch: cur.ch, s: st.s, e: st.e, c: color, text: st.text.slice(0, 2000), note: '', at: new Date().toISOString() }; HL.push(h); }
      refreshHL(); saveMarks(); doc.getSelection().removeAllRanges(); hideSel(); return;
    }
    if (act === 'note') {
      $('rdNoteQ').textContent = st.text; $('rdNoteT').value = h ? h.note || '' : ''; var d = $('rdNote'); d.returnValue = ''; d.showModal();
      d.onclose = function () {
        if (d.returnValue !== 'ok') return;
        if (!h) { h = { id: uid(), ch: cur.ch, s: st.s, e: st.e, c: 'yellow', text: st.text.slice(0, 2000), note: '', at: new Date().toISOString() }; HL.push(h); }
        h.note = $('rdNoteT').value.trim(); refreshHL(); saveMarks(); toast('Note enregistrée');
      };
      hideSel(); return;
    }
    if (act === 'copy') { (navigator.clipboard ? navigator.clipboard.writeText(st.text) : Promise.reject()).then(function () { toast('Copié'); }, function () { toast('Copie impossible'); }); }
    if (act === 'define') { var w = st.text.split(/\s+/)[0].replace(/[^\p{L}\p{N}'’-]/gu, ''); if (w) window.open('https://fr.wiktionary.org/wiki/' + encodeURIComponent(w.toLowerCase()), '_blank', 'noopener'); }
    if (act === 'search') { closePanels(); openPanel('search'); $('rdQ').value = st.text.slice(0, 80); runSearch(); }
    if (act === 'del' && h) { HL = HL.filter(function (x) { return x.id !== h.id; }); refreshHL(); saveMarks(); }
    try { doc.getSelection().removeAllRanges(); } catch (e) { /* rien */ }
    hideSel();
  }
  function refreshHL() { var off = cur.off; unwrap('mark.hl'); applyHL(); measure(); place({ off: off }); }

  /* ---------- Recherche ---------- */
  function runSearch() {
    var q = norm($('rdQ').value.trim()), box = $('rdResults'); box.textContent = '';
    if (q.length < 2) return;
    var n = 0;
    for (var i = 0; i < CH.length && n < 300; i++) {
      var c = CH[i]; if (!c.norm) c.norm = norm(c.text);
      var at = c.norm.indexOf(q), head = null;
      while (at >= 0 && n < 300) {
        if (!head) { head = el('div', 'rd-empty', chapterTitle(i) || 'Chapitre ' + (i + 1)); head.style.cssText = 'padding:10px 18px 4px;font-weight:600'; box.appendChild(head); }
        var a = Math.max(0, at - 50), b = Math.min(c.text.length, at + q.length + 70), it = el('div', 'rd-item');
        it.appendChild(document.createTextNode((a ? '…' : '') + c.text.slice(a, at).replace(/\s+/g, ' ')));
        it.appendChild(el('b', null, c.text.slice(at, at + q.length)));
        it.appendChild(document.createTextNode(c.text.slice(at + q.length, b).replace(/\s+/g, ' ') + '…'));
        (function (ch, s, e) { it.onclick = function () { goTo(ch, { off: s, flash: [s, e] }); }; })(i, at, at + q.length);
        box.appendChild(it); n++;
        at = c.norm.indexOf(q, at + q.length);
      }
    }
    if (!n) box.appendChild(el('p', 'rd-empty', 'Aucun résultat.'));
    else box.insertBefore(el('p', 'rd-empty', n + (n >= 300 ? '+' : '') + ' résultat' + (n > 1 ? 's' : '')), box.firstChild);
  }

  /* ---------- Événements ---------- */
  var wheelT = 0, touch = null;
  function key(e) {
    if (e.target && /input|textarea|select/i.test(e.target.tagName)) return;
    if (e.key === 'Escape') { closePanels(); hideSel(); return; }
    if ($('rdNote').open) return;
    if (['ArrowRight', 'PageDown'].indexOf(e.key) >= 0 || (e.key === ' ' && !e.shiftKey)) { e.preventDefault(); next(); }
    else if (['ArrowLeft', 'PageUp'].indexOf(e.key) >= 0 || (e.key === ' ' && e.shiftKey)) { e.preventDefault(); prev(); }
  }
  function hookDoc() {
    doc.addEventListener('keydown', key);
    doc.addEventListener('mouseup', function () { setTimeout(function () { checkSel(); }, 10); });
    doc.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a');
      if (a) {
        e.preventDefault();
        if (a.dataset.ext) window.open(a.dataset.ext, '_blank', 'noopener');
        else if (a.dataset.int) { var p = a.dataset.int.split('#'), i = book.spine.findIndex(function (s) { return s.href === p[0]; }); if (i >= 0) goTo(i, p[1] ? { frag: p[1] } : { page: 0 }); }
        return;
      }
      var m = e.target.closest && e.target.closest('mark.hl');
      var s = doc.getSelection();
      if (s && !s.isCollapsed && s.toString().trim()) return;
      if (m) { var h = HL.filter(function (x) { return x.id === m.dataset.id; })[0]; if (h) { showSel(m.getBoundingClientRect(), { s: h.s, e: h.e, text: h.text, id: h.id }); return; } }
      if (!$('rdSel').hidden) { hideSel(); return; }
      if (!['toc', 'search', 'aa'].every(function (n) { return $('pnl-' + n).hidden; })) { closePanels(); return; }
      var x = e.clientX / cur.W;
      if (x < 0.28) prev(); else if (x > 0.72) next(); else chrome();
    });
    doc.addEventListener('wheel', function (e) {
      var d = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY; if (Math.abs(d) < 8) return;
      e.preventDefault(); var t = Date.now(); if (t - wheelT < 420) return; wheelT = t; if (d > 0) next(); else prev();
    }, { passive: false });
    doc.addEventListener('touchstart', function (e) { var t = e.touches[0]; touch = { x: t.clientX, y: t.clientY, t: Date.now() }; }, { passive: true });
    doc.addEventListener('touchend', function (e) {
      setTimeout(function () {
        if (checkSel()) { touch = null; return; }
        if (!touch) return; var t = e.changedTouches[0], dx = t.clientX - touch.x, dy = t.clientY - touch.y; touch = null;
        if (Math.abs(dx) > 45 && Math.abs(dy) < 80) { if (dx < 0) next(); else prev(); }
      }, 30);
    });
  }
  document.addEventListener('keydown', key);
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-panel]'); if (b) { openPanel(b.dataset.panel); return; }
    var t = e.target.closest('.rd-tabs button');
    if (t) { [].forEach.call(document.querySelectorAll('.rd-tabs button'), function (x) { x.classList.toggle('on', x === t); }); ['t-toc', 't-bm', 't-hl'].forEach(function (id) { $(id).hidden = id !== t.dataset.tab; }); return; }
    var s = e.target.closest('#rdSel button'); if (s) { selAction(s.dataset.act, s.dataset.hl); return; }
    var st = e.target.closest('[data-set]'); if (st) { setPref(st.dataset.set, st.dataset.d ? +st.dataset.d : st.dataset.v); return; }
  });
  $('sFont').addEventListener('change', function () { setPref('font', this.value); });
  $('rdSlider').addEventListener('input', function () { $('rdPct').textContent = Math.floor(this.value / 10) + ' %'; });
  $('rdSlider').addEventListener('change', function () { goPercent(this.value / 10); });
  $('rdSearch').addEventListener('submit', function (e) { e.preventDefault(); runSearch(); });
  $('rdBm').addEventListener('click', function () {
    if (!doc) return; var b = currentBm();
    if (b) { BM = BM.filter(function (x) { return x !== b; }); toast('Signet retiré'); }
    else { var r = pageRange(); BM.push({ id: uid(), ch: cur.ch, off: cur.off, label: CH[cur.ch].text.slice(r[0], r[0] + 90).replace(/\s+/g, ' ').trim(), at: new Date().toISOString() }); toast('Signet ajouté'); }
    saveMarks(); ui();
  });
  $('rdFull').addEventListener('click', function () { if (document.fullscreenElement) document.exitFullscreen(); else if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen(); });
  var rz; addEventListener('resize', function () { clearTimeout(rz); rz = setTimeout(relayout, 200); });
  document.addEventListener('visibilitychange', function () { if (document.hidden && doc) savePos(true); });
  addEventListener('pagehide', function () { if (doc) savePos(true); });

  function setPref(k, v) {
    if (k === 'size') P.size = Math.max(60, Math.min(220, P.size + v * 10));
    else if (k === 'lh') P.lh = +v; else if (k === 'mg') P.mg = +v; else if (k === 'just') P.just = +v; else P[k] = v;
    savePrefs(); syncPrefsUi(); relayout();
  }
  function syncPrefsUi() {
    $('oSize').textContent = P.size + ' %'; $('sFont').value = P.font in FONTS ? P.font : 'orig';
    [].forEach.call(document.querySelectorAll('[data-set][data-v]'), function (b) { b.classList.toggle('on', String(P[b.dataset.set]) === b.dataset.v); });
    body.dataset.theme = P.theme;
  }
  function relayout() { if (!doc) return; var off = cur.off; styleDoc(); measure(); place({ off: off }); if (doc.fonts) doc.fonts.ready.then(function () { measure(); place({ off: off }); }); }

  /* ---------- Démarrage ---------- */
  syncPrefsUi();
  fetch(CFG.file, { credentials: 'same-origin' })
    .then(function (r) { if (!r.ok) throw new Error('Fichier introuvable'); return r.arrayBuffer(); })
    .then(EpubLib.open)
    .then(function (b) {
      book = b;
      return Promise.all([EpubLib.toc(b), indexAll()]);
    })
    .then(function (r) {
      toc = (r[0] || []).map(function (x) {
        var p = x.href.split('#'); return { label: x.label, depth: x.depth, frag: p[1] || '', ch: book.spine.findIndex(function (s) { return s.href === p[0]; }) };
      });
      fillToc();
      var pos = CFG.pos, loc = null;
      try { loc = JSON.parse(localStorage.getItem('rd_pos_' + CFG.id) || 'null'); } catch (e) { loc = null; }
      if (!pos || typeof pos.ch !== 'number') pos = loc && typeof loc.ch === 'number' ? loc : { ch: 0, off: 0 };
      return goTo(pos.ch, { off: pos.off || 0 });
    })
    .then(function () { $('rdLoading').hidden = true; setTimeout(function () { chrome(false); }, 2500); })
    .catch(function (e) { $('rdLoading').textContent = 'Impossible d’ouvrir ce livre : ' + (e && e.message ? e.message : 'format non reconnu') + '.'; });
})();
