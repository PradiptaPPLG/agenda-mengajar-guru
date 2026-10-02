const CACHE_NAME = 'sopan-agenda-v3';
const STATIC_ASSETS = [
  '/manifest.json',
  '/images/logo_new.png',
  '/favicon.ico'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch(() => {
        // Suppress asset load failure during install
      });
    })
  );
  self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET' || !event.request.url.startsWith('http')) {
    return;
  }

  const url = new URL(event.request.url);

  // Exclude auth, boost, or api routes from service worker interception
  if (url.pathname.startsWith('/_boost') || url.pathname.includes('logout') || url.pathname.includes('login')) {
    return;
  }

  // Static Assets Strategy (Cache First, Network Fallback)
  if (url.pathname.match(/\.(png|jpg|jpeg|svg|gif|ico|css|js|woff|woff2|ttf|eot)$/) || url.pathname === '/manifest.json') {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseToCache);
            });
          }
          return networkResponse;
        });
      })
    );
    return;
  }

  // HTML Navigation Strategy (Network First / Graceful Fallback)
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(async () => {
        const cached = await caches.match(event.request);
        if (cached) return cached;
        const root = await caches.match('/');
        if (root) return root;
        return new Response(
          '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Koneksi Terputus - SOPAN</title><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{font-family:system-ui,-apple-system,sans-serif;margin:0;padding:40px 20px;text-align:center;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;}h1{font-size:20px;margin-bottom:8px;}p{font-size:14px;color:#94a3b8;max-width:320px;margin:0 auto 20px;}button{background:#2563eb;color:#fff;border:none;padding:12px 24px;border-radius:12px;font-weight:bold;font-size:14px;cursor:pointer;}</style></head><body><h1>Koneksi Jaringan Terputus</h1><p>Gagal memuat halaman. Pastikan koneksi internet aktif, lalu coba muat ulang.</p><button onclick="location.reload()">Coba Lagi</button></body></html>',
          { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
      })
    );
    return;
  }

  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
