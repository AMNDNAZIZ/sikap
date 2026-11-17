<?php
/**
 * Test Suite: EAKB Stage 2C Comprehensive Verification
 * Mandat: EAO-HO-2026-049
 * Cakupan: Advanced RBAC (1-7), Feature Graph Traversal (8-16), Disambiguation Resolver (17-24), Unified Pipeline & Latensi (25-26)
 */

require_once __DIR__ . '/../app/Libraries/EspataApplicationKnowledge.php';
require_once __DIR__ . '/../app/Libraries/EakbRetrievalEngine.php';
require_once __DIR__ . '/../app/Libraries/EakbFeatureGraph.php';
require_once __DIR__ . '/../app/Libraries/EakbDisambiguationResolver.php';

use App\Libraries\EspataApplicationKnowledge;
use App\Libraries\EakbRetrievalEngine;
use App\Libraries\EakbFeatureGraph;
use App\Libraries\EakbDisambiguationResolver;

$scenariosPassed = 0;
$scenariosFailed = 0;
$totalAssertions = 0;

function runScenario(string $id, string $title, callable $testFunc): void {
    global $scenariosPassed, $scenariosFailed, $totalAssertions;
    $assertionsBefore = $totalAssertions;
    try {
        $result = $testFunc($totalAssertions);
        if ($result === true) {
            $scenariosPassed++;
            echo sprintf("[PASS] [%s] %s\n", $id, $title);
        } else {
            $scenariosFailed++;
            echo sprintf("[FAIL] [%s] %s (Assertion returned false)\n", $id, $title);
        }
    } catch (\Throwable $e) {
        $scenariosFailed++;
        echo sprintf("[FAIL] [%s] %s: Exception: %s (Line %d)\n", $id, $title, $e->getMessage(), $e->getLine());
    }
}

echo "====================================================================\n";
echo "   EAKB STAGE 2C COMPREHENSIVE TEST SUITE (EAO-HO-2026-049)         \n";
echo "   Advanced RBAC, Feature Graph Traversal, & Disambiguation Resolver\n";
echo "====================================================================\n\n";

// =========================================================================
// BAGIAN 1: ADVANCED RBAC (SKENARIO 1 - 7)
// =========================================================================

// [T-2C-01] Role Allowlist Resmi: 1, 2, 3, 7 Diterima
runScenario('T-2C-01', 'Role Allowlist Resmi: Role 1, 2, 3, dan 7 diterima', function(&$a) {
    $a += 4;
    return EakbRetrievalEngine::isAllowedRole(1) === true &&
           EakbRetrievalEngine::isAllowedRole(2) === true &&
           EakbRetrievalEngine::isAllowedRole(3) === true &&
           EakbRetrievalEngine::isAllowedRole(7) === true;
});

// [T-2C-02] Role Blocklist: 4, 5, 6, 8 Ditolak
runScenario('T-2C-02', 'Role Blocklist: Role 4, 5, 6, dan 8 ditolak', function(&$a) {
    $a += 8;
    $res4 = EakbRetrievalEngine::search('nilai', 4);
    $res5 = EakbRetrievalEngine::search('nilai', 5);
    $res6 = EakbRetrievalEngine::search('nilai', 6);
    $res8 = EakbRetrievalEngine::search('nilai', 8);

    return EakbRetrievalEngine::isAllowedRole(4) === false &&
           EakbRetrievalEngine::isAllowedRole(5) === false &&
           EakbRetrievalEngine::isAllowedRole(6) === false &&
           EakbRetrievalEngine::isAllowedRole(8) === false &&
           $res4['status'] === 'ROLE_NOT_ALLOWED' &&
           $res5['status'] === 'ROLE_NOT_ALLOWED' &&
           $res6['status'] === 'ROLE_NOT_ALLOWED' &&
           $res8['status'] === 'ROLE_NOT_ALLOWED';
});

// [T-2C-03] Role Kosong dan Tidak Valid Ditolak (Fail Closed)
runScenario('T-2C-03', 'Role kosong, null, negatif, dan tidak valid ditolak (Fail Closed)', function(&$a) {
    $a += 6;
    $resNull = EakbRetrievalEngine::search('jadwal', null);
    $resZero = EakbRetrievalEngine::search('jadwal', 0);
    $resNeg  = EakbRetrievalEngine::search('jadwal', -1);
    $res99   = EakbRetrievalEngine::search('jadwal', 99);

    return EakbRetrievalEngine::isAllowedRole(null) === false &&
           EakbRetrievalEngine::isAllowedRole(0) === false &&
           $resNull['status'] === 'ROLE_NOT_ALLOWED' &&
           $resZero['status'] === 'ROLE_NOT_ALLOWED' &&
           $resNeg['status'] === 'ROLE_NOT_ALLOWED' &&
           $res99['status'] === 'ROLE_NOT_ALLOWED';
});

// [T-2C-04] Fitur dengan allowed_roles Kosong atau Tidak Valid Ditolak
runScenario('T-2C-04', 'Fitur dengan allowed_roles kosong atau tidak valid ditolak', function(&$a) {
    $a += 4;
    $fakeEmptyFeature = ['feature_id' => 'feat_empty', 'title' => 'Empty', 'allowed_roles' => []];
    $fakeNullFeature  = ['feature_id' => 'feat_null', 'title' => 'Null', 'allowed_roles' => null];
    $fakeStrFeature   = ['feature_id' => 'feat_str', 'title' => 'Str', 'allowed_roles' => 'admin'];

    return EakbRetrievalEngine::canAccessFeature($fakeEmptyFeature, 1) === false &&
           EakbRetrievalEngine::canAccessFeature($fakeNullFeature, 1) === false &&
           EakbRetrievalEngine::canAccessFeature($fakeStrFeature, 1) === false &&
           EakbRetrievalEngine::canAccessFeature('non_existent_feature_xyz', 1) === false;
});

// [T-2C-05] Fitur yang Tidak Mengizinkan Role Pengguna Tidak Dikembalikan dalam Pencarian
runScenario('T-2C-05', 'Fitur yang tidak mengizinkan role pengguna tidak dikembalikan dalam pencarian', function(&$a) {
    $a += 4;
    // feat_sch_classes_master hanya untuk Role 1 (Admin)
    $resGuru = EakbRetrievalEngine::search('Master Kelas & Ruang', 3);
    $resAdmin = EakbRetrievalEngine::search('Master Kelas & Ruang', 1);

    $guruHasMasterKelas = false;
    foreach ($resGuru['results'] as $r) {
        if ($r['feature_id'] === 'feat_sch_classes_master') $guruHasMasterKelas = true;
    }

    $adminHasMasterKelas = false;
    foreach ($resAdmin['results'] as $r) {
        if ($r['feature_id'] === 'feat_sch_classes_master') $adminHasMasterKelas = true;
    }

    return $guruHasMasterKelas === false &&
           $adminHasMasterKelas === true &&
           $resGuru['total_found'] >= 0 &&
           $resAdmin['total_found'] > 0;
});

// [T-2C-06] Fitur Terlarang Tidak Bocor Melalui Graph Traversal
runScenario('T-2C-06', 'Fitur terlarang tidak bocor melalui graph traversal', function(&$a) {
    $a += 4;
    // feat_adv_snbp_tracker (Roles: 1, 2, 3) memiliki relasi ke feat_sch_jenjang_master (Roles: 1)
    $traversalGuru = EakbFeatureGraph::traverse('feat_adv_snbp_tracker', 3);
    $traversalAdmin = EakbFeatureGraph::traverse('feat_adv_snbp_tracker', 1);

    $guruFoundJenjangMaster = false;
    foreach ($traversalGuru['nodes'] as $node) {
        if ($node['feature_id'] === 'feat_sch_jenjang_master') $guruFoundJenjangMaster = true;
    }

    $adminFoundJenjangMaster = false;
    foreach ($traversalAdmin['nodes'] as $node) {
        if ($node['feature_id'] === 'feat_sch_jenjang_master') $adminFoundJenjangMaster = true;
    }

    return $guruFoundJenjangMaster === false &&
           $adminFoundJenjangMaster === true &&
           $traversalGuru['status'] === 'SUCCESS' &&
           $traversalAdmin['status'] === 'SUCCESS';
});

// [T-2C-07] Fitur Utama yang Boleh Diakses Tidak Membuka Fitur Turunan yang Terlarang
runScenario('T-2C-07', 'Fitur utama yang boleh diakses tidak membuka relasi turunan terlarang', function(&$a) {
    $a += 3;
    // feat_grd_input (Roles: 1, 2, 3) terhubung ke feat_sch_teaching_assignments (Roles: 1, 2, 7)
    $neighborsGuru = EakbFeatureGraph::getNeighbors('feat_grd_input', 3);
    $neighborsKepsek = EakbFeatureGraph::getNeighbors('feat_grd_input', 2);

    $guruHasSK = false;
    foreach ($neighborsGuru as $n) {
        if ($n['feature_id'] === 'feat_sch_teaching_assignments') $guruHasSK = true;
    }

    $kepsekHasSK = false;
    foreach ($neighborsKepsek as $n) {
        if ($n['feature_id'] === 'feat_sch_teaching_assignments') $kepsekHasSK = true;
    }

    return $guruHasSK === false &&
           $kepsekHasSK === true &&
           count($neighborsGuru) <= count($neighborsKepsek);
});

// =========================================================================
// BAGIAN 2: FEATURE GRAPH TRAVERSAL (SKENARIO 8 - 16)
// =========================================================================

// [T-2C-08] Graph Dibangun dari Katalog Aktual
runScenario('T-2C-08', 'Graph dibangun dari katalog aktual EspataApplicationKnowledge', function(&$a) {
    $a += 3;
    $adj = EakbFeatureGraph::getAdjacencyList();
    $catalogFeatures = EspataApplicationKnowledge::getAllFeatures();

    return count($adj) === 70 &&
           isset($adj['feat_cbt_bank_soal']) &&
           count($adj) === count($catalogFeatures);
});

// [T-2C-09] Relasi Valid Dapat Ditelusuri
runScenario('T-2C-09', 'Relasi valid dapat ditelusuri dengan hop_distance dan connected_from', function(&$a) {
    $a += 4;
    $res = EakbFeatureGraph::traverse('feat_cur_modul_ajar', 1);
    $nodes = $res['nodes'];

    $hasValidHop = true;
    foreach ($nodes as $n) {
        if ($n['hop_distance'] !== 1 || $n['connected_from'] !== 'feat_cur_modul_ajar') {
            $hasValidHop = false;
        }
    }

    return $res['status'] === 'SUCCESS' &&
           count($nodes) > 0 &&
           $hasValidHop &&
           $res['start_feature'] === 'feat_cur_modul_ajar';
});

// [T-2C-10] Relasi Tidak Valid Tidak Menghasilkan Node Fiktif
runScenario('T-2C-10', 'Relasi ke fitur non-existent diabaikan tanpa membuat node fiktif', function(&$a) {
    $a += 3;
    $res = EakbFeatureGraph::traverse('feat_non_existent_xyz', 1);
    $adj = EakbFeatureGraph::getAdjacencyList();

    $allEdgesValid = true;
    $catalog = EspataApplicationKnowledge::getAllFeatures();
    foreach ($adj as $src => $edges) {
        foreach ($edges as $tgt) {
            if (!isset($catalog[$tgt])) $allEdgesValid = false;
        }
    }

    return $res['status'] === 'START_NODE_NOT_FOUND' &&
           $res['total_nodes'] === 0 &&
           $allEdgesValid === true;
});

// [T-2C-11] Node Duplikat Dicegah dalam Hasil Traversal
runScenario('T-2C-11', 'Node duplikat dicegah dalam daftar ketetanggaan dan traversal', function(&$a) {
    $a += 3;
    $res = EakbFeatureGraph::traverse('feat_sys_users', 1);
    $ids = array_column($res['nodes'], 'feature_id');
    $uniqueIds = array_unique($ids);

    return count($ids) === count($uniqueIds) &&
           !in_array('feat_sys_users', $ids, true) &&
           $res['status'] === 'SUCCESS';
});

// [T-2C-12] Siklus Traversal Tidak Menyebabkan Loop Tak Terbatas
runScenario('T-2C-12', 'Siklus dua arah (A <-> B) tidak menyebabkan traversal tanpa akhir', function(&$a) {
    $a += 3;
    // feat_sys_users <-> feat_sys_roles_rbac saling merujuk
    $t0 = microtime(true);
    $res1 = EakbFeatureGraph::traverse('feat_sys_users', 1);
    $res2 = EakbFeatureGraph::traverse('feat_sys_roles_rbac', 1);
    $duration = (microtime(true) - $t0) * 1000;

    return $res1['status'] === 'SUCCESS' &&
           $res2['status'] === 'SUCCESS' &&
           $duration < 10.0; // Selesai dalam hitungan milidetik
});

// [T-2C-13] Kedalaman Maksimal Satu Hop Dipatuhi
runScenario('T-2C-13', 'Kedalaman maksimal satu hop (one-hop) dipatuhi secara ketat', function(&$a) {
    $a += 3;
    $res = EakbFeatureGraph::traverse('feat_cbt_bank_soal', 3, ['max_hops' => 1]);
    $allHop1 = true;
    foreach ($res['nodes'] as $n) {
        if ($n['hop_distance'] > 1) $allHop1 = false;
    }

    return $res['status'] === 'SUCCESS' &&
           $res['max_hops'] === 1 &&
           $allHop1 === true;
});

// [T-2C-14] Maksimal Tiga Fitur Terkait yang Dikembalikan
runScenario('T-2C-14', 'Maksimal tiga fitur terkait (max_nodes = 3) dikembalikan', function(&$a) {
    $a += 3;
    $res = EakbFeatureGraph::traverse('feat_sch_academic_year', 1, ['max_nodes' => 3]);
    return $res['status'] === 'SUCCESS' &&
           count($res['nodes']) <= 3 &&
           $res['max_nodes_limit'] === 3;
});

// [T-2C-15] Fitur Utama Tetap Menjadi Hasil Pertama pada Pipeline
runScenario('T-2C-15', 'Fitur utama tetap menjadi hasil pertama mendahului fitur terkait', function(&$a) {
    $a += 3;
    $pipe = EakbRetrievalEngine::resolve('buat perencanaan modul p5', 3);
    $primary = $pipe['primary_feature'];
    $related = $pipe['related_features'];

    return $pipe['status'] === 'RESOLVED' &&
           $primary['feature_id'] === 'feat_kok_planning' &&
           count($related) >= 0;
});

// [T-2C-16] Status dan Metadata Fitur Tetap Terpelihara dalam Node Graf
runScenario('T-2C-16', 'Status, source reference, confidence, dan metadata terpelihara utuh', function(&$a) {
    $a += 4;
    $res = EakbFeatureGraph::traverse('feat_sch_identity', 1);
    $firstNode = $res['nodes'][0] ?? [];

    return isset($firstNode['feature_id']) &&
           isset($firstNode['feature_status']) &&
           isset($firstNode['source_reference']) &&
           isset($firstNode['confidence']);
});

// =========================================================================
// BAGIAN 3: DISAMBIGUATION RESOLVER (SKENARIO 17 - 24)
// =========================================================================

// [T-2C-17] Pertanyaan dengan Satu Hasil Dominan Menjadi RESOLVED
runScenario('T-2C-17', 'Pertanyaan dengan satu hasil dominan menghasilkan status RESOLVED', function(&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::resolve('feat_cbt_bank_soal', 3);
    return $res['status'] === 'RESOLVED' &&
           $res['is_ambiguous'] === false &&
           $res['primary_feature']['feature_id'] === 'feat_cbt_bank_soal' &&
           empty($res['clarification_candidates']);
});

// [T-2C-18] Pertanyaan Ambigu Menghasilkan Status NEEDS_CLARIFICATION
runScenario('T-2C-18', 'Pertanyaan ambigu menghasilkan status NEEDS_CLARIFICATION', function(&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::resolve('nilai', 3);
    return $res['status'] === 'NEEDS_CLARIFICATION' &&
           $res['is_ambiguous'] === true &&
           $res['primary_feature'] === null &&
           count($res['clarification_candidates']) >= 2;
});

// [T-2C-19] Pertanyaan Tidak Relevan Menghasilkan Status NO_MATCH
runScenario('T-2C-19', 'Pertanyaan tidak relevan / non-sekolah menghasilkan status NO_MATCH', function(&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::resolve('resep rendang padang sapi', 3);
    return $res['status'] === 'NO_MATCH' &&
           $res['is_ambiguous'] === false &&
           $res['primary_feature'] === null &&
           empty($res['clarification_candidates']);
});

// [T-2C-20] Role Terlarang pada Resolver Menghasilkan ROLE_NOT_ALLOWED
runScenario('T-2C-20', 'Role terlarang menghasilkan status ROLE_NOT_ALLOWED', function(&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::resolve('buat soal ujian', 5); // Siswa (Role 5)
    return $res['status'] === 'ROLE_NOT_ALLOWED' &&
           $res['is_ambiguous'] === false &&
           $res['primary_feature'] === null &&
           empty($res['clarification_candidates']);
});

// [T-2C-21] Kandidat Klarifikasi Hanya Berisi Fitur yang Diizinkan untuk Role Pengguna
runScenario('T-2C-21', 'Kandidat klarifikasi hanya berisi fitur yang sah untuk role pengguna', function(&$a) {
    $a += 3;
    $resGuru = EakbRetrievalEngine::resolve('presensi', 3);
    $candidates = $resGuru['clarification_candidates'];

    $allAllowed = true;
    foreach ($candidates as $c) {
        if (!EakbRetrievalEngine::canAccessFeature($c['feature_id'], 3)) {
            $allAllowed = false;
        }
    }

    return $resGuru['status'] === 'NEEDS_CLARIFICATION' &&
           count($candidates) > 0 &&
           $allAllowed === true;
});

// [T-2C-22] Maksimal Tiga Kandidat Klarifikasi Dikembalikan
runScenario('T-2C-22', 'Maksimal tiga kandidat klarifikasi dikembalikan pada status ambiguitas', function(&$a) {
    $a += 2;
    $res = EakbRetrievalEngine::resolve('rapor', 3);
    return count($res['clarification_candidates']) <= 3 &&
           count($res['clarification_candidates']) >= 2;
});

// [T-2C-23] Skor Rendah (< 1.0) Tidak Dipaksakan Menjadi Hasil
runScenario('T-2C-23', 'Skor rendah di bawah ambang batas minimum tidak dipaksakan', function(&$a) {
    $a += 2;
    $searchMock = [
        'query'   => 'tes rendah',
        'role_id' => 3,
        'status'  => 'SUCCESS',
        'results' => [
            ['feature_id' => 'feat_low', 'title' => 'Low', 'relevance_score' => 0.45]
        ]
    ];
    $res = EakbDisambiguationResolver::resolve($searchMock, ['min_score' => 1.0]);
    return $res['status'] === 'NO_MATCH' &&
           $res['primary_candidate'] === null;
});

// [T-2C-24] Input yang Sama Menghasilkan Keputusan Deterministik
runScenario('T-2C-24', 'Input identik menghasilkan keputusan status, kandidat, dan skor deterministik', function(&$a) {
    $a += 4;
    $res1 = EakbRetrievalEngine::resolve('bikin soal ujian online', 3);
    $res2 = EakbRetrievalEngine::resolve('bikin soal ujian online', 3);

    return $res1['status'] === $res2['status'] &&
           $res1['primary_feature']['feature_id'] === $res2['primary_feature']['feature_id'] &&
           $res1['primary_feature']['relevance_score'] === $res2['primary_feature']['relevance_score'] &&
           count($res1['related_features']) === count($res2['related_features']);
});

// =========================================================================
// BAGIAN 4: INTEGRASI PIPELINE & BENCHMARK LATENSI (SKENARIO 25 - 26)
// =========================================================================

// [T-2C-25] Integrasi Pipeline Terpadu: Retrieval -> RBAC -> Disambiguation -> Graph
runScenario('T-2C-25', 'Pipeline terpadu EakbRetrievalEngine::resolve() berjalan sempurna', function(&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::resolve('Presensi Siswa Harian Per Rombel', 3);
    return $res['status'] === 'RESOLVED' &&
           $res['primary_feature']['feature_id'] === 'feat_att_daily_class' &&
           isset($res['related_features']) &&
           isset($res['latency_ms']);
});

// [T-2C-26] Pengukuran Kinerja Aktual: Latensi Rata-rata Resolve < 2.0 ms
runScenario('T-2C-26', 'Pengukuran performa aktual: latensi rata-rata resolve < 2.0 ms', function(&$a) {
    $a += 3;
    $queries = [
        'feat_cbt_bank_soal',
        'Presensi Siswa Harian Per Rombel',
        'nilai',
        'buat perencanaan modul p5',
        'jadwal pelajaran mingguan',
    ];

    $totalTime = 0;
    $iterations = 30;
    for ($i = 0; $i < $iterations; $i++) {
        foreach ($queries as $q) {
            $t0 = microtime(true);
            EakbRetrievalEngine::resolve($q, 3);
            $totalTime += (microtime(true) - $t0);
        }
    }

    $avgMs = ($totalTime / ($iterations * count($queries))) * 1000;
    echo sprintf("       -> Latensi rata-rata resolve: %.3f ms (Target: < 2.0 ms)\n", $avgMs);

    return $avgMs < 2.0 &&
           $avgMs > 0.05 &&
           $totalTime > 0;
});

// =========================================================================
// REKAPITULASI HASIL PENGUJIAN
// =========================================================================
$totalScenarios = $scenariosPassed + $scenariosFailed;
echo "\n====================================================================\n";
echo "              REKAPITULASI PENGUJIAN EAKB STAGE 2C                 \n";
echo "====================================================================\n";
echo "Total Skenario Diuji : {$totalScenarios} / {$totalScenarios}\n";
echo "Total Lulus (PASS)   : {$scenariosPassed} / {$totalScenarios} (" . round(($scenariosPassed / $totalScenarios) * 100) . "%)\n";
echo "Total Gagal (FAIL)   : {$scenariosFailed} / {$totalScenarios}\n";
echo "Pemeriksaan Assertion: {$totalAssertions} assertions\n";
echo "Status Evaluasi      : " . ($scenariosFailed === 0 ? "ALL {$totalScenarios} SCENARIOS PASSED 100%" : "FAILURES DETECTED") . "\n";
echo "====================================================================\n";

exit($scenariosFailed === 0 ? 0 : 1);
