<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtBankSoalModel;
use App\Models\CbtQuestionModel;
use Dompdf\Dompdf;

class CbtBankSoal extends BaseController
{
    protected $bankModel;
    protected $questionModel;

    public function __construct()
    {
        $this->bankModel = new CbtBankSoalModel();
        $this->questionModel = new CbtQuestionModel();
        helper('cbt');
    }


    public function index()
    {
        $context = get_cbt_user_context();

        if ($context['is_admin']) {
            $banks = $this->bankModel->getListWithCounts();
        } elseif ($context['is_teacher'] && $context['teacher_id']) {
            $banks = $this->bankModel->getListWithCounts('teacher', $context['teacher_id']);
        } else {
            $banks = [];
        }

        // Lengkapi creator_name
        foreach ($banks as &$bank) {
            $bank['creator_name'] = !empty($bank['teacher_name'])
                ? $bank['teacher_name']
                : 'Admin';
        }

        // Get subjects - filter untuk guru
        $subjectModel = new \App\Models\SubjectModel();
        if ($context['is_admin']) {
            $subjects = $subjectModel->orderBy('name', 'ASC')->findAll();
        } elseif ($context['is_teacher'] && $context['teacher_id']) {
            $subjects = get_teacher_subjects($context['teacher_id']);
        } else {
            $subjects = [];
        }

        return view('admin/cbt/banksoal/index', [
            'title' => 'Daftar Bank Soal',
            'banks' => $banks,
            'subjects' => $subjects,
            'context' => $context,
        ]);
    }



    public function storeAjax()
    {
        try {
            if (!$this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);
            }

            $db = db_connect();
            $school = $db->table('school_profile')->select('level')->get()->getRow();

            $levelName = match ((int) ($school->level ?? 0)) {
                1 => 'SD',
                2 => 'SMP',
                default => 'N/A'
            };

            // Get user context
            $context = get_cbt_user_context();
            $teacherId = $context['teacher_id'];

            $subjectId = $this->request->getPost('subject_id');

            // Ambil nama pembuat
            $creatorName = session()->get('user')['fullname'] ?? 'Admin';
            if ($teacherId) {
                $creator = $db->table('teachers')->select('name')->where('id', $teacherId)->get()->getRow();
                if ($creator) {
                    $creatorName = $creator->name;
                }
            }

            $data = [
                'code' => $this->request->getPost('code'),
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'level' => $levelName,
                'total_questions' => 0,
                'total_pg' => 0,
                'total_pg_kompleks' => 0,
                'total_bs' => 0,
                'total_esai' => 0,
                'option_count' => $this->request->getPost('option_count'),
                'is_active' => 0,
            ];

            $id = $this->bankModel->insert($data);

            if ($this->bankModel->errors()) {
                throw new \Exception(implode(', ', $this->bankModel->errors()));
            }

            $subject = $db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRow();
            $subjectName = $subject ? $subject->name : '(Tidak diketahui)';

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Bank soal berhasil dibuat',
                'data' => array_merge($data, [
                    'id' => $id,
                    'subject_name' => $subjectName,
                    'creator_name' => $creatorName,
                ])
            ]);
        } catch (\Throwable $th) {
            log_message('error', '[CbtBankSoal::storeAjax] ' . $th->getMessage() . "\n" . $th->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'error' => 'Terjadi kesalahan server: ' . $th->getMessage()
            ]);
        }
    }


    public function updateAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);
        }

        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $teacherId = $user['teacher_id'] ?? null;

        $id = $this->request->getPost('id');
        $bank = $this->bankModel->find($id);

        if (!$bank) {
            return $this->response->setJSON(['success' => false, 'error' => 'Bank soal tidak ditemukan']);
        }

        /* ==========================================================
         * 🔒 PROTEKSI AKSES EDIT:
         * Guru hanya boleh edit bank soal miliknya.
         * ========================================================== */
        if ($roleId == 2 && $bank['teacher_id'] != $teacherId) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Anda tidak memiliki akses untuk mengedit bank soal ini.'
            ]);
        }

        /* ==========================================================
         * UPDATE DATA
         * ========================================================== */
        $data = [
            'code' => $this->request->getPost('code'),
            'subject_id' => $this->request->getPost('subject_id'),
            'option_count' => $this->request->getPost('option_count'),
        ];

        $updated = $this->bankModel->update($id, $data);

        if (!$updated) {
            return $this->response->setJSON(['success' => false, 'error' => 'Gagal memperbarui data']);
        }

        // Ambil ulang data untuk mengirim ke frontend
        $bank = $this->bankModel
            ->select('cbt_question_banks.*, subjects.name AS subject_name')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->find($id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Bank soal berhasil diperbarui',
            'data' => $bank,
        ]);
    }



    public function detail($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $bank = $this->bankModel
            ->select('cbt_question_banks.*, subjects.name AS subject_name')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->find($id);

        if (!$bank) {
            return redirect()->back()->with('error', 'Bank soal tidak ditemukan.');
        }

        $questions = $this->questionModel->where('bank_id', $id)->findAll();

        return view('admin/cbt/banksoal/detail', [
            'title' => 'Rincian Bank Soal',
            'bank' => $bank,
            'questions' => $questions
        ]);
    }

    public function copy($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $source = $this->bankModel->find($id);
        if (!$source)
            return redirect()->back()->with('error', 'Bank soal tidak ditemukan.');

        $newData = $source;
        unset($newData['id']);
        $newData['code'] = $source['code'] . '_COPY';
        $newData['is_active'] = 0;

        $newId = $this->bankModel->insert($newData);

        // Copy all questions
        $questions = $this->questionModel->where('bank_id', $id)->findAll();
        foreach ($questions as $q) {
            unset($q['id']);
            $q['bank_id'] = $newId;
            $this->questionModel->insert($q);
        }

        return redirect()->to('/admin/cbt/banksoal')->with('success', 'Bank soal berhasil disalin.');
    }

    public function print($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $bank = $this->bankModel->getWithSubjectTeacher($id);
        if (!$bank) {
            return redirect()->back()->with('error', 'Bank soal tidak ditemukan.');
        }

        $questionsRaw = $this->questionModel->where('bank_id', $id)->findAll();
        $questions = [];

        foreach ($questionsRaw as $q) {
            $parsed = $this->questionModel->parseRawQuestion($q['raw_text'] ?? $q['question_text']);
            $q['question_text_clean'] = $parsed['question'];
            $questions[] = $q;
        }

        $html = view('admin/cbt/banksoal/print', compact('bank', 'questions'));

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream('bank-soal-' . $id . '.pdf');
        exit;
    }

    public function edit_Soal($bankId, $questionId)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($bankId)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $bank = $this->bankModel->find($bankId);
        $soal = $this->questionModel->find($questionId);

        if (!$bank || !$soal) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Soal tidak ditemukan');
        }

        return view('admin/cbt/banksoal/edit_soal', [
            'title' => 'Edit Soal',
            'bank' => $bank,
            'soal' => $soal,
        ]);
    }

    public function updateSoal($bankId, $questionId)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($bankId)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $rawHtml = $this->request->getPost('raw_text');

        if (empty($rawHtml)) {
            return $this->response->setJSON(['error' => 'Soal tidak boleh kosong.']);
        }

        // Process embedded images
        $cleanHtml = $this->processEmbeddedImages($rawHtml);

        // Parse HTML to extract question data
        $parsedQuestions = $this->extractQuestionsFromHtml($cleanHtml);

        if (empty($parsedQuestions)) {
            return $this->response->setJSON(['error' => 'Tidak ada soal valid yang ditemukan dalam format yang diberikan.']);
        }

        // Use first parsed question (since we're editing one question at a time)
        $q = $parsedQuestions[0];

        // Prepare data for update
        $data = [
            'question_text' => trim($q['question']),
            'option_a' => $q['options']['A'] ?? null,
            'option_b' => $q['options']['B'] ?? null,
            'option_c' => $q['options']['C'] ?? null,
            'option_d' => $q['options']['D'] ?? null,
            'option_e' => $q['options']['E'] ?? null,
            'correct_option' => $q['key'] ?? null,
            'question_type' => $q['type'],
            'score' => $q['type'] === 'esai' ? 0 : 1,
            'media_image' => json_encode($q['images'] ?? []),
            'has_image' => !empty($q['images']) ? 1 : 0,
            'raw_text' => $rawHtml,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Handle audio file uploads
        $audioFiles = ['audio_file', 'audio_a', 'audio_b', 'audio_c', 'audio_d', 'audio_e'];
        foreach ($audioFiles as $field) {
            $file = $this->request->getFile($field);
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move(UPLOAD_PATH . 'audio', $newName);

                if ($field === 'audio_file') {
                    $data['media_audio'] = $newName;
                    $data['has_audio'] = 1;
                } else {
                    $data[$field] = $newName;
                }
            }
        }

        // Update the question
        $this->questionModel->update($questionId, $data);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Soal berhasil diperbarui.'
        ]);
    }

    public function deleteAudio($questionId, $type = 'main')
    {
        $soal = $this->questionModel->find($questionId);
        if (!$soal)
            return $this->response->setJSON(['success' => false, 'message' => 'Soal tidak ditemukan']);

        $field = ($type === 'main') ? 'media_audio' : 'audio_' . $type;

        if (!empty($soal[$field])) {
            $path = UPLOAD_PATH . 'audio/' . $soal[$field];
            if (file_exists($path)) {
                unlink($path);
            }

            $update = [$field => null];
            if ($type === 'main') {
                $update['has_audio'] = 0;
            }
            $this->questionModel->update($questionId, $update);
        }
        return $this->response->setJSON(['success' => true, 'message' => 'Audio berhasil dihapus.']);
    }

    public function deleteQuestionAjax()
    {
        log_message('debug', '[CbtBankSoal::deleteQuestionAjax] Attempting delete for ID: ' . $this->request->getPost('id'));
        $id = $this->request->getPost('id');
        $bankId = $this->request->getPost('bank_id');

        if (empty($id) || empty($bankId)) {
            return $this->response->setJSON(['error' => 'Data tidak lengkap.']);
        }

        // 🔒 Pastikan soal memang milik bank soal ini
        $question = $this->questionModel
            ->where('id', $id)
            ->where('bank_id', $bankId)
            ->first();

        if (!$question) {
            return $this->response->setJSON(['error' => 'Soal tidak ditemukan atau tidak sesuai bank.']);
        }

        // 🧹 Hapus soal
        $this->questionModel->delete($id);

        // 🔁 Update ringkasan bank soal
        $totalQuestions = $this->questionModel->where('bank_id', $bankId)->countAllResults();
        $totalPG = $this->questionModel->where(['bank_id' => $bankId, 'question_type' => 'pg'])->countAllResults();
        $totalPGK = $this->questionModel->where(['bank_id' => $bankId, 'question_type' => 'pg_kompleks'])->countAllResults();
        $totalBS = $this->questionModel->where(['bank_id' => $bankId, 'question_type' => 'benar_salah'])->countAllResults();
        $totalEsai = $this->questionModel->where(['bank_id' => $bankId, 'question_type' => 'esai'])->countAllResults();

        $this->bankModel->update($bankId, [
            'total_questions' => $totalQuestions,
            'total_pg' => $totalPG,
            'total_pg_kompleks' => $totalPGK,
            'total_bs' => $totalBS,
            'total_esai' => $totalEsai,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Soal berhasil dihapus.',
            csrf_token() => csrf_hash()
        ]);
    }

    /**
     * 🔄 Hitung ulang statistik bank setelah hapus soal
     */
    private function recountBankStats($bankId)
    {
        $questions = $this->questionModel->where('bank_id', $bankId)->findAll();

        $total = count($questions);
        $pg = count(array_filter($questions, fn($q) => $q['question_type'] === 'pg'));
        $pgk = count(array_filter($questions, fn($q) => $q['question_type'] === 'pg_kompleks'));
        $bs = count(array_filter($questions, fn($q) => $q['question_type'] === 'benar_salah'));
        $esai = count(array_filter($questions, fn($q) => $q['question_type'] === 'esai'));

        $this->bankModel->update($bankId, [
            'total_questions' => $total,
            'total_pg' => $pg,
            'total_pg_kompleks' => $pgk,
            'total_bs' => $bs,
            'total_esai' => $esai,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }


    public function updateQuestion($id)
    {
        $db = \Config\Database::connect();
        $data = $this->request->getPost();
        $update = [
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'score' => $data['score'],
            'option_a' => $data['option_a'] ?? null,
            'option_b' => $data['option_b'] ?? null,
            'option_c' => $data['option_c'] ?? null,
            'option_d' => $data['option_d'] ?? null,
            'option_e' => $data['option_e'] ?? null,
            'correct_option' => $data['correct_option'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $db->table('cbt_questions')->where('id', $id)->update($update);
        return $this->response->setJSON(['success' => true, 'message' => 'Soal berhasil diperbarui.']);
    }


    public function delete($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $this->questionModel->where('bank_id', $id)->delete();
        $this->bankModel->delete($id);
        return redirect()->to('/admin/cbt/banksoal')->with('success', 'Bank soal berhasil dihapus.');
    }


    public function bulkDelete()
    {
        $ids = $this->request->getPost('ids');
        if (!$ids)
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');

        // Validasi ownership untuk setiap ID
        foreach ($ids as $id) {
            if (!can_access_cbt_bank($id)) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke salah satu bank soal yang dipilih.');
            }
        }

        foreach ($ids as $id) {
            $this->questionModel->where('bank_id', $id)->delete();
            $this->bankModel->delete($id);
        }

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }


    public function toggle($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke bank soal ini.'
            ]);
        }

        $bank = $this->bankModel->find($id);
        if (!$bank)
            return redirect()->back()->with('error', 'Data tidak ditemukan.');

        $this->bankModel->update($id, ['is_active' => !$bank['is_active']]);
        return redirect()->back()->with('success', 'Status berhasil diperbarui.');
    }


    public function deleteQuestion($bankId, $questionId)
    {
        $this->questionModel->delete($questionId);
        return redirect()->to("/admin/cbt/banksoal/detail/$bankId")->with('success', 'Soal berhasil dihapus.');
    }

    public function addQuestionAjax()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);

        $data = [
            'bank_id' => $this->request->getPost('bank_id'),
            'question_text' => $this->request->getPost('question_text'),
            'question_type' => $this->request->getPost('question_type'),
            'score' => $this->request->getPost('score')
        ];

        $this->questionModel->insert($data);
        $this->updateBankTotals($data['bank_id']);

        return $this->response->setJSON(['success' => true, 'message' => 'Soal berhasil ditambahkan']);
    }

    public function updateQuestionAjax()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);

        $id = $this->request->getPost('id');
        $bankId = $this->request->getPost('bank_id');

        $data = [
            'question_text' => $this->request->getPost('question_text'),
            'question_type' => $this->request->getPost('question_type'),
            'score' => $this->request->getPost('score')
        ];

        $this->questionModel->update($id, $data);
        $this->updateBankTotals($bankId);

        return $this->response->setJSON(['success' => true, 'message' => 'Soal berhasil diperbarui']);
    }

    private function updateBankTotals($bankId)
    {
        $total = $this->questionModel->where('bank_id', $bankId)->countAllResults();

        $totalPg = $this->questionModel
            ->where('bank_id', $bankId)
            ->where('question_type', 'pg')
            ->countAllResults();

        $totalPgk = $this->questionModel
            ->where('bank_id', $bankId)
            ->where('question_type', 'pg_kompleks')
            ->countAllResults();

        $totalBs = $this->questionModel
            ->where('bank_id', $bankId)
            ->where('question_type', 'benar_salah')
            ->countAllResults();

        $totalEsai = $this->questionModel
            ->where('bank_id', $bankId)
            ->where('question_type', 'esai')
            ->countAllResults();

        $this->bankModel->update($bankId, [
            'total_questions' => $total,
            'total_pg' => $totalPg,
            'total_pg_kompleks' => $totalPgk,
            'total_bs' => $totalBs,
            'total_esai' => $totalEsai
        ]);
    }

    public function tambahSoal($bankId)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($bankId)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $bank = $this->bankModel
            ->select('cbt_question_banks.*, subjects.name as subject_name')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->where('cbt_question_banks.id', $bankId)
            ->first();

        if (!$bank)
            return redirect()->back()->with('error', 'Bank soal tidak ditemukan.');

        $schoolModel = new \App\Models\SchoolModel();
        $school = $schoolModel->getProfile();
        $apiKey = $school['tinymce_api_key'] ?? 'no-key';

        return view('admin/cbt/banksoal/tambah_soal', [
            'title' => 'Tambah Soal - ' . $bank['code'],
            'bank' => $bank,
            'apiKey' => $apiKey
        ]);
    }

    public function parseSoal()
    {
        if (!$this->request->isAJAX())
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);

        $rawText = $this->request->getPost('text');
        $bankId = $this->request->getPost('bank_id');

        // Validasi ownership
        if ($bankId && !can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        if (!$rawText) {
            return $this->response->setJSON(['success' => false, 'error' => 'Teks soal kosong.']);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
        $parsed = [];
        $current = [];
        $type = 'pg';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '')
                continue;

            // Deteksi awal soal
            if (preg_match('/^Soal:(\d+)\)(.*)/i', $line, $m)) {
                if (!empty($current))
                    $parsed[] = $current;
                $current = [
                    'nomor' => trim($m[1]),
                    'text' => trim($m[2]),
                    'options' => [],
                    'answer' => '',
                    'type' => 'pg'
                ];
                continue;
            }

            // Deteksi opsi jawaban (A: ..., B: ..., dst)
            if (preg_match('/^[A-Ea-e]:/', $line)) {
                $optKey = strtoupper(substr($line, 0, 1));
                $optText = trim(substr($line, 2));
                $current['options'][$optKey] = $optText;
                continue;
            }

            // Deteksi kunci jawaban
            if (preg_match('/^Kunci:(.*)/i', $line, $m)) {
                $key = strtoupper(trim($m[1]));
                $current['answer'] = $key;
                $current['type'] = ($key === 'ESAI') ? 'esai' : 'pg';
                continue;
            }
        }

        if (!empty($current))
            $parsed[] = $current;

        if (empty($parsed)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Format tidak dikenali. Pastikan format mengikuti contoh.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'count' => count($parsed),
            'preview' => $parsed
        ]);
    }

    /**
     * Simpan hasil parsing dari TinyMCE ke database
     */
    public function saveParsedSoal($bankId)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        $rawHtml = $this->request->getPost('raw_text');
        if (empty($rawHtml)) {
            return $this->response->setJSON(['error' => 'Tidak ada data soal yang dikirim.']);
        }

        try {
            // 🔧 Simpan naskah mentah ke bank soal
            $this->bankModel->update($bankId, [
                'raw_text' => $rawHtml,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 🧹 Bersihkan & proses gambar
            $cleanHtml = $this->processEmbeddedImages($rawHtml);

            // 🧩 Parsing HTML menjadi array soal
            $parsedQuestions = $this->extractQuestionsFromHtml($cleanHtml);
            if (empty($parsedQuestions)) {
                return $this->response->setJSON(['error' => 'Tidak ada soal valid yang ditemukan.']);
            }

            $dataBatch = [];
            $pgCount = 0;
            $pgkCount = 0;
            $bsCount = 0;
            $esaiCount = 0;

            foreach ($parsedQuestions as $q) {
                $dataBatch[] = [
                    'bank_id' => $bankId,
                    'question_text' => trim($q['question']),
                    'option_a' => $q['options']['A'] ?? null,
                    'option_b' => $q['options']['B'] ?? null,
                    'option_c' => $q['options']['C'] ?? null,
                    'option_d' => $q['options']['D'] ?? null,
                    'option_e' => $q['options']['E'] ?? null,
                    'correct_option' => $q['key'] ?? null,
                    'question_type' => $q['type'],
                    'essay_answer' => $q['type'] === 'esai' ? null : '',
                    'score' => $q['type'] === 'esai' ? 0 : 1,
                    'media_image' => json_encode($q['images'] ?? []),
                    'has_image' => !empty($q['images']) ? 1 : 0,
                    'has_audio' => 0,
                    'raw_text' => $q['raw_html'] ?? null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if ($q['type'] === 'pg')
                    $pgCount++;
                elseif ($q['type'] === 'pg_kompleks')
                    $pgkCount++;
                elseif ($q['type'] === 'benar_salah')
                    $bsCount++;
                else
                    $esaiCount++;
            }

            // 🚀 Insert batch
            $this->questionModel->insertBatch($dataBatch);

            // 🔄 Update ringkasan bank
            $this->bankModel->update($bankId, [
                'total_questions' => $pgCount + $pgkCount + $bsCount + $esaiCount,
                'total_pg' => $pgCount,
                'total_pg_kompleks' => $pgkCount,
                'total_bs' => $bsCount,
                'total_esai' => $esaiCount,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            return $this->response->setJSON([
                'success' => true,
                'message' => "Berhasil menyimpan {$pgCount} soal PG, {$bsCount} soal BS, dan {$esaiCount} soal esai."
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error saveParsedSoal: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Terjadi kesalahan saat menyimpan soal: ' . $e->getMessage()
            ]);
        }
    }


    private function extractQuestionsFromHtml($html)
    {
        // 🔹 Pecah berdasarkan penanda "Soal: n)"
        $blocks = preg_split('/Soal:\s*\d+\)/i', $html);
        $questions = [];

        foreach ($blocks as $b) {
            $b = trim($b);
            if (strlen(strip_tags($b)) < 10)
                continue;

            $rawHtmlBlock = $b; // simpan HTML utuh blok ini

            // 🔹 Normalisasi baris
            $normalized = preg_replace('/<p[^>]*>/i', "\n", $b);
            $normalized = preg_replace('/<br[^>]*>/i', "\n", $normalized);

            // 🔹 Simpan dulu <img> agar tidak hilang
            preg_match_all('/<img[^>]+>/i', $normalized, $imgTags);
            $imgList = $imgTags[0] ?? [];
            $tmp = preg_replace('/<img[^>]+>/i', '[[IMG]]', $normalized);

            // 🔹 Bersihkan HTML lain
            $clean = strip_tags($tmp, '');
            $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $clean = str_replace(["\r", "\xc2\xa0", "&nbsp;"], ' ', $clean);

            // 🔹 Kembalikan tag <img> ke tempat semula
            foreach ($imgList as $tag) {
                $clean = preg_replace('/\[\[IMG\]\]/', $tag, $clean, 1);
            }

            $lines = array_values(array_filter(array_map('trim', explode("\n", $clean))));
            $questionLines = [];
            $options = [];
            $key = null;
            $type = 'pg';

            foreach ($lines as $line) {
                if (preg_match('/^([A-E])\s*[:.)]\s*(.+)$/iu', $line, $m)) {
                    // Opsi (bisa teks atau <img>)
                    $options[strtoupper($m[1])] = trim($m[2]);
                } elseif (preg_match('/^Tipe\s*[:.)]?\s*(.+)$/i', $line, $mTipe)) {
                    $tipeVal = strtolower(trim($mTipe[1]));
                    if (preg_match('/(bs|benar\s*salah|benar\/salah)/', $tipeVal)) {
                        $type = 'benar_salah';
                    } elseif (preg_match('/(pgk|kompleks)/', $tipeVal)) {
                        $type = 'pg_kompleks';
                    } elseif (preg_match('/(esai|uraian)/', $tipeVal)) {
                        $type = 'esai';
                    }
                } elseif (preg_match('/^Kunci\s*[:.)]?\s*(esai|.*)/i', $line, $m)) {
                    $rawKey = trim($m[1]);
                    if (strtolower($rawKey) === 'esai') {
                        $type = 'esai';
                    } elseif ($type === 'benar_salah') {
                        // For Benar/Salah, we preserve the sequence exactly (e.g., B,S,B)
                        // Normalize: remove spaces, keep only B and S, then comma-separate if not already
                        $rawKey = strtoupper(preg_replace('/\s+/', '', $rawKey));
                        // If it contains commas, we trust the manual formatting
                        if (strpos($rawKey, ',') !== false) {
                            $key = $rawKey;
                        } else {
                            // Otherwise, split every character
                            $chars = str_split($rawKey);
                            $key = implode(',', $chars);
                        }
                    } else {
                        // Extract multiple keys (e.g., "A, B, C" or "A B C")
                        preg_match_all('/[A-E]/i', $rawKey, $keyMatches);
                        if (!empty($keyMatches[0])) {
                            $uniqueKeys = array_unique(array_map('strtoupper', $keyMatches[0]));
                            sort($uniqueKeys);
                            $key = implode(',', $uniqueKeys);

                            // IF explicitly set to pg_kompleks via Tipe:, keep it.
                            // OR if multiple keys detected, auto-switch to pg_kompleks
                            if ($type === 'pg_kompleks' || count($uniqueKeys) > 1) {
                                $type = 'pg_kompleks';
                            }
                        }
                    }
                } else {
                    $questionLines[] = $line;
                }
            }

            // 🔹 Gabungkan teks pertanyaan
            $questionText = trim(preg_replace('/\s+/', ' ', implode(' ', $questionLines)));

            // 🔹 Deteksi otomatis esai (jika tidak ada opsi dan bukan BS)
            if (empty($options) && $type !== 'benar_salah') {
                $type = 'esai';
            }

            // 🔹 Kumpulkan URL gambar (opsional)
            preg_match_all('/<img[^>]+src="([^">]+)"/i', $b, $imgMatches);
            $images = $imgMatches[1] ?? [];

            $questions[] = [
                'question' => $questionText,
                'options' => $options,
                'key' => $key,
                'type' => $type,
                'images' => $images,
                'raw_html' => $rawHtmlBlock,
            ];
        }

        return $questions;
    }


    /**
     * ⚙️ Proses semua gambar base64 atau URL dari TinyMCE
     * - Simpan ke /uploads/soal_images/
     * - Deteksi duplikat berdasarkan hash
     * - Auto resize gambar > 1MB
     * - Return HTML dengan src baru berbasis base_url()
     */
    private function processEmbeddedImages($html)
    {
        $uploadDir = UPLOAD_PATH . 'soal_images/';
        
        // Detect current protocol to avoid mixed content issues
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $currentBaseUrl = base_url();
        
        // Replace protocol in base_url if needed
        if (strpos($currentBaseUrl, 'http://') === 0 && $protocol === 'https') {
            $currentBaseUrl = str_replace('http://', 'https://', $currentBaseUrl);
        } elseif (strpos($currentBaseUrl, 'https://') === 0 && $protocol === 'http') {
            $currentBaseUrl = str_replace('https://', 'http://', $currentBaseUrl);
        }
        
        $baseUrl = rtrim($currentBaseUrl, '/') . '/uploads/soal_images/';
        $hashCache = [];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        preg_match_all('/<img[^>]+src="([^">]+)"/i', $html, $matches);
        $srcList = $matches[1] ?? [];

        foreach ($srcList as $src) {
            $newUrl = '';

            // 🧩 CASE 1: base64 (TinyMCE paste)
            if (strpos($src, 'data:image') === 0) {
                if (preg_match('/data:image\/(\w+);base64,/', $src, $typeMatch)) {
                    $ext = strtolower($typeMatch[1]);
                } else {
                    $ext = 'png';
                }

                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed))
                    $ext = 'png';

                $data = explode(',', $src);
                $decoded = base64_decode(end($data));
                if (!$decoded)
                    continue;

                $hash = sha1($decoded);
                if (isset($hashCache[$hash])) {
                    $html = str_replace($src, $hashCache[$hash], $html);
                    continue;
                }

                $fileName = 'img_' . substr($hash, 0, 10) . '.' . $ext;
                $filePath = $uploadDir . $fileName;

                file_put_contents($filePath, $decoded);

                // 🧠 Auto resize jika > 1MB
                if (filesize($filePath) > 1024 * 1024) {
                    $this->resizeImage($filePath, $ext, 1280); // max width 1280px
                }

                $newUrl = $baseUrl . $fileName;
                $hashCache[$hash] = $newUrl;
                $html = str_replace($src, $newUrl, $html);
            }

            // 🌍 CASE 2: URL eksternal
            elseif (preg_match('/^https?:\/\//i', $src)) {
                $fileName = 'img_' . uniqid() . '.png';
                $filePath = $uploadDir . $fileName;

                try {
                    $imgData = @file_get_contents($src, false, stream_context_create([
                        'http' => ['timeout' => 5]
                    ]));

                    if ($imgData !== false) {
                        file_put_contents($filePath, $imgData);

                        if (filesize($filePath) > 1024 * 1024) {
                            $this->resizeImage($filePath, 'png', 1280);
                        }

                        $newUrl = $baseUrl . $fileName;
                        $html = str_replace($src, $newUrl, $html);
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Gagal ambil gambar eksternal: ' . $e->getMessage());
                }
            }
        }

        return $html;
    }

    /**
     * 🖼️ Resize gambar besar agar tidak memberatkan sistem
     * Gunakan GD library bawaan PHP (tidak perlu ekstensi tambahan)
     */
    private function resizeImage(string $path, string $ext, int $maxWidth = 1280): bool
    {
        if (!file_exists($path))
            return false;

        [$width, $height] = getimagesize($path);
        if ($width <= $maxWidth)
            return true; // tidak perlu resize

        $ratio = $height / $width;
        $newWidth = $maxWidth;
        $newHeight = (int) ($newWidth * $ratio);

        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                $srcImage = imagecreatefromjpeg($path);
                break;
            case 'png':
                $srcImage = imagecreatefrompng($path);
                break;
            case 'gif':
                $srcImage = imagecreatefromgif($path);
                break;
            case 'webp':
                $srcImage = imagecreatefromwebp($path);
                break;
            default:
                return false;
        }

        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($dstImage, $path, 85);
                break;
            case 'png':
                imagepng($dstImage, $path, 6);
                break;
            case 'gif':
                imagegif($dstImage, $path);
                break;
            case 'webp':
                imagewebp($dstImage, $path, 85);
                break;
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return true;
    }


    public function previewParsedSoal()
    {
        $rawHtml = $this->request->getPost('raw_text');
        $parsed = $this->extractQuestionsFromHtml($rawHtml);
        return $this->response->setJSON($parsed);
    }

    public function uploadImage()
    {
        helper(['filesystem', 'text']);

        $file = $this->request->getFile('file');

        // 1. Validasi keberadaan dan validitas file (termasuk deteksi MIME)
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['error' => 'File tidak valid atau sudah dipindahkan.']);
        }

        // 2. Validasi ekstensi dan MIME type (Double check)
        $allowedMimes = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return $this->response->setJSON(['error' => 'Tipe file tidak diizinkan. Hanya gambar yang diperbolehkan.']);
        }

        // 3. Validasi konten file (cek apakah benar-benar gambar)
        if (!getimagesize($file->getTempName())) {
            return $this->response->setJSON(['error' => 'Isi file bukan gambar yang valid.']);
        }

        // Folder upload
        $uploadPath = UPLOAD_PATH . 'soal_images/';

        // Pastikan folder ada
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0775, true)) {
                return $this->response->setJSON(['error' => 'Gagal membuat folder upload.']);
            }
        }

        // Simpan dengan nama unik
        $newName = $file->getRandomName();
        
        try {
            if (!$file->move($uploadPath, $newName)) {
                return $this->response->setJSON(['error' => 'Gagal memindahkan file.']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Upload image error: ' . $e->getMessage());
            return $this->response->setJSON(['error' => 'Gagal upload: ' . $e->getMessage()]);
        }

        // Verify file exists
        if (!file_exists($uploadPath . $newName)) {
            return $this->response->setJSON(['error' => 'File tidak ditemukan setelah upload.']);
        }

        // Buat URL publik relatif ke base_url()
        // Detect current protocol to avoid mixed content issues
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $currentBaseUrl = base_url();
        
        // Replace protocol in base_url if needed
        if (strpos($currentBaseUrl, 'http://') === 0 && $protocol === 'https') {
            $currentBaseUrl = str_replace('http://', 'https://', $currentBaseUrl);
        } elseif (strpos($currentBaseUrl, 'https://') === 0 && $protocol === 'http') {
            $currentBaseUrl = str_replace('https://', 'http://', $currentBaseUrl);
        }
        
        $fileUrl = rtrim($currentBaseUrl, '/') . '/uploads/soal_images/' . $newName;

        return $this->response->setJSON([
            'location' => $fileUrl,  // Untuk kompatibilitas TinyMCE (legacy)
            'url' => $fileUrl,       // Format CKEditor 5
        ]);
    }

    public function backup($id)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
        }

        $bank = $this->bankModel->find($id);
        if (!$bank) {
            return redirect()->back()->with('error', 'Bank soal tidak ditemukan.');
        }

        // Cleanup old backups in public/uploads/backups
        $backupDir = FCPATH . 'uploads/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $files = glob($backupDir . 'backup_bank_*.zip');
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file) > 3600)) { // Delete files older than 1 hour
                unlink($file);
            }
        }

        $questions = $this->questionModel->where('bank_id', $id)->findAll();

        $data = [
            'bank' => $bank,
            'questions' => $questions,
            'backup_at' => date('Y-m-d H:i:s'),
            'base_url' => base_url()
        ];

        $zip = new \ZipArchive();
        $zipName = 'backup_bank_' . $bank['code'] . '_' . date('Ymd_His') . '.zip';
        $zipPath = FCPATH . 'uploads/backups/' . $zipName;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuat file backup.');
        }

        // Add data.json
        $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT));

        // Add media files
        foreach ($questions as $q) {
            // Images from media_image field
            $images = json_decode($q['media_image'], true) ?? [];
            foreach ($images as $img) {
                // If it's a full URL, try to get the relative path
                $relativePath = str_replace(base_url(), '', $img);
                $relativePath = ltrim($relativePath, '/');
                $fullPath = FCPATH . $relativePath;

                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, UPLOAD_PATH . '' . $relativePath);
                }
            }

            // Audio files
            $audioFields = ['media_audio', 'audio_a', 'audio_b', 'audio_c', 'audio_d', 'audio_e'];
            foreach ($audioFields as $field) {
                if (!empty($q[$field])) {
                    $audioPath = UPLOAD_PATH . 'audio/' . $q[$field];
                    $fullAudioPath = FCPATH . $audioPath;
                    if (file_exists($fullAudioPath)) {
                        $zip->addFile($fullAudioPath, $audioPath);
                    }
                }
            }

            // Also check for images in question_text (TinyMCE)
            preg_match_all('/<img[^>]+src="([^">]+)"/i', $q['question_text'], $matches);
            $imgSrcs = $matches[1] ?? [];
            foreach ($imgSrcs as $src) {
                if (strpos($src, base_url()) !== false) {
                    $relativePath = str_replace(base_url(), '', $src);
                    $relativePath = ltrim($relativePath, '/');
                    $fullPath = FCPATH . $relativePath;
                    if (file_exists($fullPath)) {
                        $zip->addFile($fullPath, UPLOAD_PATH . '' . $relativePath);
                    }
                }
            }
        }

        $zip->close();

        return $this->response->download($zipPath, null);
    }

    public function restore()
    {
        $context = get_cbt_user_context();
        
        $file = $this->request->getFile('backup_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getTempName()) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuka file ZIP.');
        }

        $dataJson = $zip->getFromName('data.json');
        if (!$dataJson) {
            $zip->close();
            return redirect()->back()->with('error', 'File data.json tidak ditemukan dalam ZIP.');
        }

        $data = json_decode($dataJson, true);
        if (!$data || !isset($data['bank']) || !isset($data['questions'])) {
            $zip->close();
            return redirect()->back()->with('error', 'Format data.json tidak valid.');
        }

        $oldBaseUrl = $data['base_url'] ?? '';
        $newBaseUrl = base_url();

        // ========================================
        // VALIDASI: Guru hanya bisa restore mapel yang diampu
        // ========================================
        if ($context['is_teacher'] && $context['teacher_id']) {
            // Cek apakah guru sudah memiliki teaching assignment
            $teacherSubjects = get_teacher_subjects($context['teacher_id']);
            
            if (empty($teacherSubjects)) {
                $zip->close();
                log_message('warning', "Restore blocked: Teacher {$context['teacher_id']} has no teaching assignments");
                
                return redirect()->back()->with('error', 
                    "Anda belum memiliki tugas mengajar (plotting pembelajaran). " .
                    "Silakan hubungi admin untuk menambahkan tugas mengajar Anda terlebih dahulu sebelum melakukan restore bank soal."
                );
            }
            
            $subjectId = $data['bank']['subject_id'] ?? null;
            
            if (!$subjectId) {
                $zip->close();
                return redirect()->back()->with('error', 'Bank soal tidak memiliki informasi mata pelajaran.');
            }
            
            // Cek apakah guru mengampu mata pelajaran ini
            $teacherSubjectIds = array_column($teacherSubjects, 'id');
            
            if (!in_array($subjectId, $teacherSubjectIds)) {
                // Ambil nama mata pelajaran untuk pesan error yang jelas
                $db = \Config\Database::connect();
                $subject = $db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRow();
                $subjectName = $subject ? $subject->name : 'Unknown';
                
                // Buat daftar mata pelajaran yang diampu
                $teacherSubjectNames = array_column($teacherSubjects, 'name');
                $subjectList = implode(', ', $teacherSubjectNames);
                
                $zip->close();
                log_message('warning', "Restore blocked: Teacher {$context['teacher_id']} tried to restore subject {$subjectId} ({$subjectName}) which they don't teach");
                
                return redirect()->back()->with('error', 
                    "Bank soal tidak bisa di-restore karena bukan mata pelajaran yang Anda ampu. " .
                    "Bank soal ini untuk mata pelajaran: <strong>{$subjectName}</strong>. " .
                    "Mata pelajaran yang Anda ampu: <strong>{$subjectList}</strong>."
                );
            }
            
            log_message('info', "Restore validation passed: Teacher {$context['teacher_id']} can restore subject {$subjectId}");
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Prepare Bank Soal Data
            $bankData = $data['bank'];
            unset($bankData['id']);

            // PENTING: Guru yang restore menjadi pemilik bank soal (bisa dari backup guru lain)
            if ($context['is_teacher'] && $context['teacher_id']) {
                $bankData['teacher_id'] = $context['teacher_id'];
                log_message('info', "Restore: Setting teacher_id to {$context['teacher_id']} (Teacher restore)");
            } elseif ($context['is_admin']) {
                // Admin bisa restore dengan teacher_id asli atau null
                // Jika tidak ada teacher_id di backup, set null
                if (!isset($bankData['teacher_id'])) {
                    $bankData['teacher_id'] = null;
                }
                log_message('info', "Restore: Admin restore, teacher_id = " . ($bankData['teacher_id'] ?? 'NULL'));
            } else {
                log_message('warning', "Restore: No valid context, teacher_id not set");
            }

            // Handle code collision
            $originalCode = $bankData['code'];
            $count = 0;
            while ($this->bankModel->where('code', $bankData['code'])->countAllResults() > 0) {
                $count++;
                $bankData['code'] = $originalCode . '_RESTORED_' . $count;
            }

            $bankData['created_at'] = date('Y-m-d H:i:s');
            $bankData['updated_at'] = date('Y-m-d H:i:s');
            $bankData['is_active'] = 0;

            // Filter bank data to only allowed fields
            $bankAllowedFields = $this->bankModel->allowedFields;
            $filteredBankData = [];
            foreach ($bankData as $key => $value) {
                if (in_array($key, $bankAllowedFields)) {
                    $filteredBankData[$key] = $value;
                }
            }

            $newBankId = $this->bankModel->insert($filteredBankData);

            if (!$newBankId) {
                throw new \Exception('Gagal menyimpan bank soal ke database.');
            }

            // 2. Extract and Copy Media Files
            $uploadPath = FCPATH . 'uploads/';
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Skip data.json
                if ($filename === 'data.json') {
                    continue;
                }

                // SECURITY: Sanitize path and prevent traversal
                if (strpos($filename, '..') !== false) {
                    log_message('warning', 'Restore: Skipping file with path traversal: ' . $filename);
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    log_message('warning', 'Restore: Failed to extract file: ' . $filename);
                    continue;
                }

                // Clean filename
                $cleanFilename = str_replace(['\\'], ['/'], $filename);
                $targetPath = $uploadPath . ltrim($cleanFilename, '/');

                // Ensure directory exists
                $dir = dirname($targetPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                // Write file
                file_put_contents($targetPath, $content);
            }

            // 3. Process Questions
            $allowedFields = $this->questionModel->allowedFields;
            $questionCount = 0;
            
            foreach ($data['questions'] as $q) {
                unset($q['id']);
                $q['bank_id'] = $newBankId;

                // Normalize URLs in question_text
                if (!empty($oldBaseUrl)) {
                    $q['question_text'] = str_replace($oldBaseUrl, $newBaseUrl, $q['question_text'] ?? '');
                    $q['raw_text'] = str_replace($oldBaseUrl, $newBaseUrl, $q['raw_text'] ?? '');
                }

                // Normalize media_image URLs
                $images = json_decode($q['media_image'] ?? '[]', true) ?? [];
                $newImages = [];
                foreach ($images as $img) {
                    $newImages[] = str_replace($oldBaseUrl, $newBaseUrl, $img);
                }
                $q['media_image'] = json_encode($newImages);

                // Normalize media_audio URLs
                if (!empty($q['media_audio']) && !empty($oldBaseUrl)) {
                    $q['media_audio'] = str_replace($oldBaseUrl, $newBaseUrl, $q['media_audio']);
                }

                // Filter hanya field yang ada di allowedFields
                $filteredData = [];
                foreach ($q as $key => $value) {
                    if (in_array($key, $allowedFields)) {
                        $filteredData[$key] = $value;
                    }
                }

                // Pastikan field wajib ada
                if (empty($filteredData['question_text'])) {
                    log_message('warning', 'Restore: Skipping question without question_text');
                    continue;
                }

                $inserted = $this->questionModel->insert($filteredData);
                if ($inserted) {
                    $questionCount++;
                } else {
                    log_message('error', 'Restore: Failed to insert question: ' . json_encode($filteredData));
                }
            }

            $zip->close();

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Transaksi database gagal.');
            }

            log_message('info', "Restore success: Bank ID={$newBankId}, Code={$filteredBankData['code']}, Teacher ID={$filteredBankData['teacher_id']}, Questions={$questionCount}");

            return redirect()->to('/admin/cbt/banksoal')->with('success', "Bank soal berhasil di-restore dengan kode: {$filteredBankData['code']} ({$questionCount} soal)");

        } catch (\Exception $e) {
            $db->transRollback();
            $zip->close();
            log_message('error', 'Restore error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal restore: ' . $e->getMessage());
        }
    }

}


