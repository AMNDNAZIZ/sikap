<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Models\UserModel;

class Teachers extends BaseController
{
    protected $teacherModel;
    protected $userModel;

    public function __construct()
    {
        $this->teacherModel = new TeacherModel();
        $this->userModel    = new UserModel();
    }

    public function index()
    {
        $data['title'] = 'Manajemen Guru';
        $data['teachers'] = $this->teacherModel
            ->select('teachers.*, users.username')
            ->join('users', 'users.id = teachers.user_id', 'left')
            ->findAll();

        return view('admin/teachers/index', $data);
    }

    public function create()
    {
        return view('admin/teachers/create', ['title' => 'Tambah Guru']);
    }

    public function store()
    {
        $post = $this->request->getPost();

        $username = !empty($post['username'])
            ? strtolower($post['username'])
            : strtolower(str_replace(' ', '', $post['name']));

        // buat akun user
        $this->userModel->insert([
            'username'      => $username,
            'password'      => password_hash('123456', PASSWORD_BCRYPT),
            'fullname'      => $post['name'],
            'role_id'       => 2,
            'related_id'    => null,
            'related_type'  => 'teacher',
        ]);
        $userId = $this->userModel->getInsertID();

        // simpan guru
        $this->teacherModel->insert([
            'username'=> $post['username'] ?? null,
            'name'    => $post['name'],
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
        $teacher = $this->teacherModel->find($id);
        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Data guru tidak ditemukan.');
        }

        return view('admin/teachers/edit', [
            'title'   => 'Edit Guru',
            'teacher' => $teacher
        ]);
    }

    public function update($id)
    {
        $post = $this->request->getPost();
        $teacher = $this->teacherModel->find($id);

        if (!$teacher) {
            return redirect()->to('/admin/teachers')->with('error', 'Data guru tidak ditemukan.');
        }

        // update guru
        $this->teacherModel->update($id, [
            'username'     => $post['username'],
            'name'    => $post['name'],
        ]);

        // update user sinkron dengan guru
        if (!empty($teacher['user_id'])) {
            $this->userModel->update($teacher['user_id'], [
                'fullname' => $post['name'],
            ]);
        }

        return redirect()->to('/admin/teachers')->with('success', 'Guru berhasil diperbarui.');
    }

    public function delete($id)
    {
        // Validate request method
        if (!$this->request->is('post')) {
            return redirect()->to('/admin/teachers')->with('error', 'Metode request tidak valid.');
        }

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

    /**
     * Import guru dari file Excel
     * 
     * Format Excel:
     * Row 1: Header
     * Row 2: Keterangan
     * Row 3+: Data
     * 
     * Kolom:
     * A: NIP (opsional)
     * B: Username (wajib, unique)
     * C: Nama Lengkap (wajib)
     * D: Password (opsional, jika kosong akan di-generate)
     * 
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function import()
    {
        // Log untuk debugging
        log_message('info', '[Teachers::import] Import started');
        
        // Validate request method
        if (!$this->request->is('post')) {
            log_message('error', '[Teachers::import] Not a POST request');
            return redirect()->back()->with('error', 'Metode request tidak valid.');
        }
        
        $file = $this->request->getFile('file');
    
        if (!$file || !$file->isValid()) {
            log_message('error', '[Teachers::import] File tidak valid: ' . ($file ? $file->getErrorString() : 'null'));
            return redirect()->back()->with('error', 'File tidak valid!');
        }
    
        $ext = $file->getClientExtension();
        if (!in_array($ext, ['xls', 'xlsx'])) {
            return redirect()->back()->with('error', 'Format file harus .xls atau .xlsx!');
        }
    
        $reader = $ext === 'xls'
            ? new \PhpOffice\PhpSpreadsheet\Reader\Xls()
            : new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    
        $spreadsheet = $reader->load($file);
        $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    
        $importSuccess = [];
        $importFailed  = [];
    
        $db = \Config\Database::connect();
        $db->transStart(); // gunakan transaksi agar sinkron
    
        for ($i = 3; $i <= count($data); $i++) {
            $nip       = trim($data[$i]['A'] ?? '');
            $username  = trim($data[$i]['B'] ?? '');
            $name      = trim($data[$i]['C'] ?? '');
            $plain_pw  = trim($data[$i]['D'] ?? '');
    
            // Abaikan baris kosong
            if (empty($name) && empty($username)) continue;
    
            // Validasi data wajib
            if (empty($username)) {
                $importFailed[] = "Baris $i: Username tidak boleh kosong.";
                continue;
            }
    
            if (empty($name)) {
                $importFailed[] = "Baris $i: Nama tidak boleh kosong.";
                continue;
            }
    
            // Cek username unik
            $existingUser = $this->userModel->where('username', $username)->first();
            if ($existingUser) {
                $importFailed[] = "Baris $i: Username '$username' sudah digunakan.";
                continue;
            }
    
            // Jika password kosong → generate otomatis
            if (empty($plain_pw)) {
                $plain_pw = $this->generatePassword();
            }
    
            // Buat data guru terlebih dahulu
            $teacherData = [
                'nip'      => $nip,
                'username' => $username,
                'name'     => $name,
            ];
            $this->teacherModel->insert($teacherData);
            $teacherId = $this->teacherModel->getInsertID();
    
            // Buat user dengan relasi ke guru
            $userData = [
                'username'     => $username,
                'password'     => password_hash($plain_pw, PASSWORD_DEFAULT),
                'fullname'     => $name,
                'role_id'      => 2,              // 2 = guru
                'related_id'   => $teacherId,
                'related_type' => 'teacher'
            ];
            $this->userModel->insert($userData);
            $userId = $this->userModel->getInsertID();
    
            // Update teacher dengan user_id
            $this->teacherModel->update($teacherId, ['user_id' => $userId]);
    
            $importSuccess[] = "$name ($username) - Password: $plain_pw";
        }
    
        $db->transComplete();
    
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat impor data.');
        }
    
        return redirect()->back()
            ->with('import_success', $importSuccess)
            ->with('import_failed', $importFailed);
    }

    /**
     * Generate random password
     * 
     * @return string
     */
    private function generatePassword()
    {
        return bin2hex(random_bytes(4)); // 8 karakter random
    }

    /**
     * Download template Excel untuk import guru
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'NIP');
        $sheet->setCellValue('B1', 'Username');
        $sheet->setCellValue('C1', 'Nama Lengkap');
        $sheet->setCellValue('D1', 'Password');

        // Keterangan
        $sheet->setCellValue('A2', '(opsional)');
        $sheet->setCellValue('B2', '(wajib, unique)');
        $sheet->setCellValue('C2', '(wajib)');
        $sheet->setCellValue('D2', '(opsional, auto-generate jika kosong)');

        // Contoh data
        $sheet->setCellValue('A3', '197001011990031001');
        $sheet->setCellValue('B3', 'guru001');
        $sheet->setCellValue('C3', 'Ahmad Dahlan');
        $sheet->setCellValue('D3', 'password123');

        $sheet->setCellValue('A4', '198505152010012001');
        $sheet->setCellValue('B4', 'guru002');
        $sheet->setCellValue('C4', 'Siti Nurhaliza');
        $sheet->setCellValue('D4', '');

        // Styling
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        $sheet->getStyle('A2:D2')->getFont()->setItalic(true);
        $sheet->getStyle('A2:D2')->getFont()->setSize(9);

        // Auto width
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_guru_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}

