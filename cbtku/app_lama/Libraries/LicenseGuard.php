<?php

namespace App\Libraries;

use App\Models\LicenseModel;

class LicenseGuard
{
    /**
     * Entry point utama pengecekan lisensi
     */
    public static function verify()
    {
        // 0️⃣ Skip validation for activation, login, and static files
        $request = \Config\Services::request();
        $uri = trim($request->getUri()->getPath(), '/');
        $segments = explode('/', $uri);

        $skip = ['activate', 'login', 'assets', 'favicon.ico', 'cbt/csrf/refresh', 'fixlicense'];
        foreach ($skip as $s) {
            if (in_array($s, $segments)) {
                return;
            }
        }

        helper('license');

        // 🛡️ PROTEKSI: Monitor & restore jika lisensi dihapus
        LicenseProtector::monitorChanges();
        
        // 🛡️ PROTEKSI: Verify marker files
        LicenseProtector::verifyMarkers();

        // 1️⃣ Cek integritas file lisensi
        if (!self::checkIntegrity()) {
            self::lock("LICENSE FILE MODIFIED");
        }

        $model = new LicenseModel();
        $license = $model->first();

        // 2️⃣ Pastikan lisensi ada (dengan auto-restore)
        if (!$license) {
            // Coba restore otomatis
            LicenseProtector::restoreFromBackup();
            $license = $model->first();
            
            if (!$license) {
                self::lock("LICENSE NOT FOUND", true); // Redirect to activate
            }
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

        $machine = generate_machine_id();

        // Auto bind saat instalasi pertama
        if (empty($license['machine_id'])) {
            $model->update($license['id'], ['machine_id' => $machine]);
            return;
        }

        // Jika berbeda → lock
        if ($license['machine_id'] !== $machine) {
            self::lock("INVALID MACHINE", true); // Redirect for status mismatch
        }
    }

    private static function validateDomain($license)
    {
        if (is_local_environment()) {
            return; // localhost tidak perlu cek domain
        }

        $currentDomain = $_SERVER['HTTP_HOST'] ?? '';

        if (!empty($license['domain']) && $license['domain'] !== $currentDomain) {
            self::lock("INVALID DOMAIN");
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

    private static function checkIntegrity()
    {
        $hashFile = APPPATH . 'Config/.lic_hash';

        // 🔧 AUTO-FIX: Jika hash file tidak ada, generate otomatis
        if (!file_exists($hashFile)) {
            log_message('warning', 'Hash file not found, auto-generating...');
            
            if (self::autoGenerateHash()) {
                log_message('info', 'Hash file auto-generated successfully');
                return true; // Hash baru dibuat, anggap valid
            } else {
                log_message('error', 'Failed to auto-generate hash file');
                return false;
            }
        }

        $hashes = json_decode(file_get_contents($hashFile), true);

        if (!$hashes || !is_array($hashes)) {
            log_message('warning', 'Invalid hash file format, regenerating...');
            
            if (self::autoGenerateHash()) {
                return true;
            }
            return false;
        }

        foreach ($hashes as $file => $expectedHash) {
            $path = APPPATH . $file;

            if (!file_exists($path)) {
                log_message('error', 'Protected file not found: ' . $file);
                return false;
            }

            $currentHash = sha1_file($path);

            if ($currentHash !== $expectedHash) {
                log_message('error', 'Hash mismatch for file: ' . $file);
                return false;
            }
        }

        return true;
    }

    /**
     * Auto-generate hash file untuk instalasi baru
     * Hanya bisa dijalankan jika lisensi valid (online check)
     */
    private static function autoGenerateHash()
    {
        // 🔒 SECURITY: Hanya generate jika lisensi valid
        if (!self::isLicenseValidOnline()) {
            log_message('error', 'Cannot auto-generate hash: License not valid online');
            return false;
        }

        $protectedFiles = [
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Controllers/Activate.php',
            'Controllers/BaseController.php',
            'Controllers/Admin/License.php',
            'Libraries/LicenseGuard.php',
            'Libraries/LicenseProtector.php',
            'Helpers/license_helper.php',
            'Config/Filters.php',
            'Config/LService.php',
            'Config/Events.php'
        ];

        $hashes = [];
        foreach ($protectedFiles as $file) {
            $fullPath = APPPATH . $file;
            
            if (file_exists($fullPath)) {
                $hashes[$file] = sha1_file($fullPath);
            } else {
                log_message('error', 'Protected file not found during hash generation: ' . $file);
                return false;
            }
        }

        $hashFile = APPPATH . 'Config/.lic_hash';
        $json = json_encode($hashes, JSON_PRETTY_PRINT);
        
        // Coba tulis file
        $result = @file_put_contents($hashFile, $json);
        
        if ($result === false) {
            log_message('error', 'Failed to write hash file: permission denied');
            return false;
        }

        log_message('info', 'Hash file generated successfully with ' . count($hashes) . ' files');
        return true;
    }

    /**
     * Validasi lisensi ke server online
     * Digunakan untuk auto-generate hash
     */
    private static function isLicenseValidOnline()
    {
        try {
            $model = new LicenseModel();
            $license = $model->first();

            if (!$license) {
                return false;
            }

            // Jika localhost, skip online check
            if (is_local_environment()) {
                return true;
            }

            $lsc = config('LService');
            $serverUrl = $lsc->getUrl();
            $domain = $_SERVER['HTTP_HOST'] ?? '';
            helper('license');
            $machineId = generate_machine_id();

            $client = \Config\Services::curlrequest();
            
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'verify'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Update last check
                $model->update($license['id'], [
                    'last_check' => date('Y-m-d H:i:s')
                ]);
                
                return true;
            }

            return false;
        } catch (\Exception $e) {
            log_message('error', 'Online license validation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ===============================
     * LOCK SYSTEM (ANTI BYPASS)
     * ===============================
     */
    private static function lock($message, $shouldRedirect = false)
    {
        if ($shouldRedirect) {
            // Gunakan refresh header atau redirect manual agar tidak loop di BaseController
            header("Location: " . base_url('activate'));
            exit;
        }

        http_response_code(403);

        die("
        <h2 style='font-family:Arial;color:red'>
            LICENSE ERROR
        </h2>
        <p>$message</p>
        <hr>
        <small>SakaSalika Anti-Tamper System</small>
        ");
    }
}
