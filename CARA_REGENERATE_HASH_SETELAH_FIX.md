# Cara Regenerate Hash Setelah Fix CBT 403

## MENGAPA PERLU REGENERATE HASH?

Setelah mengubah file konfigurasi untuk fix CBT 403 errors:
- `app/Config/Security.php` - Changed `$regenerate` from true to false
- `app/Config/Filters.php` - Added exception for refreshCsrf

Sistem anti-tampering akan mendeteksi perubahan file dan memblokir akses dengan error:
```
⚠️ Anti-Tampering Aktif
File sistem telah dimodifikasi. Aplikasi diblokir untuk keamanan.
```

## SOLUSI: REGENERATE HASH

### Metode 1: Via Script Simple (RECOMMENDED)

**File:** `regenerate_hash_simple.php`

```bash
# Via CLI
php regenerate_hash_simple.php

# Via Browser
http://localhost/siakad/regenerate_hash_simple.php
```

**Output:**
```
=================================================================
Regenerate Hash - Simple Version
=================================================================

1. Regenerating Security Hash...
   ✓ Helpers/license_helper.php
   ✓ Libraries/LicenseGuard.php
   ✓ Libraries/SecurityGuard.php
   ✓ Libraries/SecureConfig.php
   ✓ Filters/LicenseFilter.php
   ✓ Models/LicenseModel.php
   ✓ Config/Filters.php
   ✓ Config/Security.php
   ✅ Security hash saved: 8 files

2. Regenerating License Hash...
   ✓ Libraries/LicenseGuard.php
   ✓ Filters/LicenseFilter.php
   ✓ Models/LicenseModel.php
   ✅ License hash saved: 3 files

✅ All hashes regenerated successfully!
```

### Metode 2: Via Script Lengkap

**File:** `regenerate_hash_after_update.php`

```bash
# Via Browser (dengan form)
http://localhost/siakad/regenerate_hash_after_update.php

# Klik tombol "Regenerate All Hashes"
```

### Metode 3: Via Security Tools

**File:** `security_tools.php`

```bash
# Via Browser
http://localhost/siakad/security_tools.php

# Pilih "Regenerate Security Hash"
```

## FILES YANG DI-HASH

### Security Hash (.security_hash)
8 files critical:
1. `Helpers/license_helper.php`
2. `Libraries/LicenseGuard.php`
3. `Libraries/SecurityGuard.php`
4. `Libraries/SecureConfig.php`
5. `Filters/LicenseFilter.php`
6. `Models/LicenseModel.php`
7. `Config/Filters.php` ✅ (Modified for CBT fix)
8. `Config/Security.php` ✅ (Modified for CBT fix)

### License Hash (.lic_hash)
3 files license:
1. `Libraries/LicenseGuard.php`
2. `Filters/LicenseFilter.php`
3. `Models/LicenseModel.php`

## HASH FILES LOCATION

Hash files disimpan di:
```
app/Config/.security_hash  (Security hash)
app/Config/.lic_hash       (License hash)
```

Kedua file ini di-gitignore untuk keamanan.

## VERIFICATION

### Check Hash Files
```bash
# Check if hash files exist
ls -la app/Config/.security_hash
ls -la app/Config/.lic_hash

# View hash content
cat app/Config/.security_hash
cat app/Config/.lic_hash
```

### Test Access
1. Akses aplikasi: `http://localhost/siakad`
2. Verifikasi tidak ada error anti-tampering
3. Login dan test fitur CBT
4. Verifikasi tidak ada error 403

## TROUBLESHOOTING

### Error: Failed to save hash
**Masalah:** Write permission denied

**Solusi:**
```bash
# Linux/Mac
chmod 755 app/Config/
chmod 644 app/Config/.security_hash
chmod 644 app/Config/.lic_hash

# Windows
# Right-click folder → Properties → Security → Edit permissions
```

### Error: File not found
**Masalah:** Critical file missing

**Solusi:**
1. Check if file exists di folder app/
2. Restore file dari backup
3. Re-upload file dari source

### Error: Still blocked after regenerate
**Masalah:** Hash tidak ter-update

**Solusi:**
1. Clear browser cache
2. Clear application cache: `php spark cache:clear`
3. Restart web server
4. Regenerate hash ulang

## SECURITY NOTES

### Kapan Perlu Regenerate?

Regenerate hash HANYA diperlukan setelah:
- ✅ Update file aplikasi (via updater atau manual)
- ✅ Modifikasi file critical (Security.php, Filters.php, dll)
- ✅ Fix bugs yang mengubah file critical
- ❌ TIDAK perlu untuk perubahan file biasa (views, controllers non-critical)

### Keamanan Hash

1. **Hash Algorithm**
   - Security Hash: SHA-256 (strong)
   - License Hash: SHA-1 (sufficient)

2. **Hash Storage**
   - Stored in `.security_hash` and `.lic_hash`
   - Files are gitignored
   - Not accessible via web (protected by .htaccess)

3. **Validation**
   - Checked on every request via SecurityGuard
   - Blocks access if mismatch detected
   - Logs tampering attempts

## AUTOMATION

### Auto-Regenerate via Updater

Fitur updater di `/admin/updater` sudah auto-regenerate hash setelah update:

```php
// In Updater controller
private function regenerateSecurityHash()
{
    // Auto-regenerate after update
    $this->securityGuard->regenerateHash();
}
```

Jadi untuk update berikutnya, gunakan fitur updater (tidak perlu manual regenerate).

## PRODUCTION DEPLOYMENT

### Checklist Setelah Deploy

- [ ] Upload file update ke server
- [ ] Regenerate hash via script
- [ ] Test akses aplikasi
- [ ] Verify tidak ada error anti-tampering
- [ ] Test fitur CBT (no 403 errors)
- [ ] Delete regenerate script dari server (security)

### Deployment Steps

1. **Backup**
   ```bash
   # Backup hash files
   cp app/Config/.security_hash app/Config/.security_hash.bak
   cp app/Config/.lic_hash app/Config/.lic_hash.bak
   ```

2. **Upload Files**
   - Upload modified files (Security.php, Filters.php, ujian.php)

3. **Regenerate Hash**
   ```bash
   php regenerate_hash_simple.php
   ```

4. **Verify**
   - Test akses aplikasi
   - Test CBT functionality

5. **Cleanup**
   ```bash
   # Delete regenerate scripts (security)
   rm regenerate_hash_simple.php
   rm regenerate_hash_after_update.php
   ```

## SUMMARY

**Setelah Fix CBT 403:**
1. ✅ Modified `app/Config/Security.php`
2. ✅ Modified `app/Config/Filters.php`
3. ✅ Modified `app/Views/siswa/cbt/ujian.php`
4. ✅ Regenerate hash via `regenerate_hash_simple.php`
5. ✅ Verify aplikasi bisa diakses
6. ✅ Test CBT (no 403 errors)
7. ✅ Delete regenerate script

**Status:** ✅ COMPLETE

Aplikasi sekarang bisa diakses normal tanpa error anti-tampering, dan CBT berjalan lancar tanpa error 403!
