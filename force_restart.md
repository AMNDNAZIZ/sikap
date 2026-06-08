# 🔥 FORCE RESTART - Cache Sangat Persistent

Error `students.academic_year_id` terus muncul padahal kode sudah benar. Ini masalah cache yang sangat persistent.

## ✅ SOLUSI PAKSA

### STEP 1: Stop Semua Service
1. Buka **XAMPP Control Panel**
2. Klik **Stop** pada **Apache**
3. Klik **Stop** pada **MySQL** (jika running)
4. **Tutup XAMPP Control Panel**

### STEP 2: Hapus Cache Folder
Buka Windows Explorer, hapus folder-folder ini:

```
C:\xampp\htdocs\siakad\writable\cache\*
C:\xampp\htdocs\siakad\writable\session\*
C:\xampp\tmp\*
```

**JANGAN HAPUS folder-nya**, hanya **isi di dalamnya** (kecuali file `index.html`).

### STEP 3: Restart Komputer (PENTING!)
Ya, restart komputer Anda. Ini akan clear semua memory cache.

### STEP 4: Start Ulang XAMPP
1. Buka **XAMPP Control Panel**
2. Klik **Start** pada **Apache**
3. Klik **Start** pada **MySQL**

### STEP 5: Test dengan Browser Baru
1. Buka **Incognito/Private mode** (Ctrl + Shift + N)
2. Login ke aplikasi
3. Test penilaian

---

## 🔍 ALTERNATIF: Cek File yang Ter-load

Jika masih error setelah restart, kemungkinan ada file duplikat atau backup yang ter-load.

Buat file `debug_loaded_files.php` di root:

```php
<?php
// Jalankan aplikasi dulu, lalu akses file ini
require 'vendor/autoload.php';

// Cek file Kokurikuler.php yang ter-load
$loadedFiles = get_included_files();
$kokurikulerFiles = array_filter($loadedFiles, function($file) {
    return strpos($file, 'Kokurikuler.php') !== false;
});

echo "<h2>File Kokurikuler.php yang ter-load:</h2>";
echo "<pre>";
print_r($kokurikulerFiles);
echo "</pre>";

// Cek isi method getStudentsPenilaian
$reflection = new ReflectionClass('App\Controllers\Admin\Kokurikuler');
$method = $reflection->getMethod('getStudentsPenilaian');
$filename = $method->getFileName();
$startLine = $method->getStartLine();
$endLine = $method->getEndLine();

echo "<h2>Method getStudentsPenilaian:</h2>";
echo "File: $filename<br>";
echo "Lines: $startLine - $endLine<br>";

// Baca isi method
$file = file($filename);
$methodCode = array_slice($file, $startLine - 1, $endLine - $startLine + 1);

echo "<h3>Kode Method:</h3>";
echo "<pre>";
echo htmlspecialchars(implode('', $methodCode));
echo "</pre>";

// Cari string "students.academic_year_id"
if (strpos(implode('', $methodCode), 'students.academic_year_id') !== false) {
    echo "<h2 style='color:red'>❌ MASIH ADA students.academic_year_id!</h2>";
} else {
    echo "<h2 style='color:green'>✅ TIDAK ADA students.academic_year_id</h2>";
}
```

Akses: `http://localhost:8080/siakad/debug_loaded_files.php`

Ini akan menunjukkan file mana yang sebenarnya ter-load dan isi method-nya.

---

## 🚨 JIKA MASIH GAGAL

Kemungkinan terakhir: Ada **2 versi file** yang berbeda.

Cari semua file Kokurikuler.php:

```bash
# Di command prompt
dir /s /b Kokurikuler.php
```

Atau di Windows Explorer:
- Tekan **Windows + F**
- Cari: `Kokurikuler.php`
- Lokasi: `C:\xampp\htdocs\siakad`

Jika ada lebih dari 1 file (misal ada backup atau copy), **HAPUS yang lain**, biarkan hanya:
```
C:\xampp\htdocs\siakad\app\Controllers\Admin\Kokurikuler.php
```

---

## 📞 Kirimkan Info Ini

Jika masih error, tolong kirimkan:

1. Screenshot hasil `debug_loaded_files.php`
2. Hasil command: `dir /s /b Kokurikuler.php`
3. Screenshot XAMPP Control Panel (untuk lihat versi PHP)

Dengan info ini saya bisa identifikasi masalah yang sebenarnya.
