# Troubleshooting Keamanan Lisensi

Panduan cepat untuk mengatasi masalah keamanan lisensi.

---

## ❌ Error: "File sistem telah dimodifikasi"

### Penyebab
File kritis dimodifikasi setelah security hash di-generate.

### Solusi

#### Opsi 1: Via CLI (Recommended)
```bash
php generate_security_hash.php
```

#### Opsi 2: Via Browser
```
http://localhost/siakad/test_security.php
→ Klik "Generate Hash"
```

#### Opsi 3: Via Aplikasi
```
Akses aplikasi sekali lagi
→ Hash akan auto-regenerate
```

---

## ❌ Error: "Access denied. This tool can only be accessed from localhost"

### Penyebab
Script security tools hanya bisa diakses dari localhost untuk keamanan.

### Solusi

#### Jika di Localhost tapi tetap error:

**Cek 1: Pastikan menggunakan localhost**
```
✓ http://localhost/siakad/verify_security_hash.php
✗ http://192.168.1.100/siakad/verify_security_hash.php
```

**Cek 2: Gunakan CLI**
```bash
cd C:\xampp\htdocs\siakad
php verify_security_hash.php
```

**Cek 3: Gunakan test_security.php**
```
http://localhost/siakad/test_security.php
```

#### Jika di Production:
Script ini memang tidak bisa diakses di production untuk keamanan. Gunakan alternatif:

1. **SSH ke server** dan jalankan via CLI
2. **Akses aplikasi** - hash akan auto-regenerate
3. **Hapus file** test_security.php di production

---

## ❌ Error: "LICENSE FILE MODIFIED"

### Penyebab
File `.lic_hash` tidak ditemukan, tidak valid, atau file license dimodifikasi.

### Solusi

#### Opsi 1: Generate License Hash (Recommended)
```bash
php generate_license_hash.php
```

#### Opsi 2: Via Browser
```
http://localhost/siakad/test_security.php
→ Akan auto-generate saat akses aplikasi
```

#### Opsi 3: Manual (Jika Permission Issue)
```bash
# Generate hash untuk setiap file
php -r "echo sha1_file('app/Libraries/LicenseGuard.php');"
php -r "echo sha1_file('app/Filters/LicenseFilter.php');"
php -r "echo sha1_file('app/Models/LicenseModel.php');"

# Update app/Config/.lic_hash dengan hash yang didapat
```

### Perbedaan .lic_hash vs .security_hash

| File | Algorithm | Purpose |
|------|-----------|---------|
| `.lic_hash` | SHA-1 | License integrity check |
| `.security_hash` | SHA-256 | Security file check |

**Keduanya diperlukan untuk proteksi penuh!**

---

## ❌ Aplikasi Redirect ke /activate Terus

### Penyebab
Lisensi expired, tidak valid, atau tidak ada.

### Solusi

#### Cek 1: Lihat Status Lisensi
```bash
php test_security.php
→ Pilih "System Info"
```

Atau via database:
```sql
SELECT * FROM app_license;
```

#### Cek 2: Lisensi Expired
```sql
-- Update masa aktif (development only!)
UPDATE app_license 
SET expires_at = DATE_ADD(NOW(), INTERVAL 1 YEAR),
    status = 'active'
WHERE id = 1;
```

#### Cek 3: Lisensi Tidak Ada
```
1. Akses: http://localhost/siakad/activate
2. Input kode lisensi test
3. Atau hubungi pengembang untuk kode lisensi
```

---

## ❌ Error: "INVALID MACHINE"

### Penyebab
Machine ID atau hardware signature tidak match.

### Solusi

#### Development:
```sql
-- Reset machine binding (development only!)
UPDATE app_license 
SET machine_id = NULL,
    hardware_signature = NULL
WHERE id = 1;

-- Akses aplikasi lagi
-- Machine ID akan auto-bind ke mesin baru
```

#### Production:
```
1. Hubungi pengembang
2. Minta re-aktivasi lisensi
3. Input kode lisensi baru
```

---

## ⚠️ Warning: "Security hash file not found"

### Penyebab
File `.security_hash` belum di-generate.

### Solusi
```bash
php generate_security_hash.php
```

Atau akses aplikasi sekali - akan auto-generate.

---

## ⚠️ Warning: "File not in hash list"

### Penyebab
Ada file baru yang belum masuk dalam daftar hash.

### Solusi
```bash
# Regenerate hash dengan daftar file terbaru
php generate_security_hash.php
```

---

## 🔍 Diagnostic Tools

### Tool 1: Verify Hash
```bash
php verify_security_hash.php
```

Output akan menunjukkan file mana yang bermasalah.

### Tool 2: Test Security (Web)
```
http://localhost/siakad/test_security.php
```

Interface web untuk:
- Generate hash
- Verify hash
- Lihat system info

### Tool 3: Check Log Files
```bash
# Windows
type writable\logs\log-*.log | findstr /i "security"

# Linux
tail -f writable/logs/log-*.log | grep -i security
```

---

## 🛠️ Quick Fixes

### Fix 1: Reset Semua Security Hash
```bash
# Hapus hash lama
rm app/Config/.security_hash

# Generate baru
php generate_security_hash.php

# Verify
php verify_security_hash.php
```

### Fix 2: Reset Lisensi (Development Only)
```sql
-- Truncate dan re-aktivasi
TRUNCATE TABLE app_license;

-- Akses aplikasi
-- Input kode lisensi baru
```

### Fix 3: Disable Security Check Sementara
```php
// app/Libraries/LicenseGuard.php
public static function verify() {
    // Skip validation for development
    if (ENVIRONMENT === 'development') {
        return;
    }
    
    // ... existing code ...
}
```

**PENTING:** Jangan lupa enable kembali untuk production!

---

## 📋 Checklist Troubleshooting

Jika aplikasi bermasalah, cek satu per satu:

- [ ] File `.security_hash` ada di `app/Config/`
- [ ] Verify hash: `php verify_security_hash.php`
- [ ] Lisensi ada di database: `SELECT * FROM app_license`
- [ ] Lisensi belum expired
- [ ] Machine ID match (jika production)
- [ ] Filter license aktif di `app/Config/Filters.php`
- [ ] Cek log files untuk error detail

---

## 📞 Masih Bermasalah?

### Step 1: Kumpulkan Informasi
```bash
# System info
php test_security.php → System Info

# Verify hash
php verify_security_hash.php

# Check logs
tail writable/logs/log-*.log
```

### Step 2: Cek Database
```sql
SELECT * FROM app_license;
SELECT * FROM migrations ORDER BY batch DESC LIMIT 10;
```

### Step 3: Hubungi Support
Sertakan informasi:
- Error message lengkap
- Output dari verify_security_hash.php
- Log files (writable/logs/)
- Database status (app_license table)

---

## 🎓 Tips Mencegah Masalah

### DO (Lakukan)
- ✅ Generate hash setelah setiap modifikasi legitimate
- ✅ Backup file `.security_hash`
- ✅ Test di development sebelum production
- ✅ Monitor log files secara berkala

### DON'T (Jangan)
- ❌ Edit file kritis tanpa regenerate hash
- ❌ Hapus file `.security_hash`
- ❌ Disable security check di production
- ❌ Share kode lisensi ke public

---

**Dibuat:** 25 Februari 2026  
**Versi:** 2.1  
**Status:** ✅ Ready to Use
