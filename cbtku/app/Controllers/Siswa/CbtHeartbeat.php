<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;

class CbtHeartbeat extends BaseController
{
    /**
     * Minimal heartbeat to keep session alive and provide fresh CSRF token
     * Accessed via GET to avoid CSRF check itself if not excluded
     */
    public function index()
    {
        return $this->response->setJSON([
            'status' => 'alive',
            'time' => date('Y-m-d H:i:s'),
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
}
