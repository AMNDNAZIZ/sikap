<?php

/**
 * Password Helper
 * 
 * Helper functions untuk password security
 */

if (!function_exists('validate_password_strength')) {
    /**
     * Validate password strength
     * 
     * Requirements:
     * - Minimum 8 characters
     * - At least 1 uppercase letter
     * - At least 1 lowercase letter
     * - At least 1 number
     * - At least 1 special character
     * 
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    function validate_password_strength(string $password): array
    {
        $errors = [];
        
        // Minimum length
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter';
        }
        
        // Uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password harus mengandung minimal 1 huruf besar';
        }
        
        // Lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password harus mengandung minimal 1 huruf kecil';
        }
        
        // Number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password harus mengandung minimal 1 angka';
        }
        
        // Special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password harus mengandung minimal 1 karakter khusus (!@#$%^&*)';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

if (!function_exists('generate_strong_password')) {
    /**
     * Generate a strong random password
     * 
     * @param int $length
     * @return string
     */
    function generate_strong_password(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $all = $uppercase . $lowercase . $numbers . $special;
        
        // Ensure at least one of each type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }
}

if (!function_exists('check_password_history')) {
    /**
     * Check if password was used before
     * 
     * @param int $userId
     * @param string $password
     * @param int $historyCount Number of previous passwords to check
     * @return bool True if password is in history (should reject)
     */
    function check_password_history(int $userId, string $password, int $historyCount = 3): bool
    {
        $db = \Config\Database::connect();
        
        // Get user's password history
        $history = $db->table('password_history')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($historyCount)
            ->get()
            ->getResultArray();
        
        // Check if new password matches any in history
        foreach ($history as $record) {
            if (password_verify($password, $record['password_hash'])) {
                return true; // Password found in history
            }
        }
        
        return false; // Password not in history
    }
}

if (!function_exists('save_password_history')) {
    /**
     * Save password to history
     * 
     * @param int $userId
     * @param string $passwordHash
     * @return bool
     */
    function save_password_history(int $userId, string $passwordHash): bool
    {
        $db = \Config\Database::connect();
        
        try {
            // Save to history
            $db->table('password_history')->insert([
                'user_id' => $userId,
                'password_hash' => $passwordHash,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Keep only last 5 passwords
            $allHistory = $db->table('password_history')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();
            
            if (count($allHistory) > 5) {
                $toDelete = array_slice($allHistory, 5);
                $ids = array_column($toDelete, 'id');
                
                $db->table('password_history')
                    ->whereIn('id', $ids)
                    ->delete();
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to save password history: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_password_strength_indicator')) {
    /**
     * Get password strength indicator for UI
     * 
     * @param string $password
     * @return array ['strength' => string, 'score' => int, 'color' => string]
     */
    function get_password_strength_indicator(string $password): array
    {
        $score = 0;
        
        // Length
        if (strlen($password) >= 8) $score++;
        if (strlen($password) >= 12) $score++;
        if (strlen($password) >= 16) $score++;
        
        // Character types
        if (preg_match('/[A-Z]/', $password)) $score++;
        if (preg_match('/[a-z]/', $password)) $score++;
        if (preg_match('/[0-9]/', $password)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
        
        // Determine strength
        if ($score <= 2) {
            return ['strength' => 'Lemah', 'score' => $score, 'color' => 'danger'];
        } elseif ($score <= 4) {
            return ['strength' => 'Sedang', 'score' => $score, 'color' => 'warning'];
        } elseif ($score <= 6) {
            return ['strength' => 'Kuat', 'score' => $score, 'color' => 'success'];
        } else {
            return ['strength' => 'Sangat Kuat', 'score' => $score, 'color' => 'primary'];
        }
    }
}
