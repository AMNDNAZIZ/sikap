# 📋 COPY-PASTE CODE - Quick Reference

## 1️⃣ ROUTES (app/Config/Routes.php)

Tambahkan di bagian admin routes:

```php
// ============================================
// ONLINE UPDATE ROUTES
// ============================================
$routes->get('admin/updater/checkOnlineUpdate', 'Admin\Updater::checkOnlineUpdate', ['filter' => 'auth']);
$routes->post('admin/updater/downloadUpdate', 'Admin\Updater::downloadUpdate', ['filter' => 'auth']);
$routes->post('admin/updater/installOnlineUpdate', 'Admin\Updater::installOnlineUpdate', ['filter' => 'auth']);
```

---

## 2️⃣ ENV CONFIGURATION (.env)

Tambahkan di bagian bawah file:

```env
#--------------------------------------------------------------------
# UPDATE SERVER
#--------------------------------------------------------------------
UPDATE_SERVER_URL = https://updatecbt.sakasalika.com
```

---

## 3️⃣ APP VERSION (app/Config/Constants.php)

Tambahkan setelah konstanta lainnya:

```php
/*
|--------------------------------------------------------------------------
| Application Version
|--------------------------------------------------------------------------
*/
defined('APP_VERSION') || define('APP_VERSION', '1.0.0');
defined('LAST_UPDATE') || define('LAST_UPDATE', '2026-03-03 10:00');
```

---

## 4️⃣ BASH COMMANDS

### Create Download Folder

```bash
# Via SSH
mkdir -p writable/updates/downloads
chmod 755 writable/updates/downloads
```

### Via PHP (create file: create_folders.php di root)

```php
<?php
$folders = [
    'writable/updates',
    'writable/updates/downloads'
];

foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
        echo "✅ Created: {$folder}\n";
    } else {
        echo "ℹ️  Already exists: {$folder}\n";
    }
}

echo "\nDone!\n";
```

Jalankan: `php create_folders.php`

---

## 5️⃣ SQL QUERY (Update Server Database)

### Insert Sample Update

```sql
INSERT INTO `updates` (
    `version`, 
    `release_date`, 
    `title`, 
    `description`, 
    `changelog`, 
    `file_name`, 
    `file_size`, 
    `file_hash`, 
    `is_critical`, 
    `is_active`
) VALUES (
    '1.0.1',
    '2026-03-03',
    'Fix Anti-Cheat & Responsive Tables',
    'Perbaikan sistem anti-cheat dan tampilan responsive untuk mobile',
    '{"added":["Responsive tables untuk mobile","DataTables responsive plugin","Online update system"],"fixed":["Anti-cheat violation count reset on refresh","Upload path gambar soal","CSRF token untuk upload"],"changed":["Removed TinyMCE, using CKEditor only","Updated layout dengan responsive CSS"]}',
    'update_v1.0.1.zip',
    0,
    'pending',
    0,
    0
);
```

### Update File Info After Upload

```sql
UPDATE updates 
SET 
    file_size = 1234567,  -- Ganti dengan ukuran file sebenarnya (bytes)
    file_hash = 'sha256_hash_dari_file',  -- Ganti dengan hash SHA256
    is_active = 1
WHERE version = '1.0.1';
```

### Check Updates

```sql
SELECT 
    version,
    release_date,
    title,
    ROUND(file_size / 1024 / 1024, 2) AS size_mb,
    download_count,
    is_active,
    created_at
FROM updates
ORDER BY version DESC;
```

---

## 6️⃣ CALCULATE SHA256 HASH

### Via PHP (create file: calculate_hash.php)

```php
<?php
$file = 'path/to/update_v1.0.1.zip';

if (!file_exists($file)) {
    die("File not found: {$file}\n");
}

$hash = hash_file('sha256', $file);
$size = filesize($file);

echo "File: {$file}\n";
echo "Size: " . number_format($size) . " bytes (" . round($size/1024/1024, 2) . " MB)\n";
echo "SHA256: {$hash}\n";
```

Jalankan: `php calculate_hash.php`

### Via Command Line

**Linux/Mac:**
```bash
sha256sum update_v1.0.1.zip
```

**Windows (PowerShell):**
```powershell
Get-FileHash update_v1.0.1.zip -Algorithm SHA256
```

---

## 7️⃣ TEST API ENDPOINTS

### Via Browser

**Check Update:**
```
https://updatecbt.sakasalika.com/api/check?current_version=1.0.0
```

**Download Update:**
```
https://updatecbt.sakasalika.com/api/download/1.0.1
```

### Via cURL

**Check Update:**
```bash
curl "https://updatecbt.sakasalika.com/api/check?current_version=1.0.0"
```

**Download Update:**
```bash
curl -O "https://updatecbt.sakasalika.com/api/download/1.0.1"
```

### Via PHP (create file: test_api.php)

```php
<?php
$serverUrl = 'https://updatecbt.sakasalika.com';
$currentVersion = '1.0.0';

// Test Check Update
echo "Testing Check Update API...\n";
$response = file_get_contents("{$serverUrl}/api/check?current_version={$currentVersion}");
$data = json_decode($response, true);

echo "Response:\n";
print_r($data);

if ($data['has_update']) {
    echo "\n✅ Update available: {$data['latest_version']}\n";
    echo "Title: {$data['title']}\n";
    echo "Size: " . round($data['file_size']/1024/1024, 2) . " MB\n";
} else {
    echo "\nℹ️  No update available\n";
}
```

Jalankan: `php test_api.php`

---

## 8️⃣ VERIFY INSTALLATION

### Check Routes

Create file: `check_routes.php` di root

```php
<?php
require 'vendor/autoload.php';

$routes = \Config\Services::routes();
$routes->loadRoutes();

$allRoutes = $routes->getRoutes();

echo "Checking Online Update Routes:\n\n";

$requiredRoutes = [
    'admin/updater/checkOnlineUpdate',
    'admin/updater/downloadUpdate',
    'admin/updater/installOnlineUpdate'
];

foreach ($requiredRoutes as $route) {
    if (isset($allRoutes[$route])) {
        echo "✅ {$route}\n";
    } else {
        echo "❌ {$route} - NOT FOUND\n";
    }
}
```

Jalankan: `php check_routes.php`

### Check ENV

Create file: `check_env.php` di root

```php
<?php
require 'vendor/autoload.php';

echo "Checking Environment Configuration:\n\n";

$updateServerUrl = env('UPDATE_SERVER_URL');

if ($updateServerUrl) {
    echo "✅ UPDATE_SERVER_URL: {$updateServerUrl}\n";
} else {
    echo "❌ UPDATE_SERVER_URL: NOT SET\n";
}

if (defined('APP_VERSION')) {
    echo "✅ APP_VERSION: " . APP_VERSION . "\n";
} else {
    echo "❌ APP_VERSION: NOT DEFINED\n";
}

if (defined('LAST_UPDATE')) {
    echo "✅ LAST_UPDATE: " . LAST_UPDATE . "\n";
} else {
    echo "❌ LAST_UPDATE: NOT DEFINED\n";
}
```

Jalankan: `php check_env.php`

### Check Folders

Create file: `check_folders.php` di root

```php
<?php
echo "Checking Required Folders:\n\n";

$folders = [
    'writable/updates',
    'writable/updates/downloads',
    'writable/backups'
];

foreach ($folders as $folder) {
    if (is_dir($folder)) {
        $writable = is_writable($folder) ? '✅ Writable' : '❌ Not Writable';
        echo "✅ {$folder} - {$writable}\n";
    } else {
        echo "❌ {$folder} - NOT EXISTS\n";
    }
}
```

Jalankan: `php check_folders.php`

---

## 9️⃣ DEBUGGING

### Enable Debug Mode

Edit `.env`:

```env
CI_ENVIRONMENT = development

# Show errors
app.forceGlobalSecureRequests = false
```

### Check Logs

**Client logs:**
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

**Server logs:**
```bash
tail -f public/updatecbt/error.log
tail -f public/updatecbt/logs/app.log
```

### Test Update Flow

Create file: `test_update_flow.php` di root

```php
<?php
require 'vendor/autoload.php';

echo "Testing Update Flow...\n\n";

// Step 1: Check Update
echo "1. Checking for updates...\n";
$updateServerUrl = env('UPDATE_SERVER_URL');
$currentVersion = APP_VERSION ?? '1.0.0';

$ch = curl_init("{$updateServerUrl}/api/check?current_version={$currentVersion}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "   ✅ Server responded: {$httpCode}\n";
    $data = json_decode($response, true);
    
    if ($data['has_update']) {
        echo "   ✅ Update available: {$data['latest_version']}\n";
        echo "   📦 File size: " . round($data['file_size']/1024/1024, 2) . " MB\n";
        echo "   🔗 Download URL: {$data['download_url']}\n";
    } else {
        echo "   ℹ️  No update available\n";
    }
} else {
    echo "   ❌ Server error: {$httpCode}\n";
}

// Step 2: Check Folders
echo "\n2. Checking folders...\n";
$downloadDir = WRITEPATH . 'updates/downloads/';
if (is_dir($downloadDir) && is_writable($downloadDir)) {
    echo "   ✅ Download folder ready\n";
} else {
    echo "   ❌ Download folder not ready\n";
}

// Step 3: Check ZipArchive
echo "\n3. Checking ZipArchive...\n";
if (class_exists('ZipArchive')) {
    echo "   ✅ ZipArchive available\n";
} else {
    echo "   ❌ ZipArchive not available\n";
}

echo "\nTest completed!\n";
```

Jalankan: `php test_update_flow.php`

---

## 🔟 QUICK COMMANDS SUMMARY

```bash
# Create folders
mkdir -p writable/updates/downloads && chmod 755 writable/updates/downloads

# Calculate hash
sha256sum update_v1.0.1.zip

# Test API
curl "https://updatecbt.sakasalika.com/api/check?current_version=1.0.0"

# Check logs
tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Check permissions
ls -la writable/updates/
```

---

**Status:** Ready to Copy-Paste! 📋
