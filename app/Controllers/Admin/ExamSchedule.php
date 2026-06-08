<?php

namespace App\Controllers\Admin;

use App\Models\ExamScheduleModel;
use App\Models\SubjectModel;
use App\Models\ClassModel;
use CodeIgniter\Controller;
use App\Controllers\BaseController;

class ExamSchedule extends BaseController
{
    protected $scheduleModel;
    protected $subjectModel;
    protected $classModel;

    public function __construct()
    {
        $this->scheduleModel = new ExamScheduleModel();
        $this->subjectModel  = new SubjectModel();
        $this->classModel    = new ClassModel();
    }

    public function index()
    {
        $data['schedules'] = $this->scheduleModel
            ->select('exam_schedules.*, subjects.name AS subject_name, classes.name AS class_name')
            ->join('subjects', 'subjects.id = exam_schedules.subject_id', 'left')
            ->join('classes', 'classes.id = exam_schedules.class_id', 'left')
            ->orderBy('exam_date', 'ASC')
            ->findAll();

        return view('admin/exam_schedule/index', $data);
    }

    public function create()
    {
        $data = [
            'subjects' => $this->subjectModel->findAll(),
            'classes'  => $this->classModel->findAll()
        ];
        return view('admin/exam_schedule/create', $data);
    }

    public function store()
    {
        $this->scheduleModel->insert([
            'subject_id'  => $this->request->getPost('subject_id'),
            'class_id'    => $this->request->getPost('class_id'),
            'exam_date'   => $this->request->getPost('exam_date'),
            'start_time'  => $this->request->getPost('start_time'),
            'end_time'    => $this->request->getPost('end_time'),
            'description' => $this->request->getPost('description'),
        ]);

        return redirect()->to(site_url('admin/exam-schedule'))
            ->with('success', 'Jadwal ujian berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $data = [
            'schedule' => $this->scheduleModel->find($id),
            'subjects' => $this->subjectModel->findAll(),
            'classes'  => $this->classModel->findAll()
        ];

        return view('admin/exam_schedule/edit', $data);
    }

    public function update($id)
    {
        $this->scheduleModel->update($id, [
            'subject_id'  => $this->request->getPost('subject_id'),
            'class_id'    => $this->request->getPost('class_id'),
            'exam_date'   => $this->request->getPost('exam_date'),
            'start_time'  => $this->request->getPost('start_time'),
            'end_time'    => $this->request->getPost('end_time'),
            'description' => $this->request->getPost('description'),
        ]);

        return redirect()->to(site_url('admin/exam-schedule'))
            ->with('success', 'Jadwal ujian berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->scheduleModel->delete($id);
        return redirect()->to(site_url('admin/exam-schedule'))
            ->with('success', 'Jadwal ujian berhasil dihapus.');
    }
}
