<?php

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

/**
 * NotificationRecipientResolver
 *
 * Komponen terpusat penentu penerima notifikasi berdasarkan relasi akademik riil.
 * Memastikan notifikasi hanya dikirim kepada guru yang memiliki kewenangan dan
 * tanggung jawab sah terhadap siswa atau tugas terkait.
 *
 * Aturan Utama:
 * 1. Konseling SD: Wali/Guru Kelas (+ Guru BK yang ditugaskan jika ada).
 * 2. Penugasan Mapel: Guru Utama & Guru Pendamping aktif; Guru Pengganti dikecualikan.
 * 3. Tugas LMS: Opsi A (berdasarkan kelas aktif siswa saat submit, publikasi materi terverifikasi).
 * 4. Tugas Reguler: Berdasarkan penugasan mengajar aktif rombel tugas.
 * 5. Larangan keras fallback created_by & broadcast ke seluruh guru.
 */
class NotificationRecipientResolver
{
    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * @param ConnectionInterface|null $db
     */
    public function __construct($db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Resolusi penerima notifikasi permohonan janji temu konseling BK.
     *
     * @param int      $studentId     ID siswa pengaju konseling
     * @param int|null $contextYearId ID tahun ajaran konteks (opsional, default: tahun aktif)
     * @return int[]   Array of user_id penerima sah (Wali Kelas + Guru BK terplot)
     */
    public function resolveCounselingRecipients(int $studentId, ?int $contextYearId = null): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $yearId = $contextYearId ?? $this->getActiveAcademicYearId();
        if (!$yearId) {
            return [];
        }

        // Cari kelas aktif siswa pada tahun ajaran ini
        $classId = $this->getActiveStudentClassId($studentId, $yearId);
        if (!$classId) {
            return [];
        }

        $teacherIds = [];

        // 1. Ambil Wali Kelas / Guru Kelas
        $classRow = $this->db->table('classes')
            ->select('teacher_id')
            ->where('id', $classId)
            ->where('is_active', 1)
            ->get()->getRowArray();
        $homeroomTeacherId = (int) ($classRow['teacher_id'] ?? 0);
        if ($homeroomTeacherId > 0) {
            $teacherIds[] = $homeroomTeacherId;
        }

        // 2. Ambil Guru BK yang secara resmi diplot untuk kelas ini
        if ($this->db->tableExists('bk_counselor_assignments')) {
            $bkRows = $this->db->table('bk_counselor_assignments')
                ->select('teacher_id')
                ->where('class_id', $classId)
                ->where('year_id', $yearId)
                ->get()->getResultArray();

            foreach ($bkRows as $row) {
                $btId = (int) ($row['teacher_id'] ?? 0);
                if ($btId > 0) {
                    $teacherIds[] = $btId;
                }
            }
        }

        $cleanTeacherIds = array_unique(array_filter($teacherIds));
        if (empty($cleanTeacherIds)) {
            return [];
        }

        return $this->mapTeachersToActiveUserIds($cleanTeacherIds);
    }

    /**
     * Resolusi guru pengampu mata pelajaran (Guru Utama dan Guru Pendamping).
     *
     * @param int      $classId       ID kelas
     * @param int      $subjectId     ID mata pelajaran
     * @param int|null $contextYearId ID tahun ajaran (opsional, default: tahun aktif)
     * @return int[]   Array of user_id guru pengampu sah
     */
    public function resolveSubjectTeacherRecipients(int $classId, int $subjectId, ?int $contextYearId = null): array
    {
        if ($classId <= 0 || $subjectId <= 0) {
            return [];
        }

        $yearId = $contextYearId ?? $this->getActiveAcademicYearId();
        if (!$yearId) {
            return [];
        }

        // Hanya role 'utama' dan 'pendamping' yang menjadi penerima sah.
        // Guru pengganti ('pengganti') tidak otomatis menerima.
        $rows = $this->db->table('teaching_assignments')
            ->select('teacher_id')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $yearId)
            ->whereIn('role', ['utama', 'pendamping'])
            ->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $teacherIds = array_unique(array_filter(array_map('intval', array_column($rows, 'teacher_id'))));
        return $this->mapTeachersToActiveUserIds($teacherIds);
    }

    /**
     * Resolusi penerima tugas langkah materi LMS Belajar (Mengikuti Opsi A).
     *
     * Ketentuan Opsi A:
     * - Hak akses & penentuan kelas mengikuti kelas aktif siswa saat submit.
     * - Materi harus dipublikasikan ke kelas aktif tersebut (subject_material_publishes).
     * - Penerima adalah guru utama & pendamping kelas aktif tersebut.
     * - Guru kelas lama tidak menerima notifikasi.
     * - Tidak ada fallback created_by.
     *
     * @param int      $studentId     ID siswa pengumpul tugas
     * @param array    $taskRow       Data baris tugas (dari tabel tugas)
     * @param int|null $contextYearId ID tahun ajaran (opsional, default: tahun aktif)
     * @return int[]   Array of user_id guru pengampu sah
     */
    public function resolveLmsTaskRecipients(int $studentId, array $taskRow, ?int $contextYearId = null): array
    {
        if ($studentId <= 0 || empty($taskRow)) {
            return [];
        }

        $yearId = $contextYearId ?? $this->getActiveAcademicYearId();
        if (!$yearId) {
            return [];
        }

        // 1. Tentukan kelas aktif siswa saat pengumpulan (Opsi A)
        $activeClassId = $this->getActiveStudentClassId($studentId, $yearId);
        if (!$activeClassId) {
            return [];
        }

        // 2. Identifikasi subject_id dan material_id
        $subjectId = (int) ($taskRow['subject_id'] ?? 0);
        $subMatId  = (int) ($taskRow['material_id'] ?? 0);

        if ($subMatId <= 0 && !empty($taskRow['segment_id'])) {
            $seg = $this->db->table('material_segments')
                ->select('material_id')
                ->where('id', (int) $taskRow['segment_id'])
                ->get()->getRowArray();
            $subMatId = (int) ($seg['material_id'] ?? 0);
        }

        if ($subjectId <= 0 && $subMatId > 0) {
            $subMat = $this->db->table('subject_materials')
                ->select('subject_id')
                ->where('id', $subMatId)
                ->get()->getRowArray();
            $subjectId = (int) ($subMat['subject_id'] ?? 0);
        }

        if ($subjectId <= 0) {
            return [];
        }

        // 3. Validasi bahwa materi ini memang dipublikasikan untuk kelas aktif siswa
        if ($subMatId > 0 && $this->db->tableExists('subject_material_publishes')) {
            $isPublished = (bool) $this->db->table('subject_material_publishes')
                ->where('material_id', $subMatId)
                ->where('class_id', $activeClassId)
                ->where('is_active', 1)
                ->where('(scheduled_at IS NULL OR scheduled_at <= NOW())')
                ->countAllResults();

            if (!$isPublished) {
                // Materi belum/tidak dipublikasikan untuk kelas aktif siswa
                return [];
            }
        }

        // 4. Resolusi guru utama & pendamping kelas aktif siswa
        return $this->resolveSubjectTeacherRecipients($activeClassId, $subjectId, $yearId);
    }

    /**
     * Resolusi penerima tugas reguler kelas.
     *
     * Prioritas:
     * 1. teaching_assignments pada kelas tugas (atau kelas aktif siswa jika class_id null).
     * 2. Fallback terkendali via teacher_id jika terbukti sah mengajar mapel & TA tersebut.
     * 3. created_by DILARANG menjadi fallback.
     *
     * @param int      $studentId     ID siswa pengumpul tugas
     * @param array    $taskRow       Data baris tugas (dari tabel tugas)
     * @param int|null $contextYearId ID tahun ajaran (opsional, default: tahun aktif)
     * @return int[]   Array of user_id guru pengampu sah
     */
    public function resolveRegularTaskRecipients(int $studentId, array $taskRow, ?int $contextYearId = null): array
    {
        if (empty($taskRow)) {
            return [];
        }

        $yearId = $contextYearId ?? $this->getActiveAcademicYearId();
        if (!$yearId) {
            return [];
        }

        $subjectId = (int) ($taskRow['subject_id'] ?? 0);
        if ($subjectId <= 0) {
            return [];
        }

        // Prioritas kelas: class_id spesifik tugas jika ada, atau kelas aktif siswa
        $classId = (int) ($taskRow['class_id'] ?? 0);
        if ($classId <= 0 && $studentId > 0) {
            $classId = (int) ($this->getActiveStudentClassId($studentId, $yearId) ?? 0);
        }

        if ($classId > 0) {
            $teacherUserIds = $this->resolveSubjectTeacherRecipients($classId, $subjectId, $yearId);
            if (!empty($teacherUserIds)) {
                return $teacherUserIds;
            }
        }

        // Fallback terkendali: tugas.teacher_id HANYA jika terverifikasi sah di teaching_assignments
        $fallbackTeacherId = (int) ($taskRow['teacher_id'] ?? 0);
        if ($fallbackTeacherId > 0) {
            $isValid = (bool) $this->db->table('teaching_assignments')
                ->where('teacher_id', $fallbackTeacherId)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $yearId)
                ->countAllResults();

            if ($isValid) {
                return $this->mapTeachersToActiveUserIds([$fallbackTeacherId]);
            }
        }

        // DILARANG keras fallback ke created_by. Kembalikan kosong jika tidak ada guru sah.
        return [];
    }

    /**
     * Dapatkan ID kelas aktif siswa pada tahun ajaran tertentu.
     *
     * @param int $studentId
     * @param int $yearId
     * @return int|null
     */
    public function getActiveStudentClassId(int $studentId, int $yearId): ?int
    {
        $row = $this->db->table('student_records')
            ->select('class_id')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId)
            ->where('status', 'aktif')
            ->get()->getRowArray();

        $classId = (int) ($row['class_id'] ?? 0);
        return $classId > 0 ? $classId : null;
    }

    /**
     * Dapatkan ID tahun ajaran yang sedang aktif.
     *
     * @return int|null
     */
    public function getActiveAcademicYearId(): ?int
    {
        $row = $this->db->table('academic_years')
            ->select('id')
            ->where('is_active', 1)
            ->get()->getRowArray();

        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    /**
     * Dapatkan seluruh user_id aktif dengan role Admin (role_id = 1).
     * Digunakan untuk eskalasi peringatan konfigurasi sistem tanpa identitas siswa.
     *
     * @return int[]
     */
    public function getAdminUserIds(): array
    {
        $rows = $this->db->table('users')
            ->select('id')
            ->where('role_id', 1)
            ->where('is_active', 1)
            ->get()->getResultArray();

        return array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'id')))));
    }

    /**
     * Petakan daftar teacher_id ke user_id aktif akun Guru (role_id = 3).
     *
     * @param int[] $teacherIds
     * @return int[]
     */
    public function mapTeachersToActiveUserIds(array $teacherIds): array
    {
        $cleanTeacherIds = array_unique(array_filter(array_map('intval', $teacherIds)));
        if (empty($cleanTeacherIds)) {
            return [];
        }

        // 1. Relasi via teachers.user_id
        $teacherRows = $this->db->table('teachers')
            ->select('user_id')
            ->whereIn('id', $cleanTeacherIds)
            ->where('is_active', 1)
            ->get()->getResultArray();
        $userIds1 = array_filter(array_map('intval', array_column($teacherRows, 'user_id')));

        // 2. Relasi via users.related_id (role_id = 3)
        $userRows = $this->db->table('users')
            ->select('id')
            ->where('role_id', 3)
            ->whereIn('related_id', $cleanTeacherIds)
            ->where('is_active', 1)
            ->get()->getResultArray();
        $userIds2 = array_filter(array_map('intval', array_column($userRows, 'id')));

        $allUserIds = array_unique(array_merge($userIds1, $userIds2));
        if (empty($allUserIds)) {
            return [];
        }

        // Verifikasi akhir: harus aktif dan memiliki role_id = 3 (Guru)
        $validUsers = $this->db->table('users')
            ->select('id')
            ->whereIn('id', $allUserIds)
            ->where('role_id', 3)
            ->where('is_active', 1)
            ->get()->getResultArray();

        return array_values(array_unique(array_filter(array_map('intval', array_column($validUsers, 'id')))));
    }
}
