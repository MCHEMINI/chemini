/* Lecture des fichiers EPUB (zip + OPF) — utilisé par la bibliothèque et la liseuse. Nécessite JSZip. */
(function () {
  'use strict';
  function dirOf(p) { var i = p.lastIndexOf('/'); return i < 0 ? '' : p.slice(0, i + 1); }
  function resolve(base, rel) {
    rel = String(rel || '').split('#')[0];
    try { rel = decodeURIComponent(rel); } catch (e) { /* nom déjà décodé */ }
    if (/^[a-z]+:/i.test(rel)) return rel;
    var parts = (rel.charAt(0) === '/' ? rel.slice(1) : dirOf(base) + rel).split('/'), out = [];
    parts.forEach(function (s) { if (s === '..') out.pop(); else if (s !== '.' && s !== '') out.push(s); });
    return out.join('/');
  }
  function parseXml(txt) {
    var d = new DOMParser().parseFromString(txt, 'application/xml');
    if (d.getElementsByTagName('parsererror').length) d = new DOMParser().parseFromString(txt, 'text/html');
    return d;
  }
  function byLocal(root, name) { return [].slice.call(root.getElementsByTagName('*')).filter(function (n) { return (n.localName || n.nodeName).toLowerCase() === name; }); }
  function text(n) { return n ? String(n.textContent || '').replace(/\s+/g, ' ').trim() : ''; }

  function open(buf) {
    return JSZip.loadAsync(buf).then(function (zip) {
      var f = zip.file('META-INF/container.xml');
      if (!f) throw new Error('EPUB invalide (container.xml absent)');
      return f.async('string').then(function (cx) {
        var rf = byLocal(parseXml(cx), 'rootfile')[0], opfPath = rf && rf.getAttribute('full-path');
        if (!opfPath || !zip.file(opfPath)) throw new Error('EPUB invalide (OPF absent)');
        return zip.file(opfPath).async('string').then(function (opfTxt) {
          var opf = parseXml(opfTxt), man = {}, byHref = {};
          byLocal(opf, 'item').forEach(function (it) {
            var o = { id: it.getAttribute('id'), href: resolve(opfPath, it.getAttribute('href')), type: it.getAttribute('media-type') || '', props: it.getAttribute('properties') || '' };
            man[o.id] = o; byHref[o.href] = o;
          });
          var spineEl = byLocal(opf, 'spine')[0];
          var spine = byLocal(opf, 'itemref').map(function (r) { return man[r.getAttribute('idref')]; }).filter(function (x) { return x && /html|xml/.test(x.type); });
          var meta = { title: text(byLocal(opf, 'title')[0]), author: byLocal(opf, 'creator').map(text).filter(Boolean).join(', '), lang: text(byLocal(opf, 'language')[0]) };
          // couverture : EPUB 3 (properties="cover-image") ou EPUB 2 (<meta name="cover">)
          var cover = Object.keys(man).map(function (k) { return man[k]; }).filter(function (x) { return /cover-image/.test(x.props); })[0];
          if (!cover) { var cm = byLocal(opf, 'meta').filter(function (m) { return m.getAttribute('name') === 'cover'; })[0]; if (cm) cover = man[cm.getAttribute('content')]; }
          if (!cover) cover = Object.keys(man).map(function (k) { return man[k]; }).filter(function (x) { return /^image\//.test(x.type) && /cover|couv/i.test(x.href + x.id); })[0];
          var nav = Object.keys(man).map(function (k) { return man[k]; }).filter(function (x) { return /\bnav\b/.test(x.props); })[0];
          var ncx = spineEl && man[spineEl.getAttribute('toc')] || Object.keys(man).map(function (k) { return man[k]; }).filter(function (x) { return x.type === 'application/x-dtbncx+xml'; })[0];
          return { zip: zip, opfPath: opfPath, manifest: man, byHref: byHref, spine: spine, meta: meta, cover: cover || null, nav: nav || null, ncx: ncx || null };
        });
      });
    });
  }

  /* Table des matières : [{label, href, path, frag, depth}] */
  function toc(book) {
    function flat(list, depth, out) { list.forEach(function (x) { out.push({ label: x.label, href: x.href, depth: depth }); flat(x.kids, depth + 1, out); }); return out; }
    if (book.nav && book.zip.file(book.nav.href)) {
      return book.zip.file(book.nav.href).async('string').then(function (t) {
        var d = parseXml(t), navs = byLocal(d, 'nav'), n = navs.filter(function (x) { return /toc/.test(x.getAttribute('epub:type') || x.getAttributeNS('http://www.idpf.org/2007/ops', 'type') || ''); })[0] || navs[0];
        function walk(ol) {
          return [].slice.call(ol ? ol.children : []).filter(function (li) { return (li.localName || '').toLowerCase() === 'li'; }).map(function (li) {
            var a = byLocal(li, 'a')[0] || byLocal(li, 'span')[0], sub = [].slice.call(li.children).filter(function (c) { return /^(ol|ul)$/i.test(c.localName); })[0];
            return { label: text(a), href: a && a.getAttribute('href') ? resolve(book.nav.href, a.getAttribute('href')) + (a.getAttribute('href').indexOf('#') >= 0 ? '#' + a.getAttribute('href').split('#')[1] : '') : '', kids: walk(sub) };
          });
        }
        var ol = n ? [].slice.call(n.children).filter(function (c) { return /^(ol|ul)$/i.test(c.localName); })[0] : null;
        return flat(walk(ol), 0, []);
      });
    }
    if (book.ncx && book.zip.file(book.ncx.href)) {
      return book.zip.file(book.ncx.href).async('string').then(function (t) {
        var d = parseXml(t), map = byLocal(d, 'navmap')[0];
        function walk(el) {
          return [].slice.call(el ? el.children : []).filter(function (c) { return (c.localName || '').toLowerCase() === 'navpoint'; }).map(function (np) {
            var lab = byLocal(np, 'text')[0], ct = byLocal(np, 'content')[0], src = ct ? ct.getAttribute('src') : '';
            return { label: text(lab), href: src ? resolve(book.ncx.href, src) + (src.indexOf('#') >= 0 ? '#' + src.split('#')[1] : '') : '', kids: walk(np) };
          });
        }
        return flat(walk(map), 0, []);
      });
    }
    return Promise.resolve([]);
  }

  /* Couverture réduite en JPEG (data:) pour la bibliothèque */
  function coverData(book, maxW) {
    if (!book.cover || !book.zip.file(book.cover.href)) return Promise.resolve('');
    return book.zip.file(book.cover.href).async('base64').then(function (b64) {
      return new Promise(function (ok) {
        var url = 'data:' + (book.cover.type || 'image/jpeg') + ';base64,' + b64, img = new Image();
        img.onload = function () {
          var w = Math.min(maxW || 360, img.naturalWidth), hh = Math.round(img.naturalHeight * w / img.naturalWidth), c = document.createElement('canvas');
          c.width = w; c.height = hh; c.getContext('2d').drawImage(img, 0, 0, w, hh);
          try { ok(c.toDataURL('image/jpeg', 0.82)); } catch (e) { ok(''); }
        };
        img.onerror = function () { ok(''); };
        img.src = url;
      });
    });
  }

  window.EpubLib = { open: open, toc: toc, coverData: coverData, resolve: resolve, parseXml: parseXml, dirOf: dirOf };
})();
