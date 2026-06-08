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

            $students = $db->table('student_records sr')
                ->select('sr.*, s.name as student_name, s.gender, s.birth_date, s.address, s.photo, u.email')
                ->join('students s', 's.id = sr.student_id')
                ->join('users u', 'u.id = s.user_id', 'left')
                ->where('sr.class_id', $waliClass['id'])
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
            $record = $db->table('student_records')
                ->where('student_id', $studentId)
                ->where('class_id', $waliClass['id'])
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
}
