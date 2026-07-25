<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class CbtHeartbeat extends BaseController
{
    /**
     * Minimal heartbeat to keep session alive and provide fresh CSRF token
     */
    public function index()
    {
        // CSRF hash is already refreshed if regenerate is true, 
        // but we return it explicitly for the frontend.
        return $this->response->setJSON([
            'status' => 'alive',
            'time' => date('Y-m-d H:i:s'),
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
}
