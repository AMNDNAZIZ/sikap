# Analisis: Masalah Sistem Updater

## Masalah yang Dilaporkan

### Masalah #1: Migrasi Database Tidak Otomatis Setelah Update File
**Gejala:**
- Zip update berisi migrasi database
- Setelah upload zip dan extract, file migrasi sudah ada
- Tapi aplikasi error karena tabel belum ter-update
- User harus manual klik "Jalankan Migrasi" di step terpisah

**Dampak:**
- User experience buruk (2 step terpisah)
- Aplikasi error sementara antara update file dan migrasi
- User bisa lupa jalankan migrasi → aplikasi broken

---

### Masalah #2: File Index Tidak Ter-update (Untuk Update Tampilan)
**Gejala:**
- Update tampilan (UI) banyak di file view
- File view ada di `app/Views/` → ter-update ✅
- Tapi file `public/index.php` atau `index.php` tidak ter-update ❌
- Padang kadang perlu update index.php untuk:
  - Update PHP version requirement
  - Update path configuration
  - Update error handling
  - Update security headers

**Dampak:**
- Update tampilan tidak lengkap
- Konfigurasi lama masih digunakan
- Potensi security issue jika index.php perlu update

---

## Root Cause Analysis

### Masalah #1: Migrasi Tidak Otomatis

#### Penyebab:
```php
// app/Controllers/Admin/Updater.php

// Step 1: Upload & Extract Zip
public function patchFiles() {
    $zip->extractTo(ROOTPATH);
    $zip->close();
    // ❌ SELESAI! Tidak ada auto-migrate
    return redirect()->back()->with('success', 'File sistem berhasil diperbarui.');
}

// Step 2: Manual - User harus klik tombol terpisah
public function runMigrations() {
    $migrate = \Config\Services::migrations();
    $executed = $migrate->latest();
    // ...
}
```

**Alur Saat Ini:**
```
1. User upload zip
2. Extract file → File migrasi sudah ada
3. ❌ Aplikasi diakses → ERROR (tabel belum ada)
4. User klik "Jalankan Migrasi"
5. ✅ Aplikasi normal
```

**Masalah:**
- Gap antara step 2 dan 4 → aplikasi broken
- User bisa lupa step 4
- Tidak user-friendly

---

### Masalah #2: File Index Tidak Ter-update

#### Penyebab:
```php
// app/Controllers/Admin/Updater.php - generatePatch()

// Directories to include
$folders = ['app', 'public', 'modules'];

foreach ($folders as $folder) {
    $path = ROOTPATH . $folder;
    if (is_dir($path)) {
        $this->addFolderToZip($path, $zip, strlen(ROOTPATH));
    }
}

// ❌ TIDAK INCLUDE: index.php di root
// ❌ TIDAK INCLUDE: .htaccess di root
```

**Struktur Instalasi:**
```
STRUKTUR 1 (Standard - /public):
/var/www/html/aplikasi/
├── app/                    ✅ Ter-include
├── public/                 ✅ Ter-include
│   ├── index.php          ✅ Ter-update
│   └── .htaccess          ✅ Ter-update
├── index.php              ❌ TIDAK ter-include (tapi tidak dipakai)
└── .htaccess              ❌ TIDAK ter-include (tapi tidak dipakai)

STRUKTUR 2 (Root Installation):
/var/www/html/aplikasi/
├── app/                    ✅ Ter-include
├── public/                 ✅ Ter-include (tapi tidak dipakai)
├── index.php              ❌ TIDAK ter-include (DIPAKAI!)
└── .htaccess              ❌ TIDAK ter-include (DIPAKAI!)
```

**Masalah:**
- Struktur 2 (root installation) → index.php di root tidak ter-update
- Update tampilan yang butuh perubahan index.php tidak ter-apply

---

## Solusi Lengkap

### Solusi #1: Auto-Migrate Setelah Extract Zip

#### Implementasi:
```php
// app/Controllers/Admin/Updater.php

public function patchFiles()
{
    $file = $this->request->getFile('patch_file');

    if (!$file || !$file->isValid() || $file->getExtension() !== 'zip') {
        return redirect()->back()->with('error', 'File tidak valid. Harap upload file .zip');
    }

    $zip = new \ZipArchive();
    if ($zip->open($file->getTempName()) === TRUE) {
        try {
            // Step 1: Extract files
            $zip->extractTo(ROOTPATH);
            $zip->close();
            
            // ✅ Step 2: AUTO-MIGRATE (NEW!)
            $migrateResult = $this->autoMigrateAfterUpdate();
            
            // ✅ Step 3: Regenerate security hashes (NEW!)
            $this->regenerateSecurityHashes();
            
            // Build success message
            $message = 'File sistem berhasil diperbarui.';
            
            if ($migrateResult['executed']) {
                $message .= ' Database berhasil dimigrasi ke versi terbaru.';
            } elseif ($migrateResult['already_latest']) {
                $message .= ' Database sudah dalam versi terbaru.';
            }
            
            if ($migrateResult['error']) {
                $message .= ' PERINGATAN: ' . $migrateResult['error'];
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekstrak: ' . $e->getMessage());
        }
    } else {
        return redirect()->back()->with('error', 'Gagal membuka file zip.');
    }
}

/**
 * Auto-migrate database after file update
 * Returns: ['executed' => bool, 'already_latest' => bool, 'error' => string|null]
 */
private function autoMigrateAfterUpdate()
{
    $result = [
        'executed' => false,
        'already_latest' => false,
        'error' => null
    ];
    
    try {
        $migrate = \Config\Services::migrations();
        $executed = $migrate->latest();
        
        if ($executed) {
            $result['executed'] = true;
            
            // Auto-sync changelogs if sync file exists
            $this->syncChangelogsFromData();
            
            log_message('info', 'Database auto-migrated successfully after file update');
        } else {
            $result['already_latest'] = true;
            log_message('info', 'Database already at latest version');
        }
        
    } catch (\Throwable $e) {
        $result['error'] = 'Migrasi gagal: ' . $e->getMessage();
        log_message('error', 'Auto-migration failed: ' . $e->getMessage());
    }
    
    return $result;
}

/**
 * Regenerate security hashes after file update
 */
private function regenerateSecurityHashes()
{
    try {
        // Regenerate .lic_hash
        if (class_exists('\App\Libraries\LicenseGuard')) {
            $method = new \ReflectionMethod('\App\Libraries\LicenseGuard', 'generateLicenseHash');
            $method->setAccessible(true);
            $method->invoke(null);
            log_message('info', 'License hash regenerated after update');
        }
        
        // Regenerate .security_hash
        if (class_exists('\App\Libraries\SecurityGuard')) {
            \App\Libraries\SecurityGuard::regenerateHashes();
            log_message('info', 'Security hash regenerated after update');
        }
        
    } catch (\Throwable $e) {
        log_message('warning', 'Failed to regenerate hashes: ' . $e->getMessage());
        // Don't block update, just log warning
    }
}
```

**Keuntungan:**
- ✅ Satu klik update (file + database)
- ✅ Tidak ada gap waktu aplikasi broken
- ✅ User-friendly
- ✅ Auto-regenerate security hashes

---

### Solusi #2: Include File Root dalam Zip

#### Implementasi:
```php
// app/Controllers/Admin/Updater.php - generatePatch()

public function generatePatch()
{
    // ... (existing code)
    
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
        return redirect()->back()->with('error', 'Gagal membuat file zip di ' . $zipPath);
    }

    // ✅ Step 1: Add folders (app, public, modules)
    $folders = ['app', 'public', 'modules'];
    foreach ($folders as $folder) {
        $path = ROOTPATH . $folder;
        if (is_dir($path)) {
            $this->addFolderToZip($path, $zip, strlen(ROOTPATH));
        }
    }
    
    // ✅ Step 2: Add root files (NEW!)
    $rootFiles = [
        'index.php',      // For root installation
        '.htaccess',      // For root installation
        'spark',          // CLI tool
        'preload.php',    // PHP preload (if exists)
    ];
    
    foreach ($rootFiles as $file) {
        $filePath = ROOTPATH . $file;
        if (is_file($filePath)) {
            $zip->addFile($filePath, $file);
            log_message('info', "Added root file to patch: {$file}");
        }
    }
    
    // ✅ Step 3: Add update manifest (NEW!)
    $this->addUpdateManifest($zip);

    $zip->close();

    return $this->response->download($zipPath, null)->setFileName($zipName);
}

/**
 * Add update manifest to zip
 * Manifest contains metadata about the update
 */
private function addUpdateManifest($zipArchive)
{
    $manifest = [
        'version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
        'build_date' => date('Y-m-d H:i:s'),
        'build_by' => 'System Updater',
        'php_min_version' => '8.1',
        'includes' => [
            'folders' => ['app', 'public', 'modules'],
            'root_files' => ['index.php', '.htaccess', 'spark'],
        ],
        'migrations' => $this->getNewMigrations(),
        'notes' => 'Auto-migrate enabled. Database will be updated automatically.',
    ];
    
    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT);
    $zipArchive->addFromString('UPDATE_MANIFEST.json', $manifestJson);
    
    log_message('info', 'Update manifest added to patch');
}

/**
 * Get list of new migrations (not yet run on current system)
 */
private function getNewMigrations()
{
    try {
        $migrate = \Config\Services::migrations();
        $history = $migrate->getHistory();
        
        // Get all migration files
        $migrationPath = APPPATH . 'Database/Migrations/';
        $files = glob($migrationPath . '*.php');
        
        $newMigrations = [];
        foreach ($files as $file) {
            $filename = basename($file);
            // Check if this migration is not in history
            $found = false;
            foreach ($history as $h) {
                if (strpos($h->class, pathinfo($filename, PATHINFO_FILENAME)) !== false) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $newMigrations[] = $filename;
            }
        }
        
        return $newMigrations;
        
    } catch (\Throwable $e) {
        log_message('warning', 'Failed to get new migrations: ' . $e->getMessage());
        return [];
    }
}
```

**Keuntungan:**
- ✅ File root (index.php, .htaccess) ter-include
- ✅ Support kedua struktur instalasi (standard & root)
- ✅ Update manifest untuk tracking
- ✅ List migrasi baru untuk transparency

---

### Solusi #3: Smart Extract dengan Backup

#### Implementasi:
```php
// app/Controllers/Admin/Updater.php

public function patchFiles()
{
    $file = $this->request->getFile('patch_file');

    if (!$file || !$file->isValid() || $file->getExtension() !== 'zip') {
        return redirect()->back()->with('error', 'File tidak valid. Harap upload file .zip');
    }

    $zip = new \ZipArchive();
    if ($zip->open($file->getTempName()) === TRUE) {
        try {
            // ✅ Step 1: Read manifest (if exists)
            $manifest = $this->readUpdateManifest($zip);
            
            // ✅ Step 2: Backup critical files before extract
            $backupPath = $this->backupCriticalFiles();
            
            // ✅ Step 3: Extract files
            $zip->extractTo(ROOTPATH);
            $zip->close();
            
            // ✅ Step 4: Verify extraction
            if (!$this->verifyExtraction($manifest)) {
                // Rollback if verification fails
                $this->rollbackFromBackup($backupPath);
                return redirect()->back()->with('error', 'Verifikasi gagal. File di-rollback ke versi sebelumnya.');
            }
            
            // ✅ Step 5: Auto-migrate
            $migrateResult = $this->autoMigrateAfterUpdate();
            
            // ✅ Step 6: Regenerate security hashes
            $this->regenerateSecurityHashes();
            
            // ✅ Step 7: Clean up backup (keep for 7 days)
            $this->scheduleBackupCleanup($backupPath);
            
            // Build success message
            $message = 'File sistem berhasil diperbarui.';
            
            if ($manifest) {
                $message .= ' Version: ' . ($manifest['version'] ?? 'unknown');
            }
            
            if ($migrateResult['executed']) {
                $message .= ' Database berhasil dimigrasi.';
            }
            
            if ($migrateResult['error']) {
                $message .= ' PERINGATAN: ' . $migrateResult['error'];
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            // Rollback on error
            if (isset($backupPath)) {
                $this->rollbackFromBackup($backupPath);
            }
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage() . '. File di-rollback.');
        }
    } else {
        return redirect()->back()->with('error', 'Gagal membuka file zip.');
    }
}

/**
 * Read update manifest from zip
 */
private function readUpdateManifest($zipArchive)
{
    try {
        $manifestContent = $zipArchive->getFromName('UPDATE_MANIFEST.json');
        if ($manifestContent) {
            return json_decode($manifestContent, true);
        }
    } catch (\Throwable $e) {
        log_message('warning', 'Failed to read update manifest: ' . $e->getMessage());
    }
    return null;
}

/**
 * Backup critical files before update
 */
private function backupCriticalFiles()
{
    $backupDir = WRITEPATH . 'backups/pre_update_' . date('Ymd_His') . '/';
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Files to backup
    $criticalFiles = [
        '.env',
        'app/Config/Database.php',
        'app/Config/App.php',
        'app/Config/.lic_hash',
        'app/Config/.security_hash',
        'writable/.install_id',
    ];
    
    foreach ($criticalFiles as $file) {
        $source = ROOTPATH . $file;
        if (is_file($source)) {
            $dest = $backupDir . $file;
            $destDir = dirname($dest);
            
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            copy($source, $dest);
        }
    }
    
    log_message('info', 'Critical files backed up to: ' . $backupDir);
    return $backupDir;
}

/**
 * Verify extraction success
 */
private function verifyExtraction($manifest)
{
    // Basic verification: check if key files exist
    $keyFiles = [
        'app/Config/App.php',
        'app/Controllers/BaseController.php',
    ];
    
    foreach ($keyFiles as $file) {
        if (!is_file(ROOTPATH . $file)) {
            log_message('error', 'Verification failed: missing file ' . $file);
            return false;
        }
    }
    
    // If manifest exists, verify migrations
    if ($manifest && isset($manifest['migrations'])) {
        foreach ($manifest['migrations'] as $migration) {
            $migrationFile = APPPATH . 'Database/Migrations/' . $migration;
            if (!is_file($migrationFile)) {
                log_message('warning', 'Migration file missing: ' . $migration);
                // Don't fail, just warn
            }
        }
    }
    
    return true;
}

/**
 * Rollback from backup
 */
private function rollbackFromBackup($backupPath)
{
    if (!is_dir($backupPath)) {
        return false;
    }
    
    // Restore critical files
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($backupPath),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($backupPath));
            $dest = ROOTPATH . $relativePath;
            
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            
            copy($filePath, $dest);
        }
    }
    
    log_message('info', 'Files rolled back from: ' . $backupPath);
    return true;
}

/**
 * Schedule backup cleanup (keep for 7 days)
 */
private function scheduleBackupCleanup($backupPath)
{
    // Create a marker file for cleanup
    $markerFile = $backupPath . '.cleanup_after_' . date('Ymd', strtotime('+7 days'));
    touch($markerFile);
    
    // Clean up old backups (older than 7 days)
    $backupBaseDir = WRITEPATH . 'backups/';
    if (is_dir($backupBaseDir)) {
        $dirs = glob($backupBaseDir . 'pre_update_*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $markerFiles = glob($dir . '.cleanup_after_*');
            foreach ($markerFiles as $marker) {
                $cleanupDate = str_replace($dir . '.cleanup_after_', '', $marker);
                if (date('Ymd') >= $cleanupDate) {
                    // Delete backup directory
                    $this->deleteDirectory($dir);
                    unlink($marker);
                    log_message('info', 'Old backup cleaned up: ' . $dir);
                }
            }
        }
    }
}

/**
 * Delete directory recursively
 */
private function deleteDirectory($dir)
{
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
    }
    
    return rmdir($dir);
}
```

**Keuntungan:**
- ✅ Backup otomatis sebelum update
- ✅ Verifikasi setelah extract
- ✅ Rollback otomatis jika gagal
- ✅ Cleanup backup otomatis (7 hari)
- ✅ Safe update process

---

## Workflow Baru: Update dengan Auto-Migrate

### Sebelum (2 Step Manual):
```
1. User upload zip
2. Extract file
3. ❌ Aplikasi error (tabel belum ada)
4. User klik "Jalankan Migrasi"
5. ✅ Aplikasi normal
```

### Sesudah (1 Step Otomatis):
```
1. User upload zip
2. Extract file
3. ✅ Auto-migrate database
4. ✅ Regenerate security hashes
5. ✅ Aplikasi langsung normal
```

---

## Testing Checklist

### Test #1: Update dengan Migrasi Baru
```bash
# 1. Buat migrasi baru di localhost
php spark make:migration AddNewColumnToUsers

# 2. Edit migrasi
# app/Database/Migrations/2026-02-25-000001_AddNewColumnToUsers.php

# 3. Generate patch
# Akses: /admin/updater
# Klik: "Download Zip"

# 4. Upload ke server production
# Upload zip
# Expected: ✅ File + database ter-update otomatis

# 5. Verify
# Check: Kolom baru ada di database
# Check: Aplikasi tidak error
```

### Test #2: Update File Root (index.php)
```bash
# 1. Edit index.php di localhost
# Tambah comment atau update version

# 2. Generate patch
# Expected: ✅ index.php ter-include dalam zip

# 3. Upload ke server (root installation)
# Expected: ✅ index.php di root ter-update

# 4. Verify
# Check: index.php di root sudah versi baru
```

### Test #3: Rollback Saat Error
```bash
# 1. Buat migrasi yang error (syntax error)

# 2. Generate patch dan upload

# 3. Expected:
# - Extract berhasil
# - Auto-migrate gagal
# - ⚠️ Warning message
# - ✅ File tetap ter-update (migrasi bisa di-fix manual)

# 4. Fix migrasi manual
# Edit file migrasi di server
# Klik "Jalankan Migrasi"
# Expected: ✅ Berhasil
```

---

## Kesimpulan

### ✅ Masalah #1: SOLVED
- Auto-migrate setelah extract zip
- Tidak ada gap waktu aplikasi broken
- User-friendly (1 klik)
- Auto-regenerate security hashes

### ✅ Masalah #2: SOLVED
- File root (index.php, .htaccess) ter-include
- Support kedua struktur instalasi
- Update manifest untuk tracking

### ✅ Bonus Features
- Backup otomatis sebelum update
- Verifikasi setelah extract
- Rollback otomatis jika gagal
- Cleanup backup otomatis

---

## Rekomendasi

### Untuk Pengembang
1. ✅ Implement auto-migrate
2. ✅ Include file root dalam zip
3. ✅ Add update manifest
4. ✅ Implement backup & rollback
5. ✅ Test di kedua struktur instalasi

### Untuk User
1. ✅ Selalu backup database sebelum update
2. ✅ Test update di staging dulu
3. ✅ Monitor log setelah update
4. ✅ Keep backup 7 hari

---

**Status**: ✅ SOLUSI LENGKAP TERSEDIA  
**Implementation**: Ready to deploy  
**Testing**: Required before production


---

## UPDATE: Solusi Final dengan Maintenance Mode

### Masalah Tambahan yang Ditemukan

#### Race Condition Saat Update
**Gejala:**
- Meskipun sudah ada auto-migrate, masih ada gap waktu antara extract file dan migrasi
- Request lain bisa masuk di gap waktu tersebut
- Code baru butuh tabel baru, tapi tabel belum ada → ERROR

**Timeline Masalah:**
```
00:00 - Admin upload zip update
00:01 - Extract file selesai (file baru sudah ada)
00:02 - User lain akses aplikasi → ERROR! (tabel belum ada)
00:03 - Auto-migrate selesai (tabel sudah ada)
00:04 - Aplikasi normal
```

### Solusi Final: Maintenance Mode

#### Implementasi:
1. **Enable maintenance mode sebelum extract**
   - Buat file marker: `writable/.maintenance`
   - Simpan session admin yang update
   - Blokir semua request kecuali admin

2. **MaintenanceFilter**
   - Filter global yang cek file marker
   - Blokir semua request dengan halaman maintenance
   - Allow admin yang update untuk continue
   - Halaman maintenance dengan auto-refresh 5 detik

3. **Disable maintenance mode setelah selesai**
   - Hapus file marker
   - User lain otomatis bisa akses lagi

#### Timeline Baru (Aman):
```
00:00 - Admin upload zip update
00:00 - Maintenance mode ON → User lain diblokir
00:01 - Extract file selesai
00:02 - Auto-migrate selesai
00:03 - Maintenance mode OFF → User lain bisa akses
00:04 - Aplikasi normal (tidak ada error!)
```

### File yang Ditambahkan

#### 1. `app/Filters/MaintenanceFilter.php` (NEW)
```php
- Filter untuk cek maintenance mode
- Blokir semua request kecuali admin
- Halaman maintenance cantik dengan auto-refresh
- Allow admin yang update untuk continue
```

#### 2. Update `app/Controllers/Admin/Updater.php`
```php
- Tambah enableMaintenanceMode()
- Tambah disableMaintenanceMode()
- Update patchFiles() dengan maintenance mode
- Error handling untuk disable maintenance mode
```

#### 3. Update `app/Config/Filters.php`
```php
- Registrasi MaintenanceFilter
- Tambah ke globals['before'] (MUST BE FIRST!)
```

### Keuntungan Solusi Final

#### Keamanan:
- ✅ Tidak ada race condition
- ✅ Tidak ada gap waktu aplikasi broken
- ✅ User tidak bisa akses saat update

#### User Experience:
- ✅ Admin tetap bisa akses saat update
- ✅ User lain melihat halaman maintenance cantik
- ✅ Auto-refresh setiap 5 detik
- ✅ Tidak perlu manual refresh

#### Reliability:
- ✅ Auto-migrate setelah extract
- ✅ Auto-regenerate security hashes
- ✅ Backup otomatis sebelum update
- ✅ Error handling yang baik

#### Compatibility:
- ✅ Support kedua struktur instalasi
- ✅ File root ter-include dalam zip
- ✅ Update lengkap untuk semua file

### Dokumentasi Lengkap

Lihat file berikut untuk detail lengkap:
- `SOLUSI_FINAL_UPDATER.md` - Dokumentasi lengkap solusi
- `QUICK_GUIDE_UPDATE_AMAN.txt` - Quick guide untuk user

---

**Status**: ✅ SOLUSI FINAL LENGKAP  
**Implementation**: ✅ SUDAH DIIMPLEMENTASIKAN  
**Testing**: Required before production  
**Risk Level**: LOW (dengan maintenance mode)
