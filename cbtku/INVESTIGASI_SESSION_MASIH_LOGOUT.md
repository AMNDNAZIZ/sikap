# 🔍 INVESTIGASI: Session Masih Logout (Follow-up)

**Tanggal**: 9 Maret 2026  
**Status**: INVESTIGATING  
**Issue**: Masih logout setelah beberapa menit dan saat halaman ditinggalkan

---

## 🚨 MASALAH BARU

User melaporkan bahwa meskipun sudah ada fix, masih tetap minta login ketika:
1. Beberapa menit idle
2. Halaman ditinggalkan (tab ditutup/minimize)

---

## 🔍 ROOT CAUSE ANALYSIS (DEEPER)

### TEMUAN PENTING: Cookie.php TIDAK MENGONTROL SESSION COOKIE ⚠️

**Fakta**:
- `app/Config/Cookie.php` hanya untuk **application cookies** (set via `set_cookie()` helper)
- **Session cookie** dikontrol oleh:
  1. `app/Config/Session.php` → `$expiration`
  2. PHP `session.cookie_lifetime` di `php.ini`
  3. CodeIgniter Session Handler

**Kesimpulan**: Perubahan `Cookie.php` expires = 14400 **TIDAK BERPENGARUH** ke session cookie!

---

## 🎯 MASALAH SEBENARNYA

### 1. Session Cookie Lifetime Masih 0 (Session Cookie)

**Lokasi**: PHP Session Configuration

**Cek dengan**:
```php
<?php
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime');
// Jika hasilnya 0 → session cookie (hilang saat browser ditutup)
```

**Dampak**:
- Session cookie dihapus saat tab ditutup
- Session cookie dihapus saat browser idle (memory cleanup)
- Meskipun session di server masih valid, cookie sudah hilang

---

### 2. Session Garbage Collection Terlalu Agresif

**Lokasi**: PHP Session Configuration

**Cek dengan**:
```php
<?php
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime');
echo "session.gc_probability: " . ini_get('session.gc_probability');
echo "session.gc_divisor: " . ini_get('session.gc_divisor');
```

**Dampak**:
- Jika `gc_maxlifetime` < 14400, session files dihapus sebelum expiration
- Garbage collector menghapus session files yang "terlalu lama"

---

### 3. Writable/Session Folder Permission

**Lokasi**: `writable/session/`

**Cek dengan**:
```bash
ls -la writable/session/
```

**Dampak**:
- Jika permission salah, session tidak bisa disimpan
- Session hilang setiap request

---

### 4. Cache/Browser Behavior

**Lokasi**: Browser

**Dampak**:
- Browser modern (Chrome, Edge) agresif menghapus session cookies
- Tab discarding menghapus session cookies
- Memory saver mode menghapus session cookies

---

## ✅ SOLUSI KOMPREHENSIF

### SOLUSI 1: Override PHP Session Cookie Lifetime (CRITICAL) ✅

**File**: `app/Config/Boot/production.php` dan `app/Config/Boot/development.php`

**Tambahkan di awal file**:
```php
<?php

// 🔒 SESSION FIX: Override PHP session cookie lifetime
// Set session cookie to expire in 4 hours (14400 seconds)
// This prevents session cookie from being deleted when tab is closed
ini_set('session.cookie_lifetime', '14400'); // 4 hours
ini_set('session.gc_maxlifetime', '14400');  // 4 hours
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');

// ... rest of file
```

**Manfaat**:
- Session cookie bertahan 4 jam (tidak hilang saat tab ditutup)
- Garbage collector tidak menghapus session sebelum 4 jam
- Override php.ini settings per-application

---

### SOLUSI 2: Set Session Cookie Params di BaseController (CRITICAL) ✅

**File**: `app/Controllers/BaseController.php`

**Tambahkan di method `initController()` sebelum `parent::initController()`**:
```php
public function initController(
    \CodeIgniter\HTTP\RequestInterface $request,
    \CodeIgniter\HTTP\ResponseInterface $response,
    \Psr\Log\LoggerInterface $logger
) {
    // 🔒 SESSION FIX: Set session cookie parameters BEFORE session starts
    // This ensures session cookie has proper lifetime
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 14400,  // 4 hours
            'path' => '/',
            'domain' => '',
            'secure' => false,    // Set true if using HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    \App\Libraries\LicenseGuard::verify();
    parent::initController($request, $response, $logger);
    
    // ... rest of method
}
```

**Manfaat**:
- Memastikan session cookie params di-set sebelum session start
- Override php.ini settings
- Konsisten di semua requests

---

### SOLUSI 3: Extend Session di Auth Filter (ENHANCEMENT) ✅

**File**: `app/Filters/Auth.php`

**Tambahkan setelah session check**:
```php
public function before(RequestInterface $request, $arguments = null)
{
    $session = session();
    $user = $session->get('user');
    
    // 🔒 SESSION FIX: Extend session lifetime on every request
    // This prevents session from expiring while user is active
    if ($user && $session->get('logged_in')) {
        // Mark session as active (extends lifetime)
        $session->markAsTempdata('user', 14400);      // 4 hours
        $session->markAsTempdata('logged_in', 14400); // 4 hours
    }
    
    // ... rest of method
}
```

**Manfaat**:
- Session lifetime di-extend setiap request
- User yang aktif tidak akan timeout
- Sliding session expiration

---

### SOLUSI 4: Check & Fix Session Folder Permissions ✅

**Command**:
```bash
# Windows (XAMPP)
icacls writable\session /grant Everyone:(OI)(CI)F

# Linux/Mac
chmod 777 writable/session
chown -R www-data:www-data writable/session
```

**Manfaat**:
- Memastikan session files bisa ditulis
- Mencegah session loss karena permission error

---

### SOLUSI 5: Add Session Keepalive (OPTIONAL) ⚠️

**File**: `app/Views/templates/header.php` atau layout utama

**Tambahkan di `<head>` section**:
```html
<script>
// 🔒 SESSION KEEPALIVE: Ping server every 5 minutes to keep session alive
setInterval(function() {
    fetch('<?= base_url('ping') ?>', {
        method: 'GET',
        credentials: 'same-origin'
    }).catch(function(error) {
        console.log('Keepalive ping failed:', error);
    });
}, 300000); // 5 minutes
</script>
```

**Tambahkan route di `app/Config/Routes.php`**:
```php
$routes->get('ping', function() {
    // Just return OK to keep session alive
    return 'OK';
});
```

**Manfaat**:
- Session tetap aktif meskipun user idle
- Mencegah timeout saat user membaca/berpikir lama

---

## 📊 PRIORITAS IMPLEMENTASI

### CRITICAL (Harus dilakukan):
1. ✅ Override PHP session cookie lifetime di Boot files
2. ✅ Set session cookie params di BaseController
3. ✅ Check & fix session folder permissions

### RECOMMENDED (Sangat disarankan):
4. ✅ Extend session di Auth Filter
5. ⚠️ Add session keepalive (optional, untuk extra safety)

---

## 🧪 TESTING CHECKLIST

### Test 1: Session Cookie Lifetime
```php
// Create test file: test_session_config.php
<?php
require 'vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();

echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";
echo "Expected: 14400 (4 hours)\n";
```

### Test 2: Browser DevTools
1. Login ke aplikasi
2. Buka DevTools (F12) → Application → Cookies
3. Cek cookie `ci_session`:
   - **Expires**: Harus ada tanggal (bukan "Session")
   - **Max-Age**: Harus 14400 atau lebih

### Test 3: Idle Test
1. Login
2. Tunggu 15 menit (idle, tidak ada aktivitas)
3. Refresh page
4. **Expected**: Masih login

### Test 4: Tab Close Test
1. Login
2. Tutup tab
3. Buka tab baru, akses aplikasi
4. **Expected**: Masih login (dalam 4 jam)

---

## 🚀 NEXT STEPS

1. ⏳ Implementasi SOLUSI 1 & 2 (CRITICAL)
2. ⏳ Check session folder permissions
3. ⏳ Test dengan browser DevTools
4. ⏳ Test idle 15 menit
5. ⏳ Monitor logs

---

**Status**: READY FOR IMPLEMENTATION  
**Priority**: CRITICAL
