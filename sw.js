/**
 * TinyShop — Service Worker
 * Network-first for everything. Cache only used as offline fallback.
 */
var CACHE_NAME = 'tinyshop-v5';
var OFFLINE_URL = '/offline.html';

// Install — cache offline page only
self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.add(OFFLINE_URL);
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

// Fetch — network-first, cache fallback for offline only
self.addEventListener('fetch', function(e) {
    var url = new URL(e.request.url);

    if (e.request.method !== 'GET') return;
    if (url.protocol !== 'https:' && url.protocol !== 'http:') return;
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/api/')) return;

    var isHTML = e.request.headers.get('Accept') && e.request.headers.get('Accept').includes('text/html');

    e.respondWith(
        fetch(e.request).then(function(res) {
            // Cache HTML pages for offline fallback
            if (isHTML && res.ok) {
                var clone = res.clone();
                caches.open(CACHE_NAME).then(function(cache) {
                    cache.put(e.request, clone);
                });
            }
            return res;
        }).catch(function() {
            if (isHTML) {
                return caches.match(e.request).then(function(cached) {
                    return cached || caches.match(OFFLINE_URL);
                });
            }
            return caches.match(e.request);
        })
    );
});
