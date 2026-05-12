<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\StudentRecordModel;
use App\Models\UserModel;

class Students extends BaseController
{
    protected $studentModel;
    protected $classModel;
    protected $yearModel;
    protected $recordModel;
    protected $userModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->yearModel = new AcademicYearModel();
        $this->recordModel = new StudentRecordModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        // ✅ Authorization check
        require_permission('students.view');
        
        $classId = $this->request->getGet('class_id');
        $search = $this->request->getGet('search');

        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name, academic_years.year as academic_year, student_records.status, users.username')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left');

        // ✅ For teachers, only show students in their class
        if (is_teacher()) {
            $user = session()->get('user');
            $teacherId = $user['teacher_id'] ?? $user['related_id'] ?? null;
            
            if ($teacherId) {
                $teacherClass = $this->classModel->where('teacher_id', $teacherId)->first();
                if ($teacherClass) {
                    $builder->where('student_records.class_id', $teacherClass['id']);
                }
            }
        }

        if ($classId) {
            // ✅ Check if teacher can access this class
            if (is_teacher() && !can_access_class($classId)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke kelas ini.');
            }
            $builder->where('student_records.class_id', $classId);
        }

        if ($search) {
            $builder->groupStart()
                ->like('students.name', $search)
                ->orLike('students.nis', $search)
                ->orLike('students.nisn', $search)
                ->groupEnd();
        }

        $data = [
            'title' => 'Manajemen Siswa',
            'students' => $builder->paginate(10),
            'pager' => $builder->pager,
            'classes' => $this->classModel->findAll(),
            'selectedClass' => $classId,
            'search' => $search,
        ];

        return view('admin/students/index', $data);
    }

    public function create()
    {
        // ✅ Authorization check
        require_permission('students.create');
        
        return view('admin/students/create', [
            'title' => 'Tambah Siswa',
            'classes' => $this->classModel->findAll()
        ]);
    }

    public function store()
    {
        // ✅ Authorization check
        require_permission('students.create');
        
        helper('security'); // Load security helper
        
        $post = $this->request->getPost();

        // ✅ File upload validation
        $photoName = null;
        if ($photo = $this->request->getFile('photo')) {
            if ($photo->isValid() && !$photo->hasMoved()) {
                // Validate MIME type
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($photo->getMimeType(), $allowedTypes)) {
                    return redirect()->back()->withInput()->with('error', 'Tipe file foto tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
                }
                
                // Validate file size (max 2MB)
                if ($photo->getSize() > 2 * 1024 * 1024) {
                    return redirect()->back()->withInput()->with('error', 'Ukuran file foto terlalu besar. Maksimal 2MB.');
                }
                
                // Validate file extension
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (!in_array(strtolower($photo->getExtension()), $allowedExtensions)) {
                    return redirect()->back()->withInput()->with('error', 'Ekstensi file foto tidak valid.');
                }
                
                // Generate random filename
                $photoName = $photo->getRandomName();
                
                // Move to public uploads folder
                $uploadPath = FCPATH . 'uploads/students/';
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                try {
                    $photo->move($uploadPath, $photoName);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to upload student photo: ' . $e->getMessage());
                    return redirect()->back()->withInput()->with('error', 'Gagal mengupload foto. Silakan coba lagi.');
                }
            }
        }

        $username = strtolower($post['nis']);
        $email = $username . '@siswa.local';

        // validasi user unik
        $existingUser = $this->userModel->where('username', $username)->orWhere('email', $email)->first();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'User dengan NIS sudah ada.');
        }

        // Generate password dengan pattern: siswa[NIS]
        $defaultPassword = generate_default_password('siswa', $post['nis']);

        // insert user
        $this->userModel->insert([
            'username' => $username,
            'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
            'fullname' => $post['name'],
            'email' => $email,
            'role_id' => 5,
            'related_id' => null,
            'related_type' => 'student',
            'must_change_password' => 1, // Wajib ganti password
        ]);
        $userId = $this->userModel->getInsertID();

        // insert student
        $studentData = [
            'nisn' => $post['nisn'],
            'nis' => $post['nis'],
            'name' => $post['name'],
            'gender' => $post['gender'],
            'birth_place' => $post['birth_place'],
            'birth_date' => $post['birth_date'],
            'religion' => $post['religion'],
            'user_id' => $userId
        ];
        
        // Add photo if uploaded
        if ($photoName) {
            $studentData['photo'] = $photoName;
        }
        
        $this->studentModel->insert($studentData);
        $studentId = $this->studentModel->getInsertID();

        // update relasi user jika insert berhasil
        if (!empty($userId) && !empty($studentId)) {
            $this->userModel->update($userId, ['related_id' => $studentId]);
            // Otomatis buat akun orang tua
            $this->syncParentAccount($studentId, $post['nis'], $post['name']);
        }

        // simpan record akademik
        $year = $this->yearModel->where('is_active', 1)->first();
        if ($year && !empty($post['class_id'])) {
            $this->recordModel->insert([
                'student_id' => $studentId,
                'class_id' => $post['class_id'],
                'academic_year_id' => $year['id'],
                'status' => 'aktif'
            ]);
        }

        return redirect()->to('/admin/students')->with('success', 'Siswa berhasil ditambahkan beserta akun login.');
    }

    public function show($id)
    {
        // ✅ Authorization check
        require_permission('students.view');
        
        // ✅ Check if teacher can access this student
        if (is_teacher() && !can_access_student($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $student = $this->studentModel->find($id);

        if (!$student) {
            return redirect()->to('admin/students')->with('error', 'Siswa tidak ditemukan');
        }

        $db = db_connect();
        $record = $db->table('student_records')
            ->select('student_records.*, classes.name as class_name')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->where('student_records.student_id', $id)
            ->orderBy('academic_years.start_date', 'DESC')
            ->get()
            ->getRowArray();

        return view('admin/students/show', [
            'title' => 'Detail Siswa',
            'student' => $student,
            'record' => $record,
        ]);
    }

    public function edit($id)
    {
        // ✅ Authorization check
        require_permission('students.update');
        
        // ✅ Check if teacher can access this student
        if (is_teacher() && !can_access_student($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $student = $this->studentModel->find($id);

        $db = db_connect();
        $record = $db->table('student_records')
            ->select('student_records.*, classes.name as class_name')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->where('student_records.student_id', $id)
            ->orderBy('academic_years.start_date', 'DESC')
            ->get()
            ->getRowArray();

        return view('admin/students/edit', [
            'title' => 'Edit Siswa',
            'student' => $student,
            'classes' => $this->classModel->findAll(),
            'record' => $record,
        ]);
    }

    public function update($id)
    {
        // ✅ Authorization check
        require_permission('students.update');
        
        // ✅ Check if teacher can access this student
        if (is_teacher() && !can_access_student($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke siswa ini.');
        }
        
        $post = $this->request->getPost();
        $student = $this->studentModel->find($id);

        if (!$student) {
            return redirect()->to('admin/students')->with('error', 'Siswa tidak ditemukan');
        }

        $data = [
            'nisn' => $post['nisn'],
            'nis' => $post['nis'],
            'name' => $post['name'],
            'nik' => $post['nik'] ?? null,
            'gender' => $post['gender'],
            'birth_place' => $post['birth_place'],
            'birth_date' => $post['birth_date'],
            'child_order' => $post['child_order'] ?? null,
            'religion' => $post['religion'],
            'nationality' => $post['nationality'] ?? 'WNI',
            'admission_date' => $post['admission_date'] ?? null,
            'admission_class' => $post['admission_class'] ?? null,
            'registration_type' => $post['registration_type'] ?? 'Siswa Baru',
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

        // ✅ Handle Photo Upload with validation
        $photo = $this->request->getFile('photo');
        if ($photo && $photo->isValid() && !$photo->hasMoved()) {
            // Validate MIME type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($photo->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Tipe file foto tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
            }
            
            // Validate file size (max 2MB)
            if ($photo->getSize() > 2 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'Ukuran file foto terlalu besar. Maksimal 2MB.');
            }
            
            // Validate file extension
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($photo->getExtension()), $allowedExtensions)) {
                return redirect()->back()->withInput()->with('error', 'Ekstensi file foto tidak valid.');
            }
            
            // Delete old photo
            if (!empty($student['photo']) && file_exists(UPLOAD_PATH . 'students/' . $student['photo'])) {
                try {
                    unlink(UPLOAD_PATH . 'students/' . $student['photo']);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to delete old student photo: ' . $e->getMessage());
                }
            }
            
            // Upload new photo
            $newName = $photo->getRandomName();
            try {
                $photo->move(UPLOAD_PATH . 'students', $newName);
                $data['photo'] = $newName;
            } catch (\Exception $e) {
                log_message('error', 'Failed to upload student photo: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload foto. Silakan coba lagi.');
            }
        }

        $this->studentModel->update($id, $data);

        // sinkron ke user
        if (!empty($student['user_id'])) {
            $this->userModel->update($student['user_id'], [
                'fullname' => $post['name'],
            ]);
        }

        // update kelas
        if (!empty($post['class_id'])) {
            $year = $this->yearModel->where('is_active', 1)->first();

            if ($year) {
                $record = $this->recordModel
                    ->where('student_id', $id)
                    ->where('academic_year_id', $year['id'])
                    ->first();

                if ($record) {
                    $this->recordModel->update($record['id'], [
                        'class_id' => $post['class_id'],
                        'status' => $record['status'] ?? 'aktif'
                    ]);
                } else {
                    $this->recordModel->insert([
                        'student_id' => $id,
                        'academic_year_id' => $year['id'],
                        'class_id' => $post['class_id'],
                        'status' => 'aktif'
                    ]);
                }
            }
        }

        return redirect()->to('admin/students')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function delete($id)
    {
        // ✅ Authorization check
        require_permission('students.delete');
        
        // ✅ Teachers cannot delete students
        if (is_teacher()) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus siswa.');
        }
        
        $student = $this->studentModel->find($id);

        if ($student) {
            $db = db_connect();

            // Hapus data terkait di tabel lain untuk menghindari foreign key constraint
            $tables = [
                'student_records',
                'attendances',
                'student_notes',
                'material_scores',
                'summative_scores',
                'final_exam_scores',
                'cbt_sessions',
                'cbt_answers'
            ];

            // Hapus junction table yang tidak punya student_id langsung
            $noteIds = $db->table('student_notes')->where('student_id', $id)->get()->getResultArray();
            if (!empty($noteIds)) {
                $ids = array_column($noteIds, 'id');
                $db->table('student_note_behaviors')->whereIn('note_id', $ids)->delete();
            }

            foreach ($tables as $table) {
                // Cek apakah tabel ada sebelum mencoba menghapus
                if ($db->tableExists($table)) {
                    $db->table($table)->where('student_id', $id)->delete();
                }
            }

            // Hapus akun user siswa jika ada
            if (!empty($student['user_id'])) {
                $this->userModel->delete($student['user_id']);
            }

            // Hapus akun orang tua jika ada
            $this->userModel->where([
                'related_id' => $id,
                'role_id' => 4,
                'related_type' => 'student'
            ])->delete();

            // Hapus data siswa
            $this->studentModel->delete($id);
        }

        return redirect()->to('/admin/students')->with('success', 'Siswa beserta akun login dan data terkait berhasil dihapus.');
    }

    public function import()
    {
        // This method should handle POST request for file upload
        if (!$this->request->is('post')) {
            return redirect()->to('/admin/students')->with('error', 'Invalid request method.');
        }

        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'File tidak valid atau tidak ditemukan.');
        }

        // Validate file extension
        $allowedExtensions = ['xlsx', 'xls'];
        if (!in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            return redirect()->back()->with('error', 'Format file tidak valid. Hanya file Excel (.xlsx, .xls) yang diperbolehkan.');
        }

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet = $reader->getActiveSheet();
            $rows = $sheet->toArray();

            $failed = [];
            $success = [];

            foreach ($rows as $i => $row) {
                if ($i == 0) continue; // skip header

                // $row[0]=nisn, $row[1]=nis, $row[2]=nama, $row[3]=gender, $row[4]=tmp lahir, $row[5]=tgl lahir
                $nisn = $row[0] ?? '';
                $nis = $row[1] ?? '';
                $name = $row[2] ?? '';
                $gender = $row[3] ?? '';
                $birthPlace = $row[4] ?? '';
                $birthDate = $row[5] ?? '';

                if (empty($nis) || empty($name)) {
                    if (!empty($nis) || !empty($name)) {
                        $failed[] = "Baris " . ($i + 1) . " - Data tidak lengkap";
                    }
                    continue;
                }

                $username = strtolower($nis);
                $email = $username . '@siswa.local';

                // cek user duplikat
                $existingUser = $this->userModel->where('username', $username)->orWhere('email', $email)->first();
                if ($existingUser) {
                    $failed[] = "$nis - $name (user sudah ada)";
                    continue;
                }

                // Generate password dengan pattern: siswa[NIS]
                helper('security');
                $defaultPassword = generate_default_password('siswa', $nis);

                $this->userModel->insert([
                    'username' => $username,
                    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname' => $name,
                    'email' => $email,
                    'role_id' => 5,
                    'related_id' => null,
                    'related_type' => 'student',
                    'must_change_password' => 1,
                ]);
                $userId = $this->userModel->getInsertID();

                $this->studentModel->insert([
                    'nisn' => $nisn,
                    'nis' => $nis,
                    'name' => $name,
                    'gender' => $gender,
                    'birth_place' => $birthPlace,
                    'birth_date' => $birthDate,
                    'user_id' => $userId
                ]);
                $studentId = $this->studentModel->getInsertID();

                if (!empty($userId) && !empty($studentId)) {
                    $this->userModel->update($userId, ['related_id' => $studentId]);
                    // Otomatis buat akun orang tua
                    $this->syncParentAccount($studentId, $nis, $name);
                }

                $success[] = "$nis - $name";
            }

            if (empty($success) && empty($failed)) {
                return redirect()->to('/admin/students')->with('error', 'Tidak ada data yang diimpor. Pastikan file Excel berisi data siswa.');
            }

            return redirect()->to('/admin/students')
                ->with('import_success', $success)
                ->with('import_failed', $failed);

        } catch (\Exception $e) {
            log_message('error', 'Import students error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'NISN');
        $sheet->setCellValue('B1', 'NIS');
        $sheet->setCellValue('C1', 'Nama');
        $sheet->setCellValue('D1', 'Jenis Kelamin (L/P)');
        $sheet->setCellValue('E1', 'Tempat Lahir');
        $sheet->setCellValue('F1', 'Tanggal Lahir (YYYY-MM-DD)');

        // Contoh Data
        $sheet->setCellValue('A2', '0012345678');
        $sheet->setCellValue('B2', '2021001');
        $sheet->setCellValue('C2', 'Siswa Contoh');
        $sheet->setCellValue('D2', 'L');
        $sheet->setCellValue('E2', 'Jakarta');
        $sheet->setCellValue('F2', '2010-01-01');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_impor_siswa.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function syncParentAccount($studentId, $nis, $name)
    {
        helper('security'); // Load security helper
        
        $username = 'ortu_' . $nis;
        $existing = $this->userModel->where([
            'related_id' => $studentId,
            'role_id' => 4,
            'related_type' => 'student'
        ])->first();

        // Generate password dengan pattern: ortu[NIS] atau tetap 12345678
        // Pilihan 1: Pattern (lebih unik)
        $defaultPassword = generate_default_password('ortu', $nis);
        
        // Pilihan 2: Fixed password (lebih mudah diingat untuk semua orang tua)
        // $defaultPassword = '12345678';

        $userData = [
            'username' => $username,
            'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
            'fullname' => 'Orang Tua ' . $name,
            'email' => $nis . '@ortu.com',
            'role_id' => 4,
            'related_id' => $studentId,
            'related_type' => 'student',
            'must_change_password' => 1, // Wajib ganti password
        ];

        if (!$existing) {
            $this->userModel->insert($userData);
        } else {
            $this->userModel->update($existing['id'], $userData);
        }
    }
}
