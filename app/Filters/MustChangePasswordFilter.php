<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MustChangePasswordFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        $user = session()->get('user');
        
        // Check if user must change password
        if ($user && isset($user['must_change_password']) && $user['must_change_password'] == 1) {
            // Allow access to change password page and logout
            $uri = $request->getUri()->getPath();
            $allowedPaths = [
                'auth/change-password-required',
                'auth/update-password-required',
                'logout'
            ];
            
            foreach ($allowedPaths as $path) {
                if (strpos($uri, $path) !== false) {
                    return;
                }
            }
            
            // Redirect to change password page
            return redirect()->to('/auth/change-password-required')
                ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
