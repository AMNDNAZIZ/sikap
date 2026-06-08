<?php

/**
 * UPDATE SERVER API CONTROLLER
 * 
 * File ini untuk diupload ke server update (subdomain terpisah)
 * Lokasi: update.cbtku.com/app/Controllers/UpdateAPI.php
 */

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class UpdateAPI extends ResourceController
{
    protected $modelName = 'App\Models\UpdateModel';
    protected $format = 'json';
    
    /**
     * 🔍 CHECK UPDATE
     * Endpoint: GET /api/check
     * 
     * Query params:
     * - current_version: Versi saat ini di client
     * - domain: Domain client (opsional, untuk tracking)
     */
    public function check()
    {
        $currentVersion = $this->request->getGet('current_version') ?? '1.0.0';
        $domain = $this->request->getGet('domain') ?? 'unknown';
        
        // Log request
        log_message('info', "[Update API] Check from {$domain}, current: {$currentVersion}");
        
        try {
            // Get latest update
            $latest = $this->model
                ->where('is_active', 1)
                ->orderBy('version', 'DESC')
                ->first();
            
            if (!$latest) {
                return $this->respond([
                    'has_update' => false,
                    'message' => 'Tidak ada update tersedia'
                ]);
            }
            
            // Compare versions
            $hasUpdate = version_compare($latest['version'], $currentVersion, '>');
            
            if (!$hasUpdate) {
                return $this->respond([
                    'has_update' => false,
                    'message' => 'Aplikasi sudah versi terbaru',
                    'current_version' => $currentVersion,
                    'latest_version' => $latest['version']
                ]);
            }
            
            // Return update info
            return $this->respond([
                'has_update' => true,
                'current_version' => $currentVersion,
                'latest_version' => $latest['version'],
                'release_date' => $latest['release_date'],
                'title' => $latest['title'],
                'description' => $latest['description'],
                'changelog' => $latest['changelog'],
                'file_size' => $latest['file_size'],
                'file_hash' => $latest['file_hash'],
                'is_critical' => (bool) $latest['is_critical'],
                'download_url' => base_url('api/download/' . $latest['version'])
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Update API] Check failed: ' . $e->getMessage());
            
            return $this->fail('Server error: ' . $e->getMessage(), 500);
        }
    }

    
    /**
     * 📥 DOWNLOAD UPDATE
     * Endpoint: GET /api/download/{version}
     * 
     * Path param:
     * - version: Versi yang akan didownload
     */
    public function download($version = null)
    {
        if (empty($version)) {
            return $this->failNotFound('Version tidak ditemukan');
        }
        
        try {
            // Get update info
            $update = $this->model->where('version', $version)->first();
            
            if (!$update) {
                return $this->failNotFound('Update version ' . $version . ' tidak ditemukan');
            }
            
            // Get file path
            $filePath = WRITEPATH . 'updates/' . $update['file_name'];
            
            if (!file_exists($filePath)) {
                log_message('error', '[Update API] File not found: ' . $filePath);
                return $this->failNotFound('File update tidak ditemukan di server');
            }
            
            // Increment download count
            $this->model->update($update['id'], [
                'download_count' => $update['download_count'] + 1
            ]);
            
            // Log download
            log_message('info', "[Update API] Download v{$version} | Total downloads: " . 
                ($update['download_count'] + 1));
            
            // Stream file
            return $this->response
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $update['file_name'] . '"')
                ->setHeader('Content-Length', filesize($filePath))
                ->setHeader('X-File-Hash', $update['file_hash'])
                ->setBody(file_get_contents($filePath));
            
        } catch (\Throwable $e) {
            log_message('error', '[Update API] Download failed: ' . $e->getMessage());
            
            return $this->fail('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 📊 LOG DOWNLOAD (Optional)
     * Endpoint: POST /api/log
     * 
     * Body:
     * - version: Versi yang didownload
     * - domain: Domain client
     * - status: success/failed
     */
    public function log()
    {
        $data = $this->request->getJSON(true);
        
        // Log to database or file
        log_message('info', '[Update API] Log: ' . json_encode($data));
        
        return $this->respond([
            'success' => true,
            'message' => 'Log recorded'
        ]);
    }
}
