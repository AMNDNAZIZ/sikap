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
        
        try {
            $encrypted = file_get_contents($configFile);
            $decrypted = self::decrypt($encrypted);
            self::$decryptedConfig = json_decode($decrypted, true);
        } catch (\Exception $e) {
            log_message('error', 'Failed to load secure config: ' . $e->getMessage());
            // Fallback to .env
            self::$decryptedConfig = [
                'license_server_url' => base64_decode(env('license.serverUrl', '')),
                'license_hash_secret' => base64_decode(env('license.hashSecret', '')),
                'license_encryption_key' => env('license.encryptionKey', '')
            ];
        }
    }
    
    /**
     * Encrypt configuration and save
     */
    public static function saveConfig($config)
    {
        try {
            $json = json_encode($config);
            $encrypted = self::encrypt($json);
            
            $configFile = APPPATH . 'Config/.secure_config';
            file_put_contents($configFile, $encrypted);
            
            // Add to .gitignore
            $gitignore = APPPATH . 'Config/.gitignore';
            $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';
            
            if (strpos($content, '.secure_config') === false) {
                file_put_contents($gitignore, $content . "\n.secure_config\n");
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to save secure config: ' . $e->getMessage());
            return false;
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
        
        if (self::saveConfig($config)) {
            log_message('info', 'License configuration migrated to encrypted storage');
            return true;
        }
        
        return false;
    }
}
