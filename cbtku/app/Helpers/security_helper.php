<?php

/**
 * Security Helper
 * 
 * Additional security functions for the application
 */

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename untuk file upload
     * Prevents directory traversal and malicious filenames
     * 
     * @param string $filename
     * @return string
     */
    function sanitize_filename($filename)
    {
        // Remove any path information
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }
}

if (!function_exists('validate_file_type')) {
    /**
     * Validate file type berdasarkan MIME type
     * 
     * @param object $file CodeIgniter file object
     * @param array $allowedTypes Array of allowed MIME types
     * @return bool
     */
    function validate_file_type($file, array $allowedTypes)
    {
        if (!$file || !$file->isValid()) {
            return false;
        }
        
        $mimeType = $file->getMimeType();
        return in_array($mimeType, $allowedTypes);
    }
}

if (!function_exists('validate_file_size')) {
    /**
     * Validate file size
     * 
     * @param object $file CodeIgniter file object
     * @param int $maxSize Maximum size in bytes
     * @return bool
     */
    function validate_file_size($file, $maxSize)
    {
        if (!$file || !$file->isValid()) {
            return false;
        }
        
        return $file->getSize() <= $maxSize;
    }
}

if (!function_exists('safe_redirect')) {
    /**
     * Safe redirect - prevents open redirect vulnerability
     * 
     * @param string $url
     * @param string $default Default URL if validation fails
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    function safe_redirect($url, $default = '/')
    {
        // Only allow relative URLs or same-origin URLs
        $parsed = parse_url($url);
        
        // If no host, it's a relative URL (safe)
        if (!isset($parsed['host'])) {
            return redirect()->to($url);
        }
        
        // If host matches current host, it's safe
        if ($parsed['host'] === $_SERVER['HTTP_HOST']) {
            return redirect()->to($url);
        }
        
        // Otherwise, redirect to default
        return redirect()->to($default);
    }
}

if (!function_exists('generate_csrf_token')) {
    /**
     * Generate CSRF token for AJAX requests
     * 
     * @return array
     */
    function generate_csrf_token()
    {
        return [
            'name' => csrf_token(),
            'hash' => csrf_hash()
        ];
    }
}

if (!function_exists('is_safe_url')) {
    /**
     * Check if URL is safe (same origin)
     * 
     * @param string $url
     * @return bool
     */
    function is_safe_url($url)
    {
        $parsed = parse_url($url);
        
        // Relative URLs are safe
        if (!isset($parsed['host'])) {
            return true;
        }
        
        // Same host is safe
        return $parsed['host'] === $_SERVER['HTTP_HOST'];
    }
}

if (!function_exists('sanitize_html')) {
    /**
     * Sanitize HTML content (for rich text editors)
     * Allows safe HTML tags only
     * 
     * @param string $html
     * @return string
     */
    function sanitize_html($html)
    {
        // Allowed tags for rich text content
        $allowedTags = '<p><br><strong><em><u><ol><ul><li><a><img><table><tr><td><th><thead><tbody><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        
        // Strip tags except allowed
        $html = strip_tags($html, $allowedTags);
        
        // Remove javascript: and data: protocols from links
        $html = preg_replace('/(<a[^>]+href=[\"\'])(javascript:|data:)/i', '$1#', $html);
        
        // Remove on* event handlers
        $html = preg_replace('/<([^>]+)\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '<$1', $html);
        
        return $html;
    }
}

if (!function_exists('log_security_event')) {
    /**
     * Log security-related events
     * 
     * @param string $event Event type
     * @param string $message Event message
     * @param array $context Additional context
     * @return void
     */
    function log_security_event($event, $message, array $context = [])
    {
        $logger = \Config\Services::logger();
        
        $logData = [
            'event' => $event,
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];
        
        $logger->warning('SECURITY: ' . $event, $logData);
    }
}
