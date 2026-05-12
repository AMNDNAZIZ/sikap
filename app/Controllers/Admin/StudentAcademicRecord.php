<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentAcademicRecordModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;

class StudentAcademicRecord extends BaseController
{
    protected $recordModel;
    protected $studentModel;
    protected $classModel;
    protected $yearModel;

    public function __construct()
    {
        $this->recordModel  = new StudentAcademicRecordModel();
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->yearModel    = new AcademicYearModel();
    }

    public function index()
    {
        $studentId   = $this->request->getGet('student_id');
        $yearId      = $this->request->getGet('year_id');
        $classId     = $this->request->getGet('class_id');

        $activeYear  = $this->yearModel->where('is_active', 1)->first();

        if ($studentId) {
            // Mode riwayat per siswa
            $student = $this->studentModel->find($studentId);
            $records = $this->recordModel->getHistoryByStudent($studentId);

            return view('admin/student_academic_records/index', [
                'title'    => 'Riwayat Akademik Siswa',
                'student'  => $student,
                'records'  => $records,
                'allYears' => $this->yearModel->orderBy('year', 'DESC')->findAll(),
                'classes'  => $this->classModel->findAll(),
            ]);
        } else {
            // Mode daftar siswa per tahun & kelas
            $yearId   = $yearId ?: ($activeYear['id'] ?? null);
            $records  = $this->recordModel->getWithRelations($yearId, $classId);

            return view('admin/student_academic_records/index', [
                'title'       => 'Riwayat Siswa per Tahun',
                'activeYear'  => $activeYear,
                'records'     => $records,
                'allYears'    => $this->yearModel->orderBy('year', 'DESC')->findAll(),
                'classes'     => $this->classModel->findAll(),
                'yearId'      => $yearId,
                'classId'     => $classId,
                'allStudents' => $this->studentModel->findAll(),
            ]);
        }
    }

    public function searchStudents()
    {
        $term = $this->request->getGet('q');
        $students = $this->studentModel
            ->like('name', $term)
            ->orLike('nisn', $term)
            ->select('id, name, nisn')
            ->findAll(20); // batasi 20 hasil

        return $this->response->setJSON($students);
    }


    public function updateStatus($id)
    {
        $status = $this->request->getPost('status');

        if ($status) {
            $this->recordModel->update($id, ['status' => $status]);
        }

        return redirect()->to('/admin/student-academic-records')->with('success', 'Status siswa diperbarui.');
    }

    public function promote()
    {
        $selectedIds = $this->request->getPost('student_ids');
        $newClassId  = $this->request->getPost('new_class_id');
        $activeYear  = $this->yearModel->where('is_active', 1)->first();

        if ($selectedIds && $newClassId && $activeYear) {
            // cari tahun ajaran berikutnya
            $nextYear = $this->yearModel
                ->where('id >', $activeYear['id'])
                ->orderBy('id', 'ASC')
                ->first();

            if ($nextYear) {
                foreach ($selectedIds as $id) {
                    $record = $this->recordModel->find($id);

                    if ($record) {
                        $this->recordModel->insert([
                            'student_id'       => $record['student_id'],
                            'academic_year_id' => $nextYear['id'],
                            'class_id'         => $newClassId,
                            'status'           => 'aktif',
                        ]);
                    }
                }
            }
        }

        return redirect()->to('/admin/student-academic-records')->with('success', 'Kenaikan kelas berhasil diproses.');
    }


    public function graduate()
    {
        $selectedIds = $this->request->getPost('student_ids');

        if ($selectedIds) {
            foreach ($selectedIds as $id) {
                $this->recordModel->update($id, ['status' => 'lulus']);
            }
        }

        return redirect()->to('/admin/student-academic-records')->with('success', 'Kelulusan berhasil diproses.');
    }
}
