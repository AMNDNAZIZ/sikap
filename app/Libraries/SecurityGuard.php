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
            'Libraries/SecurityGuard.php',
            'Libraries/SecureConfig.php',
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Config/Filters.php'
        ];
        
        $hashFile = APPPATH . 'Config/.security_hash';
        
        if (!file_exists($hashFile)) {
            // First run - generate hashes automatically
            log_message('info', 'Security hash file not found, generating...');
            self::generateSecurityHashes();
            return true;
        }
        
        $expectedHashes = json_decode(file_get_contents($hashFile), true);
        
        if (!is_array($expectedHashes)) {
            log_message('warning', 'Invalid security hash file format, regenerating...');
            self::generateSecurityHashes();
            return true; // Don't block on invalid format
        }
        
        foreach ($criticalFiles as $file) {
            $path = APPPATH . $file;
            
            if (!file_exists($path)) {
                log_message('critical', "Critical file missing: {$file}");
                return false;
            }
            
            $currentHash = hash_file('sha256', $path);
            
            if (!isset($expectedHashes[$file])) {
                // File not in hash list, regenerate
                log_message('warning', "File not in hash list: {$file}, regenerating...");
                self::generateSecurityHashes();
                return true;
            }
            
            if ($currentHash !== $expectedHashes[$file]) {
                log_message('critical', "File tampering detected: {$file}");
                log_message('critical', "Expected hash: " . substr($expectedHashes[$file], 0, 16) . "...");
                log_message('critical', "Current hash: " . substr($currentHash, 0, 16) . "...");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate security hashes for critical files
     * Called automatically on first run or when needed
     */
    public static function generateSecurityHashes()
    {
        $criticalFiles = [
            'Helpers/license_helper.php',
            'Libraries/LicenseGuard.php',
            'Libraries/SecurityGuard.php',
            'Libraries/SecureConfig.php',
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
        $result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));
        
        if ($result !== false) {
            log_message('info', 'Security hashes generated for ' . count($hashes) . ' files');
            
            // Add to .gitignore
            $gitignore = APPPATH . 'Config/.gitignore';
            $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';
            
            if (strpos($content, '.security_hash') === false) {
                file_put_contents($gitignore, $content . "\n.security_hash\n");
            }
        }
        
        return $result !== false;
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
                'error' => 'CRITICAL_FILE_MODIFIED',
                'message' => 'File sistem telah dimodifikasi. Silakan hubungi administrator.'
            ];
        }
        
        // 2. Check filter status
        if (!self::isLicenseFilterActive()) {
            return [
                'status' => false,
                'error' => 'LICENSE_FILTER_DISABLED',
                'message' => 'Sistem keamanan tidak aktif. Silakan hubungi administrator.'
            ];
        }
        
        // 3. Check environment
        if (!file_exists(APPPATH . '../.env')) {
            return [
                'status' => false,
                'error' => 'ENV_FILE_MISSING',
                'message' => 'File konfigurasi tidak ditemukan. Silakan hubungi administrator.'
            ];
        }
        
        return ['status' => true];
    }
    
    /**
     * Regenerate security hashes (for updates/maintenance)
     * Should be called after legitimate file updates
     */
    public static function regenerateHashes()
    {
        return self::generateSecurityHashes();
    }
}
