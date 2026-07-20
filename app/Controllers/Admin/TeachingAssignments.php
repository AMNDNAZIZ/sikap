<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\AcademicYearModel;

class TeachingAssignments extends BaseController
{
    protected $assignmentModel;
    protected $teacherModel;
    protected $classModel;
    protected $subjectModel;
    protected $yearModel;

    public function __construct()
    {
        $this->assignmentModel = new TeachingAssignmentModel();
        $this->teacherModel = new TeacherModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->yearModel = new AcademicYearModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('q');
        $yearId  = $this->request->getGet('year_id');

        // Default ke tahun ajaran aktif jika tidak ada filter
        if ($yearId === null) {
            $activeYear = $this->yearModel->getActiveYear();
            $yearId = $activeYear['id'] ?? null;
        }

        $data = [
            'assignments' => $this->assignmentModel->searchAssignments($keyword, $yearId, 25),
            'pager'       => $this->assignmentModel->pager,
            'keyword'     => $keyword,
            'yearId'      => $yearId,
            'years'       => $this->yearModel->orderBy('start_date', 'DESC')->findAll(),
        ];

        return view('admin/teaching_assignments/index', $data);
    }



    public function bulkDelete()
    {
        $ids = $this->request->getPost('ids');
        if ($ids && is_array($ids)) {
            foreach ($ids as $id) {
                $this->assignmentModel->delete($id);
            }
        }
        return redirect()->to('/admin/teachingassignments')->with('message', 'Beberapa pemetaan berhasil dihapus');
    }


    public function create()
    {
        $data = [
            'teachers' => $this->teacherModel->findAll(),
            'classes'  => $this->classModel->findAll(),
            'subjects' => $this->subjectModel->findAll(),
            'years'    => $this->yearModel->findAll(),
        ];
        return view('admin/teaching_assignments/create', $data);
    }

    /**
     * Get existing assignments for conflict checking
     * Returns JSON with classes that already have teachers for a specific subject
     */
    public function getExistingAssignments()
    {
        $subjectId = $this->request->getGet('subject_id');
        $yearId = $this->request->getGet('year_id');
        
        if (!$subjectId || !$yearId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Subject ID and Year ID required'
            ]);
        }
        
        // Get all assignments for this subject and year
        $assignments = $this->assignmentModel
            ->select('teaching_assignments.*, classes.name as class_name, teachers.name as teacher_name')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->where('teaching_assignments.subject_id', $subjectId)
            ->where('teaching_assignments.academic_year_id', $yearId)
            ->findAll();
        
        // Group by class_id for easy lookup
        $conflictData = [];
        foreach ($assignments as $assignment) {
            $conflictData[$assignment['class_id']] = [
                'teacher_name' => $assignment['teacher_name'],
                'class_name' => $assignment['class_name']
            ];
        }
        
        return $this->response->setJSON([
            'success' => true,
            'conflicts' => $conflictData
        ]);
    }

    public function store()
    {
        $role = $this->request->getPost('role');
        $teacherId = $this->request->getPost('teacher_id');
        $yearId = $this->request->getPost('academic_year_id');

        if ($role === 'guru_kelas') {
            $classId = $this->request->getPost('class_id');
            $subjectIds = $this->request->getPost('subject_ids');
            if ($classId && $subjectIds) {
                foreach ($subjectIds as $subjectId) {
                    $this->assignmentModel->insert([
                        'teacher_id'       => $teacherId,
                        'class_id'         => $classId,
                        'subject_id'       => $subjectId,
                        'academic_year_id' => $yearId,
                        'role'             => 'guru_kelas'
                    ]);
                }
            }
        } elseif ($role === 'guru_mapel') {
            $subjectId = $this->request->getPost('subject_id');
            $classIds = $this->request->getPost('class_ids');
            if ($subjectId && $classIds) {
                foreach ($classIds as $classId) {
                    $this->assignmentModel->insert([
                        'teacher_id'       => $teacherId,
                        'class_id'         => $classId,
                        'subject_id'       => $subjectId,
                        'academic_year_id' => $yearId,
                        'role'             => 'guru_mapel'
                    ]);
                }
            }
        }

        return redirect()->to('/admin/teachingassignments')->with('message', 'Pemetaan berhasil disimpan');
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        $this->assignmentModel->update($id, $data);

        return redirect()->to('/admin/teachingassignments')->with('message', 'Pemetaan berhasil diperbarui');
    }



    public function edit($id)
    {
        $data = [
            'assignment' => $this->assignmentModel->find($id),
            'teachers'   => $this->teacherModel->findAll(),
            'classes'    => $this->classModel->findAll(),
            'subjects'   => $this->subjectModel->findAll(),
            'years'      => $this->yearModel->findAll(),
        ];
        return view('admin/teaching_assignments/edit', $data);
    }


    public function delete($id)
    {
        $this->assignmentModel->delete($id);
        return redirect()->to('/admin/teachingassignments');
    }
}
