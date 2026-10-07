<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EkskulModel;
use App\Models\EkskulPembinaModel;
use App\Models\EkskulMemberModel;
use App\Models\EkskulJurnalModel;
use App\Models\EkskulAttendanceModel;
use App\Models\EkskulScoreModel;
use App\Models\StudentModel;
use App\Models\AcademicYearModel;
use CodeIgniter\I18n\Time;

class EkskulPembina extends BaseController
{
    protected $ekskulModel;
    protected $pembinaModel;
    protected $memberModel;
    protected $jurnalModel;
    protected $attendanceModel;
    protected $scoreModel;
    protected $studentModel;
    protected $academicYearModel;

    public function __construct()
    {
        $this->ekskulModel = new EkskulModel();
        $this->pembinaModel = new EkskulPembinaModel();
        $this->memberModel = new EkskulMemberModel();
        $this->jurnalModel = new EkskulJurnalModel();
        $this->attendanceModel = new EkskulAttendanceModel();
        $this->scoreModel = new EkskulScoreModel();
        $this->studentModel = new StudentModel();
        $this->academicYearModel = new AcademicYearModel();
    }

    private function getActiveYear()
    {
        return $this->academicYearModel->getActiveYear();
    }

    /**
     * Dashboard Pembina - Daftar ekskul yang diampu
     */
    public function index()
    {
        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif belum diset.');
        }

        $userId = session()->get('user')['id'] ?? 0;

        $myEkskuls = $this->pembinaModel->select('ekskul_pembina.*, ekskul_master.name, ekskul_master.kode')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_pembina.ekskul_id')
            ->where('user_id', $userId)
            ->where('academic_year_id', $activeYear['id'])
            ->findAll();

        $data = [
            'title' => 'Dashboard Pembina Ekskul',
            'myEkskuls' => $myEkskuls,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/index', $data);
    }

    /**
     * Kelola Anggota
     */
    public function members($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        
        // Cek hak akses pembina
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id']);
        
        // Ambil semua siswa aktif untuk dropdown tambah (filter by active student records)
        $db = \Config\Database::connect();
        $allStudents = $db->table('students')
            ->select('students.id, students.name, students.nis, classes.name as class_name')
            ->join('student_records', 'student_records.student_id = students.id')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.academic_year_id', $activeYear['id'])
            ->where('student_records.status', 'aktif')
            ->orderBy('classes.name', 'ASC')
            ->orderBy('students.name', 'ASC')
            ->get()->getResultArray();

        $data = [
            'title' => 'Anggota Ekskul: ' . $ekskul['name'],
            'ekskul' => $ekskul,
            'members' => $members,
            'allStudents' => $allStudents,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/members', $data);
    }

    public function storeMember($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $student_id = $this->request->getPost('student_id');

        // Cek apakah sudah terdaftar
        $existing = $this->memberModel->where('ekskul_id', $ekskul_id)
            ->where('student_id', $student_id)
            ->where('academic_year_id', $activeYear['id'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Siswa tersebut sudah menjadi anggota.');
        }

        $this->memberModel->insert([
            'ekskul_id' => $ekskul_id,
            'student_id' => $student_id,
            'academic_year_id' => $activeYear['id'],
            'status' => 'aktif'
        ]);

        return redirect()->back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function removeMember($member_id)
    {
        $member = $this->memberModel->find($member_id);
        if ($member) {
            $this->checkAccess($member['ekskul_id'], $member['academic_year_id']);
            $this->memberModel->delete($member_id);
            return redirect()->back()->with('success', 'Anggota berhasil dikeluarkan.');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }

    /**
     * Jurnal & Absensi
     */
    public function jurnal($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        $jurnals = $this->jurnalModel->getJurnal($ekskul_id, $activeYear['id']);

        $data = [
            'title' => 'Jurnal & Absensi: ' . $ekskul['name'],
            'ekskul' => $ekskul,
            'jurnals' => $jurnals,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/jurnal', $data);
    }

    public function createJurnal($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id']);

        $data = [
            'title' => 'Input Jurnal Kegiatan',
            'ekskul' => $ekskul,
            'members' => $members,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/create_jurnal', $data);
    }

    public function storeJurnal($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $date = $this->request->getPost('date');
        $materi = $this->request->getPost('materi');
        $attendances = $this->request->getPost('attendances'); // Array of status keyed by student_id

        // Insert Jurnal
        $jurnalData = [
            'ekskul_id' => $ekskul_id,
            'academic_year_id' => $activeYear['id'],
            'date' => $date,
            'materi' => $materi,
            'pembina_user_id' => session()->get('user')['id'] ?? 0
        ];
        
        $this->jurnalModel->insert($jurnalData);
        $jurnal_id = $this->jurnalModel->getInsertID();

        // Process Attendances (Hanya insert yang tidak hadir / non-hadir)
        if (!empty($attendances) && is_array($attendances)) {
            $batchData = [];
            foreach ($attendances as $student_id => $status) {
                if ($status != 'hadir') {
                    $batchData[] = [
                        'jurnal_id' => $jurnal_id,
                        'user_id' => $student_id,
                        'user_type' => 'siswa',
                        'status' => $status,
                        'notes' => $this->request->getPost('notes')[$student_id] ?? null
                    ];
                }
            }
            if (count($batchData) > 0) {
                $this->attendanceModel->insertBatch($batchData);
            }
        }

        return redirect()->to('/admin/ekskul-pembina/jurnal/' . $ekskul_id)->with('success', 'Jurnal dan absensi berhasil disimpan.');
    }

    public function deleteJurnal($jurnal_id)
    {
        $jurnal = $this->jurnalModel->find($jurnal_id);
        if ($jurnal) {
            $this->checkAccess($jurnal['ekskul_id'], $jurnal['academic_year_id']);
            $this->jurnalModel->delete($jurnal_id);
            return redirect()->back()->with('success', 'Jurnal berhasil dihapus.');
        }
        return redirect()->back()->with('error', 'Data tidak ditemukan.');
    }

    public function editJurnal($jurnal_id)
    {
        $jurnal = $this->jurnalModel->find($jurnal_id);
        if (!$jurnal) {
            return redirect()->back()->with('error', 'Jurnal tidak ditemukan.');
        }
        $activeYear = $this->getActiveYear();
        $this->checkAccess($jurnal['ekskul_id'], $activeYear['id']);

        $ekskul = $this->ekskulModel->find($jurnal['ekskul_id']);
        $members = $this->memberModel->getMembers($jurnal['ekskul_id'], $activeYear['id']);
        
        // Ambil data absensi untuk jurnal ini
        $attendances = $this->attendanceModel->where('jurnal_id', $jurnal_id)->findAll();
        $attMap = [];
        $notesMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['user_id']] = $a['status'];
            $notesMap[$a['user_id']] = $a['notes'];
        }

        $data = [
            'title' => 'Edit Jurnal & Absensi',
            'ekskul' => $ekskul,
            'jurnal' => $jurnal,
            'members' => $members,
            'attMap' => $attMap,
            'notesMap' => $notesMap,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/edit_jurnal', $data);
    }

    public function updateJurnal($jurnal_id)
    {
        $jurnal = $this->jurnalModel->find($jurnal_id);
        if (!$jurnal) return redirect()->back()->with('error', 'Jurnal tidak ditemukan.');
        
        $activeYear = $this->getActiveYear();
        $this->checkAccess($jurnal['ekskul_id'], $activeYear['id']);

        $date = $this->request->getPost('date');
        $materi = $this->request->getPost('materi');
        $attendances = $this->request->getPost('attendances');

        $this->jurnalModel->update($jurnal_id, [
            'date' => $date,
            'materi' => $materi
        ]);

        // Hapus absensi lama
        $this->attendanceModel->where('jurnal_id', $jurnal_id)->delete();

        // Insert ulang absensi
        if (!empty($attendances) && is_array($attendances)) {
            $batchData = [];
            foreach ($attendances as $student_id => $status) {
                if ($status != 'hadir') {
                    $batchData[] = [
                        'jurnal_id' => $jurnal_id,
                        'user_id' => $student_id,
                        'user_type' => 'siswa',
                        'status' => $status,
                        'notes' => $this->request->getPost('notes')[$student_id] ?? null
                    ];
                }
            }
            if (count($batchData) > 0) {
                $this->attendanceModel->insertBatch($batchData);
            }
        }

        return redirect()->to('/admin/ekskul-pembina/jurnal/' . $jurnal['ekskul_id'])->with('success', 'Jurnal dan absensi berhasil diperbarui.');
    }

    /**
     * Penilaian Akhir (e-Rapor)
     */
    public function nilai($ekskul_id, $semester = '1')
    {
        $semester = in_array((string)$semester, ['1', '2']) ? (string)$semester : '1';
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id']);
        
        // Ambil nilai yang sudah ada untuk semester yang dipilih (dukung '1' / 'ganjil', '2' / 'genap')
        $semesters = ($semester === '2') ? ['2', 'genap'] : ['1', 'ganjil'];
        $scoresData = $this->scoreModel->where('ekskul_id', $ekskul_id)
            ->where('academic_year_id', $activeYear['id'])
            ->whereIn('semester', $semesters)
            ->findAll();
            
        $scores = [];
        foreach ($scoresData as $s) {
            $scores[$s['student_id']] = $s;
        }

        $data = [
            'title'      => 'Input Nilai Ekskul: ' . $ekskul['name'],
            'ekskul'     => $ekskul,
            'members'    => $members,
            'scores'     => $scores,
            'semester'   => $semester,
            'activeYear' => $activeYear
        ];

        return view('admin/ekskul_pembina/nilai', $data);
    }

    public function storeNilai($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $semester     = $this->request->getPost('semester');
        $semester     = in_array((string)$semester, ['1', '2']) ? (string)$semester : '1';
        $predicates   = $this->request->getPost('predicate');
        $descriptions = $this->request->getPost('description');

        $semesters = ($semester === '2') ? ['2', 'genap'] : ['1', 'ganjil'];

        if (!empty($predicates) && is_array($predicates)) {
            foreach ($predicates as $student_id => $predicate) {
                // Find existing score
                $existing = $this->scoreModel->where('ekskul_id', $ekskul_id)
                    ->where('student_id', $student_id)
                    ->where('academic_year_id', $activeYear['id'])
                    ->whereIn('semester', $semesters)
                    ->first();

                $desc = $descriptions[$student_id] ?? '';

                if ($existing) {
                    $this->scoreModel->update($existing['id'], [
                        'semester'    => $semester,
                        'predicate'   => $predicate,
                        'description' => $desc
                    ]);
                } else {
                    if (!empty($predicate)) { // only insert if they selected a predicate
                        $this->scoreModel->insert([
                            'ekskul_id'        => $ekskul_id,
                            'student_id'       => $student_id,
                            'academic_year_id' => $activeYear['id'],
                            'semester'         => $semester,
                            'predicate'        => $predicate,
                            'description'      => $desc
                        ]);
                    }
                }
            }
        }

        return redirect()->to('/admin/ekskul-pembina/nilai/' . $ekskul_id . '/' . $semester)->with('success', 'Nilai akhir semester berhasil disimpan.');
    }

    /**
     * Cetak Nilai Akhir Ekskul
     */
    public function printNilai($ekskul_id, $semester = '1')
    {
        $semester = in_array((string)$semester, ['1', '2']) ? (string)$semester : '1';
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul  = $this->ekskulModel->find($ekskul_id);
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id']);

        $semesters  = ($semester === '2') ? ['2', 'genap'] : ['1', 'ganjil'];
        $scoresData = $this->scoreModel->where('ekskul_id', $ekskul_id)
            ->where('academic_year_id', $activeYear['id'])
            ->whereIn('semester', $semesters)
            ->findAll();

        $scores = [];
        foreach ($scoresData as $s) {
            $scores[$s['student_id']] = $s;
        }

        // Ambil profil sekolah
        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->getProfile();

        // Ambil pembina yang bertugas di ekskul ini
        $pembinaList = $this->pembinaModel
            ->select('ekskul_pembina.*, users.fullname as pembina_name, users.username')
            ->join('users', 'users.id = ekskul_pembina.user_id')
            ->where('ekskul_id', $ekskul_id)
            ->where('academic_year_id', $activeYear['id'])
            ->findAll();

        $data = [
            'title'      => 'Cetak Nilai Ekskul: ' . $ekskul['name'],
            'ekskul'     => $ekskul,
            'members'    => $members,
            'scores'     => $scores,
            'semester'   => $semester,
            'activeYear' => $activeYear,
            'school'     => $school,
            'pembinaList' => $pembinaList,
        ];

        return view('admin/ekskul_pembina/print_nilai', $data);
    }

    /**
     * Helper to verify if logged in user is actually a pembina for this ekskul
     */
    private function checkAccess($ekskul_id, $academic_year_id)
    {
        $userId = session()->get('user')['id'] ?? 0;
        $isPembina = $this->pembinaModel->where('ekskul_id', $ekskul_id)
            ->where('user_id', $userId)
            ->where('academic_year_id', $academic_year_id)
            ->first();

        // Allow admin (role 1) bypass
        if (!$isPembina && session()->get('role_id') != 1) {
            echo view('errors/html/error_403', ['message' => 'Anda tidak memiliki akses sebagai pembina di ekstrakurikuler ini.']);
            exit;
        }
    }
}
