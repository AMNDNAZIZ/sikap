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
        helper('license');

        // Skip activation page, login, auth, and static assets
        $segments = explode('/', $uri);
        $skip = ['activate', 'login', 'auth', 'attemptLogin', 'logout', 'assets', 'favicon.ico', 'cbt/csrf/refresh'];

        foreach ($skip as $s) {
            if (in_array($s, $segments)) {
                return;
            }
        }

        // 🔒 OPTIMIZATION: Skip license check for AJAX/Background requests (Heartbeats, Fetch, etc.)
        // This prevents background pings from being blocked by slow external license server calls
        if ($request->hasHeader('X-Requested-With') && $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return;
        }

        // Additional skip for auth-related URIs (comprehensive)
        if (
            strpos($uri, 'login') !== false ||
            strpos($uri, 'logout') !== false ||
            strpos($uri, 'attemptLogin') !== false ||
            strpos($uri, 'activate') !== false
        ) {
            return;
        }

        $model = new LicenseModel();
        $license = $model->getActiveLicense();

        if (!$license) {
            return redirect()->to(base_url('activate'))->with('error', 'Aplikasi belum diaktivasi.');
        }

        // Check Expiry (Strict Enforcement)
        if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
            // Auto-check renewal sebelum redirect - SKIP on localhost
            $renewed = false;
            if (!is_local_environment()) {
                $renewed = $this->attemptAutoRenewal($license, $model);
            }

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
            // SKIP online check on local environment
            if (!is_local_environment()) {
                $this->verifyLicense($license['license_key'], $model, $license['id']);
            }
        }

        // Add expiry warning to session - ONLY on Dashboard to prevent annoyance
        if ($license['expires_at'] && ($uri === 'dashboard' || $uri === 'admin/dashboard')) {
            $daysRemaining = ceil((strtotime($license['expires_at']) - time()) / 86400);

            // Threshold reduced from 30 to 7 days as per user request
            if ($daysRemaining <= 7 && $daysRemaining > 0) {
                // Ensure session is active before setting flashdata
                if (session_status() === PHP_SESSION_NONE) {
                    session()->start();
                }
                session()->setFlashdata('license_warning', "PENTING: Lisensi aplikasi akan berakhir dalam {$daysRemaining} hari. Segera hubungi pengembang untuk perpanjangan agar layanan tidak terputus.");
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

        // 🔒 FIX: Releas session lock before synchronous CURL call
        // This prevents freezing other requests (heartbeat, etc.) while waiting for license server
        session_write_close();

        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'check_renewal'
                ],
                'timeout' => 3, // Reduced from 5 to 3
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

        // 🔒 FIX: Releas session lock before synchronous CURL call
        session_write_close();

        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain' => $domain,
                    'machine_id' => $machineId,
                ],
                'timeout' => 3, // Reduced from 5 to 3
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                // ... (success logic)
                $license = $model->find($id);
                $updateData = [
                    'last_check' => date('Y-m-d H:i:s'),
                    'domain' => $domain,
                    'machine_id' => $machineId
                ];

                if (isset($result['data']['expiry'])) {
                    $updateData['expires_at'] = $result['data']['expiry'];
                }

                $lsc = config('LService');
                $hashSecret = $lsc->getSecret();
                $newExpiresAt = $updateData['expires_at'] ?? $license['expires_at'];
                $updateData['hash'] = hash('sha256', $key . $newExpiresAt . $machineId . $hashSecret);

                $model->update($id, $updateData);
                \App\Libraries\LicenseProtector::createBackup();
            } else {
                // 🔒 SECURITY: Only suspend if definitely invalid according to server
                // If it's a general 'error' or network issue, keep the license 'active' (Grace Period)
                if (isset($result['status']) && in_array($result['status'], ['invalid', 'expired', 'suspended'])) {
                    $model->update($id, ['status' => 'suspended']);
                    log_message('critical', 'License suspended by server: ' . ($result['message'] ?? 'Unknown reason'));
                } else {
                    // Grace period - just log and continue
                    log_message('warning', 'License server returned an error but state was not suspended. Result: ' . json_encode($result));
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
