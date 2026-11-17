<?php

/**
 * EAKB Knowledge Catalog Test Suite
 * Mandat EAO-HO-2026-043 (CP5)
 * 
 * Pengujian Fondasi Fisik EAKB Knowledge Catalog & Integritas 15 Atribut
 */

require_once __DIR__ . '/../app/Libraries/EspataApplicationKnowledge.php';
require_once __DIR__ . '/../app/Libraries/EakbCatalogValidator.php';

use App\Libraries\EspataApplicationKnowledge;
use App\Libraries\EakbCatalogValidator;

echo "====================================================================\n";
echo "   EAKB KNOWLEDGE CATALOG TEST SUITE (MANDAT EAO-HO-2026-043)       \n";
echo "   Verifikasi 12 Skenario Pengujian Integritas & Kepatuhan          \n";
echo "====================================================================\n\n";

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$assertions = 0;

function runTest(string $testId, string $description, callable $fn) {
    global $totalTests, $passedTests, $failedTests, $assertions;
    $totalTests++;
    try {
        $result = $fn($assertions);
        if ($result === true) {
            $passedTests++;
            echo "[PASS] [{$testId}] {$description}\n";
        } else {
            $failedTests++;
            echo "[FAIL] [{$testId}] {$description} - Assertion failed\n";
        }
    } catch (\Throwable $e) {
        $failedTests++;
        echo "[FAIL] [{$testId}] {$description} - Exception: {$e->getMessage()}\n";
    }
}

// -----------------------------------------------------------------------------
// [SKENARIO 1] Pemanggilan Katalog Tanpa Error (Declarative Pure PHP)
// -----------------------------------------------------------------------------
runTest('T-EAKB-01', 'Katalog EAKB dapat dipanggil secara murni tanpa query database', function (&$a) {
    $a += 3;
    $features = EspataApplicationKnowledge::getAllFeatures();
    $metadata = EspataApplicationKnowledge::getMetadata();
    return is_array($features) && !empty($features) && ($metadata['catalog_name'] ?? '') === 'ESPATA Application Knowledge Base (EAKB)';
});

// -----------------------------------------------------------------------------
// [SKENARIO 2] Jumlah dan Keunikan Tepat 70 Feature ID
// -----------------------------------------------------------------------------
runTest('T-EAKB-02', 'Jumlah tepat 70 Feature ID unik dan bebas duplikasi', function (&$a) {
    $a += 3;
    $features = EspataApplicationKnowledge::getAllFeatures();
    $featureIds = EspataApplicationKnowledge::getFeatureIds();
    $uniqueIds = array_unique($featureIds);
    return count($features) === 70 && count($featureIds) === 70 && count($uniqueIds) === 70;
});

// -----------------------------------------------------------------------------
// [SKENARIO 3] Validitas 15 Atribut Wajib pada Seluruh Fitur
// -----------------------------------------------------------------------------
runTest('T-EAKB-03', 'Seluruh 70 fitur memiliki 15 atribut lengkap sesuai kontrak data', function (&$a) {
    $features = EspataApplicationKnowledge::getAllFeatures();
    $required = EakbCatalogValidator::REQUIRED_ATTRIBUTES;
    $a += count($features) * count($required);
    foreach ($features as $id => $feat) {
        foreach ($required as $attr) {
            if (!array_key_exists($attr, $feat)) {
                return false;
            }
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 4] Validitas Delapan Role ESPATA (Role ID 1..8)
// -----------------------------------------------------------------------------
runTest('T-EAKB-04', 'Seluruh allowed_roles hanya memuat Role ID sah (1..8)', function (&$a) {
    $features = EspataApplicationKnowledge::getAllFeatures();
    $validRoles = [1, 2, 3, 4, 5, 6, 7, 8];
    $a += count($features);
    foreach ($features as $id => $feat) {
        foreach ($feat['allowed_roles'] as $r) {
            if (!in_array($r, $validRoles, true)) {
                return false;
            }
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 5] Kebijakan Empat Role Pengguna Asis AI (Final Human Owner Policy)
// -----------------------------------------------------------------------------
runTest('T-EAKB-05', 'Kebijakan akses Asis AI tepat mengizinkan Role 1,2,3,7 dan menolak 4,5,6,8', function (&$a) {
    $a += 8;
    $allowedPass = EspataApplicationKnowledge::isRoleAllowedForAsisAi(1) &&
                   EspataApplicationKnowledge::isRoleAllowedForAsisAi(2) &&
                   EspataApplicationKnowledge::isRoleAllowedForAsisAi(3) &&
                   EspataApplicationKnowledge::isRoleAllowedForAsisAi(7);

    $deniedPass  = !EspataApplicationKnowledge::isRoleAllowedForAsisAi(4) &&
                   !EspataApplicationKnowledge::isRoleAllowedForAsisAi(5) &&
                   !EspataApplicationKnowledge::isRoleAllowedForAsisAi(6) &&
                   !EspataApplicationKnowledge::isRoleAllowedForAsisAi(8);

    return $allowedPass && $deniedPass;
});

// -----------------------------------------------------------------------------
// [SKENARIO 6] Validitas Relasi Antar-Fitur (related_features)
// -----------------------------------------------------------------------------
runTest('T-EAKB-06', 'Seluruh related_features merujuk pada feature_id yang eksis di katalog', function (&$a) {
    $features = EspataApplicationKnowledge::getAllFeatures();
    $allIds = EspataApplicationKnowledge::getFeatureIds();
    $a += count($features);
    foreach ($features as $id => $feat) {
        foreach ($feat['related_features'] as $relId) {
            if (!in_array($relId, $allIds, true)) {
                return false;
            }
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 7] Status ACTIVE (68), LIMITED (1), dan INACTIVE (1)
// -----------------------------------------------------------------------------
runTest('T-EAKB-07', 'Rekap status fitur tepat: 68 ACTIVE, 1 LIMITED (SNBP), 1 INACTIVE (Dapodik)', function (&$a) {
    $a += 5;
    $features = EspataApplicationKnowledge::getAllFeatures();
    $active = array_filter($features, fn($f) => $f['feature_status'] === 'ACTIVE');
    $limited = array_filter($features, fn($f) => $f['feature_status'] === 'LIMITED');
    $inactive = array_filter($features, fn($f) => $f['feature_status'] === 'INACTIVE');

    $snbp = EspataApplicationKnowledge::getFeature('feat_adv_snbp_tracker');
    $dapodik = EspataApplicationKnowledge::getFeature('feat_sys_dapodik_sync');

    return count($active) === 68 &&
           count($limited) === 1 &&
           count($inactive) === 1 &&
           ($snbp['feature_status'] ?? '') === 'LIMITED' &&
           ($dapodik['feature_status'] ?? '') === 'INACTIVE';
});

// -----------------------------------------------------------------------------
// [SKENARIO 8] Pembedaan Fakta Empiris dan Sintesis Editorial
// -----------------------------------------------------------------------------
runTest('T-EAKB-08', 'Atribut empiris (routes/controllers) dan sintesis editorial terpisah konsisten', function (&$a) {
    $a += 4;
    $grades = EspataApplicationKnowledge::getFeature('feat_grd_input');
    $hasSourceRef = !empty($grades['source_reference']) && strpos($grades['source_reference'], 'Admin/Grades.php') !== false;
    $hasValidation = !empty($grades['validation_rules_and_errors']);
    $hasFaq = !empty($grades['troubleshooting_faq']);
    $hasSteps = !empty($grades['workflow_steps']);
    return $hasSourceRef && $hasValidation && $hasFaq && $hasSteps;
});

// -----------------------------------------------------------------------------
// [SKENARIO 9] Historical Alias P5 Dialihkan ke Kokurikuler & 8 Profil Lulusan
// -----------------------------------------------------------------------------
runTest('T-EAKB-09', 'Historical alias P5 terpetakan ke Kokurikuler dan 8 Profil Lulusan', function (&$a) {
    $a += 4;
    $aliases = EspataApplicationKnowledge::getHistoricalAliases();
    $hasP5 = isset($aliases['p5']) && $aliases['p5']['target_feature'] === 'feat_kok_planning';
    $hasOfficialTerm = ($aliases['p5']['official_term'] ?? '') === 'Kokurikuler';
    $dimensiCount = count(EspataApplicationKnowledge::PROFIL_LULUSAN_DIMENSI);
    return $hasP5 && $hasOfficialTerm && $dimensiCount === 8;
});

// -----------------------------------------------------------------------------
// [SKENARIO 10] Validitas Sumber Referensi dan Waktu Verifikasi (ISO-8601)
// -----------------------------------------------------------------------------
runTest('T-EAKB-10', 'Seluruh source_reference dan format waktu verifikasi valid', function (&$a) {
    $features = EspataApplicationKnowledge::getAllFeatures();
    $a += count($features) * 2;
    foreach ($features as $id => $feat) {
        if (empty($feat['source_reference']) || strlen($feat['source_reference']) < 5) {
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $feat['last_verified_at'])) {
            return false;
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 11] Deteksi Integritas Melalui EakbCatalogValidator
// -----------------------------------------------------------------------------
runTest('T-EAKB-11', 'EakbCatalogValidator menghasilkan laporan valid tanpa error', function (&$a) {
    $a += 3;
    $validation = EakbCatalogValidator::validate();
    return $validation['is_valid'] === true &&
           $validation['total_features'] === 70 &&
           empty($validation['errors']);
});

// -----------------------------------------------------------------------------
// [SKENARIO 12] Keamanan Data dan Zero Secret / Zero Student PII
// -----------------------------------------------------------------------------
runTest('T-EAKB-12', 'Katalog 100% bersih dari API Key, kata sandi, dan data pribadi siswa', function (&$a) {
    $a += 3;
    $allJson = json_encode(EspataApplicationKnowledge::getAllFeatures());
    $hasSecret = preg_match('/(sk-[a-zA-Z0-9]+|gsk_[a-zA-Z0-9]+|api_key|password_hash|\$2y\$10\$)/i', $allJson);
    $hasNisn = preg_match('/(317[0-9]{13}|00[0-9]{8})/i', $allJson);
    $hasSubapps = preg_match('/(bayaran\/|cbtku\/)/i', $allJson);
    return !$hasSecret && !$hasNisn && !$hasSubapps;
});

// -----------------------------------------------------------------------------
// REKAPITULASI HASIL PENGUJIAN
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "              REKAPITULASI PENGUJIAN EAKB KNOWLEDGE CATALOG         \n";
echo "====================================================================\n";
echo "Total Skenario Diuji : {$totalTests} / 12\n";
echo "Total Lulus (PASS)   : {$passedTests} / 12 (" . round(($passedTests / $totalTests) * 100, 2) . "%)\n";
echo "Total Gagal (FAIL)   : {$failedTests} / 12\n";
echo "Pemeriksaan Assertion: {$assertions} assertions\n";
echo "Status Evaluasi      : " . ($passedTests === 12 ? 'ALL 12 SCENARIOS PASSED 100%' : 'TEST SUITE FAILED') . "\n";
echo "====================================================================\n";

exit($failedTests === 0 ? 0 : 1);
