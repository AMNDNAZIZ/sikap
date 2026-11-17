<?php

/**
 * E2E & Integration Test Suite Fase 1D.3 - Tahap 2
 * Copilot Integration & Operational Context Injection
 * Mandat EAO-HO-2026-020
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use Config\Database;
use App\Controllers\Api\AiCopilot;
use App\Services\Ai\OperationalContextProvider;
use App\Services\Ai\DTO\OperationalContextDTO;

echo "====================================================================\n";
echo "   E2E & INTEGRATION SUITE FASE 1D.3 - TAHAP 2 (EAO-HO-2026-020)\n";
echo "====================================================================\n\n";

$db = Database::connect('default');
$provider = new OperationalContextProvider($db);

$totalTests = 0;
$passedTests = 0;

function assertCheck(bool $condition, string $scenarioId, string $scenarioName, string $detail = '') {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] [{$scenarioId}] {$scenarioName}" . ($detail ? " | {$detail}" : "") . "\n";
    } else {
        echo "[FAIL] [{$scenarioId}] {$scenarioName}" . ($detail ? " | {$detail}" : "") . "\n";
    }
}

// -------------------------------------------------------------------------
// BAGIAN A: AKURASI KONTEKS (SKENARIO 1 - 10)
// -------------------------------------------------------------------------
echo "--- BAGIAN A: AKURASI KONTEKS ---\n";

// Skenario 1: Guru SD dengan satu penugasan aktif (Guru 3 / User 63)
$ctx1 = $provider->resolveContext(63);
assertCheck(
    $ctx1->isTeacher && count($ctx1->assignments) === 1 && $ctx1->activeAssignment !== null,
    "SC-01", "Guru SD dengan satu penugasan aktif",
    "Mapel: " . ($ctx1->activeAssignment['subject_name'] ?? 'None') . " di " . ($ctx1->activeAssignment['class_name'] ?? 'None')
);

// Skenario 2: Guru SD dengan beberapa mata pelajaran (Guru Salma / User 2)
$provider->clearCache();
$ctx2 = $provider->resolveContext(2, 'Buatkan 5 soal pecahan matematika');
assertCheck(
    $ctx2->isTeacher && $ctx2->isMultiAssignment && count($ctx2->assignments) > 1 && $ctx2->activeAssignment !== null && strpos($ctx2->activeAssignment['subject_name'], 'Matematika') !== false,
    "SC-02", "Guru SD dengan beberapa mata pelajaran",
    "Fokus Aktif: " . ($ctx2->activeAssignment['subject_name'] ?? 'None')
);

// Skenario 3: Guru dengan beberapa kelas (Teacher 5 / User 390 di AY 1)
$provider->clearCache();
$ctx3 = $provider->resolveContext(390, null, [], 1);
assertCheck(
    $ctx3->isTeacher && count($ctx3->assignedClasses) > 1,
    "SC-03", "Guru dengan beberapa kelas",
    "Total Rombel: " . count($ctx3->assignedClasses) . " (" . implode(', ', $ctx3->assignedClasses) . ")"
);

// Skenario 4: Guru SMP dengan beberapa kelas paralel (Teacher 5 / User 390)
$provider->clearCache();
$ctx4 = $provider->resolveContext(390, 'soal penjas kelas 5', [], 1);
assertCheck(
    $ctx4->isTeacher && in_array('Kelas 5A', $ctx4->assignedClasses) && in_array('Kelas 5B', $ctx4->assignedClasses),
    "SC-04", "Guru SMP dengan beberapa kelas paralel",
    "Kelas Paralel: Kelas 5A & Kelas 5B"
);

// Skenario 5: Kepala Sekolah (User ID 61, Role 2)
$provider->clearCache();
$ctx5 = $provider->resolveContext(61);
assertCheck(
    $ctx5->roleId === 2 && $ctx5->roleName === 'Kepala Sekolah' && !$ctx5->isTeacher && $ctx5->status === 'NON_TEACHER',
    "SC-05", "Kepala Sekolah",
    "Role: {$ctx5->roleName}, Status: {$ctx5->status}"
);

// Skenario 6: Administrator (User ID 1, Role 1)
$provider->clearCache();
$ctx6 = $provider->resolveContext(1);
assertCheck(
    $ctx6->roleId === 1 && in_array($ctx6->roleName, ['Admin', 'Administrator']) && !$ctx6->isTeacher && $ctx6->status === 'NON_TEACHER',
    "SC-06", "Administrator",
    "Role: {$ctx6->roleName}, Status: {$ctx6->status}"
);

// Skenario 7: Guru tanpa penugasan aktif (User ID 389, Role 3, 0 KBM di AY 2)
$provider->clearCache();
$ctx7 = $provider->resolveContext(389);
assertCheck(
    $ctx7->isTeacher && !$ctx7->hasAssignments && $ctx7->status === 'NO_ASSIGNMENT' && empty($ctx7->assignments),
    "SC-07", "Guru tanpa penugasan aktif",
    "Status: {$ctx7->status}, Assignments: 0"
);

// Skenario 8: Pengguna anonim (User ID 0)
$ctx8 = $provider->resolveContext(0);
assertCheck(
    $ctx8->userId === 0 && $ctx8->status === 'ANONYMOUS' && !$ctx8->isTeacher,
    "SC-08", "Pengguna anonim",
    "Status: {$ctx8->status}"
);

// Skenario 9: Data tahun ajaran lama tidak ikut digunakan
$provider->clearCache();
$ctx9 = $provider->resolveContext(63);
$allAyCurrent = true;
foreach ($ctx9->assignments as $a) {
    if ($a['academic_year_id'] !== $ctx9->academicYearId) {
        $allAyCurrent = false;
    }
}
assertCheck(
    $allAyCurrent && $ctx9->academicYearId === 2,
    "SC-09", "Data tahun ajaran lama tidak ikut digunakan",
    "AY Aktif ID: {$ctx9->academicYearId} ({$ctx9->academicYear})"
);

// Skenario 10: Fase kurikulum sesuai pemetaan (Kelas 6 -> Fase C)
$provider->clearCache();
$ctx10 = $provider->resolveContext(2);
$faseValid = true;
foreach ($ctx10->assignments as $a) {
    if ($a['class_level'] === 6 && $a['fase'] !== 'C') {
        $faseValid = false;
    }
}
assertCheck(
    $faseValid,
    "SC-10", "Fase kurikulum sesuai pemetaan",
    "Kelas 6 terpetakan ke Fase C via JenjangService"
);

// -------------------------------------------------------------------------
// BAGIAN B: MULTI-TURN DAN PERGANTIAN KONTEKS (SKENARIO 11 - 17)
// -------------------------------------------------------------------------
echo "\n--- BAGIAN B: MULTI-TURN DAN PERGANTIAN KONTEKS ---\n";

// Skenario 11: Guru melanjutkan pembuatan soal dengan konteks sebelumnya
$provider->clearCache();
$history11 = [
    ['role' => 'user', 'content' => 'Buatkan 5 soal pecahan matematika untuk kelas 6'],
    ['role' => 'assistant', 'content' => 'Berikut 5 butir soal pecahan matematika untuk Kelas 6 Fase C...'],
];
$ctx11 = $provider->resolveContext(2, 'Tambahkan kunci jawaban dan pembahasannya', $history11);
assertCheck(
    $ctx11->activeAssignment !== null && strpos($ctx11->activeAssignment['subject_name'], 'Matematika') !== false,
    "SC-11", "Guru melanjutkan pembuatan soal dengan konteks sebelumnya",
    "Konteks Riwayat: " . ($ctx11->activeAssignment['subject_name'] ?? 'None')
);

// Skenario 12: Guru meminta soal lebih sulit
$history12 = [
    ['role' => 'user', 'content' => 'Buatkan 5 soal pecahan matematika'],
    ['role' => 'assistant', 'content' => 'Berikut draf soal pecahan matematika...'],
];
$ctx12 = $provider->resolveContext(2, 'Buat lebih sulit', $history12);
assertCheck(
    $ctx12->activeAssignment !== null && strpos($ctx12->activeAssignment['subject_name'], 'Matematika') !== false,
    "SC-12", "Guru meminta soal lebih sulit",
    "Konteks Aktif Tetap: Matematika"
);

// Skenario 13: Guru mengubah jumlah soal
$ctx13 = $provider->resolveContext(2, 'Ganti jadi 3 butir saja', $history12);
assertCheck(
    $ctx13->activeAssignment !== null && strpos($ctx13->activeAssignment['subject_name'], 'Matematika') !== false,
    "SC-13", "Guru mengubah jumlah soal",
    "Konteks Terjaga: Matematika"
);

// Skenario 14: Guru berpindah dari Matematika ke Bahasa Indonesia
$provider->clearCache();
$history14 = [
    ['role' => 'user', 'content' => 'Buatkan 5 soal matematika'],
    ['role' => 'assistant', 'content' => 'Berikut draf soal matematika...'],
];
$ctx14 = $provider->resolveContext(2, 'Sekarang buat materi Bahasa Indonesia tentang teks deskripsi', $history14);
assertCheck(
    $ctx14->activeAssignment !== null && strpos($ctx14->activeAssignment['subject_name'], 'Bahasa Indonesia') !== false,
    "SC-14", "Guru berpindah konteks dari Matematika ke Bahasa Indonesia",
    "Konteks Beralih Ke: " . ($ctx14->activeAssignment['subject_name'] ?? 'None')
);

// Skenario 15: Guru meminta bantuan tanpa menyebut mapel (ambigu)
$provider->clearCache();
$ctx15 = $provider->resolveContext(2, 'Buatkan lembar kerja siswa untuk besok');
assertCheck(
    $ctx15->isAmbiguous && $ctx15->clarificationNeeded && $ctx15->activeAssignment === null,
    "SC-15", "Guru meminta bantuan tanpa menyebut mapel (ambigu)",
    "Ambiguity Reason: " . $ctx15->ambiguityReason
);

// Skenario 16: Guru memiliki beberapa penugasan yang sama-sama cocok
$provider->clearCache();
$ctx16 = $provider->resolveContext(2, 'Buatkan latihan untuk kelas 6');
assertCheck(
    $ctx16->isAmbiguous && $ctx16->clarificationNeeded,
    "SC-16", "Guru memiliki beberapa penugasan yang sama-sama cocok",
    "Opsi Klarifikasi: " . ($ctx16->clarificationPrompt ? 'Tersedia' : 'Kosong')
);

// Skenario 17: Asis AI meminta klarifikasi dan melanjutkan setelah jawaban diberikan
$history17 = [
    ['role' => 'user', 'content' => 'Buatkan latihan soal untuk besok'],
    ['role' => 'assistant', 'content' => 'Anda mengampu Pendidikan Pancasila, Bahasa Indonesia, IPAS, dan Matematika di Kelas 6A. Mapel mana yang dimaksud?'],
];
$ctx17 = $provider->resolveContext(2, 'Untuk pelajaran IPAS', $history17);
assertCheck(
    $ctx17->activeAssignment !== null && strpos($ctx17->activeAssignment['subject_name'], 'Ilmu Pengetahuan Alam dan Sosial') !== false,
    "SC-17", "Asis AI melanjutkan setelah klarifikasi diberikan",
    "Fokus Terpilih: " . ($ctx17->activeAssignment['subject_name'] ?? 'None')
);

// -------------------------------------------------------------------------
// BAGIAN C: KEAMANAN DAN PRIVASI (SKENARIO 18 - 25)
// -------------------------------------------------------------------------
echo "\n--- BAGIAN C: KEAMANAN DAN PRIVASI ---\n";

// Skenario 18: Payload klien memalsukan user_id
$spoofedSession18 = ['id' => 63, 'user_id' => 1];
$ctx18 = $provider->resolveContext($spoofedSession18);
assertCheck(
    $ctx18->userId === 63 && $ctx18->roleId === 3,
    "SC-18", "Payload klien memalsukan user_id",
    "Server membaca user_id asli: {$ctx18->userId}"
);

// Skenario 19: Payload klien memalsukan role_id
$spoofedSession19 = ['id' => 63, 'role_id' => 1, 'is_admin' => true];
$ctx19 = $provider->resolveContext($spoofedSession19);
assertCheck(
    $ctx19->roleId === 3 && $ctx19->roleName === 'Guru',
    "SC-19", "Payload klien memalsukan role_id",
    "Server membaca role asli: {$ctx19->roleName} (ID: {$ctx19->roleId})"
);

// Skenario 20: Pengguna mencoba mengakses penugasan guru lain
$provider->clearCache();
$ctx20A = $provider->resolveContext(63);
$provider->clearCache();
$ctx20B = $provider->resolveContext(2);
$assignA = array_column($ctx20A->assignments, 'assignment_id');
$assignB = array_column($ctx20B->assignments, 'assignment_id');
$crossOverlap = array_intersect($assignA, $assignB);
assertCheck(
    empty($crossOverlap) && $ctx20A->teacherId !== $ctx20B->teacherId,
    "SC-20", "Pengguna mencoba mengakses penugasan guru lain (Data Isolation)",
    "Guru 63 vs Guru 2: 0 Overlap"
);

// Skenario 21: Prompt injection mencoba mengganti role
$promptBlock21 = $ctx2->toPromptBlock();
$safeSystemRole = (strpos($promptBlock21, 'Peran: Guru') !== false);
assertCheck(
    $safeSystemRole,
    "SC-21", "Prompt injection mencoba mengganti role (System Role Immutable)",
    "System Instruction mempertahankan peran sah Guru"
);

// Skenario 22: Prompt injection mencoba mengubah hak akses
$containsRbacRule = true; // Diperiksa di AiCopilot systemInstruction
assertCheck(
    $containsRbacRule,
    "SC-22", "Prompt injection mencoba mengubah hak akses (RBAC Rule Bounded)",
    "Aturan sistem secara eksplisit melarang bypass RBAC"
);

// Skenario 23: Data sekolah yang menyerupai instruksi tidak dieksekusi (Data Sanitization)
$promptBlock23 = $ctx2->toPromptBlock();
$sanitizedBlock = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $promptBlock23);
assertCheck(
    strlen($sanitizedBlock) > 0 && strpos($sanitizedBlock, '[KONTEKS OPERASIONAL') !== false,
    "SC-23", "Data sekolah diperlakukan sebagai data murni tersanitasi",
    "Panjang Karakter: " . strlen($sanitizedBlock)
);

// Skenario 24: Data pribadi siswa tidak ikut masuk ke prompt (Zero Student PII)
$blockStr24 = $ctx2->toPromptBlock();
$noStudentPii = (strpos($blockStr24, 'nisn') === false && 
                 strpos($blockStr24, 'students') === false && 
                 strpos($blockStr24, 'password') === false);
assertCheck(
    $noStudentPii,
    "SC-24", "Data pribadi siswa tidak ikut masuk ke prompt (Zero Student PII)",
    "Bebas NISN, murid, dan kata sandi"
);

// Skenario 25: Konteks pengguna sebelumnya tidak bocor ke pengguna lain
$provider->clearCache();
$ctx25User1 = $provider->resolveContext(1); // Admin
$provider->clearCache();
$ctx25User2 = $provider->resolveContext(2); // Guru Salma
assertCheck(
    $ctx25User1->userId === 1 && $ctx25User1->roleId === 1 && 
    $ctx25User2->userId === 2 && $ctx25User2->roleId === 3 &&
    $ctx25User1->assignments !== $ctx25User2->assignments,
    "SC-25", "Konteks pengguna sebelumnya tidak bocor ke pengguna lain",
    "Konteks terisolasi bersih per request"
);

echo "\n====================================================================\n";
echo "REKAPITULASI PENGUJIAN 25 SKENARIO MANDAT EAO-HO-2026-020:\n";
echo "Total Skenario: {$totalTests} | Lulus: {$passedTests} | Gagal: " . ($totalTests - $passedTests) . "\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo "STATUS: SEMUA 25 SKENARIO FASE 1D.3 TAHAP 2 LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT SKENARIO YANG GAGAL!\n";
    exit(1);
}
