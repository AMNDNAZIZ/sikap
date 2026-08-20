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

        $machineId         = generate_machine_id();
        $hardwareSignature = generate_hardware_signature();

        // Auto bind saat instalasi pertama
        if (empty($license['machine_id'])) {
            $model->update($license['id'], [
                'machine_id'         => $machineId,
                'hardware_signature' => $hardwareSignature,
            ]);
            log_message('info', 'License bound to new machine: ' . substr($machineId, 0, 16));
            return;
        }

        $machineMatch  = ($license['machine_id'] === $machineId);
        $hardwareMatch = empty($license['hardware_signature']) ||
                         ($license['hardware_signature'] === $hardwareSignature);

        if ($machineMatch && $hardwareMatch) {
            return; // Semua cocok — lanjut
        }

        // ─── Machine/hardware tidak cocok ─────────────────────────────────────
        // Bisa terjadi karena: server restart, upgrade PHP, perubahan env vars,
        // atau perubahan SERVER_SOFTWARE/SERVER_SIGNATURE setelah update hosting.
        //
        // Strategi: coba verify ke server lisensi terlebih dahulu.
        // Kalau server menyatakan lisensi valid untuk domain ini → rebind machine_id.
        // Kalau server menolak atau tidak bisa dihubungi → lock.
        log_message('warning', 'Machine binding mismatch detected. Attempting server re-verify before lock.', [
            'expected_machine' => substr($license['machine_id'], 0, 16) . '...',
            'current_machine'  => substr($machineId, 0, 16) . '...',
            'hardware_match'   => $hardwareMatch,
        ]);

        $serverUrl = self::getServerURL();
        $domain    = $_SERVER['HTTP_HOST'] ?? '';

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain'      => $domain,
                    'machine_id'  => $machineId,
                    'action'      => 'verify',
                ],
                'timeout'     => 8,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Server menerima → rebind machine_id ke hardware saat ini
                $expiresAt = $result['data']['expiry'] ?? $license['expires_at'];
                $secret    = self::getHashSecret();
                $newHash   = hash('sha256', $license['license_key'] . $expiresAt . $machineId . $secret);

                $model->update($license['id'], [
                    'machine_id'         => $machineId,
                    'hardware_signature' => $hardwareSignature,
                    'hash'               => $newHash,
                    'expires_at'         => $expiresAt,
                    'last_check'         => date('Y-m-d H:i:s'),
                    'status'             => 'active',
                ]);

                self::regenerateAllHashes();
                log_message('info', 'Machine ID rebound after server verification. New machine: ' . substr($machineId, 0, 16));
                return; // Rebind berhasil — lanjutkan tanpa lock
            }

            // Server menolak (lisensi tidak valid untuk domain/key ini)
            log_message('critical', 'Machine binding validation failed — server rejected re-verify', [
                'server_response' => $result['message'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            // Server tidak bisa dihubungi — gunakan grace period agar tidak false-positive
            log_message('warning', 'Machine re-verify: server unreachable (' . $e->getMessage() . '). Using grace period.');

            $cache     = \Config\Services::cache();
            $failCount = (int)($cache->get('machine_verify_fail') ?? 0);

            if ($failCount < 5) {
                $cache->save('machine_verify_fail', $failCount + 1, 3600); // reset tiap 1 jam
                log_message('warning', 'Machine grace period: attempt ' . ($failCount + 1) . '/5, allowing continue.');
                return; // Beri toleransi, jangan lock karena jaringan bermasalah
            }

            log_message('critical', 'Machine grace period exhausted (5/5). Locking.', [
                'expected_machine' => substr($license['machine_id'], 0, 16) . '...',
                'current_machine'  => substr($machineId, 0, 16) . '...',
            ]);
        }

        self::lock("INVALID MACHINE - License bound to different hardware", true);
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

        // Normalisasi: strip www. untuk perbandingan base domain
        $normalize = fn($d) => preg_replace('/^www\./i', '', strtolower(trim($d)));

        $normalizedStored  = $normalize($storedDomain);
        $normalizedCurrent = $normalize($currentDomain);

        // Cek 1: exact match (setelah strip www)
        if ($normalizedStored === $normalizedCurrent) {
            return;
        }

        // Cek 2: subdomain dari domain yang sama
        // misal stored=www.smpistora.sch.id, current=mail.smpistora.sch.id
        // keduanya berbagi base domain smpistora.sch.id → izinkan
        if (str_ends_with($normalizedCurrent, '.' . $normalizedStored) ||
            str_ends_with($normalizedStored,  '.' . $normalizedCurrent) ||
            $normalizedCurrent === $normalizedStored) {
            log_message('info', 'Domain subdomain match allowed: stored=' . $storedDomain . ', current=' . $currentDomain);
            return;
        }

        log_message('warning', 'Domain mismatch: stored=' . $storedDomain . ', current=' . $currentDomain . '. Attempting server re-verify...');

        // Coba update domain di server sebelum block
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

                // Server tidak dapat dihubungi (bukan domain benar-benar salah) —
                // gunakan grace period agar aplikasi tetap bisa diakses sementara
                $cache     = \Config\Services::cache();
                $failCount = (int)($cache->get('domain_verify_fail') ?? 0);

                if ($failCount < 10) {
                    $cache->save('domain_verify_fail', $failCount + 1, 3600);
                    log_message('warning', 'Domain re-verify: server unreachable, grace period ' . ($failCount + 1) . '/10, allowing continue.');
                    return; // Beri toleransi, jangan lock
                }
            }
        }

        // Server tidak menerima domain baru — lock
        self::lock("INVALID DOMAIN: expected={$storedDomain}, got={$currentDomain}", true);
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
        log_message('critical', 'LicenseGuard lock triggered: ' . $message);

        // Gunakan header() native PHP — lock() bisa dipanggil sangat awal
        // (dari BaseController::initController, sebelum CI4 response cycle selesai).
        // Menggunakan CI4 $response->send() di fase ini menghasilkan blank page
        // karena CI4 belum selesai menyiapkan output buffer dan headers.
        // header() native selalu aman selama output belum dikirim.
        $activateUrl = base_url('activate');

        if (!headers_sent()) {
            header('Location: ' . $activateUrl, true, 302);
            exit;
        }

        // Fallback jika headers sudah terlanjur terkirim (jarang terjadi)
        // — keluarkan meta refresh agar tetap terarahkan
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($activateUrl) . '">';
        echo '<script>window.location.href=' . json_encode($activateUrl) . ';</script>';
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
