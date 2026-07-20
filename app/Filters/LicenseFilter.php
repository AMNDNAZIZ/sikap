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

        // Skip halaman yang tidak perlu cek lisensi
        $skipPatterns = ['activate', 'login', 'assets', 'favicon.ico', 'maintenance', 'auth/'];
        foreach ($skipPatterns as $s) {
            if (strpos($uriPath, $s) !== false || strpos($rawUri, $s) !== false) {
                return;
            }
        }

        $model   = new LicenseModel();
        $license = $model->getActiveLicense();

        // Tidak ada lisensi aktif — coba cari yang suspended/expired untuk recovery
        if (!$license) {
            $anyLicense = $model->first();
            if (!$anyLicense) {
                return redirect()->to(base_url('activate'))
                    ->with('error', 'Aplikasi belum diaktivasi.');
            }

            // Ada lisensi tapi tidak active — coba recover dari server
            $recovered = $this->tryRecoverFromServer($anyLicense['license_key'], $model, $anyLicense['id']);
            if ($recovered) {
                return; // Berhasil recover
            }

            // Terapkan grace period sebelum redirect
            return $this->handleGracePeriod(
                redirect()->to(base_url('activate'))->with('error', 'Status lisensi tidak valid.')
            );
        }

        // Lisensi ada tapi gagal validasi lokal
        $error = null;
        if (!$model->isValidLicense($license, $error)) {
            log_message('warning', 'LicenseFilter: validation failed - ' . $error);

            // Hash mismatch → coba refresh dari server sebelum apapun
            if (strpos($error ?? '', 'integrity mismatch') !== false) {
                $refreshed = $this->tryRecoverFromServer($license['license_key'], $model, $license['id']);
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

            // Grace period — beri kesempatan sebelum block
            return $this->handleGracePeriod(
                redirect()->to(base_url('activate'))->with('error', $error)
            );
        }

        // Lisensi valid — reset fail count dan lakukan periodic check
        \Config\Services::cache()->delete('license_fail_count');

        $lastCheck = $license['last_check'] ? strtotime($license['last_check']) : 0;
        if (time() - $lastCheck > 86400) {
            // Background check — non-blocking, tidak ubah status lisensi
            $this->periodicCheck($license['license_key'], $model, $license['id']);
        }
    }

    /**
     * Grace period: izinkan sampai 5 kali gagal sebelum redirect.
     * Mencegah false positive karena gangguan jaringan sementara.
     */
    private function handleGracePeriod($redirectResponse)
    {
        $cache     = \Config\Services::cache();
        $failCount = (int)($cache->get('license_fail_count') ?? 0);

        if ($failCount >= 5) {
            return $redirectResponse;
        }

        $cache->save('license_fail_count', $failCount + 1, 86400);
        log_message('warning', 'License grace period: attempt ' . ($failCount + 1) . '/5, allowing continue.');
        return null; // Lanjutkan
    }

    /**
     * Coba recover lisensi dari server.
     * Berhasil jika server bilang valid → update DB dan hash lokal.
     */
    protected function tryRecoverFromServer($key, $model, $id): bool
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
                $license   = $model->find($id);
                $expiresAt = $result['data']['expiry'] ?? ($license['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+1 year')));
                $secret    = \App\Libraries\LicenseGuard::getHashSecret();
                $newHash   = hash('sha256', $license['license_key'] . $expiresAt . $machineId . $secret);

                $model->update($id, [
                    'status'     => 'active',
                    'hash'       => $newHash,
                    'expires_at' => $expiresAt,
                    'machine_id' => $machineId,
                    'last_check' => date('Y-m-d H:i:s'),
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                \Config\Services::cache()->delete('license_fail_count');
                log_message('info', 'License recovered from server. Status set to active.');
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', 'tryRecoverFromServer failed: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Auto-renew lisensi expired.
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
                $newExpiry = $result['data']['expiry'] ?? date('Y-m-d H:i:s', strtotime('+1 year'));
                $license   = $model->find($id);
                $secret    = \App\Libraries\LicenseGuard::getHashSecret();
                $newHash   = hash('sha256', $license['license_key'] . $newExpiry . $machineId . $secret);

                $model->update($id, [
                    'status'     => 'active',
                    'expires_at' => $newExpiry,
                    'machine_id' => $machineId,
                    'hash'       => $newHash,
                    'last_check' => date('Y-m-d H:i:s'),
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                log_message('info', 'License auto-renewed. New expiry: ' . $newExpiry);
                return true;
            }
        } catch (\Exception $e) {
            log_message('error', 'attemptAutoRenewal failed: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Periodic background check — TIDAK mengubah status lisensi.
     * Hanya update expiry dan hash jika server mengembalikan data baru.
     */
    protected function periodicCheck($key, $model, $id): void
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
                $newExpiresAt = $result['data']['expiry'] ?? $license['expires_at'];
                $secret       = \App\Libraries\LicenseGuard::getHashSecret();
                $newHash      = hash('sha256', $license['license_key'] . $newExpiresAt . $machineId . $secret);

                $model->update($id, [
                    'last_check' => date('Y-m-d H:i:s'),
                    'expires_at' => $newExpiresAt,
                    'machine_id' => $machineId,
                    'status'     => 'active', // Pastikan tetap active
                    'hash'       => $newHash,
                ]);

                \App\Libraries\LicenseGuard::regenerateAllHashes();
                log_message('info', 'Periodic check OK. Expiry: ' . $newExpiresAt);
            } else {
                // Server mengembalikan error — HANYA log, TIDAK ubah status DB
                log_message('warning', 'Periodic check: server returned ' . ($result['status'] ?? 'unknown'));
            }
        } catch (\Exception $e) {
            // Koneksi gagal — TIDAK apa-apa, coba lagi besok
            log_message('warning', 'Periodic check failed (network): ' . $e->getMessage());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
