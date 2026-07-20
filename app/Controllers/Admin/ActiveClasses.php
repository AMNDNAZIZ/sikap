<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentRecordModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;

class ActiveClasses extends BaseController
{
    protected StudentRecordModel $recordModel;
    protected ClassModel $classModel;
    protected AcademicYearModel $yearModel;

    public function __construct()
    {
        $this->recordModel = new StudentRecordModel();
        $this->classModel  = new ClassModel();
        $this->yearModel   = new AcademicYearModel();
    }

    public function index()
    {
        $year = $this->yearModel->where('is_active', 1)->first();
        if (!$year) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif belum diset.');
        }

        $classId = $this->request->getGet('class_id');

        $builder = $this->recordModel
            ->select('student_records.id, student_records.status,
                      students.nis, students.name,
                      classes.id as class_id, classes.name as class_name')
            ->join('students', 'students.id = student_records.student_id')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.academic_year_id', $year['id'])
            ->where('student_records.status', 'aktif');

        if (!empty($classId)) {
            $builder->where('student_records.class_id', $classId);
        }

        $records = $builder->orderBy('classes.name', 'ASC')
                           ->orderBy('students.name', 'ASC')
                           ->findAll();

        return view('admin/active_classes/index', [
            'title'        => 'Kelas Aktif',
            'activeYear'   => $year,
            'records'      => $records,
            'classes'      => $this->classModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'selectedClass'=> $classId,
        ]);
    }

    public function update(int $id)
    {
        $data = [
            'class_id' => $this->request->getPost('class_id'),
            'status'   => $this->request->getPost('status'),
        ];
        $this->recordModel->update($id, $data);

        return redirect()->back()->with('success', 'Kelas/status siswa diperbarui.');
    }
}
