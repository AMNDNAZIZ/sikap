<?php

namespace App\Services\Ai;

use CodeIgniter\Database\ConnectionInterface;
use App\Services\Ai\DTO\OperationalContextDTO;
use App\Services\JenjangService;

/**
 * Layanan Penyedia Konteks Operasional Akademik ESPATA (OperationalContextProvider)
 * 
 * Bertanggung jawab atas:
 * 1. Mengekstrak konteks satuan pendidikan, kalender akademik, dan penugasan KBM guru
 *    secara read-only dan server-authoritative dari database ESPATA.
 * 2. Mengisolasi kepemilikan data sehingga guru hanya dapat mengakses data penugasannya sendiri.
 * 3. Mengidentifikasi kondisi multi-penugasan dan menetapkan status ambigu jika kueri tidak spesifik.
 * 4. Menyediakan DTO yang bebas dari data pribadi siswa (Zero Student PII) dan bebas dari rahasia sistem.
 * 5. Menerapkan request-scoped in-memory cache untuk performa tinggi tanpa risiko stale data.
 */
class OperationalContextProvider
{
    protected ?ConnectionInterface $db;
    
    /** @var array<string, OperationalContextDTO> In-memory cache per siklus request */
    protected array $cache = [];

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?: db_connect();
    }

    /**
     * Menyelesaikan konteks operasional berdasarkan sesi atau identitas pengguna terautentikasi
     * 
     * @param array|int $userOrUserId Array sesi server ['id' => ..., 'role_id' => ...] atau int user_id
     * @param string|null $query Kueri aktif pengguna untuk evaluasi fokus penugasan
     * @param array $conversationHistory Riwayat percakapan multi-turn untuk penelusuran konteks lanjutan
     * @return OperationalContextDTO
     */
    public function resolveContext(
        $userOrUserId,
        ?string $query = null,
        array $conversationHistory = [],
        ?int $academicYearIdOverride = null
    ): OperationalContextDTO {
        $userId = is_array($userOrUserId) ? (int) ($userOrUserId['id'] ?? 0) : (int) $userOrUserId;

        if ($userId <= 0) {
            $anonymous = new OperationalContextDTO(0, 'guest', 'Tamu / Belum Login', 0, 'Anonymous');
            $anonymous->status = 'ANONYMOUS';
            return $anonymous;
        }

        // Kunci cache berbasis userId, hash kueri, dan tahun ajaran override
        $cacheKey = $userId . ':' . ($academicYearIdOverride ?? 0) . ':' . md5((string) $query);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // 1. Validasi Identitas Otoritatif dari Database Server (Zero-Trust terhadap payload klien)
        $userRow = $this->db->table('users')
            ->select('id, username, fullname, role_id, related_id, related_type, is_active')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$userRow || empty($userRow['is_active'])) {
            $inconsistent = new OperationalContextDTO($userId, 'unknown', 'Pengguna Tidak Aktif / Tidak Ditemukan', 0, 'Unknown');
            $inconsistent->status = 'DATA_INCONSISTENT';
            return $inconsistent;
        }

        $roleId = (int) $userRow['role_id'];
        $roleName = $this->resolveRoleName($roleId);
        $dto = new OperationalContextDTO(
            (int) $userRow['id'],
            (string) $userRow['username'],
            (string) ($userRow['fullname'] ?: $userRow['username']),
            $roleId,
            $roleName
        );

        // 2. Ambil Profil Satuan Pendidikan (school_profile)
        $this->populateSchoolProfile($dto);

        // 3. Ambil Tahun Ajaran Aktif & Semester
        $this->populateAcademicCalendar($dto, $academicYearIdOverride);

        // 4. Ambil Konteks Guru & Penugasan Mengajar (Hanya untuk Role 3: Guru)
        if ($roleId === 3) {
            $this->populateTeacherAssignments($dto, $userRow, $query, $conversationHistory);
        } else {
            $dto->isTeacher = false;
            $dto->hasAssignments = false;
            $dto->status = 'NON_TEACHER';
        }

        $this->cache[$cacheKey] = $dto;
        return $dto;
    }

    /**
     * Memperoleh nama peran resmi berdasarkan role_id
     */
    protected function resolveRoleName(int $roleId): string
    {
        $roleRow = $this->db->table('roles')->select('name')->where('id', $roleId)->get()->getRowArray();
        if ($roleRow && !empty($roleRow['name'])) {
            return (string) $roleRow['name'];
        }

        return match ($roleId) {
            1 => 'Administrator',
            2 => 'Kepala Sekolah',
            3 => 'Guru',
            4 => 'Orang Tua',
            5 => 'Siswa',
            6 => 'Kontributor',
            7 => 'Staf',
            8 => 'Pelatih',
            default => 'Pengguna',
        };
    }

    /**
     * Mengisi data profil sekolah dan jenjang
     */
    protected function populateSchoolProfile(OperationalContextDTO $dto): void
    {
        try {
            $school = $this->db->table('school_profile')->get()->getRowArray();
            if ($school) {
                $dto->schoolName = !empty($school['name']) ? trim($school['name']) : null;
                $dto->npsn = !empty($school['npsn']) ? trim($school['npsn']) : null;
                $dto->schoolLevel = (int) ($school['level'] ?? 0);
                
                $range = JenjangService::getClassRange($dto->schoolLevel);
                $dto->schoolLevelLabel = $range['name'] ?? 'Tidak terdefinisi';
            }
        } catch (\Throwable $e) {
            log_message('error', '[OperationalContextProvider::populateSchoolProfile] ' . $e->getMessage());
        }
    }

    /**
     * Mengisi konteks tahun ajaran aktif dan semester
     */
    protected function populateAcademicCalendar(OperationalContextDTO $dto, ?int $academicYearIdOverride = null): void
    {
        try {
            if ($academicYearIdOverride !== null && $academicYearIdOverride > 0) {
                $chosenYear = $this->db->table('academic_years')
                    ->where('id', $academicYearIdOverride)
                    ->get()
                    ->getRowArray();

                if ($chosenYear) {
                    $dto->academicYearId = (int) $chosenYear['id'];
                    $dto->academicYear   = (string) $chosenYear['year'];
                    $dto->isAcademicYearActive = ((int) $chosenYear['is_active']) === 1;
                }
            } else {
                // Prioritas: Tahun ajaran berstatus is_active = 1
                $activeYear = $this->db->table('academic_years')
                    ->where('is_active', 1)
                    ->get()
                    ->getRowArray();

                if ($activeYear) {
                    $dto->academicYearId = (int) $activeYear['id'];
                    $dto->academicYear   = (string) $activeYear['year'];
                    $dto->isAcademicYearActive = true;
                } else {
                    // Fallback jika tidak ada yang bertanda aktif: ambil tahun ajaran terbaru
                    $latestYear = $this->db->table('academic_years')
                        ->orderBy('start_date', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRowArray();

                    if ($latestYear) {
                        $dto->academicYearId = (int) $latestYear['id'];
                        $dto->academicYear   = (string) $latestYear['year'];
                        $dto->isAcademicYearActive = false;
                    }
                }
            }

            // Penentuan Semester:
            // 1. Cek apakah ada konfigurasi semester aktif di tabel settings
            $settingSem = $this->db->table('settings')
                ->where('setting_key', 'active_semester')
                ->get()
                ->getRowArray();

            if ($settingSem && !empty($settingSem['setting_value'])) {
                $semVal = (int) $settingSem['setting_value'];
                if ($semVal === 1 || $semVal === 2) {
                    $dto->semester = $semVal;
                    $dto->semesterLabel = ($semVal === 1) ? 'Ganjil' : 'Genap';
                    $dto->semesterSource = 'academic_calendar';
                    return;
                }
            }

            // 2. Fallback: Kalender standar kalender pendidikan nasional
            // Bulan 7-12 (Juli - Desember) = Semester 1 (Ganjil)
            // Bulan 1-6 (Januari - Juni)   = Semester 2 (Genap)
            $currentMonth = (int) date('n');
            $dto->semester = ($currentMonth >= 7) ? 1 : 2;
            $dto->semesterLabel = ($dto->semester === 1) ? 'Ganjil' : 'Genap';
            $dto->semesterSource = 'calendar_estimate_fallback';

        } catch (\Throwable $e) {
            log_message('error', '[OperationalContextProvider::populateAcademicCalendar] ' . $e->getMessage());
        }
    }

    /**
     * Mengisi penugasan mengajar guru untuk tahun ajaran aktif
     */
    protected function populateTeacherAssignments(
        OperationalContextDTO $dto,
        array $userRow,
        ?string $query,
        array $conversationHistory
    ): void {
        $dto->isTeacher = true;

        try {
            // Cari teacher_id sah di tabel teachers
            $userId = (int) $userRow['id'];
            $relatedId = (int) ($userRow['related_id'] ?? 0);

            $teacherBuilder = $this->db->table('teachers')->select('id, name, is_active');
            if ($relatedId > 0) {
                $teacherBuilder->where('id', $relatedId);
            } else {
                $teacherBuilder->where('user_id', $userId);
            }

            $teacher = $teacherBuilder->get()->getRowArray();
            if (!$teacher) {
                // Guru tidak memiliki data profil di tabel teachers
                $dto->hasAssignments = false;
                $dto->status = 'NO_ASSIGNMENT';
                return;
            }

            $dto->teacherId = (int) $teacher['id'];

            // Jika tahun ajaran tidak tersedia, tidak dapat mengambil penugasan sah
            if (!$dto->academicYearId) {
                $dto->hasAssignments = false;
                $dto->status = 'NO_ASSIGNMENT';
                return;
            }

            // Query penugasan mengajar guru pada tahun ajaran aktif
            $rows = $this->db->table('teaching_assignments ta')
                ->select('ta.id as assignment_id, ta.class_id, ta.subject_id, ta.academic_year_id,
                          c.name as class_name, c.level as class_level, c.is_active as class_is_active,
                          s.name as subject_name, s.code as subject_code, s.is_active as subject_is_active')
                ->join('classes c', 'c.id = ta.class_id', 'left')
                ->join('subjects s', 's.id = ta.subject_id', 'left')
                ->where('ta.teacher_id', $dto->teacherId)
                ->where('ta.academic_year_id', $dto->academicYearId)
                ->get()
                ->getResultArray();

            if (empty($rows)) {
                $dto->hasAssignments = false;
                $dto->status = 'NO_ASSIGNMENT';
                return;
            }

            $assignments = [];
            $assignedClasses = [];
            $assignedSubjects = [];

            foreach ($rows as $row) {
                // Abaikan jika relasi kelas atau mapel hilang / tidak aktif
                if (empty($row['class_id']) || empty($row['subject_id'])) {
                    continue;
                }

                $level = (int) ($row['class_level'] ?? 0);
                $fase = JenjangService::getFaseByLevel($level);

                $className = trim((string) ($row['class_name'] ?? 'Kelas ' . $level));
                $subjectName = trim((string) ($row['subject_name'] ?? 'Mata Pelajaran'));
                $subjectCode = trim((string) ($row['subject_code'] ?? ''));

                $assignmentItem = [
                    'assignment_id'   => (int) $row['assignment_id'],
                    'class_id'        => (int) $row['class_id'],
                    'class_name'      => $className,
                    'class_level'     => $level,
                    'fase'            => $fase,
                    'subject_id'      => (int) $row['subject_id'],
                    'subject_name'    => $subjectName,
                    'subject_code'    => $subjectCode,
                    'academic_year_id'=> (int) $row['academic_year_id'],
                    'is_active_year'  => true,
                ];

                $assignments[] = $assignmentItem;
                $assignedClasses[$className] = true;
                $assignedSubjects[$subjectName] = true;
            }

            if (empty($assignments)) {
                $dto->hasAssignments = false;
                $dto->status = 'NO_ASSIGNMENT';
                return;
            }

            $dto->assignments = $assignments;
            $dto->assignedClasses = array_keys($assignedClasses);
            $dto->assignedSubjects = array_keys($assignedSubjects);
            $dto->hasAssignments = true;
            $dto->isMultiAssignment = count($assignments) > 1;
            $dto->status = 'COMPLETE';

            // 5. Evaluasi Fokus Konteks Aktif & Deteksi Ambiguity
            $this->evaluateActiveAssignment($dto, $query, $conversationHistory);

        } catch (\Throwable $e) {
            log_message('error', '[OperationalContextProvider::populateTeacherAssignments] ' . $e->getMessage());
            $dto->status = 'DATA_INCONSISTENT';
        }
    }

    /**
     * Mengevaluasi apakah kueri pengguna atau riwayat mengarah ke penugasan spesifik
     */
    protected function evaluateActiveAssignment(
        OperationalContextDTO $dto,
        ?string $query,
        array $conversationHistory
    ): void {
        $count = count($dto->assignments);

        if ($count === 1) {
            $dto->activeAssignment = $dto->assignments[0];
            $dto->isAmbiguous = false;
            $dto->clarificationNeeded = false;
            return;
        }

        // Jika guru memiliki >1 penugasan, analisis teks kueri terkini
        $qClean = strtolower(trim((string) $query));
        $matchedCandidates = [];

        if (!empty($qClean)) {
            foreach ($dto->assignments as $a) {
                $subName = strtolower($a['subject_name']);
                $subCode = strtolower($a['subject_code']);
                $clsName = strtolower($a['class_name']);
                $lvlStr  = 'kelas ' . $a['class_level'];

                $score = 0;
                if (!empty($subName) && strpos($qClean, $subName) !== false) {
                    $score += 10;
                } elseif (!empty($subCode) && preg_match('/\b' . preg_quote($subCode, '/') . '\b/i', $qClean)) {
                    $score += 8;
                }

                if (!empty($clsName) && strpos($qClean, $clsName) !== false) {
                    $score += 6;
                } elseif (strpos($qClean, $lvlStr) !== false) {
                    $score += 4;
                }

                if ($score > 0) {
                    $matchedCandidates[] = [
                        'assignment' => $a,
                        'score'      => $score,
                    ];
                }
            }
        }

        // Jika pencocokan kueri terkini belum menghasilkan kandidat tunggal, periksa riwayat 2 putaran terakhir
        if (count($matchedCandidates) !== 1 && !empty($conversationHistory)) {
            $recentTurns = array_slice($conversationHistory, -2);
            $historyText = '';
            foreach ($recentTurns as $turn) {
                if (isset($turn['content']) && is_string($turn['content'])) {
                    $historyText .= ' ' . strtolower($turn['content']);
                }
            }

            if (!empty($historyText)) {
                $historyCandidates = [];
                foreach ($dto->assignments as $a) {
                    $subName = strtolower($a['subject_name']);
                    $clsName = strtolower($a['class_name']);

                    $score = 0;
                    if (!empty($subName) && strpos($historyText, $subName) !== false) {
                        $score += 5;
                    }
                    if (!empty($clsName) && strpos($historyText, $clsName) !== false) {
                        $score += 3;
                    }

                    if ($score > 0) {
                        $historyCandidates[] = [
                            'assignment' => $a,
                            'score'      => $score,
                        ];
                    }
                }

                // Jika kueri terkini tidak punya kandidat tapi riwayat punya kandidat tunggal
                if (empty($matchedCandidates) && count($historyCandidates) === 1) {
                    $matchedCandidates = $historyCandidates;
                }
            }
        }

        // Evaluasi Hasil Kandidat
        if (count($matchedCandidates) === 1) {
            // Tepat 1 kandidat cocok
            $dto->activeAssignment = $matchedCandidates[0]['assignment'];
            $dto->isAmbiguous = false;
            $dto->clarificationNeeded = false;
        } else {
            // Ambigu: Lebih dari 1 kandidat cocok ATAU tidak ada satupun yang cocok
            $dto->activeAssignment = null;
            $dto->isAmbiguous = true;
            $dto->clarificationNeeded = true;

            $options = [];
            foreach ($dto->assignments as $a) {
                $faseStr = !empty($a['fase']) && $a['fase'] !== '-' ? 'Fase ' . $a['fase'] : 'Tingkat ' . $a['class_level'];
                $options[] = $a['subject_name'] . ' ' . $a['class_name'] . ' (' . $faseStr . ')';
            }

            if (count($matchedCandidates) > 1) {
                $dto->ambiguityReason = 'Terdapat ' . count($matchedCandidates) . ' penugasan yang cocok dengan kata kunci kueri.';
            } else {
                $dto->ambiguityReason = 'Pengguna memiliki ' . $count . ' penugasan aktif tanpa menyebutkan mata pelajaran atau kelas spesifik.';
            }

            $dto->clarificationPrompt = 'Silakan tanyakan secara ramah mata pelajaran atau kelas mana yang dimaksud pengguna. Pilihan penugasan yang diampu: ' . implode(', ', $options) . '.';
        }
    }

    /**
     * Membersihkan cache internal instans
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }
}
