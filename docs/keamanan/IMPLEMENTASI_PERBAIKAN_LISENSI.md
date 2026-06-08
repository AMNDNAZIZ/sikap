# Implementasi Perbaikan Sistem Lisensi

Panduan step-by-step untuk mengimplementasikan perbaikan keamanan sistem lisensi.

---

## 🚀 Quick Start: Perbaikan Prioritas Tinggi

Implementasi 3 perbaikan kritis yang bisa dilakukan dalam 1-2 hari:

### 1. Anti-Tampering Protection (30 menit)
### 2. Encrypted Config Storage (1 jam)
### 3. Enhanced Validation (1 jam)

---

## 📋 Fase 1: Anti-Tampering Protection

### Step 1.1: Tambah Self-Integrity Check

Buat file baru: `app/Libraries/SecurityGuard.php`

```php
<?php

namespace App\Libraries;

class SecurityGuard
{
    /**
     * Verify that critical files haven't been modified
     */
    public static function verifyCriticalFiles()
    {
        $criticalFiles = [
            'Helpers/license_helper.php',
            'Libraries/LicenseGuard.php',
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Config/Filters.php'
        ];
        
        $hashFile = APPPATH . 'Config/.security_hash';
        
        if (!file_exists($hashFile)) {
            // First run - generate hashes
            self::generateSecurityHashes();
            return true;
        }
        
        $expectedHashes = json_decode(file_get_contents($hashFile), true);
        
        foreach ($criticalFiles as $file) {
            $path = APPPATH . $file;
            
            if (!file_exists($path)) {
                log_message('critical', "Critical file missing: {$file}");
                return false;
            }
            
            $currentHash = hash_file('sha256', $path);
            
            if (!isset($expectedHashes[$file]) || $currentHash !== $expectedHashes[$file]) {
                log_message('critical', "File tampering detected: {$file}");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate security hashes for critical files
     */
    public static function generateSecurityHashes()
    {
        $criticalFiles = [
            'Helpers/license_helper.php',
            'Libraries/LicenseGuard.php',
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Config/Filters.php'
        ];
        
        $hashes = [];
        
        foreach ($criticalFiles as $file) {
            $path = APPPATH . $file;
            if (file_exists($path)) {
                $hashes[$file] = hash_file('sha256', $path);
            }
        }
        
        $hashFile = APPPATH . 'Config/.security_hash';
        file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));
        
        log_message('info', 'Security hashes generated for ' . count($hashes) . ' files');
    }
    
    /**
     * Check if license filter is active
     */
    public static function isLicenseFilterActive()
    {
        $config = config('Filters');
        
        if (!isset($config->globals['before'])) {
            return false;
        }
        
        return in_array('license', $config->globals['before']);
    }
    
    /**
     * Comprehensive security check
     */
    public static function performSecurityCheck()
    {
        // 1. Check file integrity
        if (!self::verifyCriticalFiles()) {
            return [
                'status' => false,
                'error' => 'CRITICAL_FILE_MODIFIED'
            ];
        }
        
        // 2. Check filter status
        if (!self::isLicenseFilterActive()) {
            return [
                'status' => false,
                'error' => 'LICENSE_FILTER_DISABLED'
            ];
        }
        
        // 3. Check environment
        if (!file_exists(APPPATH . '../.env')) {
            return [
                'status' => false,
                'error' => 'ENV_FILE_MISSING'
            ];
        }
        
        return ['status' => true];
    }
}
```

### Step 1.2: Integrasikan ke LicenseGuard

Edit file: `app/Libraries/LicenseGuard.php`

Tambahkan di awal method `verify()`:

```php
public static function verify()
{
    // 0️⃣ Skip validation for activation, login, and static files
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $skip = ['activate', 'login', 'assets', 'favicon.ico'];
    foreach ($skip as $s) {
        if (strpos($uri, $s) !== false)
            return;
    }

    // 🛡️ NEW: Security check
    $securityCheck = \App\Libraries\SecurityGuard::performSecurityCheck();
    if (!$securityCheck['status']) {
        self::lock("SECURITY VIOLATION: " . $securityCheck['error']);
    }

    helper('license');

    // ... existing code ...
}
```

### Step 1.3: Generate Hash Pertama Kali

Buat route temporary untuk generate hash:

```php
// app/Config/Routes.php
$routes->get('admin/generate-security-hash', function() {
    if (!is_cli() && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
        die('Access denied');
    }
    
    \App\Libraries\SecurityGuard::generateSecurityHashes();
    echo "Security hashes generated successfully!";
});
```

Akses: `http://localhost/siakad/admin/generate-security-hash`

**PENTING:** Hapus route ini setelah generate!

---

## 📋 Fase 2: Encrypted Config Storage

### Step 2.1: Buat Secure Config Manager

Buat file: `app/Libraries/SecureConfig.php`

```php
<?php

namespace App\Libraries;

class SecureConfig
{
    private static $decryptedConfig = null;
    
    /**
     * Get secure configuration value
     */
    public static function get($key)
    {
        if (self::$decryptedConfig === null) {
            self::loadConfig();
        }
        
        return self::$decryptedConfig[$key] ?? null;
    }
    
    /**
     * Load and decrypt configuration
     */
    private static function loadConfig()
    {
        $configFile = APPPATH . 'Config/.secure_config';
        
        if (!file_exists($configFile)) {
            // Fallback to .env for backward compatibility
            self::$decryptedConfig = [
                'license_server_url' => base64_decode(env('license.serverUrl', '')),
                'license_hash_secret' => base64_decode(env('license.hashSecret', '')),
                'license_encryption_key' => env('license.encryptionKey', '')
            ];
            return;
        }
        
        $encrypted = file_get_contents($configFile);
        $decrypted = self::decrypt($encrypted);
        self::$decryptedConfig = json_decode($decrypted, true);
    }
    
    /**
     * Encrypt configuration and save
     */
    public static function saveConfig($config)
    {
        $json = json_encode($config);
        $encrypted = self::encrypt($json);
        
        $configFile = APPPATH . 'Config/.secure_config';
        file_put_contents($configFile, $encrypted);
        
        // Also update .gitignore
        $gitignore = APPPATH . 'Config/.gitignore';
        $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';
        
        if (strpos($content, '.secure_config') === false) {
            file_put_contents($gitignore, $content . "\n.secure_config\n");
        }
    }
    
    /**
     * Encrypt data using machine-specific key
     */
    private static function encrypt($data)
    {
        $key = self::getEncryptionKey();
        $iv = random_bytes(16);
        
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data using machine-specific key
     */
    private static function decrypt($data)
    {
        $key = self::getEncryptionKey();
        $data = base64_decode($data);
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Generate machine-specific encryption key
     */
    private static function getEncryptionKey()
    {
        helper('license');
        
        // Combine multiple factors for key generation
        $factors = [
            generate_machine_id(),
            LicenseGuard::getInstallationId(),
            'SAKASALIKA_2026_SECURE_KEY', // Hardcoded salt
            php_uname('n'), // Hostname
            $_SERVER['DOCUMENT_ROOT'] ?? ''
        ];
        
        return hash('sha256', implode('|', $factors));
    }
    
    /**
     * Migrate from .env to encrypted config
     */
    public static function migrateFromEnv()
    {
        $config = [
            'license_server_url' => base64_decode(env('license.serverUrl', '')),
            'license_hash_secret' => base64_decode(env('license.hashSecret', '')),
            'license_encryption_key' => env('license.encryptionKey', ''),
            'migrated_at' => date('Y-m-d H:i:s')
        ];
        
        self::saveConfig($config);
        
        log_message('info', 'License configuration migrated to encrypted storage');
    }
}
```

### Step 2.2: Update LicenseGuard untuk Gunakan SecureConfig

Edit file: `app/Libraries/LicenseGuard.php`

```php
public static function getServerURL()
{
    // Try secure config first
    $url = \App\Libraries\SecureConfig::get('license_server_url');
    
    if ($url) {
        return $url;
    }
    
    // Fallback to .env (for backward compatibility)
    $encoded = env('license.serverUrl', 'aHR0cHM6Ly9saXNlbnNpLnNha2FzYWxpa2EuY29tL2FwaS92ZXJpZnkucGhw');
    return base64_decode($encoded);
}

public static function getHashSecret()
{
    // Try secure config first
    $secret = \App\Libraries\SecureConfig::get('license_hash_secret');
    
    if ($secret) {
        return $secret;
    }
    
    // Fallback to .env (for backward compatibility)
    $encoded = env('license.hashSecret', 'U2FLYVNhTGlLYTIwMjZTZWNyZXRLZXlGb3JIYXNoVmVyaWZpY2F0aW9u');
    return base64_decode($encoded);
}
```

### Step 2.3: Migrate Configuration

Buat route temporary untuk migrasi:

```php
// app/Config/Routes.php
$routes->get('admin/migrate-secure-config', function() {
    if (!is_cli() && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
        die('Access denied');
    }
    
    \App\Libraries\SecureConfig::migrateFromEnv();
    echo "Configuration migrated to encrypted storage!";
});
```

Akses: `http://localhost/siakad/admin/migrate-secure-config`

**PENTING:** Hapus route ini setelah migrasi!

---

## 📋 Fase 3: Enhanced Validation

### Step 3.1: Tambah Hardware Signature

Edit file: `app/Helpers/license_helper.php`

Tambahkan function baru:

```php
/**
 * Generate hardware-based signature (more secure than machine_id)
 */
function generate_hardware_signature()
{
    $signatures = [];
    
    // Try to get hardware-specific identifiers
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        
        if (PHP_OS_FAMILY === 'Linux') {
            // CPU Serial
            $cpuSerial = @shell_exec("cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2");
            if ($cpuSerial) {
                $signatures[] = 'cpu:' . trim($cpuSerial);
            }
            
            // Motherboard Serial
            $mbSerial = @shell_exec("sudo dmidecode -s baseboard-serial-number 2>/dev/null");
            if ($mbSerial && !empty(trim($mbSerial))) {
                $signatures[] = 'mb:' . trim($mbSerial);
            }
            
            // Hard Drive Serial (first drive)
            $hdSerial = @shell_exec("lsblk -d -o SERIAL | grep -v SERIAL | head -1");
            if ($hdSerial) {
                $signatures[] = 'hd:' . trim($hdSerial);
            }
            
            // MAC Address (first interface)
            $mac = @shell_exec("cat /sys/class/net/*/address 2>/dev/null | head -1");
            if ($mac) {
                $signatures[] = 'mac:' . trim($mac);
            }
            
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // BIOS Serial
            $biosSerial = @shell_exec("wmic bios get serialnumber 2>nul");
            if ($biosSerial) {
                $signatures[] = 'bios:' . md5($biosSerial);
            }
            
            // Motherboard Serial
            $mbSerial = @shell_exec("wmic baseboard get serialnumber 2>nul");
            if ($mbSerial) {
                $signatures[] = 'mb:' . md5($mbSerial);
            }
            
            // Hard Drive Serial
            $hdSerial = @shell_exec("wmic diskdrive get serialnumber 2>nul");
            if ($hdSerial) {
                $signatures[] = 'hd:' . md5($hdSerial);
            }
        }
    }
    
    // Fallback: Use software-based identifiers
    if (empty($signatures)) {
        $signatures[] = 'host:' . php_uname('n');
        $signatures[] = 'os:' . php_uname('s');
        $signatures[] = 'arch:' . php_uname('m');
        $signatures[] = 'user:' . get_current_user();
        $signatures[] = 'root:' . md5($_SERVER['DOCUMENT_ROOT'] ?? '');
    }
    
    // Add installation ID
    $signatures[] = 'install:' . \App\Libraries\LicenseGuard::getInstallationId();
    
    return hash('sha256', implode('|', $signatures));
}
```

### Step 3.2: Update Validasi Machine

Edit file: `app/Libraries/LicenseGuard.php`

```php
private static function validateMachine($model, $license)
{
    // 🏠 LOCAL SKIP: Don't lock on local/dev environments
    if (is_local_environment()) {
        return;
    }

    helper('license');
    
    // Use hardware signature for stronger binding
    $hardwareSignature = generate_hardware_signature();
    $machineId = generate_machine_id();

    // Auto bind saat instalasi pertama
    if (empty($license['machine_id'])) {
        $model->update($license['id'], [
            'machine_id' => $machineId,
            'hardware_signature' => $hardwareSignature
        ]);
        return;
    }

    // Validate both machine_id and hardware_signature
    $machineMatch = ($license['machine_id'] === $machineId);
    $hardwareMatch = empty($license['hardware_signature']) || 
                     ($license['hardware_signature'] === $hardwareSignature);

    if (!$machineMatch || !$hardwareMatch) {
        log_message('critical', 'Machine binding validation failed', [
            'expected_machine' => substr($license['machine_id'], 0, 16),
            'current_machine' => substr($machineId, 0, 16),
            'hardware_match' => $hardwareMatch
        ]);
        
        self::lock("INVALID MACHINE - License bound to different hardware", true);
    }
}
```

### Step 3.3: Update Database Schema

Tambahkan kolom `hardware_signature` ke tabel `app_license`:

```sql
ALTER TABLE app_license 
ADD COLUMN hardware_signature VARCHAR(64) NULL AFTER machine_id;
```

Atau buat migration:

```php
// app/Database/Migrations/2026-02-25-000001_AddHardwareSignatureToLicense.php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHardwareSignatureToLicense extends Migration
{
    public function up()
    {
        $fields = [
            'hardware_signature' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'machine_id'
            ]
        ];
        
        $this->forge->addColumn('app_license', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('app_license', 'hardware_signature');
    }
}
```

---

## 🧪 Testing

### Test 1: Anti-Tampering

```bash
# 1. Generate security hash
curl http://localhost/siakad/admin/generate-security-hash

# 2. Edit file (simulasi tampering)
# Edit app/Helpers/license_helper.php - tambah spasi

# 3. Akses aplikasi
# Seharusnya muncul error: "SECURITY VIOLATION: CRITICAL_FILE_MODIFIED"

# 4. Restore file dan regenerate hash
```

### Test 2: Encrypted Config

```bash
# 1. Migrate config
curl http://localhost/siakad/admin/migrate-secure-config

# 2. Cek file .secure_config dibuat
ls -la app/Config/.secure_config

# 3. Coba baca file (seharusnya encrypted)
cat app/Config/.secure_config

# 4. Test aplikasi masih jalan normal
```

### Test 3: Hardware Signature

```php
// Test di browser atau CLI
helper('license');
echo "Machine ID: " . generate_machine_id() . "\n";
echo "Hardware Signature: " . generate_hardware_signature() . "\n";

// Seharusnya berbeda di setiap server
```

---

## 📊 Checklist Implementasi

### Fase 1: Anti-Tampering (30 menit)
- [ ] Buat `app/Libraries/SecurityGuard.php`
- [ ] Update `app/Libraries/LicenseGuard.php`
- [ ] Generate security hash pertama kali
- [ ] Test tampering detection

### Fase 2: Encrypted Config (1 jam)
- [ ] Buat `app/Libraries/SecureConfig.php`
- [ ] Update `LicenseGuard::getServerURL()`
- [ ] Update `LicenseGuard::getHashSecret()`
- [ ] Migrate configuration
- [ ] Test aplikasi masih jalan

### Fase 3: Enhanced Validation (1 jam)
- [ ] Tambah `generate_hardware_signature()` di helper
- [ ] Update `validateMachine()` di LicenseGuard
- [ ] Buat migration untuk kolom baru
- [ ] Jalankan migration
- [ ] Test hardware binding

### Fase 4: Cleanup (15 menit)
- [ ] Hapus route temporary
- [ ] Update `.gitignore`
- [ ] Backup database
- [ ] Test lengkap aplikasi

---

## ⚠️ Catatan Penting

1. **Backup Dulu!**
   - Backup database sebelum implementasi
   - Backup file-file yang akan dimodifikasi

2. **Test di Development**
   - Jangan langsung implementasi di production
   - Test semua fitur setelah implementasi

3. **Dokumentasi**
   - Catat semua perubahan yang dilakukan
   - Simpan security hash dan encrypted config

4. **Rollback Plan**
   - Siapkan cara rollback jika ada masalah
   - Simpan backup yang bisa di-restore cepat

---

**Estimasi Total Waktu:** 2-3 jam untuk implementasi lengkap Fase 1-3

**Tingkat Kesulitan:** ⭐⭐ Menengah

**Dampak Keamanan:** 🛡️🛡️🛡️ Tinggi (dari 6.5/10 menjadi 8/10)

---

**Dibuat:** 25 Februari 2026  
**Status:** ✅ Ready to Implement
