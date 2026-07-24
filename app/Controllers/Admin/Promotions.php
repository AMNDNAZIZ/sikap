<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentRecordModel;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\AcademicYearModel;
use App\Models\ClassModel;

class Promotions extends BaseController
{
    protected $recordModel, $studentModel, $userModel, $yearModel, $classModel;

    public function __construct()
    {
        $this->recordModel  = new StudentRecordModel();
        $this->studentModel = new StudentModel();
        $this->userModel    = new UserModel();
        $this->yearModel    = new AcademicYearModel();
        $this->classModel   = new ClassModel();
    }

    /**
     * Level akhir berdasarkan jenjang sekolah.
     * SD=6, SMP=9, SMA=12
     */
    private function getFinalLevel(): int
    {
        $school = db_connect()->table('school_profile')->get()->getRowArray();
        $levelMap = [1 => 6, 2 => 9, 3 => 12];
        return $levelMap[$school['level'] ?? 1] ?? 6;
    }

    public function index()
    {
        $activeYear = $this->yearModel->where('is_active', 1)->first();
        $prevYear   = $this->yearModel->where('is_active', 0)->orderBy('start_date', 'DESC')->first();
        $filterClassId = $this->request->getGet('class_id');

        // Daftar kelas yang ada di tahun ajaran sebelumnya (untuk filter)
        $prevClasses = [];
        if ($prevYear) {
            $prevClasses = db_connect()->table('student_records sr')
                ->select('c.id, c.name, c.level')
                ->join('classes c', 'c.id = sr.class_id')
                ->where('sr.academic_year_id', $prevYear['id'])
                ->whereIn('sr.status', ['aktif', 'lulus'])
                ->groupBy('c.id')
                ->orderBy('c.level', 'ASC')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray();
        }

        // Siswa berdasarkan filter kelas
        $students         = [];
        $graduatedStudents = [];
        if ($prevYear && $filterClassId) {
            // Ambil semua siswa di kelas & tahun sebelumnya
            $allStudents = $this->recordModel
                ->select('student_records.id as record_id, student_records.class_id as old_class_id, student_records.status as old_status, student_records.graduation_date as old_graduation_date,
                          students.id as student_id, students.name, students.nis,
                          classes.name as class_name, classes.level as class_level')
                ->join('students', 'students.id = student_records.student_id')
                ->join('classes', 'classes.id = student_records.class_id', 'left')
                ->where('student_records.academic_year_id', $prevYear['id'])
                ->whereIn('student_records.status', ['aktif', 'lulus'])
                ->where('student_records.class_id', $filterClassId)
                ->orderBy('students.name', 'ASC')
                ->findAll();

            // Pisahkan siswa yang sudah diproses (naik kelas / lulus)
            if (!empty($allStudents)) {
                $allStudentIds = array_column($allStudents, 'student_id');

                $processedMap = [];
                if ($activeYear) {
                    $processedRecords = db_connect()->table('student_records sr')
                        ->select('sr.id as active_record_id, sr.student_id, sr.class_id as target_class_id, sr.status, sr.graduation_date, c.name as target_class_name')
                        ->join('classes c', 'c.id = sr.class_id', 'left')
                        ->where('sr.academic_year_id', $activeYear['id'])
                        ->whereIn('sr.student_id', $allStudentIds)
                        ->get()->getResultArray();

                    foreach ($processedRecords as $pr) {
                        $processedMap[$pr['student_id']] = $pr;
                    }
                }

                foreach ($allStudents as $s) {
                    if (isset($processedMap[$s['student_id']])) {
                        $record = $processedMap[$s['student_id']];
                        $s['active_record_id'] = $record['active_record_id'];
                        $s['processed_status'] = $record['status'];
                        $s['target_class_id']  = $record['target_class_id'];
                        $s['target_class_name']= $record['target_class_name'];
                        $s['graduation_date']  = $record['graduation_date'] ?? $s['old_graduation_date'];
                        $graduatedStudents[]   = $s;
                    } elseif ($s['old_status'] === 'lulus') {
                        $s['processed_status'] = 'lulus';
                        $s['graduation_date']  = $s['old_graduation_date'];
                        $graduatedStudents[]   = $s;
                    } else {
                        $students[] = $s;
                    }
                }
            }
        }

        // Semua kelas aktif untuk dropdown tujuan (tahun ajaran aktif)
        $targetClasses = $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll();
        $finalLevel    = $this->getFinalLevel();

        // Cek apakah kelas yang difilter adalah kelas akhir
        $selectedClass  = null;
        $isLastLevel    = false;
        if ($filterClassId) {
            foreach ($prevClasses as $c) {
                if ($c['id'] == $filterClassId) {
                    $selectedClass = $c;
                    $isLastLevel   = ((int)$c['level'] === $finalLevel);
                    break;
                }
            }
        }

        return view('admin/promotions/index', [
            'title'             => 'Kenaikan & Kelulusan',
            'activeYear'        => $activeYear,
            'prevYear'          => $prevYear,
            'prevClasses'       => $prevClasses,
            'students'          => $students,
            'graduatedStudents' => $graduatedStudents,
            'targetClasses'     => $targetClasses,
            'filterClassId'     => $filterClassId,
            'selectedClass'     => $selectedClass,
            'isLastLevel'       => $isLastLevel,
            'finalLevel'        => $finalLevel,
        ]);
    }

    /**
     * Naikkan/pindahkan siswa ke kelas baru di tahun ajaran aktif.
     */
    public function promote()
    {
        $activeYear = $this->yearModel->where('is_active', 1)->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $studentIds  = $this->request->getPost('student_ids') ?? [];
        $targetClass = $this->request->getPost('target_class_id');

        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa.');
        }
        if (!$targetClass) {
            return redirect()->back()->with('error', 'Pilih kelas tujuan.');
        }

        $db = db_connect();
        $db->transStart();
        $count = 0;

        foreach ($studentIds as $studentId) {
            // Cek apakah sudah ada record di tahun aktif
            $existing = $this->recordModel
                ->where('student_id', $studentId)
                ->where('academic_year_id', $activeYear['id'])
                ->first();

            if (!$existing) {
                $this->recordModel->insert([
                    'student_id'       => $studentId,
                    'class_id'         => $targetClass,
                    'academic_year_id' => $activeYear['id'],
                    'status'           => 'aktif',
                ]);
                $count++;
            }
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal memproses kenaikan kelas.');
        }

        return redirect()->to('/admin/promotions' . '?class_id=' . $this->request->getPost('filter_class_id'))
            ->with('success', "{$count} siswa berhasil dinaikkan ke kelas baru.");
    }

    /**
     * Luluskan siswa — hanya bisa dari kelas level akhir.
     * Record kelulusan dicatat pada Tahun Ajaran Sebelumnya (saat siswa lulus).
     */
    public function graduate()
    {
        $activeYear = $this->yearModel->where('is_active', 1)->first();
        $prevYear   = $this->yearModel->where('is_active', 0)->orderBy('start_date', 'DESC')->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $targetYearId = $prevYear ? $prevYear['id'] : $activeYear['id'];

        $studentIds     = $this->request->getPost('student_ids') ?? [];
        $filterClassId  = $this->request->getPost('filter_class_id');
        $graduationDate = $this->request->getPost('graduation_date');
        $finalLevel     = $this->getFinalLevel();

        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa.');
        }

        if (empty($graduationDate)) {
            return redirect()->back()->with('error', 'Tanggal kelulusan harus diisi.');
        }

        // Validasi kelas harus kelas akhir
        if ($filterClassId) {
            $class = $this->classModel->find($filterClassId);
            if (!$class || (int)$class['level'] !== $finalLevel) {
                return redirect()->back()->with('error',
                    'Hanya siswa dari kelas level akhir (level ' . $finalLevel . ') yang dapat diluluskan.');
            }
        }

        $db = db_connect();
        $db->transStart();
        $count = 0;

        foreach ($studentIds as $studentId) {
            // Update atau insert record kelulusan pada tahun ajaran kelulusan (tahun ajaran sebelumnya)
            $existingTarget = $this->recordModel
                ->where('student_id', $studentId)
                ->where('academic_year_id', $targetYearId)
                ->first();

            $graduateData = [
                'status'          => 'lulus',
                'graduation_date' => $graduationDate,
                'updated_at'      => date('Y-m-d H:i:s'),
            ];

            if ($existingTarget) {
                $db->table('student_records')
                   ->where('id', $existingTarget['id'])
                   ->update($graduateData);
            } else {
                $db->table('student_records')->insert([
                    'student_id'       => $studentId,
                    'class_id'         => $filterClassId,
                    'academic_year_id' => $targetYearId,
                    'status'           => 'lulus',
                    'graduation_date'  => $graduationDate,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
            }

            // Hapus record di tahun aktif jika ada
            if ($activeYear && $activeYear['id'] !== $targetYearId) {
                $db->table('student_records')
                   ->where('student_id', $studentId)
                   ->where('academic_year_id', $activeYear['id'])
                   ->delete();
            }

            // Nonaktifkan akun user siswa & orang tua
            $student = $this->studentModel->find($studentId);
            if ($student) {
                if ($db->fieldExists('is_active', 'users')) {
                    if (!empty($student['user_id'])) {
                        $db->table('users')
                           ->where('id', $student['user_id'])
                           ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
                    }
                    
                    // Nonaktifkan akun orang tua
                    $db->table('users')
                       ->where(['related_id' => $studentId, 'role_id' => 4, 'related_type' => 'student'])
                       ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            }
            $count++;
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal memproses kelulusan.');
        }

        return redirect()->to('/admin/promotions?class_id=' . $filterClassId)
            ->with('success', "{$count} siswa berhasil diluluskan.");
    }

    /**
     * Membatalkan kenaikan kelas atau kelulusan siswa.
     */
    public function cancel()
    {
        $activeYear = $this->yearModel->where('is_active', 1)->first();
        $prevYear   = $this->yearModel->where('is_active', 0)->orderBy('start_date', 'DESC')->first();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        $studentIds    = $this->request->getPost('student_ids') ?? [];
        $filterClassId = $this->request->getPost('filter_class_id');

        if (empty($studentIds)) {
            return redirect()->back()->with('error', 'Pilih minimal satu siswa.');
        }

        $db = db_connect();
        $db->transStart();
        $count = 0;

        foreach ($studentIds as $studentId) {
            // Hapus record di tahun aktif jika ada
            $db->table('student_records')
               ->where('student_id', $studentId)
               ->where('academic_year_id', $activeYear['id'])
               ->delete();

            // Kembalikan status record di tahun sebelumnya ke 'aktif' jika sebelumnya 'lulus'
            if ($prevYear) {
                $prevRecord = $this->recordModel
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $prevYear['id'])
                    ->first();

                if ($prevRecord && $prevRecord['status'] === 'lulus') {
                    $db->table('student_records')
                       ->where('id', $prevRecord['id'])
                       ->update([
                           'status'          => 'aktif',
                           'graduation_date' => null,
                           'updated_at'      => date('Y-m-d H:i:s'),
                       ]);
                }
            }

            // Mengaktifkan kembali akun user siswa & orang tua
            $student = $this->studentModel->find($studentId);
            if ($student && $db->fieldExists('is_active', 'users')) {
                if (!empty($student['user_id'])) {
                    $db->table('users')
                       ->where('id', $student['user_id'])
                       ->update(['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
                }
                
                // Aktifkan akun orang tua
                $db->table('users')
                   ->where(['related_id' => $studentId, 'role_id' => 4, 'related_type' => 'student'])
                   ->update(['is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            }

            $count++;
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal membatalkan kenaikan/kelulusan.');
        }

        return redirect()->to('/admin/promotions' . ($filterClassId ? '?class_id=' . $filterClassId : ''))
            ->with('success', "{$count} siswa berhasil dibatalkan kenaikan/kelulusannya.");
    }

    /** Backward-compat — proses lama */
    public function process()
    {
        return $this->promote();
    }

    /**
     * Endpoint satu kali (one-time) untuk mensinkronisasi dan memperbaiki 
     * akun siswa yang sudah terlanjur lulus sebelum sistem pemblokiran diterapkan.
     */
    public function fixGraduatedAccounts()
    {
        // Pastikan hanya admin/superadmin yang bisa menjalankan
        require_permission('students.update');

        $db = db_connect();
        
        // Ambil semua siswa
        $students = $this->studentModel->findAll();
        $count = 0;

        foreach ($students as $student) {
            // Cek rekam jejak akademik terakhir siswa ini
            $latestRecord = $db->table('student_records')
                ->where('student_id', $student['id'])
                ->orderBy('academic_year_id', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            // Jika status terakhirnya adalah lulus, dropout, atau nonaktif
            if ($latestRecord && in_array($latestRecord['status'], ['lulus', 'dropout', 'nonaktif'])) {
                // 1. Nonaktifkan akun siswanya
                if (!empty($student['user_id'])) {
                    $db->table('users')
                       ->where('id', $student['user_id'])
                       ->update(['is_active' => 0]);
                }
                
                // 2. Nonaktifkan akun orang tuanya
                $db->table('users')
                   ->where(['related_id' => $student['id'], 'role_id' => 4, 'related_type' => 'student'])
                   ->update(['is_active' => 0]);
                   
                $count++;
            }
        }

        return redirect()->back()->with('success', "Sinkronisasi berhasil: $count akun siswa (dan orang tua mereka) yang sudah lulus/nonaktif telah dinonaktifkan.");
    }
}
