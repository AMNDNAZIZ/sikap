# Solusi: Anti-Tampering Aktif Setelah Update

## Masalah

Setelah update file di online, muncul error:
```
❌ Anti-Tampering Aktif
❌ File sistem telah dimodifikasi
❌ LICENSE ERROR: CRITICAL_FILE_MODIFIED
```

## Penyebab

Sistem keamanan mendeteksi perubahan hash file setelah update. Ini adalah behavior yang NORMAL dan EXPECTED setelah update file. Hash perlu di-regenerate agar sesuai dengan file baru.

## Solusi

### Metode 1: Via Script Regenerate (Recommended)

#### Langkah-langkah:

1. **Upload Script**
   ```
   Upload file: regenerate_hash_after_update.php
   Ke root folder aplikasi (sejajar dengan index.php)
   ```

2. **Akses Script**
   ```
   http://yourdomain.com/regenerate_hash_after_update.php
   ```

3. **Klik Tombol**
   ```
   Klik: "Regenerate All Hashes"
   Tunggu proses selesai
   ```

4. **Verify**
   ```
   Akses aplikasi normal
   Error anti-tampering hilang
   ```

5. **Hapus Script (Keamanan)**
   ```
   Hapus file: regenerate_hash_after_update.php
   Untuk keamanan, jangan biarkan script ini di server
   ```

---

### Metode 2: Via Script Existing (generate_security_hash.php)

Jika Anda sudah punya script `generate_security_hash.php`:

1. **Akses Script**
   ```
   http://yourdomain.com/generate_security_hash.php
   ```

2. **Regenerate Security Hash**
   ```
   Script akan otomatis regenerate .security_hash
   ```

3. **Regenerate License Hash**
   ```
   Akses: http://yourdomain.com/generate_license_hash.php
   ```

4. **Verify**
   ```
   Akses aplikasi normal
   ```

---

### Metode 3: Via Updater (Auto-Regenerate)

Jika menggunakan fitur updater di `/admin/updater`:

**Good News**: Hash sudah otomatis di-regenerate!

Updater sudah include auto-regenerate hash setelah extract file:
```php
// app/Controllers/Admin/Updater.php - patchFiles()

// Step 4: Auto-migrate database
$migrateResult = $this->autoMigrateAfterUpdate();

// Step 5: Regenerate security hashes (OTOMATIS!)
$this->regenerateSecurityHashes();
```

Jadi jika update via updater, seharusnya tidak ada masalah anti-tampering.

---

### Metode 4: Manual via FTP/cPanel

Jika tidak bisa akses script:

1. **Delete Hash Files**
   ```
   Hapus file:
   - app/Config/.security_hash
   - app/Config/.lic_hash
   ```

2. **Akses Aplikasi**
   ```
   Sistem akan auto-generate hash baru saat pertama kali diakses
   ```

3. **Verify**
   ```
   Check log: writable/logs/
   Seharusnya ada log: "Security hash file not found, generating..."
   ```

---

## File yang Di-regenerate

### 1. Security Hash (.security_hash)
**File yang di-protect:**
- Helpers/license_helper.php
- Libraries/LicenseGuard.php
- Libraries/SecurityGuard.php
- Libraries/SecureConfig.php
- Filters/LicenseFilter.php
- Models/LicenseModel.php
- Config/Filters.php

**Hash Algorithm**: SHA-256

---

### 2. License Hash (.lic_hash)
**File yang di-protect:**
- Libraries/LicenseGuard.php
- Filters/LicenseFilter.php
- Models/LicenseModel.php

**Hash Algorithm**: SHA-1

---

## Keamanan Script Regenerate

### Access Control

Script `regenerate_hash_after_update.php` memiliki 2 layer security:

1. **Localhost Only**
   ```
   Hanya bisa diakses dari localhost
   ```

2. **Secret Key**
   ```
   Untuk akses dari remote, butuh secret key
   Default key: "update2026"
   
   Akses: http://yourdomain.com/regenerate_hash_after_update.php?key=update2026
   ```

### Ganti Secret Key

Edit file `regenerate_hash_after_update.php`:
```php
// Line ~30
$hasSecretKey = !empty($secretKey) && $secretKey === 'update2026'; // Ganti dengan key rahasia Anda
```

Ganti `'update2026'` dengan key rahasia Anda sendiri.

---

## Troubleshooting

### Error: "Could not save security hashes"

**Penyebab**: Write permission denied pada folder `app/Config/`

**Solusi**:
```bash
# Via SSH
chmod 755 app/Config/
chmod 644 app/Config/.security_hash
chmod 644 app/Config/.lic_hash

# Via cPanel File Manager
- Klik kanan folder app/Config/
- Change Permissions → 755
```

---

### Error: "File not found"

**Penyebab**: File critical tidak ada di folder `app/`

**Solusi**:
1. Check apakah file berikut ada:
   - app/Helpers/license_helper.php
   - app/Libraries/LicenseGuard.php
   - app/Libraries/SecurityGuard.php
   - dll (lihat list di atas)

2. Jika tidak ada, upload ulang file tersebut

---

### Error: "Access denied"

**Penyebab**: Akses dari remote tanpa secret key

**Solusi**:
1. Akses dengan secret key:
   ```
   http://yourdomain.com/regenerate_hash_after_update.php?key=update2026
   ```

2. Atau akses dari localhost (jika punya SSH access)

---

### Hash Sudah Di-regenerate Tapi Masih Error

**Kemungkinan Penyebab**:
1. File hash tidak ter-save dengan benar
2. Permission issue
3. Cache browser

**Solusi**:
1. Check file hash ada dan valid:
   ```
   app/Config/.security_hash (harus ada dan berisi JSON)
   app/Config/.lic_hash (harus ada dan berisi JSON)
   ```

2. Clear cache browser (Ctrl+Shift+Delete)

3. Check log error:
   ```
   writable/logs/log-YYYY-MM-DD.log
   ```

4. Coba delete hash files dan biarkan auto-generate:
   ```
   Hapus: app/Config/.security_hash
   Hapus: app/Config/.lic_hash
   Akses aplikasi → Auto-generate
   ```

---

## Workflow Update yang Benar

Untuk menghindari masalah anti-tampering di update berikutnya:

### Update via Updater (Recommended)

```
1. Login sebagai admin
2. Akses: /admin/updater
3. Upload ZIP update
4. Klik: "Upload & Update"
5. ✅ Hash otomatis di-regenerate
6. ✅ Tidak ada error anti-tampering
```

### Update Manual via FTP

```
1. Backup database
2. Upload file update via FTP
3. Akses: regenerate_hash_after_update.php
4. Klik: "Regenerate All Hashes"
5. ✅ Hash ter-regenerate
6. ✅ Tidak ada error anti-tampering
7. Hapus: regenerate_hash_after_update.php
```

---

## Prevention (Untuk Update Berikutnya)

### 1. Gunakan Fitur Updater

Fitur updater di `/admin/updater` sudah include:
- ✅ Auto-migrate database
- ✅ Auto-regenerate security hashes
- ✅ Backup otomatis
- ✅ Maintenance mode

Jadi tidak perlu manual regenerate hash.

### 2. Include Script Regenerate di Package Update

Saat membuat package update (ZIP), include file:
- `regenerate_hash_after_update.php`

Sehingga user bisa langsung regenerate setelah extract.

### 3. Dokumentasi untuk User

Buat dokumentasi untuk user:
```
SETELAH UPDATE FILE:
1. Akses: regenerate_hash_after_update.php
2. Klik: "Regenerate All Hashes"
3. Selesai!
```

---

## Summary

### Masalah:
❌ Anti-tampering aktif setelah update file

### Penyebab:
🔍 Hash file berubah setelah update (normal behavior)

### Solusi:
✅ Regenerate hash dengan script `regenerate_hash_after_update.php`

### Prevention:
✅ Gunakan fitur updater (auto-regenerate hash)

### File yang Dibuat:
- ✅ `regenerate_hash_after_update.php` - Script regenerate hash
- ✅ `SOLUSI_ANTI_TAMPERING_AFTER_UPDATE.md` - Dokumentasi lengkap (file ini)

---

**Status**: ✅ SOLUSI LENGKAP  
**Risk Level**: LOW (script aman dengan access control)  
**Estimated Time**: 2 menit
