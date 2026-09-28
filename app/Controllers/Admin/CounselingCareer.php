<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkCareerModel;

class CounselingCareer extends BaseController
{
    protected $careerModel;

    public function __construct()
    {
        $this->careerModel = new BkCareerModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();
        
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : 0;

        // Siswa yang SUDAH isi angket
        $filled = $this->careerModel->getAllWithStudent();

        // Statistik rencana setelah lulus
        $statPlan = $this->careerModel->getStatPostGraduatePlan();

        // Jumlah siswa aktif yang BELUM isi
        $totalFilled  = count($filled);
        $totalStudents = (int) $db->query("SELECT COUNT(s.id) as total FROM students s JOIN student_records sr ON sr.student_id = s.id WHERE sr.status = 'aktif' AND sr.academic_year_id = ?", [$activeYearId])->getRow()->total;
        $totalNotFilled = max(0, $totalStudents - $totalFilled);

        $data = [
            'title'          => 'Angket Penelusuran Karir & Minat Bakat',
            'profiles'       => $filled,
            'statPlan'       => $statPlan,
            'totalFilled'    => $totalFilled,
            'totalNotFilled' => $totalNotFilled,
        ];

        return view('admin/counseling_career/index', $data);
    }

    public function show($id)
    {
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : 0;

        $profile = $this->careerModel
            ->select('bk_career_profiles.*, students.name as student_name, students.nis, students.gender, students.birth_date, classes.name as class_name, students.father_name, students.mother_name')
            ->join('students', 'students.id = bk_career_profiles.student_id')
            ->join('student_records', 'student_records.student_id = students.id')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('bk_career_profiles.id', $id)
            ->where('student_records.status', 'aktif')
            ->where('student_records.academic_year_id', $activeYearId)
            ->first();

        if (!$profile) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        return view('admin/counseling_career/show', ['title' => 'Detail Profil Karir - ' . $profile['student_name'], 'profile' => $profile]);
    }

    public function saveNote($id)
    {
        $this->careerModel->update($id, [
            'counselor_note' => $this->request->getPost('counselor_note')
        ]);
        return redirect()->back()->with('success', 'Catatan Guru BK berhasil disimpan.');
    }
}
