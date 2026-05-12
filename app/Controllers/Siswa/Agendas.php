<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\AgendaModel;
use CodeIgniter\I18n\Time;

class Agendas extends BaseController
{
    protected $agendaModel;
    protected $db;

    public function __construct()
    {
        $this->agendaModel = new AgendaModel();
        $this->db = \Config\Database::connect();
    }

    private function currentStudent()
    {
        return session()->get('user');
    }

    public function index($year = null, $month = null)
    {
        $user = $this->currentStudent();
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        // ambil kelas aktif siswa
        $record = $this->db->table('student_records')
            ->where('student_id', $user['student_id'])
            ->where('status', 'aktif')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        if (!$record) {
            return redirect()->back()->with('error', 'Kelas siswa tidak ditemukan.');
        }
        $classId = $record['class_id'];

        $now = Time::now();
        $year = $year ?? $now->getYear();
        $month = $month ?? $now->getMonth();

        // 🔹 Ambil hari libur bulan ini
        $holidays = $this->db->table('holidays')
            ->where('date >=', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01")
            ->where('date <=', date('Y-m-t', strtotime("$year-$month-01")))
            ->get()
            ->getResultArray();

        // 🔹 Hitung jumlah agenda per tanggal bulan ini (untuk kelas siswa & publik)
        $agendasCount = $this->db->table('agendas')
            ->select('date, COUNT(*) as total')
            ->where('date >=', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01")
            ->where('date <=', date('Y-m-t', strtotime("$year-$month-01")))
            ->groupStart()
            ->where('class_id', $classId)
            ->orWhere('is_public', 1)
            ->groupEnd()
            ->groupBy('date')
            ->get()
            ->getResultArray();

        $agendaMap = [];
        foreach ($agendasCount as $a) {
            $agendaMap[$a['date']] = $a['total'];
        }

        return view('siswa/agendas/index', [
            'title' => 'Agenda Siswa',
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'holidays' => $holidays,
            'agendaMap' => $agendaMap,
        ]);
    }

    public function byDate($date)
    {
        $user = $this->currentStudent();
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses ditolak.']);
        }

        // ambil kelas siswa
        $record = $this->db->table('student_records')
            ->where('student_id', $user['student_id'])
            ->where('status', 'aktif')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        if (!$record) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Kelas tidak ditemukan']);
        }

        $classId = $record['class_id'];

        $agendas = $this->agendaModel
            ->where('date', $date)
            ->groupStart()
            ->where('class_id', $classId)
            ->orWhere('is_public', 1)
            ->groupEnd()
            ->orderBy('start_time', 'ASC')
            ->findAll();

        return view('siswa/agendas/_day_agendas', [
            'agendas' => $agendas,
            'date' => $date,
        ]);
    }

    public function show($id)
    {
        $user = $this->currentStudent();
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // ambil kelas siswa
        $record = $this->db->table('student_records')
            ->where('student_id', $user['student_id'])
            ->where('status', 'aktif')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();

        if (!$record) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kelas tidak ditemukan');
        }

        $classId = $record['class_id'];

        $agenda = $this->db->table('agendas a')
            ->select('a.*, c.name as class_name')
            ->join('classes c', 'c.id = a.class_id', 'left')
            ->where('a.id', $id)
            ->groupStart()
            ->where('a.class_id', $classId)
            ->orWhere('a.is_public', 1)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if (!$agenda) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Agenda tidak ditemukan atau Anda tidak memiliki akses.');
        }

        return view('siswa/agendas/show', [
            'title' => 'Detail Agenda',
            'agenda' => $agenda,
        ]);
    }

}
