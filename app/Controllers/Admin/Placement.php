<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\StudentRecordModel;

class Placement extends BaseController
{
    protected $studentModel;
    protected $classModel;
    protected $yearModel;
    protected $recordModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->yearModel    = new AcademicYearModel();
        $this->recordModel  = new StudentRecordModel();
    }

    public function index()
    {
        // Tahun ajaran aktif
        $year = $this->yearModel->where('is_active', 1)->first();
        if (!$year) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif belum diset.');
        }

        // Ambil siswa yang BELUM PERNAH punya record di tahun ajaran manapun
        // (siswa baru yang belum pernah ditempatkan sama sekali)
        $db = db_connect();
        $students = $db->table('students')
            ->select('students.*')
            ->whereNotIn('students.id',
                $db->table('student_records')->select('student_id')->distinct()
            )
            ->orderBy('students.name', 'ASC')
            ->get()
            ->getResultArray();

        return view('admin/placement/index', [
            'title'      => 'Penempatan Siswa',
            'students'   => $students,
            'classes'    => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll(),
            'activeYear' => $year
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $year = $this->yearModel->where('is_active', 1)->first();

        if (!$year) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif belum diset.');
        }

        if (!empty($post['student_id']) && $post['class_id']) {
            foreach ($post['student_id'] as $studentId) {
                $this->recordModel->insert([
                    'student_id'       => $studentId,
                    'class_id'         => $post['class_id'],
                    'academic_year_id' => $year['id'],
                    'status'           => 'aktif'
                ]);
            }
        }

        return redirect()->to('admin/placement')->with('success', 'Penempatan siswa berhasil.');
    }
}
