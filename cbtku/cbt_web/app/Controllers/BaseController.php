<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\SchoolModel;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * @return void
     */

    protected $school;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        \App\Libraries\LicenseGuard::verify();
        parent::initController($request, $response, $logger);

        // 🔒 SECURITY HEADERS
        $this->setSecurityHeaders($response);

        // Set timezone agar konsisten dengan database
        date_default_timezone_set(config('App')->appTimezone ?? 'Asia/Jakarta');

        // 🔹 Gunakan cache bawaan CI4
        $cache = cache();
        $cachedSchool = $cache->get('school_profile');

        if ($cachedSchool) {
            // Jika ada di cache, gunakan data tersebut
            $this->school = $cachedSchool;
        } else {
            // Jika belum ada, ambil dari database
            $schoolModel = new SchoolModel();
            $this->school = $schoolModel->first();

            // Simpan di cache selama 24 jam (86400 detik)
            $cache->save('school_profile', $this->school, 86400);
        }

        // 🔹 Pastikan bisa diakses di semua view
        $view = \Config\Services::renderer();
        $view->setVar('school', $this->school);
    }

    /**
     * 🔒 Set Security Headers
     * Protects against XSS, Clickjacking, MIME sniffing, etc.
     */
    protected function setSecurityHeaders(\CodeIgniter\HTTP\ResponseInterface $response)
    {
        // Prevent MIME type sniffing
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        
        // Prevent clickjacking
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        
        // Enable XSS filter in browsers
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        
        // Control referrer information
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions policy (disable unnecessary features)
        $response->setHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // HSTS (only in production with HTTPS)
        if (ENVIRONMENT === 'production' && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
