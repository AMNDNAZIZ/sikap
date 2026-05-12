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
