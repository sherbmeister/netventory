const CACHE_NAME = 'netventory-shell-v1';
const SHELL_ASSETS = [
  '/assets/main.js?v=20',
  '/assets/icons/netventory-192.png',
  '/assets/icons/netventory-512.png',
  '/assets/icons/apple-touch-icon.png',
  '/favicon.ico',
  '/manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (event.request.method !== 'GET' || url.pathname.startsWith('/api/')) return;
  if (!SHELL_ASSETS.includes(url.pathname) && !SHELL_ASSETS.includes(url.pathname + url.search)) return;
  event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request)));
});
