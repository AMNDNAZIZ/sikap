<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\LicenseModel;

class LicenseFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = trim($request->getUri()->getPath(), '/');

        // Skip activation page, login, and static assets
        $segments = explode('/', $uri);
        $skip = ['activate', 'login', 'assets', 'favicon.ico', 'cbt/csrf/refresh'];

        foreach ($skip as $s) {
            if (in_array($s, $segments)) {
                return;
            }
        }

        $model = new LicenseModel();
        $license = $model->getActiveLicense();

        if (!$license) {
            return redirect()->to(base_url('activate'))->with('error', 'Aplikasi belum diaktivasi.');
        }

        // Check Expiry (Strict Enforcement)
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            // Auto-check renewal sebelum redirect
            $renewed = $this->attemptAutoRenewal($license, $model);
            
            if (!$renewed) {
                return redirect()->to(base_url('activate'))->with('error', 'Lisensi Anda telah kedaluwarsa. Hubungi pengembang untuk perpanjangan, lalu klik "Periksa Pembaruan" di halaman aktivasi.');
            }
        }

        // Hash Integrity Verification (Anti-tampering)
        $lsc = config('LService');
        $hashSecret = $lsc->getSecret();
        $expectedHash = hash('sha256', $license['license_key'] . $license['expires_at'] . $license['machine_id'] . $hashSecret);

        if ($license['hash'] !== $expectedHash) {
            log_message('critical', 'License hash mismatch detected! Possible tampering.');
            return redirect()->to(base_url('activate'))->with('error', 'Data lisensi tidak valid. Silakan aktivasi ulang.');
        }

        // Check cache (24 hours) - Auto renewal check
        $lastCheck = $license['last_check'] ? strtotime($license['last_check']) : 0;
        if (time() - $lastCheck > 86400) {
            $this->verifyLicense($license['license_key'], $model, $license['id']);
        }

        // Add expiry warning to session for dashboard notification
        if ($license['expires_at']) {
            $daysRemaining = ceil((strtotime($license['expires_at']) - time()) / 86400);
            if ($daysRemaining <= 30 && $daysRemaining > 0) {
                session()->setFlashdata('license_warning', "Lisensi akan berakhir dalam {$daysRemaining} hari. Segera hubungi pengembang untuk perpanjangan.");
            }
        }
    }

    /**
     * Attempt to auto-renew license when expired
     */
    protected function attemptAutoRenewal($license, $model)
    {
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
                'timeout' => 5,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success' && isset($result['data']['expiry'])) {
                $newExpiry = $result['data']['expiry'];
                
                // Check if renewed (new expiry is in the future)
                if (strtotime($newExpiry) > time()) {
                    // Regenerate hash
                    $hashSecret = $lsc->getSecret();
                    $newHash = hash('sha256', $license['license_key'] . $newExpiry . $machineId . $hashSecret);

                    $model->update($license['id'], [
                        'expires_at' => $newExpiry,
                        'last_check' => date('Y-m-d H:i:s'),
                        'hash' => $newHash
                    ]);

                    log_message('info', 'License auto-renewed successfully: ' . $license['license_key']);
                    return true;
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Auto-renewal check failed: ' . $e->getMessage());
        }

        return false;
    }

    protected function verifyLicense($key, $model, $id)
    {
        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        $domain = $_SERVER['HTTP_HOST'];

        // Machine ID (Unified with LicenseGuard)
        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain' => $domain,
                    'machine_id' => $machineId,
                ],
                'timeout' => 5,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // Get current license data
                $license = $model->find($id);

                $updateData = [
                    'last_check' => date('Y-m-d H:i:s'),
                    'domain' => $domain,
                    'machine_id' => $machineId
                ];

                if (isset($result['data']['expiry'])) {
                    $updateData['expires_at'] = $result['data']['expiry'];
                }

                // Regenerate hash with updated data
                $lsc = config('LService');
                $hashSecret = $lsc->getSecret();
                $newExpiresAt = $updateData['expires_at'] ?? $license['expires_at'];
                $updateData['hash'] = hash('sha256', $key . $newExpiresAt . $machineId . $hashSecret);

                $model->update($id, $updateData);
                
                // 🛡️ PROTEKSI: Update backup setelah renewal
                \App\Libraries\LicenseProtector::createBackup();
            } else {
                // If the server explicitly says Invalid/Suspended
                if (isset($result['status'])) {
                    $model->update($id, ['status' => 'suspended']);
                }
            }
        } catch (\Exception $e) {
            // Grace period: allow if server is unreachable
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
