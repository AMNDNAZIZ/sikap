# Cara Mengatasi License Error

## 🔴 Error yang Muncul:
```
LICENSE ERROR
LICENSE FILE MODIFIED
SakaSalika Anti-Tamper System
```

---

## 🎯 Penyebab Masalah:

Sistem lisensi memeriksa integritas file dengan checksum (hash). Ketika Anda:
1. Memodifikasi file aplikasi (seperti yang kita lakukan)
2. Install di hosting baru
3. Mengosongkan database

File `.lic_hash` tidak cocok lagi dengan file yang ada, sehingga sistem mendeteksi sebagai "tampered" (dimodifikasi).

---

## ✅ Solusi 1: Via Controller (PALING MUDAH) ⭐ RECOMMENDED

### Langkah 1: Upload File
Upload 3 file ini ke hosting:
- `app/Controllers/FixLicense.php`
- `app/Views/fix_license_view.php`
- `app/Views/fix_license_delete.php`

### Langkah 2: Update Routes
Edit `app/Config/Routes.php`, tambahkan di baris awal (setelah `$routes->get('/', ...)`):
```php
// FIX LICENSE TOOL (TEMPORARY)
$routes->get('fixlicense', 'FixLicense::index');
$routes->get('fixlicense/delete', 'FixLicense::delete');
```

### Langkah 3: Update LicenseGuard
Edit `app/Libraries/LicenseGuard.php`, ubah baris skip menjadi:
```php
$skip = ['activate', 'login', 'assets', 'favicon.ico', 'cbt/csrf/refresh', 'fixlicense'];
```

### Langkah 4: Akses Tool
Buka browser dan akses:
```
http://your-domain.com/fixlicense
```
atau
```
http://your-domain.com/index.php/fixlicense
```

### Langkah 5: Hapus Tool
Setelah selesai, klik tombol "🗑️ Hapus Controller Ini Sekarang" atau hapus manual:
- `app/Controllers/FixLicense.php`
- `app/Views/fix_license_view.php`
- `app/Views/fix_license_delete.php`

Dan hapus route di `app/Config/Routes.php`.

---

## ✅ Solusi 2: Update Hash File (Via SSH/Terminal)

### Langkah 1: Akses Terminal/SSH
```bash
# Via SSH
ssh user@your-hosting.com

# Atau via cPanel Terminal
# Buka cPanel → Terminal
```

### Langkah 2: Masuk ke Direktori Aplikasi
```bash
cd /path/to/cbtku
# Contoh: cd public_html/cbtku
```

### Langkah 3: Jalankan Command Fix Hash
```bash
php spark license:hash
```

**Output yang diharapkan:**
```
License Hash Update Tool
========================

Generating hashes for protected files...

✓ Filters/LicenseFilter.php
  Hash: 46be930231eedb85b18f8094f65823aac9f2f60b
✓ Models/LicenseModel.php
  Hash: f569f7608b91d02bb121ff6a66ac724378b94733
...

Hash file updated: /path/to/app/Config/.lic_hash
Total files: 11

✓ Hash update completed successfully!
```

### Langkah 4: Fix Checksum (Jika Perlu)
```bash
php spark license:fix-checksum
```

### Langkah 5: Clear Cache
```bash
# Clear cache aplikasi
rm -rf writable/cache/*

# Atau via PHP
php spark cache:clear
```

### Langkah 6: Test Aplikasi
Buka browser dan akses aplikasi. Seharusnya sudah tidak ada error lagi.

---

## ✅ Solusi 2: Via Script PHP (Jika Tidak Ada Akses SSH)

### Buat File: `fix_license.php` di Root Folder

```php
<?php
// fix_license.php
// Upload file ini ke root folder (sejajar dengan index.php)

define('APPPATH', __DIR__ . '/app/');

// File-file yang perlu di-hash
$protectedFiles = [
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
    'Controllers/Activate.php',
    'Controllers/BaseController.php',
    'Controllers/Admin/License.php',
    'Libraries/LicenseGuard.php',
    'Libraries/LicenseProtector.php',
    'Helpers/license_helper.php',
    'Config/Filters.php',
    'Config/LService.php',
    'Config/Events.php'
];

echo "<h2>License Hash Update Tool</h2>";
echo "<hr>";

$hashFile = APPPATH . 'Config/.lic_hash';
$hashes = [];
$errors = [];

echo "<h3>Generating hashes...</h3>";
echo "<ul>";

foreach ($protectedFiles as $file) {
    $fullPath = APPPATH . $file;
    
    if (file_exists($fullPath)) {
        $hash = sha1_file($fullPath);
        $hashes[$file] = $hash;
        echo "<li style='color:green'>✓ $file<br><small style='color:gray'>Hash: $hash</small></li>";
    } else {
        $errors[] = $file;
        echo "<li style='color:red'>✗ $file (NOT FOUND)</li>";
    }
}

echo "</ul>";

if (!empty($errors)) {
    echo "<h4 style='color:orange'>Warning: Some files were not found:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color:orange'>$error</li>";
    }
    echo "</ul>";
}

// Save to file
$json = json_encode($hashes, JSON_PRETTY_PRINT);
$result = file_put_contents($hashFile, $json);

if ($result !== false) {
    echo "<h3 style='color:green'>✓ Hash file updated successfully!</h3>";
    echo "<p>File: <code>$hashFile</code></p>";
    echo "<p>Total files: " . count($hashes) . "</p>";
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Delete this file (<code>fix_license.php</code>) for security</li>";
    echo "<li>Clear browser cache</li>";
    echo "<li>Try accessing the application again</li>";
    echo "</ol>";
} else {
    echo "<h3 style='color:red'>✗ Failed to update hash file!</h3>";
    echo "<p>Please check file permissions for: <code>$hashFile</code></p>";
    echo "<p>The file must be writable (chmod 644 or 666)</p>";
}

echo "<hr>";
echo "<p><strong>IMPORTANT:</strong> Delete this file after use for security reasons!</p>";
?>
```

### Cara Pakai:
1. Copy kode di atas
2. Buat file `fix_license.php` di root folder (sejajar dengan `index.php`)
3. Upload ke hosting
4. Akses via browser: `http://your-domain.com/fix_license.php`
5. Tunggu sampai selesai
6. **HAPUS file `fix_license.php` setelah selesai!**

---

## ✅ Solusi 3: Manual Update Hash File

### Langkah 1: Download File yang Dimodifikasi
Download file-file ini dari localhost Anda:
- `app/Controllers/Admin/Teachers.php`
- `app/Controllers/Admin/CbtBankSoal.php`
- `app/Models/CbtQuestionModel.php`
- `app/Controllers/Siswa/Cbt.php`
- `app/Views/admin/teachers/index.php`
- `app/Views/siswa/cbt/peraturan.php`
- `app/Config/Routes.php`

### Langkah 2: Generate Hash untuk Setiap File
```php
<?php
// generate_hash.php
$file = 'path/to/file.php';
echo sha1_file($file);
?>
```

### Langkah 3: Update `.lic_hash` Manual
Edit file `app/Config/.lic_hash` dan update hash untuk file yang dimodifikasi.

**Contoh:**
```json
{
    "Controllers/Admin/Teachers.php": "NEW_HASH_HERE",
    "Controllers/Siswa/Cbt.php": "NEW_HASH_HERE",
    ...
}
```

---

## ✅ Solusi 4: Disable License Check (Development Only)

⚠️ **HANYA UNTUK DEVELOPMENT/TESTING! JANGAN DI PRODUCTION!**

### Edit `app/Libraries/LicenseGuard.php`

Tambahkan di awal method `verify()`:

```php
public static function verify()
{
    // 🔧 TEMPORARY: Skip license check for development
    if (ENVIRONMENT === 'development') {
        return;
    }
    
    // ... kode lainnya ...
}
```

---

## 🔍 Troubleshooting

### Error: "Permission Denied"
```bash
# Set permission untuk file hash
chmod 666 app/Config/.lic_hash

# Atau untuk seluruh folder Config
chmod -R 755 app/Config
```

### Error: "Command not found: php"
Coba gunakan path lengkap PHP:
```bash
/usr/bin/php spark license:hash
# atau
/usr/local/bin/php spark license:hash
```

### Error: "License not found in database"
1. Pastikan database sudah di-import
2. Pastikan tabel `licenses` ada dan berisi data
3. Jalankan aktivasi ulang di `/activate`

---

## 📋 Checklist Setelah Fix

- [ ] Hash file berhasil di-update
- [ ] Tidak ada error "LICENSE FILE MODIFIED"
- [ ] Aplikasi bisa diakses normal
- [ ] File `fix_license.php` sudah dihapus (jika pakai Solusi 2)
- [ ] Cache sudah di-clear

---

## 🎯 Rekomendasi

### Untuk Instalasi Baru:
1. Upload semua file aplikasi
2. Import database
3. Jalankan `php spark license:hash`
4. Jalankan `php spark license:fix-checksum`
5. Akses `/activate` untuk aktivasi

### Untuk Update Aplikasi:
1. Backup file lama
2. Upload file baru
3. Jalankan `php spark license:hash`
4. Clear cache
5. Test aplikasi

---

## ⚠️ Catatan Penting

1. **Jangan edit file lisensi** (`LicenseGuard.php`, `LicenseProtector.php`, dll) kecuali tahu apa yang dilakukan
2. **Backup file `.lic_hash`** sebelum update
3. **Hapus `fix_license.php`** setelah digunakan untuk keamanan
4. **Jangan disable license check** di production

---

## 📞 Jika Masih Error

Jika masih ada error setelah semua solusi di atas:

1. Cek log error di `writable/logs/`
2. Pastikan semua file lisensi ada dan tidak corrupt
3. Coba restore dari backup
4. Hubungi developer aplikasi

---

Selesai! Pilih solusi yang paling sesuai dengan akses yang Anda punya. 🎉
