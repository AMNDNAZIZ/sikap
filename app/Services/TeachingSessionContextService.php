<?php

namespace App\Services;

/**
 * Service untuk menjembatani Jadwal Pelajaran (Schedules), Sebaran Promes (Prosem),
 * Modul Ajar, dan Sesi Jurnal/E-Learning secara kontekstual tanpa saling mengunci (Loose Coupling).
 */
class TeachingSessionContextService extends BaseService
{
    /**
     * Nama hari dalam Bahasa Indonesia (1 = Senin, ..., 7 = Minggu)
     */
    public const DAYS_ID = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /**
     * Nama bulan dalam Bahasa Indonesia
     */
    public const MONTHS_ID = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    /**
     * Menghitung minggu ke-berapa dalam suatu bulan (1 s.d. 5)
     */
    public function getWeekOfMonth(string $date): int
    {
        $dayOfMonth = (int)date('j', strtotime($date));
        return (int)min(5, max(1, (int)ceil($dayOfMonth / 7)));
    }

    /**
     * Menentukan semester berdasarkan bulan kalender akademik standar
     * Bulan 7-12 (Juli - Desember) = Semester 1 (Ganjil)
     * Bulan 1-6 (Januari - Juni) = Semester 2 (Genap)
     */
    public function getSemesterFromMonth(int $month): int
    {
        return ($month >= 7) ? 1 : 2;
    }

    /**
     * Mengambil daftar sesi mengajar terjadwal pada hari tertentu,
     * diperkaya dengan status pengisian jurnal hari itu dan target materi promes minggu bersangkutan.
     */
    public function getDayScheduleSessions(int $roleId, ?int $teacherId = null, ?int $classId = null, ?string $date = null): array
    {
        $targetDate = $date ?: date('Y-m-d');
        $dayOfWeek = (int)date('N', strtotime($targetDate)); // 1=Senin..7=Minggu
        $currentTime = date('H:i:s');
        $isToday = ($targetDate === date('Y-m-d'));

        $targetMonth = (int)date('n', strtotime($targetDate));
        $targetWeek = $this->getWeekOfMonth($targetDate);

        // Ambil tahun ajaran aktif jika ada
        $activeYear = $this->db->table('academic_years')
            ->where('is_active', 1)
            ->get()->getRowArray();
        $academicYearId = $activeYear['id'] ?? null;

        $builder = $this->db->table('schedules sc')
            ->select('sc.id as schedule_id, sc.day_of_week, sc.start_time, sc.end_time,
                      sc.class_id, sc.subject_id, sc.teacher_id,
                      c.name as class_name, c.level as class_level,
                      s.name as subject_name, s.code as subject_code,
                      t.name as teacher_name')
            ->join('classes c', 'c.id = sc.class_id', 'left')
            ->join('subjects s', 's.id = sc.subject_id', 'left')
            ->join('teachers t', 't.id = sc.teacher_id', 'left')
            ->where('sc.day_of_week', $dayOfWeek);

        if ($academicYearId) {
            $builder->where('sc.academic_year_id', $academicYearId);
        }

        // Otorisasi role: Guru hanya melihat jadwal dirinya
        if ($roleId === 3 && $teacherId) {
            $builder->where('sc.teacher_id', $teacherId);
        } elseif ($teacherId) {
            $builder->where('sc.teacher_id', $teacherId);
        }

        if ($classId) {
            $builder->where('sc.class_id', $classId);
        }

        $schedules = $builder->orderBy('sc.start_time', 'ASC')->get()->getResultArray();

        $result = [];
        foreach ($schedules as $item) {
            $schedClassId   = (int)$item['class_id'];
            $schedSubjectId = (int)$item['subject_id'];
            $schedTeacherId = (int)$item['teacher_id'];

            // 1. Cek apakah Jurnal sudah diisi untuk sesi ini pada tanggal tersebut
            $journalQuery = $this->db->table('teaching_journals')
                ->where('date', $targetDate)
                ->where('class_id', $schedClassId)
                ->where('teacher_id', $schedTeacherId);

            // Cek subject langsung atau lewat pivot teaching_journal_subjects
            $journalQuery->groupStart()
                ->where('subject_id', $schedSubjectId)
                ->orWhere("id IN (SELECT journal_id FROM teaching_journal_subjects WHERE subject_id = {$schedSubjectId})")
            ->groupEnd();

            $existingJournal = $journalQuery->get()->getRowArray();

            // 2. Deteksi apakah sesi ini sedang berlangsung saat ini
            $startTime = !empty($item['start_time']) ? substr($item['start_time'], 0, 5) : '00:00';
            $endTime   = !empty($item['end_time'])   ? substr($item['end_time'], 0, 5)   : '00:00';
            $isLive    = false;
            if ($isToday && !empty($item['start_time']) && !empty($item['end_time'])) {
                $isLive = ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']);
            }

            // 3. Cari rekomendasi Target Promes untuk kelas & mapel ini pada bulan/minggu ini
            $promesTarget = $this->resolvePromesTargetForSubjectAndClass(
                $schedClassId,
                $schedSubjectId,
                $schedTeacherId,
                $targetDate,
                $item['class_level']
            );

            $result[] = [
                'schedule_id'     => (int)$item['schedule_id'],
                'day_of_week'     => (int)$item['day_of_week'],
                'day_name'        => self::DAYS_ID[(int)$item['day_of_week']] ?? 'Hari',
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'time_label'      => "{$startTime} - {$endTime}",
                'class_id'        => $schedClassId,
                'class_name'      => $item['class_name'] ?? 'Kelas',
                'class_level'     => $item['class_level'] ?? null,
                'subject_id'      => $schedSubjectId,
                'subject_name'    => $item['subject_name'] ?? 'Mata Pelajaran',
                'subject_code'    => $item['subject_code'] ?? '',
                'teacher_id'      => $schedTeacherId,
                'teacher_name'    => $item['teacher_name'] ?? 'Guru',
                'is_live'         => $isLive,
                'has_journal'     => !empty($existingJournal),
                'journal'         => $existingJournal ? [
                    'id'               => (int)$existingJournal['id'],
                    'status_realisasi' => $existingJournal['status_realisasi'] ?? 'terlaksana',
                    'pertemuan_ke'     => $existingJournal['pertemuan_ke'] ?? null,
                    'notes'            => $existingJournal['notes'] ?? '',
                ] : null,
                'promes_target'   => $promesTarget,
            ];
        }

        return $result;
    }

    /**
     * Mencari materi ATP yang dialokasikan dalam Sebaran Promes pada bulan & minggu tertentu
     */
    public function resolvePromesTargetForSubjectAndClass(
        int $classId,
        int $subjectId,
        ?int $teacherId = null,
        ?string $date = null,
        ?string $classLevel = null
    ): ?array {
        $targetDate  = $date ?: date('Y-m-d');
        $targetMonth = (int)date('n', strtotime($targetDate));
        $targetWeek  = $this->getWeekOfMonth($targetDate);

        // Jika level kelas belum diketahui, cari di DB
        if ($classLevel === null) {
            $classRow = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            $classLevel = $classRow['level'] ?? null;
        }

        // Ambil semua kelas se-level untuk mencocokkan ATP lintas rombel
        $classIdsAtLevel = [$classId];
        if ($classLevel !== null && $classLevel !== '') {
            $cRows = $this->db->table('classes')
                ->select('id')
                ->where('level', $classLevel)
                ->get()->getResultArray();
            if (!empty($cRows)) {
                $classIdsAtLevel = array_map('intval', array_column($cRows, 'id'));
            }
        }

        // Cari ATP se-level yang memiliki alokasi JP di prosem_distributions pada bulan & minggu ini
        $distRow = $this->db->table('prosem_distributions pd')
            ->select('pd.atp_id, pd.jp, pd.month, pd.week,
                      atp.lingkup_materi, atp.semester, atp.urutan')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = pd.atp_id')
            ->whereIn('atp.class_id', $classIdsAtLevel)
            ->where('atp.subject_id', $subjectId)
            ->where('pd.month', $targetMonth)
            ->where('pd.week', $targetWeek)
            ->where('pd.jp >', 0)
            ->orderBy('atp.urutan', 'ASC')
            ->get()->getRowArray();

        if (!$distRow) {
            return null;
        }

        $atpId = (int)$distRow['atp_id'];

        // Cek apakah ada Modul Ajar yang tersedia untuk materi ATP ini
        $modulQuery = $this->db->table('modul_ajar ma')
            ->select('ma.id, ma.tipe, ma.updated_at')
            ->whereIn('ma.class_id', $classIdsAtLevel)
            ->where('ma.subject_id', $subjectId)
            ->where('ma.atp_id', $atpId);

        if ($teacherId) {
            $modulQuery->where('ma.teacher_id', $teacherId);
        }

        $modulRow = $modulQuery->orderBy('ma.updated_at', 'DESC')->get()->getRowArray();

        $monthName = self::MONTHS_ID[$targetMonth] ?? 'Bulan ' . $targetMonth;

        return [
            'atp_id'         => $atpId,
            'lingkup_materi' => $distRow['lingkup_materi'] ?? 'Materi Pembelajaran',
            'semester'       => (int)($distRow['semester'] ?? 1),
            'jp'             => (int)$distRow['jp'],
            'month'          => $targetMonth,
            'week'           => $targetWeek,
            'period_label'   => "Minggu ke-{$targetWeek} {$monthName}",
            'badge_label'    => "Target Promes Mg {$targetWeek} {$monthName} ({$distRow['jp']} JP)",
            'has_modul'      => !empty($modulRow),
            'modul_id'       => !empty($modulRow) ? (int)$modulRow['id'] : null,
        ];
    }

    /**
     * Memperkaya array ATP dengan metadata target Promes (apakah cocok dengan minggu aktif)
     */
    public function decorateAtpsWithPromesTarget(array $atps, ?string $date = null): array
    {
        if (empty($atps)) {
            return [];
        }

        $targetDate  = $date ?: date('Y-m-d');
        $targetMonth = (int)date('n', strtotime($targetDate));
        $targetWeek  = $this->getWeekOfMonth($targetDate);
        $monthName   = self::MONTHS_ID[$targetMonth] ?? 'Bulan ' . $targetMonth;

        $atpIds = array_column($atps, 'id');
        $promesMap = [];

        if (!empty($atpIds)) {
            $distRows = $this->db->table('prosem_distributions')
                ->whereIn('atp_id', $atpIds)
                ->where('month', $targetMonth)
                ->where('week', $targetWeek)
                ->where('jp >', 0)
                ->get()->getResultArray();

            foreach ($distRows as $dr) {
                $promesMap[$dr['atp_id']] = (int)$dr['jp'];
            }
        }

        foreach ($atps as &$atp) {
            $atpId = (int)$atp['id'];
            $jp = $promesMap[$atpId] ?? 0;
            $isTarget = ($jp > 0);

            $atp['is_promes_target'] = $isTarget;
            $atp['promes_jp']        = $jp;
            $atp['promes_badge']     = $isTarget ? "⭐ Target Promes Mg {$targetWeek} {$monthName} ({$jp} JP)" : null;
        }
        unset($atp);

        // Prioritaskan ATP yang menjadi target promes di urutan paling atas
        usort($atps, function ($a, $b) {
            $targetA = !empty($a['is_promes_target']) ? 1 : 0;
            $targetB = !empty($b['is_promes_target']) ? 1 : 0;
            if ($targetA !== $targetB) {
                return $targetB - $targetA; // target promes di atas
            }
            // urutkan berdasarkan semester lalu urutan normal
            if (($a['semester'] ?? 0) !== ($b['semester'] ?? 0)) {
                return ($a['semester'] ?? 0) - ($b['semester'] ?? 0);
            }
            return ($a['urutan'] ?? 0) - ($b['urutan'] ?? 0);
        });

        return $atps;
    }

    /**
     * TAHAP 3: Hitung rekap ringkas presensi siswa dan format teks kehadiran naratif untuk catatan jurnal.
     * Pure logic tanpa ketergantungan DB langsung (testable).
     */
    public function buildAttendanceSummary(array $students, array $attMap): array
    {
        $total = count($students);
        $countH = 0;
        $countS = 0;
        $countI = 0;
        $countA = 0;

        $namesS = [];
        $namesI = [];
        $namesA = [];

        foreach ($students as $s) {
            $sId = (int)$s['id'];
            $st = strtoupper(trim((string)($attMap[$sId] ?? 'H')));
            if ($st === 'S') {
                $countS++;
                $namesS[] = $s['name'] ?? 'Siswa';
            } elseif ($st === 'I') {
                $countI++;
                $namesI[] = $s['name'] ?? 'Siswa';
            } elseif ($st === 'A') {
                $countA++;
                $namesA[] = $s['name'] ?? 'Siswa';
            } else {
                $countH++;
            }
        }

        // Teks naratif untuk di-inject ke catatan jurnal jika guru memilih
        if ($total === 0) {
            $attendanceText = '';
        } elseif ($countH === $total) {
            $attendanceText = "Kehadiran: Seluruh siswa ({$total}) hadir lengkap.";
        } else {
            $parts = ["{$countH} Hadir"];
            if ($countS > 0) {
                $parts[] = "{$countS} Sakit (" . implode(', ', array_slice($namesS, 0, 3)) . (count($namesS) > 3 ? ' dkk' : '') . ")";
            }
            if ($countI > 0) {
                $parts[] = "{$countI} Izin (" . implode(', ', array_slice($namesI, 0, 3)) . (count($namesI) > 3 ? ' dkk' : '') . ")";
            }
            if ($countA > 0) {
                $parts[] = "{$countA} Alpa (" . implode(', ', array_slice($namesA, 0, 3)) . (count($namesA) > 3 ? ' dkk' : '') . ")";
            }
            $attendanceText = "Kehadiran: " . implode(', ', $parts) . ".";
        }

        return [
            'total'           => $total,
            'hadir'           => $countH,
            'sakit'           => $countS,
            'izin'            => $countI,
            'alpa'            => $countA,
            'sakit_names'     => $namesS,
            'izin_names'      => $namesI,
            'alpa_names'      => $namesA,
            'attendance_text' => $attendanceText,
        ];
    }

    /**
     * TAHAP 3: Rekomendasi/asisten refleksi cerdas berdasarkan status realisasi pembelajaran.
     * Pure logic tanpa ketergantungan DB langsung (testable).
     */
    public function getReflectionSuggestions(string $statusRealisasi, ?string $materi = null): array
    {
        $materiLabel = !empty($materi) ? "materi {$materi}" : "materi hari ini";

        switch ($statusRealisasi) {
            case 'terlaksana':
                return [
                    [
                        'label' => 'Lancar & Aktif',
                        'text'  => "Seluruh tahapan kegiatan pembelajaran {$materiLabel} terlaksana dengan baik sesuai rencana. Siswa aktif berpartisipasi dalam diskusi kelompok dan menyelesaikan latihan pemahaman dengan antusias."
                    ],
                    [
                        'label' => 'Tuntas & Reflektif',
                        'text'  => "Pembelajaran {$materiLabel} berjalan tertib dan tepat waktu. Evaluasi formatif menunjukkan pemahaman konsep siswa tercapai dengan baik, diakhiri refleksi bermakna bersama siswa."
                    ]
                ];

            case 'sebagian':
                return [
                    [
                        'label' => 'Kendala Waktu',
                        'text'  => "Kegiatan pembelajaran {$materiLabel} terlaksana sebagian hingga tahap elaborasi/eksplorasi. Kegiatan latihan mandiri dan refleksi akan dilanjutkan pada pertemuan berikutnya karena keterbatasan alokasi waktu."
                    ],
                    [
                        'label' => 'Perlu Penguatan Konsep',
                        'text'  => "Sebagian besar siswa membutuhkan penjelasan tambahan dan bimbingan mendalam pada bagian konsep dasar {$materiLabel}. Pembahasan latihan soal dilanjutkan pada sesi pertemuan selanjutnya."
                    ]
                ];

            case 'tertunda':
                return [
                    [
                        'label' => 'Upacara / Peringatan',
                        'text'  => "Jam pembelajaran {$materiLabel} dialihkan untuk pelaksanaan kegiatan upacara bendera dan peringatan hari besar di sekolah. Rencana materi akan dijadwalkan ulang pada sesi berikutnya."
                    ],
                    [
                        'label' => 'Kegiatan Sekolah / Lomba',
                        'text'  => "Pembelajaran {$materiLabel} ditunda karena adanya agenda kegiatan sekolah (classmeeting/lomba/kegiatan luar kelas). Materi akan dilanjutkan pada jadwal berikutnya."
                    ],
                    [
                        'label' => 'Tugas Dinas / Rapat',
                        'text'  => "Guru menghadiri rapat koordinasi / penugasan kedinasan sekolah. Siswa diberikan penugasan mandiri terarah untuk mempelajari bahan bacaan {$materiLabel}."
                    ]
                ];

            default:
                return [];
        }
    }

    /**
     * TAHAP 3: Ambil daftar siswa suatu kelas dan status kehadiran pada tanggal tertentu.
     */
    public function getClassAttendanceData(int $classId, string $date): array
    {
        if ($classId <= 0) {
            return [
                'students'        => [],
                'summary'         => $this->buildAttendanceSummary([], []),
                'attendance_text' => '',
            ];
        }

        // Ambil tahun ajaran aktif
        $activeYear = $this->db->table('academic_years')
            ->where('is_active', 1)
            ->get()->getRowArray();
        $academicYearId = $activeYear['id'] ?? null;

        // Query siswa via student_records (aktif) atau fallback langsung ke students
        $builder = $this->db->table('students s')
            ->select('s.id, s.name, s.nisn, s.gender')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->where('sr.class_id', $classId)
            ->where('sr.status', 'aktif');

        if ($academicYearId) {
            $builder->where('sr.academic_year_id', $academicYearId);
        }

        $students = $builder->orderBy('s.name', 'ASC')->get()->getResultArray();

        // Fallback jika belum dimapping di student_records
        if (empty($students)) {
            $students = $this->db->table('students')
                ->select('id, name, nisn, gender')
                ->where('class_id', $classId)
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        }

        $studentIds = array_column($students, 'id');
        $attMap = [];

        if (!empty($studentIds)) {
            $attendances = $this->db->table('attendances')
                ->select('student_id, status')
                ->whereIn('student_id', $studentIds)
                ->where('date', $date)
                ->get()->getResultArray();

            foreach ($attendances as $a) {
                $attMap[(int)$a['student_id']] = strtoupper((string)$a['status']);
            }
        }

        $summary = $this->buildAttendanceSummary($students, $attMap);

        // Tempelkan status ke masing-masing objek siswa
        foreach ($students as &$s) {
            $s['status'] = $attMap[(int)$s['id']] ?? 'H';
        }
        unset($s);

        return [
            'class_id'        => $classId,
            'date'            => $date,
            'students'        => $students,
            'summary'         => $summary,
            'attendance_text' => $summary['attendance_text'],
        ];
    }

    /**
     * TAHAP 3: Simpan presensi cepat per kelas (mengikuti kaidah hemat storage Siakad: H dihapus, S/I/A diupsert).
     */
    public function saveClassAttendanceData(int $classId, string $date, array $statusMap, int $userId): array
    {
        if ($classId <= 0 || empty($date)) {
            return [
                'success' => false,
                'message' => 'ID Kelas dan Tanggal wajib diisi.',
            ];
        }

        foreach ($statusMap as $studentId => $status) {
            $studentId = (int)$studentId;
            $st = strtoupper(trim((string)$status));
            if ($studentId <= 0) continue;

            if ($st === 'H') {
                // Default hadir -> hapus record agar hemat ruang sesuai arsitektur Siakad
                $this->db->table('attendances')
                    ->where('student_id', $studentId)
                    ->where('date', $date)
                    ->delete();
            } elseif (in_array($st, ['S', 'I', 'A'])) {
                $existing = $this->db->table('attendances')
                    ->where('student_id', $studentId)
                    ->where('date', $date)
                    ->get()->getRowArray();

                if ($existing) {
                    $this->db->table('attendances')
                        ->where('id', $existing['id'])
                        ->update([
                            'class_id'   => $classId,
                            'status'     => $st,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                } else {
                    $this->db->table('attendances')->insert([
                        'student_id' => $studentId,
                        'class_id'   => $classId,
                        'date'       => $date,
                        'status'     => $st,
                        'created_by' => $userId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        // Ambil data terbaru setelah penyimpanan
        $freshData = $this->getClassAttendanceData($classId, $date);

        return [
            'success'         => true,
            'message'         => 'Presensi siswa berhasil disimpan.',
            'summary'         => $freshData['summary'],
            'attendance_text' => $freshData['attendance_text'],
        ];
    }

    /**
     * TAHAP 4: Kalkulasi konversi nilai tes / CBT ke skala rapor dengan rumus standar Siakad.
     * Rumus: Converted = ((YA - YB) / (XA - XB)) * (NX - XB) + YB
     * Pure logic tanpa ketergantungan DB (testable).
     */
    public function calculateScoreConversion(array $rawScores, float $ya = 100.0, float $yb = 75.0): array
    {
        $participantScores = [];
        foreach ($rawScores as $sId => $score) {
            if ($score !== null && is_numeric($score)) {
                $participantScores[(int)$sId] = (float)$score;
            }
        }

        if (empty($participantScores)) {
            return [
                'xa'          => 0.0,
                'xb'          => 0.0,
                'ya'          => $ya,
                'yb'          => $yb,
                'results'     => [],
                'has_scores'  => false,
            ];
        }

        $xa = max($participantScores);
        $xb = min($participantScores);
        $denominator = ($xa - $xb) ?: 1.0;

        $results = [];
        foreach ($rawScores as $sId => $rawScore) {
            $sId = (int)$sId;
            $hasScore = ($rawScore !== null && is_numeric($rawScore));

            if (!$hasScore) {
                $results[$sId] = [
                    'student_id'      => $sId,
                    'raw_score'       => null,
                    'converted_score' => 0.0,
                    'ikut_ujian'      => false,
                ];
                continue;
            }

            $nx = (float)$rawScore;
            if ($xa == $xb) {
                $converted = $ya;
            } else {
                $converted = (($ya - $yb) / $denominator) * ($nx - $xb) + $yb;
            }

            // Normalisasi rentang wajar 0-100
            $converted = max(0.0, min(100.0, round($converted, 2)));

            $results[$sId] = [
                'student_id'      => $sId,
                'raw_score'       => $nx,
                'converted_score' => $converted,
                'ikut_ujian'      => true,
            ];
        }

        return [
            'xa'         => $xa,
            'xb'         => $xb,
            'ya'         => $ya,
            'yb'         => $yb,
            'results'    => $results,
            'has_scores' => true,
        ];
    }

    /**
     * TAHAP 4: Ambil status penilaian formatif siswa pada Lingkup Materi (ATP) tertentu
     * serta mendeteksi jadwal/bank tes CBT yang dapat dikonversikan.
     */
    public function getFormativeAssessmentStatus(int $classId, int $atpId, int $subjectId): array
    {
        if ($classId <= 0 || $atpId <= 0) {
            return [
                'total_students'   => 0,
                'graded_students'  => 0,
                'avg_score'        => 0,
                'min_score'        => 0,
                'max_score'        => 0,
                'cbt_tests'        => [],
                'conversion_url'   => base_url('admin/cbt/convertnilai'),
            ];
        }

        // Ambil tahun ajaran aktif
        $activeYear = $this->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $yearId = $activeYear['id'] ?? 1;

        // Ambil daftar siswa kelas aktif
        $students = $this->db->table('students s')
            ->select('s.id, s.name')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->where('sr.class_id', $classId)
            ->where('sr.status', 'aktif')
            ->get()->getResultArray();

        if (empty($students)) {
            $students = $this->db->table('students')->select('id, name')->where('class_id', $classId)->get()->getResultArray();
        }

        $studentIds = array_column($students, 'id');
        $totalStudents = count($studentIds);

        // Ambil nilai formatif yang tersimpan di material_scores untuk atp_id ini
        $scores = [];
        if (!empty($studentIds)) {
            $scores = $this->db->table('material_scores')
                ->where('material_id', $atpId)
                ->whereIn('student_id', $studentIds)
                ->get()->getResultArray();
        }

        $gradedCount = count($scores);
        $scoreValues = array_column($scores, 'score');
        $avgScore = ($gradedCount > 0) ? round(array_sum($scoreValues) / $gradedCount, 1) : 0;
        $minScore = ($gradedCount > 0) ? min($scoreValues) : 0;
        $maxScore = ($gradedCount > 0) ? max($scoreValues) : 0;

        // Cari Bank Soal & Tes CBT yang relevan untuk mata pelajaran ini
        $cbtTests = $this->db->table('cbt_question_banks qb')
            ->select('qb.id as bank_id, qb.code as bank_code, ts.id as test_id, ts.start_time, ts.end_time')
            ->join('cbt_test_status ts', 'ts.bank_id = qb.id', 'left')
            ->where('qb.subject_id', $subjectId)
            ->orderBy('qb.id', 'DESC')
            ->get()->getResultArray();

        $primaryBankId = !empty($cbtTests[0]['bank_id']) ? $cbtTests[0]['bank_id'] : null;
        $conversionUrl = base_url('admin/cbt/convertnilai') . ($primaryBankId ? "?bank_id={$primaryBankId}&class_id={$classId}&target_material_id={$atpId}" : "?class_id={$classId}");

        return [
            'total_students'   => $totalStudents,
            'graded_students'  => $gradedCount,
            'unassigned_count' => max(0, $totalStudents - $gradedCount),
            'avg_score'        => $avgScore,
            'min_score'        => $minScore,
            'max_score'        => $maxScore,
            'cbt_tests'        => $cbtTests,
            'conversion_url'   => $conversionUrl,
        ];
    }

    /**
     * TAHAP 4: Ambil Rekapitulasi Keterlaksanaan Pembelajaran per Kelas dan Mapel
     */
    public function getJournalLearningRecap(int $classId, ?int $subjectId = null, ?int $academicYearId = null, ?int $semester = null): array
    {
        $builder = $this->db->table('teaching_journals tj')
            ->select('tj.*, s.name as subject_name, c.name as class_name, atp.lingkup_materi, atp.semester as atp_semester')
            ->join('subjects s', 's.id = tj.subject_id', 'left')
            ->join('classes c', 'c.id = tj.class_id', 'left')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = tj.atp_id', 'left')
            ->where('tj.class_id', $classId);

        if ($subjectId) {
            $builder->where('tj.subject_id', $subjectId);
        }

        $journals = $builder->orderBy('tj.date', 'DESC')->get()->getResultArray();

        $totalSessions = count($journals);
        $terlaksanaPenuh = 0;
        $terlaksanaSebagian = 0;
        $tertunda = 0;

        foreach ($journals as $j) {
            $status = $j['status_realisasi'] ?? 'terlaksana';
            if ($status === 'sebagian') {
                $terlaksanaSebagian++;
            } elseif ($status === 'tertunda') {
                $tertunda++;
            } else {
                $terlaksanaPenuh++;
            }
        }

        $progresEfektif = ($totalSessions > 0)
            ? round((($terlaksanaPenuh + (0.5 * $terlaksanaSebagian)) / $totalSessions) * 100, 1)
            : 0;

        // Ambil materi digital (E-Learning) yang sudah diterbitkan
        $matQuery = $this->db->table('subject_materials sm')
            ->select('sm.id, sm.title, sm.parent_id, sm.is_published')
            ->where('sm.is_published', 1);

        if ($subjectId) {
            $matQuery->where('sm.subject_id', $subjectId);
        }

        $materials = $matQuery->get()->getResultArray();

        return [
            'class_id'             => $classId,
            'subject_id'           => $subjectId,
            'total_sessions'       => $totalSessions,
            'terlaksana_penuh'     => $terlaksanaPenuh,
            'terlaksana_sebagian'  => $terlaksanaSebagian,
            'tertunda'             => $tertunda,
            'progres_efektif'      => $progresEfektif,
            'journals'             => $journals,
            'digital_materials'    => $materials,
        ];
    }
}

