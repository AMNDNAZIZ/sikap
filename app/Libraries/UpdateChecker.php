<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\UserModel;

class UpdateChecker
{
    private const CACHE_KEY = 'system_update_check_result';
    private const CACHE_TTL_SUCCESS = 900;  // 15 menit (cukup ringan & tetap responsif)
    private const CACHE_TTL_FAIL = 300;     // 5 menit jika offline/gagal

    private const GITHUB_USER = 'Sakajaya';
    private const GITHUB_REPO = 'espata-update';
    private const GITHUB_BRANCH = 'main';
    private const MANIFEST_FILE = 'update_manifest.json';

    /**
     * Resolve commit SHA terbaru dari GitHub API agar CDN tidak melayani manifest lama (max-age=300).
     */
    public static function resolveLatestCommitSha(): ?string
    {
        try {
            $url = sprintf(
                'https://api.github.com/repos/%s/%s/commits/%s',
                self::GITHUB_USER,
                self::GITHUB_REPO,
                self::GITHUB_BRANCH
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/vnd.github.sha',
                'User-Agent: ESPATA-Siakad-Updater/1.0',
                'Cache-Control: no-cache',
            ]);

            $body = trim((string) curl_exec($ch));
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && preg_match('/^[a-f0-9]{40}$/i', $body)) {
                return $body;
            }
        } catch (\Throwable $e) {
            log_message('warning', '[UpdateChecker::resolveLatestCommitSha] ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Dapatkan daftar URL mirror manifest di GitHub & CDN (prioritas commit SHA immutable)
     */
    public static function getManifestUrls(?string $ref = null): array
    {
        $targetRef = $ref ?: self::GITHUB_BRANCH;
        $cacheBuster = '?t=' . time();
        return [
            sprintf('https://raw.githubusercontent.com/%s/%s/%s/%s%s', self::GITHUB_USER, self::GITHUB_REPO, $targetRef, self::MANIFEST_FILE, $cacheBuster),
            sprintf('https://cdn.jsdelivr.net/gh/%s/%s@%s/%s%s', self::GITHUB_USER, self::GITHUB_REPO, $targetRef, self::MANIFEST_FILE, $cacheBuster),
            sprintf('https://fastly.jsdelivr.net/gh/%s/%s@%s/%s%s', self::GITHUB_USER, self::GITHUB_REPO, $targetRef, self::MANIFEST_FILE, $cacheBuster),
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
                        $cached['has_update'] = version_compare($cached['remote_version'], $currentVersion, '>')
                            || ((int) ($cached['pending_files'] ?? 0) > 0);
                    }
                    return $cached;
                }
            } catch (\Throwable $e) {
                // Abaikan error cache, lanjut fetch
            }
        }

        // 2. Resolve commit SHA terbaru agar bypass CDN cache 5 menit di raw.githubusercontent.com
        $latestRef = self::resolveLatestCommitSha();
        $manifestUrls = self::getManifestUrls($latestRef);
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
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'User-Agent: ESPATA-Siakad-Updater/1.0',
            ]);

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
                $pendingFiles = self::countPendingFileChanges($manifest);
                $hasUpdate = version_compare($remoteVersion, $currentVersion, '>') || ($pendingFiles > 0);

                $result = [
                    'has_update'       => $hasUpdate,
                    'remote_version'   => $remoteVersion,
                    'current_version'  => $currentVersion,
                    'pending_files'    => $pendingFiles,
                    'remote_generated' => $manifest['generated'] ?? null,
                    'remote_ref'       => $latestRef,
                    'release_date'     => $manifest['last_update'] ?? ($manifest['generated'] ?? date('Y-m-d H:i')),
                    'total_files'      => $manifest['total_files'] ?? 0,
                    'checked_at'       => date('Y-m-d H:i:s'),
                    'status'           => 'success',
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
     * Hitung jumlah file di manifest remote yang berbeda atau belum ada di disk lokal.
     * Menjamin update tetap terdeteksi meskipun APP_VERSION belum berubah (rilis patch di versi sama).
     */
    public static function countPendingFileChanges(array $manifest): int
    {
        if (empty($manifest['files']) || !is_array($manifest['files']) || !defined('ROOTPATH')) {
            return 0;
        }

        static $textExts = ['php', 'js', 'css', 'html', 'htm', 'json', 'xml', 'md', 'txt', 'yml', 'yaml', 'tpl', 'sql', 'htaccess', 'env'];
        $changes = 0;

        foreach ($manifest['files'] as $relativePath => $remoteHash) {
            $localPath = ROOTPATH . $relativePath;
            if (!is_file($localPath)) {
                $changes++;
                continue;
            }
            $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            if (in_array($ext, $textExts, true) || $ext === '') {
                $content = @file_get_contents($localPath);
                $localHash = md5(str_replace("\r\n", "\n", (string) $content));
            } else {
                $localHash = md5_file($localPath);
            }
            if ($localHash !== $remoteHash) {
                $changes++;
            }
        }

        return $changes;
    }

    /**
     * Kirim notifikasi lonceng in-app ke seluruh Admin (role_id = 1).
     * Mencegah spam duplikat jika notifikasi untuk rilis ini sudah pernah dikirim.
     */
    public static function notifyAdmins(array $updateInfo): void
    {
        try {
            $version = $updateInfo['remote_version'] ?? '';
            if (empty($version)) {
                return;
            }
            $pendingFiles = (int) ($updateInfo['pending_files'] ?? 0);
            $generatedTag = substr((string) ($updateInfo['remote_generated'] ?? ''), 0, 16);
            $releaseMarker = $generatedTag ? "v{$version} [{$generatedTag}]" : "v{$version}";

            $userModel = new UserModel();
            $adminIds = $userModel->where('role_id', 1)->findColumn('id') ?? [];
            if (empty($adminIds)) {
                return;
            }

            $notifModel = new NotificationModel();

            // Cek apakah notifikasi untuk rilis ini sudah ada
            $existing = $notifModel->where('related_type', 'system_update')
                ->like('title', $releaseMarker)
                ->first();

            if (!$existing) {
                $title = "Pembaruan Aplikasi Tersedia ({$releaseMarker})";
                $fileInfo = $pendingFiles > 0 ? " ({$pendingFiles} berkas diperbarui)" : '';
                $message = "Tersedia pembaruan aplikasi ke versi {$version}{$fileInfo}. Klik untuk melihat detail perubahan dan menerapkan update.";
                $url = base_url('admin/updater?refresh=1');

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
