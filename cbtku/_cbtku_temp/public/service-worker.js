const CACHE_VERSION = 'v4';
const CACHE_NAME = 'cbt-cache-' + CACHE_VERSION;
const OFFLINE_CACHE = 'cbt-offline-' + CACHE_VERSION;

// Assets to precache on install
const PRECACHE_URLS = [
  'offline.html',
  'manifest.json'
];

// Install event - precache critical resources
self.addEventListener('install', event => {
  console.log('[SW] Installing service worker...');
  event.waitUntil(
    (async () => {
      try {
        const cache = await caches.open(OFFLINE_CACHE);
        for (const url of PRECACHE_URLS) {
          try {
            const response = await fetch(url, { cache: 'no-store' });
            if (response.ok) {
              await cache.put(url, response);
            }
          } catch (err) {
            console.warn('[SW] Failed to cache:', url, err);
          }
        }
        self.skipWaiting();
      } catch (err) {
        console.error('[SW] Installation error:', err);
      }
    })()
  );
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
      await self.clients.claim();
    })()
  );
});

// Fetch event
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Skip cross-origin requests (CDN, external fonts, etc.)
  // Biarkan browser handle langsung - jangan cache CDN assets
  if (url.origin !== location.origin) {
    return;
  }

  // Skip non-GET requests
  if (req.method !== 'GET') {
    return;
  }

  // Skip semua dynamic routes (PHP/CI4)
  const isDynamicRoute = (
    url.pathname.includes('/siswa/') ||
    url.pathname.includes('/admin/') ||
    url.pathname.includes('/dashboard') ||
    url.pathname.includes('/login') ||
    url.pathname.includes('/logout') ||
    url.pathname.includes('/index.php/') ||
    url.pathname.endsWith('.php')
  );

  if (isDynamicRoute) {
    return;
  }

  // Untuk static assets lokal: Network-first dengan fallback cache
  // Ini memastikan asset terbaru selalu diambil, tapi tetap bisa offline
  if (
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.woff') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.ttf')
  ) {
    event.respondWith(
      fetch(req)
        .then(response => {
          // Simpan ke cache jika berhasil
          if (response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(req, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Fallback ke cache jika network gagal
          return caches.match(req);
        })
    );
    return;
  }

  // Untuk gambar: cache-first (gambar jarang berubah)
  if (
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.jpeg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.ico') ||
    url.pathname.endsWith('.webp')
  ) {
    event.respondWith(
      caches.match(req).then(cachedResponse => {
        if (cachedResponse) {
          return cachedResponse;
        }
        return fetch(req).then(response => {
          if (response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(req, responseClone);
            });
          }
          return response;
        });
      })
    );
    return;
  }
});

// Background sync for offline answer submission
self.addEventListener('sync', function(event) {
  console.log('[SW] Background sync event:', event.tag);
  if (event.tag.startsWith('sync-answers')) {
    event.waitUntil(syncPendingAnswers());
  }
});

// Sync pending answers from IndexedDB
async function syncPendingAnswers() {
  const DB_NAME = 'cbt_pending_db';
  const STORE = 'pending_answers';

  function openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(DB_NAME, 1);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains(STORE)) {
          db.createObjectStore(STORE, { autoIncrement: true });
        }
      };
      req.onsuccess = (e) => resolve(e.target.result);
      req.onerror = (e) => reject(e.target.error);
    });
  }

  try {
    const db = await openDB();
    const tx = db.transaction(STORE, 'readwrite');
    const store = tx.objectStore(STORE);
    const allReq = store.getAll();
    
    allReq.onsuccess = async function() {
      const all = allReq.result || [];
      console.log('[SW] Syncing', all.length, 'pending answers...');
      
      let successCount = 0;
      for (const payload of all) {
        try {
          const res = await fetch('siswa/cbt/saveAnswersBulk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          
          if (res.ok) {
            successCount++;
          } else {
            console.warn('[SW] Sync failed for payload, will retry later');
            return; // Stop and keep remaining items
          }
        } catch (e) {
          console.warn('[SW] Sync attempt failed:', e);
          return; // Stop and keep remaining items
        }
      }
      
      // Clear store after successful sync of all items
      if (successCount === all.length && all.length > 0) {
        store.clear();
        console.log('[SW] All pending answers synced successfully');
      }
    };
  } catch (e) {
    console.error('[SW] syncPendingAnswers error:', e);
  }
}

console.log('[SW] Service Worker loaded');
