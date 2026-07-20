<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;

class WaliKelasStudents extends BaseController
{
    public function index()
    {
        try {
            $user = session()->get('user');
            $roleId = $user['role_id'] ?? null;
            
            if ($roleId != 3) {
                return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
            }

            $db = \Config\Database::connect();
            $teacherId = $user['related_id'];

            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRowArray();

            if (!$waliClass) {
                return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki kelas yang diwalikan');
            }

            $teacher = (new TeacherModel())->find($teacherId);

            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();

            $students = $db->table('student_records sr')
                ->select('sr.*, s.name as student_name, s.gender, s.birth_date, s.address, s.photo, u.email')
                ->join('students s', 's.id = sr.student_id')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->where('sr.class_id', $waliClass['id'])
                ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
                ->where('sr.status', 'aktif')
                ->orderBy('s.name', 'ASC')
                ->get()
                ->getResultArray();

            $stats = [
                'total' => count($students),
                'male' => 0,
                'female' => 0,
            ];

            foreach ($students as $student) {
                if ($student['gender'] == 'L') {
                    $stats['male']++;
                } elseif ($student['gender'] == 'P') {
                    $stats['female']++;
                }
            }

            // Get school data for layout
            $schoolModel = new \App\Models\SchoolModel();
            $school = $schoolModel->first();

            return view('admin/wali_kelas_students', [
                'user' => $user,
                'waliClass' => $waliClass,
                'teacher' => $teacher,
                'students' => $students,
                'stats' => $stats,
                'school' => $school,
                'title' => 'Data Siswa Wali Kelas',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'WaliKelasStudents index error: ' . $e->getMessage());
            return redirect()->to('/dashboard')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function detail($studentId)
    {
        try {
            $user = session()->get('user');
            $roleId = $user['role_id'] ?? null;
            
            if ($roleId != 3) {
                return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
            }

            $db = \Config\Database::connect();
            $teacherId = $user['related_id'];

            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRowArray();

            if (!$waliClass) {
                return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki kelas yang diwalikan');
            }

            // Get student data
            $student = $db->table('students')
                ->where('id', $studentId)
                ->get()
                ->getRowArray();

            if (!$student) {
                return redirect()->to('admin/wali-kelas-students')->with('error', 'Siswa tidak ditemukan');
            }

            // Get student record to verify class
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $record = $db->table('student_records')
                ->where('student_id', $studentId)
                ->where('class_id', $waliClass['id'])
                ->where('academic_year_id', $activeYear['id'] ?? 0)
                ->where('status', 'aktif')
                ->get()
                ->getRowArray();

            if (!$record) {
                return redirect()->to('admin/wali-kelas-students')->with('error', 'Siswa tidak ditemukan di kelas ini');
            }

            // Get email from users table
            if (!empty($student['user_id'])) {
                $user_data = $db->table('users')
                    ->select('email')
                    ->where('id', $student['user_id'])
                    ->get()
                    ->getRowArray();

                if ($user_data) {
                    $student['email'] = $user_data['email'];
                }
            }

            $teacher = (new TeacherModel())->find($teacherId);

            // Load helpers for view
            helper(['url', 'form']);
            
            return view('admin/wali_kelas_student_detail', [
                'user' => $user,
                'waliClass' => $waliClass,
                'teacher' => $teacher,
                'student' => $student,
                'record' => $record,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'WaliKelasStudents detail error: ' . $e->getMessage());
            return redirect()->to('admin/wali-kelas-students')->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function update($studentId)
    {
        try {
            $user = session()->get('user');
            $roleId = $user['role_id'] ?? null;

            if ($roleId != 3) {
                return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
            }

            $db = \Config\Database::connect();
            $teacherId = $user['related_id'];

            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRowArray();

            if (!$waliClass) {
                return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki kelas yang diwalikan');
            }

            // Verifikasi siswa ada di kelas wali
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $record = $db->table('student_records')
                ->where('student_id', $studentId)
                ->where('class_id', $waliClass['id'])
                ->where('academic_year_id', $activeYear['id'] ?? 0)
                ->where('status', 'aktif')
                ->get()
                ->getRowArray();

            if (!$record) {
                return redirect()->to('admin/wali-kelas-students')->with('error', 'Siswa tidak ditemukan di kelas ini');
            }

            // Field yang boleh diedit oleh wali kelas
            $allowedFields = [
                'address', 'residence_type', 'transportation', 'distance',
                'father_name', 'father_nik', 'father_birth_year', 'father_education', 'father_job', 'father_income',
                'mother_name', 'mother_nik', 'mother_birth_year', 'mother_education', 'mother_job', 'mother_income',
                'guardian_name', 'guardian_education', 'guardian_job', 'guardian_income',
                'special_needs', 'child_order',
            ];

            $data = [];
            foreach ($allowedFields as $field) {
                $value = $this->request->getPost($field);
                if ($value !== null) {
                    $data[$field] = $value;
                }
            }

            if (!empty($data)) {
                $studentModel = new \App\Models\StudentModel();
                $studentModel->update($studentId, $data);
            }

            return redirect()->to('admin/wali-kelas-students/detail/' . $studentId)
                ->with('success', 'Data siswa berhasil diperbarui.');
        } catch (\Exception $e) {
            log_message('error', 'WaliKelasStudents update error: ' . $e->getMessage());
            return redirect()->to('admin/wali-kelas-students/detail/' . $studentId)
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }
}
