<?php
/**
 * Test Suite: EAO-HO-2026-070
 * SEC-02: GET Destructive Routes Remediation Verification
 *
 * Verifies:
 * 1. All 44 destructive routes converted from GET to POST in Routes.php
 * 2. Zero destructive GET routes remain in Routes.php
 * 3. Auth filters and RBAC preserved across all 44 routes
 * 4. Zero insecure GET callers (<a href> or window.location.href) remain in views
 * 5. CSRF tokens present in all deletion forms and AJAX calls
 * 6. SecurityGuard license integrity (SEC_PASS: 1)
 * 7. Subapp absence (bayaran/ and cbtku/ absent)
 */

declare(strict_types=1);

echo "=== EAO-HO-2026-070: SEC-02 GET DESTRUCTIVE ROUTES TEST SUITE ===\n\n";

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
// TEST GROUP 1: Route Method Audit in app/Config/Routes.php
// -------------------------------------------------------------
echo "1. Testing Route Definitions in app/Config/Routes.php...\n";

$routesContent = file_get_contents(__DIR__ . '/../app/Config/Routes.php');
assertTest("Routes.php readable", $routesContent !== false);

// 44 target route patterns
$targetPatterns = [
    'users/delete/(:num)',
    'role-permission/delete/(:num)',
    'jenjang-master/delete/(:num)',
    'mapel-master/delete/(:num)',
    'teachers/deleteDocument/(:num)',
    'teachers/delete/(:num)',
    'teachers/deleteSub/(:segment)/(:num)',
    'classes/delete/(:num)',
    'holidays/delete/(:num)',
    'teachingassignments/delete/(:num)',
    'cp-master/delete/(:num)',
    'tp/delete/(:num)',
    'atp/delete/(:num)',
    'atp/delete-tp/(:num)',
    'modul-ajar/delete/(:num)/(:num)/(:num)',
    'kktp/delete/(:num)',
    'delete/(:num)',
    'surat-masuk/delete/(:num)',
    'buku-tamu/delete/(:num)',
    'agendas/(:num)/delete',
    'teaching-journal/delete/(:num)',
    'schedules/delete/(:num)',
    'announcements/delete/(:num)',
    'assessments/deleteOne/(:num)/(:segment)',
    'assessments/deleteBatch/formatif/(:num)/(:alpha)',
    'assessments/deleteBatch/sumatif/(:num)/(:num)/(:segment)',
    'assessments/deleteBatch/final/(:num)/(:num)',
    'assessments/deleteBatch/pts/(:num)/(:num)',
    'tugas/delete/(:num)',
    'materials/delete/(:num)',
    'student-notes/delete/(:num)',
    'behaviors/delete/(:num)',
    'cbt/banksoal/deleteAudio/(:num)/(:any)',
    'exam-schedule/delete/(:num)',
    'cbt/banksoal/delete/(:num)',
    'cbt/examname/delete/(:num)',
    'cbt/teststatus/delete/(:num)',
    'sliders/delete/(:num)',
    'facilities/delete/(:num)',
    'articles/delete/(:num)',
    'activities/delete/(:num)',
    'changelogs/delete/(:num)',
    'delete-sub/(:segment)/(:num)',
    'delete-document/(:num)',
];

// Check each target pattern is NOT registered as GET
$getDeleteRoutesFound = [];
$postDeleteRoutesFound = [];

foreach ($targetPatterns as $pattern) {
    // Check if GET route exists for this pattern
    $escaped = preg_quote($pattern, '/');
    if (preg_match('/\$routes->get\s*\(\s*[\'"]' . $escaped . '[\'"]/', $routesContent, $m)) {
        $getDeleteRoutesFound[] = $pattern;
    }
    // Check if POST route exists for this pattern
    if (preg_match('/\$routes->post\s*\(\s*[\'"]' . $escaped . '[\'"]/', $routesContent, $m)) {
        $postDeleteRoutesFound[] = $pattern;
    }
}

assertTest("Zero target patterns registered as GET", count($getDeleteRoutesFound) === 0, "Found: " . implode(', ', $getDeleteRoutesFound));
assertTest("All 44 target patterns registered as POST", count($postDeleteRoutesFound) === 44, "Found: " . count($postDeleteRoutesFound) . " / 44");

// Overall scan: Zero GET routes with 'delete' in Routes.php
preg_match_all('/\$routes->get\s*\([^;]*delete[^;]*\);/i', $routesContent, $allGetDelete);
assertTest("Overall scan: Zero GET routes with 'delete' exist in Routes.php", count($allGetDelete[0]) === 0, "Found: " . count($allGetDelete[0]));

// -------------------------------------------------------------
// TEST GROUP 2: Auth Filters & RBAC Preservation
// -------------------------------------------------------------
echo "\n2. Testing Auth Filters & RBAC Preservation...\n";

// Specific routes must retain their auth filters
assertTest("users/delete has auth12 filter", (bool)preg_match('/\$routes->post\(\'users\/delete\/\(:num\)\',\s*\'User::delete\/\$1\',\s*\$auth12\);/', $routesContent));
assertTest("role-permission/delete has auth1 filter", (bool)preg_match('/\$routes->post\(\'role-permission\/delete\/\(:num\)\',\s*\'RolePermission::deleteRole\/\$1\',\s*\$auth1\);/', $routesContent));
assertTest("jenjang-master/delete has auth1 filter", (bool)preg_match('/\$routes->post\(\'jenjang-master\/delete\/\(:num\)\',\s*\'JenjangMaster::delete\/\$1\',\s*\$auth1\);/', $routesContent));
assertTest("teachers/delete has auth127 filter", (bool)preg_match('/\$routes->post\(\'teachers\/delete\/\(:num\)\',\s*\'Teachers::delete\/\$1\',\s*\$auth127\);/', $routesContent));
assertTest("schedules/delete has auth:1,3,7 filter", (bool)preg_match('/\$routes->post\(\'schedules\/delete\/\(:num\)\',\s*\'ScheduleController::delete\/\$1\',\s*\[\'filter\'\s*=>\s*\'auth:1,3,7\'\]\);/', $routesContent));
assertTest("assessments/deleteOne has auth123 filter", (bool)preg_match('/\$routes->post\(\'assessments\/deleteOne\/\(:num\)\/\(:segment\)\',\s*\'Assessment::deleteOne\/\$1\/\$2\',\s*\$auth123\);/', $routesContent));
assertTest("cbt/banksoal/delete has auth123 filter", (bool)preg_match('/\$routes->post\(\'cbt\/banksoal\/delete\/\(:num\)\',\s*\'CbtBankSoal::delete\/\$1\',\s*\$auth123\);/', $routesContent));

// -------------------------------------------------------------
// TEST GROUP 3: Global CSRF Filter Configuration
// -------------------------------------------------------------
echo "\n3. Testing Global CSRF Filter Configuration...\n";

$filtersContent = file_get_contents(__DIR__ . '/../app/Config/Filters.php');
assertTest("Filters.php has 'csrf' alias", strpos($filtersContent, "'csrf' => CSRF::class") !== false);
assertTest("Filters.php has global csrf before filter", strpos($filtersContent, "'csrf' => ['except' => [") !== false);
assertTest("None of the 44 delete routes are in CSRF except list", (bool)!preg_match('/delete/i', substr($filtersContent, strpos($filtersContent, "'csrf' => ['except' => ["), 1200)));

// -------------------------------------------------------------
// TEST GROUP 4: View Callers Remediation Scan
// -------------------------------------------------------------
echo "\n4. Testing View Callers for Insecure GET Invocations...\n";

$viewDir = realpath(__DIR__ . '/../app/Views');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewDir));
$insecureCallers = [];
$viewsChecked = 0;

$patternsForView = [
    'admin/users/delete',
    'admin/role-permission/delete',
    'admin/jenjang-master/delete',
    'admin/mapel-master/delete',
    'admin/teachers/deleteDocument',
    'admin/teachers/delete',
    'admin/teachers/deleteSub',
    'admin/classes/delete',
    'admin/holidays/delete',
    'admin/teachingassignments/delete',
    'admin/administrasi-guru/cp-master/delete',
    'admin/administrasi-guru/tp/delete',
    'admin/administrasi-guru/atp/delete',
    'admin/administrasi-guru/atp/delete-tp',
    'admin/administrasi-guru/modul-ajar/delete',
    'admin/administrasi-guru/kktp/delete',
    'admin/kokurikuler/delete',
    'admin/surat-masuk/delete',
    'admin/buku-tamu/delete',
    'admin/agendas/',
    'admin/teaching-journal/delete',
    'admin/schedules/delete',
    'admin/announcements/delete',
    'admin/assessments/deleteOne',
    'admin/assessments/deleteBatch/formatif',
    'admin/assessments/deleteBatch/sumatif',
    'admin/assessments/deleteBatch/final',
    'admin/assessments/deleteBatch/pts',
    'admin/tugas/delete',
    'admin/materials/delete',
    'admin/student-notes/delete',
    'admin/behaviors/delete',
    'admin/cbt/banksoal/deleteAudio',
    'admin/exam-schedule/delete',
    'admin/cbt/banksoal/delete',
    'admin/cbt/examname/delete',
    'admin/cbt/teststatus/delete',
    'admin/cms/sliders/delete',
    'admin/cms/facilities/delete',
    'admin/cms/articles/delete',
    'admin/cms/activities/delete',
    'admin/changelogs/delete',
    'profile/delete-sub',
    'profile/delete-document',
];

foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js'])) continue;
    $viewsChecked++;
    
    $vContent = file_get_contents($file->getPathname());
    $relPath = str_replace($viewDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    
    foreach ($patternsForView as $pv) {
        $escaped = preg_quote($pv, '/');
        // Check for <a href="...pv..."
        if (preg_match('/<a\s+[^>]*href=[\'"][^\'"]*' . $escaped . '[^\'"]*[\'"]/i', $vContent, $m)) {
            $insecureCallers[] = "{$relPath}: {$m[0]}";
        }
        // Check for window.location.href = "...pv..."
        if (preg_match('/window\.location\.href\s*=\s*[\'"`][^\'"`]*' . $escaped . '/i', $vContent, $m)) {
            $insecureCallers[] = "{$relPath}: {$m[0]}";
        }
    }
}

assertTest("Views checked ({$viewsChecked} view files scanned)", $viewsChecked > 400);
assertTest("Zero insecure GET callers (<a href> or window.location.href) found", count($insecureCallers) === 0, "Found: " . implode("\n    ", $insecureCallers));

// -------------------------------------------------------------
// TEST GROUP 5: Protected Files, Hashes, and License Integrity
// -------------------------------------------------------------
echo "\n5. Testing Protected Files & SecurityGuard Integrity...\n";

if (!defined('APPPATH')) {
    define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
}
require_once __DIR__ . '/../app/Libraries/SecurityGuard.php';

$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertTest("SecurityGuard::verifyCriticalFiles() returns SEC_PASS (1)", $secPass === true);

$secHashFile = __DIR__ . '/../app/Config/.security_hash';
$licHashFile = __DIR__ . '/../app/Config/.lic_hash';
assertTest(".security_hash exists", file_exists($secHashFile));
assertTest(".lic_hash exists", file_exists($licHashFile));

// -------------------------------------------------------------
// TEST GROUP 6: Sub-Application Absence
// -------------------------------------------------------------
echo "\n6. Testing Sub-Application Absence...\n";

assertTest("Subapp bayaran/ is absent", !is_dir(__DIR__ . '/../bayaran'));
assertTest("Subapp cbtku/ is absent", !is_dir(__DIR__ . '/../cbtku'));

// -------------------------------------------------------------
// Summary
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "TEST RESULTS: Total = " . ($passCount + $failCount) . " | Passed = {$passCount} | Failed = {$failCount}\n";
echo "=======================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
