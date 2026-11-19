<?php
/**
 * Test Suite: EAO-HO-2026-080 — SEC-04 Dynamic Secure Cookie Implementation Test
 *
 * Menguji kepatuhan penuh terhadap seluruh spesifikasi Mandat EAO-HO-2026-080:
 * - Skenario A: HTTP Lokal (Secure = false, cookie berfungsi)
 * - Skenario B: HTTPS Langsung (Secure = true, HttpOnly = true, SameSite = Lax)
 * - Skenario C: Header Proxy Tidak Tepercaya (X-Forwarded-Proto dari untrusted IP -> Secure = false)
 * - Skenario D: Proxy Tepercaya (Ketika REMOTE_ADDR cocok dengan proxyIPs -> Secure = true)
 * - Skenario E: Verifikasi Atribut Cookie Lengkap (HttpOnly, SameSite, Domain, Path, Expires)
 * - Skenario F: Integrasi Session CI4 (system/Session/Session::configure)
 * - Skenario G: Regresi Keamanan & Lisensi (SecurityGuard SEC_PASS: 1)
 */

declare(strict_types=1);

define('FCPATH', realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'testing');

require_once __DIR__ . '/../app/Config/Paths.php';
$paths = new \Config\Paths();
require_once $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootConsole($paths);

use Config\Cookie as CookieConfig;
use Config\App as AppConfig;
use Config\Session as SessionConfig;

$passCount = 0;
$failCount = 0;

function assertSec04(string $desc, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$desc} - {$detail}\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE EAO-HO-2026-080: SEC-04 DYNAMIC SECURE COOKIE\n";
echo "====================================================================\n\n";

// Simpan state $_SERVER asli
$originalServer = $_SERVER;

// -------------------------------------------------------------
// 1. SKENARIO A: HTTP LOKAL (LOCALHOST / XAMPP)
// -------------------------------------------------------------
echo "1. UJI SKENARIO A: HTTP LOKAL (Localhost / XAMPP)...\n";

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);

$cookieLocal = new CookieConfig();
assertSec04("HTTP Lokal: Secure bernilai false", $cookieLocal->secure === false, "Aktual: " . var_export($cookieLocal->secure, true));
assertSec04("HTTP Lokal: HttpOnly tetap true", $cookieLocal->httponly === true);
assertSec04("HTTP Lokal: SameSite tetap 'Lax'", $cookieLocal->samesite === 'Lax');
assertSec04("HTTP Lokal: Domain kosong (host-only)", $cookieLocal->domain === '');
assertSec04("HTTP Lokal: Path bernilai '/'", $cookieLocal->path === '/');

// -------------------------------------------------------------
// 2. SKENARIO B: HTTPS LANGSUNG (PRODUCTION SSL / PORT 443)
// -------------------------------------------------------------
echo "\n2. UJI SKENARIO B: HTTPS LANGSUNG (Direct SSL / Port 443)...\n";

// B.1 HTTPS via $_SERVER['HTTPS'] = 'on'
$_SERVER['HTTP_HOST'] = 'smpistora.sch.id';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['REMOTE_ADDR'] = '180.252.10.5';
unset($_SERVER['HTTP_X_FORWARDED_PROTO']);

$cookieHttpsOn = new CookieConfig();
assertSec04("HTTPS Langsung (\$_SERVER['HTTPS'] = 'on'): Secure bernilai true", $cookieHttpsOn->secure === true);
assertSec04("HTTPS Langsung: HttpOnly tetap true", $cookieHttpsOn->httponly === true);
assertSec04("HTTPS Langsung: SameSite tetap 'Lax'", $cookieHttpsOn->samesite === 'Lax');

// B.2 HTTPS via Port 443 (tanpa variabel HTTPS eksplisit)
unset($_SERVER['HTTPS']);
$_SERVER['SERVER_PORT'] = '443';
$cookiePort443 = new CookieConfig();
assertSec04("HTTPS Langsung (SERVER_PORT = 443): Secure bernilai true", $cookiePort443->secure === true);

// -------------------------------------------------------------
// 3. SKENARIO C: HEADER PROXY TIDAK TEPERCAYA (SPOOFING RESISTANCE)
// -------------------------------------------------------------
echo "\n3. UJI SKENARIO C: HEADER PROXY TIDAK TEPERCAYA (Anti-Spoofing)...\n";

// Klien jahat mengirimkan X-Forwarded-Proto: https melalui koneksi plain HTTP
$_SERVER['HTTP_HOST'] = 'smpistora.sch.id';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = '203.0.113.195'; // IP publik penyerang (bukan trusted proxy)
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https'; // Header spoofing
unset($_SERVER['HTTPS']);

// Pastikan proxyIPs kosong (kondisi default produksi)
$appConfig = config(AppConfig::class);
$appConfig->proxyIPs = [];

$cookieUntrusted = new CookieConfig();
assertSec04("Header X-Forwarded-Proto dari IP tidak tepercaya DIABAIKAN (Secure = false)", $cookieUntrusted->secure === false, "Aktual: " . var_export($cookieUntrusted->secure, true));

// -------------------------------------------------------------
// 4. SKENARIO D: REVERSE PROXY TEPERCAYA (TRUSTED PROXY)
// -------------------------------------------------------------
echo "\n4. UJI SKENARIO D: REVERSE PROXY TEPERCAYA (Trusted Proxy Whitelist)...\n";

// D.1 IP tunggal tepercaya
$trustedProxyIp = '10.0.1.50';
$appConfig->proxyIPs = [
    $trustedProxyIp => 'X-Forwarded-For'
];

$_SERVER['REMOTE_ADDR'] = $trustedProxyIp;
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = '80';
unset($_SERVER['HTTPS']);

$cookieTrusted = new CookieConfig();
assertSec04("HTTPS via Trusted Proxy Tunggal: Secure bernilai true", $cookieTrusted->secure === true);

// D.2 Subnet CIDR tepercaya (misal: Cloudflare IP Range / Private Subnet)
$appConfig->proxyIPs = [
    '172.16.0.0/16' => 'X-Forwarded-For'
];
$_SERVER['REMOTE_ADDR'] = '172.16.55.10'; // Masuk dalam CIDR 172.16.0.0/16
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$cookieCidrTrusted = new CookieConfig();
assertSec04("HTTPS via Trusted Subnet CIDR: Secure bernilai true", $cookieCidrTrusted->secure === true);

// D.3 IP di luar Subnet CIDR tepercaya
$_SERVER['REMOTE_ADDR'] = '192.168.1.100'; // Di luar CIDR 172.16.0.0/16
$cookieOutsideCidr = new CookieConfig();
assertSec04("IP di luar Subnet CIDR tepercaya: Header diabaikan (Secure = false)", $cookieOutsideCidr->secure === false);

// Kembalikan proxyIPs ke default
$appConfig->proxyIPs = [];

// -------------------------------------------------------------
// 5. SKENARIO E: VERIFIKASI OVERRIDE EKSPLISIT VIA .ENV
// -------------------------------------------------------------
echo "\n5. UJI SKENARIO E: OVERRIDE EKSPLISIT .ENV...\n";

// Uji bahwa jika cookie.secure di-set di .env, nilainya mengesampingkan auto-deteksi
putenv('cookie.secure=true');
$cookieEnvTrue = new CookieConfig();
assertSec04("Override .env: cookie.secure=true memaksa Secure = true bahkan di HTTP", $cookieEnvTrue->secure === true);

putenv('cookie.secure=false');
$_SERVER['HTTPS'] = 'on'; // Meskipun HTTPS
$cookieEnvFalse = new CookieConfig();
assertSec04("Override .env: cookie.secure=false memaksa Secure = false bahkan di HTTPS", $cookieEnvFalse->secure === false);

// Bersihkan override
putenv('cookie.secure');

// -------------------------------------------------------------
// 6. SKENARIO F: INTEGRASI PARAMETER SESSION RUNTIME CODEIGNITER 4
// -------------------------------------------------------------
echo "\n6. UJI SKENARIO F: INTEGRASI SESSION CI4 & COOKIE PARAMS...\n";

// Kembalikan ke HTTP lokal
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);

$sessionConfig = config(SessionConfig::class);
assertSec04("Session Cookie Name adalah 'ci_session'", $sessionConfig->cookieName === 'ci_session');
assertSec04("Session Expiration adalah 14400s (4 jam)", $sessionConfig->expiration === 14400);
assertSec04("Session Driver adalah DatabaseHandler", $sessionConfig->driver === \CodeIgniter\Session\Handlers\DatabaseHandler::class);
assertSec04("Session RegenerateDestroy adalah true", $sessionConfig->regenerateDestroy === true);

// -------------------------------------------------------------
// 7. SKENARIO G: INTEGRASI SECURITYGUARD & LISENSI
// -------------------------------------------------------------
echo "\n7. UJI SKENARIO G: INTEGRITAS LISENSI & BERKAS TERLINDUNGI...\n";

require_once APPPATH . 'Libraries/SecureConfig.php';
require_once APPPATH . 'Libraries/SecurityGuard.php';

$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertSec04("SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS: 1", $secPass === true || $secPass === 1);
assertSec04("Subapp bayaran/ tidak ada (absent)", !is_dir(ROOTPATH . 'bayaran'));
assertSec04("Subapp cbtku/ tidak ada (absent)", !is_dir(ROOTPATH . 'cbtku'));

// Kembalikan $_SERVER asli
$_SERVER = $originalServer;

// -------------------------------------------------------------
// REKAPITULASI
// -------------------------------------------------------------
echo "\n====================================================================\n";
echo "RINGKASAN HASIL: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

if ($failCount === 0) {
    echo "STATUS: SELURUH SKENARIO SEC-04 LULUS DENGAN SEMPURNA (100% PASS)\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT KEGAGALAN DALAM PENGUJIAN\n";
    exit(1);
}
