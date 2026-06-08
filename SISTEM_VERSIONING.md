# Sistem Versioning SIAKAD

## 📌 Overview

SIAKAD menggunakan sistem versioning dinamis yang mengambil nomor versi dari database `changelogs` table, bukan dari konstanta hardcoded.

## 🔢 Format Versi

### Semantic Versioning: `X.Y.Z`

- **X** = Major Version (Perubahan besar/breaking changes)
- **Y** = Minor Version (Fitur baru, backward compatible)
- **Z** = Patch Version (Bug fixes, perbaikan kecil)

### Suffix Status

- `-stable` = Versi stabil, siap produksi
- `-beta` = Versi beta, masih testing
- `-alpha` = Versi alpha, early development
- `-dev` = Versi development

**Contoh:**
- `1.1.5-stable` = Versi 1.1.5 yang sudah stable
- `1.2.0-beta` = Versi 1.2.0 yang masih beta
- `1.1.5` = Versi 1.1.5 tanpa suffix (default)

## 🏗️ Arsitektur Sistem

### 1. Database (Source of Truth)

Tabel `changelogs` menyimpan semua versi:

```sql
CREATE TABLE `changelogs` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(50) NOT NULL,
  `release_date` date NOT NULL,
  `description` text NOT NULL,
  `is_stable` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);
```

### 2. Helper Functions

File: `app/Helpers/version_helper.php`

**Fungsi Utama:**

```php
// Ambil versi terbaru dari database
get_app_version(): string

// Ambil tanggal update terakhir
get_last_update(): string

// Ambil class badge untuk UI
get_version_badge_class(string $version): string
```

**Cara Kerja:**
1. Query database `changelogs` table
2. Order by `release_date DESC` dan `id DESC`
3. Ambil record paling atas (versi terbaru)
4. Tambahkan suffix `-stable` jika `is_stable = 1`
5. Cache hasil untuk performa

### 3. Sync File (Production Bridge)

File: `app/Database/changelogs_sync.php`

**Fungsi:**
- Auto-generated dari database saat save changelog
- Dibawa ke production via patch ZIP
- Digunakan untuk sync changelog ke database production

**Format:**
```php
<?php
return array (
  0 => array (
    'id' => '5',
    'version' => '1.1.5',
    'release_date' => '2026-04-04',
    'description' => '...',
    'is_stable' => '1',
    ...
  ),
  ...
);
```

## 🔄 Flow Versioning

### Development (Localhost)

```
Developer
  ↓
Tambah Changelog via UI
  ↓
Save ke Database
  ↓
Auto-generate changelogs_sync.php
  ↓
Commit ke Git
```

### Production (Hosting)

```
Upload Patch ZIP
  ↓
Extract Files (termasuk changelogs_sync.php)
  ↓
Jalankan Migrasi Database
  ↓
Sync changelogs_sync.php → Database
  ↓
Versi Baru Muncul di Aplikasi
```

## 📍 Lokasi Tampilan Versi

### 1. Footer (Semua Halaman)

```php
<?= get_app_version() ?>
```

Tampil: `v1.1.5-stable`

### 2. Halaman About

```php
<?php
$changelogModel = new ChangelogModel();
$changelogs = $changelogModel->orderBy('release_date', 'DESC')->findAll();
?>
```

Tampil: List semua changelog dengan detail

### 3. Dashboard Admin

```php
<div class="version-info">
    Versi: <?= get_app_version() ?>
    Update: <?= get_last_update() ?>
</div>
```

## 🎨 Badge Styling

Helper `get_version_badge_class()` menentukan warna badge:

| Suffix | Class | Warna | Keterangan |
|--------|-------|-------|------------|
| `-stable` | `badge bg-success` | Hijau | Siap produksi |
| `-beta` | `badge bg-warning text-dark` | Kuning | Testing |
| `-alpha` | `badge bg-danger` | Merah | Early dev |
| `-dev` | `badge bg-secondary` | Abu-abu | Development |
| (none) | `badge bg-primary` | Biru | Default |

## 🔐 Security & Access Control

### Localhost Only Access

Controller `Changelogs` memiliki proteksi:

```php
public function __construct()
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalhost = (
        $host === 'localhost' || 
        strpos($host, 'localhost:') === 0 || 
        $host === '127.0.0.1' || 
        strpos($host, '127.0.0.1:') === 0
    );
    
    if (!$isLocalhost) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access Denied...');
    }
    ...
}
```

**Alasan:**
- Mencegah user production mengedit changelog
- Changelog hanya dikelola di development
- Production hanya menerima sync dari development

**URL yang Valid:**
- ✅ `localhost` atau `localhost:port`
- ✅ `127.0.0.1` atau `127.0.0.1:port`
- ❌ Domain production

### Production Access

Di production, changelog:
- ✅ Bisa dilihat (halaman About)
- ❌ Tidak bisa diedit
- ✅ Otomatis sync dari file changelogs_sync.php

## 📊 Contoh Riwayat Versi

| Versi | Tanggal | Status | Deskripsi |
|-------|---------|--------|-----------|
| 1.1.5 | 2026-04-04 | Stable | Mode sinkronisasi Dapodik |
| 1.1.4 | 2026-02-20 | Stable | Optimasi CBT, CSRF protection |
| 1.1.3 | 2026-02-20 | Beta | Lisensi langganan, CBT features |
| 1.1.2 | 2026-02-19 | Beta | Perbaikan agenda, dashboard |
| 1.1.1 | 2026-02-10 | Beta | Pembobotan nilai, impor guru |
| 1.1.0 | 2026-02-05 | Beta | PWA, security enhancement |

## 🔄 Update Strategy

### Kapan Increment Versi?

**Major (X):**
- Perubahan arsitektur besar
- Breaking changes
- Migrasi database major
- Contoh: 1.x.x → 2.0.0

**Minor (Y):**
- Fitur baru
- Penambahan modul
- Enhancement signifikan
- Contoh: 1.1.x → 1.2.0

**Patch (Z):**
- Bug fixes
- Perbaikan kecil
- Optimasi
- Contoh: 1.1.4 → 1.1.5

### Kapan Mark as Stable?

✅ Mark as Stable jika:
- Semua fitur sudah di-test
- Tidak ada critical bug
- Dokumentasi lengkap
- Siap deploy ke production

❌ Jangan mark as Stable jika:
- Masih ada bug yang diketahui
- Fitur belum selesai
- Belum di-test lengkap
- Masih experimental

## 🛠️ Maintenance

### Backup Changelog

Changelog tersimpan di 3 tempat:
1. Database `changelogs` table
2. File `app/Database/changelogs_sync.php`
3. Git repository

### Restore Changelog

Jika database hilang:
1. Restore dari backup database, atau
2. Jalankan migrasi untuk re-sync dari changelogs_sync.php

### Clean Old Versions

Tidak disarankan menghapus changelog lama karena:
- History penting untuk tracking
- Ukuran data kecil
- Berguna untuk audit

## 📈 Best Practices

1. **Konsisten:** Selalu gunakan format X.Y.Z
2. **Deskriptif:** Tulis changelog yang jelas dan detail
3. **Teratur:** Update versi setiap ada perubahan signifikan
4. **Testing:** Test sebelum mark as stable
5. **Dokumentasi:** Update dokumentasi seiring update versi
6. **Backup:** Selalu backup sebelum update production

## 🔍 Monitoring

### Check Current Version

**Via Code:**
```php
$version = get_app_version();
$lastUpdate = get_last_update();
```

**Via Database:**
```sql
SELECT version, release_date, is_stable 
FROM changelogs 
ORDER BY release_date DESC, id DESC 
LIMIT 1;
```

**Via File:**
```php
$changelogs = include APPPATH . 'Database/changelogs_sync.php';
$latest = $changelogs[0];
```

## 📞 Support

Jika ada masalah dengan versioning:
1. Cek database `changelogs` table
2. Cek file `changelogs_sync.php`
3. Cek log error di `writable/logs/`
4. Verifikasi helper function `get_app_version()`

---

**Dokumentasi Sistem Versioning SIAKAD**  
**Last Updated:** 2026-04-04  
**Version:** 1.0
