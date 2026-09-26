const CACHE_NAME = 'sopan-agenda-v2';
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
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
