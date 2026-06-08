# Security Tools - SIAKAD License System

Helper scripts untuk maintenance dan testing sistem keamanan lisensi.

---

## 📋 Available Scripts

### 1. generate_security_hash.php ⭐ RECOMMENDED
**Fungsi:** Generate security hash untuk file-file kritis

**Cara Pakai:**
```bash
php generate_security_hash.php
```

**Output:**
```
✓ Helpers/license_helper.php
  Hash: 19aa11a46a6c30e5...
✓ Libraries/LicenseGuard.php
  Hash: 2aad4c0c68089a6a...
...
✅ SUCCESS: Security hashes saved
   File: app/Config/.security_hash
   Protected files: 7
```

**Kapan Digunakan:**
- Setelah install/upgrade aplikasi
- Setelah modifikasi file kritis (legitimate changes)
- Saat setup aplikasi baru

---

### 2. verify_security_hash.php ⭐ RECOMMENDED
**Fungsi:** Verifikasi integritas file-file kritis

**Cara Pakai:**
```bash
php verify_security_hash.php
```

**Output (Success):**
```
✓ Helpers/license_helper.php - OK
✓ Libraries/LicenseGuard.php - OK
...
✅ SUCCESS: All critical files are intact
   No tampering detected
```

**Output (Tampering Detected):**
```
✓ Helpers/license_helper.php - OK
✗ Libraries/LicenseGuard.php - MODIFIED!
  Expected: 2aad4c0c68089a6a...
  Current:  3bbd5d1d79190b7b...
...
❌ SECURITY VIOLATION DETECTED!
```

**Kapan Digunakan:**
- Troubleshooting error "File sistem telah dimodifikasi"
- Audit keamanan berkala
- Sebelum deployment ke production

---

### 3. security_tools.php (Advanced)
**Fungsi:** Comprehensive security tools dengan full CodeIgniter bootstrap

**Cara Pakai:**
```bash
# Show help
php security_tools.php help

# Generate hash
php security_tools.php generate-hash

# Verify hash
php security_tools.php verify-hash

# Check license status
php security_tools.php check-license

# Show machine info
php security_tools.php show-machine-id

# Test all security features
php security_tools.php test-security
```

**Catatan:** Script ini memerlukan full CodeIgniter bootstrap. Jika ada error, gunakan script simple (1 & 2) sebagai alternatif.

---

## 🚀 Quick Start

### Setup Awal (Setelah Install/Upgrade)

```bash
# 1. Generate security hash
php generate_security_hash.php

# 2. Verify integrity
php verify_security_hash.php

# 3. Test aplikasi
# Akses: http://localhost/siakad
```

### Maintenance Rutin

```bash
# Verify integrity (mingguan/bulanan)
php verify_security_hash.php
```

### Troubleshooting

```bash
# Jika ada error "File sistem telah dimodifikasi"
php generate_security_hash.php

# Verify lagi
php verify_security_hash.php
```

---

## 📊 File yang Diproteksi

Security hash melindungi 7 file kritis:

1. `app/Helpers/license_helper.php` - License helper functions
2. `app/Libraries/LicenseGuard.php` - License validation logic
3. `app/Libraries/SecurityGuard.php` - Security verification
4. `app/Libraries/SecureConfig.php` - Encrypted config storage
5. `app/Filters/LicenseFilter.php` - License filter middleware
6. `app/Models/LicenseModel.php` - License database model
7. `app/Config/Filters.php` - Filter configuration

---

## 🔍 Cara Kerja

### Generate Hash
1. Baca setiap file kritis
2. Generate SHA-256 hash untuk setiap file
3. Simpan hash ke `app/Config/.security_hash`
4. Tambahkan ke `.gitignore`

### Verify Hash
1. Load expected hash dari `app/Config/.security_hash`
2. Generate current hash untuk setiap file
3. Bandingkan expected vs current
4. Report jika ada perbedaan (tampering detected)

### Runtime Verification (Otomatis)
1. Setiap request, `SecurityGuard::verifyCriticalFiles()` dipanggil
2. Jika hash tidak match → Block akses
3. Jika hash match → Lanjutkan request

---

## 🐛 Troubleshooting

### Error: "Security hash file not found"

**Penyebab:** File `.security_hash` belum di-generate

**Solusi:**
```bash
php generate_security_hash.php
```

### Error: "File tampering detected"

**Penyebab:** File kritis dimodifikasi setelah generate hash

**Solusi (Jika modifikasi legitimate):**
```bash
# Regenerate hash
php generate_security_hash.php
```

**Solusi (Jika modifikasi unauthorized):**
```bash
# Restore dari backup
git checkout app/Helpers/license_helper.php
# atau restore dari backup manual

# Verify lagi
php verify_security_hash.php
```

### Error: "Undefined constant FCPATH" (security_tools.php)

**Penyebab:** CodeIgniter bootstrap gagal

**Solusi:** Gunakan script simple sebagai alternatif:
```bash
# Gunakan ini
php generate_security_hash.php
php verify_security_hash.php

# Bukan ini
php security_tools.php generate-hash
```

---

## 📝 Best Practices

### Development

1. **Generate hash setelah setiap perubahan legitimate:**
   ```bash
   # Edit file
   nano app/Helpers/license_helper.php
   
   # Regenerate hash
   php generate_security_hash.php
   ```

2. **Verify sebelum commit:**
   ```bash
   php verify_security_hash.php
   git add .
   git commit -m "Update license helper"
   ```

### Production

1. **Generate hash setelah deployment:**
   ```bash
   # Deploy files
   # ...
   
   # Generate hash di server production
   php generate_security_hash.php
   ```

2. **Audit berkala:**
   ```bash
   # Setup cron job (mingguan)
   0 0 * * 0 cd /path/to/siakad && php verify_security_hash.php
   ```

### Backup

1. **Backup hash file:**
   ```bash
   cp app/Config/.security_hash app/Config/.security_hash.backup
   ```

2. **Include dalam backup rutin:**
   - Backup database
   - Backup folder app
   - Backup `.security_hash`

---

## ⚠️ Catatan Penting

### DO (Lakukan)
- ✅ Generate hash setelah install/upgrade
- ✅ Regenerate hash setelah modifikasi legitimate
- ✅ Verify hash secara berkala
- ✅ Backup hash file
- ✅ Monitor log files

### DON'T (Jangan)
- ❌ Hapus file `.security_hash`
- ❌ Edit file kritis tanpa regenerate hash
- ❌ Commit `.security_hash` ke git (sudah di .gitignore)
- ❌ Share hash file ke public
- ❌ Disable security check di production

---

## 🔐 Security Considerations

1. **Hash file bersifat machine-specific:**
   - Hash di development ≠ hash di production
   - Generate ulang di setiap environment

2. **Hash file tidak di-commit ke git:**
   - Sudah otomatis ditambahkan ke `.gitignore`
   - Setiap server generate hash sendiri

3. **Regenerate hash setelah update:**
   - Setelah pull dari git
   - Setelah upload patch ZIP
   - Setelah modifikasi manual

---

## 📞 Support

Jika mengalami kesulitan:

1. **Cek log files:** `writable/logs/log-*.log`
2. **Baca troubleshooting** di atas
3. **Hubungi tim development** untuk bantuan

---

## 📚 Dokumentasi Terkait

- `UPGRADE_KEAMANAN_LISENSI.md` - Panduan lengkap upgrade
- `QUICK_GUIDE_KEAMANAN.txt` - Quick reference
- `SUMMARY_UPGRADE_KEAMANAN.md` - Summary lengkap
- `docs/keamanan/` - Folder dokumentasi keamanan

---

**Dibuat:** 25 Februari 2026  
**Versi:** 2.1  
**Status:** ✅ Production Ready
