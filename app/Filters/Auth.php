<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Auth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('user');

        // ✅ cek apakah sudah login
        if (empty($user) || !$session->get('logged_in')) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // ✅ cek role jika filter diberi argumen
        if ($arguments) {
            // CI4 otomatis memecah filter:role1,role2 menjadi array ['role1', 'role2']
            $allowedRoles = array_values(array_filter(array_map('intval', (array) $arguments), function ($v) {
                return $v > 0;
            }));

            if (!empty($allowedRoles) && !in_array((int) ($user['role_id'] ?? 0), $allowedRoles)) {
                log_message('debug', 'Auth Filter REDIRECT: Role ' . ($user['role_id'] ?? 'NONE') . ' NOT IN ' . json_encode($allowedRoles));
                return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu implementasi
    }
}
