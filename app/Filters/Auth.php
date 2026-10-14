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
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success'  => false,
                        'message'  => 'Sesi Anda telah berakhir. Silakan login kembali.',
                        'redirect' => base_url('login'),
                    ]);
            }
            return redirect()->to('/login')->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        // ✅ cek role jika filter diberi argumen
        if ($arguments) {
            // CI4 otomatis memecah filter:role1,role2 menjadi array ['role1', 'role2']
            $allowedRoles = array_values(array_filter(array_map('intval', (array) $arguments), function ($v) {
                return $v > 0;
            }));

            if (!empty($allowedRoles) && !in_array((int) ($user['role_id'] ?? 0), $allowedRoles)) {
                // 🔐 Dynamic RBAC Check: Jika role user tidak terdaftar di nomor ID statis rute,
                // cek apakah role user memiliki permission yang sesuai untuk path rute ini
                helper('authorization');
                $path = trim($request->getUri()->getPath(), '/');
                $matchedPerms = \App\Libraries\MenuRegistry::getPermissionsForPath($path);

                $hasDynamicPerm = false;
                foreach ($matchedPerms as $perm) {
                    if (has_permission($perm)) {
                        $hasDynamicPerm = true;
                        break;
                    }
                }

                if (!$hasDynamicPerm) {
                    log_message('debug', 'Auth Filter REDIRECT: Role ' . ($user['role_id'] ?? 'NONE') . ' NOT IN ' . json_encode($allowedRoles) . ' and NO permission for ' . $path);
                    return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak perlu implementasi
    }
}
