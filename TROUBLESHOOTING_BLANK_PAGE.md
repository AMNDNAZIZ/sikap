# Troubleshooting: Halaman Blank Setelah Update

## Masalah
Setelah update aplikasi dan aktivasi lisensi, halaman dashboard menjadi blank (putih kosong).

## Kemungkinan Penyebab

### 1. PHP Error (Paling Umum)
Halaman blank biasanya disebabkan PHP fatal error yang tidak ditampilkan.

### 2. File Hash Tidak Ada
Sistem keamanan memerlukan file `.lic_hash` dan `.security_hash` yang mungkin tidak ter-upload saat update.

### 3. Database Migration Belum Dijalankan
Kolom `hardware_signature` di tabel `licenses` mungkin belum ada.

### 4. Permission File
File config di `app/Config/` mungkin tidak bisa dibaca/ditulis.

---

## Solusi Cepat (Quick Fix)

### Step 1: Enable Error Display
Edit file `app/Config/Boot/production.php`:

```php
// Ubah dari:
ini_set('display_errors', '0');

// Menjadi:
ini_set('display_errors', '1');
error_reporting(E_ALL);
```

Atau edit `.env`:
```
CI_ENVIRONMENT = development
```

Refresh halaman untuk melihat error message yang sebenarnya.

---

### Step 2: Generate File Hash yang Hilang

**A. Generate .lic_hash**
Jalankan di browser: `http://namadomain.com/generate_license_hash.php`

Atau via SSH:
```bash
php generate_license_hash.php
```

**B. Generate .security_hash**
Jalankan di browser: `http://namadomain.com/generate_security_hash.php`

Atau via SSH:
```bash
php generate_security_hash.php
```

---

### Step 3: Jalankan Migration Database

Via SSH:
```bash
php spark migrate
```

Atau manual, jalankan SQL ini:
```sql
ALTER TABLE licenses ADD COLUMN hardware_signature VARCHAR(255) NULL AFTER machine_id;
```

---

### Step 4: Cek Permission File

Via SSH:
```bash
chmod 644 app/Config/.lic_hash
chmod 644 app/Config/.security_hash
chmod 755 app/Config/
```

---

## Solusi Berdasarkan Error Message

### Error: "LICENSE FILE MODIFIED"

**Penyebab**: File `.lic_hash` tidak ada atau tidak valid.

**Solusi**:
1. Generate ulang `.lic_hash`:
   ```bash
   php generate_license_hash.php
   ```
2. Atau akses: `http://namadomain.com/generate_license_hash.php`

---

### Error: "SECURITY BREACH DETECTED"

**Penyebab**: File `.security_hash` tidak ada atau file kritis dimodifikasi.

**Solusi**:
1. Generate ulang `.security_hash`:
   ```bash
   php generate_security_hash.php
   ```
2. Atau akses: `http://namadomain.com/generate_security_hash.php`

---

### Error: "Column 'hardware_signature' not found"

**Penyebab**: Database migration belum dijalankan.

**Solusi**:
```bash
php spark migrate
```

Atau manual SQL:
```sql
ALTER TABLE licenses ADD COLUMN hardware_signature VARCHAR(255) NULL AFTER machine_id;
```

---

### Error: "Call to undefined function generate_hardware_signature()"

**Penyebab**: Helper `license_helper.php` tidak ter-load atau tidak ter-upload.

**Solusi**:
1. Pastikan file `app/Helpers/license_helper.php` ada
2. Cek isi file, pastikan fungsi `generate_hardware_signature()` ada
3. Re-upload file jika perlu

---

### Error: "Class 'App\Libraries\LicenseGuard' not found"

**Penyebab**: File `LicenseGuard.php` tidak ter-upload.

**Solusi**:
1. Pastikan file `app/Libraries/LicenseGuard.php` ada
2. Re-upload file jika perlu
3. Cek permission: `chmod 644 app/Libraries/LicenseGuard.php`

---

### Error: "Class 'App\Libraries\SecurityGuard' not found"

**Penyebab**: File `SecurityGuard.php` tidak ter-upload.

**Solusi**:
1. Pastikan file `app/Libraries/SecurityGuard.php` ada
2. Re-upload file jika perlu
3. Cek permission: `chmod 644 app/Libraries/SecurityGuard.php`

---

## Bypass Sementara (Emergency)

Jika perlu akses dashboard segera untuk troubleshooting, bypass sementara sistem keamanan:

### Edit `app/Controllers/BaseController.php`

```php
public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
{
    // TEMPORARY BYPASS - REMOVE AFTER FIXING
    // \App\Libraries\LicenseGuard::verify();
    
    log_message('debug', '--- BASE CONTROLLER INIT: ' . $request->getUri()->getPath() . ' ---');
    parent::initController($request, $response, $logger);

    $schoolModel = new \App\Models\SchoolModel();
    $school = $schoolModel->first() ?: [];
    service('renderer')->setData(['school' => $school]);
}
```

**PENTING**: Jangan lupa aktifkan kembali setelah masalah selesai!

---

## Cek Log Error

### Via File Manager
Buka file: `writable/logs/log-[tanggal].php`

Cari error message di bagian bawah file.

### Via SSH
```bash
tail -n 100 writable/logs/log-$(date +%Y-%m-%d).php
```

---

## Checklist Troubleshooting

- [ ] Enable error display (development mode)
- [ ] Cek error message yang muncul
- [ ] Generate `.lic_hash` jika tidak ada
- [ ] Generate `.security_hash` jika tidak ada
- [ ] Jalankan database migration
- [ ] Cek permission file (644 untuk file, 755 untuk folder)
- [ ] Cek log error di `writable/logs/`
- [ ] Pastikan semua file ter-upload dengan benar
- [ ] Cek koneksi database
- [ ] Clear cache: `php spark cache:clear`

---

## File yang Harus Ada Setelah Update

### Libraries
- [ ] `app/Libraries/LicenseGuard.php`
- [ ] `app/Libraries/SecurityGuard.php`
- [ ] `app/Libraries/SecureConfig.php`

### Helpers
- [ ] `app/Helpers/license_helper.php`
- [ ] `app/Helpers/security_helper.php`

### Config Files
- [ ] `app/Config/.lic_hash`
- [ ] `app/Config/.security_hash`

### Controllers
- [ ] `app/Controllers/Dashboard.php` (updated)
- [ ] `app/Controllers/Activate.php`

### Views
- [ ] `app/Views/dashboard/admin_modern.php`
- [ ] `app/Views/dashboard/siswa_modern.php`
- [ ] `app/Views/dashboard/guru_modern.php`
- [ ] `app/Views/auth/login_modern.php`

### Migrations
- [ ] `app/Database/Migrations/2026-02-25-000001_AddHardwareSignatureToLicense.php`

### Root Files
- [ ] `generate_license_hash.php`
- [ ] `generate_security_hash.php`
- [ ] `verify_security_hash.php`

---

## Rollback Dashboard Modern (Jika Perlu)

Jika masalah ada di dashboard modern, rollback ke versi lama:

Edit `app/Controllers/Dashboard.php`:

```php
// Ubah dari:
return view('dashboard/admin_modern', [...]);
return view('dashboard/siswa_modern', [...]);
return view('dashboard/guru_modern', [...]);

// Kembali ke:
return view('dashboard/admin', [...]);
return view('dashboard/siswa', [...]);
return view('dashboard/guru', [...]);
```

---

## Kontak Support

Jika masalah masih berlanjut setelah semua langkah di atas:

1. Screenshot error message (setelah enable error display)
2. Copy isi log error terbaru
3. Informasi environment:
   - PHP version: `php -v`
   - CodeIgniter version
   - Database version
   - Server OS

---

## Prevention (Untuk Update Berikutnya)

1. **Backup dulu** sebelum update
2. **Test di staging** sebelum production
3. **Jalankan migration** setelah upload file
4. **Generate hash** setelah upload file
5. **Cek error log** setelah update
6. **Test semua role** (admin, guru, siswa)

---

**Last Updated**: 25 Februari 2026
