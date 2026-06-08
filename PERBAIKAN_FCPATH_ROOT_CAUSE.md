# Perbaikan Root Cause: Upload File ke Folder Salah

**Tanggal**: 19 April 2026  
**Status**: ✅ FIXED  
**Severity**: CRITICAL

## 🔍 Root Cause Analysis

### Masalah
Upload dokumen guru membuat folder `uploads/teacher_docs/` di **root folder** padahal seharusnya di `public/uploads/teacher_docs/`.

### Penyebab Utama
Ada **DUA file `index.php`** yang menyebabkan konflik:

1. **`public/index.php`** (BENAR) ✅
   - Mendefinisikan: `define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);`
   - `__DIR__` = `/path/to/siakad/public/`
   - `FCPATH` = `/path/to/siakad/public/`

2. **`index.php` di root** (SALAH) ❌
   - Mendefinisikan: `define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);`
   - `__DIR__` = `/path/to/siakad/`
   - `FCPATH` = `/path/to/siakad/` (ROOT!)

### Routing Issue
File `.htaccess` di root mengarahkan semua request ke `index.php` **di root**, bukan ke `public/index.php`:

```apache
RewriteRule ^([\s\S]*)$ index.php/$1 [L,NC,QSA]
```

Ini menyebabkan:
- Request → `index.php` (root)
- `FCPATH` = root folder
- Upload path = `FCPATH . 'uploads/teacher_docs/'` = `root/uploads/teacher_docs/` ❌

## ✅ Solusi yang Diterapkan

### 1. Ubah `index.php` di Root Menjadi Proxy

**File**: `index.php` (di root)

**Sebelum**:
```php
<?php
use CodeIgniter\Boot;
use Config\Paths;

// ... PHP version check ...

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR); // ❌ Mengarah ke root!

// ... bootstrap code ...
```

**Sesudah**:
```php
<?php
/*
 * ROOT INDEX.PHP - PROXY TO PUBLIC/INDEX.PHP
 * 
 * This file acts as a proxy to redirect all requests to the
 * actual front controller in the public/ directory.
 */

// Redirect to the actual front controller in public/ directory
require __DIR__ . '/public/index.php';
```

**Hasil**: Sekarang `index.php` di root hanya berfungsi sebagai proxy yang mengarahkan ke `public/index.php`, memastikan `FCPATH` selalu mengarah ke `public/`.

### 2. Perbaiki File Utility Scripts

File-file berikut juga mendefinisikan `FCPATH` dengan nilai salah (mengarah ke root):

#### a. `apply_additional_improvements.php`
```php
// Sebelum
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR); // ❌

// Sesudah
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR); // ✅
```

#### b. `apply_performance_improvements.php`
```php
// Sebelum
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR); // ❌

// Sesudah
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR); // ✅
```

#### c. `fix_upload_soal_images.php`
```php
// Sebelum
define('FCPATH', __DIR__ . '/'); // ❌
define('UPLOAD_PATH', FCPATH . 'public/uploads/'); // Jadi root/public/uploads/ ❌

// Sesudah
define('ROOTPATH', __DIR__ . '/');
define('FCPATH', ROOTPATH . 'public/'); // ✅
define('UPLOAD_PATH', FCPATH . 'uploads/'); // Jadi public/uploads/ ✅
```

#### d. `test_slider_upload.php`
```php
// Sebelum
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR); // ❌

// Sesudah
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR); // ✅
```

#### e. `verify_production_ready.php`
```php
// Sebelum
define('FCPATH', __DIR__ . '/'); // ❌

// Sesudah
define('ROOTPATH', __DIR__ . '/');
define('FCPATH', ROOTPATH . 'public/'); // ✅
```

## 🧪 Verifikasi

### Sebelum Fix
```
Request → index.php (root)
FCPATH = /path/to/siakad/
Upload path = /path/to/siakad/uploads/teacher_docs/ ❌
```

### Sesudah Fix
```
Request → index.php (root) → public/index.php (proxy)
FCPATH = /path/to/siakad/public/
Upload path = /path/to/siakad/public/uploads/teacher_docs/ ✅
```

## 📋 Testing Checklist

- [ ] Test upload dokumen dari Admin (Teachers controller)
- [ ] Test upload dokumen dari Guru (Profile controller)
- [ ] Verifikasi file masuk ke `public/uploads/teacher_docs/`
- [ ] Verifikasi TIDAK ada file baru di `uploads/teacher_docs/` (root)
- [ ] Test preview dokumen (PDF, Image, Word, Excel)
- [ ] Test download dokumen
- [ ] Test delete dokumen

## 🗂️ File Structure

```
siakad/
├── index.php                          ← PROXY (fixed)
├── public/
│   ├── index.php                      ← ACTUAL FRONT CONTROLLER
│   └── uploads/
│       └── teacher_docs/              ← CORRECT LOCATION ✅
├── uploads/                           ← OLD LOCATION (akan dihapus)
│   └── teacher_docs/                  ← WRONG LOCATION ❌
├── uploads_OLD_BACKUP_20260419/       ← BACKUP (jangan dihapus dulu)
└── app/
    └── Controllers/
        ├── Admin/Teachers.php         ← Upload method (sudah benar)
        └── Profile.php                ← Upload method (sudah benar)
```

## 🔧 Next Steps

1. **Test Upload** (URGENT)
   - Login sebagai Admin
   - Edit guru → Tab "Arsip Dokumen"
   - Upload file baru
   - Verifikasi file masuk ke `public/uploads/teacher_docs/`

2. **Test dari Guru**
   - Login sebagai Guru
   - Profile → Tab "Arsip Dokumen"
   - Upload file baru
   - Verifikasi file masuk ke `public/uploads/teacher_docs/`

3. **Monitoring**
   - Monitor folder `uploads/teacher_docs/` di root
   - Jika TIDAK ada file baru setelah 1-2 hari → Fix berhasil
   - Jika masih ada file baru → Ada masalah lain

4. **Cleanup** (setelah 1-2 minggu)
   - Hapus folder `uploads/teacher_docs/` di root
   - Hapus folder `uploads_OLD_BACKUP_20260419/` (jika yakin semua OK)

## 📝 Catatan Penting

### Kenapa Tidak Langsung Hapus Folder Lama?
- Untuk memastikan tidak ada file yang hilang
- Untuk monitoring apakah fix berhasil
- Untuk rollback jika ada masalah

### Kenapa Tidak Ubah .htaccess?
- Mengubah `.htaccess` bisa break routing yang sudah ada
- Solusi proxy lebih aman dan backward compatible
- Tidak perlu mengubah konfigurasi web server

### File Lain yang Aman
File-file berikut sudah benar (tidak perlu diubah):
- `public/index.php` ✅
- `preload.php` ✅
- `spark` ✅
- `app/Config/Constants.php` ✅
- `app/Controllers/Admin/Teachers.php` ✅
- `app/Controllers/Profile.php` ✅

## 🎯 Expected Result

Setelah fix ini:
1. ✅ Semua upload file akan masuk ke `public/uploads/`
2. ✅ `FCPATH` selalu mengarah ke `public/` folder
3. ✅ Tidak ada file baru di `uploads/` (root)
4. ✅ Preview dokumen berfungsi normal
5. ✅ Download dokumen berfungsi normal

## 🔒 Security Impact

**POSITIVE**: Fix ini meningkatkan keamanan karena:
- File upload berada di `public/` folder yang seharusnya
- Konsisten dengan best practice CodeIgniter 4
- Memudahkan konfigurasi web server untuk block direct access ke file sensitif

---

**Status**: ✅ FIXED  
**Tested**: ⏳ PENDING USER TESTING  
**Approved**: ⏳ PENDING
