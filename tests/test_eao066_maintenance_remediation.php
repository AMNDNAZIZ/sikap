<?php
/**
 * Test Suite: EAO-HO-2026-066 Maintenance Endpoint Remediation (SEC-01)
 *
 * Menguji kepatuhan terhadap seluruh persyaratan Mandat EAO-HO-2026-066:
 * 1. debugToken dinonaktifkan permanen (404, zero disclosure).
 * 2. Unauthenticated GET / POST ditolak fail-closed.
 * 3. Non-admin roles (Role 2, 3, 5) ditolak 403 Forbidden.
 * 4. Header spoofing (X-Forwarded-For, Host) tidak memberikan bypass.
 * 5. Invarian keamanan: Hash yang valid (SEC_PASS) tidak dihapus sembarangan.
 * 6. File hash kritis & integritas lisensi SecurityGuard tetap SEC_PASS: 1.
 */

define('APPPATH', realpath(__DIR__ . '/../app') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(__DIR__ . '/../system') . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

require_once ROOTPATH . 'vendor/autoload.php';

if (!function_exists('env')) {
    function env($key, $default = null) {
        if ($key === 'LICENSE_HASH_SECRET') return 'EspataSecureSecretKey2026';
        return $default;
    }
}
if (!function_exists('helper')) {
    function helper($h) {}
}
if (!function_exists('log_message')) {
    function log_message($l, $m) {}
}

if (!function_exists('base_url')) {
    function base_url($u = '') { return 'http://localhost/siakad/' . ltrim($u, '/'); }
}
if (!function_exists('is_cli')) {
    function is_cli() { return false; } // Simulasi web context
}
if (!function_exists('log_message')) {
    function log_message($l, $m) {}
}

require_once APPPATH . 'Libraries/SecureConfig.php';
require_once APPPATH . 'Libraries/SecurityGuard.php';
require_once APPPATH . 'Libraries/LicenseGuard.php';
require_once APPPATH . 'Controllers/BaseController.php';
require_once APPPATH . 'Controllers/Maintenance.php';

use App\Libraries\SecurityGuard;
use App\Libraries\LicenseGuard;
use App\Controllers\Maintenance;

$passed = 0;
$failed = 0;

function assertCondition(string $name, bool $cond, string $details = '') {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$name}" . ($details ? " ({$details})" : "") . PHP_EOL;
    } else {
        $failed++;
        echo "  [FAIL] {$name}" . ($details ? " ({$details})" : "") . PHP_EOL;
    }
}

echo "====================================================================" . PHP_EOL;
echo "TEST SUITE EAO-HO-2026-066: MAINTENANCE ENDPOINT REMEDIATION (SEC-01)" . PHP_EOL;
echo "====================================================================" . PHP_EOL . PHP_EOL;

// Helper Mock Response
class MockResponse {
    public int $statusCode = 200;
    public string $body = '';
    public array $headers = [];

    public function setStatusCode(int $code) { $this->statusCode = $code; return $this; }
    public function setBody(string $body) { $this->body = $body; return $this; }
    public function setHeader(string $n, string $v) { $this->headers[$n] = $v; return $this; }
    public function getStatusCode(): int { return $this->statusCode; }
    public function getBody(): string { return $this->body; }
}

// Helper Mock Request
class MockRequest {
    public string $method = 'GET';
    public string $ip = '192.168.1.100';
    public array $headers = [];

    public function getMethod(): string { return $this->method; }
    public function getIPAddress(): string { return $this->ip; }
    public function getHeaderLine(string $h): string { return $this->headers[strtolower($h)] ?? ''; }
}

// --- 1. VERIFIKASI ROUTING DI app/Config/Routes.php ---
echo "--- 1. VERIFIKASI ROUTING (app/Config/Routes.php) ---" . PHP_EOL;
$routesContent = file_get_contents(APPPATH . 'Config/Routes.php');

assertCondition("Routing: maintenance/debug-token telah dihapus total",
    strpos($routesContent, "'maintenance/debug-token'") === false &&
    strpos($routesContent, '"maintenance/debug-token"') === false
);

assertCondition("Routing: maintenance/reset-hashes menggunakan metode POST",
    strpos($routesContent, "\$routes->post('maintenance/reset-hashes/(:any)'") !== false ||
    strpos($routesContent, '$routes->post("maintenance/reset-hashes/(:any)"') !== false
);

assertCondition("Routing: maintenance/reset-hashes dilindungi filter auth:1 (Admin)",
    strpos($routesContent, "['filter' => 'auth:1']") !== false &&
    strpos($routesContent, "reset-hashes") !== false
);

// --- 2. VERIFIKASI METODE debugToken() PADA CONTROLLER ---
echo PHP_EOL . "--- 2. VERIFIKASI debugToken() CONTROLLER ---" . PHP_EOL;
$ctrl = new Maintenance();
$reflection = new \ReflectionClass($ctrl);
$propResp = $reflection->getProperty('response');
$propResp->setAccessible(true);
$propReq = $reflection->getProperty('request');
$propReq->setAccessible(true);

$mockResp = new MockResponse();
$mockReq = new MockRequest();
$propResp->setValue($ctrl, $mockResp);
$propReq->setValue($ctrl, $mockReq);

$ctrl->debugToken();
assertCondition("debugToken: Mengembalikan status 404 Not Found", $mockResp->getStatusCode() === 404);
assertCondition("debugToken: Tidak membocorkan token SHA-256", strpos($mockResp->getBody(), 'token') === false);
assertCondition("debugToken: Tidak membocorkan salt rahasia", strpos($mockResp->getBody(), 'SakaSalika') === false);

// Simulasi Header Spoofing pada debugToken
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
$mockRespSpoof = new MockResponse();
$propResp->setValue($ctrl, $mockRespSpoof);
$ctrl->debugToken();
assertCondition("debugToken: Header spoofing tetap menghasilkan 404", $mockRespSpoof->getStatusCode() === 404);

// --- 3. VERIFIKASI resetHashes() CONTROLLER ---
echo PHP_EOL . "--- 3. VERIFIKASI resetHashes() CONTROLLER ---" . PHP_EOL;

// Mock Session
if (!function_exists('session')) {
    class MockSession {
        public array $data = [];
        public function get($k = null) {
            if ($k === null) return $this->data;
            return $this->data[$k] ?? null;
        }
        public function set($k, $v) { $this->data[$k] = $v; }
    }
    $globalSession = new MockSession();
    function session() {
        global $globalSession;
        return $globalSession;
    }
}

// 3.1 Tolak Metode GET
$mockReqGet = new MockRequest();
$mockReqGet->method = 'GET';
$mockRespGet = new MockResponse();
$propReq->setValue($ctrl, $mockReqGet);
$propResp->setValue($ctrl, $mockRespGet);
$ctrl->resetHashes('any-token');
assertCondition("resetHashes: Tolak HTTP GET dengan status 405 Method Not Allowed", $mockRespGet->getStatusCode() === 405);

// 3.2 Tolak Unauthenticated POST
$mockReqPost = new MockRequest();
$mockReqPost->method = 'POST';
$mockRespPostUnauth = new MockResponse();
$propReq->setValue($ctrl, $mockReqPost);
$propResp->setValue($ctrl, $mockRespPostUnauth);
$globalSession->data = ['user' => null];
$ctrl->resetHashes('any-token');
assertCondition("resetHashes: Tolak Unauthenticated POST dengan status 403 Forbidden", $mockRespPostUnauth->getStatusCode() === 403);

// 3.3 Tolak Unauthorized Roles (Role 2 Kepsek, Role 3 Guru, Role 5 Siswa)
$rolesToTest = [2 => 'Kepala Sekolah', 3 => 'Guru', 5 => 'Siswa'];
foreach ($rolesToTest as $rId => $rName) {
    $mockRespRole = new MockResponse();
    $propResp->setValue($ctrl, $mockRespRole);
    $globalSession->data = ['user' => ['id' => 99, 'role_id' => $rId, 'username' => 'test_' . $rId]];
    $ctrl->resetHashes('any-token');
    assertCondition("resetHashes: Tolak Role {$rId} ({$rName}) dengan status 403 Forbidden", $mockRespRole->getStatusCode() === 403);
}

// 3.4 Valid Admin tapi Token Salah
$mockRespBadToken = new MockResponse();
$propResp->setValue($ctrl, $mockRespBadToken);
$globalSession->data = ['user' => ['id' => 1, 'role_id' => 1, 'username' => 'admin']];
$ctrl->resetHashes('invalid-token-12345');
assertCondition("resetHashes: Admin dengan token salah ditolak 403 Forbidden", $mockRespBadToken->getStatusCode() === 403);

// 3.5 Invarian Keamanan: Valid Admin & Token Benar tetapi hash sehat (SEC_PASS)
$secret = LicenseGuard::getHashSecret();
$salt = 'SakaSalikaMaintenanceSalt2026';
$validToken = hash('sha256', $secret . $salt);

$mockRespValidAdmin = new MockResponse();
$propResp->setValue($ctrl, $mockRespValidAdmin);
$ctrl->resetHashes($validToken);
assertCondition("resetHashes: Invarian SEC_PASS mencegah penghapusan berkas hash valid",
    $mockRespValidAdmin->getStatusCode() === 200 &&
    strpos($mockRespValidAdmin->getBody(), 'SEC_PASS') !== false
);

// --- 4. INTEGRITAS HASH DAN LISENSI ---
echo PHP_EOL . "--- 4. INTEGRITAS HASH DAN LISENSI ---" . PHP_EOL;
$secHashFile = APPPATH . 'Config/.security_hash';
$licHashFile = APPPATH . 'Config/.lic_hash';

assertCondition("Berkas .security_hash tetap utuh di disk", file_exists($secHashFile));
assertCondition("Berkas .lic_hash tetap utuh di disk", file_exists($licHashFile));

$secPass = SecurityGuard::verifyCriticalFiles();
assertCondition("SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS: 1", $secPass === true);

echo PHP_EOL . "====================================================================" . PHP_EOL;
echo "RINGKASAN HASIL: {$passed} PASSED, {$failed} FAILED" . PHP_EOL;
echo "====================================================================" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
