# SIMPLE UPDATE GUIDE - ONE CLICK!

**Versi Mudah:** Cukup double-click atau satu command!

---

## 🎯 CARA TERMUDAH: DOUBLE-CLICK

### Windows:

**Double-click file:** `CREATE_UPDATE.bat`

That's it! Script akan:
1. ✅ Auto-detect version dari Constants.php
2. ✅ Auto-increment version (1.0.1 → 1.0.2)
3. ✅ Auto-detect changed files dari git
4. ✅ Auto-regenerate license hash (jika perlu)
5. ✅ Create update package
6. ✅ Update version di Constants.php

---

## 📝 CARA ALTERNATIF: COMMAND LINE

### PowerShell:

```powershell
.\create_update_simple.ps1
```

### Bash (Linux/Mac):

```bash
# Coming soon - untuk sekarang gunakan create_update_package_v2.ps1
```

---

## 🚀 WORKFLOW SUPER SIMPLE

### STEP 1: Edit Files

Edit file yang mau diubah:
- Fix bugs
- Add features
- Update code

**Save semua files!**

---

### STEP 2: Double-Click

**Windows:** Double-click `CREATE_UPDATE.bat`

**PowerShell:** `.\create_update_simple.ps1`

---

### STEP 3: Answer Prompts

Script akan tanya:

```
Enter update title: Fix Hash Bug

Enter description (optional, press Enter to skip): 
Perbaikan bug auto-regenerate hash

Is this a critical update? (y/n): n

Continue? (y/n): y
```

---

### STEP 4: Done!

Output:
```
===============================================================
  UPDATE PACKAGE CREATED SUCCESSFULLY!
===============================================================

Package: update_v1.0.2.zip
Version: 1.0.2
Size: 0.85 MB
Files: 3

Location:
   ZIP: updates/update_v1.0.2.zip
   Info: updates/update_v1.0.2.txt

Ready to deploy!
```

---

## ✨ FITUR AUTO-DETECT

### 1. Auto-Detect Version

Script membaca `app/Config/Constants.php`:
```php
define('APP_VERSION', '1.0.1');
```

Auto-increment ke:
```php
define('APP_VERSION', '1.0.2');
```

---

### 2. Auto-Detect Changed Files

Script menggunakan `git status` untuk detect files yang berubah:

```
Modified:
   app/Controllers/Activate.php
   app/Controllers/Admin/License.php
   app/Config/.lic_hash

Auto-included in package!
```

**Filter otomatis:**
- ✅ Include: `app/`, `public/`, `modules/`
- ❌ Exclude: `.env`, `*.md`, `*.txt`, `test_*`, `debug_*`

---

### 3. Auto-Regenerate License Hash

Jika file license-protected berubah:
- `app/Filters/LicenseFilter.php`
- `app/Controllers/Activate.php`
- `app/Controllers/Admin/License.php`
- `app/Libraries/LicenseGuard.php`
- `app/Models/LicenseModel.php`
- `app/Config/LService.php`

Script otomatis:
1. Run `php generate_license_hash.php`
2. Include `app/Config/.lic_hash` di package

---

### 4. Auto-Update Constants.php

Setelah create package, script otomatis update:
```php
// Before
define('APP_VERSION', '1.0.1');
define('LAST_UPDATE', '2026-03-07 10:00');

// After
define('APP_VERSION', '1.0.2');
define('LAST_UPDATE', '2026-03-08 14:30');
```

---

## 📋 REQUIREMENTS

### 1. Git Installed

**Check:**
```bash
git --version
```

**Install:**
- Windows: https://git-scm.com/download/win
- Linux: `sudo apt install git`
- Mac: `brew install git`

---

### 2. .dev_environment File

**Check:**
```bash
dir .dev_environment    # Windows
ls .dev_environment     # Linux/Mac
```

**Create:**
```bash
echo ENVIRONMENT=development > .dev_environment
```

---

### 3. Changes Saved

Make sure semua perubahan sudah di-save!

---

## 🆚 COMPARISON: Simple vs Manual

### Simple Version (create_update_simple.ps1):

**Pros:**
- ✅ One click / one command
- ✅ Auto-detect version
- ✅ Auto-detect files from git
- ✅ Auto-regenerate hash
- ✅ Auto-update Constants.php
- ✅ Super fast (~2 menit)

**Cons:**
- ⚠️ Requires git
- ⚠️ Must save files first
- ⚠️ Less control over file selection

**Best for:**
- Quick updates
- Bug fixes
- Small changes
- Daily development

---

### Manual Version (create_update_package_v2.ps1):

**Pros:**
- ✅ Full control
- ✅ Manual file selection
- ✅ No git required
- ✅ Can include specific files only

**Cons:**
- ⚠️ More steps
- ⚠️ Manual input required
- ⚠️ Slower (~5 menit)

**Best for:**
- Complex updates
- Selective file inclusion
- When git not available
- First-time users (learning)

---

## 🎬 DEMO: Simple Version

```
C:\xampp\htdocs\cbtku> CREATE_UPDATE.bat

===============================================================
  CBT UPDATE PACKAGE CREATOR - ONE CLICK
===============================================================

Development environment detected

Git detected: git version 2.40.0

Auto-detecting version...
Current version: 1.0.1
New version: 1.0.2

Detecting changed files from git...
Found 3 changed files:
   app/Controllers/Activate.php
   app/Controllers/Admin/License.php
   app/Config/.lic_hash

License-protected files changed. Regenerating hash...
License hash regenerated successfully
Added app/Config/.lic_hash to package

Enter update title: Fix Auto-Regenerate Hash Bug

Enter description (optional, press Enter to skip): 
Perbaikan bug yang menyebabkan loop aktivasi

Is this a critical update? (y/n): n

===============================================================
  CREATING UPDATE PACKAGE
===============================================================

Version: 1.0.2
Title: Fix Auto-Regenerate Hash Bug
Files: 3
Critical: False

Continue? (y/n): y

Created folder: updates/v1.0.2

Copying files...
   Copied: app/Controllers/Activate.php
   Copied: app/Controllers/Admin/License.php
   Copied: app/Config/.lic_hash

Copied 3 files

Creating CHANGELOG...
Created: CHANGELOG.md

Creating manifest...
Created: manifest.json

Creating ZIP...
Created: update_v1.0.2.zip

Calculating hash...
Hash: 7f8e9d0c1b2a3f4e5d6c7b8a9f0e1d2c3b4a5f6e7d8c9b0a1f2e3d4c5b6a7f8e
Size: 0.85 MB

Updating version in Constants.php...
Updated APP_VERSION to 1.0.2

===============================================================
  UPDATE PACKAGE CREATED SUCCESSFULLY!
===============================================================

Package: update_v1.0.2.zip
Version: 1.0.2
Size: 0.85 MB
Files: 3

Location:
   ZIP: updates/update_v1.0.2.zip
   Info: updates/update_v1.0.2.txt

===============================================================
  NEXT STEPS
===============================================================

1. Review: updates/update_v1.0.2.txt
2. Upload: update_v1.0.2.zip to Update Server
3. Register: Run SQL query in phpMyAdmin
4. Test: Update on staging first

Ready to deploy!

Press any key to continue . . .
```

**Total time: ~2 menit!**

---

## 🔧 TROUBLESHOOTING

### Issue: "Git not installed"

**Solution:**
```bash
# Install git
# Windows: https://git-scm.com/download/win
# After install, restart terminal
```

---

### Issue: "No changed files detected"

**Possible causes:**
1. Files not saved
2. Files not in `app/`, `public/`, or `modules/`
3. No changes made

**Solution:**
```bash
# Check git status
git status

# Should show modified files
# If not, make sure files are saved
```

---

### Issue: "Not in development environment"

**Solution:**
```bash
# Create .dev_environment
echo ENVIRONMENT=development > .dev_environment
```

---

### Issue: "Failed to regenerate license hash"

**Solution:**
```bash
# Run manually
php generate_license_hash.php

# Then run script again
.\create_update_simple.ps1
```

---

## 📊 COMPARISON TABLE

| Feature | Simple | Manual |
|---------|--------|--------|
| **Ease of Use** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Speed** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Auto-detect** | ✅ | ❌ |
| **Control** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Git Required** | ✅ | ❌ |
| **Time** | ~2 min | ~5 min |
| **Steps** | 3 | 7 |
| **Best for** | Daily use | Complex updates |

---

## 🎯 RECOMMENDATION

### Use Simple Version When:
- ✅ Making regular updates
- ✅ Fixing bugs
- ✅ Small changes
- ✅ Git available
- ✅ Want speed

### Use Manual Version When:
- ✅ First time creating update
- ✅ Complex selective updates
- ✅ Git not available
- ✅ Need full control
- ✅ Learning the process

---

## 📞 QUICK REFERENCE

**One Click (Windows):**
```
Double-click: CREATE_UPDATE.bat
```

**Command Line:**
```powershell
.\create_update_simple.ps1
```

**Show Help:**
```powershell
.\create_update_simple.ps1 -Help
```

**Manual Mode:**
```powershell
.\create_update_package_v2.ps1
```

---

**Last Updated:** 2026-03-08  
**Version:** 1.0  
**Status:** ✅ Ready to Use

---

## 💡 TIP

Untuk workflow paling efisien:

1. **Daily development:** Use `CREATE_UPDATE.bat` (one click)
2. **Complex updates:** Use `create_update_package_v2.ps1` (manual)
3. **First time:** Read `TUTORIAL_CREATE_UPDATE_PACKAGE.md` (learn)

Pilih yang paling sesuai dengan kebutuhan Anda!
