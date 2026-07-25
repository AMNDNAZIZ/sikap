<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\UserModel;

class Students extends BaseController
{
    protected $studentModel;
    protected $classModel;
    protected $userModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->userModel    = new UserModel();
    }

    /**
     * Generator password default: 5 digit (1-9) + '*'
     * Contoh: 58321*
     * Returned string termasuk '*'.
     */
    private function generatePassword(): string
    {
        $digits = '';
        for ($i = 0; $i < 5; $i++) {
            $digits .= random_int(1, 9); // digit 1..9
        }
        return $digits . '*';
    }

    /**
     * Index / daftar siswa (paginate)
     */
    public function index()
    {
        $search  = $this->request->getGet('search');
        $classId = $this->request->getGet('class_id');

        $builder = $this->studentModel
            ->select('students.*, classes.name AS class_name, users.username AS user_username')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left');

        if ($classId) {
            $builder->where('students.class_id', $classId);
        }

        if ($search) {
            $builder->groupStart()
                ->like('students.name', $search)
                ->orLike('students.nis', $search)
                ->orLike('students.username', $search)
                ->groupEnd();
        }

        $data = [
            'title' => 'Manajemen Siswa',
            'students' => $builder->paginate(10),
            'pager' => $builder->pager,
            'classes' => $this->classModel->findAll(),
            'search' => $search,
            'selectedClass' => $classId,
        ];

        return view('admin/students/index', $data);
    }

    /**
     * Form create
     */
    public function create()
    {
        return view('admin/students/create', [
            'title' => 'Tambah Siswa',
            'classes' => $this->classModel->findAll(),
        ]);
    }

    /**
     * Store: buat user + student
     *
     * Username: dari input (wajib/opsional). Jika kosong, kita generate fallback (generatePassword tanpa '*'? 
     *   => untuk konsistensi kita gunakan generator yang sama tanpa syarat).
     * Plain password: jika input kosong -> generate otomatis; jika ada input admin -> pakai itu.
     */
    public function store()
    {
        $post = $this->request->getPost();

        $usernameInput = trim($post['username'] ?? '');
        $plainPasswordInput = trim($post['plain_password'] ?? '');

        // Jika username kosong, kita fallback generate (meskipun requirement menyatakan biasanya diinput/import).
        if ($usernameInput === '') {
            // generate a sensible username fallback (boleh juga diganti kebijakan lain)
            $usernameInput = 'u' . time(); // fallback unik (very unlikely collision)
        }

        // cek unik username di tabel users (lebih authoritative)
        $existingUser = $this->userModel->where('username', $usernameInput)->first();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan. Gunakan username lain.');
        }

        // password plain default jika kosong
        $plainPassword = $plainPasswordInput === '' ? $this->generatePassword() : $plainPasswordInput;

        // buat user (hash password)
        $this->userModel->insert([
            'username'      => $usernameInput,
            'password'      => password_hash($plainPassword, PASSWORD_BCRYPT),
            'fullname'      => $post['name'] ?? null,
            'role_id'       => 3,
            'related_type'  => 'student',
        ]);
        $userId = $this->userModel->getInsertID();

        // buat student dan simpan plain_password di students
        $this->studentModel->insert([
            'nis'            => $post['nis'] ?? null,
            'username'       => $usernameInput,
            'name'           => $post['name'] ?? null,
            'gender'         => $post['gender'] ?? null,
            'class_id'       => $post['class_id'] ?? null,
            'religion'       => $post['religion'] ?? null,
            'user_id'        => $userId,
            'plain_password' => $plainPassword,
            'room'           => $post['room'] ?? null,
        ]);
        $studentId = $this->studentModel->getInsertID();

        // update user.related_id ke student id
        if ($studentId) {
            $this->userModel->update($userId, ['related_id' => $studentId]);
        }

        return redirect()->to('/admin/students')->with('success', 'Siswa berhasil ditambahkan beserta akun login.');
    }

    /**
     * Edit form
     */
    public function edit($id)
    {
        $student = $this->studentModel->find($id);
        if (!$student) {
            return redirect()->to('/admin/students')->with('error', 'Siswa tidak ditemukan.');
        }

        return view('admin/students/edit', [
            'title' => 'Edit Siswa',
            'student' => $student,
            'classes' => $this->classModel->findAll(),
        ]);
    }

    /**
     * Update student + sync user
     *
     * Username: boleh diubah, namun harus unik
     * Plain password: jika dikosongkan saat update => pertahankan nilai lama; jika diisi => gunakan nilai baru dan update hash di users
     */
    public function update($id)
    {
        $student = $this->studentModel->find($id);
        if (!$student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan.');
        }

        $post = $this->request->getPost();
        $newUsername = trim($post['username'] ?? '');
        $newPlainPassword = trim($post['plain_password'] ?? '');

        // validasi username unik (kecuali milik dirinya sendiri)
        if ($newUsername === '') {
            return redirect()->back()->withInput()->with('error', 'Username wajib diisi.');
        }

        $exists = $this->userModel
            ->where('username', $newUsername)
            ->where('id !=', $student['user_id'])
            ->first();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Username sudah digunakan oleh akun lain.');
        }

        // jika plain_password kosong -> tetap pakai yang lama
        if ($newPlainPassword === '') {
            $newPlainPassword = $student['plain_password'];
            $shouldUpdateUserPassword = false;
        } else {
            $shouldUpdateUserPassword = true;
        }

        // update users: username (selalu) dan password (jika berubah)
        if (!empty($student['user_id'])) {
            $userUpdateData = ['username' => $newUsername];
            if ($shouldUpdateUserPassword) {
                $userUpdateData['password'] = password_hash($newPlainPassword, PASSWORD_BCRYPT);
            }
            $this->userModel->update($student['user_id'], $userUpdateData);
        }

        // update students
        $this->studentModel->update($id, [
            'nis'            => $post['nis'] ?? $student['nis'],
            'username'       => $newUsername,
            'name'           => $post['name'] ?? $student['name'],
            'gender'         => $post['gender'] ?? $student['gender'],
            'class_id'       => $post['class_id'] ?? $student['class_id'],
            'religion'       => $post['religion'] ?? $student['religion'],
            'plain_password' => $newPlainPassword,
            'room'           => $post['room'] ?? $student['room'],
        ]);

        return redirect()->to('/admin/students')->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Delete student + related user
     */
    public function delete($id)
    {
        $student = $this->studentModel->find($id);
        if ($student) {
            if (!empty($student['user_id'])) {
                $this->userModel->delete($student['user_id']);
            }
            $this->studentModel->delete($id);
        }
        return redirect()->to('/admin/students')->with('success', 'Siswa beserta akun login berhasil dihapus.');
    }

    /**
     * Import Excel:
     * - Excel kolom: NIS | Username | Nama | Gender | Agama | Kelas
     * - Jika username kosong pada baris, kita gagal-kan baris tersebut (karena kamu inginkan username dari input/import).
     * - Jika username sudah ada, baris dianggap gagal (dicatat di $failed)
     */
    public function import()
    {
        // Log untuk debugging
        log_message('info', '[Students::import] Import started');
        
        // Validate CSRF manually if needed (for debugging)
        if (!$this->request->is('post')) {
            log_message('error', '[Students::import] Not a POST request');
            return redirect()->back()->with('error', 'Metode request tidak valid.');
        }
        
        $file = $this->request->getFile('file');
    
        if (!$file || !$file->isValid()) {
            log_message('error', '[Students::import] File tidak valid: ' . ($file ? $file->getErrorString() : 'null'));
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
    
        $studentModel = new \App\Models\StudentModel();
        $userModel    = new \App\Models\UserModel();
    
        $importSuccess = [];
        $importFailed  = [];
    
        $db = \Config\Database::connect();
        $db->transStart(); // gunakan transaksi agar sinkron
    
        for ($i = 3; $i <= count($data); $i++) {
            $nis       = trim($data[$i]['A']);
            $username  = trim($data[$i]['B']);
            $name      = trim($data[$i]['C']);
            $gender    = trim($data[$i]['D']);
            $class_id  = trim($data[$i]['E']);
            $religion  = trim($data[$i]['F']);
            $plain_pw  = trim($data[$i]['G']);
            $room      = trim($data[$i]['H']);
    
            // Abaikan baris kosong
            if (empty($name) && empty($username)) continue;
    
            // Cek username unik
            $existingUser = $userModel->where('username', $username)->first();
            if ($existingUser) {
                $importFailed[] = "Baris $i: Username '$username' sudah digunakan.";
                continue;
            }
    
            // Jika password kosong → generate otomatis
            if (empty($plain_pw)) {
                $plain_pw = $this->generatePassword();
            }
    
            // Buat data siswa terlebih dahulu
            $studentData = [
                'nis'            => $nis,
                'username'       => $username,
                'name'           => $name,
                'gender'         => $gender,
                'class_id'       => $class_id,
                'religion'       => $religion,
                'plain_password' => $plain_pw,
                'room'           => $room
            ];
            $studentModel->insert($studentData);
            $studentId = $studentModel->getInsertID();
    
            // Buat user dengan relasi ke siswa
            $userData = [
                'username'     => $username,
                'password'     => password_hash($plain_pw, PASSWORD_DEFAULT),
                'fullname'     => $name,
                'role_id'      => 3,              // 3 = siswa
                'related_id'   => $studentId,
                'related_type' => 'student'
            ];
            $userModel->insert($userData);
            $userId = $userModel->getInsertID();
    
            // Update student dengan user_id
            $studentModel->update($studentId, ['user_id' => $userId]);
    
            $importSuccess[] = "$name ($username)";
        }
    
        $db->transComplete();
    
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat impor data.');
        }
    
        return redirect()->back()
            ->with('import_success', $importSuccess)
            ->with('import_failed', $importFailed);
    }

    
    public function template()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = ['NIS', 'Username', 'Nama', 'Gender', 'Kelas', 'Agama', 'Plain Password', 'Ruang'];
        $sheet->fromArray($headers, NULL, 'A1');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'DCE6F1']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Lebar kolom otomatis
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Tambahkan contoh baris kedua
        $sampleData = [
            ['1001', 'andi01', 'Andi Wijaya', 'L', '1', 'Islam', '12345*', 'Ruang 1']
        ];
        $sheet->fromArray($sampleData, NULL, 'A2');

        // Tambahkan komentar ke setiap kolom
        $sheet->getComment('A1')->getText()->createTextRun("Nomor Induk Siswa (opsional)");
        $sheet->getComment('B1')->getText()->createTextRun("Username harus unik. Digunakan untuk login CBT");
        $sheet->getComment('C1')->getText()->createTextRun("Nama lengkap siswa sesuai data resmi");
        $sheet->getComment('D1')->getText()->createTextRun("Isi dengan L (Laki-laki) atau P (Perempuan)");
        $sheet->getComment('E1')->getText()->createTextRun("Isi dengan ID kelas sesuai daftar kelas");
        $sheet->getComment('F1')->getText()->createTextRun("Isi salah satu: Islam, Kristen, Katolik, Hindu, Budha, Konghucu");
        $sheet->getComment('G1')->getText()->createTextRun("Password akan otomatis digenerate (5 angka + *) saat impor, tetapi bisa dikosongkan untuk auto");
        $sheet->getComment('H1')->getText()->createTextRun("Ruang tempat ujian");

        // Format agar komentar mudah dibaca
        foreach (['A1','B1','C1','D1','E1','F1','G1', 'H1'] as $cell) {
            $sheet->getComment($cell)->setWidth('200pt')->setHeight('60pt');
        }

        // Output file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_siswa.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }


}
