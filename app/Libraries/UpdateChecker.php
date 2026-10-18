<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserModel;

class UpdateChecker
{
    private const CACHE_KEY = 'system_update_check_result';
    private const CACHE_TTL_SUCCESS = 21600; // 6 jam
    private const CACHE_TTL_FAIL = 1800;    // 30 menit jika offline/gagal

    private const GITHUB_USER = 'Sakajaya';
    private const GITHUB_REPO = 'espata-update';
    private const GITHUB_BRANCH = 'main';
    private const MANIFEST_FILE = 'update_manifest.json';

    /**
     * Dapatkan daftar URL mirror manifest di GitHub & CDN
     */
    public static function getManifestUrls(): array
    {
        $cacheBuster = '?t=' . time();
        return [
            sprintf('https://raw.githubusercontent.com/%s/%s/%s/%s', self::GITHUB_USER, self::GITHUB_REPO, self::GITHUB_BRANCH, self::MANIFEST_FILE),
            sprintf('https://cdn.jsdelivr.net/gh/%s/%s@%s/%s%s', self::GITHUB_USER, self::GITHUB_REPO, self::GITHUB_BRANCH, self::MANIFEST_FILE, $cacheBuster),
            sprintf('https://fastly.jsdelivr.net/gh/%s/%s@%s/%s%s', self::GITHUB_USER, self::GITHUB_REPO, self::GITHUB_BRANCH, self::MANIFEST_FILE, $cacheBuster),
        ];
    }

    /**
     * URL raw manifest di GitHub (backward compatibility)
     */
    public static function getManifestUrl(): string
    {
        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            self::GITHUB_USER,
            self::GITHUB_REPO,
            self::GITHUB_BRANCH,
            self::MANIFEST_FILE
        );
    }

    /**
     * Periksa ketersediaan update.
     * Menggunakan cache 6 jam agar tidak membebani network / GitHub limit.
     *
     * @param bool $force Jika true, abaikan cache dan lakukan pengecekan langsung ke GitHub
     * @return array
     */
    public static function check(bool $force = false): array
    {
        $currentVersion = defined('APP_VERSION') ? APP_VERSION : '1.0.0';

        // 1. Cek cache jika tidak dipaksa (force)
        if (!$force) {
            try {
                $cached = cache(self::CACHE_KEY);
                if (is_array($cached) && isset($cached['checked_at'])) {
                    // Pastikan current_version pada cache masih cocok
                    $cached['current_version'] = $currentVersion;
                    if (isset($cached['remote_version'])) {
                        $cached['has_update'] = version_compare($cached['remote_version'], $currentVersion, '>');
                    }
                    return $cached;
                }
            } catch (\Throwable $e) {
                // Abaikan error cache, lanjut fetch
            }
        }

        // 2. Fetch manifest dari remote GitHub / multi-mirror CDN
        $manifestUrls = self::getManifestUrls();
        $body = false;
        $httpCode = 0;
        $curlError = '';

        foreach ($manifestUrls as $manifestUrl) {
            $ch = curl_init($manifestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // Paksa IPv4 untuk menghindari cURL error 7: Network is unreachable akibat routing IPv6
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'ESPATA-Siakad-Updater/1.0');

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && !empty($body)) {
                break;
            }
        }

        if ($httpCode === 200 && $body) {
            $manifest = json_decode($body, true);
            if (is_array($manifest) && !empty($manifest['version'])) {
                $remoteVersion = $manifest['version'];
                $hasUpdate = version_compare($remoteVersion, $currentVersion, '>');

                $result = [
                    'has_update'      => $hasUpdate,
                    'remote_version'  => $remoteVersion,
                    'current_version' => $currentVersion,
                    'release_date'    => $manifest['last_update'] ?? ($manifest['generated'] ?? date('Y-m-d H:i')),
                    'total_files'     => $manifest['total_files'] ?? 0,
                    'checked_at'      => date('Y-m-d H:i:s'),
                    'status'          => 'success',
                ];

                try {
                    if (function_exists('cache') && ($c = cache()) !== null) {
                        $c->save(self::CACHE_KEY, $result, self::CACHE_TTL_SUCCESS);
                    }
                } catch (\Throwable $e) {
                    log_message('warning', 'UpdateChecker cache save error: ' . $e->getMessage());
                }

                // Kirim notifikasi lonceng ke seluruh akun Admin jika ada update baru
                if ($hasUpdate) {
                    self::notifyAdmins($result);
                }

                return $result;
            }
        }

        // Gagal fetch (offline atau GitHub error)
        $fallback = [
            'has_update'      => false,
            'remote_version'  => $currentVersion,
            'current_version' => $currentVersion,
            'release_date'    => null,
            'total_files'     => 0,
            'checked_at'      => date('Y-m-d H:i:s'),
            'status'          => 'offline',
            'error'           => $curlError ?: ('HTTP Code ' . $httpCode),
        ];

        try {
            if (function_exists('cache') && ($c = cache()) !== null) {
                $c->save(self::CACHE_KEY, $fallback, self::CACHE_TTL_FAIL);
            }
        } catch (\Throwable $e) {}

        return $fallback;
    }

    /**
     * Kirim notifikasi lonceng in-app ke seluruh Admin (role_id = 1).
     * Mencegah spam duplikat jika notifikasi untuk versi ini sudah pernah dikirim.
     */
    public static function notifyAdmins(array $updateInfo): void
    {
        try {
            $version = $updateInfo['remote_version'] ?? '';
            if (empty($version)) {
                return;
            }

            $userModel = new UserModel();
            $adminIds = $userModel->where('role_id', 1)->findColumn('id') ?? [];
            if (empty($adminIds)) {
                return;
            }

            $notifModel = new NotificationModel();

            // Cek apakah notifikasi untuk versi ini sudah ada
            $existing = $notifModel->where('related_type', 'system_update')
                ->like('title', "v{$version}")
                ->first();

            if (!$existing) {
                $title = "Pembaruan Aplikasi Tersedia (v{$version})";
                $message = "Tersedia pembaruan aplikasi ke versi {$version}. Klik untuk melihat detail perubahan dan menerapkan update.";
                $url = base_url('admin/updater');

                $notifModel->sendBulk(
                    $adminIds,
                    'info',
                    $title,
                    $message,
                    $url,
                    'system_update',
                    null
                );

                log_message('info', "[UpdateChecker] Sent update notification for v{$version} to " . count($adminIds) . " admins.");
            }
        } catch (\Throwable $e) {
            log_message('warning', '[UpdateChecker] notifyAdmins failed: ' . $e->getMessage());
        }
    }

    /**
     * Hapus cache pengecekan update (misal setelah selesai update atau trigger manual)
     */
    public static function clearCache(): void
    {
        try {
            if (function_exists('cache') && ($c = cache()) !== null) {
                $c->delete(self::CACHE_KEY);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Tandai notifikasi pembaruan sistem sebagai sudah dibaca (misal setelah update sukses diterapkan)
     */
    public static function markUpdateNotifsRead(?string $version = null): void
    {
        try {
            $notifModel = new NotificationModel();
            $builder = $notifModel->where('related_type', 'system_update');
            if ($version) {
                $builder->like('title', "v{$version}");
            }
            $builder->set([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ])->update();
        } catch (\Throwable $e) {
            log_message('warning', '[UpdateChecker] markUpdateNotifsRead failed: ' . $e->getMessage());
        }
    }
}
