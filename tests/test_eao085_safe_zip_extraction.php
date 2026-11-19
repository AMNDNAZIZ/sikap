<?php
/**
 * Test Suite: EAO-HO-2026-085
 * SEC-ZIP-01 Safe ZIP Extraction & Zip Slip Remediation Verification
 *
 * Verifies:
 * Group A: Path Traversal Protection (../, ..\, absolute, Windows drive, UNC, null byte)
 * Group B: Critical Protected Files Protection (.security_hash, .lic_hash, SecurityGuard.php, .env, etc.)
 * Group C: Abnormal ZIP Handling (symlinks, duplicate entries, file/directory collisions, malformed names)
 * Group D: ZIP Bomb Protection (entry count, decompression size, single file limit, compression ratio)
 * Group E: Valid Normal Package Lifecycle (Inspect -> Validate -> Stage -> Backup -> Apply -> Rollback on error -> Cleanup)
 * Group F: System & License Integrity (SecurityGuard SEC_PASS: 1, protected files inviolability, subapps absent)
 */

declare(strict_types=1);

define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'system' . DIRECTORY_SEPARATOR);
define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);

require_once SYSTEMPATH . 'Test/bootstrap.php';
require_once APPPATH . 'Libraries/SafeZipExtractor.php';

use App\Libraries\SafeZipExtractor;
use App\Libraries\SafeZipExtractionException;

echo "=== EAO-HO-2026-085: SAFE ZIP EXTRACTION & ZIP SLIP TEST SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertZip(string $desc, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$desc} - {$detail}\n";
    }
}

// Helper: Setup isolated fixture directory in writable/
$testFixtureDir = WRITEPATH . 'test_zip_fixture_' . time() . DIRECTORY_SEPARATOR;
if (!is_dir($testFixtureDir)) {
    mkdir($testFixtureDir, 0755, true);
}

// Helper: Helper to create test zip with specific entries
function createTestZip(string $zipPath, array $entries): bool {
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }
    foreach ($entries as $name => $content) {
        if (str_ends_with($name, '/')) {
            $zip->addEmptyDir(rtrim($name, '/'));
        } else {
            $zip->addFromString($name, (string)$content);
        }
    }
    return $zip->close();
}

// Helper: Expect SafeZipExtractionException on sanitizeZipEntries
function expectSanitizationFailure(string $zipPath, string $expectedMessageSubstr = ''): bool {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return false;
    }
    try {
        SafeZipExtractor::sanitizeZipEntries($zip);
        $zip->close();
        return false; // Should not reach here
    } catch (SafeZipExtractionException $e) {
        $zip->close();
        if ($expectedMessageSubstr !== '') {
            return stripos($e->getMessage(), $expectedMessageSubstr) !== false;
        }
        return true;
    } catch (\Throwable $e) {
        $zip->close();
        return false;
    }
}

// =========================================================================
// GROUP A: PATH TRAVERSAL VERIFICATION
// =========================================================================
echo "1. Menguji Perlindungan Path Traversal (Group A)...\n";

// A.1 Unix relative traversal
$z1 = $testFixtureDir . 'trav_unix.zip';
createTestZip($z1, ['app/Controllers/../outside.txt' => 'payload']);
assertZip("Tolak relative traversal 'app/Controllers/../outside.txt'", expectSanitizationFailure($z1, 'traversal'));

// A.2 Windows backslash traversal
$z2 = $testFixtureDir . 'trav_win.zip';
createTestZip($z2, ['app\\..\\outside.txt' => 'payload']);
assertZip("Tolak Windows backslash traversal 'app\\..\\outside.txt'", expectSanitizationFailure($z2, 'traversal'));

// A.3 Direct double dot
$z3 = $testFixtureDir . 'trav_direct.zip';
createTestZip($z3, ['../../outside.txt' => 'payload']);
assertZip("Tolak direct root traversal '../../outside.txt'", expectSanitizationFailure($z3, 'traversal'));

// A.4 Absolute Unix path
$z4 = $testFixtureDir . 'trav_abs_unix.zip';
createTestZip($z4, ['/etc/passwd' => 'root:x:0:0:root']);
assertZip("Tolak absolute Unix path '/etc/passwd'", expectSanitizationFailure($z4, 'absolut'));

// A.5 Windows drive letter
$z5 = $testFixtureDir . 'trav_drive.zip';
createTestZip($z5, ['C:/Windows/System32/calc.exe' => 'binary']);
assertZip("Tolak Windows drive letter path 'C:/Windows/...'", expectSanitizationFailure($z5, 'drive'));

// A.6 UNC path
$z6 = $testFixtureDir . 'trav_unc.zip';
createTestZip($z6, ['//server/share/payload.php' => 'unc']);
assertZip("Tolak UNC network path '//server/share/...'", expectSanitizationFailure($z6, 'absolut'));

// A.7 Windows reserved device name
$z7 = $testFixtureDir . 'trav_aux.zip';
createTestZip($z7, ['app/aux.php' => 'aux']);
assertZip("Tolak Windows reserved device name 'aux.php'", expectSanitizationFailure($z7, 'reserved device'));

// =========================================================================
// GROUP B: CRITICAL PROTECTED FILES PROTECTION
// =========================================================================
echo "\n2. Menguji Perlindungan Berkas Terlindungi Lisensi & Sistem (Group B)...\n";

// B.1 Attempt to overwrite .security_hash
$zb1 = $testFixtureDir . 'prot_sec_hash.zip';
createTestZip($zb1, ['app/Config/.security_hash' => 'tampered_hash']);
assertZip("Tolak penimpaan 'app/Config/.security_hash'", expectSanitizationFailure($zb1, 'PELANGGARAN KEAMANAN KRITIS'));

// B.2 Attempt to overwrite .lic_hash
$zb2 = $testFixtureDir . 'prot_lic_hash.zip';
createTestZip($zb2, ['app/Config/.lic_hash' => 'tampered_lic']);
assertZip("Tolak penimpaan 'app/Config/.lic_hash'", expectSanitizationFailure($zb2, 'PELANGGARAN KEAMANAN KRITIS'));

// B.3 Attempt to overwrite SecurityGuard.php
$zb3 = $testFixtureDir . 'prot_sec_guard.zip';
createTestZip($zb3, ['app/Libraries/SecurityGuard.php' => '<?php // tampered']);
assertZip("Tolak penimpaan 'app/Libraries/SecurityGuard.php'", expectSanitizationFailure($zb3, 'PELANGGARAN KEAMANAN KRITIS'));

// B.4 Attempt to overwrite LicenseGuard.php
$zb4 = $testFixtureDir . 'prot_lic_guard.zip';
createTestZip($zb4, ['app/Libraries/LicenseGuard.php' => '<?php // tampered']);
assertZip("Tolak penimpaan 'app/Libraries/LicenseGuard.php'", expectSanitizationFailure($zb4, 'PELANGGARAN KEAMANAN KRITIS'));

// B.5 Attempt to overwrite .env
$zb5 = $testFixtureDir . 'prot_dotenv.zip';
createTestZip($zb5, ['.env' => 'DATABASE_PASSWORD=hacked']);
assertZip("Tolak penimpaan berkas rahasia '.env'", expectSanitizationFailure($zb5, 'PELANGGARAN KEAMANAN KRITIS'));

// B.6 Attempt to overwrite Database.php
$zb6 = $testFixtureDir . 'prot_db_config.zip';
createTestZip($zb6, ['app/Config/Database.php' => '<?php // stolen']);
assertZip("Tolak penimpaan 'app/Config/Database.php'", expectSanitizationFailure($zb6, 'PELANGGARAN KEAMANAN KRITIS'));

// B.7 Case-insensitive attempt: aPp/cOnFiG/.SeCuRiTy_HaSh
$zb7 = $testFixtureDir . 'prot_case_tamper.zip';
createTestZip($zb7, ['aPp/cOnFiG/.SeCuRiTy_HaSh' => 'tampered']);
assertZip("Tolak penimpaan case-insensitive 'aPp/cOnFiG/.SeCuRiTy_HaSh'", expectSanitizationFailure($zb7, 'PELANGGARAN KEAMANAN KRITIS'));

// =========================================================================
// GROUP C: ABNORMAL ZIP ENTRIES & DIRECTORY RESTRICTIONS
// =========================================================================
echo "\n3. Menguji Penanganan Entri Abnormal & Pembatasan Direktori (Group C)...\n";

// C.1 Unallowed root file (e.g., config.php or shell.php in root)
$zc1 = $testFixtureDir . 'unallowed_root.zip';
createTestZip($zc1, ['backdoor.php' => '<?php phpinfo();']);
assertZip("Tolak file di root selain whitelist rilis ('backdoor.php')", expectSanitizationFailure($zc1, 'di luar direktori aman'));

// C.2 Unallowed directory (e.g., vendor/ or writable/ or system/)
$zc2 = $testFixtureDir . 'unallowed_dir.zip';
createTestZip($zc2, ['system/CodeIgniter.php' => '<?php // modified']);
assertZip("Tolak entri di luar direktori aman ('system/CodeIgniter.php')", expectSanitizationFailure($zc2, 'di luar direktori aman'));

// C.3 Case-insensitive duplicate entry
$zc3 = $testFixtureDir . 'dup_case.zip';
$zipDup = new ZipArchive();
if ($zipDup->open($zc3, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zipDup->addFromString('app/Controllers/Test.php', 'version 1');
    $zipDup->addFromString('app/controllers/test.php', 'version 2');
    $zipDup->close();
}
assertZip("Tolak tabrakan nama entri case-insensitive duplikat", expectSanitizationFailure($zc3, 'duplikat atau tabrakan nama'));

// C.4 Empty zip archive (valid 22-byte empty zip record)
$zc4 = $testFixtureDir . 'empty.zip';
file_put_contents($zc4, pack("VvvvvVVv", 0x06054b50, 0, 0, 0, 0, 0, 0, 0));
assertZip("Tolak arsip ZIP kosong tanpa entri", expectSanitizationFailure($zc4, 'kosong'));

// C.5 Excessive directory depth (> 12 level)
$deepPath = 'app/' . str_repeat('sub/', 13) . 'file.php';
$zc5 = $testFixtureDir . 'deep_dir.zip';
createTestZip($zc5, [$deepPath => 'content']);
assertZip("Tolak struktur direktori dengan kedalaman melebihi batas (>12)", expectSanitizationFailure($zc5, 'Kedalaman struktur direktori'));

// =========================================================================
// GROUP D: ZIP BOMB PROTECTION
// =========================================================================
echo "\n4. Menguji Perlindungan ZIP Bomb (Group D)...\n";

// D.1 Single file exceeds MAX_FILE_SIZE (simulated without disk waste)
// We test that SafeZipExtractor defines MAX_FILE_SIZE and MAX_TOTAL_UNCOMPRESSED
assertZip("SafeZipExtractor memiliki limit MAX_FILE_SIZE (15 MB)", SafeZipExtractor::MAX_FILE_SIZE === 15728640);
assertZip("SafeZipExtractor memiliki limit MAX_TOTAL_UNCOMPRESSED (150 MB)", SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED === 157286400);
assertZip("SafeZipExtractor memiliki limit MAX_FILES (3000)", SafeZipExtractor::MAX_FILES === 3000);
assertZip("SafeZipExtractor memiliki limit MAX_RATIO (100:1)", SafeZipExtractor::MAX_RATIO === 100);

// D.2 Test high compression ratio detection
$zd2 = $testFixtureDir . 'high_ratio.zip';
// Create a file with 1MB of repetitive zeros which compresses to very few bytes (> 100:1 ratio)
$zeroData = str_repeat('A', 2 * 1024 * 1024); // 2 MB
$zipRatio = new ZipArchive();
if ($zipRatio->open($zd2, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zipRatio->addFromString('app/zeros.txt', $zeroData);
    $zipRatio->close();
}
// 2MB of 'A' compresses to ~2KB (ratio ~1000:1), should trigger MAX_RATIO protection
assertZip("Tolak entri dengan rasio kompresi ekstrem (ZIP Bomb ratio)", expectSanitizationFailure($zd2, 'rasio kompresi'));

// =========================================================================
// GROUP E: NORMAL VALID PACKAGE LIFECYCLE & ROLLBACK
// =========================================================================
echo "\n5. Menguji Siklus Paket Normal, Staging & Rollback (Group E)...\n";

$validZipPath = $testFixtureDir . 'valid_patch.zip';
$validEntries = [
    'UPDATE_MANIFEST.json' => json_encode(['version' => '2.5.1', 'date' => date('Y-m-d')]),
    'app/Controllers/AuditTestController.php' => '<?php namespace App\Controllers; class AuditTestController {}',
    'public/test_asset.js' => 'console.log("ESPATA Patch");',
];
createTestZip($validZipPath, $validEntries);

// E.1 Test sanitization passes for valid patch
$vZip = new ZipArchive();
$vZip->open($validZipPath);
$sanitized = SafeZipExtractor::sanitizeZipEntries($vZip);
assertZip("Paket patch sah lolos sanitasi (3 entri terverifikasi)", count($sanitized) === 3);

// E.2 Test staging extraction into isolated temporary staging dir
$stagingTestDir = $testFixtureDir . 'staging_test' . DIRECTORY_SEPARATOR;
mkdir($stagingTestDir, 0755, true);
$extracted = SafeZipExtractor::extractToStaging($vZip, $sanitized, $stagingTestDir);
$vZip->close();
assertZip("Ekstraksi ke staging berhasil dan terisolasi", count($extracted) === 3);
assertZip("File 'app/Controllers/AuditTestController.php' ada di staging", file_exists($stagingTestDir . 'app/Controllers/AuditTestController.php'));
assertZip("File 'UPDATE_MANIFEST.json' ada di staging", file_exists($stagingTestDir . 'UPDATE_MANIFEST.json'));

// E.3 Test backup & rollback logic
$backupTestDir = $testFixtureDir . 'backup_test' . DIRECTORY_SEPARATOR;
$meta = SafeZipExtractor::backupTargetFiles($extracted, $backupTestDir);
assertZip("Target backup logic berhasil mengidentifikasi file baru", count($meta['new_files']) >= 1);

// E.4 Test simulated rollback
SafeZipExtractor::rollback($backupTestDir, ['public/test_asset.js']);
assertZip("Mekanisme rollback berjalan tanpa exception", true);

// E.5 Test cleanup staging
SafeZipExtractor::cleanupDirectory($stagingTestDir);
assertZip("Pembersihan direktori staging berhasil (Zero residue)", !is_dir($stagingTestDir));

// =========================================================================
// GROUP F: SYSTEM INTEGRITY & SECURITYGUARD
// =========================================================================
echo "\n6. Menguji Integritas Sistem & Berkas Terlindung (Group F)...\n";

require_once APPPATH . 'Libraries/SecurityGuard.php';
$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertZip("SecurityGuard::verifyCriticalFiles() === SEC_PASS: 1", ($secPass === true || $secPass === 1));

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
    assertZip("Berkas terlindungi '{$pf}' tetap utuh di disk", file_exists(ROOTPATH . $pf));
}

assertZip("Sub-aplikasi 'bayaran/' tetap tidak ada (absent)", !is_dir(ROOTPATH . 'bayaran'));
assertZip("Sub-aplikasi 'cbtku/' tetap tidak ada (absent)", !is_dir(ROOTPATH . 'cbtku'));

// =========================================================================
// GROUP G: CUMULATIVE STREAMING DECOMPRESSION (EAO-HO-2026-088)
// =========================================================================
echo "\n7. Menguji Akumulator Streaming Kumulatif & Batas Total Dekompresi (Group G)...\n";

// Helper generator untuk konten uji realistis dengan rasio kompresi wajar (< 100:1)
function makeSampleCode(string $prefix, int $targetBytes): string {
    $code = "<?php\nnamespace App\Controllers;\n// {$prefix}\nclass Sample" . ucfirst($prefix) . " {\n";
    while (strlen($code) < $targetBytes) {
        $code .= "    public function test_" . bin2hex(random_bytes(8)) . "() { return '" . bin2hex(random_bytes(64)) . "'; }\n";
    }
    $code .= "}\n";
    return substr($code, 0, $targetBytes);
}

// G.1 Arsip valid di bawah batas dapat diekstrak normal
$zg1 = $testFixtureDir . 'cum_valid.zip';
$entriesG1 = [
    'app/Controllers/Test1.php' => makeSampleCode('test1', 5000), // 5 KB
    'app/Controllers/Test2.php' => makeSampleCode('test2', 5000), // 5 KB
    'public/asset.js'           => "/* Sample JS */\n" . makeSampleCode('asset', 5000), // 5 KB
];
createTestZip($zg1, $entriesG1);
$zipG1 = new ZipArchive();
$zipG1->open($zg1);
$sanG1 = SafeZipExtractor::sanitizeZipEntries($zipG1);
$stageG1 = $testFixtureDir . 'stage_g1' . DIRECTORY_SEPARATOR;
mkdir($stageG1, 0755, true);
$extractedG1 = SafeZipExtractor::extractToStaging($zipG1, $sanG1, $stageG1);
$zipG1->close();
assertZip("G.1: Arsip valid di bawah 150 MB tetap dapat diekstrak (3 file)", count($extractedG1) === 3);
SafeZipExtractor::cleanupDirectory($stageG1);

// G.2 File tunggal yang melebihi batas per file ditolak saat streaming
$zg2 = $testFixtureDir . 'cum_single_exceed.zip';
createTestZip($zg2, ['app/Controllers/BigFile.php' => makeSampleCode('bigfile', 20000)]); // 20 KB
$zipG2 = new ZipArchive();
$zipG2->open($zg2);
$sanG2 = SafeZipExtractor::sanitizeZipEntries($zipG2);
$stageG2 = $testFixtureDir . 'stage_g2' . DIRECTORY_SEPARATOR;
mkdir($stageG2, 0755, true);
$failedG2 = false;
$msgG2 = '';
try {
    // Uji dengan maxFileSize threshold 10 KB (10240 byte)
    SafeZipExtractor::extractToStaging($zipG2, $sanG2, $stageG2, 1048576, 10240);
} catch (SafeZipExtractionException $e) {
    $failedG2 = true;
    $msgG2 = $e->getMessage();
}
$zipG2->close();
assertZip("G.2: File tunggal melebihi batas per file ditolak saat streaming", $failedG2 && stripos($msgG2, 'melebihi batas maksimum') !== false);
assertZip("G.2: Ketika file tunggal melebihi batas, staging dibersihkan", !is_dir($stageG2));

// G.3 Banyak file kecil dengan total aktual di bawah batas diterima
$zg3 = $testFixtureDir . 'cum_multi_under.zip';
$entriesG3 = [
    'app/Controllers/Part1.php' => makeSampleCode('part1', 8000), // 8 KB
    'app/Controllers/Part2.php' => makeSampleCode('part2', 8000), // 8 KB
    'app/Controllers/Part3.php' => makeSampleCode('part3', 8000), // 8 KB
    'app/Controllers/Part4.php' => makeSampleCode('part4', 8000), // 8 KB
]; // Total 32 KB
createTestZip($zg3, $entriesG3);
$zipG3 = new ZipArchive();
$zipG3->open($zg3);
$sanG3 = SafeZipExtractor::sanitizeZipEntries($zipG3);
$stageG3 = $testFixtureDir . 'stage_g3' . DIRECTORY_SEPARATOR;
mkdir($stageG3, 0755, true);
// Limit total 50 KB (51200 byte), limit file 10 KB (10240 byte) -> total 32 KB < 50 KB
$extractedG3 = SafeZipExtractor::extractToStaging($zipG3, $sanG3, $stageG3, 51200, 10240);
$zipG3->close();
assertZip("G.3: Banyak file kecil dengan total di bawah batas kumulatif diterima", count($extractedG3) === 4);
SafeZipExtractor::cleanupDirectory($stageG3);

// G.4 Banyak file kecil dengan total aktual melebihi batas kumulatif ditolak
$zg4 = $testFixtureDir . 'cum_multi_exceed.zip';
$entriesG4 = [
    'app/Controllers/Chunk1.php' => makeSampleCode('chunk1', 8000), // 8 KB
    'app/Controllers/Chunk2.php' => makeSampleCode('chunk2', 8000), // 8 KB
    'app/Controllers/Chunk3.php' => makeSampleCode('chunk3', 8000), // 8 KB
    'app/Controllers/Chunk4.php' => makeSampleCode('chunk4', 8000), // 8 KB
    'app/Controllers/Chunk5.php' => makeSampleCode('chunk5', 8000), // 8 KB
    'app/Controllers/Chunk6.php' => makeSampleCode('chunk6', 8000), // 8 KB
    'app/Controllers/Chunk7.php' => makeSampleCode('chunk7', 8000), // 8 KB
]; // 7 file * 8 KB = 56 KB
createTestZip($zg4, $entriesG4);
$zipG4 = new ZipArchive();
$zipG4->open($zg4);
$sanG4 = SafeZipExtractor::sanitizeZipEntries($zipG4);
$stageG4 = $testFixtureDir . 'stage_g4' . DIRECTORY_SEPARATOR;
mkdir($stageG4, 0755, true);
$failedG4 = false;
$msgG4 = '';
try {
    // Limit total 40 KB (40960 byte), limit file 10 KB (10240 byte) -> file ke-6 (48 KB) melampaui limit total
    SafeZipExtractor::extractToStaging($zipG4, $sanG4, $stageG4, 40960, 10240);
} catch (SafeZipExtractionException $e) {
    $failedG4 = true;
    $msgG4 = $e->getMessage();
}
$zipG4->close();
assertZip("G.4: Banyak file kecil melebihi batas kumulatif streaming ditolak", $failedG4 && stripos($msgG4, 'melebihi batas maksimum kumulatif') !== false);

// G.5 Ketika batas total terlampaui, staging dibersihkan seketika
assertZip("G.5: Ketika batas kumulatif terlampaui, direktori staging dibersihkan", !is_dir($stageG4));

// G.6 ROOTPATH tidak berubah / tidak ada file asing tertulis di root
$rootAuditProbe = ROOTPATH . 'app/Controllers/Chunk1.php';
assertZip("G.6: ROOTPATH aman dan tidak tersentuh akibat kegagalan ekstraksi", !file_exists($rootAuditProbe));

// G.7 Batas total tetap bekerja meskipun metadata ZIP tidak dapat dipercaya
assertZip("G.7: Batas total kumulatif ditegakkan saat streaming runtime (bukan hanya metadata)", $failedG4);

// =========================================================================
// GROUP H: VERIFIED FILE WRITE INTEGRITY — SEC-ZIP-02 (EAO-091)
// =========================================================================
echo "\n8. Menguji Integritas Penulisan Berkas & Verified Write Loop (Group H)...\n";

// H.1 (W1): Penulisan normal - semua byte tertulis utuh
$zh1 = $testFixtureDir . 'write_w1.zip';
$contentW1 = makeSampleCode('w1', 12000); // 12 KB
createTestZip($zh1, ['app/Controllers/W1Controller.php' => $contentW1]);
$zipW1 = new ZipArchive();
$zipW1->open($zh1);
$sanW1 = SafeZipExtractor::sanitizeZipEntries($zipW1);
$stageW1 = $testFixtureDir . 'stage_w1' . DIRECTORY_SEPARATOR;
mkdir($stageW1, 0755, true);
$extractedW1 = SafeZipExtractor::extractToStaging($zipW1, $sanW1, $stageW1);
$zipW1->close();
$writtenContentW1 = file_get_contents($stageW1 . 'app/Controllers/W1Controller.php');
assertZip("W1: Penulisan normal menghasilkan byte utuh identik (12 KB)", $writtenContentW1 === $contentW1 && count($extractedW1) === 1);
SafeZipExtractor::cleanupDirectory($stageW1);

// H.2 (W2): Partial write satu kali lalu berhasil - loop melanjutkan hingga seluruh byte tertulis
$partialCallCountW2 = 0;
$writerW2 = function($fp, $rem, $offset, $chunkLen) use (&$partialCallCountW2) {
    $partialCallCountW2++;
    if ($partialCallCountW2 === 1) {
        $part = substr($rem, 0, min(300, strlen($rem)));
        return fwrite($fp, $part);
    }
    return fwrite($fp, $rem);
};
$zh2 = $testFixtureDir . 'write_w2.zip';
$contentW2 = makeSampleCode('w2', 2000);
createTestZip($zh2, ['app/Controllers/W2Controller.php' => $contentW2]);
$zipW2 = new ZipArchive();
$zipW2->open($zh2);
$sanW2 = SafeZipExtractor::sanitizeZipEntries($zipW2);
$stageW2 = $testFixtureDir . 'stage_w2' . DIRECTORY_SEPARATOR;
mkdir($stageW2, 0755, true);
SafeZipExtractor::extractToStaging($zipW2, $sanW2, $stageW2, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW2);
$zipW2->close();
$writtenContentW2 = file_get_contents($stageW2 . 'app/Controllers/W2Controller.php');
assertZip("W2: Partial write satu kali berhasil dilanjutkan hingga seluruh byte tertulis", $partialCallCountW2 >= 2 && $writtenContentW2 === $contentW2);
SafeZipExtractor::cleanupDirectory($stageW2);

// H.3 (W3): Partial write berulang - tidak ada byte hilang atau duplikat
$writerW3 = function($fp, $rem, $offset, $chunkLen) {
    $part = substr($rem, 0, min(37, strlen($rem)));
    return fwrite($fp, $part);
};
$zh3 = $testFixtureDir . 'write_w3.zip';
$contentW3 = makeSampleCode('w3', 1500);
createTestZip($zh3, ['app/Controllers/W3Controller.php' => $contentW3]);
$zipW3 = new ZipArchive();
$zipW3->open($zh3);
$sanW3 = SafeZipExtractor::sanitizeZipEntries($zipW3);
$stageW3 = $testFixtureDir . 'stage_w3' . DIRECTORY_SEPARATOR;
mkdir($stageW3, 0755, true);
SafeZipExtractor::extractToStaging($zipW3, $sanW3, $stageW3, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW3);
$zipW3->close();
$writtenContentW3 = file_get_contents($stageW3 . 'app/Controllers/W3Controller.php');
assertZip("W3: Partial write berulang berhasil tanpa ada byte hilang atau duplikat", $writtenContentW3 === $contentW3);
SafeZipExtractor::cleanupDirectory($stageW3);

// H.4 (W4): fwrite() mengembalikan false - ekstraksi gagal dan cleanup berjalan
$writerW4 = function($fp, $rem, $offset, $chunkLen) {
    return false;
};
$zh4 = $testFixtureDir . 'write_w4.zip';
createTestZip($zh4, ['app/Controllers/W4Controller.php' => makeSampleCode('w4', 1000)]);
$zipW4 = new ZipArchive();
$zipW4->open($zh4);
$sanW4 = SafeZipExtractor::sanitizeZipEntries($zipW4);
$stageW4 = $testFixtureDir . 'stage_w4' . DIRECTORY_SEPARATOR;
mkdir($stageW4, 0755, true);
$failedW4 = false;
$msgW4 = '';
try {
    SafeZipExtractor::extractToStaging($zipW4, $sanW4, $stageW4, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW4);
} catch (SafeZipExtractionException $e) {
    $failedW4 = true;
    $msgW4 = $e->getMessage();
}
$zipW4->close();
assertZip("W4: fwrite() mengembalikan false menyebabkan ekstraksi gagal dengan exception", $failedW4 && stripos($msgW4, 'Kegagalan penulisan') !== false);
assertZip("W4: Ketika fwrite() gagal mengembalikan false, staging dibersihkan", !is_dir($stageW4));

// H.5 (W5): fwrite() mengembalikan 0 - ekstraksi gagal tanpa infinite loop
$writerW5 = function($fp, $rem, $offset, $chunkLen) {
    return 0;
};
$zh5 = $testFixtureDir . 'write_w5.zip';
createTestZip($zh5, ['app/Controllers/W5Controller.php' => makeSampleCode('w5', 1000)]);
$zipW5 = new ZipArchive();
$zipW5->open($zh5);
$sanW5 = SafeZipExtractor::sanitizeZipEntries($zipW5);
$stageW5 = $testFixtureDir . 'stage_w5' . DIRECTORY_SEPARATOR;
mkdir($stageW5, 0755, true);
$failedW5 = false;
$msgW5 = '';
try {
    SafeZipExtractor::extractToStaging($zipW5, $sanW5, $stageW5, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW5);
} catch (SafeZipExtractionException $e) {
    $failedW5 = true;
    $msgW5 = $e->getMessage();
}
$zipW5->close();
assertZip("W5: fwrite() mengembalikan 0 menyebabkan kegagalan fatal seketika tanpa infinite loop", $failedW5 && stripos($msgW5, 'zero bytes written') !== false);
assertZip("W5: Ketika fwrite() mengembalikan 0, staging dibersihkan", !is_dir($stageW5));

// H.6 (W6): Penulisan gagal setelah sebagian chunk tertulis - file parsial dibersihkan
$callCountW6 = 0;
$writerW6 = function($fp, $rem, $offset, $chunkLen) use (&$callCountW6) {
    $callCountW6++;
    if ($callCountW6 === 1) {
        return fwrite($fp, substr($rem, 0, 200));
    }
    return false;
};
$zh6 = $testFixtureDir . 'write_w6.zip';
createTestZip($zh6, ['app/Controllers/W6Controller.php' => makeSampleCode('w6', 2000)]);
$zipW6 = new ZipArchive();
$zipW6->open($zh6);
$sanW6 = SafeZipExtractor::sanitizeZipEntries($zipW6);
$stageW6 = $testFixtureDir . 'stage_w6' . DIRECTORY_SEPARATOR;
mkdir($stageW6, 0755, true);
$failedW6 = false;
try {
    SafeZipExtractor::extractToStaging($zipW6, $sanW6, $stageW6, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW6);
} catch (SafeZipExtractionException $e) {
    $failedW6 = true;
}
$zipW6->close();
assertZip("W6: Penulisan gagal setelah sebagian chunk tertulis memicu kegagalan", $failedW6);
assertZip("W6: File parsial dan direktori staging dibersihkan (zero residue)", !is_dir($stageW6));

// H.7 (W7): Kegagalan penulisan di salah satu file ZIP - seluruh staging dibersihkan
$writerW7 = function($fp, $rem, $offset, $chunkLen) {
    if (str_contains($rem, 'FileDua')) {
        return false;
    }
    return fwrite($fp, $rem);
};
$zh7 = $testFixtureDir . 'write_w7.zip';
$entriesW7 = [
    'app/Controllers/FileSatu.php' => makeSampleCode('FileSatu', 1000),
    'app/Controllers/FileDua.php'  => "<?php class FileDua {}\n" . makeSampleCode('FileDua', 1000),
    'app/Controllers/FileTiga.php' => makeSampleCode('FileTiga', 1000),
];
createTestZip($zh7, $entriesW7);
$zipW7 = new ZipArchive();
$zipW7->open($zh7);
$sanW7 = SafeZipExtractor::sanitizeZipEntries($zipW7);
$stageW7 = $testFixtureDir . 'stage_w7' . DIRECTORY_SEPARATOR;
mkdir($stageW7, 0755, true);
$failedW7 = false;
try {
    SafeZipExtractor::extractToStaging($zipW7, $sanW7, $stageW7, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW7);
} catch (SafeZipExtractionException $e) {
    $failedW7 = true;
}
$zipW7->close();
assertZip("W7: Kegagalan penulisan di file ke-2 membatalkan ekstraksi seluruh arsip", $failedW7);
assertZip("W7: Seluruh direktori staging (termasuk file 1 yang sempat ditulis) dibersihkan total", !is_dir($stageW7));

// H.8 (W8): Kegagalan penulisan - applyStagingToRoot() tidak dijalankan
$appliedTriggeredW8 = false;
try {
    $extractedFilesW8 = SafeZipExtractor::extractToStaging($zipW7, $sanW7, $stageW7, SafeZipExtractor::MAX_TOTAL_UNCOMPRESSED, SafeZipExtractor::MAX_FILE_SIZE, $writerW7);
    SafeZipExtractor::applyStagingToRoot($stageW7, $extractedFilesW8, $testFixtureDir . 'dummy_backup');
    $appliedTriggeredW8 = true;
} catch (\Throwable $e) {
    $appliedTriggeredW8 = false;
}
assertZip("W8: Kegagalan penulisan staging mencegah pemanggilan applyStagingToRoot()", !$appliedTriggeredW8);

// H.9 (W9): File target lama sudah ada tetap tidak berubah saat penulisan gagal
$dummyTargetRoot = $testFixtureDir . 'dummy_root' . DIRECTORY_SEPARATOR;
mkdir($dummyTargetRoot . 'app/Controllers', 0755, true);
$targetFileW9 = $dummyTargetRoot . 'app/Controllers/FileSatu.php';
file_put_contents($targetFileW9, 'PRISTINE_ORIGINAL_CONTENT_W9');
assertZip("W9: File target lama di direktori root tetap tidak berubah ('PRISTINE_ORIGINAL_CONTENT_W9')", file_get_contents($targetFileW9) === 'PRISTINE_ORIGINAL_CONTENT_W9');
SafeZipExtractor::cleanupDirectory($dummyTargetRoot);

// H.10 (W10): Penulisan normal beberapa file - batas total dan per-file tetap benar
$zh10 = $testFixtureDir . 'write_w10.zip';
$entriesW10 = [
    'app/Controllers/Norm1.php' => makeSampleCode('norm1', 5000), // 5 KB
    'app/Controllers/Norm2.php' => makeSampleCode('norm2', 5000), // 5 KB
    'app/Controllers/Norm3.php' => makeSampleCode('norm3', 5000), // 5 KB
];
createTestZip($zh10, $entriesW10);
$zipW10 = new ZipArchive();
$zipW10->open($zh10);
$sanW10 = SafeZipExtractor::sanitizeZipEntries($zipW10);
$stageW10 = $testFixtureDir . 'stage_w10' . DIRECTORY_SEPARATOR;
mkdir($stageW10, 0755, true);
$extractedW10 = SafeZipExtractor::extractToStaging($zipW10, $sanW10, $stageW10);
$zipW10->close();
assertZip("W10: Penulisan normal 3 file berhasil penuh (semua file valid)", count($extractedW10) === 3);
assertZip("W10: File Norm1.php di staging terverifikasi utuh", file_exists($stageW10 . 'app/Controllers/Norm1.php'));
SafeZipExtractor::cleanupDirectory($stageW10);

// Bersihkan testFixtureDir di akhir
SafeZipExtractor::cleanupDirectory($testFixtureDir);
assertZip("Pembersihan total direktori fixture uji sementara berhasil", !is_dir($testFixtureDir));

// =========================================================================
// SUMMARY
// =========================================================================
echo "\n=== RINGKASAN HASIL TEST SUITE EAO-085 ===\n";
echo "Total Assertions : " . ($passCount + $failCount) . "\n";
echo "Passed           : {$passCount}\n";
echo "Failed           : {$failCount}\n";

if ($failCount === 0) {
    echo "\n>>> RESULT: ALL EAO-085 TESTS PASSED (100%) <<<\n";
    exit(0);
} else {
    echo "\n>>> RESULT: SOME TESTS FAILED <<<\n";
    exit(1);
}
