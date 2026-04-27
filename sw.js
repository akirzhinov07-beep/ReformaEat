const CACHE = 'reforma-v3';
const STATIC = [
  './reforma_clean%20(4).html',
  './manifest.json',
  './icon-192.png',
  './icon-512.png'
];

self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(c => c.addAll(STATIC)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // Google Sheets CSV — cache-first, update in background (stale-while-revalidate)
  if (url.hostname === 'docs.google.com') {
    e.respondWith(
      caches.open(CACHE).then(cache =>
        cache.match(e.request).then(cached => {
          const networkFetch = fetch(e.request).then(r => {
            if (r && r.status === 200) cache.put(e.request, r.clone());
            return r;
          }).catch(() => null);
          return cached || networkFetch;
        })
      )
    );
    return;
  }

  // Google Drive photos — cache-first
  if (url.hostname === 'drive.google.com' || url.hostname === 'lh3.googleusercontent.com') {
    e.respondWith(
      caches.open(CACHE).then(cache =>
        cache.match(e.request).then(cached => {
          if (cached) return cached;
          return fetch(e.request).then(r => {
            if (r && r.status === 200) cache.put(e.request, r.clone());
            return r;
          }).catch(() => cached);
        })
      )
    );
    return;
  }

  // Static assets — cache first
  e.respondWith(
    caches.match(e.request).then(cached => cached || fetch(e.request).then(r => {
      if (r && r.status === 200 && r.type !== 'opaque') {
        const clone = r.clone();
        caches.open(CACHE).then(c => c.put(e.request, clone));
      }
      return r;
    }))
  );
});
