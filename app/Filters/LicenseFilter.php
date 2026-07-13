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
        $rawUri  = $_SERVER['REQUEST_URI'] ?? '';

        // Skip activation page, login, and static assets
        $skip = ['activate', 'login', 'assets', 'favicon.ico', 'maintenance'];
        foreach ($skip as $s) {
            if (strpos($uriPath, $s) !== false || strpos($rawUri, $s) !== false) {
                return;
            }
        }

        $model   = new LicenseModel();
        $license = $model->getActiveLicense();
        $error   = null;

        // Jika tidak ada lisensi sama sekali, coba cari record apapun (termasuk suspended)
        if (!$license) {
            // Cari record lisensi meski statusnya suspended
            $anyLicense = $model->first();

            if (!$anyLicense) {
                // Benar-benar tidak ada lisensi
                return redirect()->to(base_url('activate'))->with('error', 'Aplikasi belum diaktivasi.');
            }

            // Ada lisensi tapi suspended/inactive — coba recover dari server
            log_message('warning', 'License status is: ' . ($anyLicense['status'] ?? 'unknown') . '. Attempting recovery...');
            $recovered = $this->verifyAndRefreshHash($anyLicense['license_key'], $model, $anyLicense['id']);

            if ($recovered) {
                log_message('info', 'License recovered from server.');
                return; // Lanjutkan
            }

            // Recovery gagal — terapkan grace period sebelum redirect
            $cache     = \Config\Services::cache();
            $failCount = $cache->get('license_fail_count') ?? 0;

            if ($failCount > 5) {
                return redirect()->to(base_url('activate'))->with('error', 'Lisensi tidak aktif. Silakan hubungi administrator.');
            }

            $cache->save('license_fail_count', $failCount + 1, 86400);
            log_message('warning', 'License inactive, grace period ' . ($failCount + 1) . '/5');
            return; // Allow dalam grace period
        }

        // Lisensi ada dan status active
        if (!$model->isValidLicense($license, $error)) {
            log_message('info', 'License validation failed: ' . $error);

            // Untuk hash mismatch, coba refresh dari server dulu
            if (strpos($error ?? '', 'integrity mismatch') !== false) {
                $refreshed = $this->verifyAndRefreshHash($license['license_key'], $model, $license['id']);
                if ($refreshed) {
                    \Config\Services::cache()->delete('license_fail_count');
                    return;
                }
            }

            // Coba auto-renew
            $renewed = $this->attemptAutoRenewal($license['license_key'], $model, $license['id']);
            if ($renewed) {
                \Config\Services::cache()->delete('license_fail_count');
                return;
            }

            // Grace period — jangan langsung redirect
            $cache     = \Config\Services::cache();
            $failCount = $cache->get('license_fail_count') ?? 0;

            if ($failCount > 5) {
                return redirect()->to(base_url('activate'))->with('error', $error);
            }

            $cache->save('license_fail_count', $failCount + 1, 86400);
            log_message('warning', 'License invalid, grace period ' . ($failCount + 1) . '/5. Allowing continue.');
            return; // Allow dalam grace period
        }

        // Lisensi valid — reset fail count
        \Config\Services::cache()->delete('license_fail_count');

        // Periodic verification setiap 24 jam (non-blocking)
        $lastCheck = $license['last_check'] ? strtotime($license['last_check']) : 0;
        if (time() - $lastCheck > 86400) {
            $this->verifyLicense($license['license_key'], $model, $license['id']);
        }
    }

    /**
     * Verify license with server and refresh all local data if valid.
     * Also reactivates suspended license if server says it's valid.
     */
    protected function verifyAndRefreshHash($key, $model, $id): bool
    {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain    = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain'      => $domain,
                    'machine_id'  => $machineId,
                    'action'      => 'verify',
                ],
                'timeout'     => 8,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                $license    = $model->find($id);
                $expiresAt  = $result['data']['expiry'] ?? $license['expires_at'];
                $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
                $newHash    = hash('sha256', $license['license_key'] . $expiresAt . $machineId . $hashSecret);

                $model->update($id, [
                    'hash'       => $newHash,
                    'expires_at' => $expiresAt,
                    'machine_id' => $machineId,
                    'last_check' => date('Y-m-d H:i:s'),
                    'status'     => 'active', // Reactivate jika suspended
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                log_message('info', 'License verified & refreshed from server. Status set to active.');
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', 'verifyAndRefreshHash failed: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Periodic verification — update data tapi TIDAK suspend lisensi
     * karena koneksi gagal.
     */
    protected function verifyLicense($key, $model, $id)
    {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain    = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain'      => $domain,
                    'machine_id'  => $machineId,
                    'action'      => 'verify',
                ],
                'timeout'     => 5,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                $license      = $model->find($id);
                $hashSecret   = \App\Libraries\LicenseGuard::getHashSecret();
                $newExpiresAt = $result['data']['expiry'] ?? $license['expires_at'];
                $newHash      = hash('sha256', $license['license_key'] . $newExpiresAt . $machineId . $hashSecret);

                $model->update($id, [
                    'last_check' => date('Y-m-d H:i:s'),
                    'domain'     => $domain,
                    'machine_id' => $machineId,
                    'expires_at' => $newExpiresAt,
                    'status'     => 'active',
                    'hash'       => $newHash,
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                log_message('info', 'Periodic license verify OK. Expiry: ' . $newExpiresAt);

            } elseif (isset($result['status'])) {
                // Server bilang status tertentu — HANYA log, jangan suspend otomatis
                // Suspend otomatis berisiko karena bisa error sementara dari server
                log_message('warning', 'Periodic verify: server returned status=' . $result['status']
                    . ' msg=' . ($result['message'] ?? ''));
                // Tidak ada perubahan status di DB
            }
        } catch (\Exception $e) {
            // Koneksi gagal — TIDAK apa-apa, coba lagi besok
            log_message('warning', 'Periodic license verify failed (network): ' . $e->getMessage());
        }
    }

    /**
     * Attempt to auto-renew expired license from server.
     */
    protected function attemptAutoRenewal($key, $model, $id): bool
    {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $domain    = $_SERVER['HTTP_HOST'];

        helper('license');
        $machineId = generate_machine_id();

        $client = \Config\Services::curlrequest();
        try {
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $key,
                    'domain'      => $domain,
                    'machine_id'  => $machineId,
                    'action'      => 'renew',
                ],
                'timeout'     => 10,
                'http_errors' => false,
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                $newExpiresAt = $result['data']['expiry'] ?? date('Y-m-d H:i:s', strtotime('+1 year'));
                $license      = $model->find($id);
                $hashSecret   = \App\Libraries\LicenseGuard::getHashSecret();
                $newHash      = hash('sha256', $license['license_key'] . $newExpiresAt . $machineId . $hashSecret);

                $model->update($id, [
                    'last_check' => date('Y-m-d H:i:s'),
                    'domain'     => $domain,
                    'machine_id' => $machineId,
                    'status'     => 'active',
                    'expires_at' => $newExpiresAt,
                    'hash'       => $newHash,
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                log_message('info', 'License auto-renewed. New expiry: ' . $newExpiresAt);
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', 'Auto-renewal failed: ' . $e->getMessage());
        }
        return false;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
