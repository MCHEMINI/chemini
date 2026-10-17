/* Chemini : application installable (écran d'accueil). Ressources statiques en cache, pages toujours à jour. */
const V = 'chemini-v1';
self.addEventListener('install', e => { self.skipWaiting(); e.waitUntil(caches.open(V).then(c => c.addAll(['/assets/app.css', '/assets/app.js', '/assets/theme.js', '/assets/favicon.svg', '/assets/icon-192.png']).catch(() => {}))); });
self.addEventListener('activate', e => { e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x => x !== V).map(x => caches.delete(x)))).then(() => self.clients.claim())); });
self.addEventListener('fetch', e => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET' || u.origin !== location.origin) return;
  if (u.pathname.startsWith('/assets/')) {
    e.respondWith(caches.open(V).then(c => c.match(e.request).then(hit => { const net = fetch(e.request).then(r => { if (r.ok) c.put(e.request, r.clone()); return r; }); return hit || net; })));
    return;
  }
  if (e.request.mode === 'navigate') e.respondWith(fetch(e.request).catch(() => new Response('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><body style="font-family:system-ui;background:#121720;color:#D9D6CE;display:grid;place-items:center;height:100vh;margin:0;text-align:center"><div><h1 style="font-family:Georgia,serif;color:#C9A66B">Chemini</h1><p>Pas de connexion. Réessayez dès que le réseau revient.</p></div>', { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
});
