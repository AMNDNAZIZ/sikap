<?php
/**
 * Test Suite: EAO-HO-2026-062 Runtime Remediation Verification
 * Memverifikasi perbaikan Defect A (Frontend Infinite Loading) dan Defect B (Knowledge Routing Hijacking)
 */

if (!function_exists('site_url')) {
    function site_url($u = '') {
        return 'http://localhost/siakad/' . ltrim($u, '/');
    }
}

require_once __DIR__ . '/../app/Libraries/EspataApplicationKnowledge.php';
require_once __DIR__ . '/../app/Libraries/EspataFeatureKnowledge.php';
require_once __DIR__ . '/../app/Libraries/EakbRetrievalEngine.php';
require_once __DIR__ . '/../app/Libraries/EakbContextAssembler.php';
require_once __DIR__ . '/../app/Libraries/EakbFeatureGraph.php';
require_once __DIR__ . '/../app/Libraries/EakbCatalogValidator.php';
require_once __DIR__ . '/../app/Libraries/EakbDisambiguationResolver.php';
require_once __DIR__ . '/../app/Services/Ai/Intent/IntentClassifier.php';

use App\Libraries\EspataFeatureKnowledge;
use App\Libraries\EakbRetrievalEngine;
use App\Libraries\EakbDisambiguationResolver;
use App\Services\Ai\Intent\IntentClassifier;

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

echo "====================================================================\n";
echo "TEST SUITE EAO-HO-2026-062: AI COPILOT RUNTIME REMEDIATION\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// BAGIAN A: VERIFIKASI STATIS FRONTEND (command_palette.php)
// -----------------------------------------------------------------------------
echo "--- 1. VERIFIKASI FRONTEND DEFECT A (command_palette.php) ---\n";
$cpContent = file_get_contents(__DIR__ . '/../app/Views/layouts/partials/command_palette.php');

assertCondition("Frontend: Memiliki AbortController", strpos($cpContent, 'new AbortController()') !== false);
assertCondition("Frontend: Memiliki batas timeout 25 detik", strpos($cpContent, '25000') !== false);
assertCondition("Frontend: Menggunakan abortController.signal pada fetch", strpos($cpContent, 'signal: abortController.signal') !== false);
assertCondition("Frontend: Fungsi cleanupLoadingAndUi selalu membersihkan loading spinner", strpos($cpContent, 'function cleanupLoadingAndUi()') !== false && strpos($cpContent, 'if (loadingEl) loadingEl.remove();') !== false);
assertCondition("Frontend: Menangani AbortError (Timeout)", strpos($cpContent, "err.name === 'AbortError'") !== false);
assertCondition("Frontend: Menangani HTTP 403 Forbidden / CSRF Expired", strpos($cpContent, "err.message === 'FORBIDDEN'") !== false);
assertCondition("Frontend: Menangani HTTP 500+ Server Error", strpos($cpContent, "err.message === 'SERVER_ERROR'") !== false);
assertCondition("Frontend: Menangani INVALID_JSON", strpos($cpContent, "err.message === 'INVALID_JSON'") !== false);
assertCondition("Frontend: Menangani EMPTY_RESPONSE", strpos($cpContent, "err.message === 'EMPTY_RESPONSE'") !== false);
assertCondition("Frontend: Tombol submit dibuka kembali pada blok pemulihan", strpos($cpContent, 'AsisCopilot.unlockUi') !== false);
// Cek apakah di dalam blok .catch(...) terdapat rekursi atau retry
preg_match('/\.catch\(err\s*=>\s*\{([\s\S]*?)\}\s*\);/', $cpContent, $catchMatches);
$catchBody = $catchMatches[1] ?? '';
$hasRetryInCatch = (strpos($catchBody, 'submitChatMessage') !== false || strpos($catchBody, 'fetch(') !== false);
assertCondition("Frontend: Tidak ada retry otomatis di dalam blok penanganan galat", !$hasRetryInCatch);

// -----------------------------------------------------------------------------
// BAGIAN B: VERIFIKASI DETERMINISTIK KNOWLEDGE ROUTING (6 KASUS MANDAT)
// -----------------------------------------------------------------------------
echo "\n--- 2. VERIFIKASI KNOWLEDGE ROUTING DEFECT B (6 KASUS MANDAT) ---\n";

// B.1 Kasus 1: "Bagaimana cara membuat materi di LMS?" -> Target: feat_lms_materials_seg
echo "\n[Kasus 1] 'Bagaimana cara membuat materi di LMS?'\n";
$q1 = 'Bagaimana cara membuat materi di LMS?';
$g1_guru = EspataFeatureKnowledge::findGuide($q1, 3);
$g1_admin = EspataFeatureKnowledge::findGuide($q1, 1);
$g1_kepsek = EspataFeatureKnowledge::findGuide($q1, 2);

assertCondition("Kasus 1 (Guru): Mengarah ke feat_lms_materials_seg", ($g1_guru['feature_id'] ?? '') === 'feat_lms_materials_seg');
assertCondition("Kasus 1 (Admin): Mengarah ke feat_lms_materials_seg", ($g1_admin['feature_id'] ?? '') === 'feat_lms_materials_seg');
assertCondition("Kasus 1 (Kepala Sekolah): Ditolak/NULL sesuai RBAC fail-closed", $g1_kepsek === null);

// B.2 Kasus 2: "Bagaimana cara mengelola materi pembelajaran di LMS?" -> Target: feat_lms_materials_seg
echo "\n[Kasus 2] 'Bagaimana cara mengelola materi pembelajaran di LMS?'\n";
$q2 = 'Bagaimana cara mengelola materi pembelajaran di LMS?';
$g2_guru = EspataFeatureKnowledge::findGuide($q2, 3);
$g2_admin = EspataFeatureKnowledge::findGuide($q2, 1);
$g2_kepsek = EspataFeatureKnowledge::findGuide($q2, 2);

assertCondition("Kasus 2 (Guru): Mengarah ke feat_lms_materials_seg", ($g2_guru['feature_id'] ?? '') === 'feat_lms_materials_seg');
assertCondition("Kasus 2 (Admin): Mengarah ke feat_lms_materials_seg", ($g2_admin['feature_id'] ?? '') === 'feat_lms_materials_seg');
assertCondition("Kasus 2 (Kepala Sekolah): Ditolak/NULL sesuai RBAC fail-closed", $g2_kepsek === null);

// B.3 Kasus 3: "Bagaimana cara membuat Modul Ajar?" -> Target: Panduan Modul Ajar
echo "\n[Kasus 3] 'Bagaimana cara membuat Modul Ajar?'\n";
$q3 = 'Bagaimana cara membuat Modul Ajar?';
$g3_guru = EspataFeatureKnowledge::findGuide($q3, 3);
$g3_admin = EspataFeatureKnowledge::findGuide($q3, 1);
$g3_kepsek = EspataFeatureKnowledge::findGuide($q3, 2);

assertCondition("Kasus 3 (Guru): Mengarah ke Panduan Modul Ajar", ($g3_guru['id'] ?? '') === 'guide_modul_ajar' && ($g3_guru['feature_id'] ?? '') === 'feat_cur_modul_ajar');
assertCondition("Kasus 3 (Admin): Mengarah ke Panduan Modul Ajar", ($g3_admin['id'] ?? '') === 'guide_modul_ajar');
assertCondition("Kasus 3 (Kepala Sekolah): Ditolak/NULL sesuai RBAC panduan", $g3_kepsek === null);

// B.4 Kasus 4: "Bagaimana cara melihat rekap absensi guru?" -> Target: Panduan rekap absensi guru
echo "\n[Kasus 4] 'Bagaimana cara melihat rekap absensi guru?'\n";
$q4 = 'Bagaimana cara melihat rekap absensi guru?';
$g4_admin = EspataFeatureKnowledge::findGuide($q4, 1);
$g4_kepsek = EspataFeatureKnowledge::findGuide($q4, 2);
$g4_guru = EspataFeatureKnowledge::findGuide($q4, 3);

assertCondition("Kasus 4 (Admin): Mengarah ke Panduan Rekap Absensi Guru", ($g4_admin['id'] ?? '') === 'guide_rekap_absensi_guru' && ($g4_admin['feature_id'] ?? '') === 'feat_adm_teacher_attendance');
assertCondition("Kasus 4 (Kepala Sekolah): Mengarah ke Panduan Rekap Absensi Guru", ($g4_kepsek['id'] ?? '') === 'guide_rekap_absensi_guru');
assertCondition("Kasus 4 (Guru): Ditolak/NULL (Guru tidak boleh memantau absensi rekan sejawat)", $g4_guru === null);

// B.5 Kasus 5: "Apa perbedaan ATP dan Modul Ajar?" -> Target: Pengetahuan pendidikan yang sesuai
echo "\n[Kasus 5] 'Apa perbedaan ATP dan Modul Ajar?'\n";
$q5 = 'Apa perbedaan ATP dan Modul Ajar?';
$hasGuideMarker5 = (bool) preg_match('/\b(cara|panduan|bagaimana\s*cara|tutorial|petunjuk|langkah-langkah)\b/i', $q5);
$isConceptual5 = (bool) preg_match('/^(?:apa\s+(?:itu|perbedaan|arti|definisi|tujuan|maksud)|bagaimana\s+konsep|jelaskan\s+konsep|mengapa\s+perlu|apa\s+hubungannya)\b/i', trim($q5));
$hasAppMarker5 = (bool) preg_match('/\b(espata|siakad|aplikasi|sistem|di\s+web|tombol|klik|menu|fitur|halaman)\b/i', $q5);

assertCondition("Kasus 5: Tidak memicu guide card legasi", !$hasGuideMarker5);
assertCondition("Kasus 5: Terdeteksi sebagai kueri pedagogis konseptual", $isConceptual5 && !$hasAppMarker5);

// B.6 Kasus 6: "Bagaimana cara membuat materi pembelajaran?" -> Target: Klarifikasi jika fitur belum dapat ditentukan secara meyakinkan
echo "\n[Kasus 6] 'Bagaimana cara membuat materi pembelajaran?'\n";
$q6 = 'Bagaimana cara membuat materi pembelajaran?';
$hasGuideMarker6 = (bool) preg_match('/\b(cara|panduan|bagaimana\s*cara|tutorial|petunjuk|langkah-langkah)\b/i', $q6);
$isAmbiguousMateri6 = (bool) preg_match('/\bmateri\s*(?:pembelajaran|ajar)?\b/i', $q6)
    && !preg_match('/\b(lms|elearning|portal|online)\b/i', $q6)
    && !preg_match('/\b(modul|rpp|kurikulum)\b/i', $q6);

assertCondition("Kasus 6: Memiliki penanda panduan", $hasGuideMarker6);
assertCondition("Kasus 6: Terdeteksi ambigu antara Modul Ajar vs LMS", $isAmbiguousMateri6);

// -----------------------------------------------------------------------------
// BAGIAN C: INTEGRITAS SISTEM & KEAMANAN
// -----------------------------------------------------------------------------
echo "\n--- 3. INTEGRITAS LINGKUNGAN & SECURITYGUARD ---\n";
define('APPPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
require_once __DIR__ . '/../app/Libraries/SecurityGuard.php';
$secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assertCondition("SecurityGuard: Critical protected files hash PASS", $secPass === true, "SEC_PASS: " . (int)$secPass);

$bayaranExists = is_dir(__DIR__ . '/../bayaran');
$cbtkuExists = is_dir(__DIR__ . '/../cbtku');
assertCondition("Subapps: bayaran/ tidak ada (absent)", !$bayaranExists);
assertCondition("Subapps: cbtku/ tidak ada (absent)", !$cbtkuExists);

echo "\n====================================================================\n";
echo "RINGKASAN HASIL: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
