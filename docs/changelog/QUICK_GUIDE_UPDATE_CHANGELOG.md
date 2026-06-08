# Quick Guide: Update Changelog Otomatis

Panduan singkat untuk memastikan changelog terupdate saat rilis versi baru.

## 🎯 Masalah yang Diperbaiki

Sebelumnya, changelog di production tidak ikut terupdate saat update file karena file `changelogs_sync.php` tidak diupdate.

## ✅ Solusi Otomatis (Sudah Diperbaiki!)

Sekarang sistem sudah otomatis! Saat Anda generate patch ZIP, changelog akan otomatis di-sync.

---

## 📋 Workflow Update Versi Baru

### Di Development (Localhost):

#### 1. Tambah Changelog di Database

Via phpMyAdmin atau aplikasi:

```sql
INSERT INTO changelogs (version, release_date, description, is_stable, created_at, updated_at)
VALUES (
    '1.2.0',
    '2026-02-21',
    'Perbaikan bug hosting:
- Tambah tabel ci_sessions untuk session handler
- Perbaiki error shell_exec() di sistem lisensi
- Update migrasi database
',
    1,
    NOW(),
    NOW()
);
```

Atau via halaman admin (jika ada form changelog).

#### 2. Generate Patch ZIP

1. Buka: `http://localhost/siakad/admin/updater`
2. Klik tombol **"⬇️ Download Zip"**
3. Sistem akan **OTOMATIS**:
   - ✅ Sync changelog dari database ke file `changelogs_sync.php`
   - ✅ Update `LAST_UPDATE` di Constants.php
   - ✅ Generate ZIP dengan semua file terbaru

#### 3. Download ZIP

File ZIP sudah include:
- ✅ File `changelogs_sync.php` yang terupdate
- ✅ Semua file aplikasi terbaru
- ✅ Migrasi database (jika ada)

---

### Di Production (Hosting):

#### 1. Backup Database

Wajib backup sebelum update!

#### 2. Upload & Update File

1. Buka: `https://domain.com/admin/updater`
2. Upload file ZIP
3. Klik **"📁 Update File"**
4. File akan di-extract otomatis

#### 3. Jalankan Migrasi

1. Klik **"⚡ Jalankan Migrasi"**
2. Sistem akan **OTOMATIS**:
   - ✅ Jalankan migrasi database
   - ✅ Sync changelog dari file ke database
   - ✅ Update tabel changelogs

#### 4. Verifikasi

1. Cek footer aplikasi - versi harus terupdate
2. Buka halaman "Tentang Aplikasi" - changelog harus muncul
3. Test aplikasi - pastikan berjalan normal

---

## 🔄 Alur Otomatis

```
Development:
1. Tambah changelog di database
2. Generate patch ZIP
   └─> Otomatis sync changelog ke file ✅

Production:
1. Upload & update file
2. Jalankan migrasi
   └─> Otomatis sync changelog ke database ✅
```

---

## 🛠️ Manual Sync (Jika Diperlukan)

### Opsi 1: Via Script CLI

```bash
cd /path/to/siakad
php app/Database/sync_changelogs_to_file.php
```

### Opsi 2: Via Browser

Buat route di `app/Config/Routes.php`:

```php
$routes->get('sync-changelogs', function() {
    require APPPATH . 'Database/sync_changelogs_to_file.php';
});
```

Akses: `http://localhost/siakad/sync-changelogs`

---

## 📝 Template Changelog

Format yang baik:

```
Versi X.Y.Z - Judul Update

Fitur Baru:
- Fitur A
- Fitur B

Perbaikan Bug:
- Bug A diperbaiki
- Bug B diperbaiki

Peningkatan:
- Peningkatan A
- Peningkatan B
```

Contoh:

```
Versi 1.2.0 - Perbaikan Hosting & Migrasi Database

Perbaikan Bug:
- Tambah tabel ci_sessions untuk session handler
- Perbaiki error shell_exec() di sistem lisensi
- Perbaiki bug model StudentAcademicRecordModel

Fitur Baru:
- Sistem migrasi database otomatis
- Auto-sync changelog saat generate patch

Peningkatan:
- Dokumentasi lengkap migrasi
- Optimasi query database
```

---

## ✅ Checklist Update

### Development:
- [ ] Tambah changelog di database
- [ ] Generate patch ZIP (otomatis sync!)
- [ ] Test di localhost
- [ ] Verifikasi changelog muncul

### Production:
- [ ] Backup database
- [ ] Upload patch ZIP
- [ ] Update file
- [ ] Jalankan migrasi (otomatis sync!)
- [ ] Verifikasi changelog muncul
- [ ] Test aplikasi

---

## 🐛 Troubleshooting

### Changelog Tidak Muncul di Production

**Cek:**
```sql
SELECT * FROM changelogs ORDER BY release_date DESC LIMIT 5;
```

**Jika kosong:**
1. Cek apakah file `app/Database/changelogs_sync.php` ada di ZIP
2. Cek apakah sudah jalankan "Jalankan Migrasi"
3. Cek error log di `writable/logs/`

**Solusi Cepat:**
```sql
-- Tambah manual di production
INSERT INTO changelogs (version, release_date, description, is_stable, created_at, updated_at)
VALUES ('1.2.0', '2026-02-21', 'Update terbaru', 1, NOW(), NOW());
```

### File changelogs_sync.php Tidak Terupdate

**Cek:**
```bash
cat app/Database/changelogs_sync.php
```

**Solusi:**
Jalankan manual sync:
```bash
php app/Database/sync_changelogs_to_file.php
```

---

## 🎉 Kesimpulan

Dengan sistem baru ini:
- ✅ Changelog otomatis sync saat generate patch
- ✅ Tidak perlu manual update file
- ✅ Changelog selalu konsisten antara dev & production
- ✅ Proses update lebih cepat dan aman

**Cukup:**
1. Tambah changelog di database development
2. Generate patch ZIP
3. Upload & update di production
4. Selesai! 🎉

---

**Dibuat:** 21 Februari 2026  
**Versi:** 1.0  
**Status:** ✅ Production Ready
