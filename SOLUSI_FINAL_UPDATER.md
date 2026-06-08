# Solusi Final: Masalah Updater dengan Migrasi Database

## Masalah yang Diselesaikan

### Masalah #1: Race Condition Saat Update
**Gejala:**
- Zip berisi migrasi database baru
- Setelah extract file, tapi sebelum migrasi dijalankan
- Ada request lain yang masuk → aplikasi error karena tabel belum ter-update

**Contoh Kasus:**
```
Timeline:
00:00 - Admin upload zip update
00:01 - Extract file selesai (file baru sudah ada)
00:02 - User lain akses aplikasi → ERROR! (tabel belum ada)
00:03 - Auto-migrate selesai (tabel sudah ada)
00:04 - Aplikasi normal
```

**Root Cause:**
- Gap waktu antara extract file dan migrasi database
- Request lain bisa masuk di gap waktu tersebut
- Code baru butuh tabel baru, tapi tabel belum ada

---

### Masalah #2: File Root Tidak Ter-update
**Gejala:**
- Update tampilan yang butuh perubahan `index.php`
- File `index.php` di root tidak ter-include dalam zip
- Untuk root installation → update tidak lengkap

**Contoh Kasus:**
```
Update: Tambah security header di index.php

Struktur Standard (/public):
✅ public/index.php ter-update (OK)
❌ index.php di root tidak ter-update (tapi tidak dipakai, OK)

Struktur Root Installation:
❌ index.php di root tidak ter-update (DIPAKAI! ERROR!)
```

---

## Solusi Lengkap

### Solusi #1: Maintenance Mode Saat Update

#### Implementasi:
```php
// app/Controllers/Admin/Updater.php

public function patchFiles()
{
    try {
        // Step 0: Enable maintenance mode (CRITICAL!)
        $this->enableMaintenanceMode();
        
        // Step 1: Read manifest
        $manifest = $this->readUpdateManifest($zip);
        
        // Step 2: Backup critical files
        $backupPath = $this->backupCriticalFiles();
        
        // Step 3: Extract files
        $zip->extractTo(ROOTPATH);
        $zip->close();
        
        // Step 4: IMMEDIATELY auto-migrate (no gap!)
        $migrateResult = $this->autoMigrateAfterUpdate();
        
        // Step 5: Regenerate security hashes
        $this->regenerateSecurityHashes();
        
        // Step 6: Disable maintenance mode
        $this->disableMaintenanceMode();
        
        return redirect()->back()->with('success', 'Update berhasil!');
        
    } catch (\Exception $e) {
        // Always disable maintenance mode on error
        $this->disableMaintenanceMode();
        return redirect()->back()->with('error', 'Update gagal: ' . $e->getMessage());
    }
}

private function enableMaintenanceMode()
{
    $maintenanceFile = WRITEPATH . '.maintenance';
    $data = [
        'enabled_at' => date('Y-m-d H:i:s'),
        'reason' => 'System update in progress',
        'admin_session' => session()->get('user_id'), // Allow admin to continue
    ];
    file_put_contents($maintenanceFile, json_encode($data));
    log_message('info', 'Maintenance mode enabled');
}

private function disableMaintenanceMode()
{
    $maintenanceFile = WRITEPATH . '.maintenance';
    if (is_file($maintenanceFile)) {
        unlink($maintenanceFile);
        log_message('info', 'Maintenance mode disabled');
    }
}
```

#### Cara Kerja:
```
Timeline BARU (dengan maintenance mode):
00:00 - Admin upload zip update
00:00 - Maintenance mode ON → User lain diblokir
00:01 - Extract file selesai
00:02 - Auto-migrate selesai
00:03 - Maintenance mode OFF → User lain bisa akses
00:04 - Aplikasi normal (tidak ada error!)
```

**Keuntungan:**
- ✅ Tidak ada race condition
- ✅ User lain tidak bisa akses saat update
- ✅ Admin yang update tetap bisa akses
- ✅ Auto-refresh setiap 5 detik untuk user lain

---

### Solusi #2: Maintenance Filter

#### File: `app/Filters/MaintenanceFilter.php`
```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $maintenanceFile = WRITEPATH . '.maintenance';
        
        // Check if maintenance mode is active
        if (!is_file($maintenanceFile)) {
            return; // Not in maintenance mode
        }
        
        // Read maintenance data
        $data = json_decode(file_get_contents($maintenanceFile), true);
        
        // Allow admin who initiated the update
        $currentUserId = session()->get('user_id');
        if ($currentUserId && isset($data['admin_session']) && $currentUserId == $data['admin_session']) {
            return; // Allow admin to continue
        }
        
        // Block all other requests with maintenance page
        return $this->showMaintenancePage($data);
    }
    
    private function showMaintenancePage($data)
    {
        // Beautiful maintenance page with auto-refresh
        // (See full implementation in file)
    }
}
```

#### Registrasi Filter:
```php
// app/Config/Filters.php

public array $aliases = [
    // ... existing filters
    'maintenance' => \App\Filters\MaintenanceFilter::class,
];

public array $globals = [
    'before' => [
        'maintenance', // MUST BE FIRST!
        'csrf',
        'license',
        // ...
    ],
];
```

**Keuntungan:**
- ✅ Otomatis blokir semua request saat maintenance
- ✅ Admin yang update tetap bisa akses
- ✅ Halaman maintenance cantik dengan auto-refresh
- ✅ User tidak perlu manual refresh

---

### Solusi #3: Include File Root dalam Zip

#### Implementasi:
```php
// app/Controllers/Admin/Updater.php - generatePatch()

public function generatePatch()
{
    // ... existing code
    
    // Step 1: Add folders (app, public, modules)
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
    
    // ✅ Step 3: Add update manifest
    $this->addUpdateManifest($zip);
    
    $zip->close();
}
```

**Keuntungan:**
- ✅ File root ter-include dalam zip
- ✅ Support kedua struktur instalasi
- ✅ Update lengkap untuk semua file

---

## Workflow Update Baru

### Sebelum (Bermasalah):
```
1. Admin upload zip
2. Extract file → File baru sudah ada
3. ❌ User lain akses → ERROR (tabel belum ada)
4. Auto-migrate → Tabel sudah ada
5. ✅ Aplikasi normal
```

### Sesudah (Aman):
```
1. Admin upload zip
2. Maintenance mode ON → User lain diblokir
3. Extract file → File baru sudah ada
4. Auto-migrate → Tabel sudah ada
5. Maintenance mode OFF → User lain bisa akses
6. ✅ Aplikasi normal (tidak ada error!)
```

---

## Testing Checklist

### Test #1: Update dengan Migrasi Baru
```bash
# 1. Buat migrasi baru di localhost
php spark make:migration AddTestColumnToUsers

# 2. Edit migrasi
# app/Database/Migrations/2026-02-25-XXXXXX_AddTestColumnToUsers.php
public function up()
{
    $this->forge->addColumn('users', [
        'test_column' => [
            'type' => 'VARCHAR',
            'constraint' => 100,
            'null' => true,
        ],
    ]);
}

# 3. Generate patch di localhost
# Akses: /admin/updater
# Klik: "Download Zip"

# 4. Upload ke server production
# Expected:
# - Maintenance mode ON
# - Extract file
# - Auto-migrate
# - Maintenance mode OFF
# - ✅ Tidak ada error!

# 5. Verify
# Check: Kolom test_column ada di database
# Check: Aplikasi tidak error
# Check: User lain tidak mengalami error saat update
```

### Test #2: Update File Root
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

### Test #3: Maintenance Mode
```bash
# 1. Buka 2 browser:
# - Browser A: Login sebagai admin
# - Browser B: Login sebagai user biasa

# 2. Di Browser A: Upload zip update

# 3. Expected:
# - Browser A: Bisa akses (admin yang update)
# - Browser B: Muncul halaman maintenance
# - Browser B: Auto-refresh setiap 5 detik

# 4. Setelah update selesai:
# - Browser B: Otomatis bisa akses lagi
```

### Test #4: Error Handling
```bash
# 1. Buat migrasi yang error (syntax error)

# 2. Generate patch dan upload

# 3. Expected:
# - Maintenance mode ON
# - Extract berhasil
# - Auto-migrate gagal
# - Maintenance mode OFF (tetap di-disable!)
# - ⚠️ Warning message
# - ✅ Aplikasi tetap bisa diakses

# 4. Fix migrasi manual
# Edit file migrasi di server
# Klik "Jalankan Migrasi"
# Expected: ✅ Berhasil
```

---

## File yang Dimodifikasi

### 1. `app/Controllers/Admin/Updater.php`
- ✅ Tambah `enableMaintenanceMode()`
- ✅ Tambah `disableMaintenanceMode()`
- ✅ Update `patchFiles()` dengan maintenance mode
- ✅ Update `generatePatch()` dengan include root files
- ✅ Tambah error handling untuk disable maintenance mode

### 2. `app/Filters/MaintenanceFilter.php` (NEW)
- ✅ Filter untuk cek maintenance mode
- ✅ Blokir semua request kecuali admin
- ✅ Halaman maintenance cantik dengan auto-refresh

### 3. `app/Config/Filters.php`
- ✅ Registrasi `MaintenanceFilter`
- ✅ Tambah ke `$globals['before']` (MUST BE FIRST!)

---

## Keuntungan Solusi Final

### Keamanan:
- ✅ Tidak ada race condition
- ✅ Tidak ada gap waktu aplikasi broken
- ✅ User tidak bisa akses saat update

### User Experience:
- ✅ Admin tetap bisa akses saat update
- ✅ User lain melihat halaman maintenance cantik
- ✅ Auto-refresh setiap 5 detik
- ✅ Tidak perlu manual refresh

### Reliability:
- ✅ Auto-migrate setelah extract
- ✅ Auto-regenerate security hashes
- ✅ Backup otomatis sebelum update
- ✅ Error handling yang baik

### Compatibility:
- ✅ Support kedua struktur instalasi
- ✅ File root ter-include dalam zip
- ✅ Update lengkap untuk semua file

---

## Kesimpulan

### ✅ Masalah #1: SOLVED
- Race condition diatasi dengan maintenance mode
- Tidak ada gap waktu aplikasi broken
- User tidak mengalami error saat update

### ✅ Masalah #2: SOLVED
- File root ter-include dalam zip
- Support kedua struktur instalasi
- Update lengkap untuk semua file

### ✅ Bonus Features
- Maintenance mode dengan halaman cantik
- Auto-refresh untuk user
- Error handling yang baik
- Backup otomatis

---

## Rekomendasi

### Untuk Pengembang:
1. ✅ Selalu test update di staging dulu
2. ✅ Buat migrasi yang defensif (check if exists)
3. ✅ Monitor log setelah update
4. ✅ Keep backup 7 hari

### Untuk User:
1. ✅ Selalu backup database sebelum update
2. ✅ Update saat traffic rendah (malam hari)
3. ✅ Informasikan user sebelum update
4. ✅ Monitor aplikasi setelah update

---

**Status**: ✅ SOLUSI FINAL LENGKAP  
**Implementation**: ✅ SUDAH DIIMPLEMENTASIKAN  
**Testing**: Required before production  
**Risk Level**: LOW (dengan maintenance mode)

