<?php

/**
 * Security Helper
 * 
 * Helper functions untuk keamanan aplikasi
 */

if (!function_exists('generate_secure_password')) {
    /**
     * Generate secure random password
     * 
     * @param int $length Password length (default: 12)
     * @return string Random password
     */
    function generate_secure_password($length = 12): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        // Ensure at least one of each type
        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill the rest
        $allChars = $lowercase . $uppercase . $numbers . $special;
        $remaining = $length - 4;
        
        for ($i = 0; $i < $remaining; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle to randomize position
        return str_shuffle($password);
    }
}

if (!function_exists('send_password_email')) {
    /**
     * Send password via email
     * 
     * @param string $email Recipient email
     * @param string $password Plain text password
     * @param string $username Username
     * @param string $name Full name
     * @return bool Success status
     */
    function send_password_email($email, $password, $username, $name = ''): bool
    {
        try {
            $emailService = \Config\Services::email();
            
            $schoolModel = new \App\Models\SchoolModel();
            $school = $schoolModel->first();
            $schoolName = $school['name'] ?? 'SIAKAD';
            
            $emailService->setTo($email);
            $emailService->setSubject('Akun ' . $schoolName . ' Anda');
            
            $message = view('emails/new_account', [
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'schoolName' => $schoolName,
                'loginUrl' => base_url('login')
            ]);
            
            $emailService->setMessage($message);
            
            return $emailService->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send password email: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('validate_strong_password')) {
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    function validate_strong_password($password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf kecil';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password harus mengandung huruf besar';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password harus mengandung angka';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename untuk upload
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    function sanitize_filename($filename): string
    {
        // Get extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove special characters
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        
        // Limit length
        $name = substr($name, 0, 50);
        
        return $name . '.' . $ext;
    }
}

if (!function_exists('is_safe_redirect_url')) {
    /**
     * Check if redirect URL is safe (same domain)
     * 
     * @param string $url URL to check
     * @return bool Safe status
     */
    function is_safe_redirect_url($url): bool
    {
        // Empty URL is safe (will redirect to default)
        if (empty($url)) {
            return true;
        }
        
        // Relative URLs are safe
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }
        
        // Check if same domain
        $urlHost = parse_url($url, PHP_URL_HOST);
        $baseHost = parse_url(base_url(), PHP_URL_HOST);
        
        return $urlHost === $baseHost;
    }
}

if (!function_exists('log_security_event')) {
    /**
     * Log security-related events
     * 
     * @param string $event Event type
     * @param string $message Event message
     * @param array $context Additional context
     */
    function log_security_event($event, $message, $context = []): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logMessage = sprintf(
            '[SECURITY] %s | IP: %s | UA: %s | %s',
            $event,
            $ip,
            substr($userAgent, 0, 100),
            $message
        );
        
        if (!empty($context)) {
            $logMessage .= ' | Context: ' . json_encode($context);
        }
        
        log_message('warning', $logMessage);
    }
}

if (!function_exists('generate_default_password')) {
    /**
     * Generate default password berdasarkan role dan identifier
     * Untuk kemudahan distribusi massal
     * 
     * @param string $role Role (guru/siswa/ortu)
     * @param string $identifier NIP/NIS/etc
     * @return string Default password
     */
    function generate_default_password(string $role, string $identifier): string
    {
        // Bersihkan identifier dari karakter khusus
        $clean = strtolower(str_replace([' ', '.', ',', '-', '_'], '', $identifier));
        
        switch ($role) {
            case 'guru':
            case 'teacher':
            case '3': // role_id guru
                return 'guru' . $clean;
                
            case 'siswa':
            case 'student':
            case '4': // role_id siswa
                return 'siswa' . $clean;
                
            case 'ortu':
            case 'parent':
            case '5': // role_id orang tua
                return 'ortu' . $clean;
                
            default:
                return 'user' . $clean;
        }
    }
}

if (!function_exists('is_using_default_password')) {
    /**
     * Check if user is using default password pattern
     * 
     * @param string $hashedPassword Hashed password from database
     * @param string $role User role
     * @param string $identifier NIP/NIS/etc
     * @return bool True if using default password
     */
    function is_using_default_password(string $hashedPassword, string $role, string $identifier): bool
    {
        $defaultPassword = generate_default_password($role, $identifier);
        return password_verify($defaultPassword, $hashedPassword);
    }
}
