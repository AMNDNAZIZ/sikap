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

        // 🛡️ Auto-normalisasi jika ada soal yang salah terdeteksi sebagai pg_kompleks
        // padahal kuncinya hanya berupa 1 opsi tunggal (tanpa koma)
        $needsSync = false;
        foreach ($questions as &$q) {
            if ($q['question_type'] === 'pg_kompleks') {
                $keys = array_filter(array_map('trim', explode(',', (string)($q['correct_option'] ?? ''))));
                if (count($keys) <= 1) {
                    $this->questionModel->update($q['id'], ['question_type' => 'pg']);
                    $q['question_type'] = 'pg';
                    $needsSync = true;
                }
            }
        }
        unset($q);

        if ($needsSync) {
            $this->updateBankTotals($id);
            $bank = $this->bankModel
                ->select('cbt_question_banks.*, subjects.name AS subject_name')
                ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
                ->find($id);
        }

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

        $bank = $this->bankModel
            ->select('cbt_question_banks.*, subjects.name AS subject_name')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->find($bankId);
        $soal = $this->questionModel->find($questionId);

        if (!$bank || !$soal || (int) $soal['bank_id'] !== (int) $bankId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Soal tidak ditemukan');
        }

        // Pastikan konten awal CKEditor lengkap (memuat soal, tipe, opsi A-E, dan kunci)
        // walaupun raw_text sebelumnya kosong atau berasal dari impor/generate lama
        $soal['editor_html'] = $this->buildCanonicalRawHtmlFromRecord($soal);

        return view('admin/cbt/banksoal/edit_soal', [
            'title' => 'Edit Soal',
            'bank' => $bank,
            'soal' => $soal,
        ]);
    }

    /**
     * Bangun HTML editor yang utuh & konsisten untuk CKEditor pada halaman edit_soal.
     * Jika raw_text sudah lengkap (memuat opsi/kunci yang sesuai), pertahankan format asli CKEditor
     * namun konversi awalan opsi "A. " di awal <p> menjadi "A: " agar CKEditor 5 tidak mengubahnya jadi <ol><li>.
     */
    private function buildCanonicalRawHtmlFromRecord(array $soal): string
    {
        $type = (string) ($soal['question_type'] ?? 'pg');
        $raw = trim((string) ($soal['raw_text'] ?? ''));

        $hasDbOptions = false;
        foreach (['a', 'b', 'c', 'd', 'e'] as $opt) {
            if (trim((string) ($soal['option_' . $opt] ?? '')) !== '') {
                $hasDbOptions = true;
                break;
            }
        }

        // Cek apakah raw_text sudah memiliki penanda Kunci/Opsi lengkap
        $rawHasKey = (bool) preg_match('/(?:Kunci\s*Jawaban|Kunci|Key|Ans)\s*[:.)=]/iu', strip_tags($raw));
        $rawHasOpts = !$hasDbOptions || (bool) preg_match('/(?:^|\n)\s*(?:\([A-Ea-e]\)|[A-Ea-e])\s*[:.)]/u', preg_replace('/<[^>]+>/', "\n", $raw));

        if ($raw !== '' && $rawHasKey && $rawHasOpts) {
            // Amankan awalan <p>A. ... menjadi <p>A: ... supaya CKEditor 5 Autoformat tidak mengubah opsi menjadi <ol><li>
            return preg_replace('/(<p[^>]*>\s*)([A-E])\.\s+/u', '$1$2: ', $raw);
        }

        // Rekonstruksi utuh dari kolom terstruktur database
        $qText = trim((string) ($soal['question_text'] ?? ''));
        if (preg_match('/<(?:p|div|table|ul|ol|blockquote|h[1-6]|img)\b/i', $qText)) {
            $html = $qText;
        } else {
            $paragraphs = preg_split('/\r?\n{2,}/', $qText) ?: [];
            $pHtml = [];
            foreach ($paragraphs as $p) {
                $pt = trim($p);
                if ($pt !== '') {
                    $pHtml[] = '<p>' . nl2br(htmlspecialchars($pt, ENT_NOQUOTES, 'UTF-8'), false) . '</p>';
                }
            }
            $html = !empty($pHtml) ? implode('', $pHtml) : '<p></p>';
        }

        if ($type === 'benar_salah') {
            $html .= '<p>Tipe: BS</p>';
        } elseif ($type === 'pg_kompleks') {
            $html .= '<p>Tipe: PGK</p>';
        } elseif ($type === 'pg') {
            $html .= '<p>Tipe: PG</p>';
        }

        if ($type !== 'esai') {
            foreach (['A', 'B', 'C', 'D', 'E'] as $optKey) {
                $val = trim((string) ($soal['option_' . strtolower($optKey)] ?? ''));
                if ($val !== '') {
                    $optFormatted = preg_match('/<[a-z][\s\S]*>/i', $val)
                        ? $val
                        : nl2br(htmlspecialchars($val, ENT_NOQUOTES, 'UTF-8'), false);
                    $html .= '<p>' . $optKey . ': ' . $optFormatted . '</p>';
                }
            }
            $keyVal = trim((string) ($soal['correct_option'] ?? ''));
            if ($keyVal !== '') {
                $html .= '<p>Kunci: ' . htmlspecialchars($keyVal, ENT_NOQUOTES, 'UTF-8') . '</p>';
            }
        } else {
            $html .= '<p>Kunci: esai</p>';
            $essayAns = trim((string) ($soal['essay_answer'] ?? ''));
            if ($essayAns !== '') {
                $html .= '<p>Jawaban: ' . nl2br(htmlspecialchars($essayAns, ENT_NOQUOTES, 'UTF-8'), false) . '</p>';
            }
        }

        return $html;
    }

    public function updateSoal($bankId, $questionId)
    {
        // Validasi ownership
        if (!can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        $existing = $this->questionModel->find($questionId);
        if (!$existing || (int) $existing['bank_id'] !== (int) $bankId) {
            return $this->response->setJSON(['success' => false, 'error' => 'Butir soal tidak ditemukan pada bank soal ini.']);
        }

        $rawHtml = $this->request->getPost('raw_text');

        if (empty(trim((string) $rawHtml))) {
            return $this->response->setJSON(['success' => false, 'error' => 'Soal tidak boleh kosong.']);
        }

        $explicitType = trim((string) $this->request->getPost('question_type'));
        $validTypes = ['pg', 'pg_kompleks', 'benar_salah', 'esai'];
        $forcedType = in_array($explicitType, $validTypes, true) ? $explicitType : null;

        // Process embedded images (ubah base64 menjadi URL file gambar lokal)
        $cleanHtml = $this->processEmbeddedImages($rawHtml);

        // Parse HTML dalam mode SOAL TUNGGAL ($singleQuestion = true) agar pernyataan bernomor 1. / 1) di dalam wacana TIDAK memotong soal!
        $parsedQuestions = $this->extractQuestionsFromHtml($cleanHtml, true, $forcedType);

        if (empty($parsedQuestions)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Tidak ada soal valid yang ditemukan dalam format yang diberikan.']);
        }

        $q = $parsedQuestions[0];

        $targetType = $forcedType ?? $q['type'];

        // Validasi kelengkapan opsi untuk soal objektif agar hasil simpan tidak rusak diam-diam
        if ($targetType !== 'esai' && empty($q['options'])) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Opsi jawaban (A, B, C, D) tidak terdeteksi. Pastikan setiap opsi berada di baris tersendiri dengan awalan A: / A. diikuti teks opsi.'
            ]);
        }

        $correctOption = $q['key'] ?? null;
        if ($targetType === 'esai') {
            $correctOption = null;
        } else {
            // Jika guru tidak mengubah/menulis ulang baris Kunci:, pertahankan kunci sebelumnya bila masih sesuai tipe
            if (($correctOption === null || $correctOption === '') && !empty($existing['correct_option']) && $existing['question_type'] === $targetType) {
                $correctOption = $existing['correct_option'];
            }
            if ($targetType === 'pg' && !empty($correctOption) && strpos($correctOption, ',') !== false) {
                $keyParts = array_filter(array_map('trim', explode(',', $correctOption)));
                $correctOption = reset($keyParts);
            }
        }

        $essayAnswer = null;
        if ($targetType === 'esai') {
            $essayAnswer = $q['essay_answer'] ?? $existing['essay_answer'] ?? null;
        }

        // Pertahankan skor khusus yang sudah ada kecuali berubah antara esai <-> objektif
        $existingScore = (float) ($existing['score'] ?? 1);
        if ($targetType === 'esai') {
            $newScore = ($existing['question_type'] === 'esai') ? $existingScore : 0;
        } else {
            $newScore = ($existing['question_type'] !== 'esai' && $existingScore > 0) ? $existingScore : 1;
        }

        // Bangun raw_text yang bersih & sinkron dengan tipe serta hasil edit (mempertahankan HTML & gambar soal)
        $syncedRecord = [
            'question_type'  => $targetType,
            'question_text'  => trim($q['question_html'] ?? $q['question']),
            'option_a'       => $targetType === 'esai' ? null : ($q['options']['A'] ?? null),
            'option_b'       => $targetType === 'esai' ? null : ($q['options']['B'] ?? null),
            'option_c'       => $targetType === 'esai' ? null : ($q['options']['C'] ?? null),
            'option_d'       => $targetType === 'esai' ? null : ($q['options']['D'] ?? null),
            'option_e'       => $targetType === 'esai' ? null : ($q['options']['E'] ?? null),
            'correct_option' => $correctOption,
            'essay_answer'   => $essayAnswer,
            'raw_text'       => '',
        ];
        $canonicalRawText = $this->buildCanonicalRawHtmlFromRecord($syncedRecord);

        // Prepare data for update
        $data = [
            'question_text'  => trim($q['question_html'] ?? $q['question']),
            'option_a'       => $syncedRecord['option_a'],
            'option_b'       => $syncedRecord['option_b'],
            'option_c'       => $syncedRecord['option_c'],
            'option_d'       => $syncedRecord['option_d'],
            'option_e'       => $syncedRecord['option_e'],
            'correct_option' => $correctOption,
            'essay_answer'   => $essayAnswer,
            'question_type'  => $targetType,
            'score'          => $newScore,
            'media_image'    => json_encode($q['images'] ?? []),
            'has_image'      => !empty($q['images']) ? 1 : 0,
            'raw_text'       => $canonicalRawText,
            'updated_at'     => date('Y-m-d H:i:s'),
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
        $this->updateBankTotals($bankId);
        cache()->delete('cbt_all_questions_bank_' . $bankId);
        cache()->delete('cbt_questions_bank_' . $bankId);

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
     * 🗑️ Hapus masal butir soal terpilih dari bank soal (AJAX)
     */
    public function bulkDeleteQuestionsAjax()
    {
        $ids = $this->request->getPost('ids');
        $bankId = $this->request->getPost('bank_id');

        if (empty($ids) || !is_array($ids) || empty($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Pilih minimal satu butir soal untuk dihapus.']);
        }

        // 🔒 Validasi ownership / hak akses bank soal
        if (!can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        // 🔒 Pastikan soal yang dihapus benar-benar milik bank soal ini
        $validQuestions = $this->questionModel
            ->where('bank_id', $bankId)
            ->whereIn('id', $ids)
            ->findAll();

        if (empty($validQuestions)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Tidak ada butir soal valid yang ditemukan untuk dihapus.']);
        }

        $validIds = array_column($validQuestions, 'id');

        // 🧹 Hapus soal secara masal
        $this->questionModel->whereIn('id', $validIds)->delete();

        // 🔁 Perbarui total statistik bank soal
        $this->updateBankTotals($bankId);

        return $this->response->setJSON([
            'success' => true,
            'message' => count($validIds) . ' butir soal berhasil dihapus secara masal.',
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

        // Update cache totals bank soal (tipe soal bisa berubah)
        $question = $db->table('cbt_questions')->where('id', $id)->get()->getRowArray();
        if ($question) {
            $this->updateBankTotals($question['bank_id']);
        }

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

    public function updateQuestionType()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'error' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $bankId = (int) $this->request->getPost('bank_id');
        $newType = trim((string) $this->request->getPost('type'));

        if (!can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        $allowed = ['pg', 'pg_kompleks', 'benar_salah', 'esai'];
        if (!in_array($newType, $allowed, true)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Tipe soal tidak valid.']);
        }

        $question = $this->questionModel->find($id);
        if (!$question || (int) $question['bank_id'] !== $bankId) {
            return $this->response->setJSON(['success' => false, 'error' => 'Soal tidak ditemukan.']);
        }

        $data = [
            'question_type' => $newType,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        // Jika diubah ke PG dan kunci memiliki koma (misal "A,B,D"), ambil hanya kunci pertama (misal "A")
        if ($newType === 'pg' && !empty($question['correct_option'])) {
            $keys = array_filter(array_map('trim', explode(',', (string)$question['correct_option'])));
            if (count($keys) > 1) {
                $data['correct_option'] = reset($keys);
            }
        }

        // Sinkronkan juga raw_text agar saat dibuka di Edit Soal atau Modal Lihat Soal tidak kembali ke tipe lama
        $mergedQuestion = array_merge($question, $data);
        $mergedQuestion['raw_text'] = '';
        $data['raw_text'] = $this->buildCanonicalRawHtmlFromRecord($mergedQuestion);

        $this->questionModel->update($id, $data);
        $this->updateBankTotals($bankId);
        cache()->delete('cbt_all_questions_bank_' . $bankId);
        cache()->delete('cbt_questions_bank_' . $bankId);

        $typeNames = [
            'pg' => 'Pilihan Ganda (PG)',
            'pg_kompleks' => 'PG Kompleks',
            'benar_salah' => 'Benar / Salah',
            'esai' => 'Esai / Uraian',
        ];

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Tipe soal berhasil diubah menjadi ' . ($typeNames[$newType] ?? $newType),
            'new_type' => $newType,
            'new_type_label' => $typeNames[$newType] ?? $newType,
            'new_key' => $data['correct_option'] ?? $question['correct_option']
        ]);
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
        $isSingle = (bool) $this->request->getPost('single');
        $explicitType = trim((string) $this->request->getPost('question_type'));
        $forcedType = in_array($explicitType, ['pg', 'pg_kompleks', 'benar_salah', 'esai'], true) ? $explicitType : null;

        // Validasi ownership
        if ($bankId && !can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Anda tidak memiliki akses ke bank soal ini.']);
        }

        if (!$rawText) {
            return $this->response->setJSON(['success' => false, 'error' => 'Teks soal kosong.']);
        }

        $parsedQuestions = $this->extractQuestionsFromHtml($rawText, $isSingle, $forcedType);
        if (empty($parsedQuestions)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Format tidak dikenali. Pastikan format mengikuti contoh.']);
        }

        $preview = [];
        foreach ($parsedQuestions as $q) {
            $preview[] = [
                'nomor'        => $q['nomor'] ?? (count($preview) + 1),
                'text'         => !empty($q['question_html']) ? $q['question_html'] : nl2br($q['question'], false),
                'options'      => $q['options'],
                'answer'       => $q['type'] === 'esai' ? ($q['essay_answer'] ?? '') : ($q['key'] ?? ''),
                'type'         => $q['type'],
                'essay_answer' => $q['essay_answer'] ?? ''
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'count'   => count($preview),
            'preview' => $preview,
            'csrf'    => csrf_hash()
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


    private function extractQuestionsFromHtml($html, bool $singleQuestion = false, ?string $forcedType = null)
    {
        // 🔹 1. Simpan blok <table> (beserta wrapper <figure class="table">) agar tabel di dalam soal tidak hancur
        $tableList = [];
        $workHtml = preg_replace_callback('/<figure[^>]*class="[^"]*table[^"]*"[^>]*>[\s\S]*?<\/figure>|<table\b[^>]*>[\s\S]*?<\/table>/iu', function ($m) use (&$tableList) {
            $idx = count($tableList);
            $tableList[$idx] = $m[0];
            return "\n[[TABLE_BLOCK_{$idx}]]\n";
        }, (string) $html);

        // 🔹 2. Konversi <ol> dan <ul> dari CKEditor 5 menjadi baris berpenanda eksplisit
        //       - <ol style="list-style-type:upper-alpha"> / type="A" -> A: ..., B: ..., dst.
        //       - <ol> biasa (daftar pernyataan bernomor di wacana) -> (1) ..., (2) ..., dst.
        //       - <ul> -> • ...
        $workHtml = preg_replace_callback('/<ol\b([^>]*)>([\s\S]*?)<\/ol>/iu', function ($m) {
            $attrs = strtolower($m[1] ?? '');
            $inner = $m[2] ?? '';
            $isAlpha = (strpos($attrs, 'alpha') !== false) || (bool) preg_match('/type\s*=\s*["\']?[ab]["\']?/i', $m[1] ?? '');
            preg_match_all('/<li\b[^>]*>([\s\S]*?)<\/li>/iu', $inner, $liMatches);
            $items = $liMatches[1] ?? [];
            $outLines = [];
            $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
            foreach ($items as $i => $itemHtml) {
                $itemClean = trim(preg_replace('/<\/?p[^>]*>/i', ' ', $itemHtml));
                if ($isAlpha && isset($letters[$i])) {
                    $outLines[] = '<p>' . $letters[$i] . ': ' . $itemClean . '</p>';
                } else {
                    $num = $i + 1;
                    $outLines[] = '<p>(' . $num . ') ' . $itemClean . '</p>';
                }
            }
            return implode("\n", $outLines);
        }, $workHtml);

        $workHtml = preg_replace_callback('/<ul\b[^>]*>([\s\S]*?)<\/ul>/iu', function ($m) {
            preg_match_all('/<li\b[^>]*>([\s\S]*?)<\/li>/iu', $m[1] ?? '', $liMatches);
            $outLines = [];
            foreach (($liMatches[1] ?? []) as $itemHtml) {
                $itemClean = trim(preg_replace('/<\/?p[^>]*>/i', ' ', $itemHtml));
                $outLines[] = '<p>• ' . $itemClean . '</p>';
            }
            return implode("\n", $outLines);
        }, $workHtml);

        // 🔹 3. Simpan tag <img> dengan placeholder berindeks
        $imgList = [];
        $tmp = preg_replace_callback('/<img[^>]+>/i', function ($m) use (&$imgList) {
            $idx = count($imgList);
            $imgList[$idx] = $m[0];
            return "[[IMG_TAG_PLACEHOLDER_{$idx}]]";
        }, $workHtml);

        // 🔹 4. Konversi tag-tag block & break HTML menjadi newline
        $normalized = preg_replace('/<(?:br\s*\/?)>/i', "\n", $tmp);
        $normalized = preg_replace('/<\/(?:p|div|li|h[1-6]|blockquote|section|article)>/i', "\n\n", $normalized);
        $normalized = preg_replace('/<(?:p|div|li|h[1-6]|blockquote|section|article)\b[^>]*>/i', "\n", $normalized);

        // 🔹 5. Pertahankan tag pemformatan teks inline penting (tebal, miring, garis bawah, sub/sup)
        $allowedInlineTags = '<strong><b><em><i><u><sub><sup>';
        $clean = strip_tags($normalized, $allowedInlineTags);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = str_replace(["\r", "\xc2\xa0", "&nbsp;"], ' ', $clean);

        // 🔹 6. Kembalikan tag <img> ke tempat semula
        foreach ($imgList as $idx => $tag) {
            $clean = str_replace("[[IMG_TAG_PLACEHOLDER_{$idx}]]", $tag, $clean);
        }

        // 🔹 7. KUNCI KETANGGUHAN (ROBUSTNESS) untuk copy-paste tersambung:
        if (!$singleQuestion) {
            $clean = preg_replace('/(?<!\n)\s*(?=(?:Soal\s*[:.]?\s*\d+[\).:]?))/iu', "\n\n", $clean);
        }
        // Lindungi baris Kunci/Tipe/Jawaban agar tidak terpecah jika nilai kunci ditulis "B." atau "(B)"
        $clean = preg_replace_callback('/((?:Kunci\s*Jawaban|Kunci|Jawaban|Tipe|Key|Ans)\s*[:.)=][^\n]*)/iu', function ($m) {
            return "\n[[KEY_PLACEHOLDER_" . base64_encode($m[1]) . "]]";
        }, $clean);
        // Pisahkan opsi A-E yang menyatu di baris yang sama hanya bila eksplisit berformat opsi di awal kalimat/setelah spasi
        if (!$singleQuestion) {
            $clean = preg_replace('/(?<!\n)(?<=\s|^|[>])(?=(?:[A-E]\s*[:.)]\s+\S|(?:\([A-E]\))\s*[:.)]?\s+\S))/u', "\n", $clean);
        }
        $clean = preg_replace_callback('/\[\[KEY_PLACEHOLDER_([^\]]+)\]\]/', function ($m) {
            return base64_decode($m[1]);
        }, $clean);

        $rawLines = explode("\n", $clean);
        $lines = [];
        foreach ($rawLines as $l) {
            $t = trim($l);
            if ($t !== '') {
                $lines[] = $t;
            }
        }

        $restoreTables = function (string $str) use ($tableList): string {
            foreach ($tableList as $idx => $tblHtml) {
                $str = str_replace("[[TABLE_BLOCK_{$idx}]]", $tblHtml, $str);
            }
            return $str;
        };

        $normalizeBsKey = function (string $rawKey): string {
            $tokens = preg_split('/[\s,;\/|]+/', trim($rawKey), -1, PREG_SPLIT_NO_EMPTY);
            if (count($tokens) > 1) {
                $mapped = [];
                foreach ($tokens as $tk) {
                    $low = strtolower(trim($tk, "().:"));
                    if (in_array($low, ['b', 'benar', 'true', 't', 'ya'], true)) {
                        $mapped[] = 'B';
                    } elseif (in_array($low, ['s', 'salah', 'false', 'f', 'tidak'], true)) {
                        $mapped[] = 'S';
                    }
                }
                if (!empty($mapped)) {
                    return implode(',', $mapped);
                }
            }
            $compact = strtoupper(preg_replace('/[^BSbs]/', '', $rawKey));
            if ($compact !== '') {
                return implode(',', str_split($compact));
            }
            return strtoupper(preg_replace('/\s+/', '', $rawKey));
        };

        $questions = [];
        $current = null;
        $lastOptionKey = null;
        $inEssayAnswer = false;

        $initQuestion = function (int $num, array $firstLines, string $rawLine) use ($forcedType): array {
            return [
                'nomor'          => $num,
                'question_lines' => $firstLines,
                'options'        => [],
                'key'            => null,
                'raw_key_string' => null,
                'essay_answer'   => null,
                'type'           => $forcedType ?? 'pg',
                'type_locked'    => ($forcedType !== null),
                'raw_html'       => $rawLine,
            ];
        };

        $flushQuestion = function () use (&$questions, &$current, $restoreTables, $normalizeBsKey, $forcedType) {
            if (!$current) {
                return;
            }

            // Pertahankan struktur baris / paragraf soal dengan \n\n agar wacana & daftar pernyataan tetap rapi
            $cleanQLines = [];
            $htmlParagraphs = [];
            foreach ($current['question_lines'] as $qLine) {
                $restored = trim($restoreTables($qLine));
                if ($restored === '') {
                    continue;
                }
                $cleanQLines[] = $restored;
                if (preg_match('/^<(?:table|figure)\b/i', $restored)) {
                    $htmlParagraphs[] = $restored;
                } else {
                    $htmlParagraphs[] = '<p>' . $restored . '</p>';
                }
            }

            $qText = trim(implode("\n\n", $cleanQLines));
            $qHtml = trim(implode('', $htmlParagraphs));

            $hasImgInQ = (stripos($qText, '<img') !== false) || (stripos($qHtml, '<img') !== false);
            if (!$hasImgInQ && strlen(trim(strip_tags($qText))) < 2 && empty($current['options'])) {
                $current = null;
                return;
            }

            $options = [];
            foreach ($current['options'] as $k => $v) {
                $options[$k] = trim($restoreTables($v));
            }

            $type = $forcedType ?? $current['type'];
            $typeLocked = ($forcedType !== null) || !empty($current['type_locked']);
            $rawKeyStr = $current['raw_key_string'];
            $key = $current['key'];
            $essayAnswer = $current['essay_answer'];

            // Tentukan tipe final terlebih dahulu
            if ($type === 'esai') {
                $key = null;
                if (($essayAnswer === null || $essayAnswer === '') && $rawKeyStr !== null && strtolower($rawKeyStr) !== 'esai') {
                    $essayAnswer = $rawKeyStr;
                }
            } elseif ($type === 'benar_salah') {
                if ($rawKeyStr !== null && strtolower($rawKeyStr) !== 'esai') {
                    $key = $normalizeBsKey($rawKeyStr);
                }
            } elseif (empty($options) && !$typeLocked) {
                $type = 'esai';
                if (($essayAnswer === null || $essayAnswer === '') && $rawKeyStr !== null && strtolower($rawKeyStr) !== 'esai') {
                    $essayAnswer = $rawKeyStr;
                }
                $key = null;
            } else {
                // PG atau PG Kompleks
                if ($rawKeyStr !== null && strtolower($rawKeyStr) !== 'esai') {
                    preg_match_all('/\b([A-Ea-e])\b/u', $rawKeyStr, $keyMatches);
                    $extractedKeys = !empty($keyMatches[1])
                        ? array_values(array_unique(array_map('strtoupper', $keyMatches[1])))
                        : [];

                    if (empty($extractedKeys) && preg_match('/^[A-Ea-e]{1,5}$/u', $rawKeyStr)) {
                        $extractedKeys = array_values(array_unique(str_split(strtoupper($rawKeyStr))));
                    }

                    if (!empty($extractedKeys)) {
                        sort($extractedKeys);
                        if (!$typeLocked && count($extractedKeys) > 1) {
                            $type = 'pg_kompleks';
                        }
                        if ($type === 'pg' && count($extractedKeys) > 1) {
                            $extractedKeys = [reset($extractedKeys)];
                        }
                        $key = implode(',', $extractedKeys);
                    }
                }
            }

            $rawRestored = $restoreTables($current['raw_html']);
            preg_match_all('/<img[^>]+src="([^">]+)"/i', $rawRestored . ' ' . $qHtml . ' ' . implode(' ', $options), $imgMatches);
            $images = array_values(array_unique($imgMatches[1] ?? []));

            $questions[] = [
                'nomor'         => $current['nomor'],
                'question'      => $qText,
                'question_html' => $qHtml,
                'options'       => $options,
                'key'           => $key,
                'essay_answer'  => $essayAnswer !== null ? trim($restoreTables($essayAnswer)) : null,
                'type'          => $type,
                'images'        => $images,
                'raw_html'      => $rawRestored,
            ];

            $current = null;
        };

        foreach ($lines as $line) {
            $plainLine = trim(strip_tags($line));

            // 1. Deteksi Awal Soal
            if ($singleQuestion) {
                // Mode edit 1 butir soal tunggal: JANGAN PERNAH memotong soal di tengah wacana!
                if ($current === null) {
                    $cleanedFirst = $line;
                    if (preg_match('/^(?:<[^>]+>)*\s*Soal\s*(?:No\.?|Nomor)?\s*[:.]?\s*(\d+)[\).:]?\s*(.*)$/iu', $line, $mFirst)) {
                        $num = (int) $mFirst[1];
                        $cleanedFirst = trim($mFirst[2]);
                    } else {
                        $num = 1;
                    }
                    $current = $initQuestion($num, $cleanedFirst !== '' ? [$cleanedFirst] : [], $line);
                    $lastOptionKey = null;
                    $inEssayAnswer = false;
                    continue;
                }
            } else {
                // Mode batch (tambah_soal):
                // a) Awalan eksplisit "Soal: 1)" / "Soal 1." selalu memulai soal baru
                if (preg_match('/^(?:<[^>]+>)*\s*Soal\s*(?:No\.?|Nomor)?\s*[:.]?\s*(\d+)[\).:]?\s*(.*)$/iu', $line, $mSoal)) {
                    $flushQuestion();
                    $num = (int) $mSoal[1];
                    $soalText = trim($mSoal[2]);
                    $current = $initQuestion($num, $soalText !== '' ? [$soalText] : [], $line);
                    $lastOptionKey = null;
                    $inEssayAnswer = false;
                    continue;
                }
                // b) Angka "1." atau "1)" tanpa kata "Soal" HANYA memulai soal baru jika $current belum ada
                //    ATAU soal sebelumnya sudah selesai mengumpulkan opsi/kunci (mencegah pernyataan 1. 2. 3. di wacana memotong soal!)
                $prevComplete = ($current !== null) && (!empty($current['options']) || $current['raw_key_string'] !== null || $current['essay_answer'] !== null);
                if (($current === null || $prevComplete) && preg_match('/^(?:<[^>]+>)*\s*(\d{1,3})[\).]\s*(.*)$/u', $line, $mNum)) {
                    $flushQuestion();
                    $num = (int) $mNum[1];
                    $soalText = trim($mNum[2]);
                    $current = $initQuestion($num, $soalText !== '' ? [$soalText] : [], $line);
                    $lastOptionKey = null;
                    $inEssayAnswer = false;
                    continue;
                }
            }

            if ($current === null) {
                $current = $initQuestion(count($questions) + 1, [$line], $line);
                $lastOptionKey = null;
                $inEssayAnswer = false;
                continue;
            }

            $current['raw_html'] .= "\n" . $line;

            // 2. Deteksi Tipe Soal (Tipe: BS / PGK / PG / Esai)
            if (preg_match('/^Tipe\s*[:.)=]?\s*(.+)$/iu', $plainLine, $mTipe)) {
                if ($forcedType === null) {
                    $tipeVal = strtolower(trim($mTipe[1]));
                    if (preg_match('/(bs|benar\s*salah|benar\/salah)/', $tipeVal)) {
                        $current['type'] = 'benar_salah';
                        $current['type_locked'] = true;
                    } elseif (preg_match('/(pgk|kompleks)/', $tipeVal)) {
                        $current['type'] = 'pg_kompleks';
                        $current['type_locked'] = true;
                    } elseif (preg_match('/(esai|essay|uraian)/', $tipeVal)) {
                        $current['type'] = 'esai';
                        $current['type_locked'] = true;
                    } elseif (preg_match('/(pg|pilihan\s*ganda|tunggal|single)/', $tipeVal)) {
                        $current['type'] = 'pg';
                        $current['type_locked'] = true;
                    }
                }
                $lastOptionKey = null;
                $inEssayAnswer = false;
                continue;
            }

            // 3. Deteksi Kunci Jawaban / Jawaban Esai
            if (preg_match('/^(Kunci\s*Jawaban|Kunci|Jawaban|Key|Ans)\s*[:.)=]\s*(.*)$/iu', $plainLine, $mKey)) {
                $labelLower = strtolower(preg_replace('/\s+/', ' ', trim($mKey[1])));
                $rawKey = trim($mKey[2]);

                if (strtolower($rawKey) === 'esai' || strtolower($rawKey) === 'essay' || strtolower($rawKey) === 'uraian') {
                    if ($forcedType === null) {
                        $current['type'] = 'esai';
                        $current['type_locked'] = true;
                    }
                    $current['raw_key_string'] = 'esai';
                    $lastOptionKey = null;
                    $inEssayAnswer = false;
                    continue;
                }

                // Jika soal bertipe esai (atau baris berlabel "Jawaban:" setelah "Kunci: esai"), simpan sebagai rubrik esai!
                if (($forcedType === 'esai') || ($current['type'] === 'esai') || ($labelLower === 'jawaban' && empty($current['options']))) {
                    if ($forcedType === null && $current['raw_key_string'] === 'esai') {
                        $current['type'] = 'esai';
                        $current['type_locked'] = true;
                    }
                    if (($forcedType === 'esai') || ($current['type'] === 'esai')) {
                        $current['essay_answer'] = $rawKey;
                        $lastOptionKey = null;
                        $inEssayAnswer = true;
                        continue;
                    }
                }

                $current['raw_key_string'] = $rawKey;
                $lastOptionKey = null;
                $inEssayAnswer = false;
                continue;
            }

            // 4. Deteksi Opsi Jawaban (A: ..., A. ..., A) ..., (A) ...)
            //    Catatan: Pada mode esai, jangan salah menganggap kalimat "A. ..." di rubrik sebagai opsi PG.
            if (($forcedType !== 'esai' && $current['type'] !== 'esai') && preg_match('/^(?:<[^>]+>)*\s*(?:\(([A-Ea-e])\)|([A-Ea-e]))\s*[:.)]\s*(.*)$/isu', $line, $mOpt)) {
                $optLetter = strtoupper(!empty($mOpt[1]) ? $mOpt[1] : $mOpt[2]);
                $optVal = trim($mOpt[3]);
                // Bersihkan tag penutup yatim di awal nilai opsi jika huruf opsi sebelumnya dibungkus <strong>A.</strong>
                $optVal = preg_replace('/^(?:<\/(?:strong|b|em|i|u)>)+/i', '', $optVal);
                $current['options'][$optLetter] = trim($optVal);
                $lastOptionKey = $optLetter;
                $inEssayAnswer = false;
                continue;
            }

            // 5. Konten lanjutan (multi-line essay answer, multi-line option, atau multi-line question/stimulus)
            if ($inEssayAnswer && $current['essay_answer'] !== null) {
                $current['essay_answer'] .= "\n" . $line;
            } elseif ($lastOptionKey !== null && isset($current['options'][$lastOptionKey])) {
                $current['options'][$lastOptionKey] .= '<br>' . $line;
            } else {
                $current['question_lines'][] = $line;
            }
        }

        $flushQuestion();
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

        // ── Normalisasi: pastikan setiap soal punya question_text & options terisi ──
        // Jika question_text kosong/pendek tapi raw_text ada, parse dari raw_text.
        // Ini membuat backup self-contained agar aplikasi CBT tidak perlu parsing
        // ulang (penyebab teks soal kosong saat restore).
        foreach ($questions as &$q) {
            $needParse = !empty($q['raw_text'])
                && (empty($q['question_text']) || mb_strlen(strip_tags($q['question_text'])) < 5);

            if ($needParse) {
                try {
                    $parsed = $this->questionModel->parseRawQuestion($q['raw_text']);
                    if (!empty($parsed['question'])) {
                        $q['question_text'] = trim($parsed['question']);
                    }
                    // (opsi & kunci diisi di bawah)
                    // Isi options dari hasil parse jika kolom option masih kosong
                    if (!empty($parsed['options'])) {
                        foreach (['A','B','C','D','E'] as $optKey) {
                            $col = 'option_' . strtolower($optKey);
                            if (empty($q[$col]) && !empty($parsed['options'][$optKey])) {
                                $q[$col] = $parsed['options'][$optKey];
                            }
                        }
                    }
                    // Isi correct_option jika kosong
                    if (empty($q['correct_option']) && !empty($parsed['key'])) {
                        $q['correct_option'] = is_array($parsed['key'])
                            ? implode(',', $parsed['key'])
                            : $parsed['key'];
                    }
                } catch (\Throwable $e) {
                    log_message('warning', '[Backup] Parse gagal QID ' . ($q['id'] ?? '?') . ': ' . $e->getMessage());
                }
            }

            // Kompatibilitas editor CBT: editor restore memuat raw_text lebih dulu.
            // Jika raw_text kosong, isi dari question_text agar tidak kosong saat diedit.
            if (empty(trim((string) ($q['raw_text'] ?? ''))) && !empty(trim((string) ($q['question_text'] ?? '')))) {
                $q['raw_text'] = $q['question_text'];
            }
        }
        unset($q);

        // Enrich bank data with subject info for cross-app compatibility
        $bankData = $bank;
        $db = \Config\Database::connect();
        $subject = $db->table('subjects')->select('name, code')->where('id', $bank['subject_id'])->get()->getRowArray();
        if ($subject) {
            $bankData['subject_name'] = $subject['name'];
            $bankData['subject_code'] = $subject['code'] ?? null;
        }

        $data = [
            'bank' => $bankData,
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
        // Flag lengkap: UNESCAPED_UNICODE agar karakter non-ASCII tidak rusak,
        // INVALID_UTF8_SUBSTITUTE agar json_encode tidak gagal (return false) saat
        // ada byte UTF-8 tidak valid di question_text/options (penyebab teks kosong)
        $jsonData = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($jsonData === false) {
            $zip->close();
            @unlink($zipPath);
            return redirect()->back()->with('error', 'Gagal encode data soal: ' . json_last_error_msg());
        }
        $zip->addFromString('data.json', $jsonData);

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

            // Smart subject matching (kompatibilitas dengan CBTku)
            $subjectModel = new \App\Models\SubjectModel();
            $oldSubjectCode = $bankData['subject_code'] ?? null;
            $oldSubjectName = $bankData['subject_name'] ?? null;

            if ($oldSubjectCode || $oldSubjectName) {
                $localSubject = null;
                if ($oldSubjectCode) {
                    $localSubject = $subjectModel->where('code', $oldSubjectCode)->first();
                }
                if (!$localSubject && $oldSubjectName) {
                    $localSubject = $subjectModel->where('name', $oldSubjectName)->first();
                }
                if ($localSubject) {
                    $bankData['subject_id'] = $localSubject['id'];
                }
            }

            // Clean up meta-fields yang bukan kolom DB
            unset($bankData['subject_name'], $bankData['subject_code'], $bankData['teacher_name']);

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


