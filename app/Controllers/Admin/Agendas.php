<?php

namespace App\Controllers\Admin;

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
        helper(['form', 'url']);
    }

    private function currentUserId()
    {
        return session()->get('user')['id'] ?? null;
    }

    private function currentUserRole()
    {
        return session()->get('user')['role_id'] ?? null;
    }

    public function index($year = null, $month = null)
    {
        $this->response->noCache();
        $now = Time::now();
        $year = $year ?? $now->getYear();
        $month = $month ?? $now->getMonth();

        $db = \Config\Database::connect();

        // 🔹 Ambil hari libur bulan ini
        $holidays = $db->table('holidays')
            ->where('date >=', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01")
            ->where('date <=', date('Y-m-t', strtotime("$year-$month-01")))
            ->get()
            ->getResultArray();

        // 🔹 Hitung jumlah agenda per tanggal bulan ini (Grupkan sibling agar 1 titik = 1 event)
        $builder = $db->table('agendas')
            ->select('date, COUNT(DISTINCT title, IFNULL(start_time, "00:00"), IFNULL(end_time, "00:00"), created_by) as total')
            ->where('date >=', "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01")
            ->where('date <=', date('Y-m-t', strtotime("$year-$month-01")));

        // Filter visibilitas untuk Guru (Hanya lihat milik sendiri atau publik)
        $roleId = $this->currentUserRole();
        $userId = $this->currentUserId();
        if ($roleId == 3) {
            $builder->groupStart()
                ->where('created_by', $userId)
                ->orWhere('is_public', 1)
                ->groupEnd();
        }

        $agendasCount = $builder->groupBy('date')
            ->get()
            ->getResultArray();

        // Ubah ke bentuk map [YYYY-MM-DD => total]
        $agendaMap = [];
        foreach ($agendasCount as $a) {
            $agendaMap[$a['date']] = $a['total'];
        }

        return view('admin/agendas/index', [
            'title' => 'Tugas dan Agenda',
            'year' => $year,
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT), // pastikan 2 digit
            'holidays' => $holidays,
            'agendaMap' => $agendaMap,
        ]);
    }

    private function getAvailableClasses()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('classes')->select('classes.id, classes.name');

        $roleId = $this->currentUserRole();
        $userId = $this->currentUserId();

        if ($roleId == 1 || $roleId == 2) {
            // Admin (lihat semua kelas)
            return $builder->get()->getResultArray();
        }

        if ($roleId == 3) {
            // Guru (cari teacher_id dari users.id)
            $teacher = $db->table('teachers')->where('user_id', $userId)->get()->getRow();

            if (!$teacher)
                return [];

            // Ambil tahun ajaran aktif
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $activeYear['id'] ?? 0;

            // Gabungkan kelas wali dan kelas mapel (tahun ajaran aktif)
            $waliClasses = $db->table('classes')
                ->select('id, name')
                ->where('teacher_id', $teacher->id)
                ->get()->getResultArray();

            $mapelClasses = $db->table('teaching_assignments ta')
                ->join('classes c', 'c.id = ta.class_id')
                ->select('c.id, c.name')
                ->where('ta.teacher_id', $teacher->id)
                ->where('ta.academic_year_id', $activeYearId)
                ->groupBy('c.id')
                ->get()->getResultArray();

            // Merge and unique by ID
            $merged = array_merge($waliClasses, $mapelClasses);
            $unique = [];
            foreach ($merged as $c) {
                $unique[$c['id']] = $c;
            }

            return array_values($unique);
        }

        return [];
    }

    private function canEditAgenda($agendaId)
    {
        $roleId = $this->currentUserRole();
        if ($roleId == 1 || $roleId == 2)
            return true; // Admin can edit all

        $userId = $this->currentUserId();
        $agenda = $this->agendaModel->find($agendaId);

        if (!$agenda)
            return false;

        // Guru can only edit their own
        return $agenda['created_by'] == $userId;
    }


    public function byDate($date)
    {
        $this->response->noCache();
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid date']);
        }

        $roleId = $this->currentUserRole();
        $userId = $this->currentUserId();

        // Query agenda dengan pengelompokan sibling dan agregasi nama kelas (GROUP_CONCAT)
        $builder = $this->db->table('agendas a')
            ->select('a.title, a.description, a.date, a.start_time, a.end_time, a.created_by, a.is_public, GROUP_CONCAT(c.name SEPARATOR ", ") as class_names, MAX(a.id) as id')
            ->join('classes c', 'c.id = a.class_id', 'left')
            ->where('a.date', $date);

        if ($roleId == 3) {
            $builder->groupStart()
                ->where('a.created_by', $userId)
                ->orWhere('a.is_public', 1)
                ->groupEnd();
        }

        $agendas = $builder->groupBy('a.title, a.start_time, a.end_time, a.created_by')
            ->orderBy('a.start_time', 'ASC')
            ->get()->getResultArray();

        return view('admin/agendas/_day_agendas', [
            'agendas' => $agendas,
            'date' => $date,
        ]);
    }

    public function create()
    {
        return view('admin/agendas/create', [
            'title' => 'Buat Agenda',
            'classes' => $this->getAvailableClasses(),
            'prefillDate' => $this->request->getGet('date')
        ]);
    }

    public function store()
    {
        $rules = [
            'title' => 'required|min_length[3]',
            'date' => 'required|valid_date[Y-m-d]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $classIds = $this->request->getPost('class_id');
        $formData = $this->request->getPost([
            'title',
            'description',
            'date',
            'start_time',
            'end_time',
            'is_public',
        ]);
        $formData['is_public'] = (int) $formData['is_public'];
        $formData['created_by'] = $this->currentUserId();

        if (empty($classIds)) {
            // Jika tidak ada kelas dipilih, anggap umum (class_id = null)
            $formData['class_id'] = null;
            $this->agendaModel->insert($formData);
        } else {
            // Jika pilih banyak kelas, buat entry per kelas
            foreach ($classIds as $cid) {
                // Pastikan class_id benar-benar null jika string kosong (select2 clear)
                $formData['class_id'] = !empty($cid) ? $cid : null;
                $this->agendaModel->insert($formData);
            }
        }

        return redirect()->to(base_url('admin/agendas'))->with('success', 'Agenda berhasil dibuat');
    }

    public function show($id)
    {
        $current = $this->agendaModel->find($id);
        if (!$current) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Agenda tidak ditemukan');
        }

        // Cari semua nama kelas yang terkait dengan agenda ini (siblings)
        $siblings = $this->db->table('agendas a')
            ->select('c.name as class_name')
            ->join('classes c', 'c.id = a.class_id', 'left')
            ->where([
                'a.title' => $current['title'],
                'a.date' => $current['date'],
                'a.start_time' => $current['start_time'],
                'a.end_time' => $current['end_time'],
                'a.created_by' => $current['created_by']
            ])
            ->get()->getResultArray();

        $classNames = array_filter(array_column($siblings, 'class_name'));
        $classNamesStr = !empty($classNames) ? implode(', ', $classNames) : 'Umum / Semua Kelas';

        return view('admin/agendas/show', [
            'title' => 'Detail Agenda',
            'agenda' => $current,
            'classNames' => $classNamesStr,
            'isOwnerOrAdmin' => $this->canEditAgenda($id),
        ]);
    }

    public function edit($id)
    {
        $agenda = $this->agendaModel->find($id);
        if (!$agenda) {
            return redirect()->back()->with('error', 'Agenda tidak ditemukan');
        }

        if (!$this->canEditAgenda($id)) {
            return redirect()->to(base_url('admin/agendas'))->with('error', 'Akses ditolak. Anda hanya dapat mengedit agenda yang Anda buat.');
        }

        // Cari semua agenda yang sama (sibling) untuk mendapatkan daftar class_id
        // NOTE: Kita exclude 'description' dari criteria karena Summernote bisa generate
        // HTML yang sedikit berbeda (whitespace/tag) sehingga matching gagal.
        $siblings = $this->agendaModel->where([
            'title' => $agenda['title'],
            'date' => $agenda['date'],
            'start_time' => $agenda['start_time'],
            'end_time' => $agenda['end_time'],
            'created_by' => $agenda['created_by']
        ])->findAll();

        $selectedClassIds = array_column($siblings, 'class_id');

        return view('admin/agendas/edit', [
            'title' => 'Edit Agenda',
            'agenda' => $agenda,
            'selectedClassIds' => $selectedClassIds,
            'classes' => $this->getAvailableClasses()
        ]);
    }

    public function update($id)
    {
        $agenda = $this->agendaModel->find($id);
        if (!$agenda) {
            return redirect()->back()->with('error', 'Agenda tidak ditemukan');
        }

        if (!$this->canEditAgenda($id)) {
            return redirect()->to(base_url('admin/agendas'))->with('error', 'Akses ditolak.');
        }

        // Simpan data lama untuk mencari siblings (exclude description)
        $oldData = [
            'title' => $agenda['title'],
            'date' => $agenda['date'],
            'start_time' => $agenda['start_time'],
            'end_time' => $agenda['end_time'],
            'created_by' => $agenda['created_by']
        ];

        $formData = $this->request->getPost([
            'title',
            'description',
            'date',
            'start_time',
            'end_time',
            'is_public',
        ]);
        $formData['is_public'] = (int) $formData['is_public'];
        $formData['created_by'] = $agenda['created_by'];

        $classIds = $this->request->getPost('class_id'); // Ini sekarang array (multiple select)

        // Cari siblings yang ada saat ini
        $siblings = $this->agendaModel->where($oldData)->findAll();
        $siblingsMap = [];
        foreach ($siblings as $s) {
            $siblingsMap[$s['class_id'] ?: 'null'] = $s['id'];
        }

        if (empty($classIds)) {
            // Jika tidak ada kelas dipilih (Umum)
            $classIds = [null];
        }

        $processedIds = [];

        foreach ($classIds as $cid) {
            $cid = empty($cid) ? null : $cid;
            $classKey = $cid ?: 'null';
            $formData['class_id'] = $cid;

            if (isset($siblingsMap[$classKey])) {
                // Update existing row
                $this->agendaModel->update($siblingsMap[$classKey], $formData);
                $processedIds[] = $siblingsMap[$classKey];
            } else {
                // Insert new row
                $this->agendaModel->insert($formData);
                // Kita tidak perlu track ID baru untuk penghapusan
            }
        }

        // Hapus siblings yang tidak ada lagi di daftar classIds
        foreach ($siblings as $s) {
            if (!in_array($s['id'], $processedIds)) {
                $this->agendaModel->delete($s['id']);
            }
        }

        return redirect()->to(base_url('admin/agendas'))->with('success', 'Agenda diperbarui');
    }

    public function delete($id)
    {
        $agenda = $this->agendaModel->find($id);
        if (!$agenda) {
            return redirect()->back()->with('error', 'Agenda tidak ditemukan');
        }

        if (!$this->canEditAgenda($id)) {
            return redirect()->to(base_url('admin/agendas'))->with('error', 'Akses ditolak.');
        }

        // Hapus semua sibling yang identik (exclude description untuk robustness)
        $this->agendaModel->where([
            'title' => $agenda['title'],
            'date' => $agenda['date'],
            'start_time' => $agenda['start_time'],
            'end_time' => $agenda['end_time'],
            'created_by' => $agenda['created_by']
        ])->delete();

        return redirect()->to(base_url('admin/agendas'))->with('success', 'Agenda dihapus');
    }
}
