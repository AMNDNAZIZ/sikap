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
    protected $db;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->yearModel = new AcademicYearModel();
        $this->recordModel = new StudentRecordModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // ✅ Authorization check
        require_permission('students.view');
        
        $classId = $this->request->getGet('class_id');
        $search = $this->request->getGet('search');
        $academicYearId = $this->request->getGet('academic_year_id');
        $statusFilter = $this->request->getGet('status');

        if ($academicYearId === null) {
            $activeYear = $this->yearModel->where('is_active', 1)->first();
            $academicYearId = $activeYear ? $activeYear['id'] : '';
        }

        if ($statusFilter === null) {
            $statusFilter = 'aktif';
        }

        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name, academic_years.year as academic_year, student_records.status, users.username')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left');

        if ($academicYearId && $academicYearId !== 'all') {
            $builder->where('student_records.academic_year_id', $academicYearId);
        }

        if ($statusFilter && $statusFilter !== 'all') {
            $builder->where('student_records.status', $statusFilter);
        }

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
            'classes' => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll(),
            'academicYears' => $this->yearModel->orderBy('start_date', 'DESC')->findAll(),
            'selectedClass' => $classId,
            'selectedYear' => $academicYearId,
            'selectedStatus' => $statusFilter,
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
            'classes' => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll()
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
            'classes' => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll(),
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

        // update kelas & status
        $year = $this->yearModel->where('is_active', 1)->first();

        if ($year) {
            $newClassId = $post['class_id'] ?? null;
            $newStatus = $post['status'] ?? null;

            $record = $this->recordModel
                ->where('student_id', $id)
                ->where('academic_year_id', $year['id'])
                ->first();

            if ($record) {
                $updateData = [];
                if (!empty($newClassId)) {
                    $updateData['class_id'] = $newClassId;
                }
                if ($newStatus && $newStatus !== $record['status']) {
                    $updateData['status'] = $newStatus;
                    if ($newStatus === 'lulus') {
                        $updateData['graduation_date'] = date('Y-m-d');
                    }
                }
                if (!empty($updateData)) {
                    $this->recordModel->update($record['id'], $updateData);
                }

                // deactivate user account for non-active statuses
                if ($newStatus && in_array($newStatus, ['lulus', 'dropout', 'nonaktif'])) {
                    if (!empty($student['user_id'])) {
                        $this->db->table('users')
                             ->where('id', $student['user_id'])
                             ->update(['is_active' => 0]);
                    }
                    $this->db->table('users')
                         ->where(['related_id' => $id, 'role_id' => 4, 'related_type' => 'student'])
                         ->update(['is_active' => 0]);
                } elseif ($newStatus === 'aktif' && in_array($record['status'], ['lulus', 'dropout', 'nonaktif'])) {
                    // reactivate if changed back to aktif
                    if (!empty($student['user_id'])) {
                        $this->db->table('users')
                             ->where('id', $student['user_id'])
                             ->update(['is_active' => 1]);
                    }
                    $this->db->table('users')
                         ->where(['related_id' => $id, 'role_id' => 4, 'related_type' => 'student'])
                         ->update(['is_active' => 1]);
                }
            } elseif (!empty($newClassId)) {
                $this->recordModel->insert([
                    'student_id' => $id,
                    'academic_year_id' => $year['id'],
                    'class_id' => $newClassId,
                    'status' => 'aktif'
                ]);
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
        if (!$this->request->is('post')) {
            return redirect()->to('/admin/students')->with('error', 'Invalid request method.');
        }

        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'File tidak valid atau tidak ditemukan.');
        }

        $allowedExtensions = ['xlsx', 'xls'];
        if (!in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            return redirect()->back()->with('error', 'Format file tidak valid. Hanya file Excel (.xlsx, .xls) yang diperbolehkan.');
        }

        $importType = $this->request->getPost('import_type') ?: 'dapodik';

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet  = $reader->getActiveSheet();
            $rows   = $sheet->toArray();

            $parsedStudents = [];

            if ($importType === 'dapodik') {
                $parsedStudents = $this->parseDapodikRows($rows);
            } else {
                $parsedStudents = $this->parseStandardRows($rows);
            }

            if (empty($parsedStudents)) {
                return redirect()->back()->with('error', 'Tidak ada data siswa yang valid ditemukan dalam file Excel.');
            }

            // Lakukan Matching pencocokan ke database untuk menentukan status BARU vs UPDATE
            $newCount = 0;
            $updateCount = 0;

            foreach ($parsedStudents as &$s) {
                $existing = null;
                $nik   = $s['nik'] ?? '';
                $nisn  = $s['nisn'] ?? '';
                $nis   = $s['nis'] ?? '';
                $name  = $s['name'] ?? '';
                $birthDate = $s['birth_date'] ?? '';

                if (!empty($nik)) {
                    $existing = $this->studentModel->where('nik', $nik)->first();
                }
                if (!$existing && !empty($nisn)) {
                    $existing = $this->studentModel->where('nisn', $nisn)->first();
                }
                if (!$existing && !empty($nis)) {
                    $existing = $this->studentModel->where('nis', $nis)->first();
                }
                if (!$existing && !empty($name) && !empty($birthDate)) {
                    $existing = $this->studentModel
                        ->where('name', strtoupper($name))
                        ->where('birth_date', $birthDate)
                        ->first();
                }

                if ($existing) {
                    $s['is_existing'] = true;
                    $s['existing_id'] = $existing['id'];
                    $s['user_id']     = $existing['user_id'] ?? null;
                    $updateCount++;
                } else {
                    $s['is_existing'] = false;
                    $s['existing_id'] = null;
                    $s['user_id']     = null;
                    $newCount++;
                }
            }
            unset($s);

            $data = [
                'title'        => 'Pratinjau Impor Data Siswa',
                'import_type'  => $importType,
                'students'     => $parsedStudents,
                'new_count'    => $newCount,
                'update_count' => $updateCount,
                'classes'      => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll(),
            ];

            return view('admin/students/preview_import', $data);

        } catch (\Exception $e) {
            log_message('error', 'Import preview error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Eksekusi Konfirmasi Impor Data Siswa
     */
    public function confirmImport()
    {
        if (!$this->request->is('post')) {
            return redirect()->to('/admin/students')->with('error', 'Invalid request method.');
        }

        set_time_limit(300); // 5 menit

        $selected = $this->request->getPost('selected_students');
        $syncMode = $this->request->getPost('sync_mode') ?: 'update';
        $fallbackClassId = $this->request->getPost('class_id');
        $studentsJson = $this->request->getPost('students_json');

        $studentsArray = $studentsJson ? json_decode($studentsJson, true) : null;

        if (empty($selected) || empty($studentsArray)) {
            return redirect()->to('/admin/students')->with('error', 'Tidak ada data siswa yang dipilih untuk diimpor.');
        }

        // Peta Kelas untuk Plotting Rombel
        $allClasses = $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll();
        $classMap = [];
        foreach ($allClasses as $c) {
            $cleanName = strtolower(trim($c['name']));
            $classMap[$cleanName] = $c['id'];
            $withoutKelas = str_replace('kelas ', '', $cleanName);
            if ($withoutKelas !== $cleanName) {
                $classMap[$withoutKelas] = $c['id'];
            }
        }

        $activeYear = $this->yearModel->where('is_active', 1)->first();
        helper('security');

        $insertedCount = 0;
        $updatedCount  = 0;
        $skippedCount  = 0;
        $successLogs   = [];

        foreach ($selected as $idx) {
            if (!isset($studentsArray[$idx])) continue;

            $d = $studentsArray[$idx];
            $isExisting = !empty($d['is_existing']);
            $existingId = $d['existing_id'] ?? null;
            $name       = strtoupper(trim($d['name'] ?? '-'));
            $nisn       = trim($d['nisn'] ?? '');
            $nis        = trim($d['nis'] ?? '');
            $nik        = trim($d['nik'] ?? '');
            $rombel     = trim($d['rombel'] ?? '');

            // Tangani mode sinkronisasi untuk data yang sudah ada
            if ($isExisting) {
                if ($syncMode === 'skip') {
                    $skippedCount++;
                    continue;
                }
            }

            // Persiapkan array atribut siswa
            $studentData = [
                'nisn'               => $nisn,
                'nis'                => $nis,
                'name'               => $name,
                'nik'                => $nik,
                'gender'             => $d['gender'] ?? 'L',
                'birth_place'        => $d['birth_place'] ?? '',
                'birth_date'         => $d['birth_date'] ?? '',
                'religion'           => $d['religion'] ?? 'Islam',
                'address'            => $d['address'] ?? '',
                'residence_type'     => $d['residence_type'] ?? '',
                'transportation'     => $d['transportation'] ?? '',
                'distance'           => $d['distance'] ?? '',
                'latitude'           => $d['latitude'] ?? '',
                'longitude'          => $d['longitude'] ?? '',
                'special_needs'      => $d['special_needs'] ?? '',
                'child_order'        => $d['child_order'] ?? '',
                'father_name'        => $d['father_name'] ?? '',
                'father_nik'         => $d['father_nik'] ?? '',
                'father_birth_year'  => $d['father_birth_year'] ?? '',
                'father_education'   => $d['father_education'] ?? '',
                'father_job'         => $d['father_job'] ?? '',
                'father_income'      => $d['father_income'] ?? '',
                'mother_name'        => $d['mother_name'] ?? '',
                'mother_nik'         => $d['mother_nik'] ?? '',
                'mother_birth_year'  => $d['mother_birth_year'] ?? '',
                'mother_education'   => $d['mother_education'] ?? '',
                'mother_job'         => $d['mother_job'] ?? '',
                'mother_income'      => $d['mother_income'] ?? '',
                'guardian_name'      => $d['guardian_name'] ?? '',
                'guardian_education' => $d['guardian_education'] ?? '',
                'guardian_job'       => $d['guardian_job'] ?? '',
                'guardian_income'    => $d['guardian_income'] ?? '',
            ];

            // ── PENANGANAN AKUN USER SISWA ──────────────────────────────────
            $userId = $d['user_id'] ?? null;

            if ($isExisting && $existingId) {
                $existingRecord = $this->studentModel->find($existingId);
                if ($existingRecord && !empty($existingRecord['user_id'])) {
                    $userId = $existingRecord['user_id'];
                }
            }

            if ($userId) {
                // Perbarui fullname di tabel users
                $this->userModel->update($userId, ['fullname' => $name]);
            } else {
                // Buat username unik
                $usernameBase = !empty($nis) ? $nis : (!empty($nisn) ? $nisn : (!empty($nik) ? $nik : 'siswa_' . rand(1000, 9999)));
                $username     = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $usernameBase));
                $email        = $username . '@siswa.local';

                $checkUser = $this->userModel->where('username', $username)->first();

                if ($checkUser) {
                    if ($isExisting && (empty($checkUser['related_id']) || $checkUser['related_id'] == $existingId)) {
                        $userId = $checkUser['id'];
                        $this->userModel->update($userId, ['fullname' => $name]);
                    } else {
                        $username = $username . '_' . substr(md5($name . rand(100, 999)), 0, 4);
                        $email    = $username . '@siswa.local';
                        $defaultPassword = generate_default_password('siswa', $usernameBase);

                        $this->userModel->insert([
                            'username'             => $username,
                            'password'             => password_hash($defaultPassword, PASSWORD_BCRYPT),
                            'fullname'             => $name,
                            'email'                => $email,
                            'role_id'              => 5,
                            'related_type'         => 'student',
                            'must_change_password' => 1,
                        ]);
                        $userId = $this->userModel->getInsertID();
                    }
                } else {
                    $defaultPassword = generate_default_password('siswa', $usernameBase);

                    $this->userModel->insert([
                        'username'             => $username,
                        'password'             => password_hash($defaultPassword, PASSWORD_BCRYPT),
                        'fullname'             => $name,
                        'email'                => $email,
                        'role_id'              => 5,
                        'related_type'         => 'student',
                        'must_change_password' => 1,
                    ]);
                    $userId = $this->userModel->getInsertID();
                }
            }

            if ($userId) {
                $studentData['user_id'] = $userId;
            }

            // ── EKSEKUSI DATABASE SISWA ──────────────────────────────────────
            $studentId = null;

            if ($isExisting && $existingId) {
                $studentId = $existingId;
                if ($syncMode === 'merge') {
                    // Mode Merge: Hanya isi atribut yang di DB masih kosong
                    $existingCurrent = $this->studentModel->find($studentId);
                    $mergedData = [];
                    foreach ($studentData as $key => $val) {
                        if (empty($existingCurrent[$key]) && !empty($val)) {
                            $mergedData[$key] = $val;
                        }
                    }
                    if (!empty($mergedData)) {
                        $this->studentModel->update($studentId, $mergedData);
                    }
                } else {
                    // Mode Update: Timpa data dengan yang baru dari Excel
                    $this->studentModel->update($studentId, $studentData);
                }

                if ($userId) {
                    $this->userModel->update($userId, ['related_id' => $studentId]);
                }
                $updatedCount++;
                $successLogs[] = "✏️ Diperbarui: $name (NISN: " . ($nisn ?: '-') . ")";
            } else {
                $this->studentModel->insert($studentData);
                $studentId = $this->studentModel->getInsertID();
                if ($userId && $studentId) {
                    $this->userModel->update($userId, ['related_id' => $studentId]);
                }
                $insertedCount++;
                $successLogs[] = "➕ Ditambahkan: $name (NISN: " . ($nisn ?: '-') . ")";
            }

            // ── SINKRONISASI AKUN ORANG TUA (IDEMPOTEN) ───────────────────────
            $usernameOrtuKey = !empty($nis) ? $nis : (!empty($nisn) ? $nisn : $studentId);
            $this->syncParentAccount($studentId, $usernameOrtuKey, $name);

            // ── PLOTTING KELAS / ROMBEL ──────────────────────────────────────
            $targetClassId = null;
            if (!empty($rombel)) {
                $cleanRombel = strtolower(trim($rombel));
                $targetClassId = $classMap[$cleanRombel] ?? ($classMap[str_replace('kelas ', '', $cleanRombel)] ?? null);
            }
            if (!$targetClassId && !empty($fallbackClassId)) {
                $targetClassId = $fallbackClassId;
            }

            if ($targetClassId && $activeYear) {
                $existingRecord = $this->recordModel
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $activeYear['id'])
                    ->first();

                if ($existingRecord) {
                    $this->recordModel->update($existingRecord['id'], [
                        'class_id' => $targetClassId,
                        'status'   => 'aktif'
                    ]);
                } else {
                    $this->recordModel->insert([
                        'student_id'       => $studentId,
                        'class_id'         => $targetClassId,
                        'academic_year_id' => $activeYear['id'],
                        'status'           => 'aktif'
                    ]);
                }
            }
        }

        $summaryMsg = "Impor Berhasil: $insertedCount siswa baru ditambahkan, $updatedCount data siswa diperbarui";
        if ($skippedCount > 0) {
            $summaryMsg .= ", $skippedCount siswa dilewati.";
        } else {
            $summaryMsg .= ".";
        }

        return redirect()->to('/admin/students')
            ->with('success', $summaryMsg)
            ->with('import_success', array_slice($successLogs, 0, 50));
    }

    /**
     * Parsing File Tarikan Dapodik (.xlsx)
     */
    private function parseDapodikRows(array $rows): array
    {
        $headerRowIdx = -1;
        foreach ($rows as $idx => $row) {
            $rowStr = implode(' ', array_map('strval', $row));
            if (stripos($rowStr, 'Nama') !== false && (stripos($rowStr, 'NISN') !== false || stripos($rowStr, 'NIPD') !== false)) {
                $headerRowIdx = $idx;
                break;
            }
        }

        if ($headerRowIdx === -1) {
            return [];
        }

        $validReligions = ['Islam', 'Kristen', 'Katholik', 'Hindu', 'Budha', 'Khonghucu'];
        $students = [];

        for ($i = $headerRowIdx + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $name = trim((string)($row[1] ?? ''));

            if (empty($name) || strtolower($name) === 'nama' || stripos($name, 'tahun lahir') !== false) {
                continue;
            }

            $nis        = trim((string)($row[2] ?? ''));
            $gender     = strtoupper(trim((string)($row[3] ?? 'L')));
            $nisn       = trim((string)($row[4] ?? ''));
            $birthPlace = trim((string)($row[5] ?? ''));
            $birthDate  = trim((string)($row[6] ?? ''));
            $nik        = trim((string)($row[7] ?? ''));
            $religion   = trim((string)($row[8] ?? 'Islam'));
            $address    = trim((string)($row[9] ?? ''));
            $residence  = trim((string)($row[16] ?? ''));
            $transport  = trim((string)($row[17] ?? ''));
            $rombel     = trim((string)($row[42] ?? ''));
            $specialNeeds = trim((string)($row[55] ?? ''));
            $childOrder = trim((string)($row[57] ?? ''));
            $latitude   = trim((string)($row[58] ?? ''));
            $longitude  = trim((string)($row[59] ?? ''));
            $distance   = trim((string)($row[65] ?? ''));

            if (!in_array($gender, ['L', 'P'])) {
                $gender = 'L';
            }

            if (!in_array($religion, $validReligions)) {
                $matchedReligion = false;
                foreach ($validReligions as $vr) {
                    if (strtolower($religion) === strtolower($vr)) {
                        $religion = $vr;
                        $matchedReligion = true;
                        break;
                    }
                }
                if (!$matchedReligion) {
                    $religion = 'Islam';
                }
            }

            $students[] = [
                'nisn'               => $nisn,
                'nis'                => $nis,
                'name'               => strtoupper($name),
                'nik'                => $nik,
                'gender'             => $gender,
                'birth_place'        => $birthPlace,
                'birth_date'         => $birthDate,
                'religion'           => $religion,
                'address'            => $address,
                'residence_type'     => $residence,
                'transportation'     => $transport,
                'distance'           => $distance,
                'latitude'           => $latitude,
                'longitude'          => $longitude,
                'special_needs'      => $specialNeeds,
                'child_order'        => $childOrder,
                'rombel'             => $rombel,
                'father_name'        => trim((string)($row[24] ?? '')),
                'father_birth_year'  => trim((string)($row[25] ?? '')),
                'father_education'   => trim((string)($row[26] ?? '')),
                'father_job'         => trim((string)($row[27] ?? '')),
                'father_income'      => trim((string)($row[28] ?? '')),
                'father_nik'         => trim((string)($row[29] ?? '')),
                'mother_name'        => trim((string)($row[30] ?? '')),
                'mother_birth_year'  => trim((string)($row[31] ?? '')),
                'mother_education'   => trim((string)($row[32] ?? '')),
                'mother_job'         => trim((string)($row[33] ?? '')),
                'mother_income'      => trim((string)($row[34] ?? '')),
                'mother_nik'         => trim((string)($row[35] ?? '')),
                'guardian_name'      => trim((string)($row[36] ?? '')),
                'guardian_education' => trim((string)($row[38] ?? '')),
                'guardian_job'       => trim((string)($row[39] ?? '')),
                'guardian_income'    => trim((string)($row[40] ?? '')),
            ];
        }

        return $students;
    }

    /**
     * Parsing File Template SIAKAD Manual
     */
    private function parseStandardRows(array $rows): array
    {
        $validReligions = ['Islam', 'Kristen', 'Katholik', 'Hindu', 'Budha', 'Khonghucu'];
        $students = [];

        foreach ($rows as $i => $row) {
            if ($i == 0) continue; // skip header

            $nisn       = trim($row[0] ?? '');
            $nis        = trim($row[1] ?? '');
            $name       = trim($row[2] ?? '');
            $gender     = strtoupper(trim($row[3] ?? 'L'));
            $birthPlace = trim($row[4] ?? '');
            $birthDate  = trim($row[5] ?? '');
            $religion   = trim($row[6] ?? 'Islam');

            if (empty($name)) continue;

            if (!in_array($gender, ['L', 'P'])) {
                $gender = 'L';
            }

            if (!in_array($religion, $validReligions)) {
                $matchedReligion = false;
                foreach ($validReligions as $vr) {
                    if (strtolower($religion) === strtolower($vr)) {
                        $religion = $vr;
                        $matchedReligion = true;
                        break;
                    }
                }
                if (!$matchedReligion) {
                    $religion = 'Islam';
                }
            }

            $students[] = [
                'nisn'        => $nisn,
                'nis'         => $nis,
                'name'        => strtoupper($name),
                'nik'         => '',
                'gender'      => $gender,
                'birth_place' => $birthPlace,
                'birth_date'  => $birthDate,
                'religion'    => $religion,
                'rombel'      => '',
            ];
        }

        return $students;
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ── Sheet 1: Template (hanya header, siap diisi) ─────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = [
            'A1' => 'NISN',
            'B1' => 'NIS',
            'C1' => 'Nama Lengkap',
            'D1' => 'Jenis Kelamin (L/P)',
            'E1' => 'Tempat Lahir',
            'F1' => 'Tanggal Lahir (YYYY-MM-DD)',
            'G1' => 'Agama',
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->setColor(
            (new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE))
        );
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1976D2');
        $sheet->getStyle('A1:G1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Dropdown validasi Agama di kolom G (baris 2-1000)
        $religions = 'Islam,Kristen,Katholik,Hindu,Budha,Khonghucu';
        $agamaValidation = $sheet->getCell('G2')->getDataValidation();
        $agamaValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $agamaValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $agamaValidation->setAllowBlank(true);
        $agamaValidation->setShowDropDown(true);
        $agamaValidation->setFormula1('"' . $religions . '"');
        $agamaValidation->setShowErrorMessage(true);
        $agamaValidation->setErrorTitle('Agama tidak valid');
        $agamaValidation->setError('Pilih: ' . $religions);
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('G' . $row)->setDataValidation(clone $agamaValidation);
        }

        // Dropdown validasi Jenis Kelamin di kolom D (baris 2-1000)
        $genderValidation = $sheet->getCell('D2')->getDataValidation();
        $genderValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $genderValidation->setAllowBlank(true);
        $genderValidation->setShowDropDown(true);
        $genderValidation->setFormula1('"L,P"');
        $genderValidation->setShowErrorMessage(true);
        $genderValidation->setErrorTitle('Jenis Kelamin tidak valid');
        $genderValidation->setError('Pilih L (Laki-laki) atau P (Perempuan)');
        for ($row = 2; $row <= 1000; $row++) {
            $sheet->getCell('D' . $row)->setDataValidation(clone $genderValidation);
        }

        // Auto width
        foreach (['A','B','C','D','E','F','G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Contoh Data (referensi, JANGAN copy ke sheet Template) ──
        $exampleSheet = $spreadsheet->createSheet();
        $exampleSheet->setTitle('Contoh Data');

        // Header contoh
        $exHeaders = ['NISN','NIS','Nama Lengkap','Jenis Kelamin (L/P)','Tempat Lahir','Tanggal Lahir (YYYY-MM-DD)','Agama'];
        foreach ($exHeaders as $col => $val) {
            $exampleSheet->setCellValue(['A','B','C','D','E','F','G'][$col] . '1', $val);
        }
        $exampleSheet->getStyle('A1:G1')->getFont()->setBold(true);
        $exampleSheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFC107');

        // Baris contoh
        $examples = [
            ['0012345678', '2024001', 'AHMAD FAUZI',  'L', 'Jakarta',   '2010-01-15', 'Islam'],
            ['0023456789', '2024002', 'SITI AISYAH',  'P', 'Bandung',   '2010-03-20', 'Islam'],
            ['0034567890', '2024003', 'BUDI SANTOSO', 'L', 'Surabaya',  '2010-07-10', 'Kristen'],
        ];
        $colLetters = ['A','B','C','D','E','F','G'];
        foreach ($examples as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $exampleSheet->setCellValue($colLetters[$colIdx] . ($rowIdx + 2), $val);
            }
        }
        $exampleSheet->getStyle('A2:G4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFF9C4');
        $exampleSheet->setCellValue('A6', '⚠️ Catatan: Sheet ini hanya contoh referensi.');
        $exampleSheet->setCellValue('A7', 'Masukkan data di sheet "Template Import", BUKAN di sheet ini.');
        $exampleSheet->getStyle('A6:A7')->getFont()->setBold(true)->getColor()->setARGB('FFD32F2F');
        foreach ($colLetters as $col) {
            $exampleSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 3: Petunjuk ────────────────────────────────────────────
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE IMPOR SISWA');
        $guide->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $guideData = [
            ['Kolom', 'Nama Field',       'Wajib', 'Format / Keterangan'],
            ['A',     'NISN',             'Ya *',  '10 digit angka, unik nasional'],
            ['B',     'NIS',              'Ya *',  'Nomor induk lokal sekolah'],
            ['C',     'Nama Lengkap',     'Ya',    'Nama siswa, otomatis jadi huruf kapital'],
            ['D',     'Jenis Kelamin',    'Tidak', 'L = Laki-laki | P = Perempuan (default: L)'],
            ['E',     'Tempat Lahir',     'Tidak', 'Kota/kabupaten tempat lahir'],
            ['F',     'Tanggal Lahir',    'Tidak', 'Format wajib: YYYY-MM-DD (contoh: 2010-01-15)'],
            ['G',     'Agama',            'Tidak', 'Islam / Kristen / Katholik / Hindu / Budha / Khonghucu (default: Islam)'],
        ];
        $guideColLetters = ['A','B','C','D'];
        foreach ($guideData as $idx => $rowData) {
            foreach ($rowData as $col => $val) {
                $guide->setCellValue($guideColLetters[$col] . ($idx + 3), $val);
            }
        }
        $guide->getStyle('A3:D3')->getFont()->setBold(true);
        $guide->getStyle('A3:D3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE3F2FD');
        $guide->setCellValue('A12', '* Minimal salah satu dari NISN atau NIS harus diisi.');
        $guide->setCellValue('A13', '* Akun login siswa dan akun orang tua dibuat otomatis setelah import.');
        $guide->setCellValue('A14', '* Isi data di sheet "Template Import" saja, sheet Contoh dan Petunjuk diabaikan sistem.');
        $guide->getStyle('A12:A14')->getFont()->setItalic(true)->getColor()->setARGB('FF555555');
        foreach ($guideColLetters as $col) {
            $guide->getColumnDimension($col)->setAutoSize(true);
        }

        // Aktifkan sheet pertama saat dibuka
        $spreadsheet->setActiveSheetIndex(0);

        // ── Download ─────────────────────────────────────────────────────
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
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
