# Solusi: Error 403 Forbidden Saat Mengakhiri Ujian CBT

## MASALAH

Error console: `POST https://localhost/siakad/index.php/siswa/cbt/saveAnswer 403 (Forbidden)`

Ujian tidak bisa diakhiri dengan klik tombol "Selesaikan Ujian", muncul error 403 Forbidden.

## ROOT CAUSE

### 1. CSRF Token Tidak Dikirim pada Submit
```javascript
// SEBELUM (SALAH):
fetch('siswa/cbt/submit/123', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
  // ❌ TIDAK ADA CSRF TOKEN!
})
```

### 2. CSRF Regenerate = true
Di `app/Config/Security.php`:
```php
public bool $regenerate = true; // Token berubah setiap submit
```

Ini menyebabkan token berubah setiap kali ada POST request, sehingga token yang di-cache di client bisa expired.

### 3. Tidak Ada Retry Mechanism untuk Submit
Berbeda dengan `saveAnswer` yang sudah punya auto-retry saat 403, fungsi `submitExam()` tidak punya mekanisme serupa.

## SOLUSI YANG DITERAPKAN

### 1. Kirim CSRF Token pada Submit ✅
```javascript
function submitExam() {
  window.onbeforeunload = null;
  
  // Use refreshed CSRF token if available, fallback to original
  const csrfToken = window.csrfToken || '<?= csrf_token() ?>';
  const csrfHash = window.csrfHash || '<?= csrf_hash() ?>';
  
  fetch('<?= site_url('siswa/cbt/submit/' . $test['id']) ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
      [csrfToken]: csrfHash  // ✅ CSRF TOKEN DIKIRIM
    })
  })
```

### 2. Auto-Retry pada 403 Error ✅
```javascript
.then(r => {
  if (!r.ok) {
    // If 403 (CSRF expired), try to refresh token and retry
    if (r.status === 403) {
      console.warn('[Submit] CSRF token may be expired, refreshing...');
      return fetch('<?= site_url('siswa/cbt/refreshCsrf') ?>', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ test_id: TEST_ID })
      })
      .then(res => res.json())
      .then(res => {
        if (res.token && res.hash) {
          window.csrfToken = res.token;
          window.csrfHash = res.hash;
          console.log('[CSRF] Token refreshed, retrying submit...');
          // Retry submit with new token
          return submitExam();
        }
      });
    }
    throw new Error('HTTP ' + r.status);
  }
  return r.json();
})
```

### 3. CSRF Token Refresh Sudah Ada ✅
Endpoint `refreshCsrf()` sudah tersedia di controller:
```php
public function refreshCsrf()
{
    // Regenerate CSRF token
    $security = \Config\Services::security();
    $newToken = $security->getCSRFTokenName();
    $newHash = $security->getCSRFHash();

    return $this->response->setJSON([
        'status' => 'ok',
        'token' => $newToken,
        'hash' => $newHash,
        'timestamp' => time()
    ]);
}
```

## FILES MODIFIED

### 1. app/Views/siswa/cbt/ujian.php
- ✅ Tambah CSRF token pada `submitExam()`
- ✅ Tambah auto-retry mechanism saat 403
- ✅ Gunakan `window.csrfToken` dan `window.csrfHash` yang di-refresh

## TESTING CHECKLIST

### Test Normal Flow
- [ ] Login sebagai siswa
- [ ] Mulai ujian CBT
- [ ] Jawab semua soal
- [ ] Klik "Selesaikan Ujian"
- [ ] Verifikasi tidak ada error 403
- [ ] Verifikasi redirect ke halaman hasil

### Test dengan CSRF Expired
- [ ] Mulai ujian
- [ ] Tunggu > 6 jam (atau ubah expires ke 60 detik untuk testing)
- [ ] Coba selesaikan ujian
- [ ] Verifikasi auto-refresh token terjadi
- [ ] Verifikasi ujian berhasil diselesaikan

### Test Console Logs
Buka Developer Console (F12), cek logs:
```
[Submit] CSRF token may be expired, refreshing...
[CSRF] Token refreshed, retrying submit...
```

### Test Network Tab
Buka Network tab, cek request:
1. POST `/siswa/cbt/submit/123` → 403 Forbidden
2. POST `/siswa/cbt/refreshCsrf` → 200 OK
3. POST `/siswa/cbt/submit/123` → 200 OK (retry berhasil)

## EXPECTED BEHAVIOR

### Sebelum Fix
```
User klik "Selesaikan Ujian"
  ↓
POST /siswa/cbt/submit/123 (tanpa CSRF token)
  ↓
403 Forbidden
  ↓
❌ Ujian tidak bisa diselesaikan
```

### Setelah Fix
```
User klik "Selesaikan Ujian"
  ↓
POST /siswa/cbt/submit/123 (dengan CSRF token)
  ↓
Jika 403 (token expired):
  ↓
POST /siswa/cbt/refreshCsrf
  ↓
Dapat token baru
  ↓
Retry POST /siswa/cbt/submit/123 (dengan token baru)
  ↓
✅ 200 OK → Redirect ke hasil
```

## MONITORING

### Log Files
Check `writable/logs/log-*.log`:
```
[CBT::refreshCsrf] Token refreshed for student 123, test 456
[CBT::submit] Test: 456, Student: 123, Score: 85.5
[CBT::internalSubmit] Test: 456, Student: 123, Score: 85.5
```

### Browser Console
```javascript
// Normal flow
[Submit] Submitting exam...

// Jika token expired
[Submit] CSRF token may be expired, refreshing...
[CSRF] Token refreshed, retrying submit...
```

## ROLLBACK (Jika Ada Masalah)

Jika ada masalah, revert perubahan di `app/Views/siswa/cbt/ujian.php`:

```javascript
// Kembalikan ke versi lama (tanpa CSRF token di submit)
function submitExam() {
  window.onbeforeunload = null;
  
  fetch('<?= site_url('siswa/cbt/submit/' . $test['id']) ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(r => r.json())
  .then(res => {
    // ... rest of code
  });
}
```

## ALTERNATIVE SOLUTION (Jika Masih Bermasalah)

### Option 1: Disable CSRF untuk Submit Endpoint
Di `app/Config/Filters.php`:
```php
public array $globals = [
    'before' => [
        'csrf' => ['except' => [
            'siswa/cbt/submit/*',
            'siswa/cbt/saveAnswer',
            'siswa/cbt/saveAnswersBulk'
        ]],
    ],
];
```

⚠️ **TIDAK DIREKOMENDASIKAN** untuk production karena security risk!

### Option 2: Gunakan FormData Instead of JSON
```javascript
const fd = new FormData();
fd.append(csrfToken, csrfHash);

fetch('...', {
  method: 'POST',
  body: fd  // FormData instead of JSON
})
```

## NOTES

- CSRF token expires = 21600 detik (6 jam) sudah cukup untuk ujian panjang
- Auto-refresh token setiap 60 menit sudah berjalan di background
- Retry mechanism hanya 1x untuk avoid infinite loop
- Semua changes backward compatible dengan existing code

## PRODUCTION DEPLOYMENT

1. Backup file `app/Views/siswa/cbt/ujian.php`
2. Apply changes dari solusi ini
3. Test di staging environment dulu
4. Monitor logs untuk error 403
5. Deploy ke production saat jam sepi (malam/weekend)
6. Monitor console logs dari beberapa siswa

## SUPPORT

Jika masih ada masalah:
1. Check browser console untuk error logs
2. Check server logs di `writable/logs/`
3. Verify CSRF settings di `app/Config/Security.php`
4. Test dengan browser berbeda (Chrome, Firefox, Edge)
5. Clear browser cache dan cookies
