<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\QuizConfigModel;
use App\Models\QuizSessionModel;
use App\Models\AcademicYearModel;
use App\Services\CbtQuestionService;

/**
 * Kuis Mandiri — Portal Siswa
 *
 * Alur:
 *   GET  /siswa/quiz                  → index   : daftar kuis tersedia
 *   GET  /siswa/quiz/{id}             → detail  : info kuis + riwayat attempt
 *   POST /siswa/quiz/{id}/mulai       → start   : buat sesi baru / lanjutkan sesi aktif
 *   GET  /siswa/quiz/kerjakan/{sid}   → kerjakan: halaman pengerjaan
 *   POST /siswa/quiz/save-answer      → AJAX    : simpan satu/bulk jawaban
 *   POST /siswa/quiz/{sid}/submit     → submit  : hitung skor, tutup sesi
 *   GET  /siswa/quiz/hasil/{sid}      → hasil   : lihat skor + pembahasan
 */
class Quiz extends BaseController
{
    protected QuizConfigModel  $quizModel;
    protected QuizSessionModel $sessionModel;
    protected CbtQuestionService $questionService;
    protected AcademicYearModel $yearModel;
    protected $db;

    public function __construct()
    {
        $this->quizModel       = new QuizConfigModel();
        $this->sessionModel    = new QuizSessionModel();
        $this->questionService = new CbtQuestionService();
        $this->yearModel       = new AcademicYearModel();
        $this->db              = \Config\Database::connect();
        helper('cache');
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function studentId(): int
    {
        $u = session()->get('user') ?? [];
        return (int) ($u['student_id'] ?? $u['related_id'] ?? 0);
    }

    private function activeRecord(int $studentId): ?array
    {
        $year = $this->yearModel->getActiveYear();
        if (!$year) return null;
        return $this->db->table('student_records')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $year['id'])
            ->where('status', 'aktif')
            ->get()->getRowArray() ?: null;
    }

    // =========================================================
    // SHOW — landing dari notifikasi lama (GET /siswa/quiz/{id})
    // Redirect ke halaman yang benar berdasarkan material_id
    // =========================================================
    public function show(int $quizId): \CodeIgniter\HTTP\RedirectResponse
    {
        $quiz = $this->quizModel->find($quizId);

        if (!$quiz || !$quiz['is_published']) {
            return redirect()->to('siswa/belajar')
                ->with('error', 'Kuis tidak ditemukan.');
        }

        // Jika kuis terkait sub materi → ke halaman materi
        if (!empty($quiz['material_id'])) {
            return redirect()->to('siswa/belajar/sub/' . $quiz['material_id'] . '#kuis');
        }

        // Fallback: kuis mandiri tanpa sub materi → ke halaman belajar
        return redirect()->to('siswa/belajar');
    }

    // =========================================================
    // INDEX — daftar kuis tersedia
    // =========================================================
    public function index(): string
    {
        $studentId = $this->studentId();
        $record    = $this->activeRecord($studentId);
        $classId   = (int) ($record['class_id'] ?? 0);

        $quizzes   = $classId ? $this->quizModel->getForClass($classId) : [];

        // Tambahkan info attempt + best_score per kuis
        foreach ($quizzes as &$q) {
            $q['attempts_done'] = $this->sessionModel->countFinished($q['id'], $studentId);
            $q['best_score']    = $this->sessionModel->bestScore($q['id'], $studentId);
            $q['can_retry']     = ($q['max_attempts'] == 0) || ($q['attempts_done'] < $q['max_attempts']);
            $q['active_session']= $this->sessionModel->getActive($q['id'], $studentId);
        }
        unset($q);

        // Kelompokkan per mapel
        $grouped = [];
        foreach ($quizzes as $q) {
            $subjectName = $q['subject_name'] ?? 'Lainnya';
            $grouped[$subjectName][] = $q;
        }

        return view('siswa/quiz/index', [
            'title'   => 'Kuis Mandiri',
            'grouped' => $grouped,
        ]);
    }

    // =========================================================
    // START — buat sesi baru atau lanjutkan sesi aktif
    // POST /siswa/quiz/{id}/mulai
    // =========================================================
    public function start(int $quizId): \CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        if (!$studentId) {
            return redirect()->to('siswa/quiz')->with('error', 'Sesi tidak valid.');
        }

        $quiz = $this->quizModel->getDetail($quizId);
        if (!$quiz || !$quiz['is_published']) {
            return redirect()->to('siswa/quiz')->with('error', 'Kuis tidak ditemukan.');
        }

        // Validasi akses kelas
        if (!$this->_canAccess($studentId, $quiz)) {
            return redirect()->to('siswa/quiz')->with('error', 'Anda tidak memiliki akses ke kuis ini.');
        }

        // Cek apakah ada sesi aktif → lanjutkan
        $active = $this->sessionModel->getActive($quizId, $studentId);
        if ($active) {
            return redirect()->to('siswa/quiz/kerjakan/' . $active['id']);
        }

        // Cek batas attempt
        $done = $this->sessionModel->countFinished($quizId, $studentId);
        if ($quiz['max_attempts'] > 0 && $done >= $quiz['max_attempts']) {
            return redirect()->to('siswa/quiz')
                ->with('error', 'Anda sudah mencapai batas maksimal pengerjaan kuis ini.');
        }

        // Buat sesi baru
        $sessionId = $this->_createSession($quiz, $studentId, $done + 1);
        if (!$sessionId) {
            return redirect()->to('siswa/quiz')->with('error', 'Gagal memulai kuis. Pastikan bank soal tidak kosong.');
        }

        return redirect()->to('siswa/quiz/kerjakan/' . $sessionId);
    }

    // =========================================================
    // KERJAKAN — halaman pengerjaan soal
    // GET /siswa/quiz/kerjakan/{sessionId}
    // =========================================================
    public function kerjakan(int $sessionId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $session   = $this->sessionModel->find($sessionId);

        if (!$session || (int) $session['student_id'] !== $studentId) {
            return redirect()->to('siswa/quiz')->with('error', 'Sesi tidak ditemukan.');
        }
        if ($session['status'] === 'finished') {
            return redirect()->to('siswa/quiz/hasil/' . $sessionId);
        }

        $quiz = $this->quizModel->getDetail((int) $session['quiz_id']);
        if (!$quiz) {
            return redirect()->to('siswa/quiz')->with('error', 'Kuis tidak ditemukan.');
        }

        // Cek timeout
        if ($quiz['duration'] > 0 && $session['started_at']) {
            $elapsed = time() - (int) $session['started_at'];
            if ($elapsed > ($quiz['duration'] * 60) + 30) {
                // Auto-submit
                $this->_doSubmit($session, $quiz);
                return redirect()->to('siswa/quiz/hasil/' . $sessionId)
                    ->with('info', 'Waktu habis. Kuis otomatis dikumpulkan.');
            }
        }

        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
        $optionOrders  = json_decode($session['option_orders']  ?? '{}', true) ?? [];
        $questions     = $this->_buildQuestionsForView($questionOrder, $optionOrders);

        // Ambil jawaban tersimpan
        $answerRows = $this->db->table('quiz_answers')
            ->where('session_id', $sessionId)
            ->get()->getResultArray();
        $savedAnswers = array_column($answerRows, 'answer', 'question_id');

        // Sisa waktu
        $remainingSeconds = 0;
        if ($quiz['duration'] > 0 && $session['started_at']) {
            $remainingSeconds = max(0, ($quiz['duration'] * 60) - (time() - (int) $session['started_at']));
        }

        return view('siswa/quiz/kerjakan', [
            'title'            => esc($quiz['title']),
            'quiz'             => $quiz,
            'session'          => $session,
            'questions'        => $questions,
            'savedAnswers'     => $savedAnswers,
            'remainingSeconds' => $remainingSeconds,
            'totalQuestions'   => count($questions),
        ]);
    }

    // =========================================================
    // SAVE ANSWER — AJAX: simpan satu jawaban
    // POST /siswa/quiz/save-answer
    // =========================================================
    public function saveAnswer(): \CodeIgniter\HTTP\Response
    {
        $studentId  = $this->studentId();
        $sessionId  = (int) $this->request->getPost('session_id');
        $questionId = (int) $this->request->getPost('question_id');
        $answer     = $this->request->getPost('answer') ?? '';

        // Validasi kepemilikan sesi
        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int) $session['student_id'] !== $studentId || $session['status'] !== 'active') {
            return $this->response->setJSON(['success' => false, 'message' => 'Sesi tidak valid.']);
        }

        $this->_upsertAnswer($sessionId, $questionId, $answer);

        return $this->response->setJSON(['success' => true]);
    }

    // =========================================================
    // SAVE BULK — AJAX: simpan banyak jawaban sekaligus
    // POST /siswa/quiz/save-bulk
    // =========================================================
    public function saveBulk(): \CodeIgniter\HTTP\Response
    {
        $studentId = $this->studentId();
        $payload   = $this->request->getJSON(true) ?? $this->request->getPost();
        $sessionId = (int) ($payload['session_id'] ?? 0);
        $answers   = $payload['answers'] ?? [];

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int) $session['student_id'] !== $studentId || $session['status'] !== 'active') {
            return $this->response->setJSON(['success' => false]);
        }

        foreach ($answers as $qid => $ans) {
            $this->_upsertAnswer($sessionId, (int) $qid, (string) $ans);
        }

        return $this->response->setJSON(['success' => true, 'saved' => count($answers)]);
    }

    // =========================================================
    // SUBMIT — hitung skor, tutup sesi
    // POST /siswa/quiz/{sessionId}/submit
    // =========================================================
    public function submit(int $sessionId): \CodeIgniter\HTTP\Response
    {
        $studentId = $this->studentId();
        $session   = $this->sessionModel->find($sessionId);

        if (!$session || (int) $session['student_id'] !== $studentId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sesi tidak valid.']);
        }
        if ($session['status'] === 'finished') {
            return $this->response->setJSON([
                'success'  => true,
                'redirect' => base_url('siswa/quiz/hasil/' . $sessionId),
            ]);
        }

        $quiz = $this->quizModel->getDetail((int) $session['quiz_id']);
        if (!$quiz) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kuis tidak ditemukan.']);
        }

        $score = $this->_doSubmit($session, $quiz);

        // Jika kuis terkait sub materi, arahkan ke halaman materi
        $backUrl = $quiz['material_id']
            ? base_url('siswa/belajar/sub/' . $quiz['material_id'] . '#kuis')
            : base_url('siswa/quiz/hasil/' . $sessionId);

        return $this->response->setJSON([
            'success'  => true,
            'score'    => $score,
            'redirect' => base_url('siswa/quiz/hasil/' . $sessionId),
            'back_url' => $backUrl,
        ]);
    }

    // =========================================================
    // HASIL — tampilkan skor + pembahasan
    // GET /siswa/quiz/hasil/{sessionId}
    // =========================================================
    public function hasil(int $sessionId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $session   = $this->sessionModel->find($sessionId);

        if (!$session || (int) $session['student_id'] !== $studentId) {
            return redirect()->to('siswa/quiz')->with('error', 'Data tidak ditemukan.');
        }
        if ($session['status'] !== 'finished') {
            return redirect()->to('siswa/quiz/kerjakan/' . $sessionId);
        }

        $quiz = $this->quizModel->getDetail((int) $session['quiz_id']);

        // Ambil jawaban + soal untuk pembahasan
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
        $questions     = empty($questionOrder) ? [] : $this->_buildQuestionsForView($questionOrder, []);

        $answerRows   = $this->db->table('quiz_answers')
            ->where('session_id', $sessionId)->get()->getResultArray();
        $answerMap    = array_column($answerRows, 'answer', 'question_id');

        // Hitung per-soal benar/salah untuk pembahasan
        $details      = [];
        $correctCount = 0;
        foreach ($questions as $q) {
            $qid       = $q['id'];
            $given     = $answerMap[$qid] ?? '';
            $correct   = $q['correct_option'] ?? '';
            $typeNorm  = $this->_normalizeType($q['question_type'] ?? 'pg');
            $isCorrect = $this->_checkAnswer($typeNorm, $given, $correct);
            if ($isCorrect) $correctCount++;
            $details[] = [
                'question'   => $q,
                'given'      => $given,
                'correct'    => $correct,
                'is_correct' => $isCorrect,
                'type_norm'  => $typeNorm,
            ];
        }

        // Riwayat attempt lain
        $history = $this->sessionModel->getHistory((int) $session['quiz_id'], $studentId);

        return view('siswa/quiz/hasil', [
            'title'        => 'Hasil Kuis: ' . esc($quiz['title'] ?? ''),
            'quiz'         => $quiz,
            'session'      => $session,
            'details'      => $details,
            'correctCount' => $correctCount,
            'totalCount'   => count($questions),
            'history'      => $history,
            'showAnswer'   => ($quiz['show_answer'] ?? 'ya') === 'ya',
            'backUrl'      => $quiz['material_id']
                                ? base_url('siswa/belajar/sub/' . $quiz['material_id'] . '#kuis')
                                : base_url('siswa/quiz'),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function _canAccess(int $studentId, array $quiz): bool
    {
        $classIds = json_decode($quiz['class_ids'] ?? '[]', true) ?? [];
        if (empty($classIds)) return true; // semua kelas

        $record = $this->activeRecord($studentId);
        return $record && in_array((int) $record['class_id'], $classIds);
    }

    private function _createSession(array $quiz, int $studentId, int $attemptNo): int|false
    {
        // Map quiz config ke format yang dimengerti CbtQuestionService
        $testConfig = [
            'shuffle_question'       => $quiz['shuffle_question'],
            'show_pg_count'          => (int) $quiz['show_pg_count'],
            'show_pg_kompleks_count' => (int) $quiz['show_pgk_count'], // key service = pg_kompleks
            'show_bs_count'          => (int) $quiz['show_bs_count'],
            'show_esai_count'        => (int) $quiz['show_esai_count'],
        ];

        $questionOrder = $this->questionService->generateQuestionOrder((int) $quiz['bank_id'], $testConfig);
        if (empty($questionOrder)) return false;

        // Generate option orders
        $questions    = $this->questionService->getQuestionsInOrder($questionOrder);
        $shuffle      = ($quiz['shuffle_option'] ?? 'ya') === 'ya';
        $optionOrders = $this->questionService->generateOptionOrders($questions, $shuffle);

        $this->sessionModel->insert([
            'quiz_id'        => $quiz['id'],
            'student_id'     => $studentId,
            'question_order' => json_encode($questionOrder),
            'option_orders'  => json_encode($optionOrders),
            'status'         => 'active',
            'started_at'     => time(),
            'attempt_number' => $attemptNo,
        ]);

        return (int) $this->sessionModel->getInsertID();
    }

    private function _upsertAnswer(int $sessionId, int $questionId, string $answer): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO quiz_answers (session_id, question_id, answer, updated_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE answer = VALUES(answer), updated_at = VALUES(updated_at)',
            [$sessionId, $questionId, $answer, $now]
        );
    }

    private function _doSubmit(array $session, array $quiz): float
    {
        $sessionId     = (int) $session['id'];
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
        $questions     = $this->questionService->getQuestionsInOrder($questionOrder);

        $answerRows = $this->db->table('quiz_answers')
            ->where('session_id', $sessionId)->get()->getResultArray();
        $answerMap  = array_column($answerRows, 'answer', 'question_id');

        // Hitung skor berbobot (sama dengan rumus CBT)
        $stats = [
            'pg'   => ['total' => 0, 'earned' => 0.0, 'weight' => (float) ($quiz['bobot_pg']   ?? 100)],
            'pgk'  => ['total' => 0, 'earned' => 0.0, 'weight' => (float) ($quiz['bobot_pgk']  ?? 0)],
            'bs'   => ['total' => 0, 'earned' => 0.0, 'weight' => (float) ($quiz['bobot_bs']   ?? 0)],
            'esai' => ['total' => 0, 'earned' => 0.0, 'weight' => (float) ($quiz['bobot_esai'] ?? 0)],
        ];

        foreach ($questions as $q) {
            $type    = $this->_normalizeType($q['question_type'] ?? 'pg');
            $qid     = $q['id'];
            $correct = $q['correct_option'] ?? '';
            $given   = $answerMap[$qid] ?? '';

            if ($type === 'pg') {
                $stats['pg']['total']++;
                if ($correct !== '' && $given !== '' && strtoupper(trim($given)) === strtoupper(trim($correct))) {
                    $stats['pg']['earned'] += 1;
                }
            } elseif ($type === 'pgk') {
                $stats['pgk']['total']++;
                if (!empty($correct) && !empty($given)) {
                    $cArr  = explode(',', $correct);
                    $sArr  = explode(',', $given);
                    $right = count(array_intersect($sArr, $cArr));
                    $wrong = count(array_diff($sArr, $cArr));
                    $raw   = max(0, $right - 0.5 * $wrong);
                    $stats['pgk']['earned'] += count($cArr) > 0 ? $raw / count($cArr) : 0;
                }
            } elseif ($type === 'bs') {
                $stats['bs']['total']++;
                if (!empty($correct) && !empty($given)) {
                    $cArr  = explode(',', $correct);
                    $sArr  = explode(',', $given);
                    $total = count($cArr);
                    $match = 0;
                    for ($i = 0; $i < $total; $i++) {
                        $s = isset($sArr[$i]) ? strtoupper(trim($sArr[$i])) : '';
                        $k = strtoupper(trim($cArr[$i]));
                        if ($s !== '' && $s === $k) $match++;
                    }
                    $stats['bs']['earned'] += $total > 0 ? $match / $total : 0;
                }
            } else {
                $stats['esai']['total']++;
                // Esai tidak dinilai otomatis, tidak mengurangi total
            }
        }

        // Hitung total bobot yang aktif (exclude esai dan tipe yang 0 soal)
        $totalWeight = 0;
        foreach (['pg', 'pgk', 'bs'] as $k) {
            if ($stats[$k]['total'] > 0) {
                $totalWeight += $stats[$k]['weight'];
            }
        }

        // Jika semua bobot 0 atau tidak ada soal objektif → nilai = % benar sederhana
        $finalScore = 0.0;
        if ($totalWeight > 0) {
            foreach (['pg', 'pgk', 'bs'] as $k) {
                if ($stats[$k]['total'] > 0) {
                    $pct         = $stats[$k]['earned'] / $stats[$k]['total'] * 100;
                    $finalScore += $pct * ($stats[$k]['weight'] / $totalWeight);
                }
            }
        } else {
            // fallback: jumlah benar / total soal objektif
            $totalObj   = $stats['pg']['total'] + $stats['pgk']['total'] + $stats['bs']['total'];
            $totalRight = $stats['pg']['earned'] + $stats['pgk']['earned'] + $stats['bs']['earned'];
            $finalScore = $totalObj > 0 ? ($totalRight / $totalObj) * 100 : 0;
        }

        $finalScore = round($finalScore, 2);

        $this->sessionModel->update((int) $session['id'], [
            'status'      => 'finished',
            'finished_at' => time(),
            'score'       => $finalScore,
            'total_score' => $finalScore,
        ]);

        return $finalScore;
    }

    private function _buildQuestionsForView(array $questionOrder, array $optionOrders): array
    {
        if (empty($questionOrder)) return [];

        $questions = $this->questionService->getQuestionsInOrder($questionOrder);
        $result    = [];

        foreach ($questions as $q) {
            $qid      = $q['id'];
            $typeNorm = $this->_normalizeType($q['question_type'] ?? 'pg');

            // Bangun ordered options
            $rawOpts  = [];
            foreach (['A', 'B', 'C', 'D', 'E'] as $k) {
                $col = 'option_' . strtolower($k);
                if (!empty($q[$col])) $rawOpts[$k] = $q[$col];
            }
            $order      = $optionOrders[$qid] ?? array_keys($rawOpts);
            $orderedOpt = [];
            foreach ($order as $k) {
                if (isset($rawOpts[$k])) $orderedOpt[$k] = $rawOpts[$k];
            }
            // Soal BS: pisahkan pernyataan dari correct_option
            $bsStatements = [];
            if ($typeNorm === 'bs' && !empty($q['correct_option'])) {
                // Coba parse dari essay_answer (biasanya berisi pernyataan) atau option_a
                if (!empty($q['option_a'])) {
                    // Format: opsi A=pernyataan1, B=pernyataan2, dst
                    foreach ($rawOpts as $key => $val) {
                        $bsStatements[] = ['key' => $key, 'text' => $val];
                    }
                }
            }

            $q['options']       = $orderedOpt;
            $q['type_norm']     = $typeNorm;
            $q['bs_statements'] = $bsStatements;
            $q['media_images']  = json_decode($q['media_image'] ?? '[]', true) ?? [];
            $result[]           = $q;
        }

        return $result;
    }

    private function _normalizeType(string $raw): string
    {
        $raw = strtolower(str_replace(' ', '_', $raw));
        if (in_array($raw, ['pg_kompleks', 'pgk', 'pg_complex'])) return 'pgk';
        if (in_array($raw, ['benar_salah', 'bs', 'true_false'])) return 'bs';
        if (in_array($raw, ['esai', 'essay'])) return 'esai';
        return 'pg';
    }

    private function _checkAnswer(string $type, string $given, string $correct): bool
    {
        if ($given === '' || $correct === '') return false;
        if ($type === 'pg') {
            return strtoupper(trim($given)) === strtoupper(trim($correct));
        }
        if ($type === 'pgk') {
            $c = array_map('trim', explode(',', $correct));
            $s = array_map('trim', explode(',', $given));
            sort($c); sort($s);
            return $c === $s;
        }
        if ($type === 'bs') {
            $c = array_map('strtoupper', array_map('trim', explode(',', $correct)));
            $s = array_map('strtoupper', array_map('trim', explode(',', $given)));
            return $c === $s;
        }
        return false; // esai tidak diperiksa otomatis
    }
}
