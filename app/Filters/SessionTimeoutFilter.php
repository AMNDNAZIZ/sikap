<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Session Timeout Filter
 * 
 * Checks if user session has expired based on last activity
 * Implements automatic session timeout for security
 */
class SessionTimeoutFilter implements FilterInterface
{
    /**
     * Session timeout in seconds (4 hours - increased from 2 hours)
     * This should match or be less than session.expiration in Config/Session.php
     */
    const SESSION_TIMEOUT = 14400; // 4 hours

    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Skip if not logged in
        if (!$session->get('logged_in')) {
            return;
        }
        
        // Skip for AJAX requests to prevent disrupting form submissions
        if ($request->isAJAX()) {
            // Still update last activity for AJAX requests
            $session->set('last_activity', time());
            return;
        }
        
        $lastActivity = $session->get('last_activity');
        
        // If last_activity is not set, initialize it (for existing sessions)
        if (!$lastActivity) {
            $session->set('last_activity', time());
            return;
        }
        
        // Check if session has expired
        if (time() - $lastActivity > self::SESSION_TIMEOUT) {
            // Session expired - destroy and redirect to login
            $session->destroy();
            
            return redirect()->to('/login')
                ->with('error', 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.');
        }
        
        // Update last activity timestamp
        $session->set('last_activity', time());
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
