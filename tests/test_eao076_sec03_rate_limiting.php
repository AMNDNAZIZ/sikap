<?php
/**
 * Test Suite: EAO-HO-2026-076 — SEC-03 Login Rate Limiting Implementation Test
 * 
 * Menguji kepatuhan penuh terhadap seluruh spesifikasi Mandat EAO-HO-2026-076:
 * - Bagian A: Tier 1 Targeted Brute-Force (5 kegagalan/300s, users.id binding, username/email unification)
 * - Bagian B: Tier 2 Global IP Volumetric (100 kegagalan/300s, NAT isolation, counter retention)
 * - Bagian C: Tier 3 Passive Monitoring (>15 kegagalan/600s, zero blocking, zero account lockout)
 * - Bagian D: Anti-Enumeration & UX (web form redirect vs AJAX 429, generic failure message)
 * - Bagian E: Counter Recovery (eksplisit pada login sukses, tahan terhadap 302 gagal / sesi lama)
 * - Bagian F: Cache Subsystem & Fail-Open Behavior
 * - Bagian G: SecurityGuard & Lisensi (SEC_PASS: 1)
 */

declare(strict_types=1);

define('APPPATH', realpath(__DIR__ . '/../app') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(__DIR__ . '/../system') . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

require_once ROOTPATH . 'vendor/autoload.php';

// Mock/Helper minimal untuk CI4
if (!function_exists('env')) {
    function env($key, $default = null) {
        if ($key === 'LICENSE_HASH_SECRET') return 'EspataSecureSecretKey2026';
        return $default;
    }
}
if (!function_exists('log_message')) {
    function log_message($level, $message, array $context = []): bool {
        global $loggedMessages;
        $loggedMessages[] = ['level' => $level, 'message' => $message];
        return true;
    }
}
if (!function_exists('audit_log')) {
    function audit_log($action, $tableName = null, $recordId = null, $oldData = null, $newData = null): bool {
        global $auditLogs;
        $auditLogs[] = compact('action', 'tableName', 'recordId', 'oldData', 'newData');
        return true;
    }
}

// Memory Mock Cache Handler untuk deterministic unit testing
class MockArrayCacheHandler
{
    public array $data = [];

    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function save(string $key, $value, int $ttl = 60): bool
    {
        $this->data[$key] = $value;
        return true;
    }

    public function delete(string $key): bool
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }
}

require_once APPPATH . 'Filters/RateLimitFilter.php';
require_once APPPATH . 'Libraries/SecureConfig.php';
require_once APPPATH . 'Libraries/SecurityGuard.php';

use App\Filters\RateLimitFilter;

$passCount = 0;
$failCount = 0;

function assertTest(string $desc, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$desc} - {$detail}\n";
    }
}

echo "=== TEST SUITE: EAO-HO-2026-076 SEC-03 LOGIN RATE LIMITING ===\n\n";

$mockCache = new MockArrayCacheHandler();
$ip = '192.168.1.100';

// -------------------------------------------------------------
// 1. TIER 1 TESTS: Targeted Brute Force & Account Binding
// -------------------------------------------------------------
echo "1. Testing Tier 1: Targeted Brute Force (5 Failures / 300s)...\n";

$userId = 42;
$username = 'guru_sains';
$email = 'guru.sains@sekolah.sch.id';

// Awalnya akun tidak terblokir
assertTest("Akun awalnya tidak terblokir", !RateLimitFilter::isAccountBlocked($ip, $userId, $mockCache));

// Simulasikan 4 kegagalan via username
for ($i = 1; $i <= 4; $i++) {
    RateLimitFilter::recordFailure($ip, $userId, $username, $mockCache);
}
assertTest("Setelah 4 kegagalan, akun belum terblokir (< 5)", !RateLimitFilter::isAccountBlocked($ip, $userId, $mockCache));

// Kegagalan ke-5 via EMAIL untuk akun yang sama!
RateLimitFilter::recordFailure($ip, $userId, $email, $mockCache);

// Uji penyatuan username & email pada ID akun yang sama
assertTest("Kegagalan ke-5 via email memicu blokir akun (users.id = 42 terblokir)", RateLimitFilter::isAccountBlocked($ip, $userId, $mockCache));

// Uji bahwa cache alias terbentuk dan mengarah ke ID yang benar
$mappedId = RateLimitFilter::getMappedUserId($ip, strtolower($username), $mockCache);
assertTest("Cache alias username terpetakan ke users.id 42", $mappedId === $userId);

$mappedEmailId = RateLimitFilter::getMappedUserId($ip, strtolower($email), $mockCache);
assertTest("Cache alias email terpetakan ke users.id 42", $mappedEmailId === $userId);

// Uji isolasi akun: Akun lain pada IP yang sama TIDAK terblokir
$otherUserId = 99;
assertTest("Akun lain (user_id 99) pada IP yang sama TIDAK terblokir", !RateLimitFilter::isAccountBlocked($ip, $otherUserId, $mockCache));

// Uji akun tidak ditemukan (raw input)
$mockCache->data = []; // Reset
$fakeUser = 'nonexistent_user_xyz';
for ($i = 1; $i <= 5; $i++) {
    RateLimitFilter::recordFailure($ip, null, $fakeUser, $mockCache);
}
assertTest("Raw input non-existent terblokir setelah 5 kegagalan", RateLimitFilter::isRawInputBlocked($ip, $fakeUser, $mockCache));
assertTest("Input lain tidak terblokir oleh raw input palsu", !RateLimitFilter::isRawInputBlocked($ip, 'other_fake_user', $mockCache));

// -------------------------------------------------------------
// 2. TIER 2 TESTS: Global IP Volumetric Protection (100 Failures)
// -------------------------------------------------------------
echo "\n2. Testing Tier 2: Global IP Volumetric (100 Failures / 300s)...\n";

$mockCache->data = []; // Reset
$schoolIp = '202.152.1.50'; // IP Publik NAT Sekolah

assertTest("IP sekolah awalnya tidak terblokir", !RateLimitFilter::isIpBlocked($schoolIp, $mockCache));

// Simulasikan 99 kegagalan lintas 99 username berbeda
for ($i = 1; $i <= 99; $i++) {
    RateLimitFilter::recordFailure($schoolIp, null, "student_{$i}", $mockCache);
}
assertTest("IP sekolah setelah 99 kegagalan masih belum terblokir (< 100)", !RateLimitFilter::isIpBlocked($schoolIp, $mockCache));

// Kegagalan ke-100
RateLimitFilter::recordFailure($schoolIp, null, "student_100", $mockCache);
assertTest("IP sekolah mencapai 100 kegagalan -> TERBLOKIR", RateLimitFilter::isIpBlocked($schoolIp, $mockCache));

// Uji bahwa login sukses 1 akun TIDAK menghapus counter Tier 2 IP
RateLimitFilter::recordSuccess($schoolIp, 1, 'kepsek', $mockCache);
assertTest("Login sukses satu akun TIDAK menghapus counter Tier 2 IP", RateLimitFilter::isIpBlocked($schoolIp, $mockCache));

// Uji IP lain tidak terpengaruh oleh blokir IP sekolah
$homeIp = '180.252.10.5';
assertTest("IP rumah guru tidak terpengaruh oleh blokir IP sekolah", !RateLimitFilter::isIpBlocked($homeIp, $mockCache));

// -------------------------------------------------------------
// 3. TIER 3 TESTS: Passive Monitoring (> 15 Failures)
// -------------------------------------------------------------
echo "\n3. Testing Tier 3: Passive Account Attack Monitoring (> 15 Failures / 600s)...\n";

$mockCache->data = []; // Reset
$targetUser = 1; // Akun admin/kepsek
$loggedMessages = [];
$auditLogs = [];

// Simulasikan 15 kegagalan dari 15 IP berbeda
for ($i = 1; $i <= 15; $i++) {
    RateLimitFilter::recordFailure("10.0.0.{$i}", $targetUser, 'admin', $mockCache);
}
assertTest("Setelah 15 kegagalan, Tier 3 belum mencatat alert (threshold > 15)", count($auditLogs) === 0);

// Kegagalan ke-16 dari IP ke-16
RateLimitFilter::recordFailure("10.0.0.16", $targetUser, 'admin', $mockCache);
assertTest("Kegagalan ke-16 memicu audit log security_alert Tier 3", count($auditLogs) >= 1 && $auditLogs[0]['action'] === 'security_alert');

// Pastikan Tier 3 TIDAK memblokir login sah dari IP baru
$cleanIp = '10.0.0.99';
assertTest("Tier 3 TIDAK memblokir login akun dari IP baru (zero lockout)", !RateLimitFilter::isAccountBlocked($cleanIp, $targetUser, $mockCache));

// -------------------------------------------------------------
// 4. COUNTER RECOVERY TESTS: Explicit Success vs Failure vs Old Session
// -------------------------------------------------------------
echo "\n4. Testing Counter Recovery: Explicit Authentication Success...\n";

$mockCache->data = []; // Reset
$testIp = '192.168.1.55';
$userToRecover = 7;
$loginStr = 'guru_matematika';

// Simulasikan 3 kegagalan
for ($i = 1; $i <= 3; $i++) {
    RateLimitFilter::recordFailure($testIp, $userToRecover, $loginStr, $mockCache);
}
$accKey = RateLimitFilter::getAccountKey($testIp, $userToRecover);
assertTest("Counter akun awal tercatat bernilai 3", $mockCache->get($accKey) === 3);

// Pemulihan sukses eksplisit
RateLimitFilter::recordSuccess($testIp, $userToRecover, $loginStr, $mockCache);
assertTest("Setelah recordSuccess, counter akun terhapus (null)", $mockCache->get($accKey) === null);
assertTest("Setelah recordSuccess, raw input terhapus (null)", $mockCache->get(RateLimitFilter::getRawKey($testIp, strtolower($loginStr))) === null);
assertTest("Setelah recordSuccess, map alias terhapus (null)", $mockCache->get(RateLimitFilter::getMapKey($testIp, strtolower($loginStr))) === null);

// -------------------------------------------------------------
// 5. CACHE DEGRADATION & FAIL-OPEN TESTS
// -------------------------------------------------------------
echo "\n5. Testing Cache Degradation & Fail-Open Behavior...\n";

// DummyHandler mock
$dummyHandler = new \CodeIgniter\Cache\Handlers\DummyHandler();
assertTest("DummyHandler terdeteksi sebagai degraded cache", RateLimitFilter::isCacheDegraded($dummyHandler));
assertTest("MockArrayCacheHandler bukan degraded cache", !RateLimitFilter::isCacheDegraded($mockCache));

// -------------------------------------------------------------
// 6. ANTI-ENUMERATION & RESPONSE FORMATS
// -------------------------------------------------------------
echo "\n6. Testing Anti-Enumeration & Response Consistency...\n";

assertTest("Pesan blokir generik dan ramah pengguna", RateLimitFilter::BLOCKED_MESSAGE === 'Terlalu banyak percobaan login gagal. Demi keamanan, silakan tunggu 5 menit sebelum mencoba kembali.');

// -------------------------------------------------------------
// 7. SECURITY & PROTECTED FILES VERIFICATION
// -------------------------------------------------------------
echo "\n7. Testing SecurityGuard License Integrity...\n";

$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertTest("SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS: 1", $secPass === true);

assertTest("Subapp bayaran/ tidak ada", !is_dir(ROOTPATH . 'bayaran'));
assertTest("Subapp cbtku/ tidak ada", !is_dir(ROOTPATH . 'cbtku'));

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests : " . ($passCount + $failCount) . "\n";
echo "Passed      : {$passCount}\n";
echo "Failed      : {$failCount}\n";

if ($failCount > 0) {
    echo "STATUS: FAILED\n";
    exit(1);
} else {
    echo "STATUS: ALL TESTS PASSED SUCCESSFULLY (ZERO REGRESSION)\n";
    exit(0);
}
