<?php

/**
 * ROUTES CONFIGURATION FOR UPDATE SERVER
 * 
 * File ini untuk server update (subdomain terpisah)
 * Lokasi: update.cbtku.com/app/Config/Routes.php
 */

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', function() {
    return view('welcome_message');
});

// ============================================
// UPDATE API ROUTES
// ============================================

$routes->group('api', function($routes) {
    
    /**
     * Check for updates
     * GET /api/check?current_version=1.0.0&domain=sekolah.com
     * 
     * Response:
     * {
     *   "has_update": true,
     *   "latest_version": "1.0.1",
     *   "title": "Update Title",
     *   "changelog": {...},
     *   "file_size": 2048576,
     *   "file_hash": "abc123...",
     *   "download_url": "https://update.cbtku.com/api/download/1.0.1"
     * }
     */
    $routes->get('check', 'UpdateAPI::check');
    
    /**
     * Download update file
     * GET /api/download/{version}
     * 
     * Response: ZIP file stream
     */
    $routes->get('download/(:segment)', 'UpdateAPI::download/$1');
    
    /**
     * Log download activity (optional)
     * POST /api/log
     * 
     * Body:
     * {
     *   "version": "1.0.1",
     *   "domain": "sekolah.com",
     *   "status": "success"
     * }
     */
    $routes->post('log', 'UpdateAPI::log');
    
});

// ============================================
// ADMIN ROUTES (Optional - untuk manage updates)
// ============================================

$routes->group('admin', ['filter' => 'auth'], function($routes) {
    
    // Dashboard
    $routes->get('/', 'Admin\Dashboard::index');
    
    // Manage updates
    $routes->get('updates', 'Admin\Updates::index');
    $routes->get('updates/create', 'Admin\Updates::create');
    $routes->post('updates/store', 'Admin\Updates::store');
    $routes->get('updates/edit/(:num)', 'Admin\Updates::edit/$1');
    $routes->post('updates/update/(:num)', 'Admin\Updates::update/$1');
    $routes->post('updates/delete/(:num)', 'Admin\Updates::delete/$1');
    
    // Upload update file
    $routes->post('updates/upload', 'Admin\Updates::upload');
    
    // View logs
    $routes->get('logs', 'Admin\Logs::index');
    
});

// ============================================
// ERROR ROUTES
// ============================================

$routes->set404Override(function() {
    return view('errors/html/error_404');
});
