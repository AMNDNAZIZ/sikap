<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * License Service Configuration
 * 🔒 SECURITY: Secrets moved to environment variables
 */
class LService extends BaseConfig
{
    // URL server lisensi online
    public string $sUrl = 'aHR0cHM6Ly9saXNlbnNpLnNha2FzYWxpa2EuY29tL2FwaS92ZXJpZnkucGhw'; // https://lisensi.sakasalika.com/api/verify.php
    
    // 🔒 Secret key now from environment variable
    // Fallback to encoded value for backward compatibility
    public string $hSec = 'U2FLYVNhTGlLYTIwMjZTZWNyZXRLZXlGb3JIYXNoVmVyaWZpY2F0aW9u';

    public function getUrl(): string
    {
        // Allow override from environment
        $envUrl = env('LICENSE_SERVER_URL');
        if ($envUrl) {
            return $envUrl;
        }
        return base64_decode($this->sUrl);
    }

    public function getSecret(): string
    {
        // 🔒 SECURITY: Get from environment variable first
        $envSecret = env('LICENSE_SECRET_KEY');
        if ($envSecret) {
            return $envSecret;
        }
        
        // Fallback to encoded value (for backward compatibility)
        return base64_decode($this->hSec);
    }
}
