<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkSummonModel;
use App\Models\StudentModel;

class CounselingSummon extends BaseController
{
    protected $summonModel;
    protected $studentModel;

    public function __construct()
    {
        $this->summonModel = new BkSummonModel();
        $this->studentModel = new StudentModel();
    }

    public function index()
    {
        $data = [
            'title'   => 'Surat Panggilan Orang Tua',
            'summons' => $this->summonModel->getSummonsWithDetails()
        ];
        return view('admin/counseling_summon/index', $data);
    }

    public function create()
    {
        $students = $this->studentModel
                         ->select('students.id, students.name, students.nis, classes.name as class_name')
                         ->join('classes', 'classes.id = students.class_id', 'left')
                         ->orderBy('classes.name', 'ASC')
                         ->orderBy('students.name', 'ASC')
                         ->findAll();

        $data = [
            'title'    => 'Buat Surat Panggilan Baru',
            'students' => $students
        ];
        return view('admin/counseling_summon/create', $data);
    }

    public function store()
    {
        $data = [
            'student_id'  => $this->request->getPost('student_id'),
            'issue_date'  => date('Y-m-d'),
            'summon_date' => $this->request->getPost('summon_date'),
            'reason'      => $this->request->getPost('reason'),
            'level'       => $this->request->getPost('level') ?: 'SP1',
            'status'      => 'Pending',
            'created_by'  => session()->get('user')['id']
        ];

        $this->summonModel->insert($data);
        return redirect()->to(base_url('admin/counseling-summon'))->with('success', 'Surat panggilan berhasil dibuat.');
    }

    public function updateStatus($id)
    {
        $data = [
            'status' => $this->request->getPost('status')
        ];
        $this->summonModel->update($id, $data);
        return redirect()->to(base_url('admin/counseling-summon'))->with('success', 'Status surat panggilan berhasil diperbarui.');
    }

    public function delete($id)
    {
        $this->summonModel->delete($id);
        return redirect()->to(base_url('admin/counseling-summon'))->with('success', 'Data berhasil dihapus.');
    }

    public function printPdf($id)
    {
        $summon = $this->summonModel->select('bk_summons.*, students.name as student_name, students.nis, classes.name as class_name, students.father_name, students.mother_name, students.guardian_name')
                                    ->join('students', 'students.id = bk_summons.student_id')
                                    ->join('classes', 'classes.id = students.class_id', 'left')
                                    ->where('bk_summons.id', $id)
                                    ->first();

        if (!$summon) {
            return redirect()->back()->with('error', 'Surat panggilan tidak ditemukan.');
        }

        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->first();
        
        $kop_base64 = '';
        if ($school && !empty($school['kop_surat'])) {
            $kopPath = FCPATH . ltrim($school['kop_surat'], '/');
            if (file_exists($kopPath)) {
                $type = pathinfo($kopPath, PATHINFO_EXTENSION);
                $data = file_get_contents($kopPath);
                $kop_base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        $data = [
            'summon'     => $summon,
            'school'     => $school,
            'kop_base64' => $kop_base64,
            'title'      => 'Surat Panggilan - ' . $summon['student_name']
        ];

        $html = view('admin/counseling_summon/print_pdf', $data);

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream('Surat_Panggilan_' . $summon['student_name'] . '.pdf', ['Attachment' => false]);
    }
}
