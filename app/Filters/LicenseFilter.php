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
        $uriPath = $request->getUri()->getPath();
        $rawUri = $_SERVER['REQUEST_URI'] ?? '';

        // Skip activation page, login, and static assets
        $skip = ['activate', 'login', 'assets', 'favicon.ico', 'maintenance'];
        foreach ($skip as $s) {
            if (strpos($uriPath, $s) !== false || strpos($rawUri, $s) !== false) {
                return;
            }
        }

        $model = new LicenseModel();
        $license = $model->getActiveLicense();
        $error = null;

        // Jika tidak ada lisensi sama sekali, redirect ke activate
        if (!$license) {
            return redirect()->to(base_url('activate'))->with('error', 'Aplikasi belum diaktivasi.');
        }

        // Jika lisensi expired atau invalid, coba auto-renew dari server
        if (!$model->isValidLicense($license, $error)) {
            // isValidLicense sudah menangani hash check dengan benar
            // Langsung coba auto-renew
            log_message('info', 'License expired/invalid. Attempting auto-renewal from server...');
            $renewed = $this->attemptAutoRenewal($license['license_key'], $model, $license['id']);

            if (!$renewed) {
                // Check if we are still within grace period even for invalid hash
                // (Maybe environment changed slightly, e.g. domain alias)
                $cache = \Config\Services::cache();
                $failCount = $cache->get('license_fail_count') ?? 0;

                if ($failCount > 3) {
                    return redirect()->to(base_url('activate'))->with('error', $error);
                } else {
                    log_message('warning', 'License hash mismatch but within grace period. Fail count: ' . $failCount);
                    // Allow to continue but keep periodic check active
                }
            }

            // Jika berhasil auto-renew, lanjutkan (tidak redirect)
            log_message('info', 'License auto-renewed successfully!');
        }

        // Check cache (24 hours) - periodic verification
        $lastCheck = $license['last_check'] ? strtotime($license['last_check']) : 0;
        if (time() - $lastCheck > 86400) {
            $this->verifyLicense($license['license_key'], $model, $license['id']);
        }
    }

    /**
     * Periodic verification (every 24 hours)
     * Updates license data from server if available
     */
    protected function verifyLicense($key, $model, $id)
    {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'verify'
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
                    'machine_id' => $machineId,
                    'status' => 'active' // Ensure status is active
                ];

                // Update expiry if provided by server
                if (isset($result['data']['expiry'])) {
                    $updateData['expires_at'] = $result['data']['expiry'];
                    log_message('info', 'License expiry updated from server: ' . $result['data']['expiry']);
                }

                // Regenerate hash with updated data
                $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
                $newExpiresAt = $updateData['expires_at'] ?? $license['expires_at'];
                $updateData['hash'] = hash('sha256', $key . $newExpiresAt . $machineId . $hashSecret);

                $model->update($id, $updateData);

                // Regenerate semua hash file agar sinkron dengan data terbaru
                \App\Libraries\LicenseGuard::regenerateAllHashes();

                log_message('info', 'License verified and updated successfully');
            } else {
                // If the server explicitly says Invalid/Suspended
                if (isset($result['status']) && in_array($result['status'], ['invalid', 'suspended', 'expired'])) {
                    $model->update($id, ['status' => 'suspended']);
                    log_message('warning', 'License suspended by server: ' . ($result['message'] ?? 'Unknown reason'));
                }
            }
        } catch (\Exception $e) {
            // GRACE PERIOD: Track failed attempts instead of immediate suspension
            log_message('error', 'License verification failed (grace period check): ' . $e->getMessage());

            $cache = \Config\Services::cache();
            $failCount = $cache->get('license_fail_count') ?? 0;
            $failCount++;
            $cache->save('license_fail_count', $failCount, 86400); // 24 hours

            // Allow up to 5 failed attempts (approx 5 days of periodic check)
            if ($failCount > 5) {
                $model->update($id, ['status' => 'suspended']);
                log_message('critical', 'License suspended after ' . $failCount . ' failed verification attempts');
            } else {
                log_message('warning', 'License verification failed. Grace period: attempt ' . $failCount . ' of 5');
            }
        }
    }

    /**
     * Attempt to auto-renew expired license from server
     * Returns true if renewal successful, false otherwise
     */
    protected function attemptAutoRenewal($key, $model, $id)
    {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
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
                    // Default: extend 1 year from now
                    $updateData['expires_at'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                }

                // Regenerate hash with new data
                $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
                $updateData['hash'] = hash('sha256', $key . $updateData['expires_at'] . $machineId . $hashSecret);

                $model->update($id, $updateData);

                // Regenerate semua hash file agar tidak ada gangguan lisensi ke depannya
                \App\Libraries\LicenseGuard::regenerateAllHashes();

                log_message('info', 'License auto-renewed successfully. New expiry: ' . $updateData['expires_at']);
                return true;
            } else {
                // Server says license cannot be renewed
                $message = $result['message'] ?? 'License tidak dapat diperpanjang';
                log_message('warning', 'Auto-renewal failed: ' . $message);
                return false;
            }
        } catch (\Exception $e) {
            // Server unreachable, cannot auto-renew
            log_message('error', 'Auto-renewal failed - server unreachable: ' . $e->getMessage());
            return false;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
