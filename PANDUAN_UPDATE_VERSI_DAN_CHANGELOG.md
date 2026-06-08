# Panduan Update Versi dan Changelog

## 🎯 Ringkasan Sistem

Aplikasi SIAKAD menggunakan sistem versioning dinamis yang mengambil nomor versi dari database `changelogs`. Sistem ini memiliki perlakuan khusus untuk development (localhost) dan production.

## 📍 Lokasi File Penting

1. **Controller Changelog:** `app/Controllers/Admin/Changelogs.php`
   - Hanya bisa diakses dari `localhost` (developer privilege)
   - Auto-generate file sync setiap kali ada perubahan

2. **Helper Versi:** `app/Helpers/version_helper.php`
   - Fungsi `get_app_version()` - Ambil versi dari database
   - Fungsi `get_last_update()` - Ambil tanggal update terakhir
   - Fungsi `get_version_badge_class()` - Badge class untuk UI

3. **File Sync:** `app/Database/changelogs_sync.php`
   - Auto-generated dari database
   - Digunakan untuk sinkronisasi ke production

4. **Script Sync Manual:** `app/Database/sync_changelogs_to_file.php`
   - Untuk generate file sync secara manual jika diperlukan

## 🔐 Akses Khusus Localhost

Controller Changelogs memiliki proteksi khusus:

```php
public function __construct()
{
    // Restriction: Only accessible from localhost (Developer privilege)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalhost = (
        $host === 'localhost' || 
        strpos($host, 'localhost:') === 0 || 
        $host === '127.0.0.1' || 
        strpos($host, '127.0.0.1:') === 0
    );
    
    if (!$isLocalhost) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access Denied: This feature is only available in the development environment (localhost).');
    }
    $this->changelogModel = new ChangelogModel();
}
```

**Artinya:** Menu Changelog hanya bisa diakses di environment development (localhost dengan atau tanpa port), tidak bisa diakses di production/hosting.

**URL yang Valid:**
- ✅ `http://localhost/siakad/admin/changelogs`
- ✅ `http://localhost:8080/siakad/admin/changelogs`
- ✅ `http://127.0.0.1/siakad/admin/changelogs`
- ✅ `http://127.0.0.1:8080/siakad/admin/changelogs`
- ❌ `http://siakad-anda.com/admin/changelogs` (production)

## 📝 Cara Menambah Changelog Baru

### Langkah 1: Akses Menu Changelog

1. Pastikan Anda mengakses dari `localhost` (bukan dari hosting)
2. Login sebagai Admin
3. Buka URL: `http://localhost:8080/siakad/admin/changelogs`
4. Klik tombol **"Tambah Changelog"**

### Langkah 2: Isi Form Changelog

Form memiliki field berikut:

1. **Versi** (required)
   - Format: `X.Y.Z` (contoh: `1.1.5`, `1.2.0`)
   - Gunakan semantic versioning:
     - X = Major version (perubahan besar/breaking changes)
     - Y = Minor version (fitur baru)
     - Z = Patch version (bug fixes)

2. **Tanggal Rilis** (required)
   - Pilih tanggal rilis versi ini
   - Default: Tanggal hari ini

3. **Deskripsi Update** (required)
   - Tulis semua perubahan yang dilakukan
   - Gunakan baris baru untuk setiap item
   - Contoh format:
     ```
     Penambahan fitur mode sinkronisasi Dapodik (Skip/Update/Merge)
     Perbaikan timeout saat mengambil data siswa dari Dapodik
     Penambahan mapping 34 field data siswa dari Dapodik
     Perbaikan halaman detail siswa wali kelas
     Pembersihan file-file view yang tidak digunakan
     Update dokumentasi sinkronisasi Dapodik
     ```

4. **Status Stable** (checkbox)
   - ✅ Checked = Versi Stable (siap produksi)
   - ❌ Unchecked = Versi Beta/Development
   - Suffix `-stable` akan otomatis ditambahkan jika checked

### Langkah 3: Simpan

1. Klik **"Simpan Changelog"**
2. Sistem akan:
   - Menyimpan ke database `changelogs`
   - Auto-generate file `app/Database/changelogs_sync.php`
   - Redirect ke halaman list changelog

## 🔄 Sinkronisasi ke Production

Setelah menambah changelog di localhost, file `changelogs_sync.php` akan otomatis ter-update. File ini akan dibawa saat:

1. **Generate Patch ZIP** di menu Updater
2. **Upload ke Production**
3. **Jalankan Update File** di production
4. **Jalankan Migrasi Database** - Changelog akan otomatis masuk ke database production

### Cara Manual Sync (Jika Diperlukan)

Jika file sync tidak ter-generate otomatis, jalankan script manual:

**Via Command Line:**
```bash
php app/Database/sync_changelogs_to_file.php
```

**Via Browser (jika route dibuat):**
```
http://localhost:8080/siakad/sync-changelogs
```

## 📊 Struktur Database Changelogs

Tabel: `changelogs`

| Field | Type | Description |
|-------|------|-------------|
| id | INT | Primary key |
| version | VARCHAR(50) | Nomor versi (1.1.5) |
| release_date | DATE | Tanggal rilis |
| description | TEXT | Deskripsi perubahan |
| is_stable | TINYINT | 1=Stable, 0=Beta/Dev |
| created_at | DATETIME | Waktu dibuat |
| updated_at | DATETIME | Waktu diupdate |

## 🎨 Tampilan Versi di Aplikasi

Versi akan muncul di beberapa tempat:

1. **Footer** - Menampilkan versi dan tanggal update
2. **Halaman About** - List semua changelog
3. **Dashboard Admin** - Info versi aplikasi

Contoh tampilan:
- Versi Stable: `1.1.5-stable` (badge hijau)
- Versi Beta: `1.1.5` (badge biru)
- Versi Dev: `1.1.5-dev` (badge abu-abu)

## 📋 Contoh Changelog untuk Update Saat Ini

Berdasarkan perubahan yang telah dilakukan, berikut contoh changelog yang bisa Anda buat:

**Versi:** `1.1.5`  
**Tanggal Rilis:** `2026-04-04` (atau tanggal hari ini)  
**Status:** Stable ✅  
**Deskripsi:**
```
Penambahan 3 mode sinkronisasi data siswa dari Dapodik (Skip/Update/Merge)
Ekspansi mapping data siswa dari 6 field menjadi 34 field lengkap
Perbaikan timeout saat mengambil data siswa dari Dapodik (120 detik)
Penambahan data tempat lahir dan tanggal lahir untuk guru
Perbaikan error "Undefined array key email" di preview Dapodik
Perbaikan halaman detail siswa wali kelas (blank page issue)
Penambahan fitur deteksi kelas otomatis dari nama rombel Dapodik
Pembersihan file-file view dashboard yang tidak digunakan
Update dokumentasi lengkap sinkronisasi Dapodik
Penambahan dokumentasi mapping field Dapodik-SIAKAD
```

## 🚀 Workflow Lengkap Update Versi

### Di Localhost (Development):

1. ✅ Selesaikan semua fitur dan perbaikan
2. ✅ Test semua perubahan
3. ✅ Buka `http://localhost:8080/siakad/admin/changelogs`
4. ✅ Tambah changelog baru dengan versi yang sesuai
5. ✅ Verifikasi file `app/Database/changelogs_sync.php` ter-update
6. ✅ Commit semua perubahan ke Git (termasuk changelogs_sync.php)

### Generate Patch:

7. ✅ Buka menu Admin > Updater
8. ✅ Generate Patch ZIP
9. ✅ Download file patch

### Di Production (Hosting):

10. ✅ Upload patch ZIP
11. ✅ Jalankan "Update File"
12. ✅ Jalankan "Migrasi Database"
13. ✅ Verifikasi versi baru muncul di footer/about page

## ⚠️ Catatan Penting

1. **Jangan Edit Manual** file `changelogs_sync.php` - File ini auto-generated
2. **Selalu dari Localhost** - Menu changelog tidak bisa diakses dari production
3. **Semantic Versioning** - Ikuti aturan X.Y.Z untuk konsistensi
4. **Backup Database** - Sebelum update di production, backup database dulu
5. **Test di Localhost** - Pastikan semua fitur berjalan sebelum deploy

## 🔍 Troubleshooting

### Menu Changelog tidak bisa diakses

**Penyebab:** Diakses dari hosting/production  
**Solusi:** Akses dari `localhost` atau edit langsung database di production

### File changelogs_sync.php tidak ter-update

**Penyebab:** Error saat save atau permission issue  
**Solusi:** Jalankan script manual `php app/Database/sync_changelogs_to_file.php`

### Versi tidak muncul di production

**Penyebab:** Migrasi database belum dijalankan  
**Solusi:** Jalankan "Migrasi Database" di menu Updater production

### Error "Access Denied"

**Penyebab:** Mencoba akses dari non-localhost  
**Solusi:** Gunakan localhost atau edit database langsung via phpMyAdmin

## 📞 Bantuan

Jika ada masalah:
1. Cek log error di `writable/logs/log-[tanggal].php`
2. Verifikasi struktur database `changelogs`
3. Pastikan file `changelogs_sync.php` ada dan readable
4. Test fungsi `get_app_version()` di helper

---

**Dibuat:** 2026-04-04  
**Untuk:** SIAKAD Version Management System
