<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;

/**
 * AcademicCalendar
 *
 * Kalender Akademik Terpadu — menggabungkan semua event dalam satu kalender:
 *   - Agenda kelas / sekolah
 *   - Hari libur nasional
 *   - Jadwal pelajaran (recurring → di-expand ke tanggal konkret)
 *   - Deadline tugas
 *   - Jadwal ujian (exam_schedules)
 *   - Jadwal CBT online
 *   - Kuis mandiri (window publish)
 *
 * GET /kalender           → halaman kalender (view)
 * GET /kalender/events    → JSON events untuk FullCalendar
 *                           ?start=YYYY-MM-DD&end=YYYY-MM-DD
 */
class AcademicCalendar extends BaseController
{
    protected $db;
    protected AcademicYearModel $yearModel;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->yearModel = new AcademicYearModel();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────
    private function user(): array { return session()->get('user') ?? []; }
    private function roleId(): int { return (int) ($this->user()['role_id'] ?? 0); }
    private function studentId(): int
    {
        $u = $this->user();
        return (int) ($u['student_id'] ?? $u['related_id'] ?? 0);
    }

    // =========================================================
    // VIEW — halaman kalender
    // GET /kalender
    // =========================================================
    public function index(): string
    {
        $activeYear = $this->yearModel->getActiveYear();
        $roleId     = $this->roleId();

        return view('kalender/index', [
            'title'      => 'Kalender Akademik',
            'activeYear' => $activeYear,
            'roleId'     => $roleId,
        ]);
    }

    // =========================================================
    // EVENTS — JSON endpoint untuk FullCalendar
    // GET /kalender/events?start=YYYY-MM-DD&end=YYYY-MM-DD
    // =========================================================
    public function events(): \CodeIgniter\HTTP\Response
    {
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end   = $this->request->getGet('end')   ?? date('Y-m-t');

        // Sanitasi input
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $start)) $start = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $end))   $end   = date('Y-m-t');

        // Ambil hanya tanggal (potong jam jika ada dari FullCalendar)
        $start = substr($start, 0, 10);
        $end   = substr($end,   0, 10);

        $roleId    = $this->roleId();
        $studentId = $this->studentId();
        $activeYear = $this->yearModel->getActiveYear();
        $yearId    = (int) ($activeYear['id'] ?? 0);

        // Tentukan class_id berdasarkan role
        $classId    = null;
        $className  = null;

        if (in_array($roleId, [4, 5])) {
            // Siswa / Ortu
            $rec = $this->db->table('student_records')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $yearId)
                ->where('status', 'aktif')
                ->get()->getRowArray();
            $classId   = (int) ($rec['class_id'] ?? 0) ?: null;
            if ($classId) {
                $cls = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
                $className = $cls['name'] ?? null;
            }
        } elseif ($roleId == 3) {
            // Guru — gunakan kelas wali jika ada, kalau tidak pakai query gabungan
            $teacher = $this->db->table('teachers')
                ->where('user_id', $this->user()['id'])->get()->getRowArray();
            if ($teacher) {
                $wali = $this->db->table('classes')
                    ->where('teacher_id', $teacher['id'])->get()->getRowArray();
                $classId  = $wali ? (int) $wali['id'] : null;
                $className= $wali ? $wali['name'] : null;
            }
        }
        // Admin/Kepsek/Staf: classId tetap null → ambil semua

        $events = [];

        // ── 1. Hari Libur ─────────────────────────────────────────────────
        $holidays = $this->db->table('holidays')
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->get()->getResultArray();

        foreach ($holidays as $h) {
            $events[] = [
                'id'         => 'holiday-' . $h['id'],
                'type'       => 'holiday',
                'title'      => '🏖 ' . $h['description'],
                'start'      => $h['date'],
                'allDay'     => true,
                'color'      => '#dc3545',
                'textColor'  => '#fff',
                'description'=> $h['description'],
                'classNames' => ['event-holiday'],
            ];
        }

        // ── 2. Agenda Kelas ───────────────────────────────────────────────
        $agendaBuilder = $this->db->table('agendas a')
            ->select('a.id, a.title, a.description, a.date, a.start_time, a.end_time, a.is_public, c.name as class_name')
            ->join('classes c', 'c.id = a.class_id', 'left')
            ->where('a.date >=', $start)
            ->where('a.date <=', $end);

        if ($classId) {
            $agendaBuilder->groupStart()
                ->where('a.class_id', $classId)
                ->orWhere('a.is_public', 1)
                ->orWhere('a.class_id IS NULL')
                ->groupEnd();
        }

        $agendas = $agendaBuilder->orderBy('a.date, a.start_time')->get()->getResultArray();

        // De-duplicate siblings (sama title+date+creator)
        $seen = [];
        foreach ($agendas as $a) {
            $key = $a['title'] . '_' . $a['date'] . '_' . ($a['start_time'] ?? '');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $startDt = $a['date'] . ($a['start_time'] ? 'T' . $a['start_time'] : '');
            $endDt   = $a['end_time']   ? $a['date'] . 'T' . $a['end_time'] : null;

            $events[] = [
                'id'         => 'agenda-' . $a['id'],
                'type'       => 'agenda',
                'title'      => '📅 ' . $a['title'],
                'start'      => $startDt,
                'end'        => $endDt,
                'allDay'     => !$a['start_time'],
                'color'      => '#0d6efd',
                'description'=> strip_tags($a['description'] ?? ''),
                'class_name' => $a['class_name'] ?? 'Umum',
                'classNames' => ['event-agenda'],
            ];
        }

        // ── 3. Jadwal Pelajaran (recurring → expand ke tanggal) ───────────
        $scheduleBuilder = $this->db->table('schedules s')
            ->select('s.day_of_week, s.start_time, s.end_time, sub.name as subject_name, c.name as class_name, t.name as teacher_name')
            ->join('subjects sub', 'sub.id = s.subject_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('teachers t', 't.id = s.teacher_id', 'left')
            ->where('s.academic_year_id', $yearId);

        if ($classId) {
            $scheduleBuilder->where('s.class_id', $classId);
        }

        $schedules = $scheduleBuilder->get()->getResultArray();

        if (!empty($schedules)) {
            // Buat map hari libur untuk skip
            $holidayDates = array_column($holidays, 'date');

            // Expand recurring schedule ke tiap tanggal dalam rentang
            $cur = strtotime($start);
            $fin = strtotime($end);

            while ($cur <= $fin) {
                $dateStr    = date('Y-m-d', $cur);
                $dayOfWeek  = (int) date('w', $cur); // 0=Sun,6=Sat

                // Skip hari libur
                if (!in_array($dateStr, $holidayDates)) {
                    foreach ($schedules as $sc) {
                        if ((int) $sc['day_of_week'] === $dayOfWeek) {
                            $events[] = [
                                'id'          => 'schedule-' . $dateStr . '-' . md5($sc['subject_name'] . $sc['class_name']),
                                'type'        => 'schedule',
                                'title'       => '📚 ' . $sc['subject_name'],
                                'start'       => $dateStr . 'T' . $sc['start_time'],
                                'end'         => $dateStr . 'T' . $sc['end_time'],
                                'color'       => '#6f42c1',
                                'description' => $sc['teacher_name'] . ' — ' . $sc['class_name'],
                                'class_name'  => $sc['class_name'],
                                'teacher'     => $sc['teacher_name'],
                                'classNames'  => ['event-schedule'],
                            ];
                        }
                    }
                }
                $cur = strtotime('+1 day', $cur);
            }
        }

        // ── 4. Tugas — deadline event ─────────────────────────────────────
        $tugasBuilder = $this->db->table('tugas t')
            ->select('t.id, t.judul, t.selesai_at, t.mulai_at, sub.name as subject_name, c.name as class_name')
            ->join('subjects sub', 'sub.id = t.subject_id', 'left')
            ->join('classes c', 'c.id = t.class_id', 'left')
            ->where('DATE(t.selesai_at) >=', $start)
            ->where('DATE(t.selesai_at) <=', $end);

        if ($classId) {
            $tugasBuilder->where('t.class_id', $classId);
        }

        $tugas = $tugasBuilder->get()->getResultArray();

        foreach ($tugas as $tg) {
            // Cek apakah siswa sudah submit (hanya untuk role siswa)
            $submitted = false;
            if ($roleId == 5 && $studentId) {
                $sub = $this->db->table('tugas_submissions')
                    ->where('tugas_id', $tg['id'])
                    ->where('student_id', $studentId)
                    ->where('dikumpul_at IS NOT NULL')
                    ->countAllResults();
                $submitted = $sub > 0;
            }

            $url = in_array($roleId, [4, 5])
                ? base_url('siswa/tugas/' . $tg['id'])
                : base_url('admin/tugas/' . $tg['id']);

            $events[] = [
                'id'          => 'tugas-' . $tg['id'],
                'type'        => 'tugas',
                'title'       => ($submitted ? '✓ ' : '📝 ') . $tg['judul'],
                'start'       => $tg['mulai_at'],
                'end'         => $tg['selesai_at'],
                'color'       => $submitted ? '#198754' : '#fd7e14',
                'url'         => $url,
                'description' => $tg['subject_name'] . ' — Deadline: ' . date('d M Y H:i', strtotime($tg['selesai_at'])),
                'class_name'  => $tg['class_name'],
                'submitted'   => $submitted,
                'classNames'  => ['event-tugas'],
            ];
        }

        // ── 5. Jadwal Ujian (exam_schedules) ─────────────────────────────
        $examBuilder = $this->db->table('exam_schedules es')
            ->select('es.id, es.exam_date, es.start_time, es.end_time, es.description, sub.name as subject_name, c.name as class_name')
            ->join('subjects sub', 'sub.id = es.subject_id', 'left')
            ->join('classes c', 'c.id = es.class_id', 'left')
            ->where('es.exam_date >=', $start)
            ->where('es.exam_date <=', $end);

        if ($classId) {
            $examBuilder->where('es.class_id', $classId);
        }

        $exams = $examBuilder->get()->getResultArray();

        foreach ($exams as $ex) {
            $events[] = [
                'id'          => 'exam-' . $ex['id'],
                'type'        => 'exam',
                'title'       => '📋 ' . $ex['subject_name'],
                'start'       => $ex['exam_date'] . 'T' . $ex['start_time'],
                'end'         => $ex['end_time'] ? $ex['exam_date'] . 'T' . $ex['end_time'] : null,
                'color'       => '#20c997',
                'description' => ($ex['description'] ?? '') . ' — ' . $ex['class_name'],
                'class_name'  => $ex['class_name'],
                'classNames'  => ['event-exam'],
            ];
        }

        // ── 6. CBT Online ─────────────────────────────────────────────────
        $cbtBuilder = $this->db->table('cbt_test_status cts')
            ->select('cts.id, cts.start_time, cts.end_time, cts.class_codes, sub.name as subject_name, en.name as exam_name')
            ->join('cbt_question_banks qb', 'qb.id = cts.bank_id', 'left')
            ->join('subjects sub', 'sub.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = cts.exam_name_id', 'left')
            ->where('cts.is_visible', 1)
            ->where('DATE(cts.start_time) <=', $end)
            ->where('DATE(cts.end_time) >=', $start);

        // Filter berdasarkan kelas (class_codes adalah string JSON array nama kelas)
        if ($className) {
            $cbtBuilder->like('cts.class_codes', $className);
        }

        $cbts = $cbtBuilder->get()->getResultArray();

        foreach ($cbts as $cbt) {
            $url = in_array($roleId, [4, 5])
                ? base_url('siswa/cbt')
                : base_url('admin/cbt/aktivitas');

            $events[] = [
                'id'          => 'cbt-' . $cbt['id'],
                'type'        => 'cbt',
                'title'       => '💻 CBT: ' . ($cbt['exam_name'] ?? '') . ' ' . ($cbt['subject_name'] ?? ''),
                'start'       => $cbt['start_time'],
                'end'         => $cbt['end_time'],
                'color'       => '#e83e8c',
                'url'         => $url,
                'description' => ($cbt['subject_name'] ?? '') . ' — ' . ($cbt['exam_name'] ?? ''),
                'classNames'  => ['event-cbt'],
            ];
        }

        // ── 7. Kuis Mandiri (window: published, belum expired) ────────────
        $quizBuilder = $this->db->table('quiz_configs qc')
            ->select('qc.id, qc.title, qc.created_at, sub.name as subject_name')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id', 'left')
            ->join('subjects sub', 'sub.id = qb.subject_id', 'left')
            ->where('qc.is_published', 1)
            ->where('DATE(qc.created_at) <=', $end);

        $quizzes = $quizBuilder->get()->getResultArray();

        foreach ($quizzes as $qz) {
            // Tampilkan sebagai event tunggal pada tanggal publish
            $date = substr($qz['created_at'], 0, 10);
            if ($date < $start || $date > $end) continue;

            $url = in_array($roleId, [4, 5])
                ? base_url('siswa/quiz')
                : base_url('admin/quiz');

            $events[] = [
                'id'          => 'quiz-' . $qz['id'],
                'type'        => 'quiz',
                'title'       => '✏️ Kuis: ' . $qz['title'],
                'start'       => $date,
                'allDay'      => true,
                'color'       => '#0dcaf0',
                'textColor'   => '#000',
                'url'         => $url,
                'description' => $qz['subject_name'] ?? '',
                'classNames'  => ['event-quiz'],
            ];
        }

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode(array_values($events)));
    }
}
