const CACHE_NAME = 'farmlink-pwa-v1';
const BASE_PATH = '/farmlink-web/public';

const PRECACHE_ASSETS = [
  `${BASE_PATH}/offline.html`,
  `${BASE_PATH}/manifest.json`,
  `${BASE_PATH}/assets/css/app.css`,
  `${BASE_PATH}/assets/js/app.js`,
  `${BASE_PATH}/assets/js/offline-db.js`,
  `${BASE_PATH}/assets/js/sync-manager.js`,
  `${BASE_PATH}/assets/js/geolocation.js`,
  `${BASE_PATH}/assets/img/icon-192.svg`
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  // Only cache GET requests
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Cache-first for static assets
  if (url.pathname.includes('/assets/') || url.pathname.endsWith('manifest.json')) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request))
    );
    return;
  }

  // Network-first with cache + offline.html fallback for HTML navigation
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        const cloned = networkResponse.clone();
        caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
        return networkResponse;
      })
      .catch(async () => {
        const cachedPage = await caches.match(request);
        return cachedPage || caches.match(`${BASE_PATH}/offline.html`);
      })
  );
});
