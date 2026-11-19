<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use DateTimeInterface;

class Cookie extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Cookie Prefix
     * --------------------------------------------------------------------------
     *
     * Set a cookie name prefix if you need to avoid collisions.
     */
    public string $prefix = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Expires Timestamp
     * --------------------------------------------------------------------------
     *
     * Default expires timestamp for cookies. Setting this to `0` will mean the
     * cookie will not have the `Expires` attribute and will behave as a session
     * cookie.
     *
     * @var DateTimeInterface|int|string
     */
    public $expires = 0;

    /**
     * --------------------------------------------------------------------------
     * Cookie Path
     * --------------------------------------------------------------------------
     *
     * Typically will be a forward slash.
     */
    public string $path = '/';

    /**
     * --------------------------------------------------------------------------
     * Cookie Domain
     * --------------------------------------------------------------------------
     *
     * Set to `.your-domain.com` for site-wide cookies.
     */
    public string $domain = '';

    /**
     * --------------------------------------------------------------------------
     * Cookie Secure
     * --------------------------------------------------------------------------
     *
     * Cookie will only be set if a secure HTTPS connection exists.
     */
    public bool $secure = false;

    /**
     * --------------------------------------------------------------------------
     * Cookie HTTPOnly
     * --------------------------------------------------------------------------
     *
     * Cookie will only be accessible via HTTP(S) (no JavaScript).
     */
    public bool $httponly = true;

    /**
     * --------------------------------------------------------------------------
     * Cookie SameSite
     * --------------------------------------------------------------------------
     *
     * Configure cookie SameSite setting. Allowed values are:
     * - None
     * - Lax
     * - Strict
     * - ''
     *
     * Alternatively, you can use the constant names:
     * - `Cookie::SAMESITE_NONE`
     * - `Cookie::SAMESITE_LAX`
     * - `Cookie::SAMESITE_STRICT`
     *
     * Defaults to `Lax` for compatibility with modern browsers. Setting `''`
     * (empty string) means default SameSite attribute set by browsers (`Lax`)
     * will be set on cookies. If set to `None`, `$secure` must also be set.
     *
     * @var ''|'Lax'|'None'|'Strict'
     */
    public string $samesite = 'Lax';

    /**
     * --------------------------------------------------------------------------
     * Cookie Raw
     * --------------------------------------------------------------------------
     *
     * This flag allows setting a "raw" cookie, i.e., its name and value are
     * not URL encoded using `rawurlencode()`.
     *
     * If this is set to `true`, cookie names should be compliant of RFC 2616's
     * list of allowed characters.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes
     * @see https://tools.ietf.org/html/rfc2616#section-2.2
     */
    public bool $raw = false;

    /**
     * Dynamic Constructor untuk mengamankan cookie secara dinamis (SEC-04)
     * Sesuai Mandat EAO-HO-2026-080:
     * - HTTP Lokal: secure = false
     * - HTTPS Langsung: secure = true
     * - HTTPS melalui Reverse Proxy: secure = true HANYA jika proxy terdaftar dalam Config\App::$proxyIPs
     * - Header X-Forwarded-Proto dari sumber tidak tepercaya diabaikan (anti-spoofing).
     */
    public function __construct()
    {
        parent::__construct();

        // 1. Override eksplisit via .env (cookie.secure) jika disetel
        $envSecure = env('cookie.secure');
        if ($envSecure !== null) {
            $this->secure = filter_var($envSecure, FILTER_VALIDATE_BOOLEAN);
            return;
        }

        // 2. Abaikan jika dijalankan via CLI murni (spark, migrations) tanpa konteks HTTP
        if (PHP_SAPI === 'cli' && !isset($_SERVER['HTTP_HOST']) && !isset($_SERVER['REQUEST_METHOD'])) {
            return;
        }

        // 3. Deteksi dinamis koneksi aman
        $this->secure = self::isConnectionSecure();
    }

    /**
     * Deteksi apakah koneksi saat ini aman (HTTPS)
     *
     * 1. Deteksi HTTPS Langsung:
     *    - $_SERVER['HTTPS'] === 'on' (atau bukan 'off')
     *    - $_SERVER['SERVER_PORT'] === 443
     * 2. Deteksi Reverse Proxy:
     *    - Hanya dipercaya jika REMOTE_ADDR cocok dengan Config\App::$proxyIPs.
     *    - Mencegah header spoofing (X-Forwarded-Proto) dari klien tidak tepercaya.
     */
    public static function isConnectionSecure(): bool
    {
        // A. Deteksi HTTPS Langsung (Web Server TLS Termination)
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443) {
            return true;
        }

        // B. Deteksi Reverse Proxy (Hanya jika header ada DAN berasal dari trusted proxy)
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if ($forwardedProto !== null && strtolower((string)$forwardedProto) === 'https') {
            if (self::isRemoteAddrTrustedProxy()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Memeriksa apakah REMOTE_ADDR saat ini berada dalam daftar proxy tepercaya (Config\App::$proxyIPs)
     */
    public static function isRemoteAddrTrustedProxy(): bool
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($remoteAddr === '') {
            return false;
        }

        $appConfig = config(\Config\App::class);
        $proxyIPs = $appConfig->proxyIPs ?? [];

        if (empty($proxyIPs) || !is_array($proxyIPs)) {
            return false;
        }

        foreach ($proxyIPs as $proxyIP => $header) {
            if (!str_contains($proxyIP, '/')) {
                if ($proxyIP === $remoteAddr) {
                    return true;
                }
                continue;
            }

            if (self::ipInCidr($remoteAddr, $proxyIP)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi kecocokan IP dalam rentang subnet CIDR
     */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);
        $mask = (int)$mask;

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($mask < 0 || $mask > 32) {
                return false;
            }
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $netmask = -1 << (32 - $mask);
            return ($ipLong & $netmask) === ($subnetLong & $netmask);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false || $mask < 0 || $mask > 128) {
                return false;
            }
            $bytes = intdiv($mask, 8);
            $bits = $mask % 8;
            if (strncmp($ipBin, $subnetBin, $bytes) !== 0) {
                return false;
            }
            if ($bits > 0) {
                $byte1 = ord($ipBin[$bytes]);
                $byte2 = ord($subnetBin[$bytes]);
                $netmask = 0xFF << (8 - $bits);
                return ($byte1 & $netmask) === ($byte2 & $netmask);
            }
            return true;
        }

        return false;
    }
}
