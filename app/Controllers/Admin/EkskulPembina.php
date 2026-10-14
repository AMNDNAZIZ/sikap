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
        if (!$ekskul) {
            return redirect()->back()->with('error', 'Ekstrakurikuler tidak ditemukan.');
        }

        // Jika ekskul wajib, otomatis sinkronkan seluruh siswa aktif
        if ($ekskul['category'] === 'wajib') {
            $this->memberModel->syncWajibMembers($activeYear['id'], $ekskul_id);
        }

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

    public function syncMembers($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        if (!$ekskul) {
            return redirect()->back()->with('error', 'Ekstrakurikuler tidak ditemukan.');
        }

        if ($ekskul['category'] !== 'wajib') {
            return redirect()->back()->with('error', 'Fitur sinkronisasi otomatis hanya berlaku untuk ekstrakurikuler wajib.');
        }

        $count = $this->memberModel->syncWajibMembers($activeYear['id'], $ekskul_id);
        $msg = $count > 0 
            ? "Sinkronisasi berhasil. {$count} siswa baru telah otomatis terdaftar sebagai anggota aktif." 
            : "Semua siswa aktif pada tahun ajaran ini sudah terdaftar sebagai anggota.";

        return redirect()->back()->with('success', $msg);
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
            if ($existing['status'] === 'pending') {
                $this->memberModel->update($existing['id'], ['status' => 'aktif']);
                return redirect()->back()->with('success', 'Pendaftaran siswa berstatus pending berhasil disetujui & diaktifkan.');
            }
            return redirect()->back()->with('error', 'Siswa tersebut sudah menjadi anggota aktif.');
        }

        $this->memberModel->insert([
            'ekskul_id' => $ekskul_id,
            'student_id' => $student_id,
            'academic_year_id' => $activeYear['id'],
            'status' => 'aktif'
        ]);

        return redirect()->back()->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function approveMember($member_id)
    {
        $member = $this->memberModel->find($member_id);
        if ($member) {
            $this->checkAccess($member['ekskul_id'], $member['academic_year_id']);
            $this->memberModel->update($member_id, ['status' => 'aktif']);
            return redirect()->back()->with('success', 'Pendaftaran siswa berhasil disetujui.');
        }
        return redirect()->back()->with('error', 'Data anggota tidak ditemukan.');
    }

    public function removeMember($member_id)
    {
        $member = $this->memberModel->find($member_id);
        if ($member) {
            $this->checkAccess($member['ekskul_id'], $member['academic_year_id']);

            $ekskul = $this->ekskulModel->find($member['ekskul_id']);
            if ($ekskul && $ekskul['category'] === 'wajib') {
                return redirect()->back()->with('error', 'Siswa tidak dapat dikeluarkan dari ekstrakurikuler wajib (' . esc($ekskul['name']) . ').');
            }

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
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id'], 'aktif');

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
        $members = $this->memberModel->getMembers($jurnal['ekskul_id'], $activeYear['id'], 'aktif');
        
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
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id'], 'aktif');
        
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
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id'], 'aktif');

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
     * Rekapitulasi Presensi Anggota Ekskul (Bulanan & Per Semester)
     */
    public function rekap($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        if (!$ekskul) {
            return redirect()->back()->with('error', 'Ekstrakurikuler tidak ditemukan.');
        }

        $request  = $this->request ?? service('request');
        $type     = $request->getGet('type') ?: 'monthly';
        $month    = (int) ($request->getGet('month') ?: date('n'));
        $year     = (int) ($request->getGet('year') ?: date('Y'));
        $semester = (string) ($request->getGet('semester') ?: '1');

        $rekapData = $this->getRekapData($ekskul_id, $type, $month, $year, $semester, $activeYear);

        $data = array_merge([
            'title'      => 'Rekap Presensi: ' . $ekskul['name'],
            'ekskul'     => $ekskul,
            'activeYear' => $activeYear
        ], $rekapData);

        return view('admin/ekskul_pembina/rekap', $data);
    }

    /**
     * Cetak Rekapitulasi Presensi Ekskul
     */
    public function printRekap($ekskul_id)
    {
        $activeYear = $this->getActiveYear();
        $this->checkAccess($ekskul_id, $activeYear['id']);

        $ekskul = $this->ekskulModel->find($ekskul_id);
        if (!$ekskul) {
            return redirect()->back()->with('error', 'Ekstrakurikuler tidak ditemukan.');
        }

        $request  = $this->request ?? service('request');
        $type     = $request->getGet('type') ?: 'monthly';
        $month    = (int) ($request->getGet('month') ?: date('n'));
        $year     = (int) ($request->getGet('year') ?: date('Y'));
        $semester = (string) ($request->getGet('semester') ?: '1');

        $rekapData = $this->getRekapData($ekskul_id, $type, $month, $year, $semester, $activeYear);

        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->getProfile();

        $pembinaList = $this->pembinaModel
            ->select('ekskul_pembina.*, users.fullname as pembina_name, users.username')
            ->join('users', 'users.id = ekskul_pembina.user_id')
            ->where('ekskul_id', $ekskul_id)
            ->where('academic_year_id', $activeYear['id'])
            ->findAll();

        $data = array_merge([
            'title'       => 'Cetak Rekap Presensi: ' . $ekskul['name'],
            'ekskul'      => $ekskul,
            'activeYear'  => $activeYear,
            'school'      => $school,
            'pembinaList' => $pembinaList
        ], $rekapData);

        return view('admin/ekskul_pembina/print_rekap', $data);
    }

    /**
     * Helper to compute attendance recap data for monthly or semester mode
     */
    private function getRekapData($ekskul_id, $type, $month, $year, $semester, $activeYear)
    {
        $startYear = (int) date('Y', strtotime($activeYear['start_date']));
        $endYear   = (int) date('Y', strtotime($activeYear['end_date']));

        if ($type === 'semester') {
            $semester = in_array((string)$semester, ['1', '2']) ? (string)$semester : '1';
            if ($semester === '1') {
                $startDate = $startYear . '-07-01';
                $endDate   = $startYear . '-12-31';
                $periodLabel = 'Semester 1 (Ganjil) TP ' . $activeYear['year'];
                $monthsInPeriod = [
                    7  => 'Juli ' . $startYear,
                    8  => 'Agustus ' . $startYear,
                    9  => 'September ' . $startYear,
                    10 => 'Oktober ' . $startYear,
                    11 => 'November ' . $startYear,
                    12 => 'Desember ' . $startYear
                ];
            } else {
                $startDate = $endYear . '-01-01';
                $endDate   = $endYear . '-06-30';
                $periodLabel = 'Semester 2 (Genap) TP ' . $activeYear['year'];
                $monthsInPeriod = [
                    1 => 'Januari ' . $endYear,
                    2 => 'Februari ' . $endYear,
                    3 => 'Maret ' . $endYear,
                    4 => 'April ' . $endYear,
                    5 => 'Mei ' . $endYear,
                    6 => 'Juni ' . $endYear
                ];
            }
        } else {
            $type = 'monthly';
            $month = (int) $month;
            if ($month < 1 || $month > 12) {
                $month = (int) date('n');
            }
            $year = (int) $year;
            if ($year < 2000 || $year > 2100) {
                $year = ($month >= 7) ? $startYear : $endYear;
            }
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate   = date('Y-m-t', strtotime($startDate));
            
            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            $periodLabel = 'Bulan ' . ($monthNames[$month] ?? '') . ' ' . $year;
            $monthsInPeriod = [$month => ($monthNames[$month] ?? '') . ' ' . $year];
        }

        // Ambil jurnal pertemuan dalam rentang tanggal
        $jurnals = $this->jurnalModel
            ->select('ekskul_jurnal.*, users.fullname as pembina_name')
            ->join('users', 'users.id = ekskul_jurnal.pembina_user_id', 'left')
            ->where('ekskul_jurnal.ekskul_id', $ekskul_id)
            ->where('ekskul_jurnal.academic_year_id', $activeYear['id'])
            ->where('ekskul_jurnal.date >=', $startDate)
            ->where('ekskul_jurnal.date <=', $endDate)
            ->orderBy('ekskul_jurnal.date', 'ASC')
            ->findAll();

        $totalMeetings = count($jurnals);
        $jurnalIds = !empty($jurnals) ? array_column($jurnals, 'id') : [];

        // Ambil data anggota aktif
        $members = $this->memberModel->getMembers($ekskul_id, $activeYear['id'], 'aktif');

        // Ambil data absensi non-hadir untuk jurnal-jurnal tersebut
        $absenceMap = [];
        if (!empty($jurnalIds)) {
            $absences = $this->attendanceModel
                ->whereIn('jurnal_id', $jurnalIds)
                ->where('user_type', 'siswa')
                ->findAll();

            foreach ($absences as $ab) {
                $absenceMap[$ab['jurnal_id']][$ab['user_id']] = [
                    'status' => $ab['status'],
                    'notes'  => $ab['notes'] ?? ''
                ];
            }
        }

        // Kalkulasi per siswa
        $rekapSiswa = [];
        $totalAllH = 0;
        $totalAllS = 0;
        $totalAllI = 0;
        $totalAllA = 0;

        foreach ($members as $m) {
            $sid = $m['student_id'];
            $hCount = 0;
            $sCount = 0;
            $iCount = 0;
            $aCount = 0;

            $meetingDetails = [];
            $monthlyDetails = [];
            foreach ($monthsInPeriod as $mNum => $mLabel) {
                $monthlyDetails[$mNum] = ['h' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'total' => 0];
            }

            foreach ($jurnals as $j) {
                $jid = $j['id'];
                $jDate = $j['date'];
                $jMonth = (int) date('n', strtotime($jDate));

                if (isset($absenceMap[$jid][$sid])) {
                    $st = strtolower($absenceMap[$jid][$sid]['status']);
                    $nt = $absenceMap[$jid][$sid]['notes'];
                } else {
                    $st = 'hadir';
                    $nt = '';
                }

                $meetingDetails[$jid] = [
                    'status' => $st,
                    'notes'  => $nt,
                    'date'   => $jDate
                ];

                if ($st === 'sakit') {
                    $sCount++;
                    if (isset($monthlyDetails[$jMonth])) $monthlyDetails[$jMonth]['s']++;
                } elseif ($st === 'izin') {
                    $iCount++;
                    if (isset($monthlyDetails[$jMonth])) $monthlyDetails[$jMonth]['i']++;
                } elseif ($st === 'alfa') {
                    $aCount++;
                    if (isset($monthlyDetails[$jMonth])) $monthlyDetails[$jMonth]['a']++;
                } else {
                    $hCount++;
                    if (isset($monthlyDetails[$jMonth])) $monthlyDetails[$jMonth]['h']++;
                }

                if (isset($monthlyDetails[$jMonth])) {
                    $monthlyDetails[$jMonth]['total']++;
                }
            }

            $percentage = ($totalMeetings > 0) ? round(($hCount / $totalMeetings) * 100, 1) : 0;

            if ($percentage >= 90) {
                $predicate = 'Sangat Baik';
                $predClass = 'bg-success text-white';
            } elseif ($percentage >= 75) {
                $predicate = 'Baik';
                $predClass = 'bg-info text-dark';
            } elseif ($percentage >= 60) {
                $predicate = 'Cukup';
                $predClass = 'bg-warning text-dark';
            } else {
                $predicate = 'Kurang';
                $predClass = 'bg-danger text-white';
            }

            $totalAllH += $hCount;
            $totalAllS += $sCount;
            $totalAllI += $iCount;
            $totalAllA += $aCount;

            $rekapSiswa[] = [
                'member'         => $m,
                'meetingDetails' => $meetingDetails,
                'monthlyDetails' => $monthlyDetails,
                'h'              => $hCount,
                's'              => $sCount,
                'i'              => $iCount,
                'a'              => $aCount,
                'total_meetings' => $totalMeetings,
                'percentage'     => $percentage,
                'predicate'      => $predicate,
                'predClass'      => $predClass
            ];
        }

        $totalMembers = count($members);
        $avgPercentage = ($totalMembers > 0 && $totalMeetings > 0)
            ? round(array_sum(array_column($rekapSiswa, 'percentage')) / $totalMembers, 1)
            : 0;

        $fullAttendanceCount = 0;
        foreach ($rekapSiswa as $row) {
            if ($row['percentage'] >= 100 && $totalMeetings > 0) {
                $fullAttendanceCount++;
            }
        }

        return [
            'type'                => $type,
            'month'               => $month,
            'year'                => $year,
            'semester'            => $semester,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'periodLabel'         => $periodLabel,
            'monthsInPeriod'      => $monthsInPeriod,
            'jurnals'             => $jurnals,
            'totalMeetings'       => $totalMeetings,
            'members'             => $members,
            'totalMembers'        => $totalMembers,
            'rekapSiswa'          => $rekapSiswa,
            'totalAllH'           => $totalAllH,
            'totalAllS'           => $totalAllS,
            'totalAllI'           => $totalAllI,
            'totalAllA'           => $totalAllA,
            'avgPercentage'       => $avgPercentage,
            'fullAttendanceCount' => $fullAttendanceCount,
            'startYear'           => $startYear,
            'endYear'             => $endYear
        ];
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
        $roleId = session()->get('role_id') ?? (session()->get('user')['role_id'] ?? 0);
        if (!$isPembina && $roleId != 1) {
            echo view('errors/html/error_403', ['message' => 'Anda tidak memiliki akses sebagai pembina di ekstrakurikuler ini.']);
            exit;
        }
    }
}
