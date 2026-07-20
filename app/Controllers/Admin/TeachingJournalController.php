<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeachingJournalModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\AlurTujuanPembelajaranModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherModel;

class TeachingJournalController extends BaseController
{
    protected $journalModel;
    protected $classModel;
    protected $subjectModel;
    protected $atpModel;
    protected $teachingAssignmentModel;
    protected $teacherModel;
    protected $db;

    public function __construct()
    {
        $this->journalModel = new TeachingJournalModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->atpModel = new AlurTujuanPembelajaranModel();
        $this->teachingAssignmentModel = new TeachingAssignmentModel();
        $this->teacherModel = new TeacherModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $data['title'] = 'Jurnal Mengajar';
        $filters = $this->getAvailableFilters();
        
        $classId = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');
        $teacherId = $this->request->getGet('teacher_id') ?: ($filters['role_id'] == 3 ? session()->get('user')['related_id'] : null);
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');

        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['teachers'] = in_array($filters['role_id'], [1, 2]) ? $this->teacherModel->findAll() : [];
        $data['readonly'] = $filters['readonly'];
        
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['selected_teacher'] = $teacherId;
        $data['date_from'] = $dateFrom;
        $data['date_to'] = $dateTo;

        $journalFilters = [];
        if ($classId) $journalFilters['class_id'] = $classId;
        if ($subjectId) $journalFilters['subject_id'] = $subjectId;
        if ($teacherId) $journalFilters['teacher_id'] = $teacherId;
        if ($dateFrom) $journalFilters['date_from'] = $dateFrom;
        if ($dateTo) $journalFilters['date_to'] = $dateTo;

        $data['journals'] = $this->journalModel->getJournals($journalFilters);

        return view('admin/teaching_journal/index', $data);
    }

    public function add()
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah data.');
        }

        $data['title'] = 'Tambah Jurnal Mengajar';
        $filters = $this->getAvailableFilters();
        
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['today'] = date('Y-m-d');
        
        $data['selected_class'] = $this->request->getGet('class_id');
        $data['selected_subject'] = $this->request->getGet('subject_id');

        return view('admin/teaching_journal/form', $data);
    }

    public function edit($id)
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk mengubah data.');
        }

        $journal = $this->journalModel->find($id);
        if (!$journal) {
            return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Data tidak ditemukan.');
        }

        // Ownership check for teachers
        if ($user['role_id'] == 3 && $journal['teacher_id'] != $user['related_id']) {
            return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $data['title'] = 'Edit Jurnal Mengajar';
        $data['journal'] = $journal;
        $filters = $this->getAvailableFilters();
        
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        
        return view('admin/teaching_journal/form', $data);
    }

    public function store()
    {
        $user = session()->get('user');
        $id = $this->request->getPost('id');

        $data = [
            'date'       => $this->request->getPost('date'),
            'class_id'   => $this->request->getPost('class_id'),
            'subject_id' => $this->request->getPost('subject_id'),
            'atp_id'     => $this->request->getPost('atp_id'),
            'notes'      => $this->request->getPost('notes'),
        ];

        if (!$id) {
            $data['teacher_id'] = $user['role_id'] == 3 ? $user['related_id'] : $this->request->getPost('teacher_id');
            if (empty($data['teacher_id'])) {
                // If admin doesn't provide teacher_id, try to resolve from assignment
                $assignment = $this->teachingAssignmentModel
                    ->where('class_id', $data['class_id'])
                    ->where('subject_id', $data['subject_id'])
                    ->first();
                $data['teacher_id'] = $assignment ? $assignment['teacher_id'] : null;
            }
            
            if (empty($data['teacher_id'])) {
                return redirect()->back()->withInput()->with('error', 'ID Guru tidak dapat ditentukan.');
            }

            $this->journalModel->insert($data);
            $msg = 'Jurnal berhasil disimpan.';
        } else {
            $this->journalModel->update($id, $data);
            $msg = 'Jurnal berhasil diperbarui.';
        }

        return redirect()->to(base_url('admin/teaching-journal?class_id=' . $data['class_id'] . '&subject_id=' . $data['subject_id']))->with('success', $msg);
    }

    public function delete($id)
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
        }

        $journal = $this->journalModel->find($id);
        if ($journal) {
            if ($user['role_id'] == 3 && $journal['teacher_id'] != $user['related_id']) {
                return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki akses ke data ini.');
            }
            $this->journalModel->delete($id);
            return redirect()->back()->with('success', 'Jurnal berhasil dihapus.');
        }
        return redirect()->back();
    }

    public function getAtps()
    {
        $classId   = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');

        if (!$classId || !$subjectId) {
            return $this->response->setJSON([]);
        }

        $classInfo = $this->classModel->find($classId);
        if (!$classInfo) return $this->response->setJSON([]);

        // Query ATP berdasarkan level kelas
        // Pakai subquery WHERE IN untuk filter level agar tidak ada baris yang hilang
        $classIdsAtLevel = $this->db->table('classes')
            ->select('id')
            ->where('level', $classInfo['level'])
            ->get()->getResultArray();
        $classIdsAtLevel = array_column($classIdsAtLevel, 'id');

        if (empty($classIdsAtLevel)) {
            return $this->response->setJSON([]);
        }

        $atps = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi, atp.semester, atp.urutan')
            ->whereIn('atp.class_id', $classIdsAtLevel)
            ->where('atp.subject_id', $subjectId)
            ->groupBy('atp.id')
            ->orderBy('atp.semester', 'ASC')
            ->orderBy('atp.urutan', 'ASC')
            ->get()->getResultArray();

        // Untuk setiap ATP, ambil daftar elemen CP dari atp_elemen
        foreach ($atps as &$atp) {
            $elemens = $this->db->table('atp_elemen ae')
                ->select('cp.elemen')
                ->join('cp_master cp', 'cp.id = ae.cp_master_id')
                ->where('ae.atp_id', $atp['id'])
                ->orderBy('ae.urutan', 'ASC')
                ->get()->getResultArray();

            // Gabungkan nama elemen jadi satu string
            $elemenNames = array_column($elemens, 'elemen');
            $atp['elemen'] = !empty($elemenNames)
                ? implode(', ', $elemenNames)
                : '-';
        }
        unset($atp);

        return $this->response->setJSON($atps);
    }

    private function getAvailableFilters()
    {
        $user = session()->get('user');
        if (!$user) return ['classes' => [], 'subjects' => [], 'readonly' => true, 'role_id' => null, 'auto_class' => false];

        $roleId = $user['role_id'];
        $teacherId = $user['related_id'];
        $readonly = ($roleId == 2);

        $classes = [];
        $subjects = [];

        $classId = $this->request->getGet('class_id');

        if (in_array($roleId, [1, 2])) {
            $classes = $this->classModel->findAll();
            if ($classId) {
                $subjects = $this->db->table('teaching_assignments ta')
                    ->select('s.id, s.name')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.class_id', $classId)
                    ->groupBy('s.id')
                    ->get()->getResultArray();
            } else {
                 $subjects = $this->subjectModel->findAll();
            }
        } elseif ($roleId == 3) {
            // Guru Kelas logic: find class where teacher_id = teacherId
            $homeroomClass = $this->classModel->where('teacher_id', $teacherId)->first();
            
            // Ambil tahun ajaran aktif
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $activeYear['id'] ?? 0;

            $assignmentQuery = $this->db->table('teaching_assignments ta')
                ->select('c.id as class_id, c.name as class_name, c.level, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYearId);
            
            $assignments = $assignmentQuery->get()->getResultArray();

            $tempClasses = [];
            foreach ($assignments as $a) {
                $tempClasses[$a['class_id']] = ['id' => $a['class_id'], 'name' => $a['class_name']];
            }
            if ($homeroomClass) {
                $tempClasses[$homeroomClass['id']] = ['id' => $homeroomClass['id'], 'name' => $homeroomClass['name']];
            }
            $classes = array_values($tempClasses);
            
            if (!$classId && $homeroomClass) {
                $classId = $homeroomClass['id'];
                $_GET['class_id'] = $classId;
            }

            if ($classId) {
                // For a specific class, get subjects this teacher teaches or all subjects if they are homeroom teacher?
                // The user said: "Guru kelas otomatis mendeteksi kelas yang diampu, pilih mapel yang diampu"
                // Usually homeroom teachers also teach some subjects or all subjects (SD).
                $filteredSubjects = array_filter($assignments, fn($a) => $a['class_id'] == $classId);
                $subjects = array_map(fn($s) => ['id' => $s['subject_id'], 'name' => $s['subject_name']], $filteredSubjects);
            }
        }

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'readonly' => $readonly,
            'role_id' => $roleId,
        ];
    }
}
