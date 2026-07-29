<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = session()->get('role');

        // Jika belum login
        if (!$role) return redirect()->to('/login');

        // Jika role tidak termasuk dalam yang diizinkan
        if ($arguments && !in_array($role, $arguments)) {
            return redirect()->to('/admin/dashboard')->with('msg', 'Akses ditolak.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
