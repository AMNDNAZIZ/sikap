<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel;

class Profile extends BaseController
{
    protected $studentModel;
    protected $userModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userSession = session()->get('user');
        $student = $this->studentModel->where('user_id', $userSession['id'])->first();

        if (!$student) {
            return redirect()->to('/')->with('error', 'Data siswa tidak ditemukan.');
        }

        $db = db_connect();
        $record = $db->table('student_records')
            ->select('student_records.*, classes.name as class_name')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->where('student_records.student_id', $student['id'])
            ->orderBy('academic_years.start_date', 'DESC')
            ->get()
            ->getRowArray();

        $data = [
            'title' => 'Profil Saya',
            'student' => $student,
            'record' => $record,
            'classes' => $db->table('classes')->get()->getResultArray()
        ];

        return view('siswa/profile', $data);
    }

    public function update()
    {
        $userSession = session()->get('user');
        $student = $this->studentModel->where('user_id', $userSession['id'])->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan');
        }

        $post = $this->request->getPost();

        // Data yang boleh diupdate oleh siswa (kecuali NIS, NISN, Nama, Tempat Lhr, Tgl Lhr, Foto)
        // User said: "(kecuali NIS, NISN, Nama, Tempat, Tanggal lahir, Foto)"
        $data = [
            'nik' => $post['nik'] ?? null,
            'child_order' => $post['child_order'] ?? null,
            'religion' => $post['religion'] ?? null,
            'nationality' => $post['nationality'] ?? 'WNI',
            'address' => $post['address'] ?? null,
            'residence_type' => $post['residence_type'] ?? null,
            'transportation' => $post['transportation'] ?? null,
            'distance' => $post['distance'] ?? null,
            'latitude' => $post['latitude'] ?? null,
            'longitude' => $post['longitude'] ?? null,
            'special_needs' => $post['special_needs'] ?? null,
            'father_name' => $post['father_name'] ?? null,
            'father_nik' => $post['father_nik'] ?? null,
            'father_birth_year' => $post['father_birth_year'] ?? null,
            'father_education' => $post['father_education'] ?? null,
            'father_job' => $post['father_job'] ?? null,
            'father_income' => $post['father_income'] ?? null,
            'mother_name' => $post['mother_name'] ?? null,
            'mother_nik' => $post['mother_nik'] ?? null,
            'mother_birth_year' => $post['mother_birth_year'] ?? null,
            'mother_education' => $post['mother_education'] ?? null,
            'mother_job' => $post['mother_job'] ?? null,
            'mother_income' => $post['mother_income'] ?? null,
            'guardian_name' => $post['guardian_name'] ?? null,
            'guardian_education' => $post['guardian_education'] ?? null,
            'guardian_job' => $post['guardian_job'] ?? null,
            'guardian_income' => $post['guardian_income'] ?? null,
        ];

        $this->studentModel->update($student['id'], $data);

        return redirect()->to('siswa/profile')->with('success', 'Data profil berhasil diperbarui.');
    }
}
