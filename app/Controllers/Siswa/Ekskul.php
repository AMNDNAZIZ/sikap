<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\EkskulModel;
use App\Models\EkskulMemberModel;
use App\Models\EkskulAttendanceModel;
use App\Models\EkskulScoreModel;
use App\Models\EkskulJurnalModel;
use App\Models\AcademicYearModel;

class Ekskul extends BaseController
{
    protected $ekskulModel;
    protected $memberModel;
    protected $attendanceModel;
    protected $scoreModel;
    protected $jurnalModel;
    protected $academicYearModel;

    public function __construct()
    {
        $this->ekskulModel = new EkskulModel();
        $this->memberModel = new EkskulMemberModel();
        $this->attendanceModel = new EkskulAttendanceModel();
        $this->scoreModel = new EkskulScoreModel();
        $this->jurnalModel = new EkskulJurnalModel();
        $this->academicYearModel = new AcademicYearModel();
    }

    private function getActiveYear()
    {
        return $this->academicYearModel->getActiveYear();
    }

    private function getStudentId()
    {
        return session()->get('related_id'); // Pada SIAKAD ini related_id dari user(siswa) = student_id
    }

    /**
     * Halaman Utama Siswa (Ekskul Saya & Katalog)
     */
    public function index()
    {
        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tahun ajaran belum diset.');
        }

        $student_id = $this->getStudentId();

        // 1. Ambil ekskul yang saya ikuti
        $myEkskuls = $this->memberModel->select('ekskul_members.*, ekskul_master.name, ekskul_master.kode, ekskul_master.category')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_members.ekskul_id')
            ->where('student_id', $student_id)
            ->where('academic_year_id', $activeYear['id'])
            ->findAll();

        $myEkskulIds = array_column($myEkskuls, 'ekskul_id');

        // 2. Ambil katalog ekskul aktif yang belum diikuti
        $katalogQuery = $this->ekskulModel->where('is_active', 1);
        if (!empty($myEkskulIds)) {
            $katalogQuery->whereNotIn('id', $myEkskulIds);
        }
        $availableEkskuls = $katalogQuery->findAll();

        $data = [
            'title' => 'Ekstrakurikuler',
            'myEkskuls' => $myEkskuls,
            'availableEkskuls' => $availableEkskuls,
            'activeYear' => $activeYear
        ];

        return view('siswa/ekskul/index', $data);
    }

    /**
     * Mendaftar ke Ekskul
     */
    public function daftar($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tahun ajaran belum diset.');
        }

        $student_id = $this->getStudentId();

        // Cek apakah sudah terdaftar
        $existing = $this->memberModel->where('ekskul_id', $ekskul_id)
            ->where('student_id', $student_id)
            ->where('academic_year_id', $activeYear['id'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Anda sudah mendaftar di ekstrakurikuler ini.');
        }

        $this->memberModel->insert([
            'ekskul_id' => $ekskul_id,
            'student_id' => $student_id,
            'academic_year_id' => $activeYear['id'],
            'status' => 'pending' // Menunggu persetujuan pembina
        ]);

        return redirect()->back()->with('success', 'Berhasil mendaftar. Menunggu persetujuan pembina ekstrakurikuler.');
    }

    /**
     * Lihat Detail Ekskul Saya (Nilai, Absensi)
     */
    public function detail($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $student_id = $this->getStudentId();

        // Cek apakah benar member
        $membership = $this->memberModel->where('ekskul_id', $ekskul_id)
            ->where('student_id', $student_id)
            ->where('academic_year_id', $activeYear['id'])
            ->first();

        if (!$membership) {
            return redirect()->to('/siswa/ekskul')->with('error', 'Akses ditolak.');
        }

        $ekskul = $this->ekskulModel->find($ekskul_id);

        // Ambil Nilai Akhir
        $activeSemester = ((int) date('n') >= 7) ? '1' : '2';
        $semester = $this->request->getGet('semester') ?? $activeSemester;
        $semester = in_array((string)$semester, ['1', '2']) ? (string)$semester : '1';
        $semValues = ($semester === '2') ? ['2', 'genap'] : ['1', 'ganjil'];

        $score = $this->scoreModel->where('ekskul_id', $ekskul_id)
            ->where('student_id', $student_id)
            ->where('academic_year_id', $activeYear['id'])
            ->whereIn('semester', $semValues)
            ->first();

        // Hitung Kehadiran
        // 1. Total Pertemuan (Jurnal)
        $totalPertemuan = $this->jurnalModel->where('ekskul_id', $ekskul_id)
            ->where('academic_year_id', $activeYear['id'])
            ->countAllResults();

        // 2. Ketidakhadiran Saya (Dari Attendance)
        // Gabung jurnal dan attendance untuk mendapatkan data rinci
        $absences = $this->attendanceModel->select('ekskul_attendances.*, ekskul_jurnal.date, ekskul_jurnal.materi')
            ->join('ekskul_jurnal', 'ekskul_jurnal.id = ekskul_attendances.jurnal_id')
            ->where('ekskul_jurnal.ekskul_id', $ekskul_id)
            ->where('ekskul_attendances.user_id', $student_id)
            ->where('ekskul_attendances.user_type', 'siswa')
            ->findAll();

        $totalTidakHadir = count($absences);
        $totalHadir = $totalPertemuan - $totalTidakHadir;
        if ($totalHadir < 0) $totalHadir = 0; // fallback

        $data = [
            'title' => 'Detail Ekskul: ' . $ekskul['name'],
            'ekskul' => $ekskul,
            'membership' => $membership,
            'score' => $score,
            'semester' => $semester,
            'totalPertemuan' => $totalPertemuan,
            'totalHadir' => $totalHadir,
            'absences' => $absences,
            'activeYear' => $activeYear
        ];

        return view('siswa/ekskul/detail', $data);
    }
}
