<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\{
    StudentModel,
    StudentNoteModel,
    StudentNoteBehaviorModel,
    BehaviorModel,
    TeachingAssignmentModel,
    ClassModel
};
use Config\Database;

class StudentNotes extends BaseController
{
    protected $studentModel;
    protected $noteModel;
    protected $noteBehaviorModel;
    protected $behaviorModel;
    protected $assignmentModel;
    protected $classModel;
    protected $db;

    public function __construct()
    {
        $this->studentModel      = new StudentModel();
        $this->noteModel         = new StudentNoteModel();
        $this->noteBehaviorModel = new StudentNoteBehaviorModel();
        $this->behaviorModel     = new BehaviorModel();
        $this->assignmentModel   = new TeachingAssignmentModel();
        $this->classModel        = new ClassModel();
        $this->db = Database::connect();
    }

    /**
     * Daftar catatan siswa berdasarkan kelas
     */
    public function index()
    {
        $user      = session()->get('user');
        $roleId    = $user['role_id'] ?? null;
        $teacherId = $user['teacher_id'] ?? null;
        $studentId = $user['student_id'] ?? null;

        $classId = $this->request->getGet('class_id');
        $academicYearId = $this->request->getGet('academic_year_id');
        $statusFilter = $this->request->getGet('status');
        
        $yearModel = new \App\Models\AcademicYearModel();

        if ($academicYearId === null) {
            $activeYear = $yearModel->where('is_active', 1)->first();
            $academicYearId = $activeYear ? $activeYear['id'] : '';
        }

        if ($statusFilter === null) {
            $statusFilter = 'aktif';
        }

        $classes = [];

        // 🔹 Admin & Kepala Sekolah -> semua kelas
        if (in_array($roleId, [1, 2])) {
            $classes = $this->classModel->findAll();
        }
        // 🔹 Guru (wali kelas atau guru mapel)
        elseif ($roleId == 3) {
            // cek apakah guru ini wali kelas
            $class = $this->classModel->where('teacher_id', $teacherId)->first();
            if ($class) {
                // wali kelas -> langsung ke kelasnya
                $classId = $class['id'];
            } else {
                // guru mapel -> pilih kelas yang dia ampu
                $classes = $this->assignmentModel
                    ->select('classes.id, classes.name')
                    ->join('classes', 'classes.id = teaching_assignments.class_id')
                    ->where('teaching_assignments.teacher_id', $teacherId)
                    ->groupBy('classes.id')
                    ->findAll();
            }
        }
        // 🔹 Orang Tua -> langsung ke kelas anaknya (related_id = student_id)
        elseif ($roleId == 4 && $studentId) {
            $record = $this->db->table('student_records sr')
                ->select('sr.class_id')
                ->where('sr.student_id', $studentId)
                ->orderBy('sr.id', 'DESC')
                ->get()
                ->getRowArray();
            if ($record) {
                $classId = $record['class_id'];
            }
        }
        // 🔹 Siswa -> langsung ke kelasnya sendiri
        elseif ($roleId == 5 && $studentId) {
            $record = $this->db->table('student_records sr')
                ->select('sr.class_id')
                ->where('sr.student_id', $studentId)
                ->orderBy('sr.id', 'DESC')
                ->get()
                ->getRowArray();
            if ($record) {
                $classId = $record['class_id'];
            }
        }

        $students = [];
        $studentPoints = [];

        if ($classId) {
            $builder = $this->studentModel
                ->select('students.*, sr.class_id')
                ->join('student_records sr', 'sr.student_id = students.id')
                ->where('sr.class_id', $classId)
                ->orderBy('students.name', 'ASC');

            if ($academicYearId && $academicYearId !== 'all') {
                $builder->where('sr.academic_year_id', $academicYearId);
            }
            if ($statusFilter && $statusFilter !== 'all') {
                $builder->where('sr.status', $statusFilter);
            }
            
            $students = $builder->findAll();

            foreach ($students as $s) {
                $notes = $this->noteModel
                    ->where('student_id', $s['id'])
                    ->findAll();

                $points = 0;
                foreach ($notes as $n) {
                    $behaviors = $this->noteBehaviorModel
                        ->select('behaviors.points')
                        ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id')
                        ->where('note_id', $n['id'])
                        ->findAll();

                    foreach ($behaviors as $b) {
                        $points += $b['points'];
                    }
                }
                $studentPoints[$s['id']] = $points;
            }
        }

        $academicYears = $yearModel->orderBy('start_date', 'DESC')->findAll();

        return view('admin/student-notes/index', [
            'classes'       => $classes,
            'classId'       => $classId,
            'students'      => $students,
            'studentPoints' => $studentPoints,
            'academicYears' => $academicYears,
            'selectedYear'  => $academicYearId,
            'selectedStatus'=> $statusFilter,
        ]);
    }


    /**
     * Detail catatan per siswa
     */
    public function show($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $notes = $this->noteModel
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        foreach ($notes as &$note) {
            $note['behaviors'] = $this->noteBehaviorModel
                ->select('behaviors.*')
                ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id')
                ->where('note_id', $note['id'])
                ->findAll();
        }

        return view('admin/student-notes/show', [
            'student' => $student,
            'notes'   => $notes,
        ]);
    }

    /**
     * Form tambah catatan
     */
    public function create($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan');
        }

        // Ambil semua perilaku (positif & negatif)
        $behaviors = $this->behaviorModel->findAll();

        return view('admin/student-notes/create', [
            'student'   => $student,
            'behaviors' => $behaviors,
        ]);
    }


    /**
     * Simpan catatan baru
     */
    public function store()
    {
        $studentId = $this->request->getPost('student_id');
        $teacherId = session()->get('teacher_id') ?? null;
        $noteText  = $this->request->getPost('note');
        $behaviors = $this->request->getPost('behaviors') ?? [];

        // simpan catatan utama
        $noteId = $this->noteModel->insert([
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'note'       => $noteText
        ]);

        // simpan perilaku terkait (jika ada)
        foreach ($behaviors as $behaviorId) {
            $this->db->table('student_note_behaviors')->insert([
                'note_id'     => $noteId,
                'behavior_id' => $behaviorId
            ]);
        }

        return redirect()->to(base_url('admin/student-notes/show/'.$studentId))
                         ->with('success', 'Catatan berhasil ditambahkan.');
    }


    /**
     * Form edit catatan
     */
    public function edit($id)
    {
        $note = $this->noteModel->find($id);
        if (!$note) {
            return redirect()->back()->with('error', 'Catatan tidak ditemukan');
        }

        $student = $this->studentModel->find($note['student_id']);
        $behaviors = $this->behaviorModel->findAll();

        // Ambil behavior yang sudah dipilih
        $selectedBehaviors = $this->noteBehaviorModel
            ->where('note_id', $id)
            ->findColumn('behavior_id') ?? [];

        return view('admin/student-notes/edit', [
            'note'             => $note,
            'student'          => $student,
            'behaviors'        => $behaviors,
            'selectedBehaviors'=> $selectedBehaviors
        ]);
    }


    /**
     * Update catatan
     */
    public function update($id)
    {
        $note = $this->noteModel->find($id);
        if (!$note) {
            return redirect()->back()->with('error', 'Catatan tidak ditemukan.');
        }

        $noteText  = $this->request->getPost('note');
        $behaviors = $this->request->getPost('behaviors') ?? [];

        // update catatan
        $this->noteModel->update($id, [
            'note' => $noteText
        ]);

        // hapus perilaku lama
        $this->db->table('student_note_behaviors')->where('note_id', $id)->delete();

        // simpan perilaku baru
        foreach ($behaviors as $behaviorId) {
            $this->db->table('student_note_behaviors')->insert([
                'note_id'     => $id,
                'behavior_id' => $behaviorId
            ]);
        }

        return redirect()->to(base_url('admin/student-notes/show/'.$note['student_id']))
                         ->with('success', 'Catatan berhasil diperbarui.');
    }


    /**
     * Hapus catatan
     */
    public function delete($noteId)
    {
        $note = $this->noteModel->find($noteId);
        if ($note) {
            $this->noteModel->delete($noteId);
            return redirect()->to('admin/student-notes/show/'.$note['student_id'])->with('success', 'Catatan berhasil dihapus.');
        }
        return redirect()->back()->with('error', 'Catatan tidak ditemukan.');
    }
}
