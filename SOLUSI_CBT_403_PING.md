# Solusi: Error 403 Forbidden pada Ping CBT

## MASALAH

```
Error console: POST https://localhost/siakad/index.php/siswa/cbt/ping 403 (Forbidden)
```

Endpoint `ping` mengalami error 403 Forbidden, menyebabkan:
- Heartbeat tidak berjalan
- Last activity tidak update
- Anti-cheat monitoring tidak berfungsi
- Session status tidak ter-sync

## ROOT CAUSE

### 1. Ping Tidak Mengirim CSRF Token
```javascript
// SEBELUM (SALAH):
const ping = (event = null) => {
  const data = { test_id: TEST_ID };
  if (event) data.event = event;
  
  fetch('siswa/cbt/ping', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data) // ❌ TIDAK ADA CSRF TOKEN!
  })
}
```

### 2. RefreshCsrf Juga Kena CSRF Check
Endpoint `refreshCsrf` juga POST request, jadi juga kena CSRF validation.

**Chicken-and-Egg Problem:**
- Butuh token untuk refresh token
- Jika token expired, tidak bisa refresh
- Infinite loop 403 error

## SOLUSI YANG DITERAPKAN

### 1. Kirim CSRF Token pada Ping ✅

**File:** `app/Views/siswa/cbt/ujian.php`

```javascript
const ping = (event = null) => {
  const data = { test_id: TEST_ID };
  if (event) data.event = event;
  
  // Add CSRF token to ping request
  const csrfToken = window.csrfToken || '<?= csrf_token() ?>';
  const csrfHash = window.csrfHash || '<?= csrf_hash() ?>';
  data[csrfToken] = csrfHash; // ✅ CSRF TOKEN DIKIRIM
  
  fetch('<?= site_url('siswa/cbt/ping') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
}
```

### 2. Auto-Retry pada 403 Error ✅

```javascript
.then(r => {
  if (!r.ok) {
    if (r.status === 403) {
      console.warn('[Ping] CSRF token may be expired, refreshing...');
      // Refresh token and retry
      fetch('refreshCsrf')
        .then(res => res.json())
        .then(res => {
          if (res.token && res.hash) {
            window.csrfToken = res.token;
            window.csrfHash = res.hash;
            ping(event); // Retry
          }
        });
    }
    return;
  }
  return r.json();
})
```

### 3. Exclude RefreshCsrf dari CSRF Check ✅

**File:** `app/Config/Filters.php`

```php
public array $globals = [
    'before' => [
        'csrf' => ['except' => [
            'siswa/cbt/refreshCsrf', // ✅ Exclude from CSRF check
        ]],
    ],
];
```

**Alasan:**
- `refreshCsrf` adalah endpoint untuk mendapatkan token baru
- Tidak bisa require CSRF token karena itu chicken-and-egg problem
- Endpoint sudah protected dengan:
  - AJAX check (`isAJAX()`)
  - Authentication check (`getStudentId()`)
  - Active session check
  - Rate limiting (via RateLimitFilter)

## FILES MODIFIED

### 1. app/Views/siswa/cbt/ujian.php ✅
- Tambah CSRF token pada `ping()` function
- Tambah auto-retry mechanism saat 403
- Gunakan `window.csrfToken` dan `window.csrfHash`

### 2. app/Config/Filters.php ✅
- Exclude `siswa/cbt/refreshCsrf` dari CSRF check
- Added documentation explaining why

## SECURITY CONSIDERATIONS

### Apakah Aman Exclude RefreshCsrf?

**YA, masih aman karena:**

1. **AJAX Only**
   ```php
   if (!$this->request->isAJAX()) {
       return $this->response->setStatusCode(400);
   }
   ```

2. **Authentication Required**
   ```php
   $studentId = $this->getStudentId();
   if (!$studentId) {
       return $this->response->setStatusCode(401);
   }
   ```

3. **Active Session Check**
   ```php
   $session = $this->sessionModel
       ->where('student_id', $studentId)
       ->where('test_id', (int) $testId)
       ->where('status', 'active')
       ->first();
   
   if (!$session) {
       return $this->response->setStatusCode(403);
   }
   ```

4. **Rate Limiting**
   - RateLimitFilter applied globally
   - Prevent brute force token refresh

5. **Session-based**
   - Token tied to user session
   - Logout = token invalid

6. **HTTPS**
   - Token dikirim via HTTPS
   - Prevent man-in-the-middle

### Attack Scenarios

**Scenario 1: Attacker tries to refresh token without session**
```
POST /siswa/cbt/refreshCsrf
→ 401 Unauthorized (no studentId)
```

**Scenario 2: Attacker tries to refresh token without active exam**
```
POST /siswa/cbt/refreshCsrf
→ 403 Forbidden (no active session)
```

**Scenario 3: Attacker tries to brute force**
```
POST /siswa/cbt/refreshCsrf (100x)
→ 429 Too Many Requests (rate limit)
```

**Scenario 4: Attacker tries from non-AJAX**
```
POST /siswa/cbt/refreshCsrf (via browser)
→ 400 Bad Request (not AJAX)
```

## TESTING

### Test Script
```bash
php test_cbt_submit_csrf.php
```

Expected output:
```
✅ Ping sends CSRF token
✅ Ping has auto-retry on 403
✅ RefreshCsrf excluded from CSRF check
✅ RefreshCsrf has authentication check
✅ RefreshCsrf has active session check
```

### Manual Testing
1. Login sebagai siswa
2. Mulai ujian CBT
3. Buka Console (F12)
4. Biarkan ujian berjalan 5 menit
5. Check console logs:
   ```
   [Ping] Heartbeat sent
   [CSRF] Token auto-refreshed
   ```
6. Verifikasi tidak ada error 403 pada ping

### Network Tab
Check POST requests:
```
POST /siswa/cbt/ping → 200 OK (with CSRF token)
POST /siswa/cbt/refreshCsrf → 200 OK (no CSRF required)
POST /siswa/cbt/saveAnswer → 200 OK (with CSRF token)
POST /siswa/cbt/submit → 200 OK (with CSRF token)
```

## EXPECTED BEHAVIOR

### Sebelum Fix
```
Ping interval (30 detik)
  ↓
POST /ping (tanpa CSRF token)
  ↓
403 Forbidden
  ↓
❌ Heartbeat gagal
❌ Last activity tidak update
❌ Anti-cheat tidak berfungsi
```

### Setelah Fix
```
Ping interval (30 detik)
  ↓
POST /ping (dengan CSRF token)
  ↓
200 OK
  ↓
✅ Heartbeat sukses
✅ Last activity update
✅ Anti-cheat berfungsi
```

## MONITORING

### Browser Console
```javascript
// Normal flow
[Ping] Heartbeat sent
[CSRF] Token auto-refreshed at 10:30:00

// Jika token expired (auto-recovery)
[Ping] CSRF token may be expired, refreshing...
[CSRF] Token refreshed after ping 403 error
```

### Server Logs
```
INFO - [CBT::ping] Heartbeat from student 123, test 456
INFO - [CBT::refreshCsrf] Token refreshed for student 123, test 456
```

## RELATED ENDPOINTS

Semua endpoint CBT POST sekarang sudah handle CSRF dengan benar:

1. **saveAnswer** ✅
   - Sends CSRF token via FormData
   - Auto-retry on 403
   - Read token from cookie after save

2. **saveAnswersBulk** ✅
   - Sends CSRF token via JSON
   - Auto-retry on 403

3. **submit** ✅
   - Sends CSRF token via JSON
   - Auto-retry on 403

4. **ping** ✅ (FIX BARU INI)
   - Sends CSRF token via JSON
   - Auto-retry on 403

5. **refreshCsrf** ✅
   - Excluded from CSRF check
   - Protected by authentication + session check

## ROLLBACK PLAN

Jika ada masalah, revert changes:

### 1. Revert Filters.php
```php
// Remove exception
public array $globals = [
    'before' => [
        'csrf', // No exceptions
    ],
];
```

### 2. Revert ujian.php
```javascript
// Remove CSRF token from ping
const ping = (event = null) => {
  const data = { test_id: TEST_ID };
  if (event) data.event = event;
  
  fetch('ping', {
    method: 'POST',
    body: JSON.stringify(data)
  });
};
```

**Konsekuensi:**
- 403 error akan muncul lagi pada ping
- Heartbeat tidak berfungsi

## PRODUCTION DEPLOYMENT

### Pre-Deployment
- [x] Backup `app/Views/siswa/cbt/ujian.php`
- [x] Backup `app/Config/Filters.php`
- [x] Test di staging environment
- [x] Verify all checks pass

### Deployment
1. Deploy saat jam sepi
2. Monitor logs untuk error 403
3. Monitor ping heartbeat
4. Verify anti-cheat berfungsi

### Post-Deployment
1. Test dengan beberapa siswa
2. Monitor server logs
3. Check browser console
4. Verify ping tidak ada error 403

## SUMMARY

**Masalah:** Error 403 Forbidden pada ping karena tidak mengirim CSRF token

**Solusi:**
1. Kirim CSRF token pada ping request
2. Auto-retry jika 403 error
3. Exclude refreshCsrf dari CSRF check

**Status:** ✅ FIXED AND TESTED

**Security:** ✅ AMAN (refreshCsrf protected by auth + session check)

**User Experience:** ✅ IMPROVED (heartbeat berfungsi, anti-cheat aktif)

Ping CBT sekarang berjalan lancar tanpa error 403!
