/**
 * TinyShop — Service Worker
 * Cache-first for static assets, stale-while-revalidate for pages.
 * Offline fallback page when network is unavailable.
 */
var CACHE_NAME = 'tinyshop-v4';
var OFFLINE_URL = '/offline.html';
var STATIC_ASSETS = [
    '/public/css/app.css',
    '/public/css/dashboard.css',
    '/public/js/jquery.min.js',
    '/public/js/app.js',
    '/public/js/dashboard.js',
    '/public/img/placeholder.svg',
    OFFLINE_URL
];

// Install — cache static assets + offline page
self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate — clean old caches
self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(function(k) { return k !== CACHE_NAME; })
                    .map(function(k) { return caches.delete(k); })
            );
        })
    );
    self.clients.claim();
});

// Fetch — cache-first for static, stale-while-revalidate for pages
self.addEventListener('fetch', function(e) {
    var url = new URL(e.request.url);

    // Only handle same-origin http(s) GET requests — skip extensions, API, and SPA fragments
    if (e.request.method !== 'GET') return;
    if (url.protocol !== 'https:' && url.protocol !== 'http:') return;
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/api/')) return;
    if (e.request.headers.get('X-SPA') === '1') return;

    // Static assets: cache-first
    if (url.pathname.startsWith('/public/')) {
        e.respondWith(
            caches.match(e.request).then(function(cached) {
                return cached || fetch(e.request).then(function(res) {
                    var clone = res.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(e.request, clone);
                    });
                    return res;
                });
            })
        );
        return;
    }

    // HTML pages: stale-while-revalidate (serve cached instantly, update in background)
    if (e.request.headers.get('Accept') && e.request.headers.get('Accept').includes('text/html')) {
        e.respondWith(
            caches.match(e.request).then(function(cached) {
                var fetchPromise = fetch(e.request).then(function(res) {
                    if (res.ok) {
                        var clone = res.clone();
                        caches.open(CACHE_NAME).then(function(cache) {
                            cache.put(e.request, clone);
                        });
                    }
                    return res;
                }).catch(function() {
                    return cached || caches.match(OFFLINE_URL);
                });

                return cached || fetchPromise;
            })
        );
        return;
    }

    // Other GET requests (images, fonts): cache-first
    e.respondWith(
        caches.match(e.request).then(function(cached) {
            return cached || fetch(e.request).then(function(res) {
                if (res.ok) {
                    var clone = res.clone();
                    caches.open(CACHE_NAME).then(function(cache) {
                        cache.put(e.request, clone);
                    });
                }
                return res;
            }).catch(function() {
                return new Response('', { status: 408 });
            });
        })
    );
});
