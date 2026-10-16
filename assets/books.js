/* Mes livres : envoi des fichiers (par morceaux), métadonnées EPUB, modification et suppression. */
(function () {
  'use strict';
  var csrf = (document.querySelector('meta[name="csrf"]') || {}).content || '';
  var CHUNK = 1536 * 1024;
  function api(a, d) {
    return fetch('/api/' + a, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf }, body: JSON.stringify(d || {}) })
      .then(function (r) { return r.json().then(function (j) { if (!r.ok || j.error) throw new Error(j.error || 'Erreur'); return j; }); });
  }
  function el(t, c, x) { var e = document.createElement(t); if (c) e.className = c; if (x != null) e.textContent = x; return e; }
  var box = document.getElementById('bkUploads');

  function upload(file) {
    var kind = /\.pdf$/i.test(file.name) || file.type === 'application/pdf' ? 'pdf' : (/\.epub$/i.test(file.name) || file.type === 'application/epub+zip' ? 'epub' : '');
    var row = el('div', 'bk-up'), lab = el('span', null, file.name), bar = el('span', 'bk-prog'), fill = el('i'), st = el('small', 'muted', 'Préparation…');
    bar.appendChild(fill); row.appendChild(lab); row.appendChild(bar); row.appendChild(st); box.appendChild(row);
    function fail(m) { row.classList.add('err'); st.textContent = m; }
    if (!kind) return Promise.resolve(fail('Format non pris en charge (EPUB ou PDF).'));
    var title = file.name.replace(/\.(epub|pdf)$/i, '').replace(/[_]+/g, ' ').trim();
    return api('books/start', { title: title, kind: kind, size: file.size }).then(function (r) {
      var id = r.id, off = 0;
      function next() {
        if (off >= file.size) return api('books/done', { id: id });
        var part = file.slice(off, off + CHUNK);
        return fetch('/api/books/chunk', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/octet-stream', 'X-CSRF': csrf, 'X-Book': String(id), 'X-Offset': String(off) }, body: part })
          .then(function (x) { return x.json().then(function (j) { if (!x.ok || j.error) throw new Error(j.error || 'Envoi interrompu'); }); })
          .then(function () { off += part.size; fill.style.width = Math.round(off * 100 / file.size) + '%'; st.textContent = Math.round(off * 100 / file.size) + ' %'; return next(); });
      }
      return next().then(function () {
        if (kind !== 'epub') return;
        st.textContent = 'Lecture de la couverture…';
        return file.arrayBuffer().then(EpubLib.open).then(function (b) {
          return EpubLib.coverData(b, 360).then(function (cv) { return api('books/meta', { id: id, title: b.meta.title || title, author: b.meta.author || '', cover: cv }); });
        }).catch(function () { /* métadonnées facultatives */ });
      }).then(function () { st.textContent = 'Ajouté'; row.classList.add('ok'); });
    }).catch(function (e) { fail(e.message); });
  }

  function handle(files) {
    files = [].slice.call(files || []); if (!files.length) return;
    files.reduce(function (p, f) { return p.then(function () { return upload(f); }); }, Promise.resolve()).then(function () { setTimeout(function () { location.reload(); }, 600); });
  }
  var inp = document.getElementById('bkFile');
  if (inp) inp.addEventListener('change', function () { handle(inp.files); inp.value = ''; });
  document.addEventListener('dragover', function (e) { if (e.dataTransfer && [].indexOf.call(e.dataTransfer.types, 'Files') >= 0) { e.preventDefault(); document.body.classList.add('bk-drop'); } });
  document.addEventListener('dragleave', function (e) { if (!e.relatedTarget) document.body.classList.remove('bk-drop'); });
  document.addEventListener('drop', function (e) { if (!e.dataTransfer || !e.dataTransfer.files.length) return; e.preventDefault(); document.body.classList.remove('bk-drop'); handle(e.dataTransfer.files); });

  var dlg = document.getElementById('bkEdit');
  document.addEventListener('click', function (e) {
    var li = e.target.closest && e.target.closest('.bk-item'); if (!li) return;
    var id = +li.dataset.id;
    if (e.target.closest('[data-bk-del]')) {
      if (!confirm('Supprimer « ' + li.dataset.title + ' » de votre bibliothèque ? Les surlignements et notes seront aussi supprimés.')) return;
      api('books/delete', { id: id }).then(function () { li.remove(); }).catch(function (x) { alert(x.message); });
    }
    if (e.target.closest('[data-bk-edit]') && dlg) {
      dlg.querySelector('#bkT').value = li.dataset.title; dlg.querySelector('#bkA').value = li.dataset.author;
      dlg.returnValue = ''; dlg.showModal();
      dlg.onclose = function () {
        if (dlg.returnValue !== 'ok') return;
        var t = dlg.querySelector('#bkT').value.trim(), a = dlg.querySelector('#bkA').value.trim(); if (!t) return;
        api('books/meta', { id: id, title: t, author: a }).then(function () { location.reload(); }).catch(function (x) { alert(x.message); });
      };
    }
  });
})();
