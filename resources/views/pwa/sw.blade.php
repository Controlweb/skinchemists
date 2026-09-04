// Service worker for the admin panel. Version string busts the cache on deploy.
const CACHE = 'sc-admin-{{ $version }}';
const OFFLINE = '{{ route('admin.pwa.offline') }}';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.add(OFFLINE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Network only, with the offline page as the fallback for page loads.
//
// Nothing else is cached on purpose: every /admin response is authenticated and
// user-specific, and a stale Filament asset served after a deploy is a bug that
// only shows up in production. The win here is the installed app shell, not
// offline data.
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate' || event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE))
    );
});
