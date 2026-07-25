<?php

/**
 * Environment Helper
 * 
 * Helper functions untuk deteksi environment (development/production)
 * dan kontrol akses developer tools
 */

if (!function_exists('is_development_environment')) {
    /**
     * Check if current environment is development
     * 
     * @return bool
     */
    function is_development_environment(): bool
    {
        // Define ROOTPATH if not defined
        if (!defined('ROOTPATH')) {
            define('ROOTPATH', realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR);
        }
        
        // Check 1: .dev_environment file exists
        if (file_exists(ROOTPATH . '.dev_environment')) {
            return true;
        }
        
        // Check 2: CI_ENVIRONMENT is development
        if (defined('CI_ENVIRONMENT') && CI_ENVIRONMENT === 'development') {
            return true;
        }
        
        // Check 3: ENVIRONMENT from .env
        $env = env('CI_ENVIRONMENT');
        if ($env === 'development') {
            return true;
        }
        
        // Check 4: Localhost detection
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '::1',
            '.local',
            '.test',
            '.dev'
        ];
        
        foreach ($localhostPatterns as $pattern) {
            if (
                stripos($serverName, $pattern) !== false ||
                stripos($httpHost, $pattern) !== false
            ) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('is_production_environment')) {
    /**
     * Check if current environment is production
     * 
     * @return bool
     */
    function is_production_environment(): bool
    {
        return !is_development_environment();
    }
}

if (!function_exists('require_development_environment')) {
    /**
     * Require development environment or die
     * Use this in developer-only scripts
     * 
     * @param string $message Custom error message
     * @return void
     */
    function require_development_environment(string $message = ''): void
    {
        if (!is_development_environment()) {
            $defaultMessage = 'This tool is only available in development environment.';
            $errorMessage = $message ?: $defaultMessage;
            
            if (is_cli()) {
                echo "ERROR: {$errorMessage}\n";
                exit(1);
            } else {
                http_response_code(403);
                die("
                    <h1>403 Forbidden</h1>
                    <p>{$errorMessage}</p>
                    <hr>
                    <small>Developer Tools - Localhost Only</small>
                ");
            }
        }
    }
}

if (!function_exists('developer_tools_enabled')) {
    /**
     * Check if developer tools are enabled
     * 
     * @return bool
     */
    function developer_tools_enabled(): bool
    {
        return is_development_environment();
    }
}

if (!function_exists('get_environment_name')) {
    /**
     * Get current environment name
     * 
     * @return string 'development' or 'production'
     */
    function get_environment_name(): string
    {
        return is_development_environment() ? 'development' : 'production';
    }
}

if (!function_exists('environment_badge')) {
    /**
     * Get HTML badge for current environment
     * 
     * @return string HTML badge
     */
    function environment_badge(): string
    {
        if (is_development_environment()) {
            return '<span style="background:#ffc107;color:#000;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:bold;">DEVELOPMENT</span>';
        } else {
            return '<span style="background:#28a745;color:#fff;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:bold;">PRODUCTION</span>';
        }
    }
}

if (!function_exists('show_developer_tools_warning')) {
    /**
     * Show warning if developer tools are accessible in production
     * 
     * @return string HTML warning or empty string
     */
    function show_developer_tools_warning(): string
    {
        if (is_production_environment()) {
            // Check if any developer tool files exist
            $devFiles = [
                'generate_license_hash.php',
                'verify_license_hash.php',
                'diagnostic.php',
                '.dev_environment'
            ];
            
            $foundFiles = [];
            foreach ($devFiles as $file) {
                if (file_exists(ROOTPATH . $file)) {
                    $foundFiles[] = $file;
                }
            }
            
            if (!empty($foundFiles)) {
                return '
                    <div style="background:#dc3545;color:#fff;padding:15px;margin:10px 0;border-radius:5px;">
                        <strong>⚠️ SECURITY WARNING:</strong> Developer tools detected in production!
                        <br>
                        <small>Files: ' . implode(', ', $foundFiles) . '</small>
                        <br>
                        <small>Please remove these files immediately.</small>
                    </div>
                ';
            }
        }
        
        return '';
    }
}
