<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentMutationModel;
use App\Models\StudentModel;
use App\Models\StudentRecordModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Libraries\PdfGenerator;

class StudentMutation extends BaseController
{
    protected $mutationModel;
    protected $studentModel;
    protected $recordModel;
    protected $userModel;
    protected $classModel;
    protected $yearModel;

    public function __construct()
    {
        $this->mutationModel = new StudentMutationModel();
        $this->studentModel = new StudentModel();
        $this->recordModel = new StudentRecordModel();
        $this->userModel = new UserModel();
        $this->classModel = new ClassModel();
        $this->yearModel = new AcademicYearModel();
    }

    public function index()
    {
        $params = [
            'type' => $this->request->getGet('type'),
            'status' => $this->request->getGet('status'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'search' => $this->request->getGet('search'),
        ];

        $builder = $this->mutationModel->getFiltered(array_filter($params));

        $data = [
            'title' => 'Buku Mutasi Siswa',
            'mutations' => $builder->paginate(15),
            'pager' => $builder->pager,
            'filters' => $params,
        ];

        return view('admin/student_mutation/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Mutasi',
            'students' => $this->studentModel
                ->select('students.id, students.nisn, students.nis, students.name')
                ->join('student_records sr', 'sr.student_id = students.id')
                ->where('sr.status', 'aktif')
                ->join('academic_years ay', 'ay.id = sr.academic_year_id')
                ->where('ay.is_active', 1)
                ->orderBy('students.name', 'ASC')
                ->findAll(),
            // Semua kelas (untuk mutasi keluar & pindah kelas)
            'classes' => $this->classModel->orderBy('name', 'ASC')->findAll(),
            // Hanya kelas aktif (untuk kelas tujuan mutasi masuk)
            'activeClasses' => $this->classModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
        ];

        return view('admin/student_mutation/create', $data);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $type = $post['type'] ?? '';

        $rules = [
            'type' => 'required|in_list[masuk,keluar,pindah_kelas]',
            'mutation_date' => 'required|valid_date',
        ];

        if ($type === 'masuk') {
            $rules['from_school'] = 'required|max_length[200]';
            $rules['to_class_id'] = 'required|numeric';
        } elseif ($type === 'keluar') {
            $rules['student_id'] = 'required|numeric';
            $rules['to_school'] = 'required|max_length[200]';
        } elseif ($type === 'pindah_kelas') {
            $rules['student_id'] = 'required|numeric';
            $rules['to_class_id'] = 'required|numeric';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $mutationData = [
            'type' => $type,
            'mutation_date' => $post['mutation_date'],
            'reason' => !empty($post['reason']) ? trim($post['reason']) : null,
            'letter_number' => !empty($post['letter_number']) ? trim($post['letter_number']) : null,
            'from_school' => !empty($post['from_school']) ? trim($post['from_school']) : null,
            'to_school' => !empty($post['to_school']) ? trim($post['to_school']) : null,
            'from_class_id' => !empty($post['from_class_id']) ? (int)$post['from_class_id'] : null,
            'to_class_id' => !empty($post['to_class_id']) ? (int)$post['to_class_id'] : null,
            'status' => 'pending',
        ];

        if ($type === 'masuk') {
            $nisn = $post['nisn'] ?? '';
            $student = !empty($nisn) ? $this->studentModel->where('nisn', $nisn)->first() : null;

            if ($student) {
                // Siswa sudah terdaftar di sistem
                $mutationData['student_id'] = $student['id'];
            } else {
                // Siswa baru: student_id NULL sementara, data disimpan di kolom note
                $mutationData['student_id'] = null;
                $mutationData['_new_student'] = [
                    'nisn'        => $post['nisn'] ?? '',
                    'nis'         => $post['nis'] ?? '',
                    'name'        => $post['student_name'] ?? '',
                    'gender'      => $post['gender'] ?? 'L',
                    'birth_place' => $post['birth_place'] ?? '',
                    'birth_date'  => !empty($post['birth_date']) ? $post['birth_date'] : null,
                ];
            }
        } else {
            $studentId = !empty($post['student_id']) ? (int)$post['student_id'] : null;
            $mutationData['student_id'] = $studentId;

            // Otomatis deteksi kelas asal siswa dari record aktif jika belum terisi
            if (empty($mutationData['from_class_id']) && $studentId > 0) {
                $activeYear = $this->yearModel->where('is_active', 1)->first();
                if ($activeYear) {
                    $record = $this->recordModel
                        ->where('student_id', $studentId)
                        ->where('academic_year_id', $activeYear['id'])
                        ->where('status', 'aktif')
                        ->first();
                    if ($record && !empty($record['class_id'])) {
                        $mutationData['from_class_id'] = (int)$record['class_id'];
                    }
                }
            }
        }

        // handle attachment upload
        $attachment = $this->request->getFile('attachment');
        if ($attachment && $attachment->isValid() && !$attachment->hasMoved()) {
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            if (in_array($attachment->getMimeType(), $allowedTypes)) {
                $dir = WRITEPATH . 'uploads/mutations/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $newName = $attachment->getRandomName();
                $attachment->move($dir, $newName);
                $mutationData['attachment'] = 'uploads/mutations/' . $newName;
            }
        }

        $newStudentData = $mutationData['_new_student'] ?? null;
        unset($mutationData['_new_student']);

        if ($type === 'masuk' && $newStudentData) {
            // Simpan data siswa baru di kolom note, student_id tetap NULL sampai approved
            $mutationData['note'] = ($mutationData['note'] ?? '') . "\n[DATA_SISWA_BARU]" . json_encode($newStudentData);
            $mutationData['student_id'] = null;
        }

        $this->mutationModel->insert($mutationData);

        return redirect()->to('/admin/student-mutation')->with('success', 'Mutasi berhasil dibuat dan menunggu persetujuan.');
    }

    public function show($id)
    {
        $mutation = $this->mutationModel->getWithStudent($id);
        if (!$mutation) {
            return redirect()->to('/admin/student-mutation')->with('error', 'Data mutasi tidak ditemukan.');
        }

        $data = [
            'title' => 'Detail Mutasi',
            'mutation' => $mutation,
        ];

        return view('admin/student_mutation/show', $data);
    }

    public function approve($id)
    {
        $mutation = $this->mutationModel->find($id);
        if (!$mutation || $mutation['status'] !== 'pending') {
            return redirect()->to('/admin/student-mutation')->with('error', 'Mutasi tidak valid atau sudah diproses.');
        }

        $user = session()->get('user');
        $db = db_connect();
        $db->transStart();

        $year = $this->yearModel->where('is_active', 1)->first();

        switch ($mutation['type']) {
            case 'masuk':
                $this->_processMasuk($mutation, $year);
                break;
            case 'keluar':
                $this->_processKeluar($mutation);
                break;
            case 'pindah_kelas':
                $this->_processPindahKelas($mutation, $year);
                break;
        }

        $this->mutationModel->update($id, [
            'status' => 'approved',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to('/admin/student-mutation/show/' . $id)->with('error', 'Gagal memproses persetujuan mutasi.');
        }

        return redirect()->to('/admin/student-mutation/show/' . $id)->with('success', 'Mutasi berhasil disetujui.');
    }

    public function reject($id)
    {
        $mutation = $this->mutationModel->find($id);
        if (!$mutation || $mutation['status'] !== 'pending') {
            return redirect()->to('/admin/student-mutation')->with('error', 'Mutasi tidak valid atau sudah diproses.');
        }

        $user = session()->get('user');
        $note = $this->request->getPost('note') ?? '';

        $this->mutationModel->update($id, [
            'status' => 'rejected',
            'approved_by' => $user['id'],
            'approved_at' => date('Y-m-d H:i:s'),
            'note' => $note,
        ]);

        return redirect()->to('/admin/student-mutation/show/' . $id)->with('success', 'Mutasi ditolak.');
    }

    public function print($id)
    {
        $mutation = $this->mutationModel->getWithStudent($id);
        if (!$mutation) {
            return redirect()->to('/admin/student-mutation')->with('error', 'Data mutasi tidak ditemukan.');
        }

        $data = [
            'mutation' => $mutation,
            'school' => $this->school ?? [],
        ];

        if ($mutation['type'] === 'masuk') {
            $template = 'admin/student_mutation/pdf/surat_mutasi_masuk';
            $filename = 'surat_mutasi_masuk_' . $mutation['id'] . '.pdf';
        } else {
            $template = 'admin/student_mutation/pdf/surat_mutasi_keluar';
            $filename = 'surat_mutasi_' . $mutation['type'] . '_' . $mutation['id'] . '.pdf';
        }

        $pdfGen = new PdfGenerator();
        $pdfGen->stream($template, $data, $filename, 'portrait', true);
    }

    public function delete($id)
    {
        $mutation = $this->mutationModel->find($id);
        if (!$mutation || $mutation['status'] === 'approved') {
            return redirect()->to('/admin/student-mutation')->with('error', 'Mutasi yang sudah disetujui tidak bisa dihapus.');
        }

        if (!empty($mutation['attachment'])) {
            $filePath = WRITEPATH . $mutation['attachment'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->mutationModel->delete($id);

        return redirect()->to('/admin/student-mutation')->with('success', 'Mutasi berhasil dihapus.');
    }

    private function _processMasuk(array $mutation, $year): void
    {
        $noteData  = $mutation['note'] ?? '';
        $studentId = $mutation['student_id'];

        // Jika student_id NULL, berarti siswa baru — data tersimpan di kolom note
        if (empty($studentId) && preg_match('/\[DATA_SISWA_BARU\](.*)/s', $noteData, $matches)) {
            $newStudent = json_decode(trim($matches[1]), true);

            if ($newStudent && !empty($newStudent['name'])) {
                $username = !empty($newStudent['nisn']) ? $newStudent['nisn'] : 'mutasi_' . time();
                $password = !empty($newStudent['nisn']) ? $newStudent['nisn'] : 'mutasi123';

                // Buat akun user siswa baru
                $this->userModel->insert([
                    'username'     => $username,
                    'password'     => password_hash($password, PASSWORD_DEFAULT),
                    'fullname'     => $newStudent['name'],
                    'role_id'      => 5,
                    'related_type' => 'student',
                    'is_active'    => 1,
                ]);
                $userId = $this->userModel->getInsertID();

                // Buat data siswa baru
                $this->studentModel->insert([
                    'nisn'                => $newStudent['nisn'],
                    'nis'                 => $newStudent['nis'],
                    'name'                => $newStudent['name'],
                    'gender'              => $newStudent['gender'],
                    'birth_place'         => $newStudent['birth_place'],
                    'birth_date'          => $newStudent['birth_date'],
                    'user_id'             => $userId,
                    'registration_type'   => 'Mutasi Masuk',
                ]);
                $studentId = $this->studentModel->getInsertID();

                // Hubungkan akun user ke data siswa (related_id)
                if ($userId && $studentId) {
                    $this->userModel->update($userId, ['related_id' => $studentId]);
                }

                // Update mutation dengan student_id yang baru dibuat
                $this->mutationModel->update($mutation['id'], ['student_id' => $studentId]);
            }
        }

        if ($studentId && $year) {
            $existing = $this->recordModel
                ->where('student_id', $studentId)
                ->where('academic_year_id', $year['id'])
                ->first();

            if ($existing) {
                $this->recordModel->update($existing['id'], [
                    'class_id' => $mutation['to_class_id'],
                    'status'   => 'aktif',
                ]);
            } else {
                $this->recordModel->insert([
                    'student_id'       => $studentId,
                    'academic_year_id' => $year['id'],
                    'class_id'         => $mutation['to_class_id'],
                    'status'           => 'aktif',
                ]);
            }

            // Aktifkan kembali akun user siswa
            $student = $this->studentModel->find($studentId);
            if ($student && !empty($student['user_id'])) {
                $this->userModel->update($student['user_id'], ['is_active' => 1]);
            }
        }
    }

    private function _processKeluar(array $mutation): void
    {
        $studentId = $mutation['student_id'];
        $year = $this->yearModel->where('is_active', 1)->first();

        if ($studentId && $year) {
            $record = $this->recordModel
                ->where('student_id', $studentId)
                ->where('academic_year_id', $year['id'])
                ->first();

            if ($record) {
                $this->recordModel->update($record['id'], ['status' => 'nonaktif']);
            }

            $student = $this->studentModel->find($studentId);
            if ($student && !empty($student['user_id'])) {
                $this->userModel->update($student['user_id'], ['is_active' => 0]);
            }
        }
    }

    private function _processPindahKelas(array $mutation, $year): void
    {
        $studentId = $mutation['student_id'];

        if ($studentId && $year) {
            $record = $this->recordModel
                ->where('student_id', $studentId)
                ->where('academic_year_id', $year['id'])
                ->first();

            if ($record) {
                $this->recordModel->update($record['id'], [
                    'class_id' => $mutation['to_class_id'],
                ]);
            }
        }
    }
}
