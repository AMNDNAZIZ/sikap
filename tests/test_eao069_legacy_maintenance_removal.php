<?php
/**
 * Test Suite: EAO-HO-2026-069 Permanent Removal of Legacy Maintenance Endpoints
 *
 * Menguji kepatuhan terhadap seluruh persyaratan Mandat EAO-HO-2026-069:
 * 1. Legacy Removal:
 *    - Controller Maintenance.php telah dihapus permanen dari disk.
 *    - Route maintenance/debug-token tidak terdaftar.
 *    - Route maintenance/reset-hashes/(:any) tidak terdaftar.
 *    - update_manifest.json tidak memuat Maintenance.php.
 * 2. Komponen yang Dipertahankan:
 *    - MaintenanceFilter.php tetap ada dan utuh.
 *    - LicenseRehash.php tetap ada dan utuh.
 *    - LicenseGuard, SecurityGuard, SecureConfig, license_helper, LicenseModel tetap utuh.
 * 3. Integritas:
 *    - .security_hash dan .lic_hash tetap ada dan valid.
 *    - SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS: 1.
 * 4. Subaplikasi Terhapus:
 *    - Direktori bayaran/ dan cbtku/ tetap tidak ada.
 */

define('APPPATH', realpath(__DIR__ . '/../app') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(__DIR__ . '/../system') . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(__DIR__ . '/../writable') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

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

require_once APPPATH . 'Libraries/SecureConfig.php';
require_once APPPATH . 'Libraries/SecurityGuard.php';
require_once APPPATH . 'Libraries/LicenseGuard.php';

use App\Libraries\SecurityGuard;
use App\Libraries\LicenseGuard;

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
echo "TEST SUITE EAO-HO-2026-069: PERMANENT REMOVAL OF LEGACY MAINTENANCE" . PHP_EOL;
echo "====================================================================" . PHP_EOL . PHP_EOL;

// --- 1. VERIFIKASI PENGHAPUSAN KONTROLER & ROUTE LEGACY ---
echo "--- 1. VERIFIKASI PENGHAPUSAN KONTROLER & ROUTE LEGACY ---" . PHP_EOL;

$controllerPath = APPPATH . 'Controllers/Maintenance.php';
assertCondition("Controller: Maintenance.php telah dihapus permanen dari disk",
    !file_exists($controllerPath)
);

$routesContent = file_get_contents(APPPATH . 'Config/Routes.php');
assertCondition("Routing: Route 'maintenance/debug-token' tidak terdaftar",
    strpos($routesContent, 'debug-token') === false
);
assertCondition("Routing: Route 'maintenance/reset-hashes' tidak terdaftar",
    strpos($routesContent, 'reset-hashes') === false
);
assertCondition("Routing: Tidak ada rute lain yang mengarah ke Controller Maintenance",
    strpos($routesContent, 'Maintenance::') === false
);

// --- 2. VERIFIKASI MANIFEST RILIS ---
echo PHP_EOL . "--- 2. VERIFIKASI MANIFEST RILIS ---" . PHP_EOL;

$manifestPath = ROOTPATH . 'update_manifest.json';
$manifestData = json_decode(file_get_contents($manifestPath), true);
$manifestFiles = $manifestData['files'] ?? [];

assertCondition("Manifest: update_manifest.json ada dan valid", is_array($manifestFiles) && count($manifestFiles) > 0);
assertCondition("Manifest: Maintenance.php tidak ada dalam update_manifest.json",
    !isset($manifestFiles['app/Controllers/Maintenance.php'])
);

// --- 3. VERIFIKASI KOMPONEN YANG WAJIB DIPERTAHANKAN ---
echo PHP_EOL . "--- 3. VERIFIKASI KOMPONEN YANG DIPERTAHANKAN ---" . PHP_EOL;

$maintFilterPath = APPPATH . 'Filters/MaintenanceFilter.php';
assertCondition("Komponen Bertahan: MaintenanceFilter.php tetap ada di disk",
    file_exists($maintFilterPath)
);

$rehashCmdPath = APPPATH . 'Commands/LicenseRehash.php';
assertCondition("Komponen Bertahan: LicenseRehash.php tetap ada di disk",
    file_exists($rehashCmdPath)
);

$protectedFiles = [
    'app/Libraries/LicenseGuard.php',
    'app/Libraries/SecurityGuard.php',
    'app/Libraries/SecureConfig.php',
    'app/Helpers/license_helper.php',
    'app/Models/LicenseModel.php',
    'app/Filters/LicenseFilter.php',
];
foreach ($protectedFiles as $pf) {
    assertCondition("Komponen Bertahan: {$pf} tetap ada",
        file_exists(ROOTPATH . $pf)
    );
}

// --- 4. VERIFIKASI INTEGRITAS LISENSI & SECURITYGUARD ---
echo PHP_EOL . "--- 4. VERIFIKASI INTEGRITAS LISENSI & SECURITYGUARD ---" . PHP_EOL;

$secHashPath = APPPATH . 'Config/.security_hash';
$licHashPath = APPPATH . 'Config/.lic_hash';

assertCondition("Integritas: Berkas .security_hash tetap utuh di disk", file_exists($secHashPath));
assertCondition("Integritas: Berkas .lic_hash tetap utuh di disk", file_exists($licHashPath));

$secPass = SecurityGuard::verifyCriticalFiles();
assertCondition("Integritas: SecurityGuard::verifyCriticalFiles() menghasilkan SEC_PASS: 1",
    $secPass === true
);

// --- 5. VERIFIKASI STATUS SUBAAPLIKASI (BAYARAN & CBTKU) ---
echo PHP_EOL . "--- 5. VERIFIKASI SUBAAPLIKASI (BAYARAN & CBTKU) ---" . PHP_EOL;

assertCondition("Subaplikasi: bayaran/ tetap tidak ada (absent)",
    !is_dir(ROOTPATH . 'bayaran')
);
assertCondition("Subaplikasi: cbtku/ tetap tidak ada (absent)",
    !is_dir(ROOTPATH . 'cbtku')
);

echo PHP_EOL . "====================================================================" . PHP_EOL;
echo "RINGKASAN HASIL: {$passed} PASSED, {$failed} FAILED" . PHP_EOL;
echo "====================================================================" . PHP_EOL;

exit($failed > 0 ? 1 : 0);
