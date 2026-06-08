<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LicenseModel;

class License extends BaseController
{
    protected $licenseModel;

    public function __construct()
    {
        $this->licenseModel = new LicenseModel();
    }

    /**
     * Halaman status lisensi
     */
    public function index()
    {
        $license = $this->licenseModel->getActiveLicense();
        
        if (!$license) {
            return redirect()->to(base_url('activate'))->with('error', 'Aplikasi belum diaktivasi.');
        }

        // Hitung sisa hari
        $daysRemaining = null;
        $isExpired = false;
        $isNearExpiry = false;

        if ($license['expires_at']) {
            $expiryTime = strtotime($license['expires_at']);
            $now = time();
            $daysRemaining = ceil(($expiryTime - $now) / 86400);
            $isExpired = $daysRemaining < 0;
            $isNearExpiry = $daysRemaining <= 30 && $daysRemaining > 0;
        }

        $data = [
            'title' => 'Status Lisensi',
            'license' => $license,
            'daysRemaining' => $daysRemaining,
            'isExpired' => $isExpired,
            'isNearExpiry' => $isNearExpiry
        ];

        return view('admin/license/index', $data);
    }

    /**
     * Check renewal dari server (AJAX)
     */
    public function checkRenewal()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Lisensi tidak ditemukan'
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
                // Update lisensi jika ada perpanjangan
                if (isset($result['data']['expiry'])) {
                    $newExpiry = $result['data']['expiry'];
                    $oldExpiry = $license['expires_at'];

                    // Regenerate hash dengan expiry baru
                    $hashSecret = $lsc->getSecret();
                    $newHash = hash('sha256', $license['license_key'] . $newExpiry . $machineId . $hashSecret);

                    $this->licenseModel->update($license['id'], [
                        'expires_at' => $newExpiry,
                        'last_check' => date('Y-m-d H:i:s'),
                        'hash' => $newHash
                    ]);

                    // Cek apakah ada perpanjangan
                    if (strtotime($newExpiry) > strtotime($oldExpiry)) {
                        // 🛡️ PROTEKSI: Update backup & hash file setelah renewal
                        try {
                            \App\Libraries\LicenseProtector::createBackup();
                            
                            // Update checksum file (untuk verifyIntegrity)
                            $checksumFile = APPPATH . 'Config/.lic_checksum';
                            $data = [
                                'license_key' => $license['license_key'],
                                'machine_id' => $machineId,
                                'expires_at' => $newExpiry,
                                'created_at' => $license['created_at']
                            ];
                            $lsc = config('LService');
                            $secret = $lsc->getSecret();
                            $checksum = hash('sha256', json_encode($data) . $secret);
                            file_put_contents($checksumFile, $checksum);
                            
                            // Update hash file untuk file-file proteksi
                            // 🔧 FIX: Gunakan path relatif (bukan basename) agar match dengan LicenseGuard check
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
                                    $hashes[$file] = sha1_file($fullPath); // Gunakan SHA1 (bukan SHA256) dan path relatif
                                }
                            }
                            
                            $hashFile = APPPATH . 'Config/.lic_hash';
                            file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                            
                            log_message('info', 'License renewed and protection updated successfully');
                        } catch (\Exception $e) {
                            log_message('error', 'Failed to update protection after renewal: ' . $e->getMessage());
                        }
                        
                        return $this->response->setJSON([
                            'success' => true,
                            'renewed' => true,
                            'message' => 'Lisensi berhasil diperpanjang!',
                            'data' => [
                                'old_expiry' => $oldExpiry,
                                'new_expiry' => $newExpiry,
                                'days_remaining' => ceil((strtotime($newExpiry) - time()) / 86400)
                            ]
                        ]);
                    }
                }

                return $this->response->setJSON([
                    'success' => true,
                    'renewed' => false,
                    'message' => 'Lisensi masih aktif, tidak ada perpanjangan.',
                    'data' => [
                        'expiry' => $license['expires_at'],
                        'days_remaining' => ceil((strtotime($license['expires_at']) - time()) / 86400)
                    ]
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
     * Request domain transfer
     */
    public function requestTransfer()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Lisensi tidak ditemukan'
            ]);
        }

        $newDomain = $this->request->getPost('new_domain');
        
        if (empty($newDomain)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Domain baru harus diisi'
            ]);
        }

        // Validate domain format
        if (!filter_var('http://' . $newDomain, FILTER_VALIDATE_URL)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Format domain tidak valid'
            ]);
        }

        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $currentDomain = $_SERVER['HTTP_HOST'];
        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'old_domain' => $currentDomain,
                    'new_domain' => $newDomain,
                    'machine_id' => $machineId,
                    'action' => 'transfer_domain'
                ],
                'timeout' => 15,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Update domain di database lokal
                $this->licenseModel->update($license['id'], [
                    'domain' => $newDomain,
                    'last_check' => date('Y-m-d H:i:s')
                ]);

                // Update backup
                \App\Libraries\LicenseProtector::createBackup();

                log_message('info', 'Domain transfer successful: ' . $currentDomain . ' -> ' . $newDomain);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Transfer domain berhasil! Silakan akses aplikasi di domain baru: ' . $newDomain,
                    'data' => [
                        'old_domain' => $currentDomain,
                        'new_domain' => $newDomain
                    ]
                ]);
            } else {
                $message = isset($result['message']) ? $result['message'] : 'Gagal melakukan transfer domain';
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $message
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Domain transfer failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghubungi server lisensi. Periksa koneksi internet Anda.'
            ]);
        }
    }

    /**
     * Detect domain/machine change and show reactivation option
     */
    public function detectChange()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'changed' => false
            ]);
        }

        $currentDomain = $_SERVER['HTTP_HOST'];
        $registeredDomain = $license['domain'];
        
        helper('license');
        $currentMachine = generate_machine_id();
        $registeredMachine = $license['machine_id'];

        // Check if domain or machine changed
        $domainChanged = !empty($registeredDomain) && $registeredDomain !== $currentDomain;
        $machineChanged = !empty($registeredMachine) && $registeredMachine !== $currentMachine;

        return $this->response->setJSON([
            'success' => true,
            'changed' => $domainChanged || $machineChanged,
            'domain_changed' => $domainChanged,
            'machine_changed' => $machineChanged,
            'current_domain' => $currentDomain,
            'registered_domain' => $registeredDomain,
            'status' => $license['status'] ?? 'active'
        ]);
    }

    /**
     * Request reactivation (untuk domain/machine change)
     */
    public function requestReactivation()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Lisensi tidak ditemukan'
            ]);
        }

        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $currentDomain = $_SERVER['HTTP_HOST'];
        helper('license');
        $currentMachine = generate_machine_id();

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'old_domain' => $license['domain'],
                    'new_domain' => $currentDomain,
                    'old_machine_id' => $license['machine_id'],
                    'new_machine_id' => $currentMachine,
                    'action' => 'request_reactivation'
                ],
                'timeout' => 15,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Update license dengan data baru
                $updateData = [
                    'domain' => $currentDomain,
                    'machine_id' => $currentMachine,
                    'status' => 'active',
                    'last_check' => date('Y-m-d H:i:s')
                ];

                // Update expiry jika ada dari server
                if (isset($result['data']['expiry'])) {
                    $updateData['expires_at'] = $result['data']['expiry'];
                }

                $this->licenseModel->update($license['id'], $updateData);

                // Update backup & protection
                \App\Libraries\LicenseProtector::createBackup();

                // 🔐 GENERATE HASH: Generate hash file untuk integrity check
                $this->generateLicenseHash();
                log_message('info', 'License hash generated after reactivation');

                log_message('info', 'License reactivation successful');

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Reactivation berhasil! Aplikasi sekarang aktif di domain/hosting baru.',
                    'data' => [
                        'domain' => $currentDomain,
                        'machine_id' => substr($currentMachine, 0, 16) . '...',
                        'expires_at' => $updateData['expires_at'] ?? null
                    ]
                ]);
            } else {
                $message = isset($result['message']) ? $result['message'] : 'Gagal melakukan reactivation';
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $message
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'License reactivation failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghubungi server lisensi. Periksa koneksi internet Anda.'
            ]);
        }
    }

    /**
     * Check current license status
     */
    public function checkStatus()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Lisensi tidak ditemukan'
            ]);
        }

        helper('license');
        $currentDomain = $_SERVER['HTTP_HOST'];
        $currentMachine = generate_machine_id();

        $domainMatch = $license['domain'] === $currentDomain;
        $machineMatch = $license['machine_id'] === $currentMachine;

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'license_key' => substr($license['license_key'], 0, 8) . '...',
                'status' => $license['status'] ?? 'active',
                'domain_match' => $domainMatch,
                'machine_match' => $machineMatch,
                'registered_domain' => $license['domain'],
                'current_domain' => $currentDomain,
                'expires_at' => $license['expires_at'],
                'last_check' => $license['last_check']
            ]
        ]);
    }

    /**
     * Deactivate license (untuk testing atau transfer)
     */
    public function deactivate()
    {
        $license = $this->licenseModel->getActiveLicense();

        if (!$license) {
            return redirect()->back()->with('error', 'Tidak ada lisensi aktif.');
        }

        // Optional: Notify server about deactivation
        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $client = \Config\Services::curlrequest();

        try {
            $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'action' => 'deactivate'
                ],
                'timeout' => 5,
                'http_errors' => false
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }

        // Delete local license
        $this->licenseModel->delete($license['id']);

        return redirect()->to(base_url('activate'))->with('success', 'Lisensi berhasil dinonaktifkan.');
    }

    /**
     * Generate license hash file
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
            return false;
        }

        log_message('info', 'Hash file generated successfully with ' . count($hashes) . ' files');
        return true;
    }
}
