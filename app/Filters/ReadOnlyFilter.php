<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ReadOnlyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $user = $session->get('user');

        if (!$user) {
            return;
        }

        // Role 2 is Kepala Sekolah
        if ((int) $user['role_id'] === 2) {
            $method = strtolower($request->getMethod());

            // If not GET, it's a write operation (POST, PUT, DELETE, etc.)
            if ($method !== 'get') {
                $uri = $request->getUri()->getPath();

                // Allowed paths for Principal to write
                // Kepsek boleh write di: Agenda Kelas, Pengumuman, Obrolan Kelas, Obrolan Staff
                $allowedPaths = [
                    'admin/agendas',
                    'admin/announcements',
                    'admin/chat',
                    'admin/staff-chat',
                ];

                $isAllowed = false;
                foreach ($allowedPaths as $path) {
                    if (strpos($uri, $path) !== false) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return redirect()->back()->with('error', 'Hak akses Anda hanya baca (Read-Only) untuk modul ini.');
                }
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No implementation needed
    }
}
