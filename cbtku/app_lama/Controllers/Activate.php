<?php

namespace App\Controllers;

use App\Models\LicenseModel;

class Activate extends BaseController
{
    public function index()
    {
        $model = new LicenseModel();
        $license = $model->getActiveLicense();

        // Only redirect to dashboard if license exists AND is NOT expired
        if ($license) {
            $isExpired = $license['expires_at'] && strtotime($license['expires_at']) < time();
            if (!$isExpired) {
                return redirect()->to(base_url('dashboard'));
            }
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
}
