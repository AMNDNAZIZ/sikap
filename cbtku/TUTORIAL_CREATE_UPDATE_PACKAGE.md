# TUTORIAL: Cara Menghasilkan File Update di Localhost Developer

**Tanggal:** 2026-03-08  
**Target:** Developer (Localhost)  
**Tujuan:** Membuat update package untuk distribusi ke sekolah-sekolah

---

## 📋 OVERVIEW

Sebagai developer, Anda akan membuat update package yang berisi:
1. File-file yang diubah/ditambahkan
2. CHANGELOG.md (daftar perubahan)
3. manifest.json (metadata update)
4. ZIP file untuk distribusi
5. SQL query untuk register di database

---

## 🎯 SKENARIO CONTOH

Kita akan membuat update package untuk **v1.0.2** yang berisi:
- Fix bug auto-regenerate hash
- 3 files yang diubah:
  - `app/Controllers/Activate.php`
  - `app/Controllers/Admin/License.php`
  - `app/Config/.lic_hash`

---

## 📝 STEP-BY-STEP TUTORIAL

### STEP 1: Pastikan Environment Development

**Check file marker:**
```bash
# Windows
dir .dev_environment

# Linux/Mac
ls -la .dev_environment
```

**Expected Output:**
```
.dev_environment
```

**Jika file tidak ada, buat dulu:**
```bash
# Windows
echo ENVIRONMENT=development > .dev_environment

# Linux/Mac
echo "ENVIRONMENT=development" > .dev_environment
```

**Test environment detection:**
```bash
php test_environment_detection.php
```

**Expected Output:**
```
═══════════════════════════════════════════════════════════
  DEVELOPER TOOLS - DEVELOPMENT ENVIRONMENT
═══════════════════════════════════════════════════════════

Testing Environment Detection System
═══════════════════════════════════════════════════════════

Test 1: .dev_environment file
   File exists: ✅ YES

...

Environment: DEVELOPMENT
Developer Tools: ✅ ENABLED
Protection: ✅ ACTIVE

✅ This is a DEVELOPMENT environment
   Developer tools are accessible
   Safe to run diagnostic scripts
```

---

### STEP 2: Regenerate License Hash (Jika Perlu)

**Kapan perlu regenerate hash?**
- Jika mengubah file yang di-protect license:
  - `app/Filters/LicenseFilter.php`
  - `app/Controllers/Activate.php`
  - `app/Controllers/Admin/License.php`
  - `app/Libraries/LicenseGuard.php`
  - `app/Models/LicenseModel.php`
  - `app/Config/LService.php`

**Command:**
```bash
php generate_license_hash.php
```

**Expected Output:**
```
═══════════════════════════════════════════════════════════
  DEVELOPER TOOLS - DEVELOPMENT ENVIRONMENT
═══════════════════════════════════════════════════════════

Generate License Hash File

Processing protected files...
✅ Filters/LicenseFilter.php
✅ Controllers/Activate.php
✅ Controllers/Admin/License.php
✅ Libraries/LicenseGuard.php
✅ Models/LicenseModel.php
✅ Config/LService.php

Hash file created: app/Config/.lic_hash
Checksum file created: app/Config/.lic_checksum

Done!
```

**Verify hash:**
```bash
php verify_license_hash.php
```

**Expected Output:**
```
Verifying license hash...

✅ Filters/LicenseFilter.php - OK
✅ Controllers/Activate.php - OK
✅ Controllers/Admin/License.php - OK
✅ Libraries/LicenseGuard.php - OK
✅ Models/LicenseModel.php - OK
✅ Config/LService.php - OK

All files verified successfully!
```

---

### STEP 3: Jalankan Script Create Update Package

**Command (Interactive Mode):**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1
```

**Atau dengan parameter:**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Version "1.0.2" -Title "Fix Auto-Regenerate Hash Bug" -Description "Perbaikan bug yang menyebabkan loop aktivasi berulang" -Critical
```

---

### STEP 4: Ikuti Prompt Interactive

**Prompt 1: Version Number**
```
Enter version number (e.g. 1.0.2): 1.0.2
```

**Prompt 2: Update Title**
```
Enter update title: Fix Auto-Regenerate Hash Bug
```

**Prompt 3: Description (Optional)**
```
Enter description (optional, press Enter to skip): Perbaikan bug yang menyebabkan loop aktivasi berulang setelah periksa pembaruan. Fixed JSON encoding inconsistency.
```

**Prompt 4: Critical Update?**
```
Is this a critical update? (y/n): n
```

**Output:**
```
===============================================================
  CBT UPDATE PACKAGE CREATOR
===============================================================

Development environment detected

Creating update package for version: 1.0.2
Title: Fix Auto-Regenerate Hash Bug
Description: Perbaikan bug yang menyebabkan loop aktivasi...
Critical: False

Creating folder structure...
Created: updates/v1.0.2

===============================================================
  SELECT FILES TO INCLUDE
===============================================================

Enter file paths (relative to project root), one per line.
Press Enter twice when done.

Example:
  app/Controllers/Activate.php
  app/Controllers/Admin/License.php
  app/Config/.lic_hash
```

---

### STEP 5: Input Files yang Diubah

**Input file paths (satu per satu):**

```
File path: app/Controllers/Activate.php
   Added: app/Controllers/Activate.php

File path: app/Controllers/Admin/License.php
   Added: app/Controllers/Admin/License.php

File path: app/Config/.lic_hash
   Added: app/Config/.lic_hash

File path: [Press Enter]

File path: [Press Enter lagi untuk selesai]
```

**Tips:**
- Gunakan path relatif dari root project
- Gunakan forward slash `/` atau backslash `\`
- Jika file tidak ditemukan, script akan tanya apakah tetap mau include

---

### STEP 6: Review Copying Process

**Output:**
```
===============================================================
  COPYING FILES
===============================================================

   Copied: app/Controllers/Activate.php
   Copied: app/Controllers/Admin/License.php
   Copied: app/Config/.lic_hash

Summary:
   Copied: 3 files
```

---

### STEP 7: Safety Check

**Output:**
```
===============================================================
  SAFETY CHECK
===============================================================

No sensitive files detected
```

**Jika ada sensitive files:**
```
WARNING: Sensitive files detected!

   .env
   test_something.php

These files should NOT be in production update!

Continue anyway? (y/n): n
```

---

### STEP 8: Review Generated Files

**Output:**
```
===============================================================
  CREATING CHANGELOG
===============================================================

Created: CHANGELOG.md

Creating manifest...
Created: manifest.json

===============================================================
  CREATING ZIP ARCHIVE
===============================================================

Created: update_v1.0.2.zip

Calculating file hash...
Hash: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

===============================================================
  UPDATE PACKAGE CREATED SUCCESSFULLY!
===============================================================

Package: update_v1.0.2.zip
Size: 0.85 MB
Files: 3
Hash: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

Location:
   Folder: updates/v1.0.2
   ZIP: updates/update_v1.0.2.zip
   Info: updates/update_v1.0.2.txt

===============================================================
  NEXT STEPS
===============================================================

1. Review package contents
2. Upload ZIP to Update Server: writable/updates/
3. Register in database (see updates/update_v1.0.2.txt)
4. Test update on staging first
5. Deploy to production

Ready to deploy!
```

---

## 📂 STRUKTUR FOLDER HASIL

Setelah script selesai, struktur folder:

```
updates/
├── v1.0.2/
│   ├── files/
│   │   ├── app/
│   │   │   ├── Controllers/
│   │   │   │   ├── Activate.php
│   │   │   │   └── Admin/
│   │   │   │       └── License.php
│   │   │   └── Config/
│   │   │       └── .lic_hash
│   ├── CHANGELOG.md
│   ├── manifest.json
│   └── INSTALL.txt
├── update_v1.0.2.zip
└── update_v1.0.2.txt
```

---

## 📄 ISI FILE YANG DIHASILKAN

### 1. CHANGELOG.md

```markdown
# Changelog v1.0.2

## Fix Auto-Regenerate Hash Bug

**Release Date:** 2026-03-08

### Description
Perbaikan bug yang menyebabkan loop aktivasi berulang setelah 
periksa pembaruan. Fixed JSON encoding inconsistency.

### Files Changed
- app/Controllers/Activate.php
- app/Controllers/Admin/License.php
- app/Config/.lic_hash

### Installation Instructions
1. Backup current files
2. Extract ZIP file
3. Copy files to application root
4. Clear cache
5. Test application

---

**Version:** 1.0.2  
**Critical:** False  
**Files:** 3
```

---

### 2. manifest.json

```json
{
  "version": "1.0.2",
  "title": "Fix Auto-Regenerate Hash Bug",
  "description": "Perbaikan bug yang menyebabkan loop aktivasi...",
  "release_date": "2026-03-08",
  "is_critical": false,
  "requires_backup": true,
  "requires_migration": false,
  "files": [
    "app/Controllers/Activate.php",
    "app/Controllers/Admin/License.php",
    "app/Config/.lic_hash"
  ],
  "post_install": [
    "clear_cache",
    "regenerate_hash"
  ]
}
```

---

### 3. update_v1.0.2.txt

```
===============================================================
  UPDATE PACKAGE INFO - v1.0.2
===============================================================

Version: 1.0.2
Title: Fix Auto-Regenerate Hash Bug
Description: Perbaikan bug yang menyebabkan loop aktivasi...
Release Date: 2026-03-08
Critical: False

File: update_v1.0.2.zip
Size: 0.85 MB (891234 bytes)
Hash (SHA256): a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6

Files Included: 3
- app/Controllers/Activate.php
- app/Controllers/Admin/License.php
- app/Config/.lic_hash

===============================================================
  SQL QUERY FOR DATABASE
===============================================================

INSERT INTO updates (
    version, 
    title, 
    description,
    file_name,
    file_size,
    file_hash,
    release_date,
    is_active,
    is_critical
) VALUES (
    '1.0.2',
    'Fix Auto-Regenerate Hash Bug',
    'Perbaikan bug yang menyebabkan loop aktivasi...',
    'update_v1.0.2.zip',
    891234,
    'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6',
    '2026-03-08',
    1,
    0
);

===============================================================
```

---

### 4. update_v1.0.2.zip

**Isi ZIP:**
```
files/
├── app/
│   ├── Controllers/
│   │   ├── Activate.php
│   │   └── Admin/
│   │       └── License.php
│   └── Config/
│       └── .lic_hash
CHANGELOG.md
manifest.json
INSTALL.txt
```

---

## ✅ VERIFIKASI HASIL

### Check 1: Folder Structure

```bash
# Windows
dir updates\v1.0.2

# Linux/Mac
ls -la updates/v1.0.2/
```

**Expected:**
```
files/
CHANGELOG.md
manifest.json
INSTALL.txt
```

---

### Check 2: ZIP File

```bash
# Windows
dir updates\update_v1.0.2.zip

# Linux/Mac
ls -lh updates/update_v1.0.2.zip
```

**Expected:**
```
update_v1.0.2.zip (sekitar 0.5-1 MB)
```

---

### Check 3: Info File

```bash
# Windows
type updates\update_v1.0.2.txt

# Linux/Mac
cat updates/update_v1.0.2.txt
```

**Expected:**
- Version info
- File list
- SQL query

---

### Check 4: ZIP Contents

```bash
# Windows PowerShell
Expand-Archive -Path updates\update_v1.0.2.zip -DestinationPath temp_check -Force
dir temp_check
rmdir temp_check -Recurse -Force

# Linux/Mac
unzip -l updates/update_v1.0.2.zip
```

**Expected:**
```
files/app/Controllers/Activate.php
files/app/Controllers/Admin/License.php
files/app/Config/.lic_hash
CHANGELOG.md
manifest.json
INSTALL.txt
```

---

## 🎬 VIDEO WALKTHROUGH (Text)

```
[00:00] Buka PowerShell di folder project
[00:05] Run: PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1
[00:10] Environment check: ✅ Development detected
[00:15] Input version: 1.0.2
[00:20] Input title: Fix Auto-Regenerate Hash Bug
[00:25] Input description: Perbaikan bug...
[00:30] Critical update? n
[00:35] Input file 1: app/Controllers/Activate.php
[00:40] Input file 2: app/Controllers/Admin/License.php
[00:45] Input file 3: app/Config/.lic_hash
[00:50] Press Enter twice
[00:55] Copying files... ✅
[01:00] Safety check... ✅ No sensitive files
[01:05] Creating CHANGELOG... ✅
[01:10] Creating manifest... ✅
[01:15] Creating ZIP... ✅
[01:20] Calculating hash... ✅
[01:25] Package created successfully!
[01:30] Review output: updates/v1.0.2/
[01:35] Check ZIP: updates/update_v1.0.2.zip
[01:40] Read info: updates/update_v1.0.2.txt
[01:45] Done! Ready to upload to server
```

---

## 🔍 TROUBLESHOOTING

### Issue 1: "File cannot be loaded because running scripts is disabled"

**Solution:**
```powershell
# Option 1: Bypass untuk satu kali run
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1

# Option 2: Set policy permanent
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```

---

### Issue 2: "Not in development environment"

**Solution:**
```bash
# Create .dev_environment file
echo "ENVIRONMENT=development" > .dev_environment

# Test
php test_environment_detection.php
```

---

### Issue 3: "File not found" saat input file path

**Possible Causes:**
- Path salah (typo)
- File belum di-save
- Path menggunakan absolute path (harus relative)

**Solution:**
```bash
# Check file exists
ls app/Controllers/Activate.php

# Use relative path from project root
# GOOD: app/Controllers/Activate.php
# BAD: C:\xampp\htdocs\cbtku\app\Controllers\Activate.php
```

---

### Issue 4: ZIP file terlalu besar

**Possible Causes:**
- Include folder yang tidak perlu
- Include vendor/ atau node_modules/

**Solution:**
- Hanya include file yang benar-benar diubah
- Jangan include entire folders
- Check safety warnings

---

### Issue 5: Sensitive files detected

**Possible Causes:**
- Include .env file
- Include test_*.php files
- Include debug_*.php files

**Solution:**
- Remove sensitive files dari list
- Run script lagi
- Atau skip dengan 'n' saat ditanya

---

## 📚 BEST PRACTICES

### 1. Version Numbering

**Format:** MAJOR.MINOR.PATCH (e.g., 1.0.2)

- **MAJOR:** Breaking changes (1.0.0 → 2.0.0)
- **MINOR:** New features, backward compatible (1.0.0 → 1.1.0)
- **PATCH:** Bug fixes (1.0.0 → 1.0.1)

**Examples:**
- `1.0.1` - Bug fix
- `1.1.0` - New feature
- `2.0.0` - Major rewrite

---

### 2. Update Title

**Good:**
- "Fix Auto-Regenerate Hash Bug"
- "Add Student Import Feature"
- "Security Update - XSS Prevention"

**Bad:**
- "Update"
- "Fix"
- "Changes"

---

### 3. Description

**Good:**
```
Perbaikan bug yang menyebabkan loop aktivasi berulang setelah 
periksa pembaruan. Fixed JSON encoding inconsistency di hash 
generation. Impact: Sistem tidak akan loop lagi setelah update.
```

**Bad:**
```
Fix bug
```

---

### 4. File Selection

**Include:**
- ✅ Files yang diubah
- ✅ Files yang ditambahkan
- ✅ License hash file (jika berubah)
- ✅ Migration files (jika ada)

**Don't Include:**
- ❌ .env files
- ❌ test_*.php files
- ❌ debug_*.php files
- ❌ vendor/ folder
- ❌ node_modules/ folder
- ❌ writable/ folder

---

### 5. Testing Before Upload

**Checklist:**
- [ ] Test di localhost dulu
- [ ] Verify no errors
- [ ] Check all features work
- [ ] Verify license hash (jika ada perubahan)
- [ ] Review CHANGELOG
- [ ] Check ZIP contents
- [ ] Verify file size reasonable

---

## 📊 CONTOH KASUS NYATA

### Kasus 1: Bug Fix (Patch)

**Scenario:** Fix error 500 di halaman data cleanup

**Files Changed:**
- `app/Controllers/Admin/DataCleanup.php`
- `app/Config/Filters.php`
- `app/Config/.lic_hash`

**Command:**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Version "1.0.3" -Title "Fix Data Cleanup Error 500" -Description "Perbaikan error 500 saat akses halaman data cleanup. Added CSRF exception."
```

---

### Kasus 2: New Feature (Minor)

**Scenario:** Tambah fitur import guru dari Excel

**Files Changed:**
- `app/Controllers/Admin/Teachers.php` (new method)
- `app/Views/admin/teachers/import.php` (new file)
- `app/Config/Routes.php` (new routes)
- `public/assets/js/teacher-import.js` (new file)

**Command:**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Version "1.1.0" -Title "Add Teacher Import Feature" -Description "Fitur baru: Import data guru dari file Excel. Support format .xlsx dan .xls."
```

---

### Kasus 3: Security Update (Critical)

**Scenario:** Fix XSS vulnerability

**Files Changed:**
- `app/Controllers/Admin/CbtBankSoal.php`
- `app/Helpers/security_helper.php`
- `app/Config/Security.php`

**Command:**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Version "1.0.4" -Title "Security Update - XSS Prevention" -Description "CRITICAL: Fix XSS vulnerability di bank soal. Wajib update segera!" -Critical
```

---

## 🎯 CHECKLIST LENGKAP

### Before Create Package:

- [ ] Semua perubahan sudah di-test di localhost
- [ ] No errors di error log
- [ ] License hash sudah di-regenerate (jika perlu)
- [ ] License hash sudah di-verify
- [ ] .dev_environment file exists
- [ ] Git commit (optional, untuk tracking)

### During Create Package:

- [ ] Version number correct (MAJOR.MINOR.PATCH)
- [ ] Title descriptive
- [ ] Description clear dan lengkap
- [ ] Critical flag correct
- [ ] File paths correct (relative)
- [ ] No sensitive files included
- [ ] Safety check passed

### After Create Package:

- [ ] Review CHANGELOG.md
- [ ] Review manifest.json
- [ ] Check ZIP file size
- [ ] Verify ZIP contents
- [ ] Read update_v1.0.x.txt
- [ ] Copy SQL query
- [ ] Test extract ZIP manually
- [ ] Backup package (optional)

---

## 📞 QUICK REFERENCE

**Create Package (Interactive):**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1
```

**Create Package (With Params):**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Version "1.0.2" -Title "Fix Bug" -Description "Bug fix" -Critical
```

**Show Help:**
```powershell
PowerShell -ExecutionPolicy Bypass -File .\create_update_package_v2.ps1 -Help
```

**Test Environment:**
```bash
php test_environment_detection.php
```

**Generate License Hash:**
```bash
php generate_license_hash.php
```

**Verify License Hash:**
```bash
php verify_license_hash.php
```

---

**Last Updated:** 2026-03-08  
**Version:** 1.0  
**Status:** ✅ Complete & Tested
