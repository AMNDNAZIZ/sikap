<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EkskulModel;
use App\Models\EkskulPembinaModel;
use App\Models\EkskulJurnalModel;
use App\Models\EkskulAttendanceModel;
use App\Models\EkskulMemberModel;
use App\Models\AcademicYearModel;
use App\Models\SchoolModel;
use App\Models\UserModel;

class EkskulAbsensi extends BaseController
{
    protected $ekskulModel;
    protected $pembinaModel;
    protected $jurnalModel;
    protected $attendanceModel;
    protected $memberModel;
    protected $yearModel;
    protected $schoolModel;
    protected $userModel;

    public function __construct()
    {
        $this->ekskulModel     = new EkskulModel();
        $this->pembinaModel    = new EkskulPembinaModel();
        $this->jurnalModel     = new EkskulJurnalModel();
        $this->attendanceModel = new EkskulAttendanceModel();
        $this->memberModel     = new EkskulMemberModel();
        $this->yearModel       = new AcademicYearModel();
        $this->schoolModel     = new SchoolModel();
        $this->userModel       = new UserModel();
    }

    private function getActiveYear()
    {
        return $this->yearModel->getActiveYear();
    }

    private function checkAccess()
    {
        $user = session()->get('user');
        // Admin (1), Kepsek (2), Staf (7) always have access.
        if (in_array($user['role_id'] ?? 0, [1, 2, 7])) {
            return true;
        }
        
        // If teacher (3), check if they are koordinator ekskul
        if (($user['role_id'] ?? 0) == 3 && !empty($user['related_id'])) {
            $isKoord = \Config\Database::connect()->table('teachers')
                ->where('id', $user['related_id'])
                ->where('is_koordinator_ekskul', 1)
                ->countAllResults();
            if ($isKoord > 0) return true;
        }
        
        return false;
    }

    /**
     * Dashboard Rekap & Monitoring Absensi Pembina
     */
    public function index()
    {
        if (!$this->checkAccess()) return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');

        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->to('/admin/ekskul')->with('error', 'Tahun ajaran aktif belum diset.');
        }

        $month    = (int) ($this->request->getGet('month') ?: date('n'));
        $year     = (int) ($this->request->getGet('year') ?: date('Y'));
        $ekskulId = $this->request->getGet('ekskul_id') ?: '';
        $status   = $this->request->getGet('status') ?: 'all';

        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $startDate = $yearMonth . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));

        $allEkskuls = $this->ekskulModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();

        // 1. Ambil Penugasan Pembina di Tahun Aktif
        $pembinaQuery = $this->pembinaModel->select('ekskul_pembina.*, ekskul_master.name as ekskul_name, ekskul_master.category as ekskul_category, users.fullname as pembina_name, users.username, users.role_id')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_pembina.ekskul_id')
            ->join('users', 'users.id = ekskul_pembina.user_id')
            ->where('ekskul_pembina.academic_year_id', $activeYear['id']);

        if (!empty($ekskulId)) {
            $pembinaQuery->where('ekskul_pembina.ekskul_id', $ekskulId);
        }

        $pembinaList = $pembinaQuery->orderBy('ekskul_master.name', 'ASC')->findAll();

        // Hitung Rekap Bulanan per Pembina (Dasar Pembayaran Honor)
        $rekapPembina = [];
        $totalHonorMeetings = 0;

        foreach ($pembinaList as $p) {
            $jurnals = $this->jurnalModel->where('ekskul_id', $p['ekskul_id'])
                ->where('pembina_user_id', $p['user_id'])
                ->where('academic_year_id', $activeYear['id'])
                ->where('date >=', $startDate)
                ->where('date <=', $endDate)
                ->findAll();

            $totalMeetings = count($jurnals);
            $verified      = 0;
            $pending       = 0;
            $rejected      = 0;

            foreach ($jurnals as $j) {
                $vStatus = $j['verification_status'] ?? 'pending';
                if ($vStatus === 'verified') {
                    $verified++;
                } elseif ($vStatus === 'rejected') {
                    $rejected++;
                } else {
                    $pending++;
                }
            }

            $totalHonorMeetings += $verified;

            $rekapPembina[] = [
                'pembina_id'     => $p['id'],
                'user_id'        => $p['user_id'],
                'ekskul_id'      => $p['ekskul_id'],
                'ekskul_name'    => $p['ekskul_name'],
                'pembina_name'   => $p['pembina_name'],
                'is_external'    => $p['is_external'],
                'total_meetings' => $totalMeetings,
                'verified'       => $verified,
                'pending'        => $pending,
                'rejected'       => $rejected,
            ];
        }

        // 2. Ambil Log Harian Jurnal untuk Verifikasi
        $logBuilder = $this->jurnalModel->select('ekskul_jurnal.*, ekskul_master.name as ekskul_name, pembina.fullname as pembina_name, verifier.fullname as verifier_name')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_jurnal.ekskul_id')
            ->join('users pembina', 'pembina.id = ekskul_jurnal.pembina_user_id')
            ->join('users verifier', 'verifier.id = ekskul_jurnal.verified_by', 'left')
            ->where('ekskul_jurnal.academic_year_id', $activeYear['id'])
            ->where('ekskul_jurnal.date >=', $startDate)
            ->where('ekskul_jurnal.date <=', $endDate);

        if (!empty($ekskulId)) {
            $logBuilder->where('ekskul_jurnal.ekskul_id', $ekskulId);
        }
        if ($status !== 'all') {
            $logBuilder->where('ekskul_jurnal.verification_status', $status);
        }

        $logJurnal = $logBuilder->orderBy('ekskul_jurnal.date', 'DESC')->findAll();

        // Hitung siswa hadir untuk setiap jurnal
        foreach ($logJurnal as &$log) {
            $totalMembers = $this->memberModel->where('ekskul_id', $log['ekskul_id'])
                ->where('academic_year_id', $activeYear['id'])
                ->where('status', 'aktif')
                ->countAllResults();

            $absentCount = $this->attendanceModel->where('jurnal_id', $log['id'])
                ->where('user_type', 'siswa')
                ->countAllResults();

            $log['total_members']  = $totalMembers;
            $log['siswa_hadir']    = max(0, $totalMembers - $absentCount);
        }
        unset($log);

        // Statistik Cards
        $totalAllJurnal = count($logJurnal);
        $totalVerified  = count(array_filter($logJurnal, fn($l) => ($l['verification_status'] ?? 'pending') === 'verified'));
        $totalPending   = count(array_filter($logJurnal, fn($l) => ($l['verification_status'] ?? 'pending') === 'pending'));
        $totalRejected  = count(array_filter($logJurnal, fn($l) => ($l['verification_status'] ?? 'pending') === 'rejected'));

        $monthsIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'title'               => 'Rekap & Monitoring Absensi Pembina Ekskul',
            'activeYear'          => $activeYear,
            'month'               => $month,
            'year'                => $year,
            'monthName'           => $monthsIndo[$month] ?? '',
            'monthsIndo'          => $monthsIndo,
            'ekskulId'            => $ekskulId,
            'status'              => $status,
            'allEkskuls'          => $allEkskuls,
            'rekapPembina'        => $rekapPembina,
            'logJurnal'           => $logJurnal,
            'totalAllJurnal'      => $totalAllJurnal,
            'totalVerified'       => $totalVerified,
            'totalPending'        => $totalPending,
            'totalRejected'       => $totalRejected,
            'totalHonorMeetings'  => $totalHonorMeetings,
        ];

        return view('admin/ekskul_absensi/index', $data);
    }

    /**
     * Verifikasi single jurnal oleh Admin/Staf
     */
    public function verify($jurnalId)
    {
        if (!$this->checkAccess()) return redirect()->back()->with('error', 'Akses ditolak.');

        $jurnal = $this->jurnalModel->find($jurnalId);
        if (!$jurnal) {
            return redirect()->back()->with('error', 'Data jurnal tidak ditemukan.');
        }

        $action = $this->request->getPost('action'); // 'verified', 'rejected', 'pending'
        $notes  = $this->request->getPost('notes') ?: null;

        if (!in_array($action, ['verified', 'rejected', 'pending'])) {
            return redirect()->back()->with('error', 'Status verifikasi tidak valid.');
        }

        $userId = session()->get('user')['id'] ?? null;

        $this->jurnalModel->update($jurnalId, [
            'verification_status' => $action,
            'verified_by'         => ($action !== 'pending') ? $userId : null,
            'verified_at'         => ($action !== 'pending') ? date('Y-m-d H:i:s') : null,
            'verification_notes'  => $notes
        ]);

        $statusLabel = [
            'verified' => 'berhasil disetujui/diverifikasi',
            'rejected' => 'berhasil ditolak',
            'pending'  => 'dikembalikan ke status pending'
        ];

        return redirect()->back()->with('success', 'Kehadiran pembina ' . ($statusLabel[$action] ?? 'diperbarui') . '.');
    }

    /**
     * Verifikasi Massal (Batch)
     */
    public function batchVerify()
    {
        if (!$this->checkAccess()) return redirect()->back()->with('error', 'Akses ditolak.');

        $jurnalIds = $this->request->getPost('jurnal_ids');
        $action    = $this->request->getPost('action') ?: 'verified';
        $notes     = $this->request->getPost('notes') ?: null;

        if (empty($jurnalIds) || !is_array($jurnalIds)) {
            return redirect()->back()->with('error', 'Tidak ada data jurnal yang dipilih.');
        }

        if (!in_array($action, ['verified', 'rejected'])) {
            return redirect()->back()->with('error', 'Aksi tidak valid.');
        }

        $userId = session()->get('user')['id'] ?? null;
        $now    = date('Y-m-d H:i:s');

        foreach ($jurnalIds as $id) {
            $this->jurnalModel->update((int) $id, [
                'verification_status' => $action,
                'verified_by'         => $userId,
                'verified_at'         => $now,
                'verification_notes'  => $notes
            ]);
        }

        $count = count($jurnalIds);
        $actText = $action === 'verified' ? 'disetujui' : 'ditolak';

        return redirect()->back()->with('success', "Sebanyak {$count} jurnal kegiatan pembina berhasil {$actText}.");
    }

    /**
     * Detail Riwayat Presensi Pembina Tertentu
     */
    public function detail($userId, $ekskulId)
    {
        if (!$this->checkAccess()) return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');

        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->to('/admin/ekskul-absensi')->with('error', 'Tahun ajaran belum diset.');
        }

        $month = (int) ($this->request->getGet('month') ?: date('n'));
        $year  = (int) ($this->request->getGet('year') ?: date('Y'));

        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $startDate = $yearMonth . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));

        $pembina = $this->userModel->find($userId);
        $ekskul  = $this->ekskulModel->find($ekskulId);

        if (!$pembina || !$ekskul) {
            return redirect()->to('/admin/ekskul-absensi')->with('error', 'Data tidak ditemukan.');
        }

        $jurnals = $this->jurnalModel->select('ekskul_jurnal.*, verifier.fullname as verifier_name')
            ->join('users verifier', 'verifier.id = ekskul_jurnal.verified_by', 'left')
            ->where('ekskul_jurnal.ekskul_id', $ekskulId)
            ->where('ekskul_jurnal.pembina_user_id', $userId)
            ->where('ekskul_jurnal.academic_year_id', $activeYear['id'])
            ->where('ekskul_jurnal.date >=', $startDate)
            ->where('ekskul_jurnal.date <=', $endDate)
            ->orderBy('ekskul_jurnal.date', 'DESC')
            ->findAll();

        $data = [
            'title'      => 'Detail Kehadiran Pembina: ' . $pembina['fullname'],
            'pembina'    => $pembina,
            'ekskul'     => $ekskul,
            'activeYear' => $activeYear,
            'jurnals'    => $jurnals,
            'month'      => $month,
            'year'       => $year,
        ];

        return view('admin/ekskul_absensi/detail', $data);
    }

    /**
     * Cetak Rekapitulasi Bulanan (Untuk SPJ / Pembayaran Honor)
     */
    public function printReport()
    {
        if (!$this->checkAccess()) return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');

        $activeYear = $this->getActiveYear();
        if (!$activeYear) {
            return redirect()->to('/admin/ekskul-absensi')->with('error', 'Tahun ajaran belum diset.');
        }

        $month    = (int) ($this->request->getGet('month') ?: date('n'));
        $year     = (int) ($this->request->getGet('year') ?: date('Y'));
        $ekskulId = $this->request->getGet('ekskul_id') ?: '';

        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $startDate = $yearMonth . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));

        $school = $this->schoolModel->getProfile();

        $pembinaQuery = $this->pembinaModel->select('ekskul_pembina.*, ekskul_master.name as ekskul_name, users.fullname as pembina_name, teachers.nip, users.role_id')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_pembina.ekskul_id')
            ->join('users', 'users.id = ekskul_pembina.user_id')
            ->join('teachers', 'teachers.user_id = users.id', 'left')
            ->where('ekskul_pembina.academic_year_id', $activeYear['id']);

        if (!empty($ekskulId)) {
            $pembinaQuery->where('ekskul_pembina.ekskul_id', $ekskulId);
        }

        $pembinaList = $pembinaQuery->orderBy('ekskul_master.name', 'ASC')->findAll();

        $rekapPembina = [];
        foreach ($pembinaList as $p) {
            $jurnals = $this->jurnalModel->where('ekskul_id', $p['ekskul_id'])
                ->where('pembina_user_id', $p['user_id'])
                ->where('academic_year_id', $activeYear['id'])
                ->where('date >=', $startDate)
                ->where('date <=', $endDate)
                ->findAll();

            $verified = 0;
            $dates = [];
            foreach ($jurnals as $j) {
                if (($j['verification_status'] ?? 'pending') === 'verified') {
                    $verified++;
                    $dates[] = date('d/m', strtotime($j['date']));
                }
            }

            $rekapPembina[] = [
                'pembina_name'   => $p['pembina_name'],
                'nip'            => $p['nip'] ?: '-',
                'ekskul_name'    => $p['ekskul_name'],
                'is_external'    => $p['is_external'] ? 'Pelatih Eksternal' : 'Guru Internal',
                'verified_count' => $verified,
                'dates_string'   => !empty($dates) ? implode(', ', $dates) : '-',
            ];
        }

        $monthsIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'school'       => $school,
            'activeYear'   => $activeYear,
            'month'        => $month,
            'year'         => $year,
            'monthName'    => $monthsIndo[$month] ?? '',
            'rekapPembina' => $rekapPembina,
        ];

        return view('admin/ekskul_absensi/print', $data);
    }
}
