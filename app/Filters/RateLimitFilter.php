<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limiting Filter for Authentication (SEC-03)
 *
 * Melindungi endpoint login ESPATA dari brute force & credential stuffing.
 * Memisahkan tanggung jawab secara tegas:
 * - Filter 'before()': Cek Tier 2 (IP Volumetric) & Cek Tier 1 Pre-Auth (Alias Map & Raw Input).
 * - Controller (Auth::attemptLogin): Cek Tier 1 (ID Akun internal), verifikasi kredensial,
 *   dan pencatatan kegagalan/pemulihan counter secara deterministik dan atomik.
 *
 * Mengimplementasikan mekanisme sinkronisasi flock lintas proses pada I/O file cache
 * untuk mencegah race condition (Lost Updates / TOCTOU) pada konkurensi tinggi.
 * Sesuai EAO-HO-2026-074, EAO-HO-2026-075, EAO-HO-2026-076, dan EAO-HO-2026-077.
 */
class RateLimitFilter implements FilterInterface
{
    // Ambang Batas Resmi Produksi
    public const TIER1_MAX = 5;          // 5 kegagalan per akun
    public const TIER1_TTL = 300;        // 300 detik (5 menit)
    public const TIER2_MAX = 100;        // 100 kegagalan per IP publik (NAT-friendly)
    public const TIER2_TTL = 300;        // 300 detik (5 menit)
    public const TIER3_THRESHOLD = 15;   // >15 kegagalan lintas IP (monitoring deteksi)
    public const TIER3_TTL = 600;        // 600 detik (10 menit)

    public const BLOCKED_MESSAGE = 'Terlalu banyak percobaan login gagal. Demi keamanan, silakan tunggu 5 menit sebelum mencoba kembali.';

    /**
     * Check rate limits before reaching controller
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $cache = \Config\Services::cache();

        // 1. Health check cache engine: Fail-Open dengan logging kritis jika cache lumpuh
        if (self::isCacheDegraded($cache)) {
            log_message('critical', 'RateLimitFilter: Cache subsystem unavailable or DummyHandler active. Rate limiting is failing open.');
            return $request;
        }

        $ip = $request->getIPAddress();
        $rawLogin = (string)$request->getPost('username');
        $normLogin = strtolower(trim($rawLogin));

        // 2. Cek Tier 2 (IP Volumetric Flood Protection)
        if (self::isIpBlocked($ip, $cache)) {
            log_message('warning', "RateLimitFilter: Tier 2 volumetric limit reached for IP: {$ip}");
            return self::createBlockedResponse($request);
        }

        // 3. Cek Tier 1 Pre-Auth (Optimasi Alias & Raw Input)
        if ($normLogin !== '') {
            // a. Jika ada pemetaan alias di cache ke ID akun internal
            $mappedUserId = self::getMappedUserId($ip, $normLogin, $cache);
            if ($mappedUserId !== null && self::isAccountBlocked($ip, $mappedUserId, $cache)) {
                log_message('warning', "RateLimitFilter: Tier 1 limit reached via alias for user_id {$mappedUserId} from IP: {$ip}");
                return self::createBlockedResponse($request);
            }

            // b. Cek counter raw input (untuk input acak/belum dipetakan)
            if (self::isRawInputBlocked($ip, $normLogin, $cache)) {
                log_message('warning', "RateLimitFilter: Tier 1 limit reached for raw input '{$normLogin}' from IP: {$ip}");
                return self::createBlockedResponse($request);
            }
        }

        return $request;
    }

    /**
     * After request: No-op
     *
     * Pemulihan counter ditangani secara eksplisit oleh controller pada cabang
     * keberhasilan autentikasi aktual (password valid & is_active == 1).
     * DILARANG menghapus counter pada HTTP 200/302 semata.
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    // =========================================================================
    // MEKANISME SINKRONISASI FLOCK LINTAS PROSES (ATOMIC COUNTER SYNCHRONIZATION)
    // =========================================================================

    /**
     * Dapatkan path direktori lock khusus
     */
    public static function getLockDir(): string
    {
        $base = defined('WRITEPATH') ? WRITEPATH : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $dir = rtrim($base, '\\/') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'locks' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Jalankan callback dalam proteksi flock eksklusif lintas proses
     */
    public static function withLock(string $key, callable $callback)
    {
        $lockDir = self::getLockDir();
        $lockFile = $lockDir . 'lock_' . md5($key) . '.lock';

        $fp = @fopen($lockFile, 'c+');
        if ($fp === false) {
            log_message('critical', "RateLimitFilter: Failed to open lock file for key: {$key}");
            return $callback();
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                log_message('critical', "RateLimitFilter: Failed to acquire exclusive lock for key: {$key}");
                return $callback();
            }

            return $callback();
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Increment counter secara atomik dengan mempertahankan jendela TTL asli
     * Mendukung pembatasan nilai maksimum (capping) opsional.
     */
    public static function atomicIncrement(string $key, int $defaultTtl, $cache = null, int $cap = 0): int
    {
        $cache = $cache ?? \Config\Services::cache();

        return self::withLock($key, function () use ($key, $defaultTtl, $cache, $cap) {
            $currentVal = (int)($cache->get($key) ?? 0);

            // Capping: Jika counter sudah mencapai atau melebihi cap, jangan dinaikkan lagi
            if ($cap > 0 && $currentVal >= $cap) {
                return $currentVal;
            }

            // Ambil metadata jika didukung handler
            $meta = method_exists($cache, 'getMetaData') ? $cache->getMetaData($key) : null;

            // Pertahankan sisa waktu TTL asli, jangan direset ulang setiap ada increment baru
            $remainingTtl = $defaultTtl;
            if (is_array($meta) && isset($meta['expire']) && is_int($meta['expire'])) {
                $now = time();
                if ($meta['expire'] > $now) {
                    $remainingTtl = max(1, $meta['expire'] - $now);
                }
            }

            $newVal = $currentVal + 1;
            $saved = $cache->save($key, $newVal, $remainingTtl);

            if (!$saved) {
                log_message('critical', "RateLimitFilter: atomicIncrement failed to write cache for key: {$key}");
            }

            return $newVal;
        });
    }

    /**
     * Baca counter secara atomik
     */
    public static function atomicGet(string $key, $cache = null): int
    {
        $cache = $cache ?? \Config\Services::cache();

        return self::withLock($key, function () use ($key, $cache) {
            return (int)($cache->get($key) ?? 0);
        });
    }

    /**
     * Hapus counter secara atomik
     */
    public static function atomicDelete(string $key, $cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();

        return self::withLock($key, function () use ($key, $cache) {
            return (bool)$cache->delete($key);
        });
    }

    // =========================================================================
    // HELPER METHODS: DETEKSI & STATUS COUNTER
    // =========================================================================

    public static function isCacheDegraded($cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();
        if ($cache instanceof \CodeIgniter\Cache\Handlers\DummyHandler) {
            return true;
        }
        return false;
    }

    public static function isIpBlocked(string $ip, $cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();
        $attempts = self::atomicGet(self::getIpKey($ip), $cache);
        return $attempts >= self::TIER2_MAX;
    }

    public static function isAccountBlocked(string $ip, $userId, $cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();
        $attempts = self::atomicGet(self::getAccountKey($ip, (int)$userId), $cache);
        return $attempts >= self::TIER1_MAX;
    }

    public static function isRawInputBlocked(string $ip, string $normLogin, $cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();
        $attempts = self::atomicGet(self::getRawKey($ip, $normLogin), $cache);
        return $attempts >= self::TIER1_MAX;
    }

    public static function getMappedUserId(string $ip, string $normLogin, $cache = null): ?int
    {
        $cache = $cache ?? \Config\Services::cache();
        $val = $cache->get(self::getMapKey($ip, $normLogin));
        return $val !== null ? (int)$val : null;
    }

    public static function setMappedUserId(string $ip, string $normLogin, int $userId, $cache = null): void
    {
        $cache = $cache ?? \Config\Services::cache();
        try {
            $cache->save(self::getMapKey($ip, $normLogin), $userId, self::TIER1_TTL);
        } catch (\Throwable $e) {
            log_message('critical', 'RateLimitFilter: Failed to save alias map: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // HELPER METHODS: PENCATATAN KEGAGALAN & PEMULIHAN SUKSES
    // =========================================================================

    /**
     * Periksa status blokir dan catat kegagalan secara terpadu di bawah proteksi flock lintas proses.
     * Mencegah race condition TOCTOU (Time-of-Check to Time-of-Use) dan membatasi counter agar tidak
     * pernah melebihi ambang batas (strict counter capping pada TIER1_MAX).
     *
     * @return bool True jika kegagalan berhasil dicatat dalam batas kuota, False jika sudah mencapai ambang/terblokir
     */
    public static function checkAndRecordFailure(string $ip, ?int $userId, string $rawLogin, $cache = null): bool
    {
        $cache = $cache ?? \Config\Services::cache();
        $normLogin = strtolower(trim($rawLogin));

        try {
            // 1. Akun Teridentifikasi: Sinkronisasi Terpadu pada Kunci ID Akun
            if ($userId !== null && $userId > 0) {
                $accKey = self::getAccountKey($ip, $userId);

                return self::withLock($accKey, function () use ($ip, $userId, $normLogin, $accKey, $cache) {
                    $currentAcc = (int)($cache->get($accKey) ?? 0);

                    // Jika counter sudah mencapai atau melebihi batas, tolak langsung dan jangan naikkan (Strict Capping)
                    if ($currentAcc >= self::TIER1_MAX) {
                        return false;
                    }

                    // Pertahankan sisa jendela TTL asli
                    $meta = method_exists($cache, 'getMetaData') ? $cache->getMetaData($accKey) : null;
                    $remainingTtl = self::TIER1_TTL;
                    if (is_array($meta) && isset($meta['expire']) && is_int($meta['expire'])) {
                        $now = time();
                        if ($meta['expire'] > $now) {
                            $remainingTtl = max(1, $meta['expire'] - $now);
                        }
                    }

                    $newAcc = $currentAcc + 1;
                    $cache->save($accKey, $newAcc, $remainingTtl);

                    // Simpan atau perbarui alias mapping di cache
                    if ($normLogin !== '') {
                        $cache->save(self::getMapKey($ip, $normLogin), $userId, self::TIER1_TTL);
                    }

                    // Catat ke Tier 2 (IP Volumetric Flood Protection)
                    self::atomicIncrement(self::getIpKey($ip), self::TIER2_TTL, $cache);

                    // Evaluasi Tier 3: Passive Account Attack Monitoring
                    $monKey = self::getMonKey($userId);
                    $monAttempts = self::atomicIncrement($monKey, self::TIER3_TTL, $cache);
                    if ($monAttempts > self::TIER3_THRESHOLD) {
                        log_message('critical', "Security Alert: Distributed brute force detected targeting user ID: {$userId} ({$monAttempts} failures)");
                        if (function_exists('audit_log')) {
                            audit_log('security_alert', 'users', (string)$userId, null, "Distributed brute force attack pattern detected ({$monAttempts} failures)");
                        }
                    }

                    return true;
                });
            }

            // 2. Akun Tidak Ditemukan: Sinkronisasi Terpadu pada Kunci Raw Input
            if ($normLogin !== '') {
                $rawKey = self::getRawKey($ip, $normLogin);

                return self::withLock($rawKey, function () use ($ip, $rawKey, $cache) {
                    $currentRaw = (int)($cache->get($rawKey) ?? 0);

                    if ($currentRaw >= self::TIER1_MAX) {
                        return false;
                    }

                    $meta = method_exists($cache, 'getMetaData') ? $cache->getMetaData($rawKey) : null;
                    $remainingTtl = self::TIER1_TTL;
                    if (is_array($meta) && isset($meta['expire']) && is_int($meta['expire'])) {
                        $now = time();
                        if ($meta['expire'] > $now) {
                            $remainingTtl = max(1, $meta['expire'] - $now);
                        }
                    }

                    $newRaw = $currentRaw + 1;
                    $cache->save($rawKey, $newRaw, $remainingTtl);

                    // Catat ke Tier 2 (IP Volumetric Flood Protection)
                    self::atomicIncrement(self::getIpKey($ip), self::TIER2_TTL, $cache);

                    return true;
                });
            }

            // 3. Fallback: Input kosong, hanya catat IP
            self::atomicIncrement(self::getIpKey($ip), self::TIER2_TTL, $cache);
            return true;
        } catch (\Throwable $e) {
            log_message('critical', 'RateLimitFilter: Exception in checkAndRecordFailure: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Catat kegagalan autentikasi (Dipanggil eksklusif oleh Auth::attemptLogin)
     * Menggunakan checkAndRecordFailure terpadu dan terproteksi flock lintas proses.
     */
    public static function recordFailure(string $ip, ?int $userId, string $rawLogin, $cache = null): bool
    {
        return self::checkAndRecordFailure($ip, $userId, $rawLogin, $cache);
    }

    /**
     * Pulihkan counter pada keberhasilan autentikasi aktual (Dipanggil eksklusif oleh Auth::attemptLogin)
     * Hanya menghapus counter Tier 1 ID Akun & raw input. DILARANG menghapus Tier 2 IP.
     */
    public static function recordSuccess(string $ip, int $userId, string $rawLogin, $cache = null): void
    {
        $cache = $cache ?? \Config\Services::cache();
        $normLogin = strtolower(trim($rawLogin));

        try {
            // 1. Hapus counter ID Akun secara atomik
            $del1 = self::atomicDelete(self::getAccountKey($ip, $userId), $cache);

            // 2. Hapus counter raw input & pemetaan alias
            if ($normLogin !== '') {
                self::atomicDelete(self::getRawKey($ip, $normLogin), $cache);
                $cache->delete(self::getMapKey($ip, $normLogin));
            }

            if ($del1 === false) {
                log_message('warning', "RateLimitFilter: Failed to delete Tier 1 counter for user ID {$userId} from IP {$ip}");
            }
        } catch (\Throwable $e) {
            log_message('warning', 'RateLimitFilter: Exception in recordSuccess: ' . $e->getMessage());
        }
    }

    /**
     * Buat respons standar saat diblokir (Web redirect atau AJAX 429)
     */
    public static function createBlockedResponse(RequestInterface $request)
    {
        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'error' => 'Too many requests',
                    'message' => self::BLOCKED_MESSAGE,
                    'retry_after' => self::TIER1_TTL,
                ]);
        }

        return redirect()->to('/login')->with('error', self::BLOCKED_MESSAGE);
    }

    // =========================================================================
    // KUNCI CACHE DETERMINISTIK
    // =========================================================================

    public static function getAccountKey(string $ip, int $userId): string
    {
        return 'rl_acc_' . md5($ip . '_' . $userId);
    }

    public static function getRawKey(string $ip, string $normLogin): string
    {
        return 'rl_raw_' . md5($ip . '_' . $normLogin);
    }

    public static function getMapKey(string $ip, string $normLogin): string
    {
        return 'rl_map_' . md5($ip . '_' . $normLogin);
    }

    public static function getIpKey(string $ip): string
    {
        return 'rl_ip_' . md5($ip);
    }

    public static function getMonKey(int $userId): string
    {
        return 'rl_mon_' . md5((string)$userId);
    }
}
