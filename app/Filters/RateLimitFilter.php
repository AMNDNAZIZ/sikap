<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limiting Filter
 * 
 * Mencegah brute force attacks dengan membatasi jumlah request
 * dari IP address yang sama dalam periode waktu tertentu
 */
class RateLimitFilter implements FilterInterface
{
    /**
     * Maximum attempts allowed
     */
    protected int $maxAttempts = 5;
    
    /**
     * Time window in seconds
     */
    protected int $decaySeconds = 300; // 5 minutes
    
    /**
     * Check rate limit before request
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $cache = \Config\Services::cache();
        $ipAddress = $request->getIPAddress();
        $key = 'rate_limit_' . md5($ipAddress . $request->getUri()->getPath());
        
        // Get current attempts
        $attempts = $cache->get($key) ?? 0;
        
        // Check if rate limit exceeded
        if ($attempts >= $this->maxAttempts) {
            $ttl = $cache->getMetadata($key)['expire'] ?? time();
            $remainingTime = max(0, $ttl - time());
            
            log_message('warning', "Rate limit exceeded for IP: {$ipAddress}");
            
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'error' => 'Too many requests',
                    'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam ' . ceil($remainingTime / 60) . ' menit.',
                    'retry_after' => $remainingTime
                ]);
        }
        
        // Increment attempts
        $cache->save($key, $attempts + 1, $this->decaySeconds);
        
        return $request;
    }
    
    /**
     * After request (cleanup on success)
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // If login successful (status 200 or 302), clear rate limit
        $statusCode = $response->getStatusCode();
        
        if (in_array($statusCode, [200, 302])) {
            $cache = \Config\Services::cache();
            $ipAddress = $request->getIPAddress();
            $key = 'rate_limit_' . md5($ipAddress . $request->getUri()->getPath());
            
            // Clear attempts on successful login
            $cache->delete($key);
        }
        
        return $response;
    }
}
