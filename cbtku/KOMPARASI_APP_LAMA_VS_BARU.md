# 🔍 KOMPARASI: App Lama (Normal) vs App Baru (Bermasalah)

**Tanggal**: 9 Maret 2026  
**Status**: ROOT CAUSE IDENTIFIED ✅  
**Kesimpulan**: OVER-ENGINEERING menyebabkan masalah!

---

## 📊 PERBANDINGAN KONFIGURASI

### 1. Session.php

| Setting | App Lama (Normal) | App Baru (Bermasalah) | Status |
|---------|-------------------|----------------------|--------|
| `$expiration` | 7200 (2 jam) | 14400 (4 jam) | ⚠️ Berubah |
| `$timeToUpdate` | 300 (5 menit) | 1800 (30 menit) | ⚠️ Berubah |
| `$regenerateDestroy` | false | false | ✅ Sama |
| `$matchIP` | false | false | ✅ Sama |

---

### 2. Cookie.php

| Setting | App Lama (Normal) | App Baru (Bermasalah) | Status |
|---------|-------------------|----------------------|--------|
| `$expires` | 0 (Session) | 14400 (4 jam) | ⚠️ Berubah |
| `$httponly` | true | true | ✅ Sama |
| `$samesite` | 'Lax' | 'Lax' | ✅ Sama |

---

### 3. Boot Files (production.php & development.php)

| Setting | App Lama (Normal) | App Baru (Bermasalah) | Status |
|---------|-------------------|----------------------|--------|
| `ini_set('session.cookie_lifetime')` | ❌ Tidak ada | ✅ Ada (14400) | ⚠️ DITAMBAHKAN |
| `ini_set('session.gc_maxlifetime')` | ❌ Tidak ada | ✅ Ada (14400) | ⚠️ DITAMBAHKAN |
| `ini_set('session.gc_probability')` | ❌ Tidak ada | ✅ Ada (1) | ⚠️ DITAMBAHKAN |
| `ini_set('session.gc_divisor')` | ❌ Tidak ada | ✅ Ada (100) | ⚠️ DITAMBAHKAN |

---

### 4. BaseController.php

| Feature | App Lama (Normal) | App Baru (Bermasalah) | Status |
|---------|-------------------|----------------------|--------|
| `session_set_cookie_params()` | ❌ Tidak ada | ✅ Ada | ⚠️ DITAMBAHKAN |
| LicenseGuard | ✅ Ada | ✅ Ada | ✅ Sama |
| Security Headers | ✅ Ada | ✅ Ada | ✅ Sama |

---

### 5. Auth.php Filter

| Feature | App Lama (Normal) | App Baru (Bermasalah) | Status |
|---------|-------------------|----------------------|--------|
| Sliding Expiration (`markAsTempdata`) | ❌ Tidak ada | ✅ Ada | ⚠️ DITAMBAHKAN |
| Session ID Sync | ❌ Tidak ada | ✅ Ada | ⚠️ DITAMBAHKAN |
| Exam Protection | ✅ Ada | ✅ Ada | ✅ Sama |
| Heartbeat Update | ✅ Ada | ✅ Ada | ✅ Sama |

---

## 🚨 ROOT CAUSE: OVER-ENGINEERING!

### MASALAH UTAMA:

**Terlalu banyak layer yang saling bertabrakan!**

1. **Boot Files** set `session.cookie_lifetime = 14400`
2. **BaseController** set `session_set_cookie_params(['lifetime' => 14400])`
3. **Session.php** set `$expiration = 14400`
4. **Auth Filter** set `markAsTempdata('user', 14400)`

**Konflik**:
- 4 layer berbeda mencoba mengontrol session lifetime
- `session_set_cookie_params()` di BaseController dipanggil SETELAH session sudah start
- `markAsTempdata()` di Auth Filter mengubah session data menjadi tempdata (auto-expire)
- Session regeneration 30 menit + sliding expiration = race condition

---

## 🎯 KESIMPULAN

### App Lama (Normal):
- **Simple & Clean**: Hanya pakai Session.php config
- **No Override**: Tidak ada ini_set() atau session_set_cookie_params()
- **No Sliding**: Tidak ada markAsTempdata() untuk session data
- **Works Perfectly**: 2 jam session, regenerate 5 menit, no problem

### App Baru (Bermasalah):
- **Over-Engineered**: 4 layer berbeda mengontrol session
- **Conflicts**: Layer-layer saling bertabrakan
- **Complexity**: Terlalu banyak logic yang tidak perlu
- **Result**: Logout paksa karena konflik

---

## ✅ SOLUSI: ROLLBACK KE SIMPLE CONFIG

### PRINSIP: "If it ain't broke, don't fix it!"

**Action**: Kembalikan ke konfigurasi app_lama yang sudah terbukti bekerja

---

## 🔧 ROLLBACK PLAN

### 1. Session.php - Kembalikan ke 2 jam ✅

**Sebelum** (App Baru):
```php
public int $expiration = 14400; // 4 hours
public int $timeToUpdate = 1800; // 30 minutes
```

**Sesudah** (App Lama):
```php
public int $expiration = 7200; // 2 hours
public int $timeToUpdate = 300; // 5 minutes
```

---

### 2. Cookie.php - Kembalikan ke Session Cookie ✅

**Sebelum** (App Baru):
```php
public $expires = 14400; // 4 hours
```

**Sesudah** (App Lama):
```php
public $expires = 0; // Session cookie
```

---

### 3. Boot Files - HAPUS semua ini_set() ✅

**Sebelum** (App Baru):
```php
ini_set('session.cookie_lifetime', '14400');
ini_set('session.gc_maxlifetime', '14400');
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
```

**Sesudah** (App Lama):
```php
// TIDAK ADA - Biarkan kosong
```

---

### 4. BaseController - HAPUS session_set_cookie_params() ✅

**Sebelum** (App Baru):
```php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 14400,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
```

**Sesudah** (App Lama):
```php
// TIDAK ADA - Hapus seluruh block ini
```

---

### 5. Auth.php Filter - HAPUS Sliding Expiration & Session ID Sync ✅

**Sebelum** (App Baru):
```php
// 🔒 SESSION FIX: Extend session lifetime on every request (Sliding Expiration)
if ($user && $session->get('logged_in')) {
    $session->markAsTempdata('user', 14400);
    $session->markAsTempdata('logged_in', 14400);
}

// 🔒 UPDATE: Sync active_session_id if regenerated
if ($user && !empty($user['id'])) {
    $currentSessionId = session_id();
    $userModel = new \App\Models\UserModel();
    $dbUser = $userModel->find($user['id']);
    if ($dbUser && !empty($dbUser['active_session_id']) && $dbUser['active_session_id'] !== $currentSessionId) {
        $userModel->update($user['id'], [
            'active_session_id' => $currentSessionId
        ]);
    }
}
```

**Sesudah** (App Lama):
```php
// TIDAK ADA - Hapus kedua block ini
// Biarkan hanya exam protection dan heartbeat update
```

---

## 📋 CHECKLIST ROLLBACK

### Files to Modify:

- [ ] `app/Config/Session.php` - Kembalikan expiration = 7200, timeToUpdate = 300
- [ ] `app/Config/Cookie.php` - Kembalikan expires = 0
- [ ] `app/Config/Boot/production.php` - Hapus semua ini_set() session
- [ ] `app/Config/Boot/development.php` - Hapus semua ini_set() session
- [ ] `app/Controllers/BaseController.php` - Hapus session_set_cookie_params()
- [ ] `app/Filters/Auth.php` - Hapus sliding expiration & session ID sync

### Files to Keep (No Change):

- ✅ `app/Controllers/Auth.php` - Keep double login tolerance 10 min (improvement)
- ✅ `app/Helpers/auth_helper.php` - Keep as is
- ✅ Exam protection logic - Keep as is

---

## 🧪 TESTING AFTER ROLLBACK

### Test 1: Normal Login
1. Login sebagai admin
2. Tunggu 5 menit
3. Klik menu
4. **Expected**: Masih login ✅

### Test 2: Session Expiration (2 Jam)
1. Login sebagai admin
2. Tunggu 2 jam + 5 menit (idle)
3. Refresh page
4. **Expected**: Logout (session expired) ✅

### Test 3: Exam Session
1. Login sebagai siswa
2. Mulai ujian
3. Refresh berkali-kali
4. **Expected**: Tidak logout ✅

---

## 💡 LESSONS LEARNED

### 1. Keep It Simple, Stupid (KISS)
- Jangan over-engineer jika tidak perlu
- Simple solution yang bekerja > Complex solution yang bermasalah

### 2. Don't Fix What Ain't Broke
- App lama bekerja dengan baik dengan config simple
- Tidak perlu tambah layer-layer kompleks

### 3. Test Before Deploy
- Seharusnya test dulu sebelum deploy ke production
- Bandingkan dengan app lama yang bekerja

### 4. Understand the Framework
- CodeIgniter sudah handle session dengan baik
- Tidak perlu override dengan ini_set() atau session_set_cookie_params()
- Session.php config sudah cukup

---

## 🎯 FINAL RECOMMENDATION

**ROLLBACK SEMUA PERUBAHAN SESSION**

Kembalikan ke konfigurasi app_lama yang simple dan terbukti bekerja:
- Session expiration: 2 jam (7200)
- Session regeneration: 5 menit (300)
- Cookie expires: 0 (session cookie)
- No ini_set() overrides
- No session_set_cookie_params()
- No sliding expiration
- No session ID sync

**Keep Only**:
- Exam protection logic (sudah ada di app_lama)
- Heartbeat update (sudah ada di app_lama)
- Double login tolerance 10 min (improvement yang aman)

---

**Status**: READY FOR ROLLBACK ✅  
**Confidence**: 99% (Kembali ke config yang sudah terbukti bekerja)  
**Risk**: VERY LOW (Rollback to known-good state)
