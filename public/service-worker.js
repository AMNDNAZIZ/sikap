const CACHE_NAME = 'siakad-cbt-v1';
const OFFLINE_URL = './offline.html';

// Assets to cache on install
const STATIC_CACHE = [
  './',
  './offline.html',
  './assets/css/style.css',
  './assets/js/script.js'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - network-first for API, cache-first for assets
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // Network-first strategy for API endpoints
  if (url.pathname.includes('/siswa/cbt/saveAnswer') ||
    url.pathname.includes('/siswa/cbt/submit')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Clone and cache successful responses
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // If network fails, try cache
          return caches.match(request).then((cached) => {
            if (cached) {
              return cached;
            }
            // Store failed request for background sync
            return storeFailedRequest(request);
          });
        })
    );
    return;
  }

  // Navigate requests - try network, fallback to cache, then offline page
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .catch(() => {
          return caches.match(request).then((cached) => {
            return cached || caches.match(OFFLINE_URL);
          });
        })
    );
    return;
  }

  // Cache-first strategy for static assets
  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) {
        return cached;
      }

      return fetch(request).then((response) => {
        // Don't cache if not successful
        if (!response || response.status !== 200 || response.type === 'error') {
          return response;
        }

        // Clone and cache
        const responseClone = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(request, responseClone);
        });

        return response;
      });
    })
  );
});

// Background sync for failed answer submissions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-answers') {
    console.log('[Service Worker] Background sync triggered');
    event.waitUntil(syncPendingAnswers());
  }
});

// Store failed request for background sync
async function storeFailedRequest(request) {
  const clonedRequest = request.clone();
  const body = await clonedRequest.text();

  // Open IndexedDB to store pending requests
  return new Promise((resolve) => {
    const dbRequest = indexedDB.open('cbt-pending', 1);

    dbRequest.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('answers')) {
        db.createObjectStore('answers', { keyPath: 'id', autoIncrement: true });
      }
    };

    dbRequest.onsuccess = (event) => {
      const db = event.target.result;
      const transaction = db.transaction(['answers'], 'readwrite');
      const store = transaction.objectStore('answers');

      store.add({
        url: request.url,
        method: request.method,
        body: body,
        timestamp: Date.now()
      });

      // Register background sync
      self.registration.sync.register('sync-answers').catch(() => {
        console.log('[Service Worker] Background sync registration failed');
      });

      resolve(new Response(JSON.stringify({
        status: 'queued',
        message: 'Jawaban tersimpan, akan dikirim saat online'
      }), {
        headers: { 'Content-Type': 'application/json' }
      }));
    };
  });
}

// Sync pending answers when back online
async function syncPendingAnswers() {
  return new Promise((resolve, reject) => {
    const dbRequest = indexedDB.open('cbt-pending', 1);

    dbRequest.onsuccess = async (event) => {
      const db = event.target.result;

      if (!db.objectStoreNames.contains('answers')) {
        resolve();
        return;
      }

      const transaction = db.transaction(['answers'], 'readwrite');
      const store = transaction.objectStore('answers');
      const getAllRequest = store.getAll();

      getAllRequest.onsuccess = async () => {
        const pendingAnswers = getAllRequest.result;

        if (pendingAnswers.length === 0) {
          resolve();
          return;
        }

        console.log(`[Service Worker] Syncing ${pendingAnswers.length} pending answers`);

        for (const answer of pendingAnswers) {
          try {
            const response = await fetch(answer.url, {
              method: answer.method,
              body: answer.body,
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              }
            });

            if (response.ok) {
              // Remove from pending
              const deleteTransaction = db.transaction(['answers'], 'readwrite');
              const deleteStore = deleteTransaction.objectStore('answers');
              deleteStore.delete(answer.id);
            }
          } catch (error) {
            console.error('[Service Worker] Failed to sync answer:', error);
          }
        }

        resolve();
      };
    };

    dbRequest.onerror = () => reject();
  });
}

console.log('[Service Worker] Loaded');
