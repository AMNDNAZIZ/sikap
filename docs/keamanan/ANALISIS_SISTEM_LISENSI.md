# Analisis Sistem Lisensi SIAKAD

**Tanggal Analisis:** 25 Februari 2026  
**Versi Aplikasi:** 2.0  
**Status:** ⚠️ Perlu Perbaikan

---

## 📋 Executive Summary

Sistem lisensi SIAKAD memiliki **fondasi yang baik** dengan beberapa fitur keamanan yang solid, namun terdapat **celah keamanan kritis** yang perlu segera diperbaiki untuk melindungi aplikasi dari pembajakan.

### Skor Keamanan: 6.5/10

- ✅ **Kekuatan:** Machine binding, hash integrity, enkripsi database, rate limiting
- ⚠️ **Kelemahan:** Bypass mudah, enkripsi lemah, validasi client-side, tidak ada obfuscation

---

## ✅ Fitur Keamanan yang Sudah Ada

### 1. Machine Binding (Machine ID)
```php
// ✅ BAGUS: Binding ke hardware/sistem
function generate_machine_id() {
    $data = [
        installation_id,    // Unique per install
        hostname,           // Server name
        OS type,            // Linux/Windows
        architecture,       // x86/x64
        system user,        // www-data/apache
        PHP version,        // 8.0
        CPU info,           // Hardware ID
        MAC address,        // Network card
        document root       // Installation path
    ];
    return hash('sha256', implode('|', $data));
}
```

**Kekuatan:**
- Mencegah copy-paste database ke server lain
- Binding ke hardware spesifik
- Fallback jika shell_exec disabled

**Kelemahan:**
- ⚠️ Mudah di-bypass dengan edit helper function
- ⚠️ Tidak ada proteksi terhadap modifikasi kode

### 2. Hash Integrity Verification
```php
// ✅ BAGUS: Verifikasi integritas data
$expectedHash = hash('sha256', 
    $license_key . $expires_at . $machine_id . $hashSecret
);

if ($license['hash'] !== $expectedHash) {
    // Data lisensi dimodifikasi!
}
```

**Kekuatan:**
- Deteksi jika data lisensi diubah manual di database
- Menggunakan secret key dari environment

**Kelemahan:**
- ⚠️ Secret key tersimpan plain di .env (base64 bukan enkripsi!)
- ⚠️ Mudah di-bypass dengan comment validasi

### 3. Enkripsi Database
```php
// ✅ BAGUS: Enkripsi field sensitif
public function insert($data) {
    $data['license_key'] = $this->encryptField($data['license_key']);
    $data['machine_id'] = $this->encryptField($data['machine_id']);
}
```

**Kekuatan:**
- License key tidak tersimpan plain text di database
- Menggunakan AES-256-CBC

**Kelemahan:**
- ⚠️ Encryption key tersimpan di .env
- ⚠️ Jika .env bocor, semua enkripsi bisa di-decrypt

### 4. Rate Limiting
```php
// ✅ BAGUS: Mencegah brute force
$attempts = $cache->get('activate_attempts_' . md5($ip)) ?? 0;
if ($attempts >= 5) {
    return redirect()->back()->with('error', 
        'Terlalu banyak percobaan aktivasi.');
}
```

**Kekuatan:**
- Mencegah brute force attack
- Per IP address
- Auto-reset setelah 1 jam

**Kelemahan:**
- ⚠️ Bisa di-bypass dengan ganti IP (VPN/Proxy)
- ⚠️ Cache bisa di-clear manual

### 5. Server Verification
```php
// ✅ BAGUS: Validasi ke server lisensi
$response = $client->post($serverUrl, [
    'form_params' => [
        'license_key' => $key,
        'domain' => $domain,
        'machine_id' => $machineId,
        'action' => 'activate'
    ]
]);
```

**Kekuatan:**
- Validasi real-time ke server
- Auto-renewal jika expired
- Periodic check (24 jam)

**Kelemahan:**
- ⚠️ Jika server down, aplikasi tetap jalan (grace period)
- ⚠️ Mudah di-bypass dengan mock response

### 6. File Integrity Check
```php
// ✅ BAGUS: Deteksi modifikasi file
private static function checkIntegrity() {
    $hashes = json_decode(file_get_contents($hashFile), true);
    foreach ($hashes as $file => $expectedHash) {
        if (sha1_file($path) !== $expectedHash) {
            return false; // File dimodifikasi!
        }
    }
}
```

**Kekuatan:**
- Deteksi jika file lisensi dimodifikasi
- Hash SHA-1 untuk setiap file

**Kelemahan:**
- ⚠️ File hash tersimpan plain di `.lic_hash`
- ⚠️ Mudah di-bypass dengan update hash file

---

## ❌ Celah Keamanan Kritis

### 🔴 CRITICAL: Bypass Mudah

#### 1. Comment Filter License
```php
// File: app/Config/Filters.php
public array $globals = [
    'before' => [
        // 'license',  // ← BYPASS: Tinggal comment!
    ],
];
```

**Dampak:** Aplikasi langsung jalan tanpa lisensi!

#### 2. Edit Helper Function
```php
// File: app/Helpers/license_helper.php
function is_local_environment() {
    return true; // ← BYPASS: Selalu return true!
}
```

**Dampak:** Semua validasi di-skip!

#### 3. Edit LicenseGuard
```php
// File: app/Libraries/LicenseGuard.php
public static function verify() {
    return; // ← BYPASS: Langsung return!
}
```

**Dampak:** Tidak ada pengecekan lisensi sama sekali!

#### 4. Manipulasi Database
```sql
-- BYPASS: Update manual di database
UPDATE app_license 
SET status = 'active', 
    expires_at = '2099-12-31 23:59:59'
WHERE id = 1;
```

**Dampak:** Lisensi "aktif" selamanya!

### 🟠 HIGH: Enkripsi Lemah

#### 1. Secret Key di .env (Plain Text)
```env
# File: .env
license.serverUrl = aHR0cHM6Ly9saXNlbnNpLnNha2FzYWxpa2EuY29tL2FwaS92ZXJpZnkucGhw
license.hashSecret = U2FLYVNhTGlLYTIwMjZTZWNyZXRLZXlGb3JIYXNoVmVyaWZpY2F0aW9u
```

**Masalah:**
- Base64 bukan enkripsi! Mudah di-decode:
  ```bash
  echo "aHR0cHM6Ly9saXNlbnNpLnNha2FzYWxpa2EuY29tL2FwaS92ZXJpZnkucGhw" | base64 -d
  # Output: https://lisensi.sakasalika.com/api/verify.php
  ```

**Dampak:** Attacker bisa buat server lisensi palsu!

#### 2. Encryption Key Exposed
```env
license.encryptionKey = 9a7f3c2e8d1b5f4a6e8c0d2f9b7a3e5c
```

**Masalah:**
- Key tersimpan plain text
- Jika .env bocor, semua enkripsi bisa di-decrypt

### 🟡 MEDIUM: Tidak Ada Code Obfuscation

**Masalah:**
- Semua kode PHP readable
- Logic lisensi terlihat jelas
- Mudah dipelajari dan di-bypass

**Contoh:**
```php
// Attacker bisa baca dan pahami semua logic:
if (!$model->isValidLicense($license, $error)) {
    // Tinggal edit jadi: if (false) { ... }
}
```

### 🟡 MEDIUM: Grace Period Terlalu Lama

```php
// File: app/Filters/LicenseFilter.php
if ($failCount > 3) {
    $model->update($id, ['status' => 'suspended']);
}
```

**Masalah:**
- Butuh 3x gagal koneksi baru suspend
- Jika server down, aplikasi tetap jalan
- Attacker bisa block koneksi ke server lisensi

---

## 🛡️ Rekomendasi Perbaikan

### Priority 1: CRITICAL (Harus Segera)

#### 1. Implementasi Code Obfuscation
```bash
# Install PHP obfuscator
composer require yakpro/po-php-obfuscator

# Obfuscate file-file kritis:
# - app/Helpers/license_helper.php
# - app/Libraries/LicenseGuard.php
# - app/Filters/LicenseFilter.php
# - app/Models/LicenseModel.php
```

**Manfaat:**
- Kode tidak readable
- Sulit di-reverse engineer
- Proteksi logic lisensi

#### 2. Enkripsi .env dengan Runtime Decryption
```php
// Jangan simpan secret di .env plain!
// Gunakan encrypted config yang di-decrypt saat runtime

class SecureConfig {
    private static $key = null;
    
    public static function get($name) {
        // Decrypt dari file encrypted
        $encrypted = file_get_contents(APPPATH . 'Config/.secure');
        return self::decrypt($encrypted, self::getKey());
    }
    
    private static function getKey() {
        // Generate key dari kombinasi:
        // - Hardware ID
        // - Installation ID
        // - Hardcoded salt (di-obfuscate)
        if (self::$key === null) {
            self::$key = hash('sha256', 
                generate_machine_id() . 
                'HARDCODED_SALT_HERE' . 
                LicenseGuard::getInstallationId()
            );
        }
        return self::$key;
    }
}
```

#### 3. Tambah Anti-Tampering Protection
```php
// File: app/Libraries/LicenseGuard.php
public static function verify() {
    // 1. Cek apakah file ini sudah dimodifikasi
    self::checkSelfIntegrity();
    
    // 2. Cek apakah filter license masih aktif
    self::checkFilterActive();
    
    // 3. Cek apakah helper function masih original
    self::checkHelperIntegrity();
    
    // ... existing code ...
}

private static function checkSelfIntegrity() {
    $currentHash = sha1_file(__FILE__);
    $expectedHash = self::getExpectedHash(); // From encrypted config
    
    if ($currentHash !== $expectedHash) {
        self::lock("TAMPERING DETECTED - FILE MODIFIED");
    }
}

private static function checkFilterActive() {
    $config = config('Filters');
    if (!in_array('license', $config->globals['before'])) {
        self::lock("TAMPERING DETECTED - FILTER DISABLED");
    }
}
```

#### 4. Implementasi Hardware Lock (Dongle Virtual)
```php
// Generate unique hardware signature
function generate_hardware_signature() {
    $data = [];
    
    // CPU Serial (Linux)
    $cpuSerial = @shell_exec("cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2");
    if ($cpuSerial) $data[] = trim($cpuSerial);
    
    // Motherboard Serial (Linux)
    $mbSerial = @shell_exec("dmidecode -s baseboard-serial-number 2>/dev/null");
    if ($mbSerial) $data[] = trim($mbSerial);
    
    // Hard Drive Serial (Linux)
    $hdSerial = @shell_exec("lsblk -o SERIAL | grep -v SERIAL | head -1");
    if ($hdSerial) $data[] = trim($hdSerial);
    
    // Windows: BIOS Serial
    $biosSerial = @shell_exec("wmic bios get serialnumber 2>nul");
    if ($biosSerial) $data[] = md5($biosSerial);
    
    return hash('sha256', implode('|', $data));
}
```

### Priority 2: HIGH (Penting)

#### 5. Implementasi License Server dengan Token Rotation
```php
// Setiap 24 jam, minta token baru dari server
public function rotateToken() {
    $response = $client->post($serverUrl, [
        'form_params' => [
            'license_key' => $key,
            'current_token' => $currentToken,
            'action' => 'rotate'
        ]
    ]);
    
    $newToken = $result['data']['token'];
    $model->update($id, ['token' => $newToken]);
}

// Validasi token setiap request
public function validateToken() {
    if ($license['token'] !== $this->calculateExpectedToken()) {
        self::lock("INVALID TOKEN");
    }
}
```

#### 6. Tambah Watermark/Fingerprint di Output
```php
// Inject hidden fingerprint di setiap halaman
public function after(RequestInterface $request, ResponseInterface $response) {
    $body = $response->getBody();
    
    // Inject hidden license info
    $fingerprint = "<!-- LIC:" . 
        base64_encode(json_encode([
            'key' => substr($license['license_key'], 0, 8),
            'domain' => $license['domain'],
            'ts' => time()
        ])) . 
    " -->";
    
    $body = str_replace('</body>', $fingerprint . '</body>', $body);
    $response->setBody($body);
    
    return $response;
}
```

#### 7. Implementasi Remote Kill Switch
```php
// Server bisa remote disable lisensi
public function checkKillSwitch() {
    $response = $client->post($serverUrl, [
        'form_params' => [
            'license_key' => $key,
            'action' => 'check_status'
        ]
    ]);
    
    if ($result['status'] === 'killed') {
        $model->update($id, ['status' => 'suspended']);
        self::lock("LICENSE REVOKED BY ADMINISTRATOR");
    }
}
```

### Priority 3: MEDIUM (Recommended)

#### 8. Logging & Monitoring
```php
// Log semua aktivitas lisensi
log_message('security', 'License activation attempt', [
    'ip' => $ip,
    'domain' => $domain,
    'machine_id' => substr($machineId, 0, 16),
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'timestamp' => time()
]);

// Kirim alert ke server jika ada aktivitas mencurigakan
if ($suspiciousActivity) {
    $this->sendAlertToServer([
        'type' => 'suspicious_activity',
        'details' => $details
    ]);
}
```

#### 9. Implementasi Trial Period
```php
// Berikan trial 30 hari tanpa lisensi
public function isTrialValid() {
    $installDate = $this->getInstallDate();
    $daysSinceInstall = (time() - $installDate) / 86400;
    
    return $daysSinceInstall <= 30;
}

public function verify() {
    $license = $model->first();
    
    if (!$license) {
        // Cek trial period
        if (!$this->isTrialValid()) {
            self::lock("TRIAL PERIOD EXPIRED");
        }
        return; // Trial masih valid
    }
    
    // ... existing validation ...
}
```

#### 10. Multi-Layer Validation
```php
// Validasi di multiple layer
// Layer 1: Filter (sebelum controller)
// Layer 2: BaseController (sebelum method)
// Layer 3: Model (sebelum query)
// Layer 4: View (sebelum render)

// Jika salah satu layer di-bypass, layer lain masih protect
```

---

## 📊 Perbandingan: Sebelum vs Sesudah

| Aspek | Sebelum | Sesudah (Recommended) |
|-------|---------|----------------------|
| **Code Readability** | ❌ Semua readable | ✅ Obfuscated |
| **Secret Storage** | ❌ Plain di .env | ✅ Encrypted runtime |
| **Anti-Tampering** | ❌ Tidak ada | ✅ Self-check |
| **Hardware Binding** | ⚠️ Software-based | ✅ Hardware serial |
| **Token Security** | ❌ Static | ✅ Rotating token |
| **Remote Control** | ❌ Tidak ada | ✅ Kill switch |
| **Monitoring** | ⚠️ Basic logging | ✅ Full audit trail |
| **Trial Period** | ❌ Tidak ada | ✅ 30 hari trial |
| **Multi-Layer** | ⚠️ Single layer | ✅ 4 layers |
| **Bypass Difficulty** | 🔴 Mudah (5 menit) | 🟢 Sulit (berhari-hari) |

---

## 🎯 Kesimpulan

### Sistem Saat Ini: 6.5/10

**Kekuatan:**
- ✅ Fondasi yang solid
- ✅ Machine binding
- ✅ Hash integrity
- ✅ Enkripsi database
- ✅ Rate limiting
- ✅ Server verification

**Kelemahan:**
- ❌ Mudah di-bypass (comment code)
- ❌ Enkripsi lemah (base64 bukan enkripsi)
- ❌ Tidak ada obfuscation
- ❌ Secret key exposed
- ❌ Tidak ada anti-tampering

### Setelah Implementasi Rekomendasi: 9/10

**Improvement:**
- ✅ Code obfuscation (sulit dibaca)
- ✅ Encrypted config (secret aman)
- ✅ Anti-tampering (deteksi modifikasi)
- ✅ Hardware lock (binding ke hardware)
- ✅ Token rotation (dynamic security)
- ✅ Remote kill switch (kontrol penuh)
- ✅ Multi-layer validation (redundancy)
- ✅ Trial period (user-friendly)

**Estimasi Waktu Bypass:**
- Sebelum: 5-10 menit (pemula)
- Sesudah: Berhari-hari hingga berminggu-minggu (expert)

---

## 📝 Action Plan

### Fase 1: Critical (1-2 Minggu)
1. ✅ Implementasi code obfuscation
2. ✅ Enkripsi .env dengan runtime decryption
3. ✅ Tambah anti-tampering protection
4. ✅ Implementasi hardware lock

### Fase 2: High (2-3 Minggu)
5. ✅ Token rotation system
6. ✅ Watermark/fingerprint
7. ✅ Remote kill switch

### Fase 3: Medium (1-2 Minggu)
8. ✅ Enhanced logging & monitoring
9. ✅ Trial period implementation
10. ✅ Multi-layer validation

**Total Estimasi:** 4-7 minggu untuk implementasi penuh

---

## ⚠️ Disclaimer

Tidak ada sistem keamanan yang 100% aman. Tujuan dari proteksi lisensi adalah:
1. **Membuat bypass menjadi sulit** (bukan impossible)
2. **Meningkatkan cost of piracy** (waktu & effort)
3. **Mendeteksi & mencegah casual piracy** (bukan expert hacker)

Untuk proteksi maksimal, kombinasikan dengan:
- Legal agreement (kontrak)
- Regular audit
- Customer relationship management
- Value-added services (support, updates, cloud features)

---

**Dibuat:** 25 Februari 2026  
**Oleh:** Security Analysis Team  
**Status:** ⚠️ Perlu Implementasi Segera
