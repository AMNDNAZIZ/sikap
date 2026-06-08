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
                            $hashFile = APPPATH . 'Config/.lic_hash';
                            $filesToHash = [
                                APPPATH . 'Libraries/LicenseGuard.php',
                                APPPATH . 'Filters/LicenseFilter.php',
                                APPPATH . 'Models/LicenseModel.php',
                                APPPATH . 'Controllers/Activate.php',
                                APPPATH . 'Helpers/license_helper.php',
                                APPPATH . 'Config/LService.php',
                                APPPATH . 'Commands/LicenseVerify.php',
                                APPPATH . 'Commands/LicenseRestore.php',
                                APPPATH . 'Libraries/LicenseProtector.php',
                                APPPATH . 'Controllers/Admin/License.php',
                                APPPATH . 'Config/Events.php'
                            ];
                            
                            $hashes = [];
                            foreach ($filesToHash as $file) {
                                if (file_exists($file)) {
                                    $hashes[basename($file)] = hash_file('sha256', $file);
                                }
                            }
                            
                            file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));
                            
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
}
