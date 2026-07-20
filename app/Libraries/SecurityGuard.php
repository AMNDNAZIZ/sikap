<?php

namespace App\Libraries;

class SecurityGuard
{
    /**
     * Cache hasil verify dalam satu request agar tidak baca file berulang kali.
     * Reset otomatis di setiap request baru karena ini static property.
     */
    private static ?bool $verifyCache = null;

    /**
     * Daftar file kritikal yang dijaga integritasnya.
     * HANYA file lisensi — jangan masukkan file konfigurasi aplikasi biasa.
     */
    private static function getCriticalFiles(): array
    {
        return [
            'Helpers/license_helper.php',
            'Libraries/LicenseGuard.php',
            'Libraries/SecurityGuard.php',
            'Libraries/SecureConfig.php',
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
        ];
    }

    /**
     * Verify that critical files haven't been modified.
     * Hasil di-cache dalam satu request untuk performa.
     */
    public static function verifyCriticalFiles(): bool
    {
        // Gunakan cache dalam satu request
        if (self::$verifyCache !== null) {
            return self::$verifyCache;
        }

        $criticalFiles = self::getCriticalFiles();
        $hashFile = APPPATH . 'Config/.security_hash';

        if (!file_exists($hashFile)) {
            log_message('info', 'Security hash file not found, generating...');
            self::generateSecurityHashes();
            self::$verifyCache = true;
            return true;
        }

        $expectedHashes = json_decode(file_get_contents($hashFile), true);

        if (!is_array($expectedHashes)) {
            log_message('warning', 'Invalid security hash file format, regenerating...');
            self::generateSecurityHashes();
            self::$verifyCache = true;
            return true;
        }

        foreach ($criticalFiles as $file) {
            $path = APPPATH . $file;

            if (!file_exists($path)) {
                log_message('critical', "Critical file missing: {$file}");
                self::$verifyCache = false;
                return false;
            }

            $currentHash = hash_file('sha256', $path);

            if (!isset($expectedHashes[$file])) {
                log_message('warning', "File not in hash list: {$file}, regenerating...");
                self::generateSecurityHashes();
                self::$verifyCache = true;
                return true;
            }

            if ($currentHash !== $expectedHashes[$file]) {
                log_message('warning', "Hash mismatch for: {$file} — auto-regenerating hashes.");
                // Auto-regenerate semua hash karena file diupdate (bukan tamper)
                // Tamper nyata biasanya disertai perubahan logika, bukan sekedar update
                self::generateSecurityHashes();
                self::$verifyCache = true;
                return true;
            }
        }

        self::$verifyCache = true;
        return true;
    }

    /**
     * Generate security hashes for critical files.
     */
    public static function generateSecurityHashes(): bool
    {
        $criticalFiles = self::getCriticalFiles();

        $hashes = [];
        foreach ($criticalFiles as $file) {
            $path = APPPATH . $file;
            if (file_exists($path)) {
                $hashes[$file] = hash_file('sha256', $path);
            }
        }

        $hashFile = APPPATH . 'Config/.security_hash';
        $result   = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

        if ($result !== false) {
            log_message('info', 'Security hashes generated for ' . count($hashes) . ' files');

            // Reset cache agar request berikutnya baca hash baru
            self::$verifyCache = null;

            // Add to .gitignore
            $gitignore = APPPATH . 'Config/.gitignore';
            $content   = file_exists($gitignore) ? file_get_contents($gitignore) : '';
            if (strpos($content, '.security_hash') === false) {
                file_put_contents($gitignore, $content . "\n.security_hash\n");
            }
        }

        return $result !== false;
    }

    /**
     * Check if license filter is active in globals before.
     */
    public static function isLicenseFilterActive(): bool
    {
        $config = config('Filters');

        if (empty($config->globals['before'])) {
            return false;
        }

        foreach ($config->globals['before'] as $key => $value) {
            // Format bisa: ['license'] atau ['license' => [...except...]]
            $filterName = is_string($key) ? $key : $value;
            if ($filterName === 'license') {
                return true;
            }
        }

        return false;
    }

    /**
     * Comprehensive security check.
     */
    public static function performSecurityCheck(): array
    {
        // 1. Check file integrity
        if (!self::verifyCriticalFiles()) {
            return [
                'status'  => false,
                'error'   => 'CRITICAL_FILE_MODIFIED',
                'message' => 'File sistem telah dimodifikasi. Silakan hubungi administrator.',
            ];
        }

        // 2. Check filter status
        if (!self::isLicenseFilterActive()) {
            return [
                'status'  => false,
                'error'   => 'LICENSE_FILTER_DISABLED',
                'message' => 'Sistem keamanan tidak aktif. Silakan hubungi administrator.',
            ];
        }

        // 3. Check environment
        if (!file_exists(APPPATH . '../.env')) {
            return [
                'status'  => false,
                'error'   => 'ENV_FILE_MISSING',
                'message' => 'File konfigurasi tidak ditemukan. Silakan hubungi administrator.',
            ];
        }

        return ['status' => true];
    }

    /**
     * Regenerate security hashes (for updates/maintenance).
     */
    public static function regenerateHashes(): bool
    {
        return self::generateSecurityHashes();
    }
}
