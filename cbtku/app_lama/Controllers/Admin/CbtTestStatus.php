<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtTestStatusModel;
use App\Models\CbtBankSoalModel;
use App\Models\CbtExamNameModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;

class CbtTestStatus extends BaseController
{
    protected $testStatusModel;
    protected $bankModel;
    protected $examNameModel;
    protected $classModel;
    protected $subjectModel;

    public function __construct()
    {
        $this->testStatusModel = new CbtTestStatusModel();
        $this->bankModel = new CbtBankSoalModel();
        $this->examNameModel = new CbtExamNameModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
    }

    /**
     * ==============================
     * INDEX — daftar penjadwalan ujian
     * ==============================
     */
    public function index()
    {
        // Ambil user dan role
        $user = session()->get('user');
        $role = $user['role_id'] ?? 0;
        $teacherId = $user['teacher_id'] ?? 0;

        // Ambil bank soal dengan hitungan real-time (hanya yang aktif)
        if ($role == 2) {
            // Guru → hanya milik sendiri yang aktif
            $banks = $this->bankModel->getListWithCounts('teacher', $teacherId, true);
        } else {
            // Admin → semua yang aktif
            $banks = $this->bankModel->getListWithCounts(null, null, true);
        }

        // Ambil daftar jenis ujian
        $examNames = $this->examNameModel->orderBy('name', 'ASC')->findAll();

        // Ambil daftar kelas
        $classes = $this->classModel->orderBy('name', 'ASC')->findAll();

        // Ambil jadwal ujian (status tes)
        $testStatuses = $this->testStatusModel
            ->select('cbt_test_status.*, cbt_question_banks.code AS bank_code, subjects.name AS subject_name, cbt_exam_names.name AS exam_name')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
            ->orderBy('cbt_test_status.start_time', 'DESC')
            ->findAll();

        // Hitung status berdasarkan waktu
        $now = time();
        foreach ($testStatuses as &$t) {
            $start = strtotime($t['start_time']);
            $end = strtotime($t['end_time']);

            if ($now < $start) {
                $t['status_label'] = '<span class="badge bg-secondary">Belum Mulai</span>';
            } elseif ($now >= $start && $now <= $end) {
                $t['status_label'] = '<span class="badge bg-success">Ujian Berlangsung</span>';
            } else {
                $t['status_label'] = '<span class="badge bg-danger">Ujian Berakhir</span>';
            }

            // Tambahan opsional: override jika dijeda
            if (!empty($t['is_paused']) && $t['is_paused'] == 1) {
                $t['status_label'] = '<span class="badge bg-warning text-dark">Dijeda</span>';
            }
        }

        $data = [
            'title' => 'Atur Ujian',
            'banks' => $banks,
            'examNames' => $examNames,
            'classes' => $classes,
            'testStatuses' => $testStatuses
        ];

        return view('admin/cbt/test_status/index', $data);
    }


    /**
     * ==============================
     * CREATE — form modal tambah ujian
     * ==============================
     */
    public function create()
    {
        // Ambil bank soal dengan hitungan real-time (hanya yang aktif)
        $activeBanks = $this->bankModel->getListWithCounts(null, null, true);

        $data = [
            'title' => 'Tambah Jadwal Ujian',
            'banks' => $activeBanks,
            'examNames' => $this->examNameModel->findAll(),
            'classes' => $this->classModel->findAll(),
        ];

        return view('admin/cbt/test_status/form', $data);
    }

    /**
     * ==============================
     * STORE — simpan data penjadwalan baru
     * ==============================
     */
    public function store()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }

        $post = $this->request->getPost();

        // -------------------------------------------------------
        // 1. Validasi Bank Soal
        // -------------------------------------------------------
        $user = session()->get('user');
        $role = $user['role_id'] ?? 0;
        $bankId = $post['bank_id'] ?? 0;

        // Ambil SEMUA bank dengan hitungan real-time
        $allBanks = $this->bankModel->getListWithCounts();
        $bank = null;

        // Cari bank yang dipilih dan cek hak akses
        foreach ($allBanks as $b) {
            if ($b['id'] == $bankId) {
                // Admin (1) -> bebas
                if ($role == 1) {
                    $bank = $b;
                }
                // Guru (2) -> harus miliknya
                else if ($role == 2 && ($b['teacher_id'] == ($user['teacher_id'] ?? 0))) {
                    $bank = $b;
                }
                break;
            }
        }

        if (!$bank) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak diizinkan menggunakan bank soal tersebut.',
                'errors' => [
                    'bank_id' => 'Anda tidak memiliki akses ke bank soal ini atau bank tidak ditemukan.'
                ]
            ]);
        }

        // Validasi bank soal harus aktif
        if ($bank['is_active'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Bank soal tidak aktif.',
                'errors' => [
                    'bank_id' => 'Bank soal yang dipilih tidak aktif. Silakan aktifkan terlebih dahulu atau pilih bank soal lain.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 2. Validasi dasar
        // -------------------------------------------------------
        $rules = [
            'bank_id' => 'required|integer',
            'exam_name_id' => 'required|integer',
            'semester' => 'required|in_list[ganjil,genap]',
            'start_time' => 'required|valid_date',
            'end_time' => 'required|valid_date',
            'duration' => 'required|integer|greater_than[0]',
            'show_pg_count' => 'permit_empty|integer',
            'show_esai_count' => 'permit_empty|integer',
            'bobot_pg' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'bobot_esai' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $this->validator->getErrors()  // ⬅️ tampil detail
            ]);
        }

        // -------------------------------------------------------
        // 3. Validasi jumlah soal
        // -------------------------------------------------------
        $show_pg_count = (int) ($post['show_pg_count'] ?? 0);
        $show_pgk_count = (int) ($post['show_pg_kompleks_count'] ?? 0);
        $show_bs_count = (int) ($post['show_bs_count'] ?? 0);
        $show_esai_count = (int) ($post['show_esai_count'] ?? 0);

        if ($show_pg_count > (int) $bank['total_pg']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_pg_count' => 'Jumlah PG melebihi total (' . $bank['total_pg'] . ')'
                ]
            ]);
        }

        if ($show_pgk_count > (int) ($bank['total_pg_kompleks'] ?? 0)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_pg_kompleks_count' => 'Jumlah PG Kompleks melebihi total (' . ($bank['total_pg_kompleks'] ?? 0) . ')'
                ]
            ]);
        }

        if ($show_bs_count > (int) ($bank['total_bs'] ?? 0)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_bs_count' => 'Jumlah BS melebihi total (' . ($bank['total_bs'] ?? 0) . ')'
                ]
            ]);
        }

        if ($show_esai_count > (int) $bank['total_esai']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_esai_count' => 'Jumlah Esai melebihi total (' . $bank['total_esai'] . ')'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 4. Validasi bobot PG + PGK + Esai
        // -------------------------------------------------------
        $bobot_pg = (int) $post['bobot_pg'];
        $bobot_pgk = (int) ($post['bobot_pg_kompleks'] ?? 0);
        $bobot_bs = (int) ($post['bobot_bs'] ?? 0);
        $bobot_esai = (int) $post['bobot_esai'];

        if (($bobot_pg + $bobot_pgk + $bobot_bs + $bobot_esai) !== 100) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'bobot_pg' => 'Total bobot PG + PGK + BS + Esai harus 100%.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 5. Validasi kelas
        // -------------------------------------------------------
        if (empty($post['class_codes']) || !is_array($post['class_codes'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'class_codes' => 'Minimal pilih satu kelas peserta.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 6. Validasi waktu
        // -------------------------------------------------------
        $start_time = strtotime($post['start_time']);
        $end_time = strtotime($post['end_time']);

        if ($end_time <= $start_time) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'end_time' => 'Waktu selesai harus setelah waktu mulai.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 7. Validasi token
        // -------------------------------------------------------
        $token = strtoupper(trim($post['token'] ?? ''));

        if (!preg_match('/^[A-Z0-9]{6}$/', $token)) {
            $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); // auto generate
        }

        // -------------------------------------------------------
        // 8. Simpan data
        // -------------------------------------------------------
        $insert = [
            'bank_id' => (int) $post['bank_id'],
            'exam_name_id' => (int) $post['exam_name_id'],
            'class_codes' => json_encode($post['class_codes']),
            'semester' => $post['semester'],
            'subject_type' => $post['subject_type'] ?? 'umum',
            'religion' => $post['religion'] ?? null,
            'show_pg_count' => $show_pg_count,
            'show_pg_kompleks_count' => $show_pgk_count,
            'show_bs_count' => $show_bs_count,
            'show_esai_count' => $show_esai_count,
            'bobot_pg' => $bobot_pg,
            'bobot_pg_kompleks' => $bobot_pgk,
            'bobot_bs' => $bobot_bs,
            'bobot_esai' => $bobot_esai,
            'shuffle_question' => $post['shuffle_question'] ?? 'tidak',
            'shuffle_option' => $post['shuffle_option'] ?? 'tidak',
            'finish_button_lock' => $post['finish_button_lock'] ?? '0',
            'start_time' => $post['start_time'],
            'duration' => (int) $post['duration'],
            'end_time' => $post['end_time'],
            'show_token' => $post['show_token'] ?? 'tidak',
            'token' => $token,
            'show_score' => $post['show_score'] ?? 'tidak',
            'anti_cheat' => ($post['anti_cheat'] == 'none' || empty($post['anti_cheat'])) ? 'tidak' : $post['anti_cheat'],
            'audio_limit' => (int) ($post['audio_limit'] ?? 0),
            'is_paused' => 0,
            'is_visible' => 1,
            'created_by' => $user['id'] ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->testStatusModel->insert($insert);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Jadwal ujian berhasil dibuat.'
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ DB ERROR: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan ke database.',
                'errors' => [
                    'database' => $e->getMessage()
                ]
            ]);
        }
    }

    public function getFilteredBanks()
    {
        $user = session()->get('user');
        $role = $user['role_id'] ?? 0;

        // Ambil semua bank dengan count (hanya yang aktif)
        if ($role == 1) {
            // Admin → semua yang aktif
            $banks = $this->bankModel->getListWithCounts(null, null, true);
        } else if ($role == 2) {
            // Guru → milik sendiri yang aktif
            $banks = $this->bankModel->getListWithCounts('teacher', $user['teacher_id'], true);
        } else {
            $banks = [];
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => $banks
        ]);
    }


    /**
     * ==============================
     * TOGGLE PAUSE — jeda / lanjut ujian
     * ==============================
     */

    public function detail($id)
    {
        $test = $this->testStatusModel
            ->select('cbt_test_status.*, cbt_question_banks.code AS bank_code, subjects.name AS subject_name, cbt_exam_names.name AS exam_name')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
            ->where('cbt_test_status.id', $id)
            ->first();

        if ($test) {
            $test['class_codes'] = json_decode($test['class_codes'] ?? '[]', true);
            return $this->response->setJSON(['success' => true, 'data' => $test]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Data tidak ditemukan.']);
    }

    /* -----------------------------------------------------
     * Halaman Edit Jadwal Ujian
     * ----------------------------------------------------- */
    public function edit($id)
    {
        try {
            $user = session()->get('user');
            $role = $user['role_id'] ?? 0;
            $teacherId = $user['teacher_id'] ?? 0;

            // Ambil data jadwal
            $test = $this->testStatusModel
                ->select('cbt_test_status.*, cbt_question_banks.code AS bank_code, cbt_question_banks.teacher_id AS bank_owner,
                          subjects.name AS subject_name, cbt_exam_names.name AS exam_name')
                ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
                ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
                ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                ->where('cbt_test_status.id', $id)
                ->first();

            if (!$test) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ]);
            }

            // Cek apakah user adalah pembuat jadwal
            $isCreator = ($test['created_by'] ?? 0) == $teacherId;

            // ================================
            // Filter bank soal sesuai aturan (gunakan getListWithCounts)
            // Hanya tampilkan bank soal yang aktif (is_active = 1)
            // ================================
            if ($role == 1) {
                // ADMIN → semua bank soal yang aktif
                $banks = $this->bankModel->getListWithCounts(null, null, true);
            } else if ($isCreator) {
                // GURU PEMBUAT → hanya bank buatan sendiri yang aktif
                $banks = $this->bankModel->getListWithCounts('teacher', $teacherId, true);
            } else {
                // GURU LAIN → hanya bank yang dipakai jadwal (jika aktif)
                $allBanks = $this->bankModel->getListWithCounts(null, null, true);
                $banks = array_filter($allBanks, function ($b) use ($test) {
                    return $b['id'] == $test['bank_id'];
                });
                $banks = array_values($banks);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $test,
                'banks' => $banks,
                'role' => $role,
                'is_creator' => $isCreator,
                'can_edit_bank' => ($role == 1 || $isCreator)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Edit error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ]);
        }
    }


    public function update($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }

        $post = $this->request->getPost();

        // 0. Ambil data jadwal lama untuk backup/validasi
        $oldTest = $this->testStatusModel->find($id);
        if (!$oldTest) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data jadwal tidak ditemukan.'
            ]);
        }

        // -------------------------------------------------------
        // 1. Validasi Bank Soal (Gunakan real-time count)
        // -------------------------------------------------------
        $bankId = $post['bank_id'] ?? $oldTest['bank_id']; // Gunakan yang lama jika disabled di form
        $allBanks = $this->bankModel->getListWithCounts();
        $bank = null;
        foreach ($allBanks as $b) {
            if ($b['id'] == $bankId) {
                $bank = $b;
                break;
            }
        }

        if (!$bank) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'bank_id' => 'Bank soal tidak ditemukan.'
                ]
            ]);
        }

        // Validasi bank soal harus aktif
        if ($bank['is_active'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Bank soal tidak aktif.',
                'errors' => [
                    'bank_id' => 'Bank soal yang dipilih tidak aktif. Silakan aktifkan terlebih dahulu atau pilih bank soal lain.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 2. Validasi dasar
        // -------------------------------------------------------
        $rules = [
            'exam_name_id' => 'required|integer',
            'semester' => 'required|in_list[ganjil,genap]',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required|integer|greater_than[0]',
            'show_pg_count' => 'permit_empty|integer',
            'show_pg_kompleks_count' => 'permit_empty|integer',
            'show_bs_count' => 'permit_empty|integer',
            'show_esai_count' => 'permit_empty|integer',
            'bobot_pg' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'bobot_pg_kompleks' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'bobot_bs' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
            'bobot_esai' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // -------------------------------------------------------
        // 3. Validasi jumlah soal
        // -------------------------------------------------------
        $show_pg_count = (int) ($post['show_pg_count'] ?? 0);
        $show_pgk_count = (int) ($post['show_pg_kompleks_count'] ?? 0);
        $show_bs_count = (int) ($post['show_bs_count'] ?? 0);
        $show_esai_count = (int) ($post['show_esai_count'] ?? 0);

        if ($show_pg_count > (int) $bank['total_pg']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_pg_count' => 'Jumlah PG melebihi total (' . $bank['total_pg'] . ')'
                ]
            ]);
        }

        if ($show_pgk_count > (int) ($bank['total_pg_kompleks'] ?? 0)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_pg_kompleks_count' => 'Jumlah PG Kompleks melebihi total (' . ($bank['total_pg_kompleks'] ?? 0) . ')'
                ]
            ]);
        }

        if ($show_bs_count > (int) ($bank['total_bs'] ?? 0)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_bs_count' => 'Jumlah BS melebihi total (' . ($bank['total_bs'] ?? 0) . ')'
                ]
            ]);
        }

        if ($show_esai_count > (int) $bank['total_esai']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'show_esai_count' => 'Jumlah Esai melebihi total (' . $bank['total_esai'] . ')'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 4. Validasi bobot total
        // -------------------------------------------------------
        $bobot_pg = (int) $post['bobot_pg'];
        $bobot_pgk = (int) ($post['bobot_pg_kompleks'] ?? 0);
        $bobot_bs = (int) ($post['bobot_bs'] ?? 0);
        $bobot_esai = (int) $post['bobot_esai'];

        if (($bobot_pg + $bobot_pgk + $bobot_bs + $bobot_esai) !== 100) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'bobot_pg' => 'Total bobot PG + PGK + BS + Esai harus 100%.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 5. Validasi kelas
        // -------------------------------------------------------
        if (empty($post['class_codes']) || !is_array($post['class_codes'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'class_codes' => 'Minimal pilih satu kelas peserta.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 6. Validasi waktu
        // -------------------------------------------------------
        $start_time = strtotime($post['start_time']);
        $end_time = strtotime($post['end_time']);

        if ($end_time <= $start_time) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => [
                    'end_time' => 'Waktu selesai harus setelah waktu mulai.'
                ]
            ]);
        }

        // -------------------------------------------------------
        // 7. Token (tetap, tapi hasil edit harus tetap valid)
        // -------------------------------------------------------
        $token = strtoupper(trim($post['token'] ?? ''));

        if (!preg_match('/^[A-Z0-9]{6}$/', $token)) {
            $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }

        // -------------------------------------------------------
        // 7.5. Validasi hak akses mengubah bank soal
        // -------------------------------------------------------
        $user = session()->get('user');
        $loggedUserId = $user['id'] ?? 0;
        $isAdmin = ($user['role_id'] ?? 0) == 1;

        // Ambil data bank yang dipilih user saat update (untuk cek hak akses)
        $selectedBank = $this->bankModel->find($bankId);

        // Jika bukan admin → lakukan pembatasan
        if (!$isAdmin) {

            // Jika user bukan pembuat jadwal → dia TIDAK BOLEH ubah bank soal
            if ($oldTest['created_by'] != $loggedUserId) {
                if ((int) $post['bank_id'] !== (int) $oldTest['bank_id']) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Anda tidak diizinkan mengubah bank soal.',
                        'errors' => [
                            'bank_id' => 'Hanya pembuat jadwal yang boleh mengganti bank soal.'
                        ]
                    ]);
                }
            }

            // Jika user adalah pembuat jadwal → dia hanya boleh pilih bank soal miliknya
            if ($oldTest['created_by'] == $loggedUserId) {
                // Ambil bank soal buat dicek teacher_id-nya
                if ($selectedBank['teacher_id'] != $user['teacher_id']) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Bank soal tidak valid.',
                        'errors' => [
                            'bank_id' => 'Anda hanya bisa memilih bank soal yang Anda buat.'
                        ]
                    ]);
                }
            }
        }


        // -------------------------------------------------------
        // 8. Siapkan data update
        // -------------------------------------------------------
        $update = [
            'bank_id' => (int) $bankId,
            'exam_name_id' => (int) $post['exam_name_id'],
            'class_codes' => json_encode($post['class_codes']),
            'semester' => $post['semester'],
            'subject_type' => $post['subject_type'] ?? 'umum',
            'religion' => $post['religion'] ?? null,
            'show_pg_count' => $show_pg_count,
            'show_pg_kompleks_count' => $show_pgk_count,
            'show_bs_count' => $show_bs_count,
            'show_esai_count' => $show_esai_count,
            'bobot_pg' => $bobot_pg,
            'bobot_pg_kompleks' => $bobot_pgk,
            'bobot_bs' => $bobot_bs,
            'bobot_esai' => $bobot_esai,
            'shuffle_question' => $post['shuffle_question'] ?? 'tidak',
            'shuffle_option' => $post['shuffle_option'] ?? 'tidak',
            'finish_button_lock' => $post['finish_button_lock'] ?? '0',
            'start_time' => $post['start_time'],
            'duration' => (int) $post['duration'],
            'end_time' => $post['end_time'],
            'show_token' => $post['show_token'] ?? 'tidak',
            'token' => $token,
            'show_score' => $post['show_score'] ?? 'tidak',
            'anti_cheat' => ($post['anti_cheat'] == 'none' || empty($post['anti_cheat'])) ? 'tidak' : $post['anti_cheat'],
            'audio_limit' => (int) ($post['audio_limit'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // -------------------------------------------------------
        // 9. Simpan
        // -------------------------------------------------------
        try {
            $this->testStatusModel->update($id, $update);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Jadwal ujian berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'UPDATE ERROR: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan ke database.',
                'errors' => [
                    'database' => $e->getMessage()
                ]
            ]);
        }
    }


    /* -----------------------------------------------------
     * Fungsi Toggle (Pause & Visible) — sudah aman
     * ----------------------------------------------------- */
    public function togglePause($id)
    {
        $test = $this->testStatusModel->find($id);
        if (!$test)
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        $this->testStatusModel->update($id, ['is_paused' => !$test['is_paused']]);
        return redirect()->back()->with('success', 'Status ujian diperbarui.');
    }

    public function toggleVisible($id)
    {
        $test = $this->testStatusModel->find($id);
        if (!$test)
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        $this->testStatusModel->update($id, ['is_visible' => !$test['is_visible']]);
        return redirect()->back()->with('success', 'Tampilan ujian diperbarui.');
    }

    /* -----------------------------------------------------
     * Hapus Jadwal (termasuk data nilai & hasil ujian)
     * ----------------------------------------------------- */
    public function delete($id)
    {
        // TODO: tambahkan penghapusan data hasil ujian di sini bila tabelnya sudah siap
        $this->testStatusModel->delete($id);
        return redirect()->back()->with('success', 'Jadwal ujian berhasil dihapus.');
    }

}
