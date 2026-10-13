<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\LandingSliderModel;
use App\Models\SchoolModel;
use App\Models\ArticleModel;
use App\Models\AgendaModel;
use App\Models\AnnouncementModel;
use App\Models\TeacherModel;
use App\Models\LandingLinkModel;
use App\Models\FacilityModel;
use App\Models\ActivityModel;
use App\Models\ChangelogModel;

class Home extends BaseController
{
    public function index()
    {
        helper('text');
        $sliderModel       = new LandingSliderModel();
        $schoolModel       = new SchoolModel();
        $articleModel      = new ArticleModel();
        $agendaModel       = new AgendaModel();
        $announcementModel = new AnnouncementModel();
        $teacherModel      = new TeacherModel();
        $facilityModel     = new FacilityModel();
        $activityModel     = new ActivityModel();
        $linkModel         = new LandingLinkModel();
        $studentModel      = new \App\Models\StudentModel();
        $classModel        = new \App\Models\ClassModel();
        $ekskulModel       = new \App\Models\EkskulModel();
        $academicYearModel = new \App\Models\AcademicYearModel();

        $activeYear   = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        $db           = \Config\Database::connect();

        // 1. Peserta Didik Aktif (siswa yang berstatus 'aktif' pada tahun ajaran aktif, bukan hanya terdaftar)
        $totalStudents = 0;
        if (!empty($activeYearId)) {
            $totalStudents = $db->table('student_records')
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'aktif')
                ->countAllResults();
        }
        if ($totalStudents === 0) {
            $totalStudents = $db->table('student_records')
                ->where('status', 'aktif')
                ->countAllResults();
            if ($totalStudents === 0) {
                $totalStudents = $studentModel->countAllResults();
            }
        }

        // 2. Guru & Tenaga Pendidik
        $totalTeachers = $teacherModel->countAllResults();

        // 3. Rombongan Belajar (Kelas) Aktif (hanya rombel yang berstatus aktif / is_active = 1, bukan semua rombel yang ada di database)
        $totalClasses = $classModel->where('is_active', 1)->countAllResults();
        if ($totalClasses === 0) {
            $totalClasses = $classModel->countAllResults();
        }

        // 4. Ekstrakurikuler Aktif
        $totalEkskuls = $ekskulModel->where('is_active', 1)->countAllResults();

        // 5. Jam Operasional (Dinamis dari jadwal pelajaran dengan fallback standar)
        $scheduleRow = $db->table('schedules')
            ->select('MIN(start_time) as min_start, MAX(end_time) as max_end, MIN(day_of_week) as min_day, MAX(day_of_week) as max_day');
        if (!empty($activeYearId)) {
            $scheduleRow->where('academic_year_id', $activeYearId);
        }
        $scheduleStats = $scheduleRow->get()->getRowArray();

        // Fallback jika tahun ajaran aktif belum memiliki jadwal, periksa seluruh jadwal yang ada
        if (empty($scheduleStats['min_start']) && !empty($activeYearId)) {
            $scheduleStats = $db->table('schedules')
                ->select('MIN(start_time) as min_start, MAX(end_time) as max_end, MIN(day_of_week) as min_day, MAX(day_of_week) as max_day')
                ->get()->getRowArray();
        }

        if (!empty($scheduleStats['min_start']) && !empty($scheduleStats['max_end'])) {
            $formattedStart = date('H.i', strtotime($scheduleStats['min_start']));
            $formattedEnd   = date('H.i', strtotime($scheduleStats['max_end']));
            $timeString     = "{$formattedStart} - {$formattedEnd} WIB";

            // Deteksi hari aktif sekolah (berdasarkan pengaturan school_days tahun ajaran atau data jadwal)
            $schoolDaysCount = $activeYear['school_days'] ?? null;
            if ($schoolDaysCount == 6 || ($scheduleStats['max_day'] ?? 0) >= 6) {
                $daysString    = "Senin - Sabtu";
                $offDaysString = "Minggu: Libur";
            } else {
                $daysString    = "Senin - Jumat";
                $offDaysString = "Sabtu - Minggu: Libur";
            }

            $operationalHours = [
                'has_schedule'  => true,
                'time_range'    => $timeString,
                'days_range'    => $daysString,
                'off_days'      => $offDaysString,
                'service_text'  => $timeString,
                'schedule_text' => "{$daysString}: {$timeString}",
            ];
        } else {
            // Template Standar Fallback
            $operationalHours = [
                'has_schedule'  => false,
                'time_range'    => '07.00 - 15.00 WIB',
                'days_range'    => 'Senin - Jumat',
                'off_days'      => 'Sabtu - Minggu: Libur',
                'service_text'  => '07.00 - 15.00 WIB',
                'schedule_text' => 'Senin - Jumat: 07.00 - 15.00 WIB',
            ];
        }

        $stats = [
            'students' => $totalStudents > 0 ? $totalStudents : 240,
            'teachers' => $totalTeachers > 0 ? $totalTeachers : 18,
            'classes'  => $totalClasses > 0 ? $totalClasses : 8,
            'ekskuls'  => $totalEkskuls > 0 ? $totalEkskuls : 6,
        ];

        $data = [
            'sliders'          => $sliderModel->where('is_active', 1)->orderBy('order', 'ASC')->findAll(),
            'school'           => $schoolModel->first() ?: [],
            'articles'         => $articleModel->where('is_published', 1)->orderBy('created_at', 'DESC')->findAll(3),
            'agendas'          => $agendaModel->where('is_public', 1)->where('date >=', date('Y-m-d'))->orderBy('date', 'ASC')->findAll(5),
            'announcements'    => $announcementModel->where('is_public', 1)->orderBy('created_at', 'DESC')->findAll(3),
            'teachers'         => $teacherModel->getPublicTeachers(),
            'facilities'       => $facilityModel->findAll(),
            'activities'       => $activityModel->getPublicActivities(),
            'links'            => $linkModel->where('is_active', 1)->orderBy('order_no', 'ASC')->findAll(),
            'ekskuls'          => $ekskulModel->where('is_active', 1)->findAll(),
            'stats'            => $stats,
            'operationalHours' => $operationalHours,
            'title'            => 'Beranda'
        ];

        return view('index', $data);
    }

    public function profil(): string
    {
        $schoolModel = new SchoolModel();
        $data['school'] = $schoolModel->first() ?: [];
        return view('pages/profil', $data);
    }

    public function about(): string
    {
        $schoolModel = new SchoolModel();
        $changelogModel = new ChangelogModel();

        $data = [
            'school' => $schoolModel->first() ?: [],
            'title' => 'Tentang Aplikasi',
            'is_logged_in' => session()->has('user'),
            'changelogs' => $changelogModel->orderBy('release_date', 'DESC')->orderBy('version', 'DESC')->findAll()
        ];
        return view('pages/about', $data);
    }
}
