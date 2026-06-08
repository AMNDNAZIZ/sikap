const CACHE_VERSION = 'v2';
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
        
        // Try to cache each URL individually
        for (const url of PRECACHE_URLS) {
          try {
            const response = await fetch(url, { cache: 'no-store' });
            if (response.ok) {
              await cache.put(url, response);
              console.log('[SW] Cached:', url);
            }
          } catch (err) {
            console.warn('[SW] Failed to cache:', url, err);
          }
        }
        
        // Skip waiting to activate immediately
        self.skipWaiting();
        console.log('[SW] Installation complete');
      } catch (err) {
        console.error('[SW] Installation error:', err);
      }
    })()
  );
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
  console.log('[SW] Activating service worker...');
  event.waitUntil(
    (async () => {
      // Delete old caches
      const cacheNames = await caches.keys();
      await Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
            console.log('[SW] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
      
      // Take control of all clients immediately
      await self.clients.claim();
      console.log('[SW] Activation complete');
    })()
  );
});

// Fetch event - handle requests with appropriate strategies
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // Network-first for CBT API endpoints (critical for real-time data)
  if (url.pathname.includes('/siswa/cbt/saveAnswersBulk') ||
      url.pathname.includes('/siswa/cbt/ping') ||
      url.pathname.includes('/siswa/cbt/submit')) {
    event.respondWith(
      fetch(req)
        .then(response => {
          // Don't cache API responses, just return them
          return response;
        })
        .catch(err => {
          console.warn('[SW] API request failed (offline?):', url.pathname);
          // Return a basic error response for API calls
          return new Response(
            JSON.stringify({ error: 'offline', message: 'Request will be queued' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
          );
        })
    );
    return;
  }

  // For HTML pages: network-first, cache as fallback, offline.html as last resort
  if (req.mode === 'navigate' || req.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(req)
        .then(response => {
          // Cache successful navigations
          if (response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(req, responseClone);
            });
          }
          return response;
        })
        .catch(async () => {
          // Try cached version first
          const cachedResponse = await caches.match(req);
          if (cachedResponse) {
            return cachedResponse;
          }
          
          // Fall back to offline page
          const offlineResponse = await caches.match('offline.html');
          if (offlineResponse) {
            return offlineResponse;
          }
          
          // Ultimate fallback
          return new Response(
            '<h1>Offline</h1><p>No cached version available.</p>',
            { headers: { 'Content-Type': 'text/html' } }
          );
        })
    );
    return;
  }

  // For static assets: cache-first strategy
  event.respondWith(
    caches.match(req).then(cachedResponse => {
      if (cachedResponse) {
        return cachedResponse;
      }
      
      return fetch(req).then(response => {
        // Cache successful responses for static assets
        if (response.ok && (
          url.pathname.endsWith('.css') ||
          url.pathname.endsWith('.js') ||
          url.pathname.endsWith('.png') ||
          url.pathname.endsWith('.jpg') ||
          url.pathname.endsWith('.svg') ||
          url.pathname.endsWith('.woff') ||
          url.pathname.endsWith('.woff2')
        )) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(req, responseClone);
          });
        }
        return response;
      });
    })
  );
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
