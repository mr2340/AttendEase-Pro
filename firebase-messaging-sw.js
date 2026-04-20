importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

const CACHE_NAME = 'attendease-v1';
const ASSETS_TO_CACHE = [
    '/sodex/',
    '/sodex/index.php',
    '/sodex/assets/css/main.css',
    '/sodex/assets/js/main.js',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
    'https://unpkg.com/lucide@latest'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    // Skip external API calls and Firebase calls from caching
    if (event.request.url.includes('firebase') || event.request.url.includes('googleapis')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).catch(() => {
                // If offline and request is for a page, show offline fallback if needed
                // For now, we just return the cached home if available
                return caches.match('/sodex/index.php');
            });
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/')
    );
});

// We'll initialize firebase when we receive the config via a message
let messaging;

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'INIT_FIREBASE') {
        const config = event.data.config;
        if (!firebase.apps.length) {
            firebase.initializeApp(config);
            messaging = firebase.messaging();
            
            messaging.onBackgroundMessage((payload) => {
                console.log('[firebase-messaging-sw.js] Received background message ', payload);
                const notificationTitle = payload.notification.title;
                const notificationOptions = {
                    body: payload.notification.body,
                    icon: payload.notification.image || '/sodex/assets/img/logo.png'
                };

                self.registration.showNotification(notificationTitle, notificationOptions);
            });
        }
    }
});
