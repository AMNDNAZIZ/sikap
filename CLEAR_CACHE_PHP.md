# 🔄 CLEAR CACHE PHP - Error Masih Muncul

## Masalah
Kode sudah diperbaiki tapi error masih muncul: `Unknown column 'students.academic_year_id'`

Ini kemungkinan besar karena **PHP cache** (opcache) masih menyimpan kode lama.

---

## ✅ Solusi: Clear Cache

### Opsi 1: Restart Web Server (PALING MUDAH)

#### Untuk XAMPP:
1. Buka XAMPP Control Panel
2. Klik **Stop** pada Apache
3. Tunggu beberapa detik
4. Klik **Start** pada Apache

#### Untuk Laragon:
1. Klik **Stop All**
2. Tunggu beberapa detik
3. Klik **Start All**

#### Untuk Command Line:
```bash
# Windows (run as Administrator)
net stop Apache2.4
net start Apache2.4

# Linux
sudo service apache2 restart
# atau
sudo systemctl restart apache2
```

---

### Opsi 2: Clear OPcache via PHP Script

Buat file `clear_cache.php` di root project:

```php
<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared successfully!";
} else {
    echo "⚠️ OPcache not enabled";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "<br>✅ APC cache cleared!";
}

echo "<br><br>Cache cleared. Please refresh your application.";
```

Akses: `http://localhost:8080/siakad/clear_cache.php`

Setelah itu **hapus file** `clear_cache.php` untuk keamanan.

---

### Opsi 3: Disable OPcache Sementara

Edit `php.ini`:

```ini
; Cari baris ini dan ubah jadi 0
opcache.enable=0
```

Restart Apache, test aplikasi, lalu enable lagi:

```ini
opcache.enable=1
```

---

### Opsi 4: Clear Browser Cache

1. Tekan **Ctrl + Shift + Delete**
2. Pilih **Cached images and files**
3. Klik **Clear data**
4. Atau tekan **Ctrl + F5** untuk hard refresh

---

## 🧪 Verifikasi Fix Sudah Diterapkan

Buat file `test_query.php` di root project:

```php
<?php
require 'vendor/autoload.php';

$db = \Config\Database::connect();

// Test query yang benar
try {
    $result = $db->table('students')
        ->select('students.id, students.name')
        ->join('student_records', 'student_records.student_id = students.id')
        ->where('student_records.year_id', 1)
        ->limit(1)
        ->get()
        ->getResultArray();
    
    echo "✅ Query BENAR - Berhasil!<br>";
    echo "Result: " . json_encode($result);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";

// Test query yang salah (untuk verifikasi)
try {
    $result = $db->table('students')
        ->where('students.academic_year_id', 1) // Kolom tidak ada
        ->limit(1)
        ->get()
        ->getResultArray();
    
    echo "❌ Query SALAH tapi berhasil? Ini aneh!";
} catch (\Exception $e) {
    echo "✅ Query SALAH - Error seperti yang diharapkan:<br>";
    echo $e->getMessage();
}
```

Akses: `http://localhost:8080/siakad/test_query.php`

**Expected**:
- Query pertama: ✅ Berhasil
- Query kedua: ❌ Error (ini normal)

---

## 📝 Checklist

- [ ] Restart web server (Apache/Nginx)
- [ ] Clear browser cache (Ctrl + F5)
- [ ] Test halaman penilaian lagi
- [ ] Jika masih error, cek server log untuk melihat query yang sebenarnya dijalankan

---

## 🔍 Debug Lebih Lanjut

Jika setelah restart masih error, tolong:

1. **Cek server log** di `writable/logs/log-YYYY-MM-DD.php`
2. **Cari baris error** yang mengandung "Unknown column"
3. **Screenshot** error lengkap dengan stack trace
4. **Kirimkan** screenshot tersebut

Dengan stack trace, saya bisa tahu persis di file mana dan baris berapa error terjadi.

---

## 🚨 Jika Masih Belum Berhasil

Kemungkinan ada file lain yang belum ter-update. Tolong jalankan:

```bash
# Di terminal/command prompt, masuk ke folder project
cd C:\xampp\htdocs\siakad

# Cari semua file yang mengandung "students.academic_year_id"
grep -r "students\.academic_year_id" app/
```

Atau di Windows PowerShell:
```powershell
Get-ChildItem -Path app\ -Recurse -Include *.php | Select-String "students.academic_year_id"
```

Jika ada hasil, berarti masih ada file yang belum diperbaiki.
