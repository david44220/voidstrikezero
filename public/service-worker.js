/**
 * VOIDSTRIKE ARENA — Progressive Web App Service Worker
 * Cache-First for static assets, Network-First for dynamic endpoints.
 */

const CACHE_NAME = 'voidstrike-v1.0';
const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/js/app.js',
    '/assets/vendor/three/three.module.js',
    '/assets/vendor/three/three.module.min.js',
    '/assets/js/game/Audio.js',
    '/assets/js/game/Input.js',
    '/assets/js/game/TouchControls.js',
    '/assets/js/game/Vehicle.js',
    '/assets/js/game/Arena.js',
    '/assets/js/game/Weapons.js',
    '/assets/js/game/Pickups.js',
    '/assets/js/game/AI.js',
    '/assets/js/game/HUD.js',
    '/assets/js/game/Engine.js',
    '/assets/icons/icon-192.svg',
    '/assets/icons/icon-512.svg',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip caching for API calls, POST requests, or admin
    if (event.request.method !== 'GET' || url.pathname.startsWith('/api/') || url.pathname.startsWith('/admin')) {
        event.respondWith(
            fetch(event.request).catch(() => {
                return new Response(
                    JSON.stringify({ error: 'Network unavailable. Online connectivity required for competitive matches.' }),
                    { status: 503, headers: { 'Content-Type': 'application/json' } }
                );
            })
        );
        return;
    }

    // Cache-First for static assets, Network-First for HTML views
    if (url.pathname.startsWith('/assets/') || url.pathname.endsWith('.js') || url.pathname.endsWith('.css') || url.pathname.endsWith('.svg')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                return cached || fetch(event.request).then((resp) => {
                    const clone = resp.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    return resp;
                });
            })
        );
    } else {
        // Network-First for pages with offline fallback
        event.respondWith(
            fetch(event.request).then((resp) => {
                const clone = resp.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                return resp;
            }).catch(() => {
                return caches.match(event.request).then((cached) => {
                    if (cached) return cached;
                    return caches.match('/');
                });
            })
        );
    }
});
