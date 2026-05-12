<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtQuestionModel;
use App\Models\CbtAnswerModel;
use App\Models\ClassModel;
use App\Models\CbtSessionModel;
use App\Services\CbtAnswerService;
use App\Services\CbtScoringService;
use App\Services\CbtSessionService;
use App\Services\CbtQuestionService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class Cbt extends BaseController
{
    use ResponseTrait;

    protected $testModel;
    protected $studentModel;
    protected $classModel;
    protected $questionModel;
    protected $answerModel;
    protected $sessionModel;

    // Services
    protected $answerService;
    protected $scoringService;
    protected $sessionService;
    protected $questionService;

    public function __construct()
    {
        $this->testModel = new CbtTestStatusModel();
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->questionModel = new CbtQuestionModel();
        $this->answerModel = new CbtAnswerModel();
        $this->sessionModel = new CbtSessionModel();

        // Initialize services
        $this->answerService = new CbtAnswerService();
        $this->scoringService = new CbtScoringService();
        $this->sessionService = new CbtSessionService();
        $this->questionService = new CbtQuestionService();
    }

    protected function getStudentId()
    {
        $user = session()->get('user') ?? [];
        return session('student_id') ?? $user['student_id'] ?? $user['related_id'] ?? null;
    }

    // =========================
    // index() - daftar ujian
    // =========================
    public function index()
    {
        $studentId = $this->getStudentId();

        if (!$studentId) {
            return redirect()->to('/login')->with('error', 'Data siswa tidak ditemukan.');
        }

        $student = $this->studentModel
            ->select('students.*, classes.name AS class_name, users.username')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->orderBy('student_records.id', 'DESC')
            ->find($studentId);

        if (!$student) {
            return view('siswa/cbt/index', ['tests' => [], 'error' => 'Data siswa tidak ditemukan.']);
        }

        $className = $student['class_name'];
        $studentReligion = strtolower(trim($student['religion'] ?? ''));
        // Pastikan menggunakan timezone yang sesuai (Asia/Jakarta) agar sesuai dengan jadwal di DB
        $now = date('Y-m-d H:i:s');

        // --- OPTIMIZED LAZY AUTO-CLOSE LOGIC ---
        // Fetch sessions and test durations in one go to avoid N+1 queries
        $expiredCheck = $this->sessionModel
            ->select('cbt_sessions.*, t.duration, t.end_time AS test_end_time')
            ->join('cbt_test_status t', 't.id = cbt_sessions.test_id')
            ->where('cbt_sessions.student_id', $studentId)
            ->where('cbt_sessions.status', 'active')
            ->findAll();

        foreach ($expiredCheck as $s) {
            $startTs = (int) $s['started_at'];
            $durationSec = max(10, ((int) $s['duration']) * 60);
            $extraDuration = ((int) ($s['extra_time'] ?? 0)) * 60;
            $sessionEndTs = $startTs + $durationSec + $extraDuration;

            $testEndTs = !empty($s['test_end_time']) ? strtotime($s['test_end_time']) : 2147483647;
            $effectiveEndTs = min($sessionEndTs, $testEndTs);

            if (time() > $effectiveEndTs) {
                log_message('info', "[LazyAutoClose] Auto-closing test {$s['test_id']} for student $studentId");
                $this->internalSubmit($studentId, $s['test_id']);
            }
        }

        $tests = $this->testModel
            ->asArray()
            ->distinct()
            ->select('
                cbt_test_status.id AS test_id,
                cbt_test_status.bank_id,
                cbt_test_status.class_codes,
                cbt_test_status.token,
                cbt_test_status.show_token,
                cbt_test_status.start_time,
                cbt_test_status.end_time,
                cbt_test_status.is_visible,
                cbt_test_status.show_score,
                cbt_test_status.subject_type,
                cbt_test_status.religion,
                qb.code AS bank_code,
                s.name AS subject_name,
                en.name AS exam_name,
                cs.status AS session_status,
                cs.score AS session_score
            ')
            ->join('cbt_question_banks qb', 'qb.id = cbt_test_status.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = cbt_test_status.exam_name_id', 'left')
            ->join('(
                SELECT 
                    cs1.test_id,
                    cs1.student_id,
                    cs1.status,
                    cs1.score,
                    cs1.total_score
                FROM cbt_sessions cs1
                INNER JOIN (
                    SELECT test_id, student_id, MAX(id) as max_id
                    FROM cbt_sessions
                    WHERE student_id = ' . (int) $studentId . '
                    GROUP BY test_id, student_id
                ) cs2 ON cs1.id = cs2.max_id
            ) cs', 'cs.test_id = cbt_test_status.id', 'left')
            ->where('cbt_test_status.is_visible', 1)
            ->orderBy('cbt_test_status.start_time', 'ASC')
            ->findAll();

        $filtered = array_filter($tests, function ($t) use ($className, $studentReligion, $now) {
            $classes = json_decode($t['class_codes'], true) ?? [];
            if (!in_array($className, $classes))
                return false;

            // jika mata pelajaran agama, cek agama siswa
            if (strtolower(trim($t['subject_type'] ?? '')) === 'agama') {
                $testReligion = strtolower(trim($t['religion'] ?? ''));
                if ($testReligion !== $studentReligion)
                    return false;
            }

            // Filter ujian yang aktif hari ini (berdasarkan rentang start & end)
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');

            $testStart = $t['start_time'] ?? '2000-01-01 00:00:00';
            $testEnd = $t['end_time'] ?? '9999-12-31 23:59:59';

            // Ujian tampil jika (start_ujian <= akhir_hari_ini) AND (end_ujian >= awal_hari_ini)
            if ($testStart > $todayEnd || $testEnd < $todayStart) {
                return false;
            }

            return true;
        });

        // Compute Display Status
        $finalTests = array_map(function ($t) use ($now) {
            $t['display_status'] = 'ready'; // default

            if ($t['session_status'] === 'finished') {
                $t['display_status'] = 'finished';
            } else {
                $start = $t['start_time'] ?? '2000-01-01 00:00:00';
                $end = $t['end_time'] ?? '9999-12-31 23:59:59';
                if ($now < $start) {
                    $t['display_status'] = 'upcoming';
                } elseif ($now > $end) {
                    $t['display_status'] = 'passed';
                }
            }
            return $t;
        }, array_values($filtered));

        return view('siswa/cbt/index', [
            'tests' => $finalTests,
            'class' => $className,
            'student' => $student,
            'now' => $now,
            'just_finished' => session()->getFlashdata('just_finished_test_id')
        ]);
    }

    // =========================
    // saveAnswersBulk() - autosave batch
    // Endpoint: POST JSON { test_id, answers: {questionId:answer,...} } OR [{question_id,answer},...]
    // =========================
    public function saveAnswersBulk()
    {
        log_message('info', '[CBT::saveAnswersBulk] Hit from ' . $this->request->getIPAddress());

        $studentId = $this->getStudentId();
        if (!$studentId) {
            log_message('error', '[CBT::saveAnswersBulk] No student ID found in session');
            return $this->failUnauthorized('Sesi tidak valid.');
        }

        // Parse JSON body first; fallback to POST if needed
        $payload = $this->request->getJSON(true);
        if (!$payload) {
            $payload = $this->request->getPost();
            if (!$payload) {
                return $this->failValidationErrors('Payload tidak ditemukan.');
            }
        }

        $testId = isset($payload['test_id']) ? (int) $payload['test_id'] : null;
        $answersInput = $payload['answers'] ?? null;

        if (empty($testId) || empty($answersInput)) {
            log_message('error', "[CBT::saveAnswersBulk] Incomplete payload - Student: $studentId, TestID: " . ($testId ?? 'null'));
            return $this->failValidationErrors('Payload tidak lengkap atau kosong.');
        }

        log_message('debug', "[CBT::saveAnswersBulk] Processing - Student: $studentId, Test: $testId, Answers count: " . (is_array($answersInput) ? count($answersInput) : 'unknown'));

        // Validate session using service
        $validation = $this->sessionService->validateSession($studentId, $testId);

        if (!$validation['valid']) {
            log_message('error', "[CBT::saveAnswersBulk] Validation failed - Student: $studentId, Test: $testId, Reason: {$validation['reason']}, Message: {$validation['message']}");
            $statusCode = in_array($validation['reason'], ['session_expired', 'cheat_locked']) ? 403 : 400;
            return $this->fail($validation['message'], $statusCode);
        }

        $session = $validation['session'];

        // Check if locked by cheat detection
        if ($session['cheat_locked'] ?? 0) {
            log_message('warning', "[CBT::saveAnswersBulk] Student $studentId locked by anti-cheat for test $testId");
            return $this->failForbidden('Ujian telah ditutup oleh pengawas.');
        }

        // Normalize answers into array structure
        if (!is_array($answersInput)) {
            if (is_string($answersInput)) {
                $decoded = json_decode($answersInput, true);
                if (is_array($decoded)) {
                    $answersInput = $decoded;
                }
            }
        }

        if (!is_array($answersInput)) {
            return $this->failValidationErrors('Invalid answers payload');
        }

        // Parse answers into normalized format
        $answers = $this->parseAnswersPayload($answersInput);

        if (empty($answers)) {
            return $this->respond(['status' => 'ok', 'saved' => 0]);
        }

        // Save using service
        $result = $this->answerService->saveBulkAnswers($studentId, $testId, $answers);

        if (!$result['success']) {
            log_message('error', "[CBT::saveAnswersBulk] Save failed - Student: $studentId, Test: $testId, Error: {$result['message']}");
            return $this->fail($result['message'], 500);
        }

        log_message('info', "[CBT::saveAnswersBulk] Success - Student: $studentId, Test: $testId, Saved: " . ($result['data']['count'] ?? 0) . " answers");

        // Update last_activity with throttling
        helper('cbt');
        update_last_activity_throttled($studentId, $testId, 30);

        return $this->respond(['status' => 'ok', 'saved' => $result['data']['count'] ?? 0]);
    }

    /**
     * Parse various answer payload formats into normalized array
     * 
     * @param array $answersInput Raw answers input
     * @return array Normalized answers [question_id => answer]
     */
    protected function parseAnswersPayload(array $answersInput): array
    {
        $answers = [];

        // Check if it's an associative map directly
        $isAssocDirect = array_keys($answersInput) !== range(0, count($answersInput) - 1);

        if ($isAssocDirect) {
            // Check if all values are scalar
            $allValsAreScalar = true;
            foreach ($answersInput as $k => $v) {
                if (is_array($v)) {
                    $allValsAreScalar = false;
                    break;
                }
            }

            if ($allValsAreScalar) {
                // Format: {question_id: answer}
                foreach ($answersInput as $qid => $ans) {
                    if (is_numeric((string) $qid)) {
                        $answers[(int) $qid] = (string) $ans;
                    }
                }
            } else {
                // Mixed structure
                foreach ($answersInput as $item) {
                    if (is_array($item)) {
                        if (isset($item['question_id'])) {
                            $answers[(int) $item['question_id']] = (string) ($item['answer'] ?? '');
                        } else {
                            // Try single-pair assoc
                            $keys = array_keys($item);
                            if (count($keys) === 1 && is_numeric((string) $keys[0])) {
                                $answers[(int) $keys[0]] = (string) $item[$keys[0]];
                            }
                        }
                    }
                }
            }
        } else {
            // Sequential array
            foreach ($answersInput as $item) {
                if (is_array($item)) {
                    $qid = $item['question_id'] ?? $item['qid'] ?? null;

                    if ($qid === null) {
                        // Maybe item is assoc map like {"12":"A"}
                        $keys = array_keys($item);
                        if (count($keys) === 1 && is_numeric((string) $keys[0])) {
                            $qid = $keys[0];
                            $ans = $item[$qid];
                        } else {
                            continue;
                        }
                    } else {
                        $ans = $item['answer'] ?? '';
                    }

                    $answers[(int) $qid] = (string) $ans;
                }
            }
        }

        return $answers;
    }

    // =========================
    // getTimerSync() - Get remaining time from server for timer sync
    // =========================
    public function getTimerSync()
    {
        if (!$this->request->isAJAX()) {
            return $this->failUnauthorized('Invalid request');
        }

        $studentId = $this->getStudentId();
        if (!$studentId) {
            return $this->failUnauthorized('Sesi tidak valid.');
        }

        $json = $this->request->getJSON(true) ?? [];
        $testId = $json['test_id'] ?? null;

        if (!$testId) {
            return $this->failValidationErrors('Test ID tidak ditemukan.');
        }

        // Get session
        $session = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', (int) $testId)
            ->first();

        if (!$session) {
            return $this->fail('Sesi tidak ditemukan.', 404);
        }

        if ($session['status'] === 'finished') {
            return $this->respond([
                'success' => true,
                'status' => 'finished',
                'remaining_seconds' => 0
            ]);
        }

        // Get test data
        $test = $this->testModel->find($testId);
        if (!$test) {
            return $this->fail('Data ujian tidak ditemukan.', 404);
        }

        // Calculate remaining time
        $startTs = (int) $session['started_at'];
        $durationSec = max(10, ((int) $test['duration']) * 60);
        $extraDuration = ((int) ($session['extra_time'] ?? 0)) * 60;
        $sessionEndTs = $startTs + $durationSec + $extraDuration;

        $testEndTs = !empty($test['end_time']) ? strtotime($test['end_time']) : 2147483647;
        $effectiveEndTs = min($sessionEndTs, $testEndTs);

        $remainingSeconds = max(0, $effectiveEndTs - time());

        return $this->respond([
            'success' => true,
            'status' => 'active',
            'remaining_seconds' => $remainingSeconds,
            'server_time' => time()
        ]);
    }

    // =========================
    // ping() - lightweight heartbeat
    // =========================
    public function ping()
    {
        try {
            $studentId = $this->getStudentId();
            if (!$studentId) {
                return $this->respond(['status' => 'unauthenticated']);
            }

            $json = $this->request->getJSON(true) ?? [];
            $testId = $this->request->getPost('test_id') ?? $json['test_id'] ?? null;
            $event = $this->request->getPost('event') ?? $json['event'] ?? null;

            if ($testId) {
                $session = $this->sessionModel
                    ->where('student_id', $studentId)
                    ->where('test_id', (int) $testId)
                    ->first();

                if (!$session) {
                    return $this->respond(['status' => 'error', 'message' => 'Sesi tidak ditemukan']);
                }

                if ($session['status'] === 'finished') {
                    return $this->respond([
                        'status' => 'finished',
                        'message' => 'Ujian telah diselesaikan oleh pengawas.',
                        'score' => $session['total_score'] ?? $session['score'] ?? null
                    ]);
                }

                // Update last_activity using datetime string
                try {
                    $this->sessionModel->where('student_id', $studentId)
                        ->where('test_id', (int) $testId)
                        ->set(['last_activity' => date('Y-m-d H:i:s')])
                        ->update();
                } catch (\Throwable $e) {
                }

                // Handle anti-cheat logic
                $test = $this->testModel->find($testId);
                $antiCheat = $test['anti_cheat'] ?? 'tidak';

                if ($event && $antiCheat !== 'tidak') {
                    // Log the violation
                    try {
                        $db = db_connect();
                        $db->table('cbt_cheat_logs')->insert([
                            'student_id' => $studentId,
                            'test_id' => (int) $testId,
                            'event' => $event,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                    } catch (\Throwable $e) {
                        log_message('error', '[CBT::ping] Error logging cheat: ' . $e->getMessage());
                    }
                }

                // Enforce limits and get current status
                $cheatStatus = $this->sessionService->checkAndEnforceCheatLimit($studentId, (int) $testId, $antiCheat);

                return $this->respond(array_merge([
                    'status' => $cheatStatus['is_locked'] ? 'finished' : 'ok',
                    'is_locked' => $cheatStatus['is_locked']
                ], $cheatStatus));
            }

            return $this->respond(['status' => 'ok']);
        } catch (\Throwable $th) {
            log_message('error', '[CBT::ping] FATAL: ' . $th->getMessage());
            return $this->respond(['status' => 'error', 'message' => 'Internal error']);
        }
    }


    // =========================
    // mulai() - start or resume an exam
    // =========================
    /**
     * mulai() - start or resume an exam (fully corrected)
     */
    /**
     * Mulai ujian
     * 
     * Main entry point for starting an exam session
     */
    public function mulai($testId)
    {
        // 1. Validate and get student
        $studentId = $this->validateAndGetStudentId($testId);
        if (!$studentId) {
            log_message('error', "[CBT::mulai] validateAndGetStudentId failed - session lost for test $testId");
            log_message('debug', "[CBT::mulai] Session data: " . json_encode([
                'user' => session()->get('user'),
                'logged_in' => session()->get('logged_in'),
                'active_test' => session()->get('active_test'),
                'exam_active' => session()->getTempdata('exam_active')
            ]));
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid.');
        }

        log_message('info', "[CBT::mulai] Student $studentId accessing test $testId");
        log_message('debug', "[CBT::mulai] Session validated: user_id=" . (session()->get('user')['id'] ?? 'null') . ", student_id=$studentId");

        // 🔒 SECURITY: Check if student already finished this exam
        $existingSession = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->orderBy('id', 'DESC')
            ->first();

        if ($existingSession && $existingSession['status'] === 'finished') {
            log_message('warning', "[CBT::mulai] Student $studentId attempted to re-enter finished test $testId");
            return redirect()->to(site_url('siswa/cbt'))
                ->with('error', 'Anda sudah menyelesaikan ujian ini. Tidak dapat mengulang ujian.');
        }

        // 2. Mark session as exam-active
        $this->markExamActive($testId);

        // 3. Get test data
        $test = $this->getTestData($testId);
        if (!$test) {
            log_message('error', "[CBT::mulai] Test $testId not found for student $studentId");
            return redirect()->to(site_url('siswa/cbt'))->with('error', 'Data ujian tidak ditemukan.');
        }

        // 4. Get student data
        $student = $this->getStudentData($studentId);
        if (!$student) {
            log_message('error', "[CBT::mulai] Student $studentId data not found for test $testId");
            return redirect()->to(site_url('siswa/cbt'))->with('error', 'Data siswa tidak valid.');
        }

        // 5. Validate access permissions
        $accessValidation = $this->validateExamAccess($test, $student);
        if (!$accessValidation['valid']) {
            log_message('warning', "[CBT::mulai] Access validation failed for student $studentId, test $testId: " . $accessValidation['message']);
            return redirect()->to(site_url('siswa/cbt'))->with('error', $accessValidation['message']);
        }

        // 6. Get or create session
        $sessionResult = $this->getOrCreateSession($studentId, $testId, $test);
        if (isset($sessionResult['redirect'])) {
            log_message('info', "[CBT::mulai] Redirecting student $studentId from test $testId (session already finished or error)");
            return $sessionResult['redirect'];
        }

        $session = $sessionResult['session'];
        $isNewSession = $sessionResult['isNewSession'];
        $resetToken = $sessionResult['resetToken'];
        
        log_message('info', "[CBT::mulai] Session prepared for student $studentId, test $testId, isNew=" . ($isNewSession ? 'true' : 'false'));

        // 7. Prepare questions for view
        $questionsData = $this->prepareQuestionsForView($session, $test, $studentId, $testId);

        // 8. Get saved answers
        $answersData = $this->getSavedAnswers($studentId, $testId);

        // 9. Calculate timing
        $timingData = $this->calculateTiming($session, $test);

        // 10. Save session data
        $this->saveActiveTestSession($test, $session['started_at'], $resetToken);

        // 11. Get existing violation count for anti-cheat
        $existingViolationCount = 0;
        if (($test['anti_cheat'] ?? 'tidak') !== 'tidak') {
            $db = db_connect();
            $violationResult = $db->table('cbt_cheat_logs')
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->countAllResults();
            $existingViolationCount = (int) $violationResult;
        }
        
        log_message('info', "[CBT::mulai] Rendering exam page for student $studentId, test $testId");

        // 12. Render view
        return view('siswa/cbt/ujian', array_merge([
            'test' => $test,
            'isNewSession' => $isNewSession,
            'reset_token' => $resetToken,
            'student_id' => $studentId,
            'anti_cheat' => $test['anti_cheat'] ?? 'tidak',
            'existingViolationCount' => $existingViolationCount,
        ], $questionsData, $answersData, $timingData));
    }

    // =========================
    // saveAnswer() - single answer save (AJAX)
    // =========================
    public function saveAnswer()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        // 🔹 FIX: Use getStudentId() method for consistent student ID retrieval
        $studentId = $this->getStudentId();
        $testId = $this->request->getPost('test_id');
        $questionId = $this->request->getPost('question_id');
        $answer = $this->request->getPost('answer');

        if (!$studentId || !$testId || !$questionId) {
            log_message('error', "[CBT::saveAnswer] MISSING DATA: studentId=" . var_export($studentId, true) . ", testId=" . var_export($testId, true) . ", qid=" . var_export($questionId, true));
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Data tidak lengkap']);
        }

        // Validate session
        $validation = $this->sessionService->validateSession($studentId, $testId);

        if (!$validation['valid']) {
            $statusCode = in_array($validation['reason'], ['session_expired', 'cheat_locked']) ? 403 : 400;
            return $this->response->setStatusCode($statusCode)->setJSON(['error' => $validation['message']]);
        }

        $session = $validation['session'];

        // Check if locked by cheat detection
        if ($session['cheat_locked'] ?? 0) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Ujian telah ditutup oleh pengawas.']);
        }

        // Save answer using service
        $result = $this->answerService->saveAnswer($studentId, $testId, $questionId, $answer, false);

        if (!$result['success']) {
            return $this->response->setStatusCode(500)->setJSON(['error' => $result['message']]);
        }

        // Update last_activity with throttling (30 seconds interval)
        helper('cbt');
        update_last_activity_throttled($studentId, $testId, 30);

        // Get saved answer for response
        $saved = $this->answerModel->where([
            'student_id' => $studentId,
            'test_id' => $testId,
            'question_id' => $questionId
        ])->first();

        return $this->response->setJSON(['status' => 'ok', 'saved' => $saved]);
    }

    // =========================
    // submit() - finalize and calculate scores
    // =========================
    // submit() - finalize and calculate scores
    // =========================
    /**
     * Submit exam - orchestrator method
     * 
     * @param int $testId Test ID
     * @return ResponseInterface JSON response with redirect URL
     */
    public function submit($testId)
    {
        // Validate student authentication
        $studentId = $this->getStudentId();
        if (!$studentId) {
            return $this->respond(['error' => 'Sesi login tidak valid.'], 401);
        }

        // Start database transaction with lock
        $db = db_connect();
        $db->transStart();

        try {
            // Lock and validate session
            $session = $this->lockAndGetSession($db, $studentId, $testId);
            if (!$session) {
                $db->transRollback();
                return $this->respond(['error' => 'Sesi ujian tidak ditemukan.'], 400);
            }

            // Check if already finished
            if ($session['status'] === 'finished') {
                $db->transRollback();
                return $this->respond(['redirect' => site_url('siswa/cbt/hasil/' . $testId)]);
            }

            // Get test data
            $test = $this->testModel->find($testId);
            if (!$test) {
                $db->transRollback();
                return $this->respond(['error' => 'Data ujian tidak ditemukan.'], 404);
            }

            // Check if forced submit
            $isForced = $this->isForcedSubmit();

            // Validate submission timing (unless forced)
            if (!$isForced) {
                $timingValidation = $this->validateSubmitTiming($session, $test);
                if (!$timingValidation['valid']) {
                    $db->transRollback();
                    return $this->respond(['error' => $timingValidation['message']], 403);
                }
            }

            // Validate all questions answered (unless forced)
            if (!$isForced) {
                $validation = $this->scoringService->validateAllAnswered($studentId, $testId, $test['bank_id']);
                if (!$validation['valid']) {
                    $db->transRollback();

                    $message = 'Ujian belum bisa diselesaikan. ';
                    if (!empty($validation['unanswered'])) {
                        $message .= 'Masih ada ' . count($validation['unanswered']) . ' soal belum dijawab. ';
                    }
                    if (!empty($validation['incomplete_bs'])) {
                        $message .= 'Soal Benar/Salah harus dijawab lengkap (semua pernyataan).';
                    }

                    return $this->respond([
                        'error' => $message,
                        'details' => [
                            'unanswered' => $validation['unanswered'] ?? [],
                            'incomplete_bs' => $validation['incomplete_bs'] ?? []
                        ]
                    ], 400);
                }
            }

            // Perform actual submission
            $success = $this->internalSubmit($studentId, $testId);
            if (!$success) {
                $db->transRollback();
                return $this->respond(['error' => 'Gagal menyelesaikan ujian.'], 500);
            }

            // Complete transaction
            $db->transComplete();
            if ($db->transStatus() === false) {
                return $this->respond(['error' => 'Gagal menyelesaikan ujian. Silakan coba lagi.'], 500);
            }

            // Set session flashdata
            session()->setFlashdata('just_finished_test_id', $testId);

            // Determine redirect URL
            $redirectUrl = $this->determineSubmitRedirect($testId, $test, $isForced);

            log_message('info', "[CBT::submit] Student $studentId successfully submitted test $testId");

            return $this->respond(['redirect' => $redirectUrl]);

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[CBT::submit] Exception: ' . $e->getMessage());
            return $this->respond(['error' => 'Terjadi kesalahan sistem. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Lock session row and retrieve session data
     * Uses FOR UPDATE to prevent race conditions
     * 
     * @param \CodeIgniter\Database\BaseConnection $db Database connection
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @return array|null Session data or null if not found
     */
    protected function lockAndGetSession($db, int $studentId, int $testId): ?array
    {
        $session = $db->table('cbt_sessions')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->get()
            ->getRowArray();

        return $session ?: null;
    }

    /**
     * Check if this is a forced submit (from anti-cheat or admin)
     * 
     * @return bool True if forced submit
     */
    protected function isForcedSubmit(): bool
    {
        // Check GET parameter
        if ($this->request->getGet('forced') === '1') {
            return true;
        }
        
        // Check POST parameter
        $postForced = $this->request->getPost('forced');
        if ($postForced === true || $postForced === '1' || $postForced === 1) {
            return true;
        }
        
        // Check JSON body (with error handling)
        try {
            $json = $this->request->getJSON();
            if ($json && isset($json->forced) && $json->forced) {
                return true;
            }
        } catch (\Throwable $e) {
            // Not a JSON request or invalid JSON - ignore
            log_message('debug', '[CBT::isForcedSubmit] JSON parse failed (expected for non-JSON requests): ' . $e->getMessage());
        }
        
        return false;
    }

    /**
     * Validate submission timing based on finish button lock
     * 
     * @param array $session Session data
     * @param array $test Test data
     * @return array ['valid' => bool, 'message' => string]
     */
    protected function validateSubmitTiming(array $session, array $test): array
    {
        $durationSec = (int) ($test['duration'] ?? 30) * 60;
        $lockFactor = (float) ($test['finish_button_lock'] ?? 0);
        $serverStartTs = (int) ($session['started_at'] ?? 0);

        // No lock configured
        if ($lockFactor <= 0 || $serverStartTs <= 0) {
            return ['valid' => true, 'message' => ''];
        }

        // Check if minimum time has elapsed
        $threshold = $serverStartTs + ($lockFactor * $durationSec);
        if (time() < $threshold) {
            return [
                'valid' => false,
                'message' => 'Anda belum diperbolehkan menyelesaikan ujian (batas waktu minimal belum terpenuhi).'
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Determine redirect URL after successful submit
     * 
     * @param int $testId Test ID
     * @param array $test Test data
     * @param bool $isForced Whether this is a forced submit
     * @return string Redirect URL
     */
    protected function determineSubmitRedirect(int $testId, array $test, bool $isForced): string
    {
        // 🔹 DEBUG: Log redirect decision
        log_message('debug', "[determineSubmitRedirect] testId={$testId}, show_score=" . ($test['show_score'] ?? 'tidak') . ", isForced=" . ($isForced ? 'true' : 'false'));
        
        if ($isForced) {
            // Use regular session (not flashdata) for forced submit to persist across redirects
            session()->set('forced_submit_' . $testId, true);
            $url = site_url('siswa/cbt/selesai/' . $testId . '?forced=1');
            log_message('debug', "[determineSubmitRedirect] FORCED redirect: {$url}");
            return $url;
        }

        // 🔹 FIX: Always redirect to selesai/hasil page first, not directly to dashboard
        // If show_score = 'ya' → go to 'hasil' page (with score details)
        // If show_score = 'tidak' → go to 'selesai' page (without score)
        if (strtolower($test['show_score'] ?? 'tidak') === 'ya') {
            $url = site_url('siswa/cbt/hasil/' . $testId);
            log_message('debug', "[determineSubmitRedirect] SHOW_SCORE=YA redirect: {$url}");
            return $url;
        }

        $url = site_url('siswa/cbt/selesai/' . $testId);
        log_message('debug', "[determineSubmitRedirect] DEFAULT redirect: {$url}");
        return $url;
    }

    /**
     * internalSubmit - Core logic for submitting a test (scoring + DB update)
     * Does NOT return a Response object causing side-effects.
     */
    private function internalSubmit($studentId, $testId)
    {
        $session = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->first();

        if (!$session)
            return false;
        if ($session['status'] === 'finished')
            return true; // already done

        // Calculate score with bobot (delegates to centralized scoring service)
        $score = $this->scoringService->calculateScoreWithBobot($studentId, $testId);

        log_message('info', "[CBT::internalSubmit] Test: $testId, Student: $studentId, Score: $score");

        $updateData = [
            'status' => 'finished',
            'finished_at' => time(),
            'score' => $score,
            'essay_score' => null,
            'total_score' => $score,
        ];

        try {
            $this->sessionModel->update($session['id'], $updateData);
            
            // 🔹 FIX: Clear active_test session to prevent redirect loop
            // When exam is finished, remove active_test so dashboard doesn't redirect back to exam
            if (session()->get('active_test') == $testId) {
                session()->remove('active_test');
                log_message('info', "[CBT::internalSubmit] Cleared active_test session for test $testId");
            }
            
            // 🔹 FIX: Set post_exam_active to protect selesai/hasil pages
            // Clear exam_active but set post_exam_active for 30 minutes
            session()->removeTempdata('exam_active');
            session()->setTempdata('post_exam_active', true, 1800); // 30 minutes protection
            log_message('info', "[CBT::internalSubmit] Set post_exam_active protection for test $testId");
            
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[CBT::internalSubmit] session update failed: ' . $e->getMessage());
            return false;
        }
    }


    // =========================
    // hasil() - view results
    // =========================
    /**
     * Display exam results - orchestrator method
     * 
     * @param int $testId Test ID
     * @return mixed View or redirect
     */
    public function hasil($testId)
    {
        // Validate student authentication
        $studentId = $this->getStudentId();
        if (!$studentId) {
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid.');
        }

        // Get test and session data
        $test = $this->getTestDataForResults($testId);
        if (!$test) {
            return redirect()->back()->with('error', 'Data ujian tidak ditemukan.');
        }

        // 🔒 SECURITY: Only allow access if show_score is 'ya'
        // Allow immediate access after finishing (don't wait for end_time)
        $showScore = strtolower($test['show_score'] ?? 'tidak') === 'ya';

        if (!$showScore) {
            return redirect()->to('siswa/cbt')->with('error', 'Ujian ini tidak menampilkan hasil.');
        }

        $session = $this->getSessionForResults($studentId, $testId);
        if (!$session) {
            return redirect()->back()->with('error', 'Data sesi ujian tidak ditemukan.');
        }

        // 🔒 SECURITY: Only allow if student has finished the exam
        if ($session['status'] !== 'finished') {
            return redirect()->to('siswa/cbt')->with('error', 'Anda belum menyelesaikan ujian ini.');
        }

        // Get question order from session
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
        if (empty($questionOrder)) {
            return redirect()->back()->with('error', 'Data soal ujian tidak ditemukan.');
        }

        // Process questions and answers
        $questionData = $this->processQuestionsForResults($studentId, $testId, $questionOrder);

        // Calculate scores
        $scoreData = $this->calculateDetailedScores($questionData, $test, $session);

        // Update session score if needed
        $this->updateSessionScoreIfNeeded($session, $scoreData['final_score']);

        // Prepare view data
        return view('siswa/cbt/hasil', array_merge([
            'test' => $test,
            'details' => $questionData
        ], $scoreData));
    }

    /**
     * Get test data with joins for results page
     * 
     * @param int $testId Test ID
     * @return array|null Test data or null
     */
    protected function getTestDataForResults(int $testId): ?array
    {
        $db = db_connect();
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        return $test ?: null;
    }

    /**
     * Get session data for results page
     * 
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @return array|null Session data or null
     */
    protected function getSessionForResults(int $studentId, int $testId): ?array
    {
        return $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->first();
    }

    /**
     * Process questions and answers for results display
     * 
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @param array $questionOrder Question IDs in order
     * @return array Processed question data
     */
    protected function processQuestionsForResults(int $studentId, int $testId, array $questionOrder): array
    {
        // Fetch answers in batch
        $answers = $this->answerModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->findAll();

        $answerMap = [];
        foreach ($answers as $a) {
            $answerMap[$a['question_id']] = $a['answer'];
        }

        // Fetch questions that were shown to student
        $questions = $this->questionModel->whereIn('id', $questionOrder)->findAll();

        // Sort questions according to question_order
        $questionMap = [];
        foreach ($questions as $q) {
            $questionMap[$q['id']] = $q;
        }

        $sortedQuestions = [];
        foreach ($questionOrder as $qid) {
            if (isset($questionMap[$qid])) {
                $sortedQuestions[] = $questionMap[$qid];
            }
        }

        // Process each question
        $questionData = [];
        foreach ($sortedQuestions as $q) {
            $qid = $q['id'];
            $ans = $answerMap[$qid] ?? '';

            // Parse question and determine type
            $parsed = null;
            $rawTypeFromDb = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));

            if (!empty($q['raw_text'])) {
                $parsed = $this->questionService->parseRawQuestion($q['raw_text']);
                if (empty($rawTypeFromDb) || $rawTypeFromDb === 'pg' || $rawTypeFromDb === 'pilihan_ganda') {
                    $rawTypeFromDb = $parsed['type'];
                }
            }

            $type = $this->normalizeQuestionTypeForResults($rawTypeFromDb);

            // Check if answer is correct
            $isCorrect = $this->checkAnswerCorrectness($type, $ans, $q['correct_option'] ?? null);

            // Get question text
            $qText = $this->getQuestionTextForResults($q, $parsed);

            $questionData[] = [
                'id' => $q['id'],
                'type' => $type,
                'text' => $qText,
                'options' => [
                    'A' => trim($q['option_a'] ?? ''),
                    'B' => trim($q['option_b'] ?? ''),
                    'C' => trim($q['option_c'] ?? ''),
                    'D' => trim($q['option_d'] ?? ''),
                    'E' => trim($q['option_e'] ?? '')
                ],
                'answer' => $ans,
                'correct_option' => $q['correct_option'] ?? null,
                'is_correct' => $isCorrect
            ];
        }

        // Sort by question type
        usort($questionData, function ($a, $b) {
            $order = [
                'pg' => 1,
                'pilihan_ganda' => 1,
                'multiple_choice' => 1,
                'pgk' => 2,
                'pg_kompleks' => 2,
                'bs' => 3,
                'benar_salah' => 3,
                'esai' => 4,
                'essay' => 4
            ];
            return ($order[$a['type']] ?? 99) <=> ($order[$b['type']] ?? 99);
        });

        return $questionData;
    }

    /**
     * Normalize question type for results display
     * 
     * @param string $rawType Raw question type
     * @return string Normalized type
     */
    protected function normalizeQuestionTypeForResults(string $rawType): string
    {
        $type = $rawType;
        if ($type === 'pgk')
            $type = 'pg_kompleks';
        if ($type === 'bs')
            $type = 'benar_salah';
        if ($type === 'essay')
            $type = 'esai';
        return $type;
    }

    /**
     * Check if student answer is correct
     * 
     * @param string $type Question type
     * @param string $studentAnswer Student's answer
     * @param string|null $correctOption Correct answer
     * @return bool True if correct
     */
    protected function checkAnswerCorrectness(string $type, string $studentAnswer, ?string $correctOption): bool
    {
        if (empty($correctOption) || empty($studentAnswer)) {
            return false;
        }

        if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
            return $studentAnswer === $correctOption;
        }

        if ($type === 'pg_kompleks' || $type === 'pgk') {
            $correctArr = explode(',', $correctOption);
            $studentArr = explode(',', $studentAnswer);
            $cSel = count(array_intersect($studentArr, $correctArr));
            $iSel = count(array_diff($studentArr, $correctArr));
            $tCorr = count($correctArr);
            $raw = $cSel - (0.5 * $iSel);
            $qScore = ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
            return $qScore >= 0.7;
        }

        if ($type === 'benar_salah' || $type === 'bs') {
            $cArr = explode(',', $correctOption);
            $sArr = explode(',', $studentAnswer);
            $matches = 0;
            $ti = count($cArr);
            for ($k = 0; $k < $ti; $k++) {
                $studentAns = isset($sArr[$k]) ? trim($sArr[$k]) : '';
                $correctAns = isset($cArr[$k]) ? trim($cArr[$k]) : '';
                if ($studentAns !== '' && $studentAns === $correctAns) {
                    $matches++;
                }
            }
            return ($ti > 0 && ($matches / $ti) >= 0.7);
        }

        return false;
    }

    /**
     * Get formatted question text for results
     * 
     * @param array $question Question data
     * @param array|null $parsed Parsed question data
     * @return string Question text
     */
    protected function getQuestionTextForResults(array $question, ?array $parsed): string
    {
        $qText = trim($question['question_text'] ?? '');

        if ($parsed) {
            $qText = trim($parsed['question']);
        } elseif (!empty($question['raw_text'])) {
            $parsed = $this->questionService->parseRawQuestion($question['raw_text']);
            $qText = trim($parsed['question']);
        }

        return $qText;
    }

    /**
     * Calculate detailed scores for all question types
     * 
     * @param array $questionData Processed question data
     * @param array $test Test data with bobot values
     * @param array $session Session data with essay_score
     * @return array Score data for view
     */
    protected function calculateDetailedScores(array $questionData, array $test, array $session): array
    {
        $bobotPg = (float) ($test['bobot_pg'] ?? 0);
        $bobotPgk = (float) ($test['bobot_pg_kompleks'] ?? 0);
        $bobotBs = (float) ($test['bobot_bs'] ?? 0);
        $bobotEsai = (float) ($test['bobot_esai'] ?? 0);

        $totalEarnedPg = 0;
        $totalEarnedPgk = 0;
        $totalEarnedBs = 0;
        $totalCountPg = 0;
        $totalCountPgk = 0;
        $totalCountBs = 0;
        $totalCountEsai = 0;

        foreach ($questionData as $qd) {
            if (in_array($qd['type'], ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $totalCountPg++;
                if ($qd['is_correct'])
                    $totalEarnedPg += 1;
            } elseif ($qd['type'] === 'pg_kompleks' || $qd['type'] === 'pgk') {
                $totalCountPgk++;
                $qScore = $this->calculatePgkScore($qd['answer'], $qd['correct_option']);
                $totalEarnedPgk += $qScore;
            } elseif ($qd['type'] === 'benar_salah' || $qd['type'] === 'bs') {
                $totalCountBs++;
                $qScore = $this->calculateBsScore($qd['answer'], $qd['correct_option']);
                $totalEarnedBs += $qScore;
            } elseif (in_array($qd['type'], ['esai', 'essay'])) {
                $totalCountEsai++;
            }
        }

        $nilaiPg = ($totalCountPg > 0) ? ($totalEarnedPg / $totalCountPg) * 100 : 0;
        $nilaiPgk = ($totalCountPgk > 0) ? ($totalEarnedPgk / $totalCountPgk) * 100 : 0;
        $nilaiBs = ($totalCountBs > 0) ? ($totalEarnedBs / $totalCountBs) * 100 : 0;
        $nilaiEsai = (float) ($session['essay_score'] ?? 0);

        $finalScore = round(
            ($nilaiPg * ($bobotPg / 100)) +
            ($nilaiPgk * ($bobotPgk / 100)) +
            ($nilaiBs * ($bobotBs / 100)) +
            ($nilaiEsai * ($bobotEsai / 100)),
            2
        );

        return [
            'score' => $finalScore,
            'final_score' => $finalScore,
            'correct' => $totalEarnedPg, // Backward compatibility
            'correct_pg' => $totalEarnedPg,
            'correct_pgk' => $totalEarnedPgk,
            'correct_bs' => $totalEarnedBs,
            'total_pg' => $totalCountPg,
            'total_pg_kompleks' => $totalCountPgk,
            'total_bs' => $totalCountBs,
            'bobot_pg' => $bobotPg,
            'bobot_pg_kompleks' => $bobotPgk,
            'bobot_bs' => $bobotBs,
            'bobot_esai' => $bobotEsai,
            'nilai_pg' => $nilaiPg,
            'nilai_pgk' => $nilaiPgk,
            'nilai_bs' => $nilaiBs,
            'nilai_esai' => $nilaiEsai,
            'total_esai' => $totalCountEsai
        ];
    }

    /**
     * Calculate score for PG Kompleks question
     * 
     * @param string $studentAnswer Student's answer
     * @param string|null $correctOption Correct answer
     * @return float Score (0-1)
     */
    protected function calculatePgkScore(string $studentAnswer, ?string $correctOption): float
    {
        if (empty($correctOption) || empty($studentAnswer)) {
            return 0;
        }

        $correctArr = explode(',', $correctOption);
        $studentArr = explode(',', $studentAnswer);
        $cSel = count(array_intersect($studentArr, $correctArr));
        $iSel = count(array_diff($studentArr, $correctArr));
        $tCorr = count($correctArr);

        $rawScore = $cSel - (0.5 * $iSel);
        return $tCorr > 0 ? (max(0, $rawScore) / $tCorr) : 0;
    }

    /**
     * Calculate score for Benar-Salah question
     * 
     * @param string $studentAnswer Student's answer
     * @param string|null $correctOption Correct answer
     * @return float Score (0-1)
     */
    protected function calculateBsScore(string $studentAnswer, ?string $correctOption): float
    {
        if (empty($correctOption) || empty($studentAnswer)) {
            return 0;
        }

        $correctArr = explode(',', $correctOption);
        $studentArr = explode(',', $studentAnswer);
        $matches = 0;
        $totalItems = count($correctArr);

        for ($k = 0; $k < $totalItems; $k++) {
            $studentAns = isset($studentArr[$k]) ? trim($studentArr[$k]) : '';
            $correctAns = isset($correctArr[$k]) ? trim($correctArr[$k]) : '';
            if ($studentAns !== '' && $studentAns === $correctAns) {
                $matches++;
            }
        }

        return ($totalItems > 0) ? ($matches / $totalItems) : 0;
    }

    /**
     * Update session score if it differs from calculated score
     * 
     * @param array $session Session data
     * @param float $calculatedScore Calculated score
     * @return void
     */
    protected function updateSessionScoreIfNeeded(array $session, float $calculatedScore): void
    {
        if (!isset($session['score']) || (float) $session['score'] != $calculatedScore) {
            $this->sessionModel->update($session['id'], [
                'score' => $calculatedScore,
                'total_score' => $calculatedScore
            ]);
        }
    }

    // =========================
    // selesai() - simple view after submit (no score shown)
    // =========================
    public function selesai($testId)
    {
        // 🔹 FIX: Use getStudentId() method for consistent student ID retrieval
        $studentId = $this->getStudentId();
        if (!$studentId) {
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid.');
        }

        // Check if this was a forced submit and set flashdata for view
        $forcedKey = 'forced_submit_' . $testId;
        if (session()->has($forcedKey)) {
            session()->setFlashdata('forced_submit', true);
            session()->remove($forcedKey); // Clear after use
        }

        $db = db_connect();
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->to('siswa/cbt')->with('error', 'Data ujian tidak ditemukan.');
        }

        $session = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->first();

        if (!$session) {
            return redirect()->to('siswa/cbt')->with('error', 'Data sesi ujian tidak ditemukan.');
        }

        // Finalize session if not already finished
        if ($session['status'] !== 'finished') {
            $this->internalSubmit($studentId, $testId);
            // Refresh session data
            $session = $this->sessionModel->find($session['id']);
        }

        // 🔒 FIX: Get question_order from session (only questions shown to student)
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];

        if (empty($questionOrder)) {
            // Fallback to all questions if question_order is empty (shouldn't happen)
            $questions = $this->questionModel->where('bank_id', $test['bank_id'])->findAll();
        } else {
            // 🔒 FIX: Fetch ONLY questions that were shown to student
            $questions = $this->questionModel->whereIn('id', $questionOrder)->findAll();
        }

        $answers = $this->answerModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->findAll();

        $answerMap = [];
        foreach ($answers as $a)
            $answerMap[$a['question_id']] = $a;

        $stats = [
            'pg' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_pg'] ?? 0)],
            'pgk' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_pg_kompleks'] ?? 0)],
            'bs' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_bs'] ?? 0)],
            'esai' => ['total' => 0, 'earned' => (float) ($session['essay_score'] ?? 0), 'weight' => (float) ($test['bobot_esai'] ?? 0)],
        ];

        foreach ($questions as $q) {
            $type = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
            $qid = $q['id'];
            $correctStr = $q['correct_answer'] ?? $q['correct_option'] ?? '';
            $studentAns = isset($answerMap[$qid]) ? ($answerMap[$qid]['answer'] ?? null) : null;

            if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $stats['pg']['total']++;
                if ($correctStr !== '' && $studentAns !== null && strtoupper(trim((string) $studentAns)) === strtoupper(trim((string) $correctStr))) {
                    $stats['pg']['earned']++;
                }
            } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                $stats['pgk']['total']++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $cArr = explode(',', $correctStr);
                    $sArr = explode(',', $studentAns);
                    $cSel = count(array_intersect($sArr, $cArr));
                    $iSel = count(array_diff($sArr, $cArr));
                    $tCorr = count($cArr);
                    $raw = $cSel - (0.5 * $iSel);
                    $stats['pgk']['earned'] += ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
                }
            } elseif ($type === 'benar_salah' || $type === 'bs') {
                $stats['bs']['total']++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $cArr = explode(',', $correctStr);
                    $sArr = explode(',', $studentAns);
                    $m = 0;
                    $ti = count($cArr);
                    for ($i = 0; $i < $ti; $i++) {
                        // 🔒 FIX: Handle empty answers properly
                        $stdAns = isset($sArr[$i]) ? trim($sArr[$i]) : '';
                        $keyAns = isset($cArr[$i]) ? trim($cArr[$i]) : '';

                        if ($stdAns !== '' && strtoupper($stdAns) === strtoupper($keyAns)) {
                            $m++;
                        }
                    }
                    $stats['bs']['earned'] += ($ti > 0) ? ($m / $ti) : 0;
                }
            } else {
                $stats['esai']['total']++;
            }
        }

        // Final Score Calculation for summary
        $stats['pg']['score'] = ($stats['pg']['total'] > 0) ? ($stats['pg']['earned'] / $stats['pg']['total']) * 100 : 0;
        $stats['pgk']['score'] = ($stats['pgk']['total'] > 0) ? ($stats['pgk']['earned'] / $stats['pgk']['total']) * 100 : 0;
        $stats['bs']['score'] = ($stats['bs']['total'] > 0) ? ($stats['bs']['earned'] / $stats['bs']['total']) * 100 : 0;
        $stats['esai']['score'] = ($stats['esai']['total'] > 0) ? ($stats['esai']['earned']) : 0;

        $stats['pg']['contribution'] = $stats['pg']['score'] * ($stats['pg']['weight'] / 100);
        $stats['pgk']['contribution'] = $stats['pgk']['score'] * ($stats['pgk']['weight'] / 100);
        $stats['bs']['contribution'] = $stats['bs']['score'] * ($stats['bs']['weight'] / 100);
        $stats['esai']['contribution'] = $stats['esai']['score'] * ($stats['esai']['weight'] / 100);

        $calculatedTotal = $stats['pg']['contribution'] + $stats['pgk']['contribution'] + $stats['bs']['contribution'] + $stats['esai']['contribution'];

        return view('siswa/cbt/selesai', [
            'test' => $test,
            'session' => $session,
            'stats' => $stats,
            'calculated_total' => $calculatedTotal
        ]);
    }

    // =========================
    // getScore() - AJAX helper to fetch latest session score/status
    // =========================
    public function getScore($testId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Akses tidak valid']);
        }

        $user = session()->get('user');
        $studentId = $user['related_id'] ?? null;
        if (!$studentId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sesi tidak valid.']);
        }

        $session = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->first();

        if (!$session) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data sesi tidak ditemukan.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'status' => $session['status'],
            'score' => $session['score'] ?? '-'
        ]);
    }

    // =========================
    // cekToken(), verifyToken(), peraturan() - token & rules checks
    // =========================
    public function cekToken($id)
    {
        $token = $this->request->getPost('token');
        $test = $this->testModel->find($id);

        if (!$test) {
            return redirect()->back()->with('error', 'Ujian tidak ditemukan.');
        }

        if (strtoupper($token) !== strtoupper($test['token'])) {
            return redirect()->back()->with('error', 'Token salah.');
        }

        return redirect()->to('siswa/cbt/peraturan/' . $id);
    }

    public function verifyToken($testId = null)
    {
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('siswa/cbt'))->with('error', 'Metode request tidak valid.');
        }

        $inputToken = strtoupper(trim($this->request->getPost('token') ?? ''));

        $test = $this->testModel
            ->select('cbt_test_status.*, cbt_question_banks.code AS bank_code, subjects.name AS subject_name')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->find($testId);

        if (!$test) {
            return redirect()->back()->with('error', 'Ujian tidak ditemukan.');
        }

        $user = session()->get('user');
        $studentId = $user['related_id'] ?? null;

        if (!$studentId) {
            return redirect()->to('/login')->with('error', 'Sesi login tidak valid.');
        }

        log_message('info', "[CBT::verifyToken] Student $studentId attempting test $testId with token");

        // 🔒 SECURITY: Check if student already has a session for this test
        $existingSession = $this->sessionModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->orderBy('id', 'DESC')
            ->first();

        // 🔒 CRITICAL: Prevent re-entry if exam already finished
        if ($existingSession && $existingSession['status'] === 'finished') {
            log_message('warning', "[CBT::verifyToken] Student $studentId attempted to re-enter finished test $testId");
            return redirect()->back()->with('error', 'Anda sudah menyelesaikan ujian ini. Tidak dapat mengulang ujian.');
        }

        // If session exists and still active, redirect to exam page directly (resume)
        if ($existingSession && $existingSession['status'] === 'active') {
            log_message('info', "[CBT::verifyToken] Student $studentId resuming active test $testId");
            return redirect()->to(site_url('siswa/cbt/mulai/' . $testId));
        }

        $student = $this->studentModel
            ->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->find($studentId);

        $className = $student['class_name'] ?? '';
        $allowedClasses = json_decode($test['class_codes'], true) ?? [];

        if (!in_array($className, $allowedClasses)) {
            return redirect()->back()->with('error', 'Anda tidak terdaftar pada kelas peserta ujian ini.');
        }

        if (strtoupper($test['token']) !== $inputToken) {
            return redirect()->back()->with('error', 'Token ujian salah.');
        }

        $now = time();
        $start = strtotime($test['start_time']);
        $end = strtotime($test['end_time']);

        if ($now < $start) {
            return redirect()->back()->with('error', 'Ujian belum dimulai.');
        }
        if ($now > $end) {
            return redirect()->back()->with('error', 'Waktu ujian sudah berakhir.');
        }

        // preserve reset_token if already in session (do not overwrite with null)
        session()->set('active_test', [
            'test_id' => $test['id'],
            'subject' => $test['subject_name'],
            'token' => $inputToken,
            'reset_token' => session()->get('active_test.reset_token') ?? null
        ]);

        return redirect()->to(site_url('siswa/cbt/peraturan/' . $test['id']));
    }

    public function peraturan($testId)
    {
        // 🔒 SECURITY: Validate session before showing rules
        $user = session()->get('user');
        $studentId = $user['related_id'] ?? null;
        
        if (!$studentId) {
            log_message('warning', "[CBT::peraturan] Session lost - student_id not found for test $testId");
            return redirect()->to(site_url('siswa/cbt'))
                ->with('error', 'Sesi login tidak valid. Silakan input token kembali.');
        }
        
        // 🔒 SECURITY: Validate active_test session
        $activeTest = session()->get('active_test');
        if (!$activeTest || $activeTest['test_id'] != $testId) {
            log_message('warning', "[CBT::peraturan] Active test session invalid for student $studentId, test $testId");
            return redirect()->to(site_url('siswa/cbt'))
                ->with('error', 'Sesi ujian tidak valid. Silakan input token kembali.');
        }
        
        log_message('info', "[CBT::peraturan] Student $studentId accessing rules for test $testId");
        log_message('debug', "[CBT::peraturan] Session data: " . json_encode([
            'user_id' => $user['id'] ?? null,
            'student_id' => $studentId,
            'active_test' => $activeTest
        ]));
        
        $test = $this->testModel
            ->select('
                cbt_test_status.*, 
                cbt_question_banks.code AS bank_code, 
                subjects.name AS subject_name, 
                cbt_exam_names.name AS exam_name
            ')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
            ->find($testId);

        if (!$test) {
            log_message('error', "[CBT::peraturan] Test $testId not found for student $studentId");
            return redirect()->to(site_url('siswa/cbt'))->with('error', 'Data ujian tidak ditemukan.');
        }

        return view('siswa/cbt/peraturan', ['test' => $test]);
    }

    /**
     * isSessionExpired - check if session has passed its deadline
     * @deprecated Use CbtSessionService::isSessionExpired() instead
     */
    private function isSessionExpired($session, $test, $graceSeconds = 30): bool
    {
        // Delegate to service
        return $this->sessionService->isSessionExpired($session, $test, $graceSeconds);
    }

    // =========================
    // utility / helper
    // =========================
    protected function getStudentClass($studentId)
    {
        $student = $this->studentModel
            ->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->find($studentId);

        return $student['class_name'] ?? '-';
    }

    /**
     * Validate and get student ID with refresh protection
     */
    protected function validateAndGetStudentId(int $testId): ?int
    {
        $user = session()->get('user');
        $studentId = $user['student_id'] ?? $user['related_id'] ?? null;

        // 🔒 REFRESH PROTECTION: If session is empty but there's an active exam, try to restore
        if (!$studentId) {
            $activeSession = $this->sessionModel
                ->select('cbt_sessions.*, students.user_id')
                ->join('students', 'students.id = cbt_sessions.student_id', 'left')
                ->where('cbt_sessions.test_id', $testId)
                ->where('cbt_sessions.status', 'active')
                ->orderBy('cbt_sessions.started_at', 'DESC')
                ->first();

            if ($activeSession && !empty($activeSession['user_id'])) {
                $userModel = new \App\Models\UserModel();
                $userData = $userModel->find($activeSession['user_id']);

                if ($userData) {
                    session()->set([
                        'logged_in' => true,
                        'user' => $userData,
                        'student_id' => $activeSession['student_id']
                    ]);

                    $studentId = $activeSession['student_id'];
                    log_message('info', "[CBT::mulai] Session restored for student {$studentId} after refresh");
                }
            }
        }

        return $studentId;
    }

    /**
     * Mark session as exam-active
     */
    protected function markExamActive(int $testId): void
    {
        session()->setTempdata('exam_active', true, 14400); // 4 hours
        session()->set('active_exam_test_id', $testId);
    }

    /**
     * Get test data with caching
     */
    protected function getTestData(int $testId): ?array
    {
        $db = db_connect();
        $cacheKey = 'cbt_test_info_' . $testId;
        return cache_remember($cacheKey, 300, function () use ($db, $testId) {
            return $db->table('cbt_test_status ts')
                ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->where('ts.id', $testId)
                ->get()
                ->getRowArray();
        });
    }

    /**
     * Get student data with caching
     */
    protected function getStudentData(int $studentId): ?array
    {
        $studentCacheKey = 'student_data_' . $studentId;
        return cache_remember($studentCacheKey, 300, function () use ($studentId) {
            return $this->studentModel
                ->select('students.*, classes.name AS class_name')
                ->join('student_records', 'student_records.student_id = students.id', 'left')
                ->join('classes', 'classes.id = student_records.class_id', 'left')
                ->orderBy('student_records.id', 'DESC')
                ->find($studentId);
        });
    }

    /**
     * Validate exam access permissions
     */
    protected function validateExamAccess(array $test, array $student): array
    {
        $nowStr = date('Y-m-d H:i:s');
        $className = $student['class_name'];
        $studentReligion = strtolower(trim($student['religion'] ?? ''));

        // Check if test is visible
        if ((int) $test['is_visible'] !== 1) {
            return ['valid' => false, 'message' => 'Ujian ini tidak aktif.'];
        }

        // Check start time
        if ($nowStr < $test['start_time']) {
            return ['valid' => false, 'message' => 'Ujian belum dimulai.'];
        }

        // Check end time
        if ($nowStr > $test['end_time']) {
            return ['valid' => false, 'message' => 'Waktu ujian telah berakhir.'];
        }

        // Check class permission
        $allowedClasses = json_decode($test['class_codes'], true) ?? [];
        if (!in_array($className, $allowedClasses)) {
            return ['valid' => false, 'message' => 'Ujian ini tidak ditujukan untuk kelas Anda.'];
        }

        // Check religion for agama subjects
        if (strtolower(trim($test['subject_type'] ?? '')) === 'agama') {
            $testReligion = strtolower(trim($test['religion'] ?? ''));
            if ($testReligion !== $studentReligion) {
                return ['valid' => false, 'message' => 'Ujian ini hanya untuk siswa dengan agama tertentu.'];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get or create exam session
     */
    protected function getOrCreateSession(int $studentId, int $testId, array $test): array
    {
        // 🔒 LOCK: Prevent race condition from double-click
        $db = db_connect();
        $db->transStart();

        // Use FOR UPDATE to lock the row
        $existingSession = $db->table('cbt_sessions')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        // Check if already finished
        if ($existingSession && ($existingSession['status'] === 'finished')) {
            $db->transComplete();
            return [
                'redirect' => redirect()->to(site_url('siswa/cbt'))->with('info', 'Ujian ini sudah Anda selesaikan.')
            ];
        }

        $nowTs = time();
        $needCreate = false;
        $session = null;

        if ($existingSession) {
            // Check for admin reset token mismatch
            $clientActiveTest = session()->get('active_test') ?? [];
            $clientToken = $clientActiveTest['reset_token'] ?? '';
            $serverToken = $existingSession['reset_token'] ?? '';

            if (!empty($serverToken) && $clientToken !== $serverToken) {
                // Admin reset - clear answers and mark for recreation
                $this->clearStudentAnswers($studentId, $testId);
                $needCreate = true;
            } else {
                // Check if session expired
                $sessionEndTs = $this->calculateSessionEndTime($existingSession, $test);
                if ($sessionEndTs === null || $nowTs >= $sessionEndTs) {
                    $needCreate = true;
                } else {
                    // Check if question_order is empty (failsafe)
                    $qOrderCheck = json_decode($existingSession['question_order'] ?? '[]', true);
                    if (empty($qOrderCheck)) {
                        $needCreate = true;
                    } else {
                        // Reuse existing session
                        $session = $existingSession;
                    }
                }
            }
        } else {
            $needCreate = true;
        }

        $isNewSession = false;
        $resetToken = '';

        if ($needCreate) {
            // Clear old answers if exists
            if ($existingSession && !empty($existingSession['id'])) {
                $this->clearStudentAnswers($studentId, $testId);
            }

            $isNewSession = true;

            // Generate question and option orders using service
            $questionOrder = $this->questionService->generateQuestionOrder($test['bank_id'], $test);
            $mergedQuestions = $this->questionService->getQuestionsInOrder($questionOrder);
            $shouldShuffleOptions = ($test['shuffle_option'] ?? 'tidak') === 'ya';
            $optionOrders = $this->questionService->generateOptionOrders($mergedQuestions, $shouldShuffleOptions);

            $resetToken = bin2hex(random_bytes(8));

            $insertData = [
                'student_id' => $studentId,
                'test_id' => $testId,
                'started_at' => $nowTs,
                'status' => 'active',
                'question_order' => json_encode($questionOrder),
                'option_orders' => json_encode($optionOrders),
                'last_activity' => date('Y-m-d H:i:s'),
                'reset_token' => $resetToken,
                'cheat_locked' => 0,
                'finished_at' => null,
                'score' => null,
                'essay_score' => null,
                'total_score' => null
            ];

            if ($existingSession && !empty($existingSession['id'])) {
                $db->table('cbt_sessions')->where('id', $existingSession['id'])->update($insertData);
                $sessionId = $existingSession['id'];
            } else {
                $db->table('cbt_sessions')->insert($insertData);
                $sessionId = $db->insertID();
            }

            $session = $db->table('cbt_sessions')->where('id', $sessionId)->get()->getRowArray();
        } else {
            $resetToken = $session['reset_token'] ?? '';
        }

        $db->transComplete();

        return [
            'session' => $session,
            'isNewSession' => $isNewSession,
            'resetToken' => $resetToken
        ];
    }

    /**
     * Calculate session end time
     */
    protected function calculateSessionEndTime(array $session, array $test): ?int
    {
        if (empty($session) || empty($session['started_at'])) {
            return null;
        }

        $startTs = (int) $session['started_at'];
        $baseDuration = ((int) $test['duration']) * 60;
        $extraDuration = ((int) ($session['extra_time'] ?? 0)) * 60;
        $durationSec = max(10, $baseDuration + $extraDuration);

        return $startTs + $durationSec;
    }

    /**
     * Clear student answers for a test
     */
    protected function clearStudentAnswers(int $studentId, int $testId): void
    {
        try {
            $this->answerModel->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->delete();
        } catch (\Throwable $e) {
            log_message('error', '[CBT::clearStudentAnswers] Failed: ' . $e->getMessage());
        }
    }

    /**
     * Prepare questions for view with self-healing
     */
    protected function prepareQuestionsForView(array $session, array $test, int $studentId, int $testId): array
    {
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
        $optionOrders = json_decode($session['option_orders'] ?? '{}', true) ?? [];

        $questions = [];

        if (empty($questionOrder)) {
            return ['questions' => $questions];
        }

        // 🚀 OPTIMIZATION: Use cache for questions to avoid repeated parsing
        $cacheKey = 'cbt_questions_' . md5(json_encode($questionOrder));
        $qRows = cache()->remember($cacheKey, 300, function () use ($questionOrder) {
            return $this->questionModel->whereIn('id', $questionOrder)->findAll();
        });

        // Self-healing: Check for stale session data
        $showPg = (int) ($test['show_pg_count'] ?? 0);
        $showPgk = (int) ($test['show_pg_kompleks_count'] ?? 0);
        $showBs = (int) ($test['show_bs_count'] ?? 0);
        $showEsai = (int) ($test['show_esai_count'] ?? 0);
        $expectedTotal = $showPg + $showPgk + $showBs + $showEsai;

        $missingIds = count($questionOrder) - count($qRows);
        $countMismatch = count($questionOrder) !== $expectedTotal;

        if (($missingIds > 0 && count($qRows) < $expectedTotal) || ($countMismatch && $expectedTotal > 0)) {
            log_message('info', "[CBT::mulai] Self-healing triggered for student {$studentId}, test {$testId}");

            $this->clearStudentAnswers($studentId, $testId);
            $questionOrder = $this->questionService->generateQuestionOrder($test['bank_id'], $test);

            $this->sessionModel->update($session['id'] ?? 0, [
                'question_order' => json_encode($questionOrder),
                'option_orders' => '{}'
            ]);

            // Clear cache and refetch
            cache()->delete($cacheKey);
            $qRows = $this->questionModel->whereIn('id', $questionOrder)->findAll();
            $optionOrders = [];
        }

        // Build question map
        $qMap = [];
        foreach ($qRows as $q) {
            $qMap[$q['id']] = $q;
        }

        // Process questions
        foreach ($questionOrder as $qid) {
            if (!isset($qMap[$qid]))
                continue;

            $q = $qMap[$qid];
            $q = $this->processQuestion($q, $optionOrders[$qid] ?? []);
            $questions[] = $q;
        }

        return ['questions' => $questions];
    }

    /**
     * Process single question for display
     */
    protected function processQuestion(array $q, array $optionOrder): array
    {
        $q['question_text_db'] = $q['question_text'] ?? '';
        $q['raw_text_original'] = $q['raw_text'] ?? ''; // Simpan raw_text asli untuk view
        $rawTypeDb = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));

        // 🚀 OPTIMIZATION: Only parse raw_text if question_text is empty or very short
        $parsed = ['question' => '', 'options' => [], 'type' => 'pg', 'keys' => []];
        $needsParsing = !empty($q['raw_text']) && (empty($q['question_text']) || strlen(strip_tags($q['question_text'])) < 10);

        if ($needsParsing) {
            try {
                $parsed = $this->questionService->parseRawQuestion($q['raw_text']);
                $q['question_text'] = trim($parsed['question']);
                if (empty($rawTypeDb) || $rawTypeDb === 'pg' || $rawTypeDb === 'pilihan_ganda') {
                    $rawTypeDb = $parsed['type'];
                }
            } catch (\Throwable $e) {
                log_message('error', "[CBT-PARSE-ERROR] QID {$q['id']}: " . $e->getMessage());
                $q['question_text'] = trim($q['question_text'] ?? '');
            }
        } else {
            $q['question_text'] = trim($q['question_text'] ?? '');
        }

        // Clean question text ONLY if raw_text is empty (old data)
        // If raw_text exists, parseRawQuestion already cleaned it properly
        if (empty($q['raw_text'])) {
            $q['question_text'] = $this->cleanQuestionText($q['question_text']);
        }

        // Normalize type
        $rawType = $this->normalizeQuestionTypeForView($rawTypeDb);
        $q['type_norm'] = $rawType;

        // Extract options
        $opts = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
            $col = 'option_' . strtolower($opt);
            if (!empty($q[$col])) {
                $opts[$opt] = trim($q[$col]);
            }
        }

        // Use parsed options if DB options are empty
        if (empty($opts) && $needsParsing && !empty($parsed['options'])) {
            $opts = $parsed['options'];
        }

        // Validate and apply option order
        $orderKeys = $this->validateOptionOrder($optionOrder, $opts);

        // Build ordered options
        $orderedOpts = [];
        foreach ($orderKeys as $k) {
            if (isset($opts[$k])) {
                $orderedOpts[$k] = $opts[$k];
            }
        }

        $q['options'] = $orderedOpts;
        return $q;
    }

    /**
     * Clean question text from options and keys
     * This ensures backward compatibility with old data that has options in question text
     */
    protected function cleanQuestionText(string $html): string
    {
        $out = $html;

        // Simpan tag img dulu dengan placeholder
        $imgTags = [];
        $out = preg_replace_callback('/<img[^>]+>/i', function ($match) use (&$imgTags) {
            $imgTags[] = $match[0];
            return '[[IMG_' . (count($imgTags) - 1) . ']]';
        }, $out);

        // Remove formatting tags (tapi biarkan p, br, ul, ol, li, table, dll)
        $out = preg_replace('/<\/?(span|font)[^>]*>/i', '', $out);

        // Remove option lines in <p> tags - HANYA yang benar-benar opsi
        $out = preg_replace('/<p[^>]*>\s*[\(]?[A-Ea-e]\s*[:.)\-]\s*[^<]*<\/p>/i', '', $out);

        // Remove option lines (plain text) - HANYA di awal baris
        $out = preg_replace('/^[\(]?[A-Ea-e]\s*[:.)\-]\s*[^\n]*$/im', '', $out);

        // Remove Kunci lines in <p> tags
        $out = preg_replace('/<p[^>]*>\s*Kunci\s*[\:\=\-]\s*[^<]*<\/p>/i', '', $out);

        // Remove Kunci lines (plain text)
        $out = preg_replace('/^Kunci\s*[\:\=\-]\s*.*$/im', '', $out);

        // Remove Tipe lines in <p> tags
        $out = preg_replace('/<p[^>]*>\s*Tipe\s*[\:\=\-]\s*[^<]*<\/p>/i', '', $out);

        // Remove Tipe lines (plain text)
        $out = preg_replace('/^Tipe\s*[\:\=\-]\s*.*$/im', '', $out);

        // Remove ONLY truly empty paragraphs (no content at all)
        $out = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $out);

        // Kembalikan tag img
        foreach ($imgTags as $index => $tag) {
            $out = str_replace('[[IMG_' . $index . ']]', $tag, $out);
        }

        return trim($out);
    }

    /**
     * Normalize question type for view
     */
    protected function normalizeQuestionTypeForView(string $rawType): string
    {
        if ($rawType === 'pgk' || strpos($rawType, 'kompleks') !== false) {
            return 'pg_kompleks';
        }
        if ($rawType === 'bs' || strpos($rawType, 'benar_salah') !== false) {
            return 'benar_salah';
        }
        if ($rawType === 'essay' || $rawType === 'esai') {
            return 'esai';
        }
        if ($rawType === 'pg' || $rawType === 'pilihan_ganda') {
            return 'pg';
        }
        return $rawType;
    }

    /**
     * Validate option order
     */
    protected function validateOptionOrder(array $optionOrder, array $opts): array
    {
        if (empty($optionOrder)) {
            return array_keys($opts);
        }

        $intersect = array_intersect($optionOrder, array_keys($opts));
        if (count($intersect) < count($opts)) {
            return array_keys($opts);
        }

        return $optionOrder;
    }

    /**
     * Get saved answers for student
     */
    protected function getSavedAnswers(int $studentId, int $testId): array
    {
        $rows = $this->answerModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->findAll();

        $savedAnswers = [];
        $doubtfulAnswers = [];

        foreach ($rows as $r) {
            $savedAnswers[$r['question_id']] = $r['answer'];
            $doubtfulAnswers[$r['question_id']] = (int) ($r['is_doubtful'] ?? 0);
        }

        return [
            'savedAnswers' => $savedAnswers,
            'doubtfulAnswers' => $doubtfulAnswers
        ];
    }

    /**
     * Calculate timing data for exam
     */
    protected function calculateTiming(array $session, array $test): array
    {
        $nowTs = time();
        $sessionStartTs = (int) ($session['started_at'] ?? $nowTs);
        $baseDuration = ((int) $test['duration']) * 60;
        $extraDuration = ((int) ($session['extra_time'] ?? 0)) * 60;
        $durationSec = max(10, $baseDuration + $extraDuration);

        $endByDuration = $sessionStartTs + $durationSec;
        $testEndTs = !empty($test['end_time']) ? strtotime($test['end_time']) : null;

        if ($testEndTs) {
            $effectiveEndTs = min($endByDuration, $testEndTs);
        } else {
            $effectiveEndTs = $endByDuration;
        }

        $remainingSeconds = max(0, $effectiveEndTs - $nowTs);
        $elapsedSeconds = $nowTs - $sessionStartTs;

        return [
            'serverStartTs' => (int) $sessionStartTs,
            'remaining_seconds' => (int) $remainingSeconds,
            'test_end_ts' => (int) ($testEndTs ?? 0),
            'durationSec' => (int) $durationSec,
            'elapsed_seconds' => (int) $elapsedSeconds,
            'finish_button_lock' => (float) ($test['finish_button_lock'] ?? 0)
        ];
    }

    /**
     * Save active test session data
     */
    protected function saveActiveTestSession(array $test, int $startedAt, string $resetToken): void
    {
        session()->set('active_test', [
            'id' => $test['id'],
            'started_at_ts' => (int) $startedAt,
            'reset_token' => $resetToken
        ]);
    }

    /**
     * Refresh CSRF Token
     * 
     * Endpoint untuk mendapatkan CSRF token baru tanpa regenerate
     * Digunakan untuk long-running operations seperti CBT
     */
    public function refreshCsrf()
    {
        if (!$this->request->isAJAX()) {
            return $this->failUnauthorized('Invalid request');
        }

        // Get current CSRF token
        $csrfToken = csrf_hash();
        $csrfTokenName = csrf_token();

        return $this->respond([
            'success' => true,
            'csrf_token_name' => $csrfTokenName,
            'csrf_token_hash' => $csrfToken
        ]);
    }

}

