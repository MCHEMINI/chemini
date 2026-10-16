/* Thème : clair par défaut, sombre ou automatique au choix (appliqué avant l'affichage). */
(function () {
  var t = 'light';
  try { t = localStorage.getItem('gid_theme') || 'light'; } catch (e) {}
  if (['light', 'dark', 'auto'].indexOf(t) < 0) t = 'light';
  document.documentElement.setAttribute('data-theme', t);
  try { var fs = parseFloat(localStorage.getItem('gid_fs')); if (fs >= 0.8 && fs <= 1.5) document.documentElement.style.setProperty('--fs', fs); } catch (e) {}
  try { if (localStorage.getItem('gid_rail') === 'closed') document.documentElement.setAttribute('data-rail', 'closed'); } catch (e) {}
})();
