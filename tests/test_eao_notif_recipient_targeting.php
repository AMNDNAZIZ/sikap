<?php
/**
 * Test Suite EAO-NOTIF-IMPLEMENT-001: Notification Recipient Targeting & Security Verification
 *
 * Menguji secara komprehensif:
 * Bagian 1: Verifikasi Kode Sumber Statis (Audit Baris Kode Kontroler)
 *   - Penghapusan broadcast notify_by_role([1, 3])
 *   - Penghapusan kebocoran parameter topik konseling
 *   - Penerapan transaksi database sebelum notifikasi di Belajar.php & Tugas.php
 *   - Eliminasi total fallback created_by
 * Bagian 2: Pengujian Unit & Logika Resolusi (NotificationRecipientResolver)
 *   - Skenario Konseling SD (dengan BK, tanpa BK, wali kelas hilang, unassigned BK, no recipient)
 *   - Skenario Penugasan Guru (Guru Utama, Pendamping, Pengganti, created_by admin, invalid teacher_id)
 *   - Skenario Tugas LMS Opsi A (kelas aktif, materi dipublish vs tidak, mutasi sebelum submit)
 *   - Skenario Tugas Reguler (prioritas penugasan aktif vs fallback terkendali)
 * Bagian 3: Integritas Lisensi & Proteksi
 */

define('APPPATH', dirname(__DIR__) . '/app/');
define('SYSTEMPATH', dirname(__DIR__) . '/system/');
define('ROOTPATH', dirname(__DIR__) . '/');
define('WRITEPATH', dirname(__DIR__) . '/writable/');
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Bootstrap CodeIgniter functions
require_once SYSTEMPATH . 'Common.php';
require_once APPPATH . 'Common.php';
require_once APPPATH . 'Libraries/SecurityGuard.php';
require_once APPPATH . 'Libraries/NotificationRecipientResolver.php';

use App\Libraries\NotificationRecipientResolver;

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = [];

function assert_test(string $name, bool $condition, string $details = ''): void {
    global $totalAssertions, $passedAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo "  [PASS] {$name}\n";
    } else {
        $failedAssertions[] = "{$name} - {$details}";
        echo "  [FAIL] {$name} - {$details}\n";
    }
}

echo "====================================================================\n";
echo "TEST SUITE EAO-NOTIF-IMPLEMENT-001: RECIPIENT TARGETING & SECURITY\n";
echo "====================================================================\n\n";

// =========================================================================
// BAGIAN 1: VERIFIKASI STATIS KODE SUMBER CONTROLLER
// =========================================================================
echo "--- 1. AUDIT STATIS KODE SUMBER CONTROLLER ---\n";

// 1.1 Siswa/Counseling.php
$counselingContent = file_get_contents(APPPATH . 'Controllers/Siswa/Counseling.php');
assert_test(
    "Counseling.php: Bebas dari broadcast notify_by_role([1, 3])",
    strpos($counselingContent, 'notify_by_role') === false,
    "Masih ditemukan pemanggilan notify_by_role di Counseling.php"
);
assert_test(
    "Counseling.php: Parameter topik konseling diisolasi (tidak ada Topik: {\$data['topic']})",
    strpos($counselingContent, "Topik: {\$data['topic']}") === false,
    "Masih ditemukan kebocoran data['topic'] pada pesan notifikasi Counseling.php"
);
assert_test(
    "Counseling.php: Menggunakan NotificationRecipientResolver",
    strpos($counselingContent, 'NotificationRecipientResolver') !== false &&
    strpos($counselingContent, 'resolveCounselingRecipients') !== false,
    "Counseling.php belum menggunakan NotificationRecipientResolver::resolveCounselingRecipients"
);
assert_test(
    "Counseling.php: Memiliki fail-safe eskalasi konfigurasi Admin tanpa identitas siswa",
    strpos($counselingContent, 'Penugasan Pembina Siswa Belum Diatur') !== false,
    "Counseling.php belum memiliki pesan eskalasi konfigurasi admin"
);

// 1.2 Siswa/Belajar.php
$belajarContent = file_get_contents(APPPATH . 'Controllers/Siswa/Belajar.php');
assert_test(
    "Belajar.php: Menggunakan transaksi database (transStart & transComplete)",
    strpos($belajarContent, 'transStart()') !== false &&
    strpos($belajarContent, 'transComplete()') !== false &&
    strpos($belajarContent, 'transStatus()') !== false,
    "Belajar.php belum menerapkan transaksi database lengkap"
);
assert_test(
    "Belajar.php: Menggunakan NotificationRecipientResolver::resolveLmsTaskRecipients",
    strpos($belajarContent, 'NotificationRecipientResolver') !== false &&
    strpos($belajarContent, 'resolveLmsTaskRecipients') !== false,
    "Belajar.php belum memanggil resolveLmsTaskRecipients"
);
assert_test(
    "Belajar.php: Eliminasi total pengiriman langsung ke created_by",
    strpos($belajarContent, '$teacherUserId = (int) ($taskRow[\'created_by\'] ?? 0);') === false,
    "Masih ditemukan routing langsung ke taskRow['created_by'] di Belajar.php"
);

// 1.3 Siswa/Tugas.php
$tugasContent = file_get_contents(APPPATH . 'Controllers/Siswa/Tugas.php');
assert_test(
    "Tugas.php: Menggunakan transaksi database (transStart & transComplete)",
    strpos($tugasContent, 'transStart()') !== false &&
    strpos($tugasContent, 'transComplete()') !== false &&
    strpos($tugasContent, 'transStatus()') !== false,
    "Tugas.php belum menerapkan transaksi database lengkap"
);
assert_test(
    "Tugas.php: Menggunakan NotificationRecipientResolver::resolveRegularTaskRecipients",
    strpos($tugasContent, 'NotificationRecipientResolver') !== false &&
    strpos($tugasContent, 'resolveRegularTaskRecipients') !== false,
    "Tugas.php belum memanggil resolveRegularTaskRecipients"
);
assert_test(
    "Tugas.php: Eliminasi total prioritas created_by",
    strpos($tugasContent, '$teacherUserId = (int) ($tugas[\'created_by\'] ?? 0);') === false,
    "Masih ditemukan prioritas created_by di Tugas.php"
);

// =========================================================================
// BAGIAN 2: MOCK DATABASE BUILDER & PENGUJIAN LOGIKA RESOLVER
// =========================================================================
echo "\n--- 2. PENGUJIAN LOGIKA RESOLVER (UNIT & DETERMINISTIC SCENARIOS) ---\n";

/**
 * Mock Query Builder dan Connection untuk menguji NotificationRecipientResolver
 * secara deterministik tanpa dependensi jaringan atau socket TCP eksternal.
 */
class MockQueryBuilder {
    protected array $data;
    protected array $where = [];
    protected array $whereIn = [];
    protected array $select = [];

    public function __construct(array $tableData) {
        $this->data = $tableData;
    }

    public function select($fields): self {
        if (is_string($fields)) {
            $this->select = array_map('trim', explode(',', $fields));
        }
        return $this;
    }

    public function where($key, $value = null): self {
        if ($value === null && is_string($key)) {
            // raw SQL expression like '(scheduled_at IS NULL OR scheduled_at <= NOW())'
            return $this;
        }
        $this->where[$key] = $value;
        return $this;
    }

    public function whereIn(string $key, array $values): self {
        $this->whereIn[$key] = $values;
        return $this;
    }

    protected function filterData(): array {
        $filtered = [];
        foreach ($this->data as $row) {
            $match = true;
            foreach ($this->where as $k => $v) {
                if (!isset($row[$k]) || (string)$row[$k] !== (string)$v) {
                    $match = false;
                    break;
                }
            }
            if ($match && !empty($this->whereIn)) {
                foreach ($this->whereIn as $k => $vals) {
                    if (!isset($row[$k]) || !in_array($row[$k], $vals)) {
                        $match = false;
                        break;
                    }
                }
            }
            if ($match) {
                if (!empty($this->select)) {
                    $proj = [];
                    foreach ($this->select as $col) {
                        $proj[$col] = $row[$col] ?? null;
                    }
                    $filtered[] = $proj;
                } else {
                    $filtered[] = $row;
                }
            }
        }
        return $filtered;
    }

    public function countAllResults(): int {
        return count($this->filterData());
    }

    public function get(): self {
        return $this;
    }

    public function getRowArray(): ?array {
        $rows = $this->filterData();
        return !empty($rows) ? reset($rows) : null;
    }

    public function getResultArray(): array {
        return $this->filterData();
    }
}

class MockDbConnection {
    public array $tables = [];

    public function __construct(array $dataset = []) {
        $this->tables = $dataset;
    }

    public function tableExists(string $tableName): bool {
        return isset($this->tables[$tableName]);
    }

    public function table($tableName): MockQueryBuilder {
        $data = $this->tables[$tableName] ?? [];
        return new MockQueryBuilder($data);
    }

    // Dummy interface implementations
    public function initialize(): bool { return true; }
    public function connect(bool $persistent = false) { return true; }
    public function reconnect(): void {}
    public function close(): void {}
    public function persistentConnect() { return true; }
    public function getConnection(?string $alias = null) { return null; }
    public function query(string $sql, $binds = null, bool $setArgs = true, string $queryClass = '') { return null; }
    public function simpleQuery(string $sql) { return null; }
    public function transOff(): void {}
    public function transBegin(): bool { return true; }
    public function transCommit(): bool { return true; }
    public function transRollback(): bool { return true; }
    public function transStart(bool $testMode = false): bool { return true; }
    public function transComplete(): bool { return true; }
    public function transStrict(bool $mode = true): self { return $this; }
    public function transStatus(): bool { return true; }
    public function error(): array { return []; }
    public function insertID(): int { return 1; }
    public function getPrefix(): string { return ''; }
    public function setPrefix(string $prefix = ''): string { return ''; }
    public function getDatabase(): string { return 'mock'; }
    public function setDatabase(string $databaseName): bool { return true; }
    public function getPlatform(): string { return 'mock'; }
    public function getVersion(): string { return '1.0'; }
    public function isWriteType($sql): bool { return false; }
    public function escape($str) { return $str; }
    public function escapeString($str, bool $like = false) { return $str; }
    public function escapeLikeString($str) { return $str; }
    public function callFunction(string $functionName, ...$params) { return null; }
    public function getFieldData(string $table): array { return []; }
    public function getIndexData(string $table): array { return []; }
    public function getForeignKeyData(string $table): array { return []; }
    public function disableForeignKeyChecks(): bool { return true; }
    public function enableForeignKeyChecks(): bool { return true; }
    public function pretend(bool $pretend = true): self { return $this; }
    public function resetDataCache(): self { return $this; }
    public function getLastQuery() { return null; }
    public function getQueryCount(): int { return 0; }
}

// Persiapkan data mock terintegrasi untuk pengujian skenario
$dataset = [
    'academic_years' => [
        ['id' => 1, 'year' => '2026/2027', 'is_active' => 1],
        ['id' => 2, 'year' => '2025/2026', 'is_active' => 0],
    ],
    'classes' => [
        ['id' => 10, 'name' => '6A', 'teacher_id' => 1, 'is_active' => 1, 'academic_year_id' => 1], // Wali: Guru 1
        ['id' => 20, 'name' => '6B', 'teacher_id' => 2, 'is_active' => 1, 'academic_year_id' => 1], // Wali: Guru 2
        ['id' => 30, 'name' => '7A', 'teacher_id' => 3, 'is_active' => 1, 'academic_year_id' => 1], // Wali: Guru 3
        ['id' => 40, 'name' => 'TanpaWali', 'teacher_id' => null, 'is_active' => 1, 'academic_year_id' => 1],
    ],
    'teachers' => [
        ['id' => 1, 'name' => 'Guru A (Wali 6A)', 'user_id' => 101, 'is_active' => 1],
        ['id' => 2, 'name' => 'Guru B (Wali 6B)', 'user_id' => 102, 'is_active' => 1],
        ['id' => 3, 'name' => 'Guru C (Wali 7A)', 'user_id' => 103, 'is_active' => 1],
        ['id' => 4, 'name' => 'Guru D (Pengampu Pendamping)', 'user_id' => 104, 'is_active' => 1],
        ['id' => 5, 'name' => 'Guru E (BK 7A)', 'user_id' => 105, 'is_active' => 1],
        ['id' => 6, 'name' => 'Guru F (BK 9C)', 'user_id' => 106, 'is_active' => 1],
        ['id' => 7, 'name' => 'Guru G (Pengganti)', 'user_id' => 107, 'is_active' => 1],
    ],
    'users' => [
        ['id' => 1, 'username' => 'admin', 'role_id' => 1, 'related_id' => null, 'is_active' => 1],
        ['id' => 101, 'username' => 'guru1', 'role_id' => 3, 'related_id' => 1, 'is_active' => 1],
        ['id' => 102, 'username' => 'guru2', 'role_id' => 3, 'related_id' => 2, 'is_active' => 1],
        ['id' => 103, 'username' => 'guru3', 'role_id' => 3, 'related_id' => 3, 'is_active' => 1],
        ['id' => 104, 'username' => 'guru4', 'role_id' => 3, 'related_id' => 4, 'is_active' => 1],
        ['id' => 105, 'username' => 'guru5', 'role_id' => 3, 'related_id' => 5, 'is_active' => 1],
        ['id' => 106, 'username' => 'guru6', 'role_id' => 3, 'related_id' => 6, 'is_active' => 1],
        ['id' => 107, 'username' => 'guru7', 'role_id' => 3, 'related_id' => 7, 'is_active' => 1],
    ],
    'student_records' => [
        ['id' => 1, 'student_id' => 501, 'class_id' => 10, 'academic_year_id' => 1, 'status' => 'aktif'], // Siswa 501 di 6A
        ['id' => 2, 'student_id' => 502, 'class_id' => 20, 'academic_year_id' => 1, 'status' => 'aktif'], // Siswa 502 di 6B
        ['id' => 3, 'student_id' => 503, 'class_id' => 30, 'academic_year_id' => 1, 'status' => 'aktif'], // Siswa 503 di 7A
        ['id' => 4, 'student_id' => 504, 'class_id' => 40, 'academic_year_id' => 1, 'status' => 'aktif'], // Siswa 504 di TanpaWali
    ],
    'bk_counselor_assignments' => [
        ['id' => 1, 'teacher_id' => 5, 'class_id' => 30, 'year_id' => 1], // Guru 5 diplot BK di 7A
        ['id' => 2, 'teacher_id' => 6, 'class_id' => 99, 'year_id' => 1], // Guru 6 diplot BK di 9C (kelas 99)
    ],
    'teaching_assignments' => [
        ['id' => 1, 'teacher_id' => 1, 'class_id' => 10, 'subject_id' => 100, 'academic_year_id' => 1, 'role' => 'utama'],
        ['id' => 2, 'teacher_id' => 4, 'class_id' => 10, 'subject_id' => 100, 'academic_year_id' => 1, 'role' => 'pendamping'],
        ['id' => 3, 'teacher_id' => 7, 'class_id' => 10, 'subject_id' => 100, 'academic_year_id' => 1, 'role' => 'pengganti'],
        ['id' => 4, 'teacher_id' => 2, 'class_id' => 20, 'subject_id' => 100, 'academic_year_id' => 1, 'role' => 'utama'],
    ],
    'material_segments' => [
        ['id' => 1001, 'material_id' => 2001, 'has_task' => 1],
    ],
    'subject_materials' => [
        ['id' => 2001, 'subject_id' => 100],
    ],
    'subject_material_publishes' => [
        ['id' => 1, 'material_id' => 2001, 'class_id' => 10, 'is_active' => 1, 'scheduled_at' => null],
        ['id' => 2, 'material_id' => 2001, 'class_id' => 20, 'is_active' => 1, 'scheduled_at' => null],
    ],
];

$mockDb = new MockDbConnection($dataset);
$resolver = new NotificationRecipientResolver($mockDb);

// -------------------------------------------------------------------------
// 2.1 PENGUJIAN SKENARIO KONSELING (BK)
// -------------------------------------------------------------------------
echo "\n--- 2.1 SKENARIO KONSELING BK ---\n";

// SCN-BK-01: SD tanpa guru BK khusus -> hanya Wali Kelas (Guru 1 / User 101)
$bk01 = $resolver->resolveCounselingRecipients(501);
assert_test(
    "SCN-BK-01: SD tanpa Guru BK -> hanya Wali Kelas 6A (User 101)",
    $bk01 === [101],
    "Expected [101], got: " . json_encode($bk01)
);

// SCN-BK-02: Sekolah dengan guru BK -> Wali Kelas 7A (User 103) DAN Guru BK (User 105)
$bk02 = $resolver->resolveCounselingRecipients(503);
sort($bk02);
assert_test(
    "SCN-BK-02: Kelas 7A dengan Guru BK -> Wali Kelas (103) & Guru BK (105)",
    $bk02 === [103, 105],
    "Expected [103, 105], got: " . json_encode($bk02)
);

// SCN-BK-03: Wali kelas tidak ditemukan / NULL -> hanya Guru BK jika ada
// Ubah kelas 30 teacher_id jadi null
$datasetNoHomeroom = $dataset;
$datasetNoHomeroom['classes'][2]['teacher_id'] = null;
$resolverNoHomeroom = new NotificationRecipientResolver(new MockDbConnection($datasetNoHomeroom));
$bk03 = $resolverNoHomeroom->resolveCounselingRecipients(503);
assert_test(
    "SCN-BK-03: Wali kelas tidak ada -> hanya Guru BK (User 105)",
    $bk03 === [105],
    "Expected [105], got: " . json_encode($bk03)
);

// SCN-BK-04: Guru BK unassigned (Guru 6 / User 106) tidak menerima konseling kelas 7A
assert_test(
    "SCN-BK-04: Guru BK kelas lain (User 106) tidak ada di penerima kelas 7A",
    !in_array(106, $bk02),
    "Guru BK kelas lain (106) bocor menerima notifikasi"
);

// SCN-BK-05: Tidak ada penerima valid (Kelas tanpa wali & tanpa BK) -> mengembalikan []
$bk05 = $resolver->resolveCounselingRecipients(504);
assert_test(
    "SCN-BK-05: Kelas tanpa pembina -> resolver return empty [] untuk memicu peringatan admin",
    empty($bk05),
    "Expected [], got: " . json_encode($bk05)
);

// SCN-BK-05b: Peringatan konfigurasi admin menghasilkan ID admin (User 1)
$adminIds = $resolver->getAdminUserIds();
assert_test(
    "SCN-BK-05b: getAdminUserIds() menghasilkan daftar admin aktif (User 1)",
    $adminIds === [1],
    "Expected [1], got: " . json_encode($adminIds)
);

// SCN-BK-07: Guru kelas lain (User 102) tidak menerima konseling siswa 6A
assert_test(
    "SCN-BK-07: Guru kelas 6B (User 102) tidak menerima notifikasi siswa 6A",
    !in_array(102, $bk01),
    "Guru kelas 6B bocor menerima notifikasi 6A"
);

// -------------------------------------------------------------------------
// 2.2 PENGUJIAN SKENARIO PENUGASAN GURU MATA PELAJARAN
// -------------------------------------------------------------------------
echo "\n--- 2.2 SKENARIO PENUGASAN GURU MAPEL ---\n";

// SCN-TA-01 & TA-02: Kelas 10 Mapel 100 memiliki Guru Utama (101) & Pendamping (104)
$ta01 = $resolver->resolveSubjectTeacherRecipients(10, 100);
sort($ta01);
assert_test(
    "SCN-TA-02: Guru Utama (101) & Guru Pendamping (104) keduanya menerima",
    $ta01 === [101, 104],
    "Expected [101, 104], got: " . json_encode($ta01)
);

// SCN-TA-03: Guru Pengganti ('pengganti' -> Guru 7 / User 107) tidak otomatis menerima
assert_test(
    "SCN-TA-03: Guru Pengganti (User 107) dikecualikan dari penerima otomatis",
    !in_array(107, $ta01),
    "Guru Pengganti (107) bocor menerima notifikasi"
);

// SCN-TA-04: Penugasan tidak ada (kelas 30 mapel 999) -> return []
$ta04 = $resolver->resolveSubjectTeacherRecipients(30, 999);
assert_test(
    "SCN-TA-04: Penugasan mapel tidak ada -> return empty []",
    empty($ta04),
    "Expected [], got: " . json_encode($ta04)
);

// SCN-TA-05 & TA-06: Tugas Reguler dengan created_by Admin & teacher_id tidak valid
$taskRowRegular = [
    'id' => 99,
    'class_id' => 10,
    'subject_id' => 100,
    'created_by' => 1, // Admin!
    'teacher_id' => 999, // Guru fiktif tidak valid!
];
$regRecipients = $resolver->resolveRegularTaskRecipients(501, $taskRowRegular);
sort($regRecipients);
assert_test(
    "SCN-TA-05: created_by Admin (User 1) diabaikan; dialihkan ke Guru Pengampu sah (101, 104)",
    $regRecipients === [101, 104] && !in_array(1, $regRecipients),
    "Expected [101, 104] without 1, got: " . json_encode($regRecipients)
);

// SCN-TA-07: Tugas reguler tanpa penugasan sah & created_by admin -> return []
$taskRowNoAssign = [
    'id' => 99,
    'class_id' => 40,
    'subject_id' => 999,
    'created_by' => 1,
    'teacher_id' => null,
];
$regNoAssign = $resolver->resolveRegularTaskRecipients(504, $taskRowNoAssign);
assert_test(
    "SCN-TA-07: Tidak ada penugasan sah -> Fail-Silent (return [])",
    empty($regNoAssign),
    "Expected [], got: " . json_encode($regNoAssign)
);

// -------------------------------------------------------------------------
// 2.3 PENGUJIAN SKENARIO TUGAS LMS (OPSI A TERKUNCI)
// -------------------------------------------------------------------------
echo "\n--- 2.3 SKENARIO TUGAS LMS OPSI A ---\n";

$taskRowLms = [
    'id' => 888,
    'class_id' => null, // Sesuai MakeTugasClassIdNullable
    'segment_id' => 1001,
    'material_id' => 2001,
    'subject_id' => 100,
    'created_by' => 1, // Dibuat Admin!
];

// SCN-LMS-01: Siswa 501 (aktif di 6A) submit tugas LMS
$lms01 = $resolver->resolveLmsTaskRecipients(501, $taskRowLms);
sort($lms01);
assert_test(
    "SCN-LMS-01: Siswa 6A submit tugas bersama -> Guru 6A (101, 104) menerima",
    $lms01 === [101, 104],
    "Expected [101, 104], got: " . json_encode($lms01)
);

// SCN-LMS-05: Guru kelas 6B (User 102) tidak menerima notifikasi dari siswa 6A
assert_test(
    "SCN-LMS-05: Guru kelas 6B (User 102) tidak menerima notifikasi submit siswa 6A",
    !in_array(102, $lms01),
    "Guru kelas 6B bocor menerima tugas siswa 6A"
);

// SCN-LMS-02: Materi tidak dipublikasikan untuk kelas siswa (misal Siswa 503 di 7A)
// Materi 2001 hanya dipublish ke kelas 10 (6A) dan 20 (6B), TIDAK ke 30 (7A)
$lms02 = $resolver->resolveLmsTaskRecipients(503, $taskRowLms);
assert_test(
    "SCN-LMS-02: Materi tidak dipublish ke kelas 7A -> resolver return empty []",
    empty($lms02),
    "Expected [], got: " . json_encode($lms02)
);

// SCN-LMS-04: Siswa mutasi dari 6A ke 6B sebelum submit (Opsi A)
// Ubah kelas Siswa 501 menjadi kelas 20 (6B)
$datasetMutated = $dataset;
$datasetMutated['student_records'][0]['class_id'] = 20; // 501 pindah ke 6B
$resolverMutated = new NotificationRecipientResolver(new MockDbConnection($datasetMutated));

$lms04 = $resolverMutated->resolveLmsTaskRecipients(501, $taskRowLms);
assert_test(
    "SCN-LMS-04: Siswa 501 pindah ke 6B -> Penerima adalah Guru Pengampu 6B (User 102)",
    $lms04 === [102],
    "Expected [102], got: " . json_encode($lms04)
);
assert_test(
    "SCN-LMS-04b: Guru kelas lama (6A - User 101, 104) TIDAK menerima notifikasi",
    !in_array(101, $lms04) && !in_array(104, $lms04),
    "Guru kelas lama bocor menerima notifikasi setelah siswa mutasi"
);

// =========================================================================
// BAGIAN 3: VERIFIKASI KEAMANAN, LISENSI, & INTEGRITAS SISTEM
// =========================================================================
echo "\n--- 3. VERIFIKASI KEAMANAN, LISENSI, & INTEGRITAS SISTEM ---\n";

// 3.1 SecurityGuard::verifyCriticalFiles()
$guardResult = \App\Libraries\SecurityGuard::verifyCriticalFiles();
assert_test(
    "SecurityGuard::verifyCriticalFiles() = true (SEC_PASS:1)",
    $guardResult === true,
    "Expected true, got: " . var_export($guardResult, true)
);

// 3.2 Sub-aplikasi bayaran dan cbtku tetap absent
assert_test(
    "Direktori bayaran/ tetap ABSENT",
    !is_dir(ROOTPATH . 'bayaran'),
    "Direktori bayaran/ ditemukan!"
);
assert_test(
    "Direktori cbtku/ tetap ABSENT",
    !is_dir(ROOTPATH . 'cbtku'),
    "Direktori cbtku/ ditemukan!"
);

echo "\n====================================================================\n";
echo "HASIL PENGUJIAN: {$passedAssertions} / {$totalAssertions} ASSERTIONS BERHASIL\n";
if (!empty($failedAssertions)) {
    echo "GAGAL: " . count($failedAssertions) . " ASSERTIONS:\n";
    foreach ($failedAssertions as $fa) {
        echo "  - {$fa}\n";
    }
    exit(1);
} else {
    echo "SELURUH SKENARIO UJI LULUS 100% (ZERO REGRESSION, ZERO DATA LEAKAGE)\n";
    echo "====================================================================\n";
    exit(0);
}
