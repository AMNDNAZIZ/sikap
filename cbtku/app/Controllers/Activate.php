<?php

namespace App\Controllers;

use App\Models\LicenseModel;

class Activate extends BaseController
{
    public function index()
    {
        $model = new LicenseModel();
        $license = $model->getActiveLicense();

        // Check if license exists and valid for THIS environment
        if ($license) {
            $isExpired = $license['expires_at'] && strtotime($license['expires_at']) < time();
            
            // Check if machine_id and domain match
            helper('license');
            $currentMachineId = generate_machine_id();
            $currentDomain = $_SERVER['HTTP_HOST'] ?? '';
            
            $machineMatch = ($license['machine_id'] === $currentMachineId);
            $domainMatch = ($license['domain'] === $currentDomain);
            
            // Only redirect if:
            // 1. License not expired
            // 2. Machine ID matches (or empty)
            // 3. Domain matches (or empty)
            if (!$isExpired && ($machineMatch || empty($license['machine_id'])) && ($domainMatch || empty($license['domain']))) {
                
                // 🛡️ FIX: Prevent infinite redirect loop by checking integrity FIRST
                if (\App\Libraries\LicenseGuard::checkIntegrity(false)) {
                    // 🔒 FIX: Check if user is logged in before redirecting
                    // If not logged in, redirect to login page (not dashboard)
                    if (session()->get('logged_in')) {
                        return redirect()->to(base_url('dashboard'));
                    } else {
                        return redirect()->to(base_url('login'))->with('success', 'Aplikasi sudah diaktivasi. Silakan login.');
                    }
                } else {
                    // Integrity failed, stay on activate page to force user to re-activate (sync hash)
                    session()->setFlashdata('error', 'Integritas file sistem gagal diverifikasi (File Modified). Silakan klik tombol "Periksa Pembaruan" atau aktivasi ulang.');
                }
            }
            
            // If machine_id or domain doesn't match, stay on activate page
            // User needs to input NEW license key for this environment
            log_message('info', 'License exists but machine/domain/integrity mismatch - showing activate form');
        }

        return view('activate');
    }

    public function process()
    {
        $key = $this->request->getPost('license_key');
        if (empty($key)) {
            return redirect()->back()->with('error', 'Token lisensi tidak boleh kosong.');
        }

        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $domain = $_SERVER['HTTP_HOST'];
        helper('license');
        $machineId = generate_machine_id();

        $debugMode = false;

        if ($debugMode) {
            echo "<h3>DEBUG MODE - License Activation</h3>";
            echo "<pre>";
            echo "Server URL: " . $serverUrl . "\n";
            echo "License Key: " . $key . "\n";
            echo "Domain: " . $domain . "\n";
            echo "Machine ID: " . substr($machineId, 0, 50) . "...\n";
            echo "Action: activate\n\n";
            echo "Sending request...\n";
            flush();
        }
        log_message('info', 'License Activation Attempt - Key: ' . $key);
        log_message('info', 'License Server URL: ' . $serverUrl);
        log_message('info', 'Domain: ' . $domain);
        log_message('info', 'Machine ID: ' . substr($machineId, 0, 30));

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'activate'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $responseBody = $response->getBody();
            $result = json_decode($responseBody, true);

            if (isset($result['status']) && $result['status'] === 'success') {
                $model = new LicenseModel();

                // Clear existing (optional, or just add new)
                $model->truncate();

                $expiresAt = isset($result['data']['expiry']) ? $result['data']['expiry'] : date('Y-m-d H:i:s', strtotime('+1 year'));

                // Generate hash for integrity verification
                $lsc = config('LService');
                $hashSecret = $lsc->getSecret();
                $hash = hash('sha256', $key . $expiresAt . $machineId . $hashSecret);

                $model->insert([
                    'license_key' => $key,
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'status' => 'active',
                    'last_check' => date('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt,
                    'hash' => $hash
                ]);

                // 🛡️ PROTEKSI: Create backup & markers
                \App\Libraries\LicenseProtector::createBackup();
                \App\Libraries\LicenseProtector::createMarkers();

                // 🔐 Deep Cleanup: Ensure all local markers are fresh and synced
                $this->deepCleanupMarkers();

                log_message('info', 'License activated and deep cleanup completed');

                // Set session flag untuk bypass LicenseGuard check sementara
                session()->set('license_just_activated', true);

                return redirect()->to(base_url('login'))->with('success', 'Aplikasi berhasil diaktivasi!');
            } else {
                $error = isset($result['message']) ? $result['message'] : 'Token lisensi tidak valid atau tidak terdaftar.';
                return redirect()->back()->with('error', $error);
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghubungi server lisensi. Pastikan koneksi internet aktif.');
        }
    }

    /**
     * Check renewal dari halaman activate (untuk expired license)
     */
    public function checkRenewal()
    {
        $model = new LicenseModel();
        $license = $model->first(); // Ambil lisensi meski expired

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada lisensi yang terdaftar. Silakan aktivasi terlebih dahulu.'
            ]);
        }

        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $domain = $_SERVER['HTTP_HOST'];
        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'check_renewal'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                if (isset($result['data']['expiry'])) {
                    $newExpiry = $result['data']['expiry'];
                    $oldExpiry = $license['expires_at'];

                    // Check if renewed (new expiry is in the future)
                    if (strtotime($newExpiry) > time()) {
                        // Regenerate hash
                        $hashSecret = $lsc->getSecret();
                        $newHash = hash('sha256', $license['license_key'] . $newExpiry . $machineId . $hashSecret);

                        $model->update($license['id'], [
                            'expires_at' => $newExpiry,
                            'last_check' => date('Y-m-d H:i:s'),
                            'status' => 'active',
                            'hash' => $newHash
                        ]);

                        // 🛡️ PROTEKSI: Update backup & hash file setelah renewal
                        try {
                            // Deep Cleanup for renewal as well
                            $this->deepCleanupMarkers();
                            log_message('info', 'License renewed and protection deep-cleaned successfully');
                        } catch (\Exception $e) {
                            log_message('error', 'Failed to update protection after renewal: ' . $e->getMessage());
                        }

                        // Set session flag untuk bypass LicenseGuard check sementara
                        session()->set('license_just_activated', true);

                        return $this->response->setJSON([
                            'success' => true,
                            'renewed' => true,
                            'message' => 'Lisensi berhasil diperpanjang! Anda akan diarahkan ke dashboard.'
                        ]);
                    }
                }

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Lisensi belum diperpanjang oleh pengembang. Silakan hubungi pengembang terlebih dahulu.'
                ]);
            } else {
                $message = isset($result['message']) ? $result['message'] : 'Gagal memeriksa status lisensi';
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $message
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'License renewal check failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghubungi server lisensi. Periksa koneksi internet Anda.'
            ]);
        }
    }

    /**
     * Generate license hash file setelah aktivasi berhasil
     */
    private function generateLicenseHash()
    {
        $protectedFiles = [
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Controllers/Activate.php',
            'Controllers/BaseController.php',
            'Controllers/Admin/License.php',
            'Libraries/LicenseGuard.php',
            'Libraries/LicenseProtector.php',
            'Helpers/license_helper.php',
            'Config/Filters.php',
            'Config/LService.php',
            'Config/Events.php'
        ];

        $hashes = [];
        foreach ($protectedFiles as $file) {
            $fullPath = APPPATH . $file;
            
            if (file_exists($fullPath)) {
                $hashes[$file] = sha1_file($fullPath);
            } else {
                log_message('warning', 'Protected file not found during hash generation: ' . $file);
            }
        }

        $hashFile = APPPATH . 'Config/.lic_hash';
        $json = json_encode($hashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $result = @file_put_contents($hashFile, $json);
        
        if ($result === false) {
            log_message('error', 'Failed to write hash file: permission denied');
            // Don't fail activation, just log the error
            return false;
        }

        // Verify hash file was created successfully
        if (!file_exists($hashFile)) {
            log_message('error', 'Hash file not found after write attempt');
            return false;
        }

        log_message('info', 'Hash file generated successfully with ' . count($hashes) . ' files');
        return true;
    }

    /**
     * Perform deep cleanup and sync of all local license markers
     */
    private function deepCleanupMarkers()
    {
        try {
            // 1. Force Backup & Checksum (re-generate)
            \App\Libraries\LicenseProtector::createBackup();
            
            // 2. Force Marker re-creation
            \App\Libraries\LicenseProtector::createMarkers();
            
            // 3. Force Hash re-generation
            $this->generateLicenseHash();
            
            // 4. Update throttle cache so it doesn't immediately re-check
            $cache = cache();
            $cache->save('license_protector_last_init', time(), 3600);
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Deep cleanup failed: ' . $e->getMessage());
            return false;
        }
    }
}
