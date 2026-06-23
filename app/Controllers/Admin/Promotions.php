<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentRecordModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\AcademicYearModel;
use App\Models\ClassModel;

class Promotions extends BaseController {
    protected $recordModel,$studentModel,$userModel,$yearModel,$classModel;
    public function __construct() {
        $this->recordModel=new StudentRecordModel();
        $this->studentModel=new StudentModel();
        $this->userModel=new UserModel();
        $this->yearModel=new AcademicYearModel();
        $this->classModel=new ClassModel();
    }

    public function index() {
        $activeYear = $this->yearModel->where('is_active',1)->first();
        $prevYear   = $this->yearModel->where('is_active',0)->orderBy('start_date','DESC')->first();

        $students=[];
        if($prevYear){
            $students=$this->recordModel
                ->select('student_records.*,students.name,classes.name as class_name')
                ->join('students','students.id=student_records.student_id')
                ->join('classes','classes.id=student_records.class_id','left')
                ->where('student_records.academic_year_id',$prevYear['id'])
                ->where('student_records.status','aktif')
                ->findAll();
        }

        return view('admin/promotions/index',[
            'title'=>'Kenaikan & Kelulusan',
            'students'=>$students,
            'activeYear'=>$activeYear,
            'classes'=>$this->classModel->findAll()
        ]);
    }

    public function process() {
        $activeYear=$this->yearModel->where('is_active',1)->first();
        $post=$this->request->getPost();
        $db = db_connect();
        $db->transStart();

        foreach($post['student_id'] as $i=>$sid){
            $status = $post['status'][$i];
            $recordData = [
                'student_id'=>$sid,
                'academic_year_id'=>$activeYear['id'],
                'class_id'=>$post['class_id'][$i] ?? null,
                'status'=>$status
            ];

            if ($status === 'lulus') {
                $recordData['graduation_date'] = date('Y-m-d');
            }

            $this->recordModel->insert($recordData);

            if (in_array($status, ['lulus', 'dropout', 'nonaktif'])) {
                $student = $this->studentModel->find($sid);
                if ($student && !empty($student['user_id'])) {
                    $this->userModel->update($student['user_id'], ['is_active' => 0]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/admin/promotions')->with('error', 'Gagal memproses kenaikan kelas.');
        }

        return redirect()->to('/admin/promotions')->with('success','Kenaikan berhasil diproses.');
    }
}
