<?php

/**
 * TEST SUITE: EAKB STAGE 2E.2-B — ESPATA EDUCATIONAL KNOWLEDGE
 * Mandat: EAO-HO-2026-063
 * 
 * Verifikasi 12 Item Persyaratan Mandat:
 * 1. Seluruh data pengetahuan dapat dimuat.
 * 2. Struktur setiap entri valid.
 * 3. Metadata sumber tersedia.
 * 4. Filter kategori bekerja.
 * 5. Filter jenjang dan fase bekerja.
 * 6. Pemilihan topik relevan.
 * 7. Hasil tidak ditemukan ditangani dengan benar.
 * 8. Data yang belum terverifikasi tidak dinyatakan sebagai regulasi resmi.
 * 9. Tidak ada klaim fitur ESPATA dalam konten pendidikan (Pemisahan Kewenangan).
 * 10. Keluaran konsisten dan deterministik.
 * 11. Karakter Unicode dan Bahasa Indonesia diproses dengan benar.
 * 12. Konten tidak memuat data pribadi atau rahasia (Zero PII).
 */

require_once __DIR__ . '/../app/Libraries/EspataEducationalKnowledge.php';

use App\Libraries\EspataEducationalKnowledge;

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
echo "TEST SUITE EAO-HO-2026-063: EDUCATIONAL KNOWLEDGE (STAGE 2E.2-B)\n";
echo "====================================================================\n\n";

// -----------------------------------------------------------------------------
// 1. Seluruh data pengetahuan dapat dimuat
// -----------------------------------------------------------------------------
echo "--- 1. PEMUATAN BASIS PENGETAHUAN PENDIDIKAN ---\n";
$allEntries = EspataEducationalKnowledge::getAllEntries();
assertCondition(
    "Data dapat dimuat dan memuat minimal 10 cakupan topik esensial",
    is_array($allEntries) && count($allEntries) >= 10,
    "Total entri termuat: " . count($allEntries)
);

// -----------------------------------------------------------------------------
// 2. Struktur setiap entri valid
// -----------------------------------------------------------------------------
echo "\n--- 2. VALIDASI STRUKTUR & KONTRAK METADATA ENTRI ---\n";
$requiredKeys = [
    'id', 'title', 'category', 'relevant_levels', 'relevant_phases',
    'content', 'principles', 'source', 'source_status', 'keywords'
];

$allValidStructure = true;
$invalidReasons = [];

foreach ($allEntries as $id => $entry) {
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $entry)) {
            $allValidStructure = false;
            $invalidReasons[] = "Entri {$id} kehilangan key: {$k}";
        }
    }
    if ($entry['id'] !== $id) {
        $allValidStructure = false;
        $invalidReasons[] = "Key array ({$id}) berbeda dengan field id ({$entry['id']})";
    }
    if (!is_array($entry['relevant_levels']) || empty($entry['relevant_levels'])) {
        $allValidStructure = false;
        $invalidReasons[] = "Entri {$id} memiliki relevant_levels kosong";
    }
    if (!is_array($entry['relevant_phases']) || empty($entry['relevant_phases'])) {
        $allValidStructure = false;
        $invalidReasons[] = "Entri {$id} memiliki relevant_phases kosong";
    }
    if (!is_array($entry['principles']) || empty($entry['principles'])) {
        $allValidStructure = false;
        $invalidReasons[] = "Entri {$id} memiliki principles kosong";
    }
    if (!is_array($entry['keywords']) || empty($entry['keywords'])) {
        $allValidStructure = false;
        $invalidReasons[] = "Entri {$id} memiliki keywords kosong";
    }
}

assertCondition(
    "Seluruh entri memenuhi 10 field kontrak metadata wajib",
    $allValidStructure,
    $invalidReasons ? implode(', ', $invalidReasons) : "Semua " . count($allEntries) . " entri valid"
);

// -----------------------------------------------------------------------------
// 3. Metadata sumber tersedia & berstatus sah
// -----------------------------------------------------------------------------
echo "\n--- 3. METADATA SUMBER & STATUS VERIFIKASI SUMBER ---\n";
$validSourceStatuses = [
    EspataEducationalKnowledge::SOURCE_VERIFIED_REGULATION,
    EspataEducationalKnowledge::SOURCE_PEDAGOGICAL_PRINCIPLE,
    EspataEducationalKnowledge::SOURCE_UNVERIFIED,
];

$allSourcesValid = true;
foreach ($allEntries as $id => $entry) {
    if (empty($entry['source']) || !in_array($entry['source_status'], $validSourceStatuses, true)) {
        $allSourcesValid = false;
        break;
    }
}

assertCondition(
    "Setiap materi memiliki referensi sumber dan status verifikasi resmi",
    $allSourcesValid
);

// -----------------------------------------------------------------------------
// 4. Filter kategori bekerja
// -----------------------------------------------------------------------------
echo "\n--- 4. PENGUJIAN FILTER KATEGORI ---\n";
$categories = EspataEducationalKnowledge::getCategories();
assertCondition(
    "Daftar kategori unik tersedia dan terisi",
    is_array($categories) && count($categories) >= 5,
    "Jumlah kategori: " . count($categories)
);

$catAsesmen = EspataEducationalKnowledge::getByCategory('Asesmen & Penilaian');
$allAsesmenMatch = true;
foreach ($catAsesmen as $item) {
    if ($item['category'] !== 'Asesmen & Penilaian') {
        $allAsesmenMatch = false;
        break;
    }
}
assertCondition(
    "getByCategory('Asesmen & Penilaian') mengembalikan entri kategori yang tepat",
    $allAsesmenMatch && count($catAsesmen) >= 2,
    "Total entri asesmen: " . count($catAsesmen)
);

$catEmpty = EspataEducationalKnowledge::getByCategory('Kategori Fiktif Yang Tidak Pernah Ada');
assertCondition(
    "getByCategory() dengan kategori fiktif mengembalikan array kosong",
    is_array($catEmpty) && empty($catEmpty)
);

// -----------------------------------------------------------------------------
// 5. Filter jenjang dan fase bekerja
// -----------------------------------------------------------------------------
echo "\n--- 5. PENGUJIAN FILTER JENJANG & FASE ---\n";
$sdEntries = EspataEducationalKnowledge::filterByLevel('SD');
$allSdValid = true;
foreach ($sdEntries as $item) {
    if (!in_array('SD', $item['relevant_levels'], true)) {
        $allSdValid = false;
        break;
    }
}
assertCondition(
    "filterByLevel('SD') menyaring seluruh entri yang relevan untuk SD",
    $allSdValid && count($sdEntries) >= 8,
    "Total entri SD: " . count($sdEntries)
);

$smpEntries = EspataEducationalKnowledge::filterByLevel('SMP');
$allSmpValid = true;
foreach ($smpEntries as $item) {
    if (!in_array('SMP', $item['relevant_levels'], true)) {
        $allSmpValid = false;
        break;
    }
}
assertCondition(
    "filterByLevel('SMP') menyaring seluruh entri yang relevan untuk SMP",
    $allSmpValid && count($smpEntries) >= 8,
    "Total entri SMP: " . count($smpEntries)
);

$faseDEntries = EspataEducationalKnowledge::filterByPhase('Fase D');
$allFaseDValid = true;
foreach ($faseDEntries as $item) {
    $phasesLower = array_map('strtolower', $item['relevant_phases']);
    if (!in_array('fase d', $phasesLower, true)) {
        $allFaseDValid = false;
        break;
    }
}
assertCondition(
    "filterByPhase('Fase D') menyaring seluruh entri yang mencakup Fase D (SMP)",
    $allFaseDValid && count($faseDEntries) >= 6,
    "Total entri Fase D: " . count($faseDEntries)
);

// -----------------------------------------------------------------------------
// 6. Pemilihan topik relevan (Search & Scoring)
// -----------------------------------------------------------------------------
echo "\n--- 6. PEMILIHAN TOPIK RELEVAN (RELEVANCE SEARCH & GROUNDING) ---\n";
$testQueries = [
    [
        'query'         => 'Bagaimana cara menyusun asesmen diagnostik di awal pembelajaran?',
        'expected_id'   => 'edu_asesmen_tiga_jenis',
        'desc'          => 'Kueri Asesmen Diagnostik'
    ],
    [
        'query'         => 'Apa perbedaan capaian pembelajaran CP, tujuan pembelajaran TP, dan alur ATP?',
        'expected_id'   => 'edu_cp_tp_atp_konstruksi',
        'desc'          => 'Kueri Hierarki CP-TP-ATP'
    ],
    [
        'query'         => 'Bagaimana cara menentukan kriteria ketercapaian tujuan pembelajaran KKTP dengan rubrik?',
        'expected_id'   => 'edu_kktp_penentuan',
        'desc'          => 'Kueri Penentuan KKTP'
    ],
    [
        'query'         => 'Bagaimana strategi pembelajaran untuk murid kelas 1 dan 2 SD?',
        'expected_id'   => 'edu_fase_a_sd_awal',
        'desc'          => 'Kueri Karakteristik Fase A SD'
    ],
    [
        'query'         => 'Bagaimana menerapkan pembelajaran terdiferensiasi konten dan proses?',
        'expected_id'   => 'edu_diferensiasi_adaptif',
        'desc'          => 'Kueri Pembelajaran Terdiferensiasi'
    ],
    [
        'query'         => 'Bagaimana tata cara pelaksanaan remedial bagi siswa yang belum tuntas?',
        'expected_id'   => 'edu_remedial_dan_pengayaan',
        'desc'          => 'Kueri Remedial dan Pengayaan'
    ],
    [
        'query'         => 'Apa saja delapan dimensi profil lulusan untuk karakter murid?',
        'expected_id'   => 'edu_profil_lulusan_dimensi',
        'desc'          => 'Kueri Profil Lulusan Holistik'
    ],
];

foreach ($testQueries as $tq) {
    $results = EspataEducationalKnowledge::search($tq['query'], ['limit' => 3]);
    $matched = !empty($results) && $results[0]['id'] === $tq['expected_id'];
    assertCondition(
        "Search: {$tq['desc']}",
        $matched,
        $matched ? "Top match: {$results[0]['id']} (Skor: {$results[0]['_match_score']})" : "Mismatch or Empty"
    );
}

// -----------------------------------------------------------------------------
// 7. Penanganan kueri tidak ditemukan / tidak relevan
// -----------------------------------------------------------------------------
echo "\n--- 7. PENANGANAN HASIL TIDAK DITEMUKAN (NOT FOUND / FAIL-SAFE) ---\n";
$unrelatedQueries = [
    'resep membuat rendang padang asli yang empuk',
    'jadwal bioskop xxi hari ini dan harga tiket',
    'spesifikasi mobil listrik terbaru tahun ini',
];

$allNotFoundGraceful = true;
foreach ($unrelatedQueries as $uq) {
    $resSearch = EspataEducationalKnowledge::search($uq);
    $resCtx    = EspataEducationalKnowledge::buildEducationalContext($uq);
    if (!empty($resSearch) || $resCtx !== null) {
        $allNotFoundGraceful = false;
        break;
    }
}
assertCondition(
    "Kueri di luar dunia pendidikan mengembalikan array kosong / null tanpa error",
    $allNotFoundGraceful
);

$emptyFormatted = EspataEducationalKnowledge::formatContextForPrompt([]);
assertCondition(
    "formatContextForPrompt() dengan data kosong mengembalikan string kosong",
    $emptyFormatted === ''
);

// -----------------------------------------------------------------------------
// 8. Integritas status regulasi vs prinsip pedagogis
// -----------------------------------------------------------------------------
echo "\n--- 8. INTEGRITAS STATUS REGULASI VS PRINSIP PEDAGOGIS ---\n";
$regulationIntegrityValid = true;
$pedagogicalIntegrityValid = true;

foreach ($allEntries as $id => $entry) {
    if ($entry['source_status'] === EspataEducationalKnowledge::SOURCE_VERIFIED_REGULATION) {
        // Harus mereferensikan dokumen resmi pemerintah
        if (!preg_match('/(Permendikbudristek|Keputusan Kepala BSKAP|Panduan Pembelajaran dan Asesmen|Standar Penilaian)/i', $entry['source'])) {
            $regulationIntegrityValid = false;
            echo "  [WARN] Regulasi tidak memiliki rujukan resmi yang dikenal: {$entry['source']}\n";
        }
    }
    if ($entry['source_status'] === EspataEducationalKnowledge::SOURCE_PEDAGOGICAL_PRINCIPLE) {
        // Tidak boleh mengklaim nomor Permendikbud tunggal tanpa rujukan teori/panduan
        if (preg_match('/^Permendikbudristek No\. [0-9]+ Tahun [0-9]+$/i', trim($entry['source']))) {
            $pedagogicalIntegrityValid = false;
        }
    }
}

assertCondition(
    "Seluruh entri VERIFIED_REGULATION mereferensikan regulasi resmi yang terverifikasi",
    $regulationIntegrityValid
);
assertCondition(
    "Prinsip pedagogis dibedakan secara tegas dari ketentuan regulasi kaku",
    $pedagogicalIntegrityValid
);

// -----------------------------------------------------------------------------
// 9. Pemisahan Kewenangan (Zero ESPATA Feature Claims)
// -----------------------------------------------------------------------------
echo "\n--- 9. PEMISAHAN KEWENANGAN (ZERO ESPATA APPLICATION FEATURE CLAIMS) ---\n";
$forbiddenKeywords = [
    'feat_', 'espata', 'siakad', 'command palette', 'menu lms', 'klik tombol',
    '/admin/', 'role 1', 'role 3', 'allowed_roles', 'cbtku', 'bayaran', 'api_key'
];

$zeroFeatureClaims = true;
$detectedViolations = [];

foreach ($allEntries as $id => $entry) {
    $textCorpus = strtolower(
        $entry['title'] . ' ' .
        $entry['content'] . ' ' .
        implode(' ', $entry['principles']) . ' ' .
        implode(' ', $entry['keywords'])
    );

    foreach ($forbiddenKeywords as $fk) {
        if (strpos($textCorpus, $fk) !== false) {
            $zeroFeatureClaims = false;
            $detectedViolations[] = "Entri {$id} memuat kata terlarang '{$fk}'";
        }
    }
}

assertCondition(
    "Pemisahan Kewenangan: Konten pendidikan tidak mengklaim menu, tombol, atau fitur ESPATA",
    $zeroFeatureClaims,
    $detectedViolations ? implode('; ', $detectedViolations) : "100% bersih dari klaim fitur aplikasi"
);

// -----------------------------------------------------------------------------
// 10. Konsistensi & Determinisme Keluaran
// -----------------------------------------------------------------------------
echo "\n--- 10. KONSISTENSI & DETERMINISME KELUARAN ---\n";
$deterministicQuery = "Apa perbedaan modul ajar dan RPP dalam Kurikulum Merdeka?";
$run1 = EspataEducationalKnowledge::search($deterministicQuery);
$run2 = EspataEducationalKnowledge::search($deterministicQuery);
$run3 = EspataEducationalKnowledge::search($deterministicQuery);

$isDeterministic = ($run1 === $run2) && ($run2 === $run3) && !empty($run1);
assertCondition(
    "Pencarian deterministik menghasilkan skor, urutan, dan metadata 100% identik di setiap eksekusi",
    $isDeterministic,
    "Hasil 3x eksekusi berulang identik: " . ($isDeterministic ? "YES" : "NO")
);

$contextStruct = EspataEducationalKnowledge::buildEducationalContext($deterministicQuery);
$formattedPrompt = EspataEducationalKnowledge::formatContextForPrompt($contextStruct, 1200);

assertCondition(
    "formatContextForPrompt() menghasilkan blok XML <educational_guidance> yang terstruktur dan terikat batas panjang",
    strpos($formattedPrompt, '<educational_guidance>') !== false &&
    strpos($formattedPrompt, '</educational_guidance>') !== false &&
    mb_strlen($formattedPrompt) <= 1200,
    "Panjang karakter: " . mb_strlen($formattedPrompt)
);

// -----------------------------------------------------------------------------
// 11. Pemrosesan Karakter Unicode & Bahasa Indonesia
// -----------------------------------------------------------------------------
echo "\n--- 11. PEMROSESAN UNICODE & TATA BAHASA INDONESIA ---\n";
$unicodeQuery = "Bagaimana tata cara penyusunan langkah-langkah pembelajaran bermakna (student-centered) & umpan-balik formatif?";
$unicodeResults = EspataEducationalKnowledge::search($unicodeQuery);
assertCondition(
    "Kueri dengan karakter khusus (tanda hubung, kurung, ampersand, tanda tanya) diproses aman tanpa crash",
    !empty($unicodeResults) && $unicodeResults[0]['id'] === 'edu_kurikulum_merdeka_prinsip'
);

// -----------------------------------------------------------------------------
// 12. Bebas Data Pribadi atau Rahasia (Zero PII / Confidential Data)
// -----------------------------------------------------------------------------
echo "\n--- 12. AUDIT PRIVASI & DATA SENSITIF (ZERO PII) ---\n";
$piiPatterns = [
    '/\b[0-9]{16}\b/',                   // Format NIK/KTP
    '/\b[0-9]{10}\b/',                   // Format NISN
    '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/', // Email
    '/\b08[0-9]{8,11}\b/',               // Nomor HP Indonesia
    '/\b(password|secret|token|api_key)\b/i' // Kunci rahasia
];

$zeroPii = true;
$piiViolations = [];

foreach ($allEntries as $id => $entry) {
    $fullText = $entry['title'] . ' ' . $entry['content'] . ' ' . implode(' ', $entry['principles']);
    foreach ($piiPatterns as $pat) {
        if (preg_match($pat, $fullText)) {
            $zeroPii = false;
            $piiViolations[] = "Pola PII terdeteksi pada entri {$id}";
        }
    }
}

assertCondition(
    "Konten pendidikan bebas dari PII, nomor identitas, dan data rahasia",
    $zeroPii,
    $piiViolations ? implode(', ', $piiViolations) : "100% Bersih dari PII"
);

// -----------------------------------------------------------------------------
// RINGKASAN HASIL
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
echo "RINGKASAN HASIL PENGUJIAN EDUCATIONAL KNOWLEDGE:\n";
echo "TOTAL: " . ($passed + $failed) . " | PASSED: {$passed} | FAILED: {$failed}\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
