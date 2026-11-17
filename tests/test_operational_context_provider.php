<?php

/**
 * Runner Pengujian Integrasi OperationalContextProvider (Fase 1D.3 - Tahap 1)
 * EAO-HO-2026-019
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use Config\Database;
use App\Services\Ai\OperationalContextProvider;
use App\Services\Ai\DTO\OperationalContextDTO;

echo "====================================================================\n";
echo "   PENGUJIAN OPERATIONAL CONTEXT FOUNDATION (MANDAT EAO-HO-2026-019)\n";
echo "====================================================================\n\n";

$db = Database::connect('default');
$provider = new OperationalContextProvider($db);

$totalTests = 0;
$passedTests = 0;

function assertCondition(bool $condition, string $scenarioName, string $detail = '') {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] {$scenarioName}" . ($detail ? " | {$detail}" : "") . "\n";
    } else {
        echo "[FAIL] {$scenarioName}" . ($detail ? " | {$detail}" : "") . "\n";
    }
}

// SKENARIO 1: Guru dengan 1 penugasan aktif (Guru Fallback: User ID 63, Teacher ID 3)
$ctx1 = $provider->resolveContext(63);
assertCondition(
    $ctx1 instanceof OperationalContextDTO && $ctx1->isTeacher && $ctx1->hasAssignments && count($ctx1->assignments) === 1,
    "SKENARIO 1: Guru dengan satu penugasan aktif",
    "Teacher ID: {$ctx1->teacherId}, Assignments: " . count($ctx1->assignments)
);

// SKENARIO 2A: Guru multi-mapel deteksi fokus kueri (User ID 2: Salma Wijaya, 4 mapel di Kelas 6A)
$provider->clearCache();
$ctx2 = $provider->resolveContext(2, 'Buatkan 5 butir soal pilihan ganda pecahan matematika');
assertCondition(
    $ctx2->isTeacher && $ctx2->isMultiAssignment && $ctx2->activeAssignment !== null && strpos($ctx2->activeAssignment['subject_name'], 'Matematika') !== false,
    "SKENARIO 2A: Guru multi-mapel deteksi fokus kueri",
    "Fokus Aktif: " . ($ctx2->activeAssignment['subject_name'] ?? 'None') . " di " . ($ctx2->activeAssignment['class_name'] ?? 'None')
);

// SKENARIO 2B: Guru multi-mapel kueri ambigu tanpa mapel
$provider->clearCache();
$ctx2b = $provider->resolveContext(2, 'Buatkan kisi-kisi asesmen sumatif untuk besok');
assertCondition(
    $ctx2b->isAmbiguous && $ctx2b->clarificationNeeded && $ctx2b->activeAssignment === null,
    "SKENARIO 2B: Guru multi-mapel kueri ambigu mendeteksi ambiguity",
    "Ambiguity Reason: " . $ctx2b->ambiguityReason
);

// SKENARIO 3: Guru dengan beberapa kelas berbeda (Teacher ID 5 / User ID 390 di AY 1)
$provider->clearCache();
$ctx3 = $provider->resolveContext(390, null, [], 1);
assertCondition(
    $ctx3->isTeacher && count($ctx3->assignedClasses) > 1,
    "SKENARIO 3: Guru dengan beberapa kelas berbeda",
    "Total Rombel: " . count($ctx3->assignedClasses) . " (" . implode(', ', $ctx3->assignedClasses) . ")"
);

// SKENARIO 4: Guru tanpa penugasan aktif (User ID 389: Afifah Noer Fauziah, Role 3 Guru, 0 KBM di AY 2)
$provider->clearCache();
$ctx4 = $provider->resolveContext(389);
assertCondition(
    $ctx4->isTeacher && !$ctx4->hasAssignments && $ctx4->status === 'NO_ASSIGNMENT' && empty($ctx4->assignments),
    "SKENARIO 4: Guru tanpa penugasan aktif pada tahun ajaran aktif",
    "Status: {$ctx4->status}, Assignments: " . count($ctx4->assignments)
);

// SKENARIO 5: Penugasan dari tahun ajaran lampau (AY 1) diabaikan pada sesi normal AY 2
$provider->clearCache();
$ctx5 = $provider->resolveContext(63);
$allAyMatch = true;
foreach ($ctx5->assignments as $a) {
    if ($a['academic_year_id'] !== $ctx5->academicYearId) {
        $allAyMatch = false;
    }
}
assertCondition(
    $allAyMatch && $ctx5->academicYearId === 2,
    "SKENARIO 5: Penugasan tahun ajaran lampau diabaikan",
    "Tahun Ajaran Aktif ID: {$ctx5->academicYearId} ({$ctx5->academicYear})"
);

// SKENARIO 6: Integritas relasi kelas & mapel
$relationsValid = true;
foreach ($ctx2->assignments as $a) {
    if (empty($a['class_name']) || empty($a['subject_name']) || $a['class_id'] <= 0 || $a['subject_id'] <= 0) {
        $relationsValid = false;
    }
}
assertCondition(
    $relationsValid,
    "SKENARIO 6: Integritas relasi kelas & mapel terjaga",
    "Setiap penugasan memiliki ID dan Nama sah"
);

// SKENARIO 7: Akun Kepala Sekolah (User ID 61, Role 2)
$provider->clearCache();
$ctx7 = $provider->resolveContext(61);
assertCondition(
    $ctx7->roleId === 2 && $ctx7->roleName === 'Kepala Sekolah' && !$ctx7->isTeacher && $ctx7->status === 'NON_TEACHER',
    "SKENARIO 7: Pengguna dengan role Kepala Sekolah",
    "Role: {$ctx7->roleName}, Status: {$ctx7->status}"
);

// SKENARIO 8: Akun Administrator (User ID 1, Role 1)
$provider->clearCache();
$ctx8 = $provider->resolveContext(1);
assertCondition(
    $ctx8->roleId === 1 && in_array($ctx8->roleName, ['Admin', 'Administrator']) && !$ctx8->isTeacher && $ctx8->status === 'NON_TEACHER',
    "SKENARIO 8: Pengguna dengan role Administrator",
    "Role: {$ctx8->roleName}, Status: {$ctx8->status}"
);

// SKENARIO 9: Sesi pengguna tidak tersedia (User ID 0 / Anonymous)
$ctx9 = $provider->resolveContext(0);
assertCondition(
    $ctx9->userId === 0 && $ctx9->status === 'ANONYMOUS' && !$ctx9->isTeacher,
    "SKENARIO 9: Sesi pengguna tidak tersedia (Anonymous)",
    "Status: {$ctx9->status}"
);

// SKENARIO 10: Pemalsuan role melalui payload ditolak server
$spoofedSession = ['id' => 63, 'role_id' => 1, 'username' => 'hacker'];
$ctx10 = $provider->resolveContext($spoofedSession);
assertCondition(
    $ctx10->roleId === 3 && $ctx10->roleName === 'Guru' && $ctx10->isTeacher,
    "SKENARIO 10: Pemalsuan payload sesi ditolak (Server Authoritative)",
    "Role Asli: {$ctx10->roleName} (ID: {$ctx10->roleId})"
);

// SKENARIO 11: Isolasi data penugasan antar-pengguna (Data Isolation)
$provider->clearCache();
$ctx11A = $provider->resolveContext(63);
$provider->clearCache();
$ctx11B = $provider->resolveContext(2);
$assignA = array_column($ctx11A->assignments, 'assignment_id');
$assignB = array_column($ctx11B->assignments, 'assignment_id');
$intersect = array_intersect($assignA, $assignB);
assertCondition(
    empty($intersect) && $ctx11A->teacherId !== $ctx11B->teacherId,
    "SKENARIO 11: Isolasi data penugasan antar-guru terbukti kedap",
    "Guru A: {$ctx11A->teacherId} vs Guru B: {$ctx11B->teacherId} (0 Overlap)"
);

// SKENARIO 12: Pasangan kelas dan mapel tidak digabungkan sembarangan
$pairIntegrity = true;
foreach ($ctx2->assignments as $a) {
    if (!isset($a['class_id'], $a['subject_id'], $a['fase'])) {
        $pairIntegrity = false;
    }
}
assertCondition(
    $pairIntegrity,
    "SKENARIO 12: Pasangan kelas dan mata pelajaran terjaga",
    "Setiap item memiliki kombinasi class_id, subject_id, dan fase"
);

// SKENARIO 13: Profil sekolah terisi aman
assertCondition(
    !empty($ctx1->schoolName) && $ctx1->schoolLevel > 0 && !empty($ctx1->schoolLevelLabel),
    "SKENARIO 13: Profil sekolah & jenjang terisi lengkap",
    "Sekolah: {$ctx1->schoolName} ({$ctx1->schoolLevelLabel})"
);

// SKENARIO 14: Fase kurikulum berhasil dipetakan secara sah
$faseMapped = true;
foreach ($ctx2->assignments as $a) {
    if ($a['class_level'] === 6 && $a['fase'] !== 'C') {
        $faseMapped = false;
    }
}
assertCondition(
    $faseMapped,
    "SKENARIO 14: Fase Kurikulum Merdeka dipetakan presisi",
    "Kelas 6 terpetakan ke Fase C via JenjangService"
);

// SKENARIO 15: Penentuan semester menggunakan kalender sah / fallback kalender
assertCondition(
    in_array($ctx1->semester, [1, 2]) && in_array($ctx1->semesterSource, ['academic_calendar', 'calendar_estimate_fallback']),
    "SKENARIO 15: Penentuan semester menggunakan sumber sah / fallback eksplisit",
    "Semester: {$ctx1->semester} ({$ctx1->semesterLabel}) | Sumber: {$ctx1->semesterSource}"
);

// SKENARIO 16: Zero Student PII & Zero Secret
$arr16 = $ctx2->toArray();
$json16 = json_encode($arr16);
$zeroSecret = (strpos($json16, 'password') === false && strpos($json16, 'gemini_api_key') === false && strpos($json16, 'gsk_') === false);
$zeroPii = (strpos($json16, 'nisn') === false && strpos($json16, 'students') === false);
assertCondition(
    $zeroSecret && $zeroPii,
    "SKENARIO 16: Zero Student PII & Zero Secret dalam DTO",
    "Bebas password, API key, NISN, dan data murid"
);

// SKENARIO 17: Request-scoped cache mengembalikan instans identik
$provider->clearCache();
$cInst1 = $provider->resolveContext(2, 'kueri cache');
$cInst2 = $provider->resolveContext(2, 'kueri cache');
assertCondition(
    $cInst1 === $cInst2,
    "SKENARIO 17: Request-scoped cache mengembalikan objek instan",
    "Instans memori sama persis (0 query ulang)"
);

// SKENARIO 18: Blok prompt sistem terbentuk rapi dan padat
$promptBlock = $ctx2->toPromptBlock();
$blockValid = (strpos($promptBlock, '[KONTEKS OPERASIONAL PENGGUNA & SEKOLAH]') !== false &&
               strpos($promptBlock, 'Satuan Pendidikan:') !== false &&
               strpos($promptBlock, 'Tahun Ajaran:') !== false);
assertCondition(
    $blockValid,
    "SKENARIO 18: Blok prompt sistem terformat rapi dan terenkapsulasi",
    "Panjang Karakter Blok: " . strlen($promptBlock) . " chars"
);

echo "\n--------------------------------------------------------------------\n";
echo "HASIL AKHIR PENGUJIAN OPERATIONAL CONTEXT: {$passedTests} / {$totalTests} PASS\n";
echo "--------------------------------------------------------------------\n";

if ($passedTests === $totalTests) {
    echo "STATUS: SEMUA SKENARIO OPERATIONAL CONTEXT FOUNDATION LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT SKENARIO YANG GAGAL!\n";
    exit(1);
}
