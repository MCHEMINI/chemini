/* Thème : clair par défaut, sombre ou automatique au choix (appliqué avant l'affichage). */
(function () {
  var t = 'light';
  try { t = localStorage.getItem('gid_theme') || 'light'; } catch (e) {}
  if (['light', 'dark', 'auto'].indexOf(t) < 0) t = 'light';
  document.documentElement.setAttribute('data-theme', t);
})();
