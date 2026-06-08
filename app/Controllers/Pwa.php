<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Pwa extends BaseController
{
    /**
     * Serving manifest.json
     */
    public function manifest()
    {
        return $this->response
            ->setContentType('application/json')
            ->setBody(view('pwa/manifest'));
    }

    /**
     * Serving service-worker.js
     */
    public function serviceWorker()
    {
        return $this->response
            ->setContentType('application/javascript')
            ->setBody(view('pwa/service_worker'));
    }

    /**
     * Serving offline.html
     */
    public function offline()
    {
        return view('pwa/offline');
    }
}
