<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkJournalModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use Config\Database;

class Counseling extends BaseController
{
    protected $journalModel;
    protected $studentModel;
    protected $classModel;
    protected $db;

    public function __construct()
    {
        $this->journalModel = new BkJournalModel();
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->db = Database::connect();
    }

    public function index()
    {
        // 🔹 Get current user info to check access
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        
        // 🔹 Query Builder for Journals with Student and Class info
        $builder = $this->db->table('bk_journals j')
            ->select('j.*, s.name as student_name, s.nisn, c.name as class_name, u.fullname as counselor_name')
            ->join('students s', 's.id = j.student_id')
            ->join('users u', 'u.id = j.counselor_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->groupBy('j.id') // In case student_records has multiple active/inactive records
            ->orderBy('j.session_date', 'DESC');

        // Note: In real app, you might want to filter active academic year for student_records
        
        $journals = $builder->get()->getResultArray();

        return view('admin/counseling/index', [
            'title' => 'Jurnal Bimbingan Konseling',
            'journals' => $journals
        ]);
    }

    public function create()
    {
        // For dropdown of students
        $students = $this->db->table('students s')
            ->select('s.id, s.name, s.nisn, c.name as class_name')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        return view('admin/counseling/create', [
            'title' => 'Tambah Jurnal Konseling',
            'students' => $students
        ]);
    }

    public function store()
    {
        $user = session()->get('user');

        $data = [
            'student_id'          => $this->request->getPost('student_id'),
            'counselor_id'        => $user['id'] ?? null,
            'counseling_type'     => $this->request->getPost('counseling_type'),
            'session_date'        => $this->request->getPost('session_date'),
            'problem_description' => $this->request->getPost('problem_description'),
            'diagnosis'           => $this->request->getPost('diagnosis'),
            'treatment'           => $this->request->getPost('treatment'),
            'follow_up'           => $this->request->getPost('follow_up'),
            'is_confidential'     => $this->request->getPost('is_confidential') ?? 1,
            'status'              => $this->request->getPost('status') ?? 'Open',
        ];

        $this->journalModel->insert($data);

        return redirect()->to(base_url('admin/counseling'))->with('success', 'Jurnal Konseling berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $journal = $this->journalModel->find($id);
        if (!$journal) {
            return redirect()->to(base_url('admin/counseling'))->with('error', 'Data tidak ditemukan.');
        }

        $students = $this->db->table('students s')
            ->select('s.id, s.name, s.nisn, c.name as class_name')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        return view('admin/counseling/edit', [
            'title' => 'Ubah Jurnal Konseling',
            'journal' => $journal,
            'students' => $students
        ]);
    }

    public function update($id)
    {
        $data = [
            'student_id'          => $this->request->getPost('student_id'),
            'counseling_type'     => $this->request->getPost('counseling_type'),
            'session_date'        => $this->request->getPost('session_date'),
            'problem_description' => $this->request->getPost('problem_description'),
            'diagnosis'           => $this->request->getPost('diagnosis'),
            'treatment'           => $this->request->getPost('treatment'),
            'follow_up'           => $this->request->getPost('follow_up'),
            'is_confidential'     => $this->request->getPost('is_confidential') ?? 1,
            'status'              => $this->request->getPost('status') ?? 'Open',
        ];

        $this->journalModel->update($id, $data);

        return redirect()->to(base_url('admin/counseling'))->with('success', 'Jurnal Konseling berhasil diperbarui.');
    }

    public function show($id)
    {
        $builder = $this->db->table('bk_journals j')
            ->select('j.*, s.name as student_name, s.nisn, c.name as class_name, u.fullname as counselor_name')
            ->join('students s', 's.id = j.student_id')
            ->join('users u', 'u.id = j.counselor_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->where('j.id', $id)
            ->groupBy('j.id');
            
        $journal = $builder->get()->getRowArray();

        if (!$journal) {
            return redirect()->to(base_url('admin/counseling'))->with('error', 'Data tidak ditemukan.');
        }

        return view('admin/counseling/show', [
            'title' => 'Detail Jurnal Konseling',
            'journal' => $journal
        ]);
    }

    public function delete($id)
    {
        $this->journalModel->delete($id);
        return redirect()->to(base_url('admin/counseling'))->with('success', 'Jurnal Konseling berhasil dihapus.');
    }
}
