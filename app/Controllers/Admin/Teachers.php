<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\TeacherEducationModel;
use App\Models\TeacherTrainingModel;
use App\Models\TeacherCareerModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Teachers extends BaseController
{
    protected $teacherModel;
    protected $userModel;
    protected $educationModel;
    protected $trainingModel;
    protected $careerModel;

    public function __construct()
    {
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();
        $this->educationModel = new TeacherEducationModel();
        $this->trainingModel = new TeacherTrainingModel();
        $this->careerModel = new TeacherCareerModel();
    }

    public function index()
    {
        // ✅ Authorization check
        require_permission('teachers.view');
        
        $data['title'] = 'Manajemen Guru';
        $data['teachers'] = $this->teacherModel
            ->select('teachers.*, users.username, users.email as user_email')
            ->join('users', 'users.id = teachers.user_id', 'left')
            ->findAll();

        return view('admin/teachers/index', $data);
    }

    public function create()
    {
        // ✅ Authorization check
        require_permission('teachers.create');
        
        return view('admin/teachers/create', ['title' => 'Tambah Guru']);
    }

    public function store()
    {
        // ✅ Authorization check
        require_permission('teachers.create');
        
        helper('security'); // Load security helper
        
        $post = $this->request->getPost();

        $username = !empty($post['nip'])
            ? strtolower($post['nip'])
            : strtolower(str_replace(' ', '', $post['name']));

        $email = !empty($post['email'])
            ? $post['email']
            : $username . '@guru.local';

        // cek email unik
        $existingUser = $this->userModel->where('email', $email)->first();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'Email sudah terdaftar sebagai user.');
        }

        // Generate password dengan pattern: guru[NIP]
        $identifier = !empty($post['nip']) ? $post['nip'] : $post['name'];
        $defaultPassword = generate_default_password('guru', $identifier);

        // buat akun user
        $this->userModel->insert([
            'username' => $username,
            'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
            'fullname' => $post['name'],
            'email' => $email,
            'role_id' => 3,
            'related_id' => null,
            'related_type' => 'teacher',
            'must_change_password' => 1, // Wajib ganti password
        ]);
        $userId = $this->userModel->getInsertID();

        // ✅ Handle Photo Upload with validation
        $photoName = null;
        $photoFile = $this->request->getFile('photo');
        if ($photoFile && $photoFile->isValid() && !$photoFile->hasMoved()) {
            // Validate MIME type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($photoFile->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Tipe file foto tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
            }
            
            // Validate file size (max 2MB)
            if ($photoFile->getSize() > 2 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'Ukuran file foto terlalu besar. Maksimal 2MB.');
            }
            
            // Validate file extension
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($photoFile->getExtension()), $allowedExtensions)) {
                return redirect()->back()->withInput()->with('error', 'Ekstensi file foto tidak valid.');
            }
            
            $photoName = $photoFile->getRandomName();
            try {
                $photoFile->move(UPLOAD_PATH . 'teachers', $photoName);
            } catch (\Exception $e) {
                log_message('error', 'Failed to upload teacher photo: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload foto. Silakan coba lagi.');
            }
        }

        // simpan guru
        $this->teacherModel->insert([
            'name' => $post['name'],
            'nip' => $post['nip'] ?? null,
            'nuptk' => $post['nuptk'] ?? null,
            'nik' => $post['nik'] ?? null,
            'gender' => $post['gender'],
            'birth_place' => $post['birth_place'] ?? null,
            'birth_date' => $post['birth_date'] ?? null,
            'mother_name' => $post['mother_name'] ?? null,
            'religion' => $post['religion'] ?? null,
            'marital_status' => $post['marital_status'] ?? null,
            'phone' => $post['phone'] ?? null,
            'email' => $email,
            'address' => $post['address'] ?? '',
            'rt_rw' => $post['rt_rw'] ?? null,
            'village' => $post['village'] ?? null,
            'district' => $post['district'] ?? null,
            'city' => $post['city'] ?? null,
            'province' => $post['province'] ?? null,
            'postal_code' => $post['postal_code'] ?? null,
            'employment_status' => $post['employment_status'] ?? null,
            'appointing_agency' => $post['appointing_agency'] ?? null,
            'appointment_sk' => $post['appointment_sk'] ?? null,
            'appointment_tmt' => $post['appointment_tmt'] ?: null,
            'functional_position' => $post['functional_position'] ?? null,
            'rank_grade' => $post['rank_grade'] ?? null,
            'certification_number' => $post['certification_number'] ?? null,
            'certification_field' => $post['certification_field'] ?? null,
            'certification_year' => $post['certification_year'] ?: null,
            'photo' => $photoName,
            'user_id' => $userId ?: null,
        ]);

        $teacherId = $this->teacherModel->getInsertID();

        // hanya update user jika teacherId valid (>0)
        if ($teacherId && $teacherId > 0 && $userId && $userId > 0) {
            $this->userModel->update($userId, ['related_id' => $teacherId]);
        } else {
            log_message('error', sprintf(
                'Insert guru berhasil tapi ID kosong. userId=%s teacherId=%s',
                var_export($userId, true),
                var_export($teacherId, true)
            ));
        }

        return redirect()->to('/admin/teachers')->with('success', 'Guru berhasil ditambahkan beserta akun login.');
    }

    public function edit($id)
    {
        // ✅ Authorization check
        require_permission('teachers.update');
        
        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Data guru tidak ditemukan.');
        }

        $documentModel = new \App\Models\TeacherDocumentModel();

        return view('admin/teachers/edit', [
            'title' => 'Edit Guru',
            'teacher' => $teacher,
            'educations' => $this->educationModel->getByTeacher($id),
            'trainings' => $this->trainingModel->getByTeacher($id),
            'careers' => $this->careerModel->getByTeacher($id),
            'documents' => $documentModel->where('teacher_id', $id)->orderBy('created_at', 'DESC')->findAll(),
            'years' => (new \App\Models\AcademicYearModel())->findAll()
        ]);
    }

    public function update($id)
    {
        // ✅ Authorization check
        require_permission('teachers.update');
        
        $post = $this->request->getPost();
        $teacher = $this->teacherModel->find($id);

        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Data guru tidak ditemukan.');
        }

        $email = !empty($post['email'])
            ? $post['email']
            : strtolower($post['nip'] . '@guru.local');

        // ✅ Handle Photo Upload with validation
        $photoName = $teacher['photo'] ?? null;
        $photoFile = $this->request->getFile('photo');
        if ($photoFile && $photoFile->isValid() && !$photoFile->hasMoved()) {
            // Validate MIME type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($photoFile->getMimeType(), $allowedTypes)) {
                return redirect()->back()->withInput()->with('error', 'Tipe file foto tidak valid. Hanya JPG, JPEG, dan PNG yang diperbolehkan.');
            }
            
            // Validate file size (max 2MB)
            if ($photoFile->getSize() > 2 * 1024 * 1024) {
                return redirect()->back()->withInput()->with('error', 'Ukuran file foto terlalu besar. Maksimal 2MB.');
            }
            
            // Validate file extension
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($photoFile->getExtension()), $allowedExtensions)) {
                return redirect()->back()->withInput()->with('error', 'Ekstensi file foto tidak valid.');
            }
            
            // Delete old photo if exists
            if (!empty($teacher['photo']) && file_exists(UPLOAD_PATH . 'teachers/' . $teacher['photo'])) {
                try {
                    unlink(UPLOAD_PATH . 'teachers/' . $teacher['photo']);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to delete old teacher photo: ' . $e->getMessage());
                }
            }
            
            $photoName = $photoFile->getRandomName();
            try {
                $photoFile->move(UPLOAD_PATH . 'teachers', $photoName);
            } catch (\Exception $e) {
                log_message('error', 'Failed to upload teacher photo: ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload foto. Silakan coba lagi.');
            }
        }

        // update guru
        $this->teacherModel->update($id, [
            'name' => $post['name'] ?? null,
            'nip' => $post['nip'] ?? null,
            'nuptk' => $post['nuptk'] ?? null,
            'nik' => $post['nik'] ?? null,
            'gender' => $post['gender'] ?? null,
            'birth_place' => $post['birth_place'] ?? null,
            'birth_date' => $post['birth_date'] ?? null,
            'mother_name' => $post['mother_name'] ?? null,
            'religion' => $post['religion'] ?? null,
            'marital_status' => $post['marital_status'] ?? null,
            'phone' => $post['phone'] ?? null,
            'email' => $email,
            'address' => $post['address'] ?? '',
            'rt_rw' => $post['rt_rw'] ?? null,
            'village' => $post['village'] ?? null,
            'district' => $post['district'] ?? null,
            'city' => $post['city'] ?? null,
            'province' => $post['province'] ?? null,
            'postal_code' => $post['postal_code'] ?? null,
            'employment_status' => $post['employment_status'] ?? null,
            'appointing_agency' => $post['appointing_agency'] ?? null,
            'appointment_sk' => $post['appointment_sk'] ?? null,
            'appointment_tmt' => $post['appointment_tmt'] ?: null,
            'functional_position' => $post['functional_position'] ?? null,
            'rank_grade' => $post['rank_grade'] ?? null,
            'certification_number' => $post['certification_number'] ?? null,
            'certification_field' => $post['certification_field'] ?? null,
            'certification_year' => $post['certification_year'] ?: null,
            'photo' => $photoName,
        ]);

        // update user sinkron dengan guru
        if (!empty($teacher['user_id'])) {
            $this->userModel->update($teacher['user_id'], [
                'fullname' => $post['name'],
                'email' => $email,
            ]);
        }

        return redirect()->to('/admin/teachers')->with('success', 'Guru berhasil diperbarui.');
    }

    public function delete($id)
    {
        // ✅ Authorization check
        require_permission('teachers.delete');
        
        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Data guru tidak ditemukan.');
        }

        // hapus user terkait
        if (!empty($teacher['user_id'])) {
            $this->userModel->delete($teacher['user_id']);
        }

        // hapus guru
        $this->teacherModel->delete($id);

        return redirect()->to('/admin/teachers')->with('success', 'Guru beserta akun login berhasil dihapus.');
    }

    // --- Sub-data Management ---

    public function addEducation($teacherId)
    {
        $this->educationModel->insert([
            'teacher_id' => $teacherId,
            'level' => $this->request->getPost('level'),
            'major' => $this->request->getPost('major'),
            'institution' => $this->request->getPost('institution'),
            'graduation_year' => $this->request->getPost('graduation_year'),
        ]);

        return redirect()->back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function addTraining($teacherId)
    {
        $this->trainingModel->insert([
            'teacher_id' => $teacherId,
            'name' => $this->request->getPost('name'),
            'year' => $this->request->getPost('year'),
            'organizer' => $this->request->getPost('organizer'),
            'certificate_number' => $this->request->getPost('certificate_number'),
        ]);

        return redirect()->back()->with('success', 'Riwayat pelatihan berhasil ditambahkan.');
    }

    public function addCareer($teacherId)
    {
        $this->careerModel->insert([
            'teacher_id' => $teacherId,
            'academic_year_id' => $this->request->getPost('academic_year_id'),
            'sk_number' => $this->request->getPost('sk_number'),
            'assignment_description' => $this->request->getPost('assignment_description'),
        ]);

        return redirect()->back()->with('success', 'Riwayat karier berhasil ditambahkan.');
    }

    public function deleteSub($type, $id)
    {
        switch ($type) {
            case 'education':
                $this->educationModel->delete($id);
                break;
            case 'training':
                $this->trainingModel->delete($id);
                break;
            case 'career':
                $this->careerModel->delete($id);
                break;
        }

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'NAMA');
        $sheet->setCellValue('C1', 'EMAIL');
        $sheet->setCellValue('D1', 'JENIS KELAMIN (L/P)');

        // Column widths
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'template_import_guru.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function import()
    {
        helper('security'); // Load security helper
        
        $file = $this->request->getFile('file_excel');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $extension = $file->getClientExtension();
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus .xlsx atau .xls');
        }

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            $successCount = 0;
            $skipCount = 0;
            $errors = [];

            // Skip header (row 0)
            for ($i = 1; $i < count($sheetData); $i++) {
                $row = $sheetData[$i];
                if (empty($row[1]))
                    continue; // Skip if name is empty

                $nip = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');
                $email = trim($row[2] ?? '');
                $gender = strtoupper(trim($row[3] ?? 'L'));

                if (empty($email)) {
                    $username = !empty($nip) ? strtolower($nip) : strtolower(str_replace(' ', '', $name));
                    $email = $username . '@guru.local';
                } else {
                    $username = !empty($nip) ? strtolower($nip) : explode('@', $email)[0];
                }

                // Cek email/username unik
                $existingUser = $this->userModel->where('email', $email)->orWhere('username', $username)->first();
                if ($existingUser) {
                    $skipCount++;
                    continue;
                }

                // Generate password dengan pattern: guru[NIP] atau guru[nama]
                $identifier = !empty($nip) ? $nip : $name;
                $defaultPassword = generate_default_password('guru', $identifier);

                // 1. Buat User
                $this->userModel->insert([
                    'username' => $username,
                    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname' => $name,
                    'email' => $email,
                    'role_id' => 3,
                    'related_type' => 'teacher',
                    'must_change_password' => 1, // Wajib ganti password
                ]);
                $userId = $this->userModel->getInsertID();

                // 2. Buat Guru
                $this->teacherModel->insert([
                    'nip' => $nip ?: null,
                    'name' => $name,
                    'email' => $email,
                    'gender' => ($gender == 'P') ? 'P' : 'L',
                    'user_id' => $userId,
                ]);
                $teacherId = $this->teacherModel->getInsertID();

                // 3. Link back
                $this->userModel->update($userId, ['related_id' => $teacherId]);

                $successCount++;
            }

            $message = "Berhasil mengimpor $successCount data guru.";
            if ($skipCount > 0)
                $message .= " Melompati $skipCount data (sudah ada).";

            return redirect()->to('/admin/teachers')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Preview data dari file Dapodik sebelum di-import (AJAX/JSON)
     */
    public function importDapodikPreview()
    {
        require_permission('teachers.create');

        $file = $this->request->getFile('file_excel');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File tidak valid.']);
        }

        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Format file harus .xlsx atau .xls']);
        }

        try {
            try {
                $sheetData = $this->readXlsxAsStrings($file->getTempName());
            } catch (\Throwable $ex) {
                $spreadsheet = IOFactory::load($file->getTempName());
                $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            }

            // Validasi: pastikan ini file Dapodik
            $firstCell = trim((string) ($sheetData[0][0] ?? ''));
            $isDapodikGuru   = stripos($firstCell, 'Daftar Guru') !== false;
            $isDapodikTendik = stripos($firstCell, 'Daftar Tenaga Kependidikan') !== false;

            if (!$isDapodikGuru && !$isDapodikTendik) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'File bukan format tarikan Dapodik. Baris pertama harus berisi "Daftar Guru" atau "Daftar Tenaga Kependidikan".',
                ]);
            }

            $tipePtk     = $isDapodikGuru ? 'guru' : 'tendik';
            $sekolahNama = trim((string) ($sheetData[1][0] ?? '-'));

            // Header kolom ada di baris index 4 (baris ke-5)
            $headerRow = array_map('trim', $sheetData[4] ?? []);
            // Buat mapping: nama_kolom => index
            $colMap = [];
            foreach ($headerRow as $idx => $colName) {
                if ($colName !== '') {
                    $colMap[strtolower($colName)] = $idx;
                }
            }

            $get     = fn($row, $key) => trim((string) ($row[$colMap[strtolower($key)] ?? -1] ?? ''));
            $preview = [];
            $totalRows = 0;
            $willInsert = 0;
            $willUpdate = 0;
            $willSkip   = 0;

            for ($i = 5; $i < count($sheetData); $i++) {
                $row  = $sheetData[$i];
                $nama = $get($row, 'Nama');
                if (empty($nama)) continue;

                $totalRows++;
                $nuptk = $get($row, 'NUPTK');
                $nip   = $get($row, 'NIP');

                // Deteksi duplikat
                $existing = null;
                if (!empty($nuptk)) {
                    $existing = $this->teacherModel->where('nuptk', $nuptk)->first();
                }
                if (!$existing && !empty($nip)) {
                    $existing = $this->teacherModel->where('nip', $nip)->first();
                }

                $action = $existing ? 'UPDATE' : 'INSERT';
                if ($action === 'INSERT') $willInsert++;
                elseif ($action === 'UPDATE') $willUpdate++;

                // Ambil max 10 baris untuk preview
                if (count($preview) < 10) {
                    $preview[] = [
                        'nama'              => $nama,
                        'nuptk'             => $nuptk,
                        'nip'               => $nip,
                        'jk'                => $get($row, 'JK'),
                        'status_kepegawaian' => $get($row, 'Status Kepegawaian'),
                        'jenis_ptk'         => $get($row, 'Jenis PTK'),
                        'action'            => $action,
                    ];
                }
            }

            return $this->response->setJSON([
                'status'      => 'ok',
                'format'      => 'dapodik',
                'tipe_ptk'    => $tipePtk,
                'sekolah_nama' => $sekolahNama,
                'total_rows'  => $totalRows,
                'preview'     => $preview,
                'summary'     => [
                    'akan_insert' => $willInsert,
                    'akan_update' => $willUpdate,
                    'akan_skip'   => $willSkip,
                ],
            ]);

        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memproses file: ' . $e->getMessage()]);
        }
    }

    /**
     * Eksekusi import dari file tarikan Dapodik
     */
    public function importDapodik()
    {
        require_permission('teachers.create');
        helper('security');

        $file = $this->request->getFile('file_excel');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus .xlsx atau .xls');
        }

        $onDuplicate = $this->request->getPost('on_duplicate') ?? 'skip'; // 'skip' | 'update'

        try {
            try {
                $sheetData = $this->readXlsxAsStrings($file->getTempName());
            } catch (\Throwable $ex) {
                $spreadsheet = IOFactory::load($file->getTempName());
                $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            }

            // Validasi format Dapodik
            $firstCell = trim((string) ($sheetData[0][0] ?? ''));
            if (stripos($firstCell, 'Daftar Guru') === false && stripos($firstCell, 'Daftar Tenaga Kependidikan') === false) {
                return redirect()->back()->with('error', 'File bukan format tarikan Dapodik yang valid.');
            }

            // Header kolom ada di baris index 4
            $headerRow = array_map('trim', $sheetData[4] ?? []);
            $colMap    = [];
            foreach ($headerRow as $idx => $colName) {
                if ($colName !== '') {
                    $colMap[strtolower($colName)] = $idx;
                }
            }

            $get = fn($row, $key) => trim((string) ($row[$colMap[strtolower($key)] ?? -1] ?? ''));

            $successCount = 0;
            $updateCount  = 0;
            $skipCount    = 0;
            $errorCount   = 0;

            for ($i = 5; $i < count($sheetData); $i++) {
                $row  = $sheetData[$i];
                $nama = $get($row, 'Nama');
                if (empty($nama)) continue;

                $nuptk = $get($row, 'NUPTK') ?: null;
                $nip   = $get($row, 'NIP') ?: null;
                $jk    = strtoupper($get($row, 'JK')) === 'P' ? 'P' : 'L';

                // Gabungkan RT & RW
                $rt   = $get($row, 'RT');
                $rw   = $get($row, 'RW');
                $rtRw = ($rt && $rw) ? "$rt/$rw" : ($rt ?: ($rw ?: null));

                // HP prioritas atas Telepon
                $hp    = $get($row, 'HP');
                $telp  = $get($row, 'Telepon');
                $phone = $hp ?: ($telp ?: null);

                // Normalisasi tanggal lahir
                $tglLahirRaw = $get($row, 'Tanggal Lahir');
                $birthDate   = $this->normalizeDapodikDate($tglLahirRaw);

                // Normalisasi TMT
                $tmtRaw         = $get($row, 'TMT Pengangkatan');
                $appointmentTmt = $this->normalizeDapodikDate($tmtRaw);

                // Email & username
                $emailDapodik = $get($row, 'Email');
                $identifier   = $nip ?: $nama;
                $username     = $nip ? strtolower($nip) : strtolower(str_replace(' ', '', $nama));
                $email        = !empty($emailDapodik) ? $emailDapodik : ($username . '@guru.local');

                // Cari duplikat
                $existing = null;
                if ($nuptk) {
                    $existing = $this->teacherModel->where('nuptk', $nuptk)->first();
                }
                if (!$existing && $nip) {
                    $existing = $this->teacherModel->where('nip', $nip)->first();
                }

                $teacherData = [
                    'name'               => $nama,
                    'nip'                => $nip,
                    'nuptk'              => $nuptk,
                    'nik'                => $get($row, 'NIK') ?: null,
                    'gender'             => $jk,
                    'birth_place'        => $get($row, 'Tempat Lahir') ?: null,
                    'birth_date'         => $birthDate,
                    'mother_name'        => $get($row, 'Nama Ibu Kandung') ?: null,
                    'religion'           => $get($row, 'Agama') ?: null,
                    'marital_status'     => $get($row, 'Status Perkawinan') ?: null,
                    'phone'              => $phone,
                    'email'              => $email,
                    'address'            => $get($row, 'Alamat Jalan') ?: null,
                    'rt_rw'              => $rtRw,
                    'village'            => $get($row, 'Desa/Kelurahan') ?: null,
                    'district'           => $get($row, 'Kecamatan') ?: null,
                    'postal_code'        => $get($row, 'Kode Pos') ?: null,
                    'employment_status'  => $get($row, 'Status Kepegawaian') ?: null,
                    'jenis_ptk'          => $get($row, 'Jenis PTK') ?: null,
                    'functional_position' => $get($row, 'Tugas Tambahan') ?: null,
                    'appointment_sk'     => $get($row, 'SK Pengangkatan') ?: null,
                    'appointment_tmt'    => $appointmentTmt,
                    'appointing_agency'  => $get($row, 'Lembaga Pengangkatan') ?: null,
                    'rank_grade'         => $get($row, 'Pangkat Golongan') ?: null,
                ];

                if ($existing) {
                    // Data duplikat — putuskan sesuai pilihan on_duplicate
                    if ($onDuplicate === 'update') {
                        $this->teacherModel->update($existing['id'], $teacherData);
                        // Sinkron nama, email, dan username di tabel users jika ada
                        if (!empty($existing['user_id'])) {
                            $usernameConflict = $this->userModel
                                ->where('username', $username)
                                ->where('id !=', $existing['user_id'])
                                ->first();

                            $userDataToUpdate = [
                                'fullname' => $nama,
                                'email'    => $email,
                            ];

                            // Update username jika tidak konflik dengan user lain
                            if (!$usernameConflict) {
                                $userDataToUpdate['username'] = $username;
                            }

                            $this->userModel->update($existing['user_id'], $userDataToUpdate);
                        }
                        $updateCount++;
                    } else {
                        $skipCount++;
                    }
                    continue;
                }

                // Cek keunikan email & username untuk user baru
                $existingUser = $this->userModel->where('email', $email)->orWhere('username', $username)->first();
                if ($existingUser) {
                    // Fallback: gunakan nuptk sebagai username jika tersedia
                    $username = $nuptk ? strtolower($nuptk) : ($username . '_' . time());
                    $email    = $username . '@guru.local';
                    $teacherData['email'] = $email;

                    // Cek lagi
                    $existingUser2 = $this->userModel->where('email', $email)->orWhere('username', $username)->first();
                    if ($existingUser2) {
                        $errorCount++;
                        continue;
                    }
                }

                $defaultPassword = generate_default_password('guru', $identifier);

                $this->userModel->insert([
                    'username'            => $username,
                    'password'            => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname'            => $nama,
                    'email'               => $email,
                    'role_id'             => 3,
                    'related_type'        => 'teacher',
                    'must_change_password' => 1,
                ]);
                $userId = $this->userModel->getInsertID();

                $teacherData['user_id'] = $userId;

                $this->teacherModel->insert($teacherData);
                $teacherId = $this->teacherModel->getInsertID();

                if ($teacherId && $userId) {
                    $this->userModel->update($userId, ['related_id' => $teacherId]);
                }

                $successCount++;
            }

            $message = "Import Dapodik selesai: $successCount data baru ditambahkan";
            if ($updateCount > 0) $message .= ", $updateCount diperbarui";
            if ($skipCount > 0)   $message .= ", $skipCount dilewati (duplikat)";
            if ($errorCount > 0)  $message .= ", $errorCount gagal (konflik akun)";
            $message .= '.';

            return redirect()->to('/admin/teachers')->with('success', $message);

        } catch (\Throwable $e) {
            log_message('error', 'importDapodik error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Normalisasi format tanggal dari Dapodik ke Y-m-d
     * Mendukung: "YYYY-MM-DD", "DD-MM-YYYY", "DD/MM/YYYY"
     */
    private function normalizeDapodikDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (empty($raw)) return null;

        // Sudah dalam format Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        // Format DD-MM-YYYY atau DD/MM/YYYY
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $raw, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // Coba parse generik
        try {
            $ts = strtotime($raw);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        } catch (\Exception $e) {
            // abaikan
        }
        return null;
    }

    /**
     * Membaca file XLSX secara manual menggunakan ZipArchive & SimpleXML
     * untuk menghindari precision loss/floating-point conversion pada angka panjang (NIP, NUPTK, NIK)
     */
    private function readXlsxAsStrings(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membuka file XLSX.');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsEntry = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsEntry !== false) {
            // Strip default namespace to simplify parsing
            $sharedStringsEntry = preg_replace('/xmlns="[^"]+"/', '', $sharedStringsEntry);
            $xml = simplexml_load_string($sharedStringsEntry);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            if (isset($r->t)) {
                                $text .= (string)$r->t;
                            }
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // 2. Read sheet1
        $sheetEntry = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetEntry === false) {
            $zip->close();
            throw new \Exception('Gagal membaca data sheet.');
        }

        // Strip default namespace
        $sheetEntry = preg_replace('/xmlns="[^"]+"/', '', $sheetEntry);
        $xml = simplexml_load_string($sheetEntry);
        if (!$xml) {
            $zip->close();
            throw new \Exception('Format XML sheet tidak valid.');
        }
        
        $rows = [];
        if (isset($xml->sheetData) && isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $rowXml) {
                $rowIndex = (int)$rowXml['r'] - 1; // 0-based index
                $rowData = [];
                
                if (isset($rowXml->c)) {
                    foreach ($rowXml->c as $c) {
                        $r = (string)$c['r']; // e.g., "A1"
                        $t = (string)$c['t']; // e.g., "s" for shared string
                        
                        // Get column string reference (A, B, C...)
                        preg_match('/^([A-Z]+)/', $r, $matches);
                        if (!isset($matches[1])) continue;
                        $colStr = $matches[1];
                        
                        // Convert column letters to 0-based index
                        $colIndex = 0;
                        $len = strlen($colStr);
                        for ($j = 0; $j < $len; $j++) {
                            $colIndex = $colIndex * 26 + (ord($colStr[$j]) - ord('A') + 1);
                        }
                        $colIndex--; // 0-based index

                        $v = isset($c->v) ? (string)$c->v : '';
                        if ($t === 's' && $v !== '') {
                            $v = $sharedStrings[(int)$v] ?? '';
                        }
                        
                        $rowData[$colIndex] = $v;
                    }
                }
                
                if (!empty($rowData)) {
                    $maxCol = max(array_keys($rowData));
                    for ($k = 0; $k <= $maxCol; $k++) {
                        if (!isset($rowData[$k])) {
                            $rowData[$k] = '';
                        }
                    }
                    ksort($rowData);
                    $rows[$rowIndex] = $rowData;
                }
            }
        }
        $zip->close();

        if (!empty($rows)) {
            $maxRow = max(array_keys($rows));
            for ($k = 0; $k <= $maxRow; $k++) {
                if (!isset($rows[$k])) {
                    $rows[$k] = [];
                }
            }
            ksort($rows);
        }

        return $rows;
    }

    /**
     * Upload dokumen guru
     */
    public function uploadDocument($teacherId)
    {
        require_permission('teachers.update');
        
        $teacher = $this->teacherModel->find($teacherId);
        if (!$teacher) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan.');
        }

        $file = $this->request->getFile('document');
        $title = $this->request->getPost('title');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        // Validate file type (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG)
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/jpg'
        ];

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return redirect()->back()->with('error', 'Tipe file tidak diizinkan. Hanya PDF, DOC, DOCX, XLS, XLSX, JPG, PNG yang diperbolehkan.');
        }

        // Validate file size (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Ukuran file terlalu besar. Maksimal 5MB.');
        }

        // Create upload directory if not exists
        $uploadPath = FCPATH . 'uploads/teacher_docs/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filename = $file->getRandomName();
        try {
            $file->move($uploadPath, $filename);
        } catch (\Exception $e) {
            log_message('error', 'Failed to upload teacher document: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengupload dokumen. Silakan coba lagi.');
        }

        // Save to database
        $documentModel = new \App\Models\TeacherDocumentModel();
        $documentModel->insert([
            'teacher_id' => $teacherId,
            'title' => $title,
            'filename' => $filename,
            'original_name' => $file->getClientName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil diupload.');
    }

    /**
     * Delete dokumen guru
     */
    public function deleteDocument($documentId)
    {
        require_permission('teachers.update');
        
        $documentModel = new \App\Models\TeacherDocumentModel();
        $document = $documentModel->find($documentId);

        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Delete file from disk
        $filePath = FCPATH . 'uploads/teacher_docs/' . $document['filename'];
        if (file_exists($filePath)) {
            try {
                unlink($filePath);
            } catch (\Exception $e) {
                log_message('error', 'Failed to delete teacher document file: ' . $e->getMessage());
            }
        }

        // Delete from database
        $documentModel->delete($documentId);

        return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * Download dokumen guru
     */
    public function downloadDocument($documentId)
    {
        require_permission('teachers.read');
        
        $documentModel = new \App\Models\TeacherDocumentModel();
        $document = $documentModel->find($documentId);

        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/teacher_docs/' . $document['filename'];
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        return $this->response->download($filePath, null)->setFileName($document['original_name']);
    }

    /**
     * View/serve dokumen guru untuk preview
     */
    public function viewDocument($documentId)
    {
        // Check if user is logged in and has appropriate role
        $session = session();
        $user = $session->get('user');
        
        if (!$user || !in_array($user['role_id'], [1, 2, 7])) { // Admin, Kepsek, Staf
            return $this->response->setStatusCode(403)->setBody('Akses ditolak.');
        }
        
        $documentModel = new \App\Models\TeacherDocumentModel();
        $document = $documentModel->find($documentId);

        if (!$document) {
            return $this->response->setStatusCode(404)->setBody('Dokumen tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/teacher_docs/' . $document['filename'];
        if (!file_exists($filePath)) {
            return $this->response->setStatusCode(404)->setBody('File tidak ditemukan di server: ' . $filePath);
        }

        // Set appropriate headers for inline viewing
        $this->response->setHeader('Content-Type', $document['file_type']);
        $this->response->setHeader('Content-Disposition', 'inline; filename="' . $document['original_name'] . '"');
        $this->response->setHeader('Content-Length', filesize($filePath));
        
        // Read and output file
        return $this->response->setBody(file_get_contents($filePath));
    }


}
