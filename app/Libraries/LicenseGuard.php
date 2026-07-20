<?php

namespace App\Libraries;

use App\Models\LicenseModel;

class LicenseGuard
{
    /**
     * Get Server URL (with decryption)
     */
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

    /**
     * Get Hash Secret (with decryption)
     */
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

    /**
     * Get Installation ID (unique per installation)
     */
    public static function getInstallationId()
    {
        static $installId = null;

        if ($installId !== null) {
            return $installId;
        }

        // Try from environment variable first
        $installId = env('license.installId');

        if (!$installId) {
            // Fallback: try from file
            $installFile = WRITEPATH . '.install_id';
            if (file_exists($installFile)) {
                $installId = trim(file_get_contents($installFile));
            }
        }

        // If still not found, generate new one
        if (!$installId) {
            $installId = bin2hex(random_bytes(32));
            $installFile = WRITEPATH . '.install_id';
            file_put_contents($installFile, $installId);
            log_message('warning', 'Generated new installation ID. This should only happen once.');
        }

        return $installId;
    }

    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data)
    {
        $key = env('license.encryptionKey');
        if (!$key) {
            throw new \Exception('License encryption key not configured');
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    public static function decrypt($data)
    {
        $key = env('license.encryptionKey');
        if (!$key) {
            throw new \Exception('License encryption key not configured');
        }

        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Entry point utama pengecekan lisensi
     */
    public static function verify()
    {
        // 0️⃣ Skip validation for activation, login, and static files
        $request = \Config\Services::request();
        $uriPath = $request->getUri()->getPath();
        $rawUri = $_SERVER['REQUEST_URI'] ?? '';

        $skip = ['activate', 'login', 'assets', 'favicon.ico', 'maintenance'];
        foreach ($skip as $s) {
            if (strpos($uriPath, $s) !== false || strpos($rawUri, $s) !== false) {
                return;
            }
        }

        // 🛡️ Security check - verify critical files haven't been tampered
        $securityCheck = \App\Libraries\SecurityGuard::performSecurityCheck();
        if (!$securityCheck['status']) {
            // Selalu redirect ke aktivasi, tidak pernah die/block
            self::lock($securityCheck['message'] ?? "SECURITY VIOLATION: " . $securityCheck['error'], true);
        }

        helper('license');

        // 1️⃣ Cek integritas file lisensi
        if (!self::checkIntegrity()) {
            // Selalu redirect ke aktivasi, tidak pernah die/block
            self::lock("LICENSE FILE MODIFIED", true);
        }

        $model = new LicenseModel();
        $license = $model->first();

        // 2️⃣ Pastikan lisensi ada
        if (!$license) {
            self::lock("LICENSE NOT FOUND", true); // Redirect to activate
        }

        // 3️⃣ Validasi machine binding
        self::validateMachine($model, $license);

        // 4️⃣ Validasi domain (hanya jika bukan localhost)
        self::validateDomain($license);

        // 5️⃣ Validasi expiry
        self::validateExpiry($license);
    }

    /**
     * ===============================
     * VALIDATION METHODS
     * ===============================
     */

    private static function validateMachine($model, $license)
    {
        // 🏠 LOCAL SKIP: Don't lock on local/dev environments
        if (is_local_environment()) {
            return;
        }

        helper('license');

        $machineId = generate_machine_id();
        $hardwareSignature = generate_hardware_signature();

        // Auto bind saat instalasi pertama
        if (empty($license['machine_id'])) {
            $model->update($license['id'], [
                'machine_id' => $machineId,
                'hardware_signature' => $hardwareSignature
            ]);
            log_message('info', 'License bound to new machine: ' . substr($machineId, 0, 16));
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

            // Redirect ke aktivasi, bukan die/block
            self::lock("INVALID MACHINE - License bound to different hardware", true);
        }
    }

    private static function validateDomain($license)
    {
        if (is_local_environment()) {
            return; // localhost tidak perlu cek domain
        }

        $currentDomain = $_SERVER['HTTP_HOST'] ?? '';

        if (empty($license['domain'])) {
            return; // belum ada domain terdaftar, skip
        }

        $storedDomain  = $license['domain'];

        // Normalisasi: strip www. untuk perbandingan
        $normalize = fn($d) => preg_replace('/^www\./i', '', strtolower(trim($d)));

        if ($normalize($storedDomain) !== $normalize($currentDomain)) {
            log_message('warning', 'Domain mismatch: stored=' . $storedDomain . ', current=' . $currentDomain . '. Attempting server re-verify...');

            // Coba update domain di server sebelum block
            // Mungkin domain berubah (www vs non-www, atau perubahan subdomain)
            $model = new LicenseModel();
            $lic   = $model->first();

            if ($lic) {
                $serverUrl = self::getServerURL();
                helper('license');
                $machineId = generate_machine_id();

                try {
                    $client   = \Config\Services::curlrequest();
                    $response = $client->post($serverUrl, [
                        'form_params' => [
                            'license_key' => $lic['license_key'],
                            'domain'      => $currentDomain,
                            'machine_id'  => $machineId,
                            'action'      => 'verify',
                        ],
                        'timeout'     => 8,
                        'http_errors' => false,
                    ]);

                    $result = json_decode($response->getBody(), true);

                    if (isset($result['status']) && $result['status'] === 'success') {
                        // Server terima domain baru — update DB
                        $expiresAt = $result['data']['expiry'] ?? $lic['expires_at'];
                        $secret    = self::getHashSecret();
                        $newHash   = hash('sha256', $lic['license_key'] . $expiresAt . $machineId . $secret);

                        $model->update($lic['id'], [
                            'domain'     => $currentDomain,
                            'hash'       => $newHash,
                            'expires_at' => $expiresAt,
                            'machine_id' => $machineId,
                            'last_check' => date('Y-m-d H:i:s'),
                            'status'     => 'active',
                        ]);

                        self::regenerateAllHashesSync();
                        log_message('info', 'Domain updated to: ' . $currentDomain);
                        return; // Domain berhasil diupdate, lanjutkan
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Domain re-verify failed: ' . $e->getMessage());
                }
            }

            // Server tidak menerima domain baru — lock
            self::lock("INVALID DOMAIN: expected={$storedDomain}, got={$currentDomain}", true);
        }
    }

    private static function validateExpiry($license)
    {
        if (empty($license['expires_at'])) {
            return; // lisensi lifetime
        }

        if (strtotime($license['expires_at']) < time()) {
            self::lock("LICENSE EXPIRED", true); // Redirect to activate
        }
    }

    /**
     * ===============================
     * FILE INTEGRITY CHECK
     * ===============================
     */

    public static function checkIntegrity()
    {
        $hashFile = APPPATH . 'Config/.lic_hash';

        if (!file_exists($hashFile)) {
            // Generate hash file if not exists
            log_message('info', 'License hash file not found, generating...');
            self::generateLicenseHash();
            return true; // Don't block on first run
        }

        $hashes = json_decode(file_get_contents($hashFile), true);

        if (!$hashes || !is_array($hashes)) {
            log_message('warning', 'Invalid license hash file, regenerating...');
            self::generateLicenseHash();
            return true; // Don't block on invalid format
        }

        foreach ($hashes as $file => $expectedHash) {
            $path = APPPATH . $file;

            if (!file_exists($path)) {
                log_message('critical', "License file missing: {$file}");
                return false;
            }

            $currentHash = sha1_file($path);

            if ($currentHash !== $expectedHash) {
                log_message('critical', "License file modified: {$file}");
                return false;
            }
        }

        return true;
    }

    /**
     * Generate license hash file
     */
    private static function generateLicenseHash()
    {
        $licenseFiles = [
            'Libraries/LicenseGuard.php',
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
        ];

        $hashes = [];

        foreach ($licenseFiles as $file) {
            $path = APPPATH . $file;
            if (file_exists($path)) {
                $hashes[$file] = sha1_file($path);
            }
        }

        $hashFile = APPPATH . 'Config/.lic_hash';
        $result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

        if ($result !== false) {
            log_message('info', 'License hashes generated for ' . count($hashes) . ' files');

            // Add to .gitignore
            $gitignore = APPPATH . 'Config/.gitignore';
            $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';

            if (strpos($content, '.lic_hash') === false) {
                file_put_contents($gitignore, $content . "\n.lic_hash\n");
            }
        }

        return $result !== false;
    }

    /**
     * ===============================
     * LOCK SYSTEM — SELALU REDIRECT
     * ===============================
     */
    private static function lock($message, $shouldRedirect = true)
    {
        // Selalu redirect ke halaman aktivasi, tidak pernah die/block
        log_message('critical', 'LicenseGuard lock triggered: ' . $message);

        // Simpan pesan error ke session jika memungkinkan
        // Gunakan session_status() agar tidak memaksa inisialisasi session
        // yang bisa menyebabkan konflik dengan CSRF filter di PHP 8.2
        if (session_status() === PHP_SESSION_ACTIVE) {
            try {
                $session = \Config\Services::session();
                $session->setFlashdata('license_error', $message);
            } catch (\Exception $e) {
                // Abaikan jika session tidak tersedia
            }
        }

        // Gunakan CI4 response object, BUKAN header() native PHP.
        // header() native akan mengirim headers langsung ke browser dan merusak
        // state PHP sehingga request berikutnya gagal dengan
        // "ini_set(): Session ini settings cannot be changed after headers already sent"
        $response = \Config\Services::response();
        $response->redirect(base_url('activate'));
        $response->send();
        exit;
    }

    /**
     * Regenerate semua hash file (lic_hash + security_hash).
     * Dipanggil setelah aktivasi/renewal agar hash selalu sinkron.
     *
     * Menggunakan register_shutdown_function agar penulisan file terjadi
     * SETELAH response dikirim ke browser — tidak mengganggu session/header.
     */
    public static function regenerateAllHashes()
    {
        // Daftarkan sebagai shutdown function agar tidak mengganggu
        // session initialization dan header management CI4
        register_shutdown_function(function () {
            self::regenerateAllHashesSync();
        });
    }

    /**
     * Regenerate semua hash file secara sinkron (untuk CLI/migration/setup).
     */
    public static function regenerateAllHashesSync(): array
    {
        $licResult = self::generateLicenseHash();
        $secResult = \App\Libraries\SecurityGuard::regenerateHashes();
        log_message('info', 'All hashes regenerated (sync). lic_hash=' . ($licResult ? 'OK' : 'FAIL') . ', security_hash=' . ($secResult ? 'OK' : 'FAIL'));
        return [
            'lic_hash' => $licResult,
            'security_hash' => $secResult
        ];
    }

    /**
     * Pengecekan menyeluruh terhadap lisensi dan integritas file tanpa melakukan redirect/blocking.
     * Digunakan oleh halaman aktivasi untuk menghindari redirect loop.
     */
    public static function isFullyValid(): bool
    {
        // 1. Cek validitas filter dan integritas file kritikal
        $securityCheck = \App\Libraries\SecurityGuard::performSecurityCheck();
        if (!$securityCheck['status']) {
            log_message('warning', 'isFullyValid failed: security check failed - ' . ($securityCheck['message'] ?? $securityCheck['error']));
            return false;
        }

        // 2. Cek integritas file lisensi
        if (!self::checkIntegrity()) {
            log_message('warning', 'isFullyValid failed: file integrity check failed');
            return false;
        }

        $model = new LicenseModel();
        $license = $model->first();

        // 3. Pastikan data lisensi ada
        if (!$license) {
            log_message('warning', 'isFullyValid failed: no license found');
            return false;
        }

        // 4. Validasi machine binding & domain jika bukan local/development
        helper('license');
        if (!is_local_environment()) {
            $machineId = generate_machine_id();
            $hardwareSignature = generate_hardware_signature();

            $machineMatch = ($license['machine_id'] === $machineId);
            $hardwareMatch = empty($license['hardware_signature']) || ($license['hardware_signature'] === $hardwareSignature);

            if (!$machineMatch || !$hardwareMatch) {
                log_message('warning', 'isFullyValid failed: machine/hardware signature mismatch');
                return false;
            }

            // Validasi domain
            $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($license['domain']) && $license['domain'] !== $currentDomain) {
                log_message('warning', 'isFullyValid failed: domain mismatch (expected ' . $license['domain'] . ', got ' . $currentDomain . ')');
                return false;
            }
        }

        // 5. Validasi expiry
        if (!empty($license['expires_at']) && strtotime($license['expires_at']) < time()) {
            log_message('warning', 'isFullyValid failed: license expired');
            return false;
        }

        return true;
    }
}
