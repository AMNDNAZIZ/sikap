<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\ScheduleModel;

class Schedules extends BaseController
{
    protected $db;
    protected $scheduleModel;
    protected $academicYearModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->scheduleModel = new ScheduleModel();
        $this->academicYearModel = new AcademicYearModel();
        helper('jp');
    }

    public function index()
    {
        $user = session()->get('user');
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $studentId = $user['student_id'] ?? $user['related_id'] ?? null;
        if (!$studentId) {
            return redirect()->to('/dashboard')->with('error', 'Data siswa tidak ditemukan pada akun ini.');
        }

        // Ambil tahun ajaran aktif
        $activeYear = $this->academicYearModel->getActiveYear();
        if (!$activeYear) {
            $activeYear = $this->academicYearModel->orderBy('start_date', 'DESC')->first();
        }

        // Data Siswa
        $student = $this->db->table('students')->where('id', $studentId)->get()->getRowArray();

        // Cari record kelas aktif siswa
        $recordBuilder = $this->db->table('student_records sr')
            ->select('sr.class_id, sr.academic_year_id, c.name as class_name, c.level, t.name as homeroom_teacher')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('sr.student_id', $studentId)
            ->where('sr.status', 'aktif');

        if (!empty($activeYear['id'])) {
            $recordBuilder->where('sr.academic_year_id', $activeYear['id']);
        }
        $studentRecord = $recordBuilder->orderBy('sr.id', 'DESC')->get()->getRowArray();

        // Fallback jika belum tersinkronisasi di tahun aktif tertentu
        if (!$studentRecord) {
            $studentRecord = $this->db->table('student_records sr')
                ->select('sr.class_id, sr.academic_year_id, c.name as class_name, c.level, t.name as homeroom_teacher')
                ->join('classes c', 'c.id = sr.class_id', 'left')
                ->join('teachers t', 't.id = c.teacher_id', 'left')
                ->where('sr.student_id', $studentId)
                ->where('sr.status', 'aktif')
                ->orderBy('sr.id', 'DESC')
                ->get()->getRowArray();
        }

        $classId = $studentRecord['class_id'] ?? null;
        $className = $studentRecord['class_name'] ?? '-';
        $homeroomTeacher = $studentRecord['homeroom_teacher'] ?? null;

        $schedules = [];
        if ($classId && !empty($activeYear['id'])) {
            $schedules = $this->scheduleModel->getSchedulesByClass($classId, $activeYear['id']);
        }

        // Definisi hari
        $schoolDays = (int) ($activeYear['school_days'] ?? 5);
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        // Kelompokkan jadwal per hari
        $weeklySchedules = [];
        $totalSessions = count($schedules);
        $totalJP = 0;
        $uniqueSubjects = [];

        foreach ($schedules as $s) {
            $dayNum = (int) $s['day_of_week'];
            if (!isset($weeklySchedules[$dayNum])) {
                $weeklySchedules[$dayNum] = [];
            }
            $jp = function_exists('hitung_jp_dari_waktu') 
                ? hitung_jp_dari_waktu($s['start_time'], $s['end_time']) 
                : 1;
            $s['jp'] = $jp;
            $totalJP += $jp;
            if (!empty($s['subject_id'])) {
                $uniqueSubjects[$s['subject_id']] = $s['subject_name'] ?? '';
            }
            $weeklySchedules[$dayNum][] = $s;
        }

        // Susun daftar hari yang ditampilkan (hari sekolah reguler atau hari yang ada jadwalnya)
        $displayDays = [];
        foreach ($days as $num => $name) {
            if ($num <= $schoolDays || !empty($weeklySchedules[$num])) {
                $displayDays[$num] = [
                    'name' => $name,
                    'items' => $weeklySchedules[$num] ?? [],
                    'total_jp' => array_sum(array_column($weeklySchedules[$num] ?? [], 'jp')),
                ];
            }
        }

        $data = [
            'title'           => 'Jadwal Pelajaran',
            'user'            => $user,
            'student'         => $student,
            'studentRecord'   => $studentRecord,
            'className'       => $className,
            'homeroomTeacher' => $homeroomTeacher,
            'activeYear'      => $activeYear,
            'displayDays'     => $displayDays,
            'days'            => $days,
            'totalSessions'   => $totalSessions,
            'totalJP'         => $totalJP,
            'totalSubjects'   => count($uniqueSubjects),
            'todayDayNum'     => (int) date('N'),
        ];

        return view('siswa/schedules/index', $data);
    }
}
