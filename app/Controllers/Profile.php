<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;
    protected $teacherModel;
    protected $educationModel;
    protected $trainingModel;
    protected $careerModel;
    protected $documentModel;

    public function __construct()
    {
        $this->userModel     = new \App\Models\UserModel();
        $this->teacherModel  = new \App\Models\TeacherModel();
        $this->educationModel = new \App\Models\TeacherEducationModel();
        $this->trainingModel = new \App\Models\TeacherTrainingModel();
        $this->careerModel   = new \App\Models\TeacherCareerModel();
        $this->documentModel = new \App\Models\TeacherDocumentModel();
    }

    public function index()
    {
        $userSession = session()->get('user');
        $user = $this->userModel->find($userSession['id']);

        $data = [
            'title' => 'Profil Saya',
            'user' => $user
        ];

        if ($user['related_type'] === 'teacher' && !empty($user['related_id'])) {
            $teacherId = $user['related_id'];
            $data['teacher'] = $this->teacherModel->find($teacherId);
            $data['educations'] = $this->educationModel->getByTeacher($teacherId);
            $data['trainings'] = $this->trainingModel->getByTeacher($teacherId);
            $data['careers'] = $this->careerModel->getByTeacher($teacherId);
            $data['years'] = (new \App\Models\AcademicYearModel())->findAll();
            $data['documents'] = $this->documentModel->getByTeacher($teacherId);

            return view('profile/teacher_edit', $data);
        }

        return view('profile/index', $data);
    }

    public function changePassword()
    {
        $user = session()->get('user');
        
        $data = [
            'title' => 'Ganti Password',
            'user' => $this->userModel->find($user['id'])
        ];

        return view('profile/change_password', $data);
    }

    public function updatePassword()
    {
        $user = session()->get('user');
        $id = $user['id'];

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        // Get user data
        $userData = $this->userModel->find($id);

        // Verify current password
        if (!password_verify($currentPassword, $userData['password'])) {
            return redirect()->back()->with('error', 'Password lama tidak sesuai.');
        }

        // Validate new password
        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok.');
        }

        // Validate password strength
        if (strlen($newPassword) < 6) {
            return redirect()->back()->with('error', 'Password minimal 6 karakter.');
        }

        // Update password
        $this->userModel->update($id, [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT),
            'must_change_password' => 0,
            'password_changed_at' => date('Y-m-d H:i:s')
        ]);

        // Update session
        $userSession = session()->get('user');
        $userSession['must_change_password'] = 0;
        session()->set('user', $userSession);

        return redirect()->to('/dashboard')->with('success', 'Password berhasil diperbarui.');
    }
    public function updateTeacher()
    {
        $userSession = session()->get('user');
        $teacherId = $userSession['related_id'];
        $post = $this->request->getPost();

        $teacher = $this->teacherModel->find($teacherId);
        if (!$teacher)
            return redirect()->back()->with('error', 'Guru tidak ditemukan.');

        // Handle Photo Upload
        $photoName = $teacher['photo'];
        $photoFile = $this->request->getFile('photo');
        if ($photoFile && $photoFile->isValid() && !$photoFile->hasMoved()) {
            if (!empty($teacher['photo']) && file_exists(UPLOAD_PATH . 'teachers/' . $teacher['photo'])) {
                unlink(UPLOAD_PATH . 'teachers/' . $teacher['photo']);
            }
            $photoName = $photoFile->getRandomName();
            $photoFile->move(UPLOAD_PATH . 'teachers', $photoName);
        }

        // Update Teacher Data (Self-Management)
        $this->teacherModel->update($teacherId, [
            'name' => $post['name'],
            'nik' => $post['nik'] ?? null,
            'birth_place' => $post['birth_place'] ?? null,
            'birth_date' => $post['birth_date'] ?? null,
            'gender' => $post['gender'],
            'religion' => $post['religion'] ?? null,
            'mother_name' => $post['mother_name'] ?? null,
            'marital_status' => $post['marital_status'] ?? null,
            'address' => $post['address'] ?? '',
            'rt_rw' => $post['rt_rw'] ?? null,
            'village' => $post['village'] ?? null,
            'district' => $post['district'] ?? null,
            'city' => $post['city'] ?? null,
            'province' => $post['province'] ?? null,
            'postal_code' => $post['postal_code'] ?? null,
            'phone' => $post['phone'] ?? null,
            'photo' => $photoName,
        ]);

        // Handle Password Update if provided
        if (!empty($post['new_password'])) {
            if ($post['new_password'] === $post['confirm_password']) {
                $this->userModel->update($userSession['id'], [
                    'password' => password_hash($post['new_password'], PASSWORD_BCRYPT)
                ]);
            } else {
                return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok.');
            }
        }

        return redirect()->to('/profile')->with('success', 'Data profil berhasil diperbarui.');
    }

    // --- Sub-data Management for Teachers ---

    public function addEducation()
    {
        $userSession = session()->get('user');
        $teacherId = $userSession['related_id'];

        $this->educationModel->insert([
            'teacher_id' => $teacherId,
            'level' => $this->request->getPost('level'),
            'major' => $this->request->getPost('major'),
            'institution' => $this->request->getPost('institution'),
            'graduation_year' => $this->request->getPost('graduation_year'),
        ]);

        return redirect()->back()->with('success', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function addTraining()
    {
        $userSession = session()->get('user');
        $teacherId = $userSession['related_id'];

        $this->trainingModel->insert([
            'teacher_id' => $teacherId,
            'name' => $this->request->getPost('name'),
            'year' => $this->request->getPost('year'),
            'organizer' => $this->request->getPost('organizer'),
            'certificate_number' => $this->request->getPost('certificate_number'),
        ]);

        return redirect()->back()->with('success', 'Riwayat pelatihan berhasil ditambahkan.');
    }

    public function deleteSub($type, $id)
    {
        $userSession = session()->get('user');
        $teacherId = $userSession['related_id'];

        // Verify ownership
        switch ($type) {
            case 'education':
                $item = $this->educationModel->find($id);
                if ($item && $item['teacher_id'] == $teacherId)
                    $this->educationModel->delete($id);
                break;
            case 'training':
                $item = $this->trainingModel->find($id);
                if ($item && $item['teacher_id'] == $teacherId)
                    $this->trainingModel->delete($id);
                break;
        }

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function previewDocument($id)
    {
        $userSession = session()->get('user');
        $teacherId   = $userSession['related_id'] ?? null;

        $doc = $this->documentModel->find($id);

        if (!$doc || $doc['teacher_id'] != $teacherId) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/teacher_docs/' . $doc['filename'];

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $mimeType = $doc['file_type'] === 'pdf' ? 'application/pdf' : mime_content_type($filePath);

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline; filename="' . $doc['original_name'] . '"')
            ->setBody(file_get_contents($filePath));
    }

    public function documents()
    {
        $userSession = session()->get('user');
        $teacherId   = $userSession['related_id'] ?? null;

        if (!$teacherId) {
            return redirect()->to('/profile')->with('error', 'Akses ditolak.');
        }

        return view('profile/documents', [
            'title'     => 'Arsip Dokumen',
            'documents' => $this->documentModel->getByTeacher($teacherId),
        ]);
    }

    public function uploadDocument()
    {
        $userSession = session()->get('user');
        $teacherId   = $userSession['related_id'] ?? null;

        if (!$teacherId) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $file  = $this->request->getFile('document_file');
        $title = trim($this->request->getPost('document_title'));

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        if (empty($title)) {
            return redirect()->back()->with('error', 'Judul dokumen wajib diisi.');
        }

        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowedMime)) {
            return redirect()->back()->with('error', 'Hanya file gambar (JPG/PNG/GIF/WEBP) atau PDF yang diizinkan.');
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file->getSize() > $maxSize) {
            return redirect()->back()->with('error', 'Ukuran file maksimal 5 MB.');
        }

        $ext      = $file->getClientExtension();
        $newName  = 'doc_' . $teacherId . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
        $fileType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'pdf';

        $uploadPath = FCPATH . 'uploads/teacher_docs/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $newName);

        $this->documentModel->insert([
            'teacher_id'    => $teacherId,
            'title'         => $title,
            'filename'      => $newName,
            'original_name' => $file->getClientName(),
            'file_type'     => $fileType,
            'file_size'     => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function downloadDocument($id)
    {
        $userSession = session()->get('user');
        $teacherId   = $userSession['related_id'] ?? null;

        $doc = $this->documentModel->find($id);

        if (!$doc || $doc['teacher_id'] != $teacherId) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/teacher_docs/' . $doc['filename'];

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        return $this->response->download($filePath, null)->setFileName($doc['original_name']);
    }

    public function deleteDocument($id)
    {
        $userSession = session()->get('user');
        $teacherId   = $userSession['related_id'] ?? null;

        $doc = $this->documentModel->find($id);

        if (!$doc || $doc['teacher_id'] != $teacherId) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/teacher_docs/' . $doc['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->documentModel->delete($id);

        return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
