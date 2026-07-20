<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentRecordModel;
use App\Models\AcademicYearModel;
use App\Models\ClassModel;

class StudentRecords extends BaseController
{
    protected $studentModel;
    protected $recordModel;
    protected $yearModel;
    protected $classModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->recordModel  = new StudentRecordModel();
        $this->yearModel    = new AcademicYearModel();
        $this->classModel   = new ClassModel();
    }

    /**
     * Jika $studentId = null -> tampilkan LIST per tahun (dengan filter & pagination)
     * Jika $studentId != null -> tampilkan DETAIL riwayat siswa tersebut
     */
    public function index($studentId = null)
    {
        // ---------------------
        // MODE LIST (no param)
        // ---------------------
        if ($studentId === null) {
            $yearId  = $this->request->getGet('year_id');
            $classId = $this->request->getGet('class_id');
            $search  = $this->request->getGet('search');

            // 'all' = tampilkan semua tahun ajaran (untuk pencarian lintas tahun)
            // kosong (pertama kali buka) = default ke tahun aktif
            $allYears = ($yearId === 'all');

            if ($allYears) {
                $year = null;
            } elseif ($yearId) {
                $year = $this->yearModel->find($yearId);
            } else {
                // Default: tahun aktif
                $year = $this->yearModel->where('is_active', 1)->first();
            }

            // Build query lewat model (paginate tetap bekerja)
            $builder = $this->recordModel
                ->select('student_records.id as record_id,
                          students.id as student_id, students.nis, students.name,
                          classes.name as class_name,
                          academic_years.id as year_id, academic_years.year,
                          student_records.status')
                ->join('students', 'students.id = student_records.student_id', 'left')
                ->join('classes', 'classes.id = student_records.class_id', 'left')
                ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left');

            if ($year) {
                $builder->where('student_records.academic_year_id', $year['id']);
            }

            // Exclude siswa yang sudah lulus (mereka tampil di halaman Alumni)
            $builder->where('student_records.status !=', 'lulus');

            if (!empty($classId)) {
                $builder->where('student_records.class_id', $classId);
            }

            if (!empty($search)) {
                $builder->groupStart()
                        ->like('students.name', $search)
                        ->orLike('students.nis', $search)
                        ->groupEnd();
            }

            $records = $builder->orderBy('students.name', 'ASC')->paginate(10);

            return view('admin/student_records/index', [
                'title'         => 'Riwayat Siswa (Per Tahun)',
                'records'       => $records,
                'pager'         => $this->recordModel->pager,
                'years'         => $this->yearModel->orderBy('start_date', 'DESC')->findAll(),
                'classes'       => $this->classModel->orderBy('name', 'ASC')->findAll(),
                'selectedYear'  => $allYears ? 'all' : ($year['id'] ?? null),
                'selectedClass' => $classId,
                'search'        => $search,
            ]);
        }

        // -----------------------
        // MODE DETAIL (with id)
        // -----------------------
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return redirect()->to('admin/student-records')->with('error', 'Siswa tidak ditemukan.');
        }

        $records = $this->recordModel
            ->select('student_records.*, academic_years.year, classes.name as class_name')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.student_id', $studentId)
            ->orderBy('academic_years.start_date', 'ASC')
            ->findAll();

        return view('admin/student_records/show', [
            'title'   => 'Riwayat Siswa',
            'student' => $student,
            'records' => $records,
        ]);
    }
}
