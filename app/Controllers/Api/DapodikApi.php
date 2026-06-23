<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\AcademicYearModel;
use App\Models\StudentRecordModel;
use App\Models\ClassModel;

class DapodikApi extends BaseController
{
    protected $studentModel;
    protected $teacherModel;
    protected $userModel;
    protected $yearModel;
    protected $recordModel;
    protected $classModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();
        $this->yearModel = new AcademicYearModel();
        $this->recordModel = new StudentRecordModel();
        $this->classModel = new ClassModel();
    }

    public function receive()
    {
        // Log incoming request for debugging
        log_message('info', 'Dapodik Sync API: Incoming request from ' . $this->request->getIPAddress());
        
        $token = $this->request->getHeaderLine('X-Siakad-Sync-Token');
        $expectedToken = env('SIAKAD_SYNC_TOKEN');

        if (empty($expectedToken)) {
            log_message('error', 'Dapodik Sync API: SIAKAD_SYNC_TOKEN not configured in .env');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Server configuration error. Sync token not configured.'
            ])->setStatusCode(500);
        }

        if (empty($token) || $token !== $expectedToken) {
            log_message('warning', 'Dapodik Sync API: Unauthorized access attempt. Token: ' . substr($token, 0, 10) . '...');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized access. Invalid or missing sync token.'
            ])->setStatusCode(401);
        }

        $json = $this->request->getJSON(true);
        if (!$json) {
            log_message('error', 'Dapodik Sync API: Invalid JSON data received');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid JSON data.'
            ])->setStatusCode(400);
        }

        $type = $json['type'] ?? '';
        $data = $json['data'] ?? [];

        log_message('info', 'Dapodik Sync API: Processing type=' . $type . ', data count=' . count($data));

        if ($type === 'students') {
            return $this->processStudents($data);
        } elseif ($type === 'teachers') {
            return $this->processTeachers($data);
        }

        log_message('error', 'Dapodik Sync API: Unknown data type: ' . $type);
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Unknown data type. Expected "students" or "teachers".'
        ])->setStatusCode(400);
    }

    private function processStudents($students)
    {
        helper('security');
        
        if (empty($students) || !is_array($students)) {
            log_message('warning', 'Dapodik Sync API: Empty or invalid students data');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No student data provided or invalid format.'
            ])->setStatusCode(400);
        }
        
        $success = 0;
        $skipped = 0;
        $errors = [];

        // Prep class map for auto-detection
        $allClasses = $this->classModel->findAll();
        $classMap = [];
        foreach ($allClasses as $c) {
            $classMap[strtolower(trim($c['name']))] = $c['id'];
        }

        $year = $this->yearModel->where('is_active', 1)->first();
        
        if (!$year) {
            log_message('error', 'Dapodik Sync API: No active academic year found');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No active academic year. Please activate an academic year first.'
            ])->setStatusCode(400);
        }

        foreach ($students as $index => $s) {
            $nisn = $s['nisn'] ?? '';
            if (empty($nisn)) {
                $errors[] = "Row " . ($index + 1) . ": NISN kosong";
                continue;
            }

            $existing = $this->studentModel->where('nisn', $nisn)->first();
            if ($existing) {
                $skipped++;
                continue;
            }

            try {
                // Create User account
                $username = strtolower($nisn);
                $email = $username . '@siswa.local';
                $name = strtoupper($s['nama'] ?? '-');
                $defaultPassword = generate_default_password('siswa', $nisn);

                $this->userModel->insert([
                    'username' => $username,
                    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname' => $name,
                    'email' => $email,
                    'role_id' => 5,
                    'related_type' => 'student',
                    'must_change_password' => 1,
                ]);
                $userId = $this->userModel->getInsertID();

                // Mapping data lengkap dari Dapodik
                $studentData = [
                    'nisn' => $nisn,
                    'nis' => $s['nis'] ?? $nisn,
                    'name' => $name,
                    'nik' => $s['nik'] ?? '',
                    'gender' => $s['jenis_kelamin'] ?? 'L',
                    'birth_place' => $s['tempat_lahir'] ?? '-',
                    'birth_date' => $s['tanggal_lahir'] ?? '2000-01-01',
                    'child_order' => $s['anak_keberapa'] ?? '',
                    'religion' => $s['agama'] ?? '',
                    'nationality' => $s['kewarganegaraan'] ?? 'WNI',
                    'address' => $s['alamat_jalan'] ?? '',
                    'residence_type' => $s['jenis_tinggal'] ?? '',
                    'transportation' => $s['moda_transportasi'] ?? '',
                    'distance' => $s['jarak_rumah_ke_sekolah'] ?? '',
                    'latitude' => $s['latitude'] ?? '',
                    'longitude' => $s['longitude'] ?? '',
                    'special_needs' => $s['kebutuhan_khusus'] ?? '',
                    // Data Orang Tua - Ayah
                    'father_name' => $s['nama_ayah'] ?? '',
                    'father_nik' => $s['nik_ayah'] ?? '',
                    'father_birth_year' => $s['tahun_lahir_ayah'] ?? '',
                    'father_education' => $s['pendidikan_ayah'] ?? '',
                    'father_job' => $s['pekerjaan_ayah'] ?? '',
                    'father_income' => $s['penghasilan_ayah'] ?? '',
                    // Data Orang Tua - Ibu
                    'mother_name' => $s['nama_ibu_kandung'] ?? '',
                    'mother_nik' => $s['nik_ibu'] ?? '',
                    'mother_birth_year' => $s['tahun_lahir_ibu'] ?? '',
                    'mother_education' => $s['pendidikan_ibu'] ?? '',
                    'mother_job' => $s['pekerjaan_ibu'] ?? '',
                    'mother_income' => $s['penghasilan_ibu'] ?? '',
                    // Data Wali
                    'guardian_name' => $s['nama_wali'] ?? '',
                    'guardian_education' => $s['pendidikan_wali'] ?? '',
                    'guardian_job' => $s['pekerjaan_wali'] ?? '',
                    'guardian_income' => $s['penghasilan_wali'] ?? '',
                    'user_id' => $userId
                ];

                // Store Student dengan data lengkap
                $this->studentModel->insert($studentData);
                $studentId = $this->studentModel->getInsertID();

                if ($userId && $studentId) {
                    $this->userModel->update($userId, ['related_id' => $studentId]);

                    // Auto-detect class
                    $dapodikRombel = $s['nama_rombel'] ?? '';
                    if (!empty($dapodikRombel)) {
                        $cleanRombel = strtolower(trim($dapodikRombel));
                        if (isset($classMap[$cleanRombel])) {
                            $this->recordModel->insert([
                                'student_id' => $studentId,
                                'class_id' => $classMap[$cleanRombel],
                                'academic_year_id' => $year['id'],
                                'status' => 'aktif'
                            ]);
                        }
                    }
                    $success++;
                    log_message('info', 'Dapodik Sync API: Student created - NISN: ' . $nisn . ', Name: ' . $name);
                }
            } catch (\Exception $e) {
                $errors[] = "NISN $nisn: " . $e->getMessage();
                log_message('error', 'Dapodik Sync API: Error creating student NISN ' . $nisn . ': ' . $e->getMessage());
            }
        }

        $message = "Berhasil sinkronisasi $success siswa via API. Melompati $skipped siswa (sudah ada).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " (+" . (count($errors) - 5) . " more errors)";
            }
        }

        log_message('info', 'Dapodik Sync API: Students sync completed - Success: ' . $success . ', Skipped: ' . $skipped . ', Errors: ' . count($errors));

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'summary' => [
                'success' => $success,
                'skipped' => $skipped,
                'errors' => count($errors)
            ]
        ]);
    }

    private function processTeachers($teachers)
    {
        helper('security');
        
        if (empty($teachers) || !is_array($teachers)) {
            log_message('warning', 'Dapodik Sync API: Empty or invalid teachers data');
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'No teacher data provided or invalid format.'
            ])->setStatusCode(400);
        }
        
        $success = 0;
        $skipped = 0;
        $errors = [];

        foreach ($teachers as $index => $t) {
            $nuptk = $t['nuptk'] ?? $t['nik'] ?? '';
            if (empty($nuptk)) {
                $errors[] = "Row " . ($index + 1) . ": NUPTK/NIK kosong";
                continue;
            }

            $existing = $this->teacherModel->where('nuptk', $nuptk)->first();
            if ($existing) {
                $skipped++;
                continue;
            }

            try {
                // Create User account
                $username = strtolower($nuptk);
                $email = $username . '@guru.local';
                $name = strtoupper($t['nama'] ?? '-');
                $defaultPassword = generate_default_password('guru', $nuptk);

                $this->userModel->insert([
                    'username' => $username,
                    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname' => $name,
                    'email' => $email,
                    'role_id' => 3,
                    'related_type' => 'teacher',
                    'must_change_password' => 1,
                ]);
                $userId = $this->userModel->getInsertID();

                // Store Teacher
                $this->teacherModel->insert([
                    'nuptk' => $nuptk,
                    'name' => $name,
                    'gender' => $t['jenis_kelamin'] ?? 'L',
                    'birth_place' => $t['tempat_lahir'] ?? '-',
                    'birth_date' => $t['tanggal_lahir'] ?? '1980-01-01',
                    'user_id' => $userId
                ]);
                $teacherId = $this->teacherModel->getInsertID();

                if ($userId && $teacherId) {
                    $this->userModel->update($userId, ['related_id' => $teacherId]);
                    $success++;
                    log_message('info', 'Dapodik Sync API: Teacher created - NUPTK: ' . $nuptk . ', Name: ' . $name);
                }
            } catch (\Exception $e) {
                $errors[] = "NUPTK $nuptk: " . $e->getMessage();
                log_message('error', 'Dapodik Sync API: Error creating teacher NUPTK ' . $nuptk . ': ' . $e->getMessage());
            }
        }

        $message = "Berhasil sinkronisasi $success guru via API. Melompati $skipped guru (sudah ada).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " (+" . (count($errors) - 5) . " more errors)";
            }
        }

        log_message('info', 'Dapodik Sync API: Teachers sync completed - Success: ' . $success . ', Skipped: ' . $skipped . ', Errors: ' . count($errors));

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $message,
            'summary' => [
                'success' => $success,
                'skipped' => $skipped,
                'errors' => count($errors)
            ]
        ]);
    }
}
