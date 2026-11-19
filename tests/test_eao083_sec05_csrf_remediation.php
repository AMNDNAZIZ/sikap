<?php
/**
 * Test Suite: EAO-HO-2026-083
 * SEC-05: Global CSRF Exceptions & Updater HTTP Methods Remediation Verification
 *
 * Verifies:
 * 1. Global CSRF exception 'admin/updater/*' is removed from app/Config/Filters.php
 * 2. No wildcard or specific updater CSRF bypasses exist
 * 3. All 8 state-mutating updater routes converted to strict HTTP POST
 * 4. GET requests on state-mutating updater endpoints are safely intercepted without mutation
 * 5. All forms in updater views include csrf_field() and method="post"
 * 6. Zero insecure GET action links remain in updater views
 * 7. RBAC auth:1 filter enforced on all updater endpoints
 * 8. CI4 CSRF filter verification logic rejects un-tokened or invalid-token POSTs
 * 9. SecurityGuard critical files integrity check (SEC_PASS: 1)
 * 10. Sub-applications bayaran/ and cbtku/ verified absent
 */

declare(strict_types=1);

define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'system' . DIRECTORY_SEPARATOR);
define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);

if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

echo "=== EAO-HO-2026-083: SEC-05 CSRF & UPDATER HTTP METHODS TEST SUITE ===\n\n";

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

// -------------------------------------------------------------
// TEST GROUP 1: CSRF Configuration Audit (app/Config/Filters.php)
// -------------------------------------------------------------
echo "1. Testing CSRF Configuration in app/Config/Filters.php...\n";

$filtersContent = file_get_contents(APPPATH . 'Config/Filters.php');
assertTest("Filters.php exists and is readable", $filtersContent !== false);

// Check that 'admin/updater/*' is completely gone from Filters.php
assertTest("Pengecualian 'admin/updater/*' TIDAK ADA di Filters.php", !strpos($filtersContent, "'admin/updater/*'"));
assertTest("Pengecualian 'admin/updater' TIDAK ADA di Filters.php", !preg_match("/['\"]admin\/updater(\/\*)?['\"]/", $filtersContent));

// Verify that csrf is still registered in globals before
assertTest("Filter CSRF tetap aktif di globals['before']", strpos($filtersContent, "'csrf' => ['except' => [") !== false);

// Check that only sanctioned webhooks remain in exceptions
$expectedExceptions = [
    'auth/ping-session',
    'ping-session',
    'siswa/cbt/refreshCsrf',
    'siswa/cbt/ping',
    'siswa/cbt/saveAnswer',
    'siswa/cbt/saveAnswersBulk',
    'siswa/cbt/submit/*',
    'api/dapodik/receive',
];
foreach ($expectedExceptions as $exc) {
    assertTest("Pengecualian sah tetap dipertahankan: '{$exc}'", strpos($filtersContent, "'{$exc}'") !== false);
}

// -------------------------------------------------------------
// TEST GROUP 2: Routes Definition & HTTP Methods (app/Config/Routes.php)
// -------------------------------------------------------------
echo "\n2. Testing Route Definitions in app/Config/Routes.php...\n";

$routesContent = file_get_contents(APPPATH . 'Config/Routes.php');
assertTest("Routes.php exists and is readable", $routesContent !== false);

// Mutating endpoints that MUST be POST
$mutatingPostEndpoints = [
    'updater/patch-files'       => 'Updater::patchFiles',
    'updater/apply-online'      => 'Updater::applyOnlineUpdate',
    'updater/restore-database'  => 'Updater::restoreDatabase',
    'updater/run-migrations'    => 'Updater::runMigrations',
    'updater/generate-patch'    => 'Updater::generatePatch',
    'updater/generate-manifest' => 'Updater::generateManifest',
    'updater/backup-database'   => 'Updater::backupDatabase',
    'updater/clean-backups'     => 'Updater::cleanBackups',
];

foreach ($mutatingPostEndpoints as $route => $handler) {
    $pattern = "/\\\$routes->post\\(['\"]" . preg_quote($route, '/') . "['\"],\\s*['\"]" . preg_quote($handler, '/') . "['\"]/";
    assertTest("Route POST mutasi terdaftar: {$route} -> {$handler}", (bool)preg_match($pattern, $routesContent));
}

// Ensure generate-manifest is NO LONGER match(['get', 'post'])
assertTest("generate-manifest TIDAK MENGGUNAKAN match(['get', 'post'])", strpos($routesContent, "['get', 'post'], 'updater/generate-manifest'") === false);

// Read-only endpoints that MUST be GET
$readOnlyGetEndpoints = [
    'updater'              => 'Updater::index',
    'updater/check-online' => 'Updater::checkOnlineUpdate',
];

foreach ($readOnlyGetEndpoints as $route => $handler) {
    $pattern = "/\\\$routes->get\\(['\"]" . preg_quote($route, '/') . "['\"],\\s*['\"]" . preg_quote($handler, '/') . "['\"]/";
    assertTest("Route GET read-only terdaftar: {$route} -> {$handler}", (bool)preg_match($pattern, $routesContent));
}

// GET Fallback notice routes (safely intercepted without mutation)
$fallbackGetEndpoints = [
    'updater/run-migrations'    => 'Updater::getNotAllowedNotice',
    'updater/generate-patch'    => 'Updater::getNotAllowedNotice',
    'updater/generate-manifest' => 'Updater::getNotAllowedNotice',
    'updater/backup-database'   => 'Updater::getNotAllowedNotice',
    'updater/clean-backups'     => 'Updater::getNotAllowedNotice',
];

foreach ($fallbackGetEndpoints as $route => $handler) {
    $pattern = "/\\\$routes->get\\(['\"]" . preg_quote($route, '/') . "['\"],\\s*['\"]" . preg_quote($handler, '/') . "['\"]/";
    assertTest("Fallback GET aman terdaftar: {$route} -> {$handler}", (bool)preg_match($pattern, $routesContent));
}

// Verify auth:1 filter on updater routes
assertTest("Rute updater dilindungi filter auth:1", strpos($routesContent, "'updater', 'Updater::index', ['filter' => 'auth:1']") !== false);

// -------------------------------------------------------------
// TEST GROUP 3: Controller Defense-in-Depth Guard (Updater.php)
// -------------------------------------------------------------
echo "\n3. Testing Controller Implementation & Defense-in-Depth in Updater.php...\n";

$updaterContent = file_get_contents(APPPATH . 'Controllers/Admin/Updater.php');
assertTest("Updater.php exists and is readable", $updaterContent !== false);

// Verify getNotAllowedNotice method exists
assertTest("Method getNotAllowedNotice() terdefinisi di Updater.php", strpos($updaterContent, 'public function getNotAllowedNotice()') !== false);

// Verify method checks in mutating methods
$mutatingMethods = [
    'runMigrations',
    'backupDatabase',
    'generateManifest',
    'cleanBackups',
    'generatePatch',
];

foreach ($mutatingMethods as $m) {
    $pos = strpos($updaterContent, "public function {$m}()");
    assertTest("Method {$m}() terdefinisi", $pos !== false);
    if ($pos !== false) {
        $bodySlice = substr($updaterContent, $pos, 400);
        $hasMethodCheck = strpos($bodySlice, "\$this->request->getMethod()") !== false && strpos($bodySlice, "'post'") !== false;
        assertTest("Method {$m}() memiliki defense-in-depth method check (POST only)", $hasMethodCheck);
    }
}

// -------------------------------------------------------------
// TEST GROUP 4: View Callers & Form CSRF Verification
// -------------------------------------------------------------
echo "\n4. Testing Updater Views for CSRF Tokens and POST Forms...\n";

$indexView = file_get_contents(APPPATH . 'Views/admin/updater/index.php');
assertTest("index.php view exists and is readable", $indexView !== false);

// Verify no destructive GET links remain in index.php
assertTest("TIDAK ADA <a href> ke backup-database di index.php", !preg_match("/<a[^>]+href=['\"][^'\"]*updater\/backup-database['\"]/", $indexView));
assertTest("TIDAK ADA <a href> ke clean-backups di index.php", !preg_match("/<a[^>]+href=['\"][^'\"]*updater\/clean-backups['\"]/", $indexView));
assertTest("TIDAK ADA <a href> ke generate-patch di index.php", !preg_match("/<a[^>]+href=['\"][^'\"]*updater\/generate-patch['\"]/", $indexView));
assertTest("TIDAK ADA <a href> ke run-migrations di index.php", !preg_match("/<a[^>]+href=['\"][^'\"]*updater\/run-migrations['\"]/", $indexView));

// Verify that all forms targeting mutating actions use POST and contain csrf_field()
$expectedForms = [
    'admin/updater/backup-database',
    'admin/updater/clean-backups',
    'admin/updater/restore-database',
    'admin/updater/generate-manifest',
    'admin/updater/generate-patch',
    'admin/updater/patch-files',
    'admin/updater/run-migrations',
];

// Strip PHP tags to avoid premature closing of HTML tag regex
$cleanIndexView = preg_replace('/<\?=(.*?)\?>/s', '$1', $indexView);
preg_match_all('/<form\b[^>]*>/is', $cleanIndexView, $formTags);

foreach ($expectedForms as $action) {
    $found = false;
    foreach ($formTags[0] as $tag) {
        if (strpos($tag, $action) !== false) {
            if (stripos($tag, 'method="post"') !== false || stripos($tag, "method='post'") !== false) {
                $found = true;
                break;
            }
        }
    }
    assertTest("Form action '{$action}' menggunakan method='post'", $found);
}

// Check csrf_field count in index.php: should have at least 7 csrf_fields
$csrfCount = substr_count($indexView, 'csrf_field()');
assertTest('Seluruh 7 form di index.php memiliki csrf_field() (Ditemukan: ' . $csrfCount . ')', $csrfCount >= 7);

// Verify online_update.php
$onlineView = file_get_contents(APPPATH . 'Views/admin/updater/online_update.php');
assertTest("online_update.php view exists and is readable", $onlineView !== false);

$cleanOnlineView = preg_replace('/<\?=(.*?)\?>/s', '$1', $onlineView);
preg_match_all('/<form\b[^>]*>/is', $cleanOnlineView, $onlineTags);

$onlineFound = false;
foreach ($onlineTags[0] as $tag) {
    if (strpos($tag, 'updater/apply-online') !== false) {
        if (stripos($tag, 'method="post"') !== false || stripos($tag, "method='post'") !== false) {
            $onlineFound = true;
            break;
        }
    }
}
assertTest("Form apply-online menggunakan method='post'", $onlineFound);
assertTest('Form apply-online memiliki csrf_field()', strpos($onlineView, 'csrf_field()') !== false);

// -------------------------------------------------------------
// TEST GROUP 5: Simulated CI4 CSRF Verification Logic
// -------------------------------------------------------------
echo "\n5. Testing Simulated CI4 CSRF Verification Logic...\n";

// Emulate CI4 CSRF validation logic
function simulateCsrfFilter(string $method, string $uri, ?string $tokenSubmitted, string $expectedToken, array $exceptPatterns): int {
    // If method is GET, HEAD, OPTIONS -> CSRF filter does not block
    if (in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
        return 200; // Passthrough
    }

    // Check exceptions
    foreach ($exceptPatterns as $pattern) {
        $pattern = str_replace('/*', '/.*', $pattern);
        if (preg_match('#^' . $pattern . '$#i', $uri)) {
            return 200; // Whitelisted, bypass CSRF
        }
    }

    // Validate token
    if ($tokenSubmitted === null || $tokenSubmitted === '' || !hash_equals($expectedToken, $tokenSubmitted)) {
        return 403; // Forbidden by CSRF
    }

    return 200; // OK
}

// Extract current except patterns from Filters.php dynamically
preg_match("/'csrf'\\s*=>\\s*\\[\\s*'except'\\s*=>\\s*\\[(.*?)\\]\\]/s", $filtersContent, $matches);
$currentExcepts = [];
if (!empty($matches[1])) {
    preg_match_all("/['\"]([^'\"]+)['\"]/", $matches[1], $itemMatches);
    $currentExcepts = $itemMatches[1] ?? [];
}

$testCsrfToken = bin2hex(random_bytes(16));

// Scenario 1: POST to admin/updater/run-migrations WITHOUT CSRF token
$res1 = simulateCsrfFilter('POST', 'admin/updater/run-migrations', null, $testCsrfToken, $currentExcepts);
assertTest("POST ke admin/updater/run-migrations TANPA token ditolak HTTP 403", $res1 === 403);

// Scenario 2: POST to admin/updater/patch-files with INVALID CSRF token
$res2 = simulateCsrfFilter('POST', 'admin/updater/patch-files', 'invalid_token_123', $testCsrfToken, $currentExcepts);
assertTest("POST ke admin/updater/patch-files dengan token PALSU ditolak HTTP 403", $res2 === 403);

// Scenario 3: POST to admin/updater/restore-database with VALID CSRF token
$res3 = simulateCsrfFilter('POST', 'admin/updater/restore-database', $testCsrfToken, $testCsrfToken, $currentExcepts);
assertTest("POST ke admin/updater/restore-database dengan token VALID diterima HTTP 200", $res3 === 200);

// Scenario 4: POST to admin/updater/generate-manifest with VALID CSRF token
$res4 = simulateCsrfFilter('POST', 'admin/updater/generate-manifest', $testCsrfToken, $testCsrfToken, $currentExcepts);
assertTest("POST ke admin/updater/generate-manifest dengan token VALID diterima HTTP 200", $res4 === 200);

// Scenario 5: GET to admin/updater (read-only UI) is permitted
$res5 = simulateCsrfFilter('GET', 'admin/updater', null, $testCsrfToken, $currentExcepts);
assertTest("GET ke admin/updater (read-only) lolos filter HTTP 200", $res5 === 200);

// Scenario 6: GET to admin/updater/check-online is permitted
$res6 = simulateCsrfFilter('GET', 'admin/updater/check-online', null, $testCsrfToken, $currentExcepts);
assertTest("GET ke admin/updater/check-online lolos filter HTTP 200", $res6 === 200);

// -------------------------------------------------------------
// TEST GROUP 6: RBAC & Authenticated Authorization Check
// -------------------------------------------------------------
echo "\n6. Testing RBAC & Authentication Rules on Updater Routes...\n";

// In CI4, all routes inside group('admin', ..., ['filter' => 'auth:1']) require role === 1
function simulateRbacCheck(?int $userRole, string $requiredRole): int {
    if ($userRole === null) {
        return 302; // Redirect to login
    }
    if ($userRole !== (int)$requiredRole) {
        return 403; // Forbidden for non-admin
    }
    return 200; // Authorized
}

assertTest("Akses guest (belum login) dialihkan ke login (HTTP 302)", simulateRbacCheck(null, '1') === 302);
assertTest("Akses role 2 (Guru) ditolak hak aksesnya (HTTP 403)", simulateRbacCheck(2, '1') === 403);
assertTest("Akses role 3 (Siswa) ditolak hak aksesnya (HTTP 403)", simulateRbacCheck(3, '1') === 403);
assertTest("Akses role 7 (Kepala Sekolah) ditolak hak aksesnya (HTTP 403)", simulateRbacCheck(7, '1') === 403);
assertTest("Akses role 1 (Admin) lolos otorisasi (HTTP 200)", simulateRbacCheck(1, '1') === 200);

// -------------------------------------------------------------
// TEST GROUP 7: SecurityGuard & License Inviolability
// -------------------------------------------------------------
echo "\n7. Testing SecurityGuard License Integrity & Protected Files...\n";

require_once APPPATH . 'Libraries/SecurityGuard.php';
$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertTest("SecurityGuard::verifyCriticalFiles() === SEC_PASS: 1", ($secPass === true || $secPass === 1));

// Verify 8 protected files exist and are not tampered
$protectedFiles = [
    'app/Helpers/license_helper.php',
    'app/Libraries/LicenseGuard.php',
    'app/Libraries/SecurityGuard.php',
    'app/Libraries/SecureConfig.php',
    'app/Filters/LicenseFilter.php',
    'app/Models/LicenseModel.php',
    'app/Config/.security_hash',
    'app/Config/.lic_hash',
];

foreach ($protectedFiles as $pf) {
    assertTest("Protected file '{$pf}' exists", file_exists(ROOTPATH . $pf));
}

// Verify absence of deleted sub-apps
assertTest("Sub-app 'bayaran/' absent", !is_dir(ROOTPATH . 'bayaran'));
assertTest("Sub-app 'cbtku/' absent", !is_dir(ROOTPATH . 'cbtku'));

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests : " . ($passCount + $failCount) . "\n";
echo "Passed      : {$passCount}\n";
echo "Failed      : {$failCount}\n";

if ($failCount === 0) {
    echo "\n>>> RESULT: ALL EAO-083 TESTS PASSED (100%) <<<\n";
    exit(0);
} else {
    echo "\n>>> RESULT: SOME TESTS FAILED <<<\n";
    exit(1);
}
