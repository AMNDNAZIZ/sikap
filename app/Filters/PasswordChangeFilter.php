<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class PasswordChangeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('user');

        // Skip if not logged in
        if (!$user) {
            return;
        }

        $uri = $request->getUri()->getPath();

        // Skip for these routes
        $skipRoutes = [
            'auth/change-password-required',
            'auth/update-password-required',
            'profile/change-password',
            'profile/update-password',
            'logout',
            'assets',
            'favicon.ico'
        ];

        foreach ($skipRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return;
            }
        }

        // Check if user must change password
        if (isset($user['must_change_password']) && $user['must_change_password'] == 1) {
            // Redirect to dedicated change password page
            return redirect()->to(base_url('auth/change-password-required'))
                ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
