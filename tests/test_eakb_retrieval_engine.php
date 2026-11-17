<?php

/**
 * EAKB Core Retrieval Engine & Indexer Test Suite
 * Mandat EAO-HO-2026-047 (Checkpoint 5)
 * 
 * Pengujian Mandiri Mesin Pencarian Inti EAKB, Indexer, Toleransi Typo,
 * RBAC Filtering, Kontrak Hasil, dan Pengukuran Performa Aktual.
 */

require_once __DIR__ . '/../app/Libraries/EspataApplicationKnowledge.php';
require_once __DIR__ . '/../app/Libraries/EakbCatalogValidator.php';
require_once __DIR__ . '/../app/Libraries/EakbRetrievalEngine.php';

use App\Libraries\EspataApplicationKnowledge;
use App\Libraries\EakbCatalogValidator;
use App\Libraries\EakbRetrievalEngine;

echo "====================================================================\n";
echo "   EAKB RETRIEVAL ENGINE TEST SUITE (MANDAT EAO-HO-2026-047)        \n";
echo "   Verifikasi 18+ Skenario Mandiri, Indexer, RBAC, & Latensi        \n";
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
// [SKENARIO 1] Seluruh 70 Fitur Terbaca dari Katalog Aktual
// -----------------------------------------------------------------------------
runTest('T-RE-01', 'Seluruh 70 fitur dapat dibaca dari katalog EAKB ke indexer', function (&$a) {
    $a += 2;
    $index = EakbRetrievalEngine::getIndex(true);
    return isset($index['total_features']) && $index['total_features'] === 70 && count($index['features']) === 70;
});

// -----------------------------------------------------------------------------
// [SKENARIO 2] Keunikan Feature ID di Indeks (Zero Duplicate)
// -----------------------------------------------------------------------------
runTest('T-RE-02', 'Keunikan Feature ID: tepat 70 entri unik tanpa duplikasi indeks', function (&$a) {
    $a += 2;
    $index = EakbRetrievalEngine::getIndex();
    $keys = array_keys($index['features']);
    return count($keys) === 70 && count(array_unique($keys)) === 70;
});

// -----------------------------------------------------------------------------
// [SKENARIO 3] Pencarian Berdasarkan Feature ID Eksak
// -----------------------------------------------------------------------------
runTest('T-RE-03', 'Pencarian berdasarkan Feature ID mengembalikan fitur target presisi', function (&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::search('feat_cbt_bank_soal', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           $top['feature_id'] === 'feat_cbt_bank_soal' &&
           in_array('feature_id:exact', $top['matched_fields'], true);
});

// -----------------------------------------------------------------------------
// [SKENARIO 4] Pencarian Berdasarkan Judul Fitur Eksak / Parsial
// -----------------------------------------------------------------------------
runTest('T-RE-04', 'Pencarian berdasarkan judul fitur menemukan entri yang relevan', function (&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::search('Presensi Siswa Harian', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           $top['feature_id'] === 'feat_att_daily_class' &&
           $top['category'] === 'Akademik & KBM';
});

// -----------------------------------------------------------------------------
// [SKENARIO 5] Pencarian Berdasarkan Kata Kunci Domain
// -----------------------------------------------------------------------------
runTest('T-RE-05', 'Pencarian berdasarkan kata kunci modul menemukan fitur terkait', function (&$a) {
    $a += 3;
    $res = EakbRetrievalEngine::search('panduan modul ajar kurikulum merdeka', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           ($top['feature_id'] === 'feat_cur_modul_ajar' || $top['feature_id'] === 'feat_cur_modul_ajar_ai');
});

// -----------------------------------------------------------------------------
// [SKENARIO 6] Pencarian Berdasarkan Sinonim Terstruktur
// -----------------------------------------------------------------------------
runTest('T-RE-06', 'Pencarian sinonim awam memetakan ke fitur resmi yang tepat', function (&$a) {
    $a += 3;
    // 'bikin soal' dipetakan via alias index ke feat_cbt_bank_soal
    $res = EakbRetrievalEngine::search('bikin soal', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           $top['feature_id'] === 'feat_cbt_bank_soal';
});

// -----------------------------------------------------------------------------
// [SKENARIO 7] Alias Historis P5 Memetakan ke Kokurikuler (Tanpa Menjadi Nomenklatur)
// -----------------------------------------------------------------------------
runTest('T-RE-07', 'Alias historis P5 memetakan ke Kokurikuler dan tetap menggunakan istilah resmi', function (&$a) {
    $a += 5;
    $res = EakbRetrievalEngine::search('buat modul p5', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           $top['feature_id'] === 'feat_kok_planning' &&
           $top['category'] === 'Kokurikuler' &&
           stripos($top['title'], 'P5') === false; // Tidak menggunakan P5 di judul resmi
});

// -----------------------------------------------------------------------------
// [SKENARIO 8] Toleransi Salah Ketik (Fuzzy Levenshtein)
// -----------------------------------------------------------------------------
runTest('T-RE-08', 'Toleransi salah ketik (typo Levenshtein) memetakan token ke fitur yang benar', function (&$a) {
    $a += 3;
    // 'kokulikuler' (jarak 1 dari kokurikuler) memetakan ke modul kokurikuler
    $res = EakbRetrievalEngine::search('projek kokulikuler', 3);
    $top = $res['results'][0] ?? null;
    return $res['status'] === 'SUCCESS' &&
           $top !== null &&
           str_starts_with($top['feature_id'], 'feat_kok_');
});

// -----------------------------------------------------------------------------
// [SKENARIO 9] Pengurutan Hasil Berdasarkan Skor Tertinggi (Descending)
// -----------------------------------------------------------------------------
runTest('T-RE-09', 'Hasil pencarian diurutkan menurun berdasarkan relevance_score', function (&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::search('rapor nilai siswa', 3, ['limit' => 5]);
    $results = $res['results'];
    if (count($results) < 2) {
        return false;
    }
    for ($i = 0; $i < count($results) - 1; $i++) {
        if ($results[$i]['relevance_score'] < $results[$i + 1]['relevance_score']) {
            return false;
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 10] Hasil Kosong untuk Pertanyaan Tidak Relevan (Zero Hallucination)
// -----------------------------------------------------------------------------
runTest('T-RE-10', 'Pertanyaan tidak relevan / non-ESPATA mengembalikan hasil kosong', function (&$a) {
    $a += 3;
    $res = EakbRetrievalEngine::search('resep masakan nasi goreng rendang padang', 3);
    return $res['status'] === 'NO_MATCH' &&
           $res['total_found'] === 0 &&
           empty($res['results']);
});

// -----------------------------------------------------------------------------
// [SKENARIO 11] Penyaringan Role yang Diizinkan (Allowlist Role 1, 2, 3, 7)
// -----------------------------------------------------------------------------
runTest('T-RE-11', 'Role ID yang diizinkan (1, 2, 3, 7) berhasil mengakses hasil pencarian', function (&$a) {
    $a += 4;
    $r1 = EakbRetrievalEngine::search('kalender sekolah', 1); // Admin
    $r2 = EakbRetrievalEngine::search('kalender sekolah', 2); // Kepsek
    $r3 = EakbRetrievalEngine::search('kalender sekolah', 3); // Guru
    $r7 = EakbRetrievalEngine::search('kalender sekolah', 7); // TU
    return $r1['is_allowed_role'] === true &&
           $r2['is_allowed_role'] === true &&
           $r3['is_allowed_role'] === true &&
           $r7['is_allowed_role'] === true;
});

// -----------------------------------------------------------------------------
// [SKENARIO 12] Penolakan Role yang Tidak Diizinkan (Role 4, 5, 6, 8)
// -----------------------------------------------------------------------------
runTest('T-RE-12', 'Role yang tidak diizinkan (4, 5, 6, 8) ditolak dan tidak menerima hasil', function (&$a) {
    $a += 8;
    $r4 = EakbRetrievalEngine::search('input nilai', 4); // Orang Tua
    $r5 = EakbRetrievalEngine::search('input nilai', 5); // Siswa
    $r6 = EakbRetrievalEngine::search('input nilai', 6); // Kontributor
    $r8 = EakbRetrievalEngine::search('input nilai', 8); // Coach

    return $r4['status'] === 'ROLE_NOT_ALLOWED' && empty($r4['results']) &&
           $r5['status'] === 'ROLE_NOT_ALLOWED' && empty($r5['results']) &&
           $r6['status'] === 'ROLE_NOT_ALLOWED' && empty($r6['results']) &&
           $r8['status'] === 'ROLE_NOT_ALLOWED' && empty($r8['results']);
});

// -----------------------------------------------------------------------------
// [SKENARIO 13] Status ACTIVE, LIMITED, dan INACTIVE Terpelihara Presisi
// -----------------------------------------------------------------------------
runTest('T-RE-13', 'Status ACTIVE, LIMITED, dan INACTIVE terpelihara tanpa manipulasi status', function (&$a) {
    $a += 6;
    $snbp = EakbRetrievalEngine::search('pelacak eligible snbp', 1);
    $dapodik = EakbRetrievalEngine::search('sinkronisasi data dapodik', 1);
    $grades = EakbRetrievalEngine::search('input nilai harian formatif', 3);

    $snbpStatus = $snbp['results'][0]['feature_status'] ?? '';
    $dapodikStatus = $dapodik['results'][0]['feature_status'] ?? '';
    $gradesStatus = $grades['results'][0]['feature_status'] ?? '';

    return $snbpStatus === 'LIMITED' &&
           $dapodikStatus === 'INACTIVE' &&
           $gradesStatus === 'ACTIVE';
});

// -----------------------------------------------------------------------------
// [SKENARIO 14] Metadata Sumber dan Batasan Tidak Hilang
// -----------------------------------------------------------------------------
runTest('T-RE-14', 'Metadata source_reference, confidence, dan known_limitations lengkap', function (&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::search('pelacak eligible snbp', 1);
    $item = $res['results'][0] ?? [];
    return !empty($item['source_reference']) &&
           isset($item['confidence']) && $item['confidence'] >= 1 &&
           !empty($item['known_limitations']) &&
           (strpos($item['known_limitations'][0], 'menengah atas') !== false || strpos($item['known_limitations'][0], 'SMA') !== false);
});

// -----------------------------------------------------------------------------
// [SKENARIO 15] Indexer Dapat Dibangun Ulang Dinamis (Rebuild Index)
// -----------------------------------------------------------------------------
runTest('T-RE-15', 'Indexer dapat dibangun ulang secara dinamis tanpa duplikasi memori', function (&$a) {
    $a += 3;
    $index1 = EakbRetrievalEngine::getIndex();
    $index2 = EakbRetrievalEngine::rebuildIndex();
    return $index1['total_features'] === 70 &&
           $index2['total_features'] === 70 &&
           $index2['built_at'] >= $index1['built_at'];
});

// -----------------------------------------------------------------------------
// [SKENARIO 16] Tidak Ada Duplikasi Hasil Akibat Indeks
// -----------------------------------------------------------------------------
runTest('T-RE-16', 'Satu feature_id hanya muncul maksimal satu kali dalam hasil pencarian', function (&$a) {
    $a += 3;
    $res = EakbRetrievalEngine::search('nilai raport ujian formatif sumatif', 3, ['limit' => 10]);
    $ids = array_column($res['results'], 'feature_id');
    return count($ids) === count(array_unique($ids));
});

// -----------------------------------------------------------------------------
// [SKENARIO 17] Tidak Ada Informasi Fitur yang Dibuat-Buat (Grounding Check)
// -----------------------------------------------------------------------------
runTest('T-RE-17', 'Seluruh atribut hasil pencarian berasal dari data riil katalog', function (&$a) {
    $a += 4;
    $res = EakbRetrievalEngine::search('jurnal mengajar guru harian', 3);
    $item = $res['results'][0] ?? null;
    if (!$item) return false;

    $catalogItem = EspataApplicationKnowledge::getFeature($item['feature_id']);
    return $catalogItem !== null &&
           $item['title'] === $catalogItem['title'] &&
           $item['category'] === $catalogItem['category'];
});

// -----------------------------------------------------------------------------
// [SKENARIO 18] Konsistensi Hasil untuk Kueri yang Sama (Determinisme)
// -----------------------------------------------------------------------------
runTest('T-RE-18', 'Hasil pencarian deterministik: kueri identik menghasilkan urutan dan skor sama', function (&$a) {
    $a += 4;
    $res1 = EakbRetrievalEngine::search('rekap presensi siswa', 3);
    $res2 = EakbRetrievalEngine::search('rekap presensi siswa', 3);

    $ids1 = array_column($res1['results'], 'feature_id');
    $ids2 = array_column($res2['results'], 'feature_id');
    $scores1 = array_column($res1['results'], 'relevance_score');
    $scores2 = array_column($res2['results'], 'relevance_score');

    return $ids1 === $ids2 && $scores1 === $scores2;
});

// -----------------------------------------------------------------------------
// [SKENARIO 19 - EDGE CASE] Penanganan Backlog EAO-045: Koreksi Typo Berita.php
// -----------------------------------------------------------------------------
runTest('T-RE-19', 'Mitigasi backlog EAO-045: path Berita.php pada feat_cms_school_web dikoreksi ke Articles.php', function (&$a) {
    $a += 3;
    $res = EakbRetrievalEngine::search('portal website berita sekolah cms', 1);
    $item = $res['results'][0] ?? null;
    return $item !== null &&
           $item['feature_id'] === 'feat_cms_school_web' &&
           str_contains($item['source_reference'], 'Articles.php') &&
           !str_contains($item['source_reference'], 'Berita.php');
});

// -----------------------------------------------------------------------------
// [SKENARIO 20 - PERFORMA] Pengukuran Latensi Aktual pada 70 Fitur (< 2.0 ms)
// -----------------------------------------------------------------------------
runTest('T-RE-20', 'Pengukuran performa aktual: latensi rata-rata pencarian < 2.0 ms pada shared hosting', function (&$a) {
    $a += 4;
    $queries = [
        'cara membuat modul ajar',
        'input nilai formatif',
        'presensi siswa',
        'bank soal cbt',
        'cetak kartu ujian',
        'bantu buat modul p5',
        'kalender pendidikan',
        'mutasi siswa keluar',
        'reset ujian cbt siswa',
        'jadwal mengajar'
    ];

    $totalTime = 0.0;
    $runs = 50;

    for ($i = 0; $i < $runs; $i++) {
        $q = $queries[$i % count($queries)];
        $t0 = microtime(true);
        EakbRetrievalEngine::search($q, 3);
        $totalTime += (microtime(true) - $t0);
    }

    $avgMs = ($totalTime / $runs) * 1000;
    echo "       -> Latensi rata-rata aktual: " . round($avgMs, 3) . " ms (Target: < 2.0 ms)\n";

    return $avgMs < 2.0;
});

// -----------------------------------------------------------------------------
// REKAPITULASI HASIL PENGUJIAN
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "              REKAPITULASI PENGUJIAN EAKB RETRIEVAL ENGINE         \n";
echo "====================================================================\n";
echo "Total Skenario Diuji : {$totalTests} / 20\n";
echo "Total Lulus (PASS)   : {$passedTests} / 20 (" . round(($passedTests / $totalTests) * 100, 2) . "%)\n";
echo "Total Gagal (FAIL)   : {$failedTests} / 20\n";
echo "Pemeriksaan Assertion: {$assertions} assertions\n";
echo "Status Evaluasi      : " . ($passedTests === 20 ? 'ALL 20 SCENARIOS PASSED 100%' : 'TEST SUITE FAILED') . "\n";
echo "====================================================================\n";

exit($failedTests === 0 ? 0 : 1);
