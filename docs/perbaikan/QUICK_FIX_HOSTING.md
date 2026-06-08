# 🚨 Quick Fix untuk Masalah di Hosting

Panduan cepat memperbaiki 2 masalah kritis yang ditemukan setelah deploy.

## ⚡ Masalah 1: Error Session (Tabel ci_sessions tidak ada)

### Gejala
- Error saat login
- Session tidak tersimpan
- Aplikasi logout otomatis

### Solusi Cepat (1 Menit)

**Via phpMyAdmin:**

1. Buka phpMyAdmin
2. Pilih database aplikasi
3. Klik tab "SQL"
4. Copy-paste query ini:

```sql
CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` BLOB NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

5. Klik "Go"
6. Selesai! ✅

**Via Command Line:**

```bash
php spark migrate
```

---

## ⚡ Masalah 2: Error shell_exec() di Aktivasi Lisensi

### Gejala
- Error: `Call to undefined function shell_exec()`
- Terjadi saat memasukkan kode lisensi
- Aktivasi gagal

### Solusi Cepat (2 Menit)

**Update File:**

1. Buka file: `app/Helpers/license_helper.php`
2. Cari fungsi `generate_machine_id()`
3. Cari bagian yang menggunakan `shell_exec()`
4. Ganti dengan kode yang sudah diperbaiki (lihat di repository)

**Atau download file terbaru:**

```bash
# Via Git
git pull origin main

# Atau download manual dari repository
# Lalu replace file app/Helpers/license_helper.php
```

**Atau copy-paste kode perbaikan:**

Ganti bagian ini di `app/Helpers/license_helper.php`:

```php
// GANTI DARI:
// 7. Try to get hardware-based identifiers (if available)
if (PHP_OS_FAMILY === 'Linux') {
    $cpuinfo = @shell_exec('cat /proc/cpuinfo | grep "model name" | head -1');
    // ...
}

// MENJADI:
// 7. Try to get hardware-based identifiers (if available and shell_exec is enabled)
if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
    if (PHP_OS_FAMILY === 'Linux') {
        $cpuinfo = @shell_exec('cat /proc/cpuinfo | grep "model name" | head -1');
        if ($cpuinfo) {
            $data[] = md5($cpuinfo);
        }
        $mac = @shell_exec('cat /sys/class/net/*/address 2>/dev/null | head -1');
        if ($mac) {
            $data[] = trim($mac);
        }
    } elseif (PHP_OS_FAMILY === 'Windows') {
        $guid = @shell_exec('wmic csproduct get uuid 2>nul');
        if ($guid) {
            $data[] = md5($guid);
        }
    }
} else {
    // Fallback: Use server-specific identifiers when shell_exec is disabled
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $data[] = md5($_SERVER['SERVER_SOFTWARE']);
    }
    if (isset($_SERVER['SERVER_SIGNATURE'])) {
        $data[] = md5($_SERVER['SERVER_SIGNATURE']);
    }
    $data[] = md5($_SERVER['SERVER_ADDR'] ?? 'unknown');
    $data[] = md5($_SERVER['HTTP_HOST'] ?? 'unknown');
}
```

---

## ✅ Verifikasi

### Test Masalah 1 (Session)

1. Buka aplikasi
2. Login dengan user valid
3. Refresh halaman
4. Pastikan tetap login (tidak logout otomatis)

### Test Masalah 2 (Lisensi)

1. Buka halaman aktivasi
2. Masukkan kode lisensi
3. Klik aktivasi
4. Pastikan tidak ada error
5. Aktivasi berhasil ✅

---

## 🔄 Jika Sudah Pernah Aktivasi

Setelah perbaikan masalah 2, machine ID akan berubah. Anda perlu:

1. Hapus lisensi lama:
   ```sql
   DELETE FROM app_license;
   ```

2. Aktivasi ulang dengan kode lisensi yang sama

3. Selesai! ✅

---

## 📞 Masih Ada Masalah?

### Cek Error Log

**Via File Manager:**
- Buka folder: `writable/logs/`
- Baca file log terbaru
- Cari error message

**Via cPanel:**
- Error Logs → Pilih domain
- Lihat error terbaru

### Hubungi Support

Jika masih error, hubungi support dengan informasi:
- Error message lengkap
- Screenshot error
- File log (jika ada)

---

## 📋 Checklist

- [ ] Masalah 1: Tabel ci_sessions dibuat
- [ ] Test login berhasil
- [ ] Session tersimpan dengan baik
- [ ] Masalah 2: File license_helper.php diupdate
- [ ] Test aktivasi lisensi berhasil
- [ ] Tidak ada error shell_exec()
- [ ] Aplikasi berjalan normal

---

**Estimasi Waktu:** 3-5 menit  
**Tingkat Kesulitan:** ⭐ Mudah  
**Priority:** 🔴 High (Critical)

**Dibuat:** 21 Februari 2026  
**Status:** ✅ Tested & Working
