# DEVELOPER TOOLS - LOCALHOST ONLY

**⚠️ PENTING: File-file ini TIDAK BOLEH diupload ke production!**

---

## 📋 DAFTAR DEVELOPER TOOLS

### 1. Update Package Creator

**File:** `create_update_package.ps1`  
**Status:** ✅ Enhanced & Protected  
**Purpose:** Membuat update package untuk distribusi

**Features:**
- Interactive mode dengan parameter
- 3 mode file selection (manual, git diff, entire folders)
- Auto-generate: CHANGELOG.md, manifest.json, INSTALL.txt
- Calculate SHA256 hash
- Generate SQL INSERT query
- Safety check untuk sensitive files
- Environment detection (requires .dev_environment)

**Usage:**
```powershell
# Interactive mode
.\create_update_package.ps1

# With parameters
.\create_update_package.ps1 -Version "1.0.2" -Title "Fix Hash Bug" -Description "Perbaikan bug" -Critical

# Show help
.\create_update_package.ps1 -Help
```

**Output:**
```
updates/
├── v1.0.2/
│   ├── files/              # Files to update
│   ├── CHANGELOG.md        # Changelog
│   ├── manifest.json       # Manifest
│   └── INSTALL.txt         # Installation guide
├── update_v1.0.2.zip       # ZIP package
└── update_v1.0.2.txt       # SQL query & info
```

**Safety Features:**
- ✅ Requires `.dev_environment` file
- ✅ Detects sensitive files (*.env, debug_*.php, test_*.php, dll)
- ✅ Warns before including sensitive files
- ✅ Interactive confirmation

---

### 2. License Hash Generator

**File:** `generate_license_hash.php`  
**Status:** ✅ Protected  
**Purpose:** Generate hash untuk license-protected files

**Usage:**
```bash
php generate_license_hash.php
```

**Output:**
- `app/Config/.lic_hash` - Hash file (JSON)
- `app/Config/.lic_checksum` - Checksum file

**When to Use:**
- Setelah mengubah file yang di-protect license
- Sebelum create update package
- Setelah fix bugs di Controllers/Models

**Protected Files:**
- Filters/LicenseFilter.php
- Controllers/Activate.php
- Controllers/Admin/License.php
- Libraries/LicenseGuard.php
- Models/LicenseModel.php
- Config/LService.php

**Safety Features:**
- ✅ Requires development environment
- ✅ Shows environment badge
- ✅ Cannot run in production

---

### 3. License Hash Verifier

**File:** `verify_license_hash.php`  
**Status:** ✅ Protected  
**Purpose:** Verify integrity of license hash file

**Usage:**
```bash
php verify_license_hash.php
```

**Output:**
- List of all protected files
- Hash status (OK/MISMATCH/MISSING)
- Summary report

**When to Use:**
- Setelah generate hash
- Sebelum upload ke production
- Troubleshooting license issues

---

### 4. Diagnostic Tools

**Files:**
- `diagnostic.php` - General diagnostic
- `debug_*.php` - Various debug scripts
- `test_*.php` - Test scripts
- `check_*.php` - Check scripts
- `verify_*.php` - Verification scripts

**Status:** ⚠️ Not yet protected (will be updated)

**Purpose:** Troubleshooting dan debugging

**Recommendation:** Add guard to all diagnostic files

---

## 🔒 PROTECTION SYSTEM

### Environment Detection

**File:** `app/Helpers/environment_helper.php`

**Functions:**
```php
is_development_environment()      // Check if dev environment
is_production_environment()       // Check if production
require_development_environment() // Die if not dev
developer_tools_enabled()         // Check if tools enabled
get_environment_name()            // Get env name
environment_badge()               // Get HTML badge
show_developer_tools_warning()    // Show warning if tools in prod
```

**Detection Methods:**
1. `.dev_environment` file exists
2. `CI_ENVIRONMENT === 'development'`
3. `.env` has `CI_ENVIRONMENT=development`
4. Localhost patterns (localhost, 127.0.0.1, .local, .test, .dev)

---

### Guard System

**File:** `_dev_tools_guard.php`

**Usage:**
```php
<?php
// At the top of any developer-only script
require_once __DIR__ . '/_dev_tools_guard.php';

// Your code here...
```

**What it does:**
- Loads environment helper
- Checks if development environment
- Dies with 403 if production
- Shows environment badge

---

### .gitignore Protection

**File:** `.gitignore`

**Protected Patterns:**
```gitignore
# Developer Tools
create_update_package.ps1
create_patch.ps1
updates/

# License Hash Tools
generate_license_hash.php
verify_license_hash.php
calculate_hash.php
regenerate_hash.php

# Diagnostic Tools
diagnostic.php
debug_*.php
test_*.php
check_*.php
verify_*.php
fix_*.php

# Documentation
ANALISIS_*.md
AUDIT_*.md
CHECKLIST_*.md
... (and many more)

# Environment Marker
.dev_environment
```

---

## 📦 PRODUCTION PACKAGE PREPARATION

### Checklist Before Upload:

1. **Verify .gitignore**
   ```bash
   # Check if .gitignore exists
   ls -la .gitignore
   ```

2. **Remove .dev_environment**
   ```bash
   # This file should NOT exist in production
   rm .dev_environment
   ```

3. **Verify No Developer Tools**
   ```bash
   # Check for developer tools
   ls -la create_*.ps1
   ls -la generate_*.php
   ls -la verify_*.php
   ls -la diagnostic.php
   ls -la debug_*.php
   ls -la test_*.php
   ```

4. **Clean Documentation**
   ```bash
   # Remove all .md files except README.md
   ls -la *.md
   ```

5. **Use Cleanup Script**
   ```powershell
   # Use existing cleanup script
   .\cleanup_for_production.ps1
   ```

---

## 🚀 WORKFLOW: CREATE UPDATE PACKAGE

### Step 1: Make Changes
```bash
# Edit files
# Fix bugs
# Add features
```

### Step 2: Test Locally
```bash
# Test all changes
# Verify no errors
```

### Step 3: Regenerate License Hash (if needed)
```bash
php generate_license_hash.php
php verify_license_hash.php
```

### Step 4: Create Update Package
```powershell
.\create_update_package.ps1 -Version "1.0.2" -Title "Fix Hash Bug"
```

### Step 5: Review Package
```bash
# Check files in updates/v1.0.2/files/
# Read CHANGELOG.md
# Verify manifest.json
```

### Step 6: Upload to Update Server
```bash
# Upload: updates/update_v1.0.2.zip → writable/updates/
# Copy SQL from: updates/update_v1.0.2.txt
# Run SQL in phpMyAdmin
```

### Step 7: Activate Update
```sql
UPDATE updates SET is_active = 1 WHERE version = '1.0.2';
```

### Step 8: Test Update Flow
```bash
# Test di localhost
# Test di production
```

---

## ⚠️ SECURITY WARNINGS

### DO NOT:
- ❌ Upload `.dev_environment` to production
- ❌ Upload `create_update_package.ps1` to production
- ❌ Upload `generate_license_hash.php` to production
- ❌ Upload any `test_*.php` files to production
- ❌ Upload any `debug_*.php` files to production
- ❌ Upload any `diagnostic.php` to production
- ❌ Upload documentation files (*.md) to production
- ❌ Commit `.env` to git
- ❌ Share license hash generation tools

### ALWAYS:
- ✅ Use `.gitignore` properly
- ✅ Remove `.dev_environment` before production upload
- ✅ Verify no developer tools in production
- ✅ Use cleanup script before upload
- ✅ Test in localhost first
- ✅ Backup before update
- ✅ Monitor error logs after update

---

## 🔧 TROUBLESHOOTING

### Script says "Not in development environment"

**Solution:**
```bash
# Create .dev_environment file
echo "ENVIRONMENT=development" > .dev_environment
```

### Cannot run create_update_package.ps1

**Solution:**
```powershell
# Check PowerShell execution policy
Get-ExecutionPolicy

# If Restricted, change to RemoteSigned
Set-ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### License hash mismatch after update

**Solution:**
```bash
# Regenerate hash
php generate_license_hash.php

# Verify
php verify_license_hash.php
```

### Developer tools accessible in production

**Solution:**
```bash
# Remove all developer tools
rm create_*.ps1
rm generate_*.php
rm verify_*.php
rm diagnostic.php
rm debug_*.php
rm test_*.php
rm .dev_environment

# Or use cleanup script
.\cleanup_for_production.ps1
```

---

## 📝 NOTES

### Version Numbering:
- **Major.Minor.Patch** (e.g., 1.0.2)
- **Major:** Breaking changes
- **Minor:** New features (backward compatible)
- **Patch:** Bug fixes

### Critical Updates:
- Use `-Critical` flag for security updates
- Forces immediate installation
- Shows warning to users

### File Selection Modes:
1. **Manual:** Select specific files (recommended)
2. **Git Diff:** Auto-detect changed files
3. **Entire Folders:** Include all files in app/, public/, modules/

### Best Practices:
- Always test in localhost first
- Create backup before update
- Document all changes in CHANGELOG
- Use semantic versioning
- Keep update packages small
- Test rollback procedure

---

## 📚 RELATED DOCUMENTATION

- `STATUS_ARSITEKTUR_UPDATE_SYSTEM.md` - Architecture status
- `DIAGRAM_ARSITEKTUR_LENGKAP.txt` - Architecture diagram
- `PENJELASAN_SISTEM_UPDATE_OTOMATIS.md` - Update system explanation
- `QUICK_GUIDE_UPDATE_SYSTEM.txt` - Quick guide
- `.gitignore` - Git ignore rules

---

**Last Updated:** 2026-03-08  
**Version:** 1.0  
**Status:** ✅ Complete & Protected
