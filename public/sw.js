const CACHE_NAME = 'chaturji-pwa-v1';
const SW_PATH = self.location.pathname;
const BASE = SW_PATH.slice(0, SW_PATH.lastIndexOf('/') + 1);

const PRECACHE_URLS = [
    `${BASE}offline.html`,
    `${BASE}images/pwa/icon-192.png`,
    `${BASE}images/pwa/icon-512.png`,
    `${BASE}images/brand/logo.png`,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(() => caches.match(`${BASE}offline.html`))
        );
        return;
    }

    const isStatic = /\.(?:png|jpg|jpeg|gif|svg|webp|ico|woff2?|css|js)$/i.test(url.pathname)
        || url.pathname.includes('/images/')
        || url.pathname.includes('/fonts/');

    if (!isStatic) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        })
    );
});
