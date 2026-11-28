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

        $path = trim($request->getUri()->getPath(), '/');
        $wantsJson = $request->isAJAX()
            || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')
            || str_starts_with($path, 'siswa/cbt/save')
            || str_starts_with($path, 'siswa/cbt/ping')
            || str_starts_with($path, 'siswa/cbt/keepAlive')
            || str_starts_with($path, 'siswa/cbt/getTimerSync')
            || str_starts_with($path, 'siswa/cbt/submit');

        // ✅ cek apakah sudah login
        if (empty($user) || !$session->get('logged_in')) {
            if ($wantsJson) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'success'  => false,
                        'status'   => 'unauthenticated',
                        'message'  => 'Sesi Anda telah berakhir. Silakan login kembali.',
                        'redirect' => base_url('login'),
                    ]);
            }
            return redirect()->to('/login')->with('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        }

        // 🔐 Single-Device Session Enforcement for Students (role_id = 5)
        if ((int) ($user['role_id'] ?? 0) === 5 && !empty($user['id'])) {
            try {
                $db = db_connect();
                $userRow = $db->table('users')
                    ->select('is_active, active_session_id, last_activity')
                    ->where('id', (int) $user['id'])
                    ->get()
                    ->getRowArray();

                $deviceToken = $session->get('device_token');
                $dbToken = $userRow['active_session_id'] ?? null;
                $isActiveUser = (int) ($userRow['is_active'] ?? 0) === 1;

                if (!$userRow || !$isActiveUser || empty($dbToken) || $dbToken !== $deviceToken) {
                    $session->destroy();
                    $msg = 'Sesi login Anda telah di-reset oleh pengawas atau akun sedang digunakan di perangkat lain. Silakan login kembali.';
                    if ($wantsJson) {
                        return service('response')
                            ->setStatusCode(401)
                            ->setJSON([
                                'success'  => false,
                                'status'   => 'unauthenticated',
                                'message'  => $msg,
                                'error'    => $msg,
                                'redirect' => base_url('login'),
                            ]);
                    }
                    return redirect()->to('/login')->with('error', $msg);
                }

                // Throttled update last_activity (every 60s)
                $nowTs = time();
                if (($nowTs - (int) ($userRow['last_activity'] ?? 0)) >= 60) {
                    $db->table('users')
                        ->where('id', (int) $user['id'])
                        ->update(['last_activity' => $nowTs]);
                }
            } catch (\Throwable $e) {
                log_message('error', '[Filters\\Auth] Single-device check error: ' . $e->getMessage());
            }
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
                    if ($wantsJson) {
                        return service('response')
                            ->setStatusCode(403)
                            ->setJSON([
                                'success' => false,
                                'error'   => 'Anda tidak memiliki akses ke fitur ini.',
                                'message' => 'Anda tidak memiliki akses ke fitur ini.',
                            ]);
                    }
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
