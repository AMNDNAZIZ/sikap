<?php

/**
 * ENHANCEMENT CODE FOR UPDATER CONTROLLER
 * 
 * Tambahkan method-method ini ke dalam class Updater yang sudah ada
 * di app/Controllers/Admin/Updater.php
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UpdaterEnhancement extends BaseController
{
    /**
     * 🌐 CHECK ONLINE UPDATE
     * Cek update terbaru dari server
     */
    public function checkOnlineUpdate()
    {
        // Check if online update is enabled
        $updateServerUrl = env('UPDATE_SERVER_URL');
        
        if (empty($updateServerUrl)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Update server tidak dikonfigurasi. Tambahkan UPDATE_SERVER_URL di file .env'
            ]);
        }
        
        // Get current version
        $currentVersion = $this->getCurrentVersion();
        
        try {
            // Call update server API
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($updateServerUrl . '/api/check', [
                'query' => [
                    'current_version' => $currentVersion,
                    'domain' => $_SERVER['HTTP_HOST'] ?? 'unknown'
                ],
                'timeout' => 10,
                'verify' => false // Set true in production with valid SSL
            ]);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            
            if ($statusCode !== 200) {
                throw new \Exception('Server returned status: ' . $statusCode);
            }
            
            $data = json_decode($body, true);
            
            if (!$data) {
                throw new \Exception('Invalid response from server');
            }
            
            // Log check
            log_message('info', '[Online Update] Check completed. Current: ' . $currentVersion . 
                ', Latest: ' . ($data['latest_version'] ?? 'unknown'));
            
            return $this->response->setJSON([
                'success' => true,
                'current_version' => $currentVersion,
                'data' => $data
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Online Update] Check failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghubungi server update: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * 📥 DOWNLOAD UPDATE
     * Download file update dari server
     */
    public function downloadUpdate()
    {
        $version = $this->request->getPost('version');
        $fileHash = $this->request->getPost('file_hash');
        
        if (empty($version)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Version tidak valid'
            ]);
        }
        
        $updateServerUrl = env('UPDATE_SERVER_URL');
        
        if (empty($updateServerUrl)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Update server tidak dikonfigurasi'
            ]);
        }
        
        // Ensure download directory exists
        $downloadDir = WRITEPATH . 'updates/downloads/';
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }
        
        $filename = 'update_' . $version . '.zip';
        $filepath = $downloadDir . $filename;
        
        try {
            log_message('info', '[Online Update] Downloading version: ' . $version);
            
            // Download file
            $client = \Config\Services::curlrequest();
            
            $response = $client->get($updateServerUrl . '/api/download/' . $version, [
                'timeout' => 300, // 5 minutes for large files
                'verify' => false,
                'save_to' => $filepath
            ]);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Download failed with status: ' . $response->getStatusCode());
            }
            
            // Verify file exists
            if (!file_exists($filepath)) {
                throw new \Exception('Downloaded file not found');
            }
            
            // Verify file hash
            $downloadedHash = hash_file('sha256', $filepath);
            
            if (!empty($fileHash) && $downloadedHash !== $fileHash) {
                unlink($filepath); // Delete corrupted file
                throw new \Exception('File hash mismatch. File mungkin corrupt.');
            }
            
            $fileSize = filesize($filepath);
            
            log_message('info', '[Online Update] Download completed. Size: ' . 
                number_format($fileSize / 1024 / 1024, 2) . ' MB');
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Download berhasil',
                'file_path' => $filepath,
                'file_size' => $fileSize,
                'file_hash' => $downloadedHash
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Online Update] Download failed: ' . $e->getMessage());
            
            // Cleanup failed download
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal download: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * 🚀 INSTALL ONLINE UPDATE
     * Install update yang sudah didownload
     */
    public function installOnlineUpdate()
    {
        $filepath = $this->request->getPost('file_path');
        $version = $this->request->getPost('version');
        
        if (empty($filepath) || !file_exists($filepath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File update tidak ditemukan'
            ]);
        }
        
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Extension PHP Zip tidak aktif'
            ]);
        }
        
        try {
            log_message('info', '[Online Update] Installing version: ' . $version);
            
            // Validate ZIP
            $zip = new \ZipArchive();
            if ($zip->open($filepath) !== TRUE) {
                throw new \Exception('Gagal membuka file ZIP');
            }
            
            // Get list of files
            $filesToUpdate = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (substr($filename, -1) !== '/') {
                    $filesToUpdate[] = $filename;
                }
            }
            
            // Backup before update
            $backupDir = $this->backupBeforeUpdate($filesToUpdate);
            log_message('info', '[Online Update] Backup created: ' . $backupDir);
            
            // Check for migrations
            $hasMigrations = $this->checkZipForMigrations($zip);
            
            // Extract files
            $extractResult = $this->extractZipSafely($zip, ROOTPATH);
            $zip->close();
            
            if (!$extractResult['success']) {
                throw new \Exception('Gagal extract beberapa file: ' . 
                    implode(', ', array_slice($extractResult['failed'], 0, 3)));
            }
            
            log_message('info', '[Online Update] Extracted ' . 
                count($extractResult['extracted']) . ' files');
            
            // Check license files
            $licenseFilesUpdated = $this->checkLicenseFilesUpdated($extractResult['extracted']);
            
            if ($licenseFilesUpdated) {
                $this->regenerateLicenseHash();
                log_message('info', '[Online Update] License hash regenerated');
            }
            
            // Run migrations
            $migrationResult = ['success' => true, 'message' => 'No migrations'];
            if ($hasMigrations) {
                $migrationResult = $this->autoRunMigrations();
            }
            
            // Update version in Constants.php
            $this->updateVersionConstant($version);
            
            // Cleanup
            $this->cleanupOldBackups(7);
            unlink($filepath); // Delete downloaded ZIP
            
            log_message('info', '[Online Update] Installation completed successfully');
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Update berhasil diinstall! Versi: ' . $version,
                'details' => [
                    'files_updated' => count($extractResult['extracted']),
                    'backup_dir' => basename($backupDir),
                    'migrations' => $migrationResult['message'],
                    'license_updated' => $licenseFilesUpdated
                ]
            ]);
            
        } catch (\Throwable $e) {
            log_message('error', '[Online Update] Installation failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal install update: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * 📌 GET CURRENT VERSION
     * Ambil versi saat ini dari Constants.php
     */
    private function getCurrentVersion()
    {
        // Try to get from Constants
        if (defined('APP_VERSION')) {
            return APP_VERSION;
        }
        
        // Try to read from Constants.php file
        $constantsPath = APPPATH . 'Config/Constants.php';
        if (file_exists($constantsPath)) {
            $content = file_get_contents($constantsPath);
            
            // Match: define('APP_VERSION', '1.0.0');
            if (preg_match("/define\('APP_VERSION',\s*'([^']+)'\)/", $content, $matches)) {
                return $matches[1];
            }
        }
        
        return '1.0.0'; // Default
    }
    
    /**
     * 📝 UPDATE VERSION CONSTANT
     * Update versi di Constants.php
     */
    private function updateVersionConstant($newVersion)
    {
        $constantsPath = APPPATH . 'Config/Constants.php';
        
        if (!file_exists($constantsPath)) {
            log_message('warning', 'Constants.php not found, cannot update version');
            return false;
        }
        
        $content = file_get_contents($constantsPath);
        
        // Update APP_VERSION
        $pattern = "/define\('APP_VERSION',\s*'[^']+'\);/";
        $replacement = "define('APP_VERSION', '{$newVersion}');";
        
        if (preg_match($pattern, $content)) {
            $newContent = preg_replace($pattern, $replacement, $content);
            
            // Also update LAST_UPDATE
            $newDate = date('Y-m-d H:i');
            $datePattern = "/define\('LAST_UPDATE',\s*'[^']*'\);/";
            $dateReplacement = "define('LAST_UPDATE', '{$newDate}');";
            $newContent = preg_replace($datePattern, $dateReplacement, $newContent);
            
            file_put_contents($constantsPath, $newContent);
            log_message('info', 'Version updated to: ' . $newVersion);
            return true;
        }
        
        return false;
    }
}
