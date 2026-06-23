<?php

namespace App\Controllers;

use App\Models\LicenseModel;

class Activate extends BaseController
{
    public function index()
    {
        $model = new LicenseModel();
        $license = $model->getActiveLicense();

        if ($model->isValidLicense($license) && \App\Libraries\LicenseGuard::isFullyValid()) {
            return redirect()->to(base_url('dashboard'));
        }

        // Get existing license info (even if expired)
        $existingLicense = $model->first();

        return view('activate', [
            'existingLicense' => $existingLicense
        ]);
    }

    /**
     * Check for license renewal from server
     * Used when license is expired but user wants to check if it's been renewed
     */
    public function checkRenewal()
    {
        // Rate limiting
        $ip = $this->request->getIPAddress();
        $cache = \Config\Services::cache();
        
        // Sanitize IP for cache key (remove reserved characters)
        $cacheKey = 'renewal_attempts_' . md5($ip);
        $attempts = $cache->get($cacheKey) ?? 0;
        
        if ($attempts >= 10) {
            return redirect()->back()->with('error', 
                'Terlalu banyak percobaan cek pembaruan. Silakan coba lagi dalam 1 jam.');
        }
        
        $cache->save($cacheKey, $attempts + 1, 3600); // 1 hour

        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            return redirect()->back()->with('error', 'Tidak ada data lisensi. Silakan aktivasi dengan kode lisensi baru.');
        }

        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        log_message('info', 'Manual license renewal check - Key: ' . substr($license['license_key'], 0, 10) . '...');

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'renew'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // License renewed on server, update local data
                $updateData = [
                    'last_check' => date('Y-m-d H:i:s'),
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'status' => 'active'
                ];

                // Get new expiry from server
                if (isset($result['data']['expiry'])) {
                    $updateData['expires_at'] = $result['data']['expiry'];
                } else {
                    $updateData['expires_at'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                }

                // Regenerate hash with new data
                $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
                $updateData['hash'] = hash('sha256', $license['license_key'] . $updateData['expires_at'] . $machineId . $hashSecret);

                $model->update($license['id'], $updateData);

                // Regenerate semua hash file agar tidak ada gangguan lisensi ke depannya
                \App\Libraries\LicenseGuard::regenerateAllHashes();

                // Clear rate limit on successful renewal
                $cacheKey = 'renewal_attempts_' . md5($ip);
                $cache->delete($cacheKey);

                log_message('info', 'License renewed successfully via manual check');
                return redirect()->to(base_url('dashboard'))->with('success', 'Lisensi berhasil diperpanjang! Berlaku hingga: ' . date('d M Y', strtotime($updateData['expires_at'])));
            } else {
                $message = $result['message'] ?? 'Lisensi belum diperpanjang di server. Silakan hubungi administrator atau masukkan kode lisensi baru.';
                return redirect()->back()->with('error', $message);
            }
        } catch (\Exception $e) {
            log_message('error', 'Manual renewal check failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghubungi server lisensi. Pastikan koneksi internet aktif.');
        }
    }

    /**
     * Cek online via AJAX — apakah masa berlaku lisensi sudah diperbarui di server.
     * Jika iya, otomatis update data lokal dan kembalikan status sukses.
     * Endpoint: POST activate/checkOnline (JSON response)
     */
    public function checkOnline()
    {
        // Pastikan request adalah AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden']);
        }

        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Tidak ada data lisensi tersimpan. Silakan masukkan kode lisensi baru.',
            ]);
        }

        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain    = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain'      => $domain,
                    'machine_id'  => $machineId,
                    'action'      => 'verify',
                ],
                'timeout'     => 10,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);

            if (!isset($result['status'])) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Respons server tidak valid. Coba beberapa saat lagi.',
                ]);
            }

            if ($result['status'] === 'success') {
                // Ambil expiry dari server
                $newExpiry = $result['data']['expiry'] ?? null;

                // Bandingkan dengan expiry lokal
                $localExpiry  = $license['expires_at'];
                $expiryChanged = $newExpiry && ($newExpiry !== $localExpiry);
                $localExpired  = $localExpiry && strtotime($localExpiry) < time();
                $newExpired    = $newExpiry && strtotime($newExpiry) < time();

                // Jika expiry sudah diperbarui di server (lebih baru dari lokal) ATAU lisensi lokal expired tapi server bilang valid
                if ($expiryChanged || $localExpired) {
                    // Update data lokal
                    $updateData = [
                        'last_check' => date('Y-m-d H:i:s'),
                        'domain'     => $domain,
                        'machine_id' => $machineId,
                        'status'     => 'active',
                        'expires_at' => $newExpiry ?? date('Y-m-d H:i:s', strtotime('+1 year')),
                    ];

                    // Regenerate hash integrity
                    $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
                    $updateData['hash'] = hash('sha256',
                        $license['license_key'] . $updateData['expires_at'] . $machineId . $hashSecret
                    );

                    $model->update($license['id'], $updateData);

                    // Regenerate file hash agar tidak ada gangguan ke depannya
                    \App\Libraries\LicenseGuard::regenerateAllHashes();

                    log_message('info', 'License renewed via checkOnline. New expiry: ' . $updateData['expires_at']);

                    return $this->response->setJSON([
                        'status'      => 'renewed',
                        'message'     => 'Lisensi berhasil diperbarui! Berlaku hingga ' . date('d M Y', strtotime($updateData['expires_at'])) . '.',
                        'expires_at'  => date('d M Y H:i', strtotime($updateData['expires_at'])),
                        'redirect'    => base_url('dashboard'),
                    ]);
                }

                // Lisensi valid dan expiry tidak berubah
                return $this->response->setJSON([
                    'status'     => 'valid',
                    'message'    => 'Lisensi masih aktif dan belum ada pembaruan dari server.',
                    'expires_at' => $newExpiry ? date('d M Y H:i', strtotime($newExpiry)) : null,
                ]);

            } elseif ($result['status'] === 'expired') {
                return $this->response->setJSON([
                    'status'  => 'expired',
                    'message' => $result['message'] ?? 'Lisensi sudah kedaluwarsa di server. Silakan perpanjang lisensi Anda.',
                ]);
            } else {
                return $this->response->setJSON([
                    'status'  => 'invalid',
                    'message' => $result['message'] ?? 'Lisensi tidak dikenali oleh server.',
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'checkOnline failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Tidak dapat menghubungi server lisensi. Periksa koneksi internet Anda.',
            ]);
        }
    }

    public function process()    {
        // Rate limiting - prevent brute force
        $ip = $this->request->getIPAddress();
        $cache = \Config\Services::cache();
        
        // Sanitize IP for cache key (remove reserved characters)
        $cacheKey = 'activate_attempts_' . md5($ip);
        $attempts = $cache->get($cacheKey) ?? 0;
        
        if ($attempts >= 5) {
            return redirect()->back()->with('error', 
                'Terlalu banyak percobaan aktivasi. Silakan coba lagi dalam 1 jam.');
        }
        
        $cache->save($cacheKey, $attempts + 1, 3600); // 1 hour

        $key = $this->request->getPost('license_key');
        if (empty($key)) {
            return redirect()->back()->with('error', 'Token lisensi tidak boleh kosong.');
        }

        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        log_message('info', 'License Activation Attempt - Key: ' . substr($key, 0, 10) . '...');
        log_message('info', 'License Server URL: ' . $serverUrl);
        log_message('info', 'Domain: ' . $domain);
        log_message('info', 'Machine ID: ' . substr($machineId, 0, 30) . '...');

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
                $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
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

                // Regenerate semua hash file agar tidak ada gangguan lisensi ke depannya
                \App\Libraries\LicenseGuard::regenerateAllHashes();

                // Clear rate limit on successful activation
                $cacheKey = 'activate_attempts_' . md5($ip);
                $cache->delete($cacheKey);

                return redirect()->to(base_url('login'))->with('success', 'Aplikasi berhasil diaktivasi!');
            } else {
                $error = isset($result['message']) ? $result['message'] : 'Token lisensi tidak valid atau tidak terdaftar.';
                return redirect()->back()->with('error', $error);
            }
        } catch (\Exception $e) {
            log_message('error', 'License activation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menghubungi server lisensi. Pastikan koneksi internet aktif.');
        }
    }
}
