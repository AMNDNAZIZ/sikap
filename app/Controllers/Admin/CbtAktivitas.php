<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtAnswerModel;
use App\Models\CbtQuestionModel;
use App\Models\StudentModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Dompdf\Dompdf;

class CbtAktivitas extends BaseController
{
    protected $sessionModel;
    protected $testModel;
    protected $answerModel;
    protected $questionModel;
    protected $studentModel;
    protected $db;

    public function __construct()
    {
        $this->sessionModel = new CbtSessionModel();
        $this->testModel = new CbtTestStatusModel();
        $this->answerModel = new CbtAnswerModel();
        $this->questionModel = new CbtQuestionModel();
        $this->studentModel = new StudentModel();
        $this->db = db_connect();
    }



    /**
     * 📊 Halaman utama daftar aktivitas (index)
     * Menampilkan daftar ujian yang sudah mulai DAN ada siswa yang mengerjakan
     */
    public function index()
    {
        helper('cbt');
        $context = get_cbt_user_context();
        $now = date('Y-m-d H:i:s');

        $query = $this->db->table('cbt_test_status ts')
            ->select('ts.id AS test_id, qb.code AS test_code, s.name AS subject_name, en.name AS exam_name, ts.start_time, ts.end_time')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left');

        // KRITERIA OTOMATIS: 
        // 1. Ujian sudah masuk waktu mulai (start_time <= now)
        // 2. Minimal ada 1 siswa yang sudah/sedang mengerjakan (exists in cbt_sessions)
        $query->where('ts.start_time <=', $now);
        $query->where("EXISTS (SELECT 1 FROM cbt_sessions cs WHERE cs.test_id = ts.id)", null, false);

        // Filter untuk guru - hanya lihat aktivitas ujian yang ia jadwalkan
        if ($context['is_teacher'] && $context['user_id']) {
            $query->where('ts.created_by', $context['user_id']);
        }

        $sessions = $query
            ->orderBy('ts.start_time', 'DESC')
            ->get()->getResultArray();

        // Add status indicator for each exam
        foreach ($sessions as &$session) {
            $startTime = strtotime($session['start_time']);
            $endTime = strtotime($session['end_time']);
            $currentTime = strtotime($now);

            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                $session['status'] = 'ongoing'; // Sedang berlangsung
            } elseif ($currentTime > $endTime) {
                $session['status'] = 'finished'; // Sudah selesai
            } else {
                $session['status'] = 'upcoming'; // Belum dimulai
            }
        }

        $classes = $this->db->table('classes')->select('id, name')->get()->getResultArray();

        return view('admin/cbt/aktivitas/index', [
            'sessions' => $sessions,
            'classes' => $classes
        ]);
    }

    /**
     * Alias untuk index() sesuai route lama
     */
    public function aktivitasIndex()
    {
        return $this->index();
    }

    /**
     * 📈 Detail aktivitas ujian (per tes)
     */
    public function detail($testId)
    {
        helper('cbt');
        $context = get_cbt_user_context();

        // Validasi ownership - guru hanya bisa lihat aktivitas ujian yang ia jadwalkan
        if (!can_access_cbt_test_status($testId)) {
            return redirect()->to(site_url('admin/cbt/aktivitas'))->with('error', 'Anda tidak memiliki akses ke aktivitas ujian ini.');
        }

        // 🔹 FORCE NO CACHE - Prevent browser caching
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        
        $db = $this->db;

        // 🔹 CLEAR CACHE untuk memastikan data fresh
        cache()->delete('cbt_test_detail_' . $testId);

        // Get test info (no cache for debugging)
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->to(site_url('admin/cbt/aktivitas'))->with('error', 'Data ujian tidak ditemukan.');
        }

        // 🔹 FIX V3: Get all sessions, log them, then filter
        $allSessions = $db->table('cbt_sessions cs')
            ->select('cs.id, cs.test_id, cs.student_id, cs.question_order, cs.option_orders,
                      cs.status, cs.score, cs.essay_score, cs.total_score, cs.started_at, 
                      cs.finished_at, cs.last_activity, cs.extra_time, cs.updated_at, cs.created_at,
                      st.name AS student_name, st.nis, st.username, c.name AS class_name')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->where('cs.test_id', $testId)
            ->orderBy('cs.id', 'DESC') // Latest first
            ->get()->getResultArray();

        log_message('info', "[DetailAktivitas] Test ID: $testId - Total sessions from DB: " . count($allSessions));

        // Debug: Log all sessions
        foreach ($allSessions as $idx => $s) {
            log_message('debug', "[DetailAktivitas] Session #{$idx}: ID={$s['id']}, Student={$s['student_id']} ({$s['student_name']}), Status={$s['status']}");
        }

        // Filter to get only latest session per student
        $seenStudents = [];
        $sessions = [];
        foreach ($allSessions as $session) {
            $studentId = $session['student_id'];
            if (!isset($seenStudents[$studentId])) {
                $seenStudents[$studentId] = true;
                $sessions[] = $session;
                log_message('debug', "[DetailAktivitas] KEEPING Session ID={$session['id']} for Student={$studentId} ({$session['student_name']})");
            } else {
                log_message('debug', "[DetailAktivitas] SKIPPING Session ID={$session['id']} for Student={$studentId} ({$session['student_name']}) - already have newer session");
            }
        }

        log_message('info', "[DetailAktivitas] Test ID: $testId - Unique students after filter: " . count($sessions));

        // Sort by class and name
        usort($sessions, function($a, $b) {
            $classCompare = strcmp($a['class_name'] ?? '', $b['class_name'] ?? '');
            if ($classCompare !== 0) return $classCompare;
            return strcmp($a['student_name'] ?? '', $b['student_name'] ?? '');
        });

        // Get violation counts for all students in this test (single query)
        $violationCounts = $db->table('cbt_cheat_logs')
            ->select('student_id, COUNT(*) as violation_count')
            ->where('test_id', $testId)
            ->groupBy('student_id')
            ->get()
            ->getResultArray();

        // Map violation counts by student_id
        $violationMap = [];
        foreach ($violationCounts as $v) {
            $violationMap[$v['student_id']] = $v['violation_count'];
        }

        // Get questions (no cache for debugging)
        $questions = $db->table('cbt_questions')
            ->where('bank_id', $test['bank_id'])
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $noBankMap = [];
        $counter = 1;
        foreach ($questions as $q) {
            $noBankMap[$q['id']] = $counter++;
        }

        // Hitung sisa waktu per sesi
        $now = time();
        foreach ($sessions as &$s) {
            $order = json_decode($s['question_order'], true) ?? [];
            $noUserMap = [];
            $i = 1;
            foreach ($order as $qid) {
                $noUserMap[$qid] = $i++;
            }

            $s['no_bank_map'] = $noBankMap;
            $s['no_user_map'] = $noUserMap;
            $s['violation_count'] = $violationMap[$s['student_id']] ?? 0;

            // Hitung sisa waktu
            $startTs = 0;
            if (!empty($s['started_at'])) {
                $startTs = is_numeric($s['started_at']) ? (int) $s['started_at'] : strtotime((string) $s['started_at']);
                // Safety check: if startTs is invalid (e.g. 0), use now to avoid wild math
                if (!$startTs || $startTs < 100000)
                    $startTs = $now;

                // Update started_at to timestamp for the view
                $s['started_at'] = $startTs;

                $endTs = $now;

                // Jika status finished, "waktu berhenti" adalah finished_at
                // Jika finished_at tidak valid, gunakan updated_at sebagai fallback terbaik
                if ($s['status'] === 'finished') {
                    $finishValid = false;
                    if (!empty($s['finished_at'])) {
                        $fStr = (string) $s['finished_at'];
                        if ($fStr !== '0' && substr($fStr, 0, 10) !== '0000-00-00') {
                            $parsedEnd = is_numeric($fStr) ? (int) $fStr : strtotime($fStr);
                            if ($parsedEnd > 315360000) {
                                $endTs = $parsedEnd;
                                $finishValid = true;
                            }
                        }
                    }

                    if (!$finishValid && !empty($s['updated_at'])) {
                        $uStr = (string) $s['updated_at'];
                        $parsedUpd = is_numeric($uStr) ? (int) $uStr : strtotime($uStr);
                        if ($parsedUpd > 315360000) {
                            $endTs = $parsedUpd;
                        }
                    }
                }

                // Pastikan End tidak lebih kecil dari Start
                if ($endTs < $startTs) {
                    $endTs = $startTs;
                }

                $elapsed = ($endTs - $startTs) / 60;
                $remaining = $test['duration'] - $elapsed;
                $s['remaining_minutes'] = max(0, floor($remaining));
            } else {
                $s['started_at'] = 0;
                $s['remaining_minutes'] = 0;
            }

            // Fallback score agar tidak "KOSONG" jika salah satu null
            $s['display_score'] = $s['total_score'] ?? $s['score'] ?? 0;
        }
        unset($s); // 🔹 CRITICAL: Break reference to avoid corruption in next loop

        // Hitung jumlah yang belum selesai (status active)
        $count_belum = 0;
        foreach ($sessions as $s) {
            if ($s['status'] === 'active') {
                $count_belum++;
            }
        }

        log_message('info', "[DetailAktivitas] Test ID: $testId - Final sessions to display: " . count($sessions) . ", Active: $count_belum");

        return view('admin/cbt/aktivitas/detail', [
            'test' => $test,
            'sessions' => $sessions,
            'noBankMap' => $noBankMap,
            'count_belum' => $count_belum
        ]);
    }

    private function getRemainingMinutes($session)
    {
        if (empty($session['started_at']))
            return 0;
        $duration = (int) ($session['duration'] ?? 0);
        $elapsed = (time() - (int) $session['started_at']) / 60;
        return round(max(0, $duration - $elapsed));
    }

    /**
     * 📄 Modal detail jawaban siswa
     */
    public function detail_jawaban($sessionId)
    {
        $db = db_connect();

        // Ambil session & test info (termasuk bank_id)
        $session = $db->table('cbt_sessions s')
            ->select('s.*, ts.bank_id, ts.id AS test_id')
            ->join('cbt_test_status ts', 'ts.id = s.test_id', 'left')
            ->where('s.id', $sessionId)
            ->get()
            ->getRowArray();

        if (!$session) {
            return '<div class="text-danger">❌ Sesi tidak ditemukan.</div>';
        }

        $bankId = $session['bank_id'];
        $studentId = $session['student_id'];
        $testId = $session['test_id'];

        // 1) Mapping: No di Bank Soal (urut berdasarkan id ASC pada bank soal)
        $bankQuestions = $db->table('cbt_questions')
            ->select('id')
            ->where('bank_id', $bankId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $noBankMap = [];
        $no = 1;
        foreach ($bankQuestions as $q) {
            $noBankMap[$q['id']] = $no++;
        }

        // 2) Ambil question_order dari session => ini menentukan "No di User"
        $questionOrder = json_decode($session['question_order'] ?? '[]', true);
        // Pastikan questionOrder adalah array
        if (!is_array($questionOrder))
            $questionOrder = [];

        // 3) Ambil jawaban siswa untuk test ini (indexable by question_id)
        $answerRows = $db->table('cbt_answers')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->get()
            ->getResultArray();

        $answerMap = [];
        foreach ($answerRows as $a) {
            // Kolom utama adalah 'answer'
            $val = $a['answer'] ?? '';

            $answerMap[$a['question_id']] = $val;
        }

        // 4) Build Dashboard & HTML
        $totalSoal = count($bankQuestions);
        $totalJawab = count($answerMap);
        $percent = $totalSoal > 0 ? round(($totalJawab / $totalSoal) * 100) : 0;
        $empty = $totalSoal - $totalJawab;

        $html = "
      <div class='row g-2 mb-3 text-center'>
        <div class='col-4'>
          <div class='p-2 border rounded bg-light'>
            <small class='text-muted d-block'>Total Soal</small>
            <h5 class='mb-0'>{$totalSoal}</h5>
          </div>
        </div>
        <div class='col-4'>
          <div class='p-2 border rounded bg-success-subtle border-success'>
            <small class='text-muted d-block'>Sudah Dijawab</small>
            <h5 class='mb-0 text-success'>{$totalJawab}</h5>
          </div>
        </div>
        <div class='col-4'>
          <div class='p-2 border rounded bg-warning-subtle border-warning'>
            <small class='text-muted d-block'>Belum Dijawab</small>
            <h5 class='mb-0 text-warning'>{$empty}</h5>
          </div>
        </div>
      </div>

      <div class='progress mb-4' style='height: 20px;'>
        <div class='progress-bar progress-bar-striped progress-bar-animated bg-success' 
             role='progressbar' style='width: {$percent}%' 
             aria-valuenow='{$percent}' aria-valuemin='0' aria-valuemax='100'>
             {$percent}% Pelaksanaan
        </div>
      </div>

      <div class='mb-4'>
        <h6 class='mb-2'>Visual Progres:</h6>
        <div class='d-flex flex-wrap gap-1 justify-content-center'>";

        // Tampilkan Grid Kotak Jawaban
        foreach ($questionOrder as $idx => $qid) {
            $noUser = $idx + 1;
            $hasAnswer = isset($answerMap[$qid]);
            $bgClass = $hasAnswer ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary-subtle';

            $html .= "<div class='border rounded d-flex align-items-center justify-content-center {$bgClass}' 
                       style='width:35px; height:35px; font-size:12px; font-weight:bold;' 
                       title='Soal No {$noUser} " . ($hasAnswer ? '(Sudah Dijawab)' : '(Belum)') . "'>
                    {$noUser}
                  </div>";
        }

        $html .= "
        </div>
      </div>

      <h6 class='mb-2'>Rincian Jawaban:</h6>
      <div class='table-responsive'>
        <table class='table table-bordered table-sm text-center align-middle'>
          <thead class='table-light'>
            <tr>
              <th>No di User</th>
              <th>ID Soal</th>
              <th>No di Bank</th>
              <th>Jawaban</th>
            </tr>
          </thead>
          <tbody>
    ";

        // Tampilkan baris sesuai urutan questionOrder (No di User)
        foreach ($questionOrder as $idx => $qid) {
            $idSoal = $qid;
            $noBank = $noBankMap[$qid] ?? '-';
            $noUser = $idx + 1;
            $jawab = $answerMap[$qid] ?? '-';

            $bgRow = !isset($answerMap[$qid]) ? 'table-warning' : '';

            // Escape minimal untuk keamanan
            $jawabDisplay = is_string($jawab) ? esc($jawab) : json_encode($jawab);

            $html .= "
          <tr class='{$bgRow}'>
            <td class='fw-bold text-primary'>{$noUser}</td>
            <td>{$idSoal}</td>
            <td>{$noBank}</td>
            <td style='white-space:normal; max-width:420px; font-size:13px;'>{$jawabDisplay}</td>
          </tr>";
        }

        // Jika ada soal di bank yang tidak ada di questionOrder (sebagai fallback)
        $missing = array_diff(array_column($bankQuestions, 'id'), $questionOrder);
        if (!empty($missing)) {
            foreach ($missing as $qid) {
                $idSoal = $qid;
                $noBank = $noBankMap[$qid] ?? '-';
                $noUser = '-';
                $jawab = $answerMap[$qid] ?? '-';
                $jawabDisplay = is_string($jawab) ? esc($jawab) : json_encode($jawab);

                $html .= "
              <tr class='table-secondary'>
                <td>{$noUser}</td>
                <td>{$idSoal}</td>
                <td>{$noBank}</td>
                <td style='white-space:normal; max-width:420px; font-size:13px;'>{$jawabDisplay}</td>
              </tr>";
            }
        }

        $html .= "</tbody></table></div>";

        return $html;
    }

    /**
     * 🛑 Paksa menyelesaikan ujian siswa
     */
    public function forceFinish($sessionId)
    {
        // Log the request for debugging
        log_message('info', '[ForceFinish] Request received for session: ' . $sessionId);

        // Validate if this is an AJAX request
        if (!$this->request->isAJAX()) {
            log_message('error', '[ForceFinish] Not an AJAX request');
            return $this->response->setJSON(['error' => 'Invalid request'])->setStatusCode(400);
        }

        try {
            $session = $this->sessionModel->find($sessionId);
            if (!$session) {
                log_message('error', '[ForceFinish] Session not found: ' . $sessionId);
                return $this->response->setJSON(['error' => 'Sesi tidak ditemukan'])->setStatusCode(404);
            }

            // Check if already finished
            if ($session['status'] === 'finished') {
                log_message('warning', '[ForceFinish] Session already finished: ' . $sessionId);
                return $this->response->setJSON(['error' => 'Sesi sudah selesai'])->setStatusCode(400);
            }

            $studentId = $session['student_id'];
            $testId = $session['test_id'];

            log_message('info', '[ForceFinish] Processing - Student: ' . $studentId . ', Test: ' . $testId);

            $db = \Config\Database::connect();
            $db->transStart();
            // 🔹 Ambil info tes untuk dapat bank_id
            $testStatus = $this->testModel->find($testId);
            if (!$testStatus) {
                log_message('error', '[ForceFinish] Test status not found: ' . $testId);
                $db->transRollback();
                return $this->response->setJSON(['error' => 'Data ujian tidak ditemukan'])->setStatusCode(404);
            }
            $bankId = $testStatus['bank_id'];
            log_message('info', '[ForceFinish] Bank ID: ' . $bankId);

            // 🔒 FIX: Get question_order from session (only questions shown to student)
            $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
            log_message('info', '[ForceFinish] Question order count: ' . count($questionOrder));

            if (empty($questionOrder)) {
                // Fallback to all questions if question_order is empty
                log_message('info', '[ForceFinish] Using all questions from bank');
                $questions = $this->questionModel
                    ->select('id, question_type, correct_option, score')
                    ->where('bank_id', $bankId)
                    ->findAll();
            } else {
                // 🔒 FIX: Fetch ONLY questions that were shown to student
                log_message('info', '[ForceFinish] Using question_order from session');
                $questions = $this->questionModel
                    ->select('id, question_type, correct_option, score')
                    ->whereIn('id', $questionOrder)
                    ->findAll();
            }

            if (empty($questions)) {
                log_message('error', '[ForceFinish] No questions found for bank: ' . $bankId);
                $db->transRollback();
                return $this->response->setJSON(['error' => 'Tidak ada soal ditemukan'])->setStatusCode(404);
            }

            log_message('info', '[ForceFinish] Found ' . count($questions) . ' questions');

            // 🔹 Ambil semua jawaban siswa
            $answers = $this->answerModel
                ->select('question_id, answer')
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->findAll();

            log_message('info', '[ForceFinish] Found ' . count($answers) . ' answers');

            $answerMap = [];
            foreach ($answers as $a) {
                $answerMap[$a['question_id']] = $a['answer'];
            }

            // ✅ FIX: Calculate score with bobot (same logic as selesai() page)
            // Initialize stats
            $stats = [
                'pg' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_pg'] ?? 0)],
                'pgk' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_pg_kompleks'] ?? 0)],
                'bs' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_bs'] ?? 0)],
                'esai' => ['total' => 0, 'earned' => (float) ($session['essay_score'] ?? 0), 'weight' => (float) ($testStatus['bobot_esai'] ?? 0)]
            ];

            foreach ($questions as $q) {
                $type = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
                $qid = $q['id'];
                $studentAns = $answerMap[$qid] ?? null;
                $correctAns = $q['correct_option'] ?? '';

                $score = $this->calculateNormalizedScore($type, $studentAns, $correctAns);

                if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                    $stats['pg']['total']++;
                    $stats['pg']['earned'] += $score;
                } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                    $stats['pgk']['total']++;
                    $stats['pgk']['earned'] += $score;
                } elseif ($type === 'benar_salah' || $type === 'bs') {
                    $stats['bs']['total']++;
                    $stats['bs']['earned'] += $score;
                } elseif (in_array($type, ['esai', 'essay'])) {
                    $stats['esai']['total']++;
                }
            }

            // Calculate final score with bobot
            $stats['pg']['score'] = ($stats['pg']['total'] > 0) ? ($stats['pg']['earned'] / $stats['pg']['total']) * 100 : 0;
            $stats['pgk']['score'] = ($stats['pgk']['total'] > 0) ? ($stats['pgk']['earned'] / $stats['pgk']['total']) * 100 : 0;
            $stats['bs']['score'] = ($stats['bs']['total'] > 0) ? ($stats['bs']['earned'] / $stats['bs']['total']) * 100 : 0;
            $stats['esai']['score'] = ($stats['esai']['total'] > 0) ? ($stats['esai']['earned']) : 0;

            $stats['pg']['contribution'] = $stats['pg']['score'] * ($stats['pg']['weight'] / 100);
            $stats['pgk']['contribution'] = $stats['pgk']['score'] * ($stats['pgk']['weight'] / 100);
            $stats['bs']['contribution'] = $stats['bs']['score'] * ($stats['bs']['weight'] / 100);
            $stats['esai']['contribution'] = $stats['esai']['score'] * ($stats['esai']['weight'] / 100);

            $finalScore = round($stats['pg']['contribution'] + $stats['pgk']['contribution'] + $stats['bs']['contribution'] + $stats['esai']['contribution'], 2);

            log_message('info', '[ForceFinish] Calculated score: ' . $finalScore);

            // 🔹 Update sesi: tandai selesai dan kunci
            $updateData = [
                'status' => 'finished',
                'finished_at' => date('Y-m-d H:i:s'),
                'last_activity' => date('Y-m-d H:i:s'),
                'score' => $finalScore,
                'essay_score' => null, // bisa diisi manual oleh pengawas
                'total_score' => $finalScore,
                'locked' => 1, // siswa tidak bisa simpan jawaban lagi
            ];

            log_message('info', '[ForceFinish] Updating session with data: ' . json_encode($updateData));

            $this->sessionModel->update($sessionId, $updateData);

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', '[ForceFinish] Transaction failed for session: ' . $sessionId);
                return $this->response->setJSON([
                    'error' => 'Gagal memaksa penyelesaian ujian'
                ])->setStatusCode(500);
            }

            log_message('info', '[ForceFinish] Success! Session: ' . $sessionId . ', Score: ' . $finalScore);

            return $this->response->setJSON([
                'success' => true,
                'session_id' => $sessionId,
                'score' => $finalScore,
                'message' => 'Sesi ujian berhasil dipaksa selesai dan nilai telah dihitung otomatis.',
            ]);

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[ForceFinish] Exception: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            log_message('error', '[ForceFinish] Stack trace: ' . $e->getTraceAsString());
            return $this->response->setJSON([
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }


    /**
     * 🏁 Selesaikan secara paksa SEMUA siswa yang masih 'active' di tes tertentu
     */
    public function forceFinishMassal($testId)
    {
        $testStatus = $this->testModel->find($testId);
        if (!$testStatus) {
            return $this->response->setJSON(['error' => 'Data ujian tidak ditemukan'])->setStatusCode(404);
        }

        $sessions = $this->sessionModel
            ->where('test_id', $testId)
            ->where('status', 'active')
            ->findAll();

        if (empty($sessions)) {
            return $this->response->setJSON(['error' => 'Tidak ada siswa yang sedang aktif ujian.'])->setStatusCode(404);
        }

        $db = \Config\Database::connect();
        $successCount = 0;
        $errors = [];

        foreach ($sessions as $session) {
            $db->transStart();
            try {
                $sessionId = $session['id'];
                $studentId = $session['student_id'];

                // 🔒 Fetch ONLY questions that were shown to student
                $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
                if (empty($questionOrder)) {
                    $questions = $this->questionModel
                        ->select('id, question_type, correct_option, score')
                        ->where('bank_id', $testStatus['bank_id'])
                        ->findAll();
                } else {
                    $questions = $this->questionModel
                        ->select('id, question_type, correct_option, score')
                        ->whereIn('id', $questionOrder)
                        ->findAll();
                }

                // Ambil semua jawaban siswa
                $answers = $this->answerModel
                    ->select('question_id, answer')
                    ->where('student_id', $studentId)
                    ->where('test_id', $testId)
                    ->findAll();

                $answerMap = [];
                foreach ($answers as $a) {
                    $answerMap[$a['question_id']] = $a['answer'];
                }

                $stats = [
                    'pg' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_pg'] ?? 0)],
                    'pgk' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_pg_kompleks'] ?? 0)],
                    'bs' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($testStatus['bobot_bs'] ?? 0)],
                    'esai' => ['total' => 0, 'earned' => (float) ($session['essay_score'] ?? 0), 'weight' => (float) ($testStatus['bobot_esai'] ?? 0)]
                ];

                foreach ($questions as $q) {
                    $type = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
                    $score = $this->calculateNormalizedScore($type, $answerMap[$q['id']] ?? null, $q['correct_option'] ?? '');

                    if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                        $stats['pg']['total']++;
                        $stats['pg']['earned'] += $score;
                    } elseif ($type === 'pgk' || $type === 'pg_kompleks') {
                        $stats['pgk']['total']++;
                        $stats['pgk']['earned'] += $score;
                    } elseif ($type === 'bs' || $type === 'benar_salah') {
                        $stats['bs']['total']++;
                        $stats['bs']['earned'] += $score;
                    } elseif (in_array($type, ['esai', 'essay'])) {
                        $stats['esai']['total']++;
                    }
                }

                $stats['pg']['score'] = ($stats['pg']['total'] > 0) ? ($stats['pg']['earned'] / $stats['pg']['total']) * 100 : 0;
                $stats['pgk']['score'] = ($stats['pgk']['total'] > 0) ? ($stats['pgk']['earned'] / $stats['pgk']['total']) * 100 : 0;
                $stats['bs']['score'] = ($stats['bs']['total'] > 0) ? ($stats['bs']['earned'] / $stats['bs']['total']) * 100 : 0;
                $stats['esai']['score'] = ($stats['esai']['total'] > 0) ? $stats['esai']['earned'] : 0;

                $finalScore = round(
                    ($stats['pg']['score'] * $stats['pg']['weight'] / 100) +
                    ($stats['pgk']['score'] * $stats['pgk']['weight'] / 100) +
                    ($stats['bs']['score'] * $stats['bs']['weight'] / 100) +
                    ($stats['esai']['score'] * $stats['esai']['weight'] / 100),
                    2
                );

                $this->sessionModel->update($sessionId, [
                    'status' => 'finished',
                    'finished_at' => date('Y-m-d H:i:s'),
                    'score' => $finalScore,
                    'total_score' => $finalScore,
                    'locked' => 1
                ]);

                $db->transComplete();
                if ($db->transStatus() !== false)
                    $successCount++;
                else
                    $errors[] = "Sesi #{$sessionId} gagal : transaction error";

            } catch (\Throwable $e) {
                $db->transRollback();
                $errors[] = "Sesi #{$session['id']} error: " . $e->getMessage();
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$successCount} siswa berhasil diselesaikan secara paksa.",
            'errors' => $errors
        ]);
    }


    /**
     * ⏰ Tambah waktu ujian siswa
     */
    public function addTime($sessionId)
    {
        $minutes = (int) $this->request->getPost('minutes');
        if ($minutes <= 0)
            return $this->response->setJSON(['error' => 'Waktu tidak valid']);

        $session = $this->sessionModel->find($sessionId);
        if (!$session)
            return $this->response->setJSON(['error' => 'Sesi tidak ditemukan']);

        // Check test end time constraint
        $test = $this->testModel->find($session['test_id']);
        if ($test && !empty($test['end_time'])) {
            // Calculate current theoretical end time
            $durationSec = ((int) $test['duration']) * 60;
            $extraSec = ((int) ($session['extra_time'] ?? 0)) * 60;
            $startTs = (int) $session['started_at'];

            $currentEnd = $startTs + $durationSec + $extraSec;
            $newEnd = $currentEnd + ($minutes * 60);

            $testEndTs = strtotime($test['end_time']);

            if ($newEnd > $testEndTs) {
                // Return error if exceeds
                $maxMinutes = floor(($testEndTs - $currentEnd) / 60);
                if ($maxMinutes <= 0) {
                    return $this->response->setJSON(['error' => 'Tidak bisa tambah waktu. Sudah melewati batas akhir ujian (' . $test['end_time'] . ')']);
                }
                return $this->response->setJSON(['error' => "Hanya bisa menambah maksimal $maxMinutes menit karena batas waktu ujian."]);
            }
        }

        // Update extra_time
        $currentExtra = (int) ($session['extra_time'] ?? 0);
        $this->sessionModel->update($sessionId, [
            'extra_time' => $currentExtra + $minutes
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * 🔄 Reset sesi ujian agar siswa bisa mengulang
     */
    public function resetSession($sessionId)
    {
        // 🔹 Cari sesi berdasarkan ID
        $session = $this->sessionModel->find($sessionId);
        if (!$session) {
            return $this->response->setJSON(['error' => 'Sesi tidak ditemukan'])
                ->setStatusCode(404);
        }

        // 🔹 (Opsional) Validasi hanya sesi tertentu yang bisa direset
        // Izinkan status: active, finished, completed, expired, inactive
        if (!in_array($session['status'], ['active', 'finished', 'completed', 'expired', 'inactive'])) {
            return $this->response->setJSON(['error' => 'Status sesi tidak valid untuk direset (' . $session['status'] . ')'])
                ->setStatusCode(400);
        }

        $studentId = $session['student_id'];
        $testId = $session['test_id'];

        // 🔹 Gunakan transaksi agar aman bila salah satu query gagal
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1️⃣ Count existing data (for logging)
            $existingAnswers = $this->answerModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->countAllResults(false);

            $existingSessions = $this->sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->countAllResults(false);

            $existingLogs = $db->table('cbt_cheat_logs')
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->countAllResults(false);

            log_message('info', "[ResetSession] BEFORE: student={$studentId}, test={$testId}, sessions={$existingSessions}, answers={$existingAnswers}, logs={$existingLogs}");

            // 2️⃣ Delete answers
            $deletedAnswers = $this->answerModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->delete();

            log_message('info', "[ResetSession] Deleted {$deletedAnswers} answers");

            // 3️⃣ Delete cheat logs
            $deletedLogs = $db->table('cbt_cheat_logs')
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->delete();

            log_message('info', "[ResetSession] Deleted {$deletedLogs} cheat logs");

            // 4️⃣ Delete ALL sessions (not just one)
            $deletedSessions = $this->sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->delete();

            log_message('info', "[ResetSession] Deleted {$deletedSessions} sessions");

            // 5️⃣ Verify deletion
            $remainingSessions = $this->sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->countAllResults();

            if ($remainingSessions > 0) {
                log_message('error', "[ResetSession] FAILED: {$remainingSessions} sessions still exist!");
                $db->transRollback();
                return $this->response->setJSON(['error' => 'Gagal menghapus session (masih ada ' . $remainingSessions . ' session tersisa)'])
                    ->setStatusCode(500);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', "[ResetSession] Transaction failed");
                return $this->response->setJSON(['error' => 'Gagal mereset sesi ujian (transaction failed)'])
                    ->setStatusCode(500);
            }

            // 6️⃣ Clear cache
            cache()->delete("cbt_test_info_{$testId}");
            cache()->delete("student_data_{$studentId}");
            cache()->delete("cbt_test_detail_{$testId}");

            // Get bank_id from test for cache clearing
            $test = $this->testModel->find($testId);
            if ($test && !empty($test['bank_id'])) {
                cache()->delete("cbt_questions_bank_" . $test['bank_id']);
                cache()->delete("cbt_all_questions_bank_" . $test['bank_id']);
            }

            log_message('info', "[ResetSession] SUCCESS: student={$studentId}, test={$testId}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sesi ujian berhasil dihapus. Siswa dapat memulai ujian dari awal.',
                'deleted' => [
                    'sessions' => $deletedSessions,
                    'answers' => $deletedAnswers,
                    'cheat_logs' => $deletedLogs
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', "[ResetSession] Exception: " . $e->getMessage());
            return $this->response->setJSON(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->setStatusCode(500);
        }
    }

    /**
     * 🔓 Reset login session (active_session_id & last_activity) for a student
     * Used so the student can immediately login from another browser/device
     */
    public function resetLogin($studentId)
    {
        // Require AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        try {
            // Find user where related_id = studentId and role_id = 3 (Student)
            $db = \Config\Database::connect();
            $user = $db->table('users')
                ->where('related_id', $studentId)
                ->where('role_id', 3)
                ->get()
                ->getRowArray();

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Data user siswa tidak ditemukan']);
            }

            // Update user table
            $db->table('users')->where('id', $user['id'])->update([
                'active_session_id' => null,
                'last_activity' => null
            ]);

            log_message('info', "[ResetLogin] SUCCESS: user={$user['id']}, student={$studentId}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sesi login berhasil di-reset. Siswa bisa login kembali.'
            ]);
        } catch (\Throwable $e) {
            log_message('error', "[ResetLogin] Exception: " . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Terjadi kesalahan internal.']);
        }
    }
    /**
     * 🔄 Reset sessions for a specific test (Full or Selective)
     */
    public function resetMassal($testId)
    {
        // 🔹 FIX: Get array from POST (handles both session_ids and session_ids[])
        $sessionIds = $this->request->getPost('session_ids');
        
        // If not found, try with [] suffix (jQuery traditional format)
        if (empty($sessionIds)) {
            $sessionIds = $this->request->getPost('session_ids[]');
        }

        // 🔹 Use transaction for safety
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if (!empty($sessionIds) && is_array($sessionIds)) {
                // --- SELECTIVE RESET ---

                // 1) Get student IDs associated with these sessions to clean answers/logs
                $sessions = $this->sessionModel
                    ->select('student_id')
                    ->whereIn('id', $sessionIds)
                    ->where('test_id', $testId)
                    ->findAll();

                $studentIds = array_column($sessions, 'student_id');

                if (empty($studentIds)) {
                    $db->transRollback();
                    return $this->response->setJSON(['error' => 'Sesi terpilih tidak ditemukan'])->setStatusCode(404);
                }

                // 2) Delete answers
                $deletedAnswers = $this->answerModel
                    ->whereIn('student_id', $studentIds)
                    ->where('test_id', $testId)
                    ->delete();

                // 3) Delete cheat logs
                $deletedLogs = $db->table('cbt_cheat_logs')
                    ->whereIn('student_id', $studentIds)
                    ->where('test_id', $testId)
                    ->delete();

                // 4) Delete sessions
                $deletedSessions = $this->sessionModel
                    ->whereIn('id', $sessionIds)
                    ->where('test_id', $testId)
                    ->delete();

                $message = count($sessionIds) . ' sesi terpilih berhasil direset.';

            } else {
                // --- FULL RESET (ALL) ---

                // 1) Delete answers
                $deletedAnswers = $this->answerModel
                    ->where('test_id', $testId)
                    ->delete();

                // 2) Delete cheat logs
                $deletedLogs = $db->table('cbt_cheat_logs')
                    ->where('test_id', $testId)
                    ->delete();

                // 3) Delete sessions
                $deletedSessions = $this->sessionModel
                    ->where('test_id', $testId)
                    ->delete();

                $message = 'Seluruh sesi ujian berhasil direset.';
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', "[ResetMassal] Transaction failed");
                return $this->response->setJSON(['error' => 'Gagal mereset sesi ujian (transaction failed)'])
                    ->setStatusCode(500);
            }

            // 5️⃣ Clear cache
            cache()->delete("cbt_test_info_{$testId}");
            cache()->delete("cbt_test_detail_{$testId}");

            // Get bank_id from test for cache clearing
            $test = $this->testModel->find($testId);
            if ($test && !empty($test['bank_id'])) {
                cache()->delete("cbt_questions_bank_" . $test['bank_id']);
                cache()->delete("cbt_all_questions_bank_" . $test['bank_id']);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message . ' Siswa dapat mengulang dari awal.',
                'deleted' => [
                    'sessions' => $deletedSessions,
                    'answers' => $deletedAnswers,
                    'cheat_logs' => $deletedLogs
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', "[ResetMassal] Exception: " . $e->getMessage());
            return $this->response->setJSON(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->setStatusCode(500);
        }
    }



    public function belumTes($testId)
    {
        $db = $this->db;

        $test = $db->table('cbt_test_status')
            ->select('id, class_codes, bank_id')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return $this->response->setBody('<div class="text-danger">Tes tidak ditemukan</div>');
        }

        $classList = json_decode($test['class_codes'] ?? '[]', true);
        if (empty($classList)) {
            return $this->response->setBody('<div class="text-warning">Tidak ada kelas terkait ujian ini.</div>');
        }

        // ✅ Ambil siswa berdasarkan students.class_id
        $students = $db->table('students st')
            ->select('st.id, st.name AS student_name, c.name AS class_name')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->whereIn('c.name', $classList)
            ->get()
            ->getResultArray();

        $done = $db->table('cbt_sessions')
            ->select('student_id')
            ->where('test_id', $testId)
            ->get()
            ->getResultArray();

        $doneIds = array_column($done, 'student_id');
        $notTested = array_filter($students, fn($s) => !in_array($s['id'], $doneIds));

        if (empty($notTested)) {
            return $this->response->setBody('<div class="text-success">Semua siswa telah mengikuti tes ini 🎉</div>');
        }

        $html = "<table class='table table-bordered table-sm align-middle'>
            <thead class='table-light text-center'>
                <tr>
                    <th style='width:50px;'>No</th>
                    <th class='text-start'>Nama Siswa</th>
                    <th style='width:150px;'>Kelas</th>
                </tr>
            </thead>
            <tbody>";

        $i = 1;
        foreach ($notTested as $row) {
            $html .= "<tr>
                <td class='text-center'>{$i}</td>
                <td class='text-start'>" . esc($row['student_name']) . "</td>
                <td class='text-center'>" . esc($row['class_name']) . "</td>
            </tr>";
            $i++;
        }

        $html .= "</tbody></table>";
        return $this->response->setBody($html);
    }


    public function unduhNilai($testId)
    {
        $classFilter = $this->request->getGet('class') ?? 'all';
        $db = $this->db;

        $test = $db->table('cbt_test_status ts')
            ->select('ts.id, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Data ujian tidak ditemukan.');
        }

        // FIX: Handle duplicate sessions - only get latest session per student using MAX(id)
        // Build subquery first to ensure we only get latest session per student
        $subquery = '(
            SELECT student_id, MAX(id) as max_session_id
            FROM cbt_sessions
            WHERE test_id = ' . (int) $testId;

        // Apply class filter in subquery if needed
        if ($classFilter !== 'all') {
            $subquery .= ' AND student_id IN (
                SELECT id FROM students WHERE class_id = ' . (int) $classFilter . '
            )';
        }

        $subquery .= ' GROUP BY student_id
        ) latest';

        $builder = $db->table('cbt_sessions cs')
            ->select('cs.student_id, st.name AS student_name, st.nis, c.name AS class_name, 
                      COALESCE(cs.total_score, cs.score, 0) as final_score, 
                      cs.status, cs.finished_at')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->join($subquery, 'cs.id = latest.max_session_id', 'inner')
            ->where('cs.test_id', $testId)
            ->orderBy('c.name', 'ASC')
            ->orderBy('st.name', 'ASC');

        $data = $builder->get()->getResultArray();

        // DEBUG: Check for duplicate student_ids
        $studentIds = array_column($data, 'student_id');
        $studentNames = array_column($data, 'student_name');
        $duplicates = array_diff_assoc($studentIds, array_unique($studentIds));

        log_message('info', '[unduhNilai] Test ID: ' . $testId . ', Total rows: ' . count($data));
        log_message('info', '[unduhNilai] Student IDs: ' . json_encode($studentIds));
        log_message('info', '[unduhNilai] Student Names: ' . json_encode($studentNames));

        if (!empty($duplicates)) {
            log_message('error', '[unduhNilai] Found duplicate student_ids in result: ' . json_encode($duplicates));
            log_message('error', '[unduhNilai] Full data: ' . json_encode($data));
        }

        // Get violation counts for all students in this test
        $violationCounts = $db->table('cbt_cheat_logs')
            ->select('student_id, COUNT(*) as violation_count')
            ->where('test_id', $testId)
            ->groupBy('student_id')
            ->get()
            ->getResultArray();

        // Map violation counts by student_id
        $violationMap = [];
        foreach ($violationCounts as $v) {
            $violationMap[$v['student_id']] = $v['violation_count'];
        }

        // Add violation count to each student record (without reference to avoid issues)
        $dataWithViolations = [];
        foreach ($data as $d) {
            $d['violation_count'] = $violationMap[$d['student_id']] ?? 0;
            $dataWithViolations[] = $d;
        }
        $data = $dataWithViolations;

        if (empty($data)) {
            return redirect()->back()->with('error', 'Tidak ada data nilai yang ditemukan.');
        }

        // === Generate Spreadsheet (tidak berubah) ===
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'REKAP NILAI UJIAN');
        $sheet->setCellValue('A2', $test['exam_name'] . ' - ' . $test['subject_name'] . ' [' . $test['bank_code'] . ']');
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        $sheet->fromArray(['No', 'NIS', 'Nama Siswa', 'Kelas', 'Nilai', 'Status', 'Pelanggaran'], null, 'A4');
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal('center');

        $row = 5;
        $no = 1;
        foreach ($data as $d) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $d['nis'] ?? '-');
            $sheet->setCellValue("C{$row}", $d['student_name']);
            $sheet->setCellValue("D{$row}", $d['class_name']);
            $sheet->setCellValue("E{$row}", $d['final_score']); // Changed from total_score to final_score
            $sheet->setCellValue("F{$row}", strtoupper($d['status']));
            $sheet->setCellValue("G{$row}", $d['violation_count']);
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getStyle('A5:A' . $row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('B5:B' . $row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('E5:G' . $row)->getAlignment()->setHorizontal('center');

        $fileName = 'Nilai_' . str_replace(' ', '_', $test['subject_name']) . '_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$fileName}\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }


    public function analisisSoal($testId)
    {
        $db = $this->db;

        // ambil info test
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Data ujian tidak ditemukan.');
        }

        // ambil semua soal di bank
        $questions = $db->table('cbt_questions')
            ->where('bank_id', $test['bank_id'])
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // ambil statistik jawaban untuk PG biasa (berdasarkan test_id)
        $answersStats = $db->table('cbt_answers ca')
            ->select('ca.question_id, COUNT(*) AS total,
                     SUM(CASE WHEN q.correct_option = ca.answer THEN 1 ELSE 0 END) AS benar')
            ->join('cbt_questions q', 'q.id = ca.question_id', 'left')
            ->where('ca.test_id', $testId)
            ->whereIn('q.question_type', ['pg', 'pilihan_ganda', 'multiple_choice'])
            ->groupBy('ca.question_id')
            ->get()
            ->getResultArray();

        $statsMap = [];
        foreach ($answersStats as $r) {
            $statsMap[$r['question_id']] = $r;
        }

        // statistik untuk PG Kompleks & Benar Salah
        $multiAnswers = $db->table('cbt_answers ca')
            ->select('ca.question_id, ca.answer, q.correct_option, q.question_type')
            ->join('cbt_questions q', 'q.id = ca.question_id', 'left')
            ->where('ca.test_id', $testId)
            ->whereIn('q.question_type', ['pg_kompleks', 'benar_salah'])
            ->get()
            ->getResultArray();

        $multiStats = [];
        foreach ($multiAnswers as $ans) {
            $qid = $ans['question_id'];
            $type = $ans['question_type'];
            if (!isset($multiStats[$qid])) {
                $multiStats[$qid] = ['total' => 0, 'benar' => 0];
            }
            $multiStats[$qid]['total']++;

            $correctStr = $ans['correct_option'] ?? '';
            $studentStr = $ans['answer'] ?? '';

            if (!empty($correctStr) && !empty($studentStr)) {
                $correctArr = explode(',', $correctStr);
                $studentArr = explode(',', $studentStr);
                $score = 0;

                if ($type === 'pg_kompleks') {
                    $cSel = count(array_intersect($studentArr, $correctArr));
                    $iSel = count(array_diff($studentArr, $correctArr));
                    $tCorr = count($correctArr);
                    $raw = $cSel - (0.5 * $iSel);
                    $score = ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
                } else { // benar_salah
                    $matches = 0;
                    $tItems = count($correctArr);
                    for ($i = 0; $i < $tItems; $i++) {
                        if (isset($studentArr[$i]) && $studentArr[$i] === $correctArr[$i])
                            $matches++;
                    }
                    $score = ($tItems > 0) ? ($matches / $tItems) : 0;
                }

                // Threshold 70% untuk dianggap "benar" di analisis
                if ($score >= 0.7) {
                    $multiStats[$qid]['benar']++;
                }
            }
        }

        foreach ($multiStats as $qid => $stat) {
            $statsMap[$qid] = $stat;
        }

        $result = [];
        foreach ($questions as $i => $q) {
            $qid = $q['id'];
            $total = (int) ($statsMap[$qid]['total'] ?? 0);
            $benar = (int) ($statsMap[$qid]['benar'] ?? 0);
            $analisis = '-';
            if ($total > 0) {
                $persen = $benar / $total;
                if ($persen <= (1 / 3))
                    $analisis = 'Susah';
                elseif ($persen <= (2 / 3))
                    $analisis = 'Sedang';
                else
                    $analisis = 'Mudah';
            }

            $result[] = [
                'no' => $i + 1,
                'question' => $q['raw_text'] ?? ($q['question_text'] ?? ($q['question'] ?? '')),
                'type' => strtolower($q['question_type'] ?? 'pg'),
                'total' => $total,
                'benar' => $benar,
                'analisis' => $analisis
            ];
        }

        return view('admin/cbt/analisis_soal/index', [
            'test' => $test,
            'data' => $result
        ]);
    }

    public function analisisDownload($testId)
    {
        $db = $this->db;

        // ambil data ujian
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Data ujian tidak ditemukan.');
        }

        // ambil soal & jawaban
        $questions = $db->table('cbt_questions')->where('bank_id', $test['bank_id'])->orderBy('id', 'ASC')->get()->getResultArray();

        // ambil statistik untuk PG biasa (berdasarkan test_id)
        $answersStats = $db->table('cbt_answers ca')
            ->select('ca.question_id, COUNT(*) AS total, SUM(CASE WHEN q.correct_option = ca.answer THEN 1 ELSE 0 END) AS benar')
            ->join('cbt_questions q', 'q.id = ca.question_id', 'left')
            ->where('ca.test_id', $testId)
            ->whereIn('q.question_type', ['pg', 'pilihan_ganda', 'multiple_choice'])
            ->groupBy('ca.question_id')
            ->get()
            ->getResultArray();

        $statsMap = [];
        foreach ($answersStats as $r)
            $statsMap[$r['question_id']] = $r;

        // statistik untuk PG Kompleks & BS
        $multiAnswers = $db->table('cbt_answers ca')
            ->select('ca.question_id, ca.answer, q.correct_option, q.question_type')
            ->join('cbt_questions q', 'q.id = ca.question_id', 'left')
            ->where('ca.test_id', $testId)
            ->whereIn('q.question_type', ['pg_kompleks', 'benar_salah'])
            ->get()
            ->getResultArray();

        $multiStats = [];
        foreach ($multiAnswers as $ans) {
            $qid = $ans['question_id'];
            $type = $ans['question_type'];
            if (!isset($multiStats[$qid]))
                $multiStats[$qid] = ['total' => 0, 'benar' => 0];
            $multiStats[$qid]['total']++;

            $cStr = $ans['correct_option'] ?? '';
            $sStr = $ans['answer'] ?? '';
            $score = $this->calculateNormalizedScore($type, $ans['answer'], $ans['correct_option']);
            if ($score >= 0.7) {
                $multiStats[$qid]['benar']++;
            }
        }
        foreach ($multiStats as $qid => $stat)
            $statsMap[$qid] = $stat;

        // buat spreadsheet (PG & Esai seperti sebelumnya)
        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Analisis PG');
        $sheet1->setCellValue('A1', 'Analisis Soal Pilihan Ganda');
        $sheet1->setCellValue('A2', $test['exam_name'] . ' - ' . $test['subject_name']);
        $sheet1->mergeCells('A1:E1');
        $sheet1->mergeCells('A2:E2');
        $sheet1->getStyle('A1:A2')->getFont()->setBold(true);

        $sheet1->fromArray(['No', 'Pertanyaan', 'Tipe', 'Partisipan', 'Benar', 'Analisis'], null, 'A4');
        $row = 5;
        $no = 1;
        foreach ($questions as $q) {
            $qType = strtolower($q['question_type'] ?? 'pg');
            if (in_array($qType, ['esai', 'essay']))
                continue;

            $qid = $q['id'];
            $total = (int) ($statsMap[$qid]['total'] ?? 0);
            $benar = (int) ($statsMap[$qid]['benar'] ?? 0);
            $analisis = '-';
            if ($total > 0) {
                $p = $benar / $total;
                if ($p <= (1 / 3))
                    $analisis = 'Susah';
                elseif ($p <= (2 / 3))
                    $analisis = 'Sedang';
                else
                    $analisis = 'Mudah';
            }
            $sheet1->fromArray([
                $no++,
                strip_tags($q['question_text'] ?? ''),
                strtoupper($qType),
                $total,
                $benar,
                $analisis
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'F') as $col)
            $sheet1->getColumnDimension($col)->setAutoSize(true);

        // --- Sheet 2: Esai ---
        $spreadsheet->createSheet();
        $sheet2 = $spreadsheet->getSheet(1);
        $sheet2->setTitle('Analisis Esai');
        $sheet2->setCellValue('A1', 'Daftar Soal Esai');
        $sheet2->setCellValue('A2', $test['exam_name'] . ' - ' . $test['subject_name']);
        $sheet2->fromArray(['No', 'Pertanyaan', 'Tipe'], null, 'A4');

        $rowEsai = 5;
        $noEsai = 1;
        foreach ($questions as $q) {
            $qType = strtolower($q['question_type'] ?? 'pg');
            if (!in_array($qType, ['esai', 'essay']))
                continue;

            $sheet2->fromArray([
                $noEsai++,
                strip_tags($q['question_text'] ?? ''),
                'ESAI'
            ], null, 'A' . $rowEsai);
            $rowEsai++;
        }
        foreach (range('A', 'C') as $col)
            $sheet2->getColumnDimension($col)->setAutoSize(true);

        $fileName = 'Analisis_Soal_' . preg_replace('/\s+/', '_', $test['subject_name']) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function downloadAnalisis($testId)
    {
        $mode = $this->request->getGet('mode');
        $classId = $this->request->getGet('class');

        // 🔹 Ambil data tes
        $test = $this->testModel
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->from('cbt_test_status ts')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Tes tidak ditemukan.');
        }

        // FIX: Handle duplicate sessions - only get latest session per student using MAX(id)
        $subquery = $this->db->table('cbt_sessions')
            ->select('student_id, MAX(id) as max_id')
            ->where('test_id', $testId)
            ->groupBy('student_id')
            ->getCompiledSelect();

        // 🔹 Ambil sesi siswa — langsung join ke classes
        $sessions = $this->sessionModel
            ->select('cs.*, st.name AS student_name, cl.name AS class_name, st.class_id')
            ->from('cbt_sessions cs')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('classes cl', 'cl.id = st.class_id', 'left')
            ->join("($subquery) latest", 'cs.id = latest.max_id', 'inner')
            ->where('cs.test_id', $testId);

        if ($mode === 'kelas' && $classId) {
            $sessions->where('st.class_id', $classId);
        }

        $sessions = $sessions
            ->orderBy('st.name', 'ASC')
            ->get()
            ->getResultArray();

        if (!$sessions) {
            return redirect()->back()->with('error', 'Tidak ada data sesi ditemukan.');
        }

        // 🔹 Ambil semua soal (PG, PGK, BS, Esai)
        $questions = $this->questionModel
            ->where('bank_id', $test['bank_id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        // 🔹 Siapkan header kolom soal
        $headers = [];
        foreach ($questions as $i => $q) {
            $keyStr = $q['correct_option'] ?: '-';
            if (strtolower($q['question_type']) === 'esai')
                $keyStr = 'Esai';

            $headers[] = [
                'no' => $i + 1,
                'key' => str_replace(',', ', ', strtoupper($keyStr))
            ];
        }

        // 🔹 Buat spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analisis Jawaban');

        // Header informasi umum
        $sheet->mergeCells('A1:Z1')->setCellValue('A1', 'ANALISIS JAWABAN UJIAN BERBASIS KOMPUTER');
        $sheet->mergeCells('A2:Z2')->setCellValue('A2', 'Data Analisis Jawaban Siswa');
        $sheet->setCellValue('A4', 'Kode Tes : ' . $test['bank_code']);
        $sheet->setCellValue('A5', 'Mapel : ' . $test['subject_name']);
        $sheet->setCellValue('A6', 'Ujian : ' . $test['exam_name']);

        // Header tabel
        $headerRow = ['NO', 'NAMA SISWA', 'KELAS', 'BENAR', 'SALAH', 'NILAI'];
        $sheet->fromArray([$headerRow], NULL, 'A7');

        // Tambahkan kolom nomor soal
        $colIndex = 7; // Kolom ke-7 = G
        foreach ($headers as $h) {
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '7', $h['no']);
            $colIndex++;
        }

        // 🔹 Isi data siswa
        $row = 8;
        foreach ($sessions as $i => $s) {
            $answers = $this->answerModel
                ->where('student_id', $s['student_id'])
                ->where('test_id', $testId)
                ->findAll();

            $ansMap = [];
            foreach ($answers as $a) {
                $ansMap[$a['question_id']] = $a['answer']; // Tetap case-sensitive untuk Esai
            }

            $benar = 0;
            $salah = 0;
            $cols = [];

            foreach ($questions as $q) {
                $rawJawaban = $ansMap[$q['id']] ?? '';
                $jawaban = $rawJawaban;
                $qType = strtolower($q['question_type'] ?? 'pg');

                $score = $this->calculateNormalizedScore($qType, $jawaban, $q['correct_option']);
                $isCorrect = ($score >= 0.7);

                if ($isCorrect) {
                    $benar++;
                } else {
                    if ($qType !== 'esai' && $jawaban)
                        $salah++;
                }

                // Tampilkan jawaban di kolom matrix
                if ($qType === 'esai') {
                    $cols[] = $rawJawaban ?: '-';
                } else {
                    $cols[] = $jawaban ?: '-';
                }
            }

            // Gunakan nilai akhir dari database (berbobot)
            $nilai = $s['total_score'] ?? 0;

            $sheet->fromArray([
                [
                    $i + 1,
                    $s['student_name'],
                    $s['class_name'] ?? '-',
                    $benar,
                    $salah,
                    $nilai,
                    ...$cols
                ]
            ], NULL, "A{$row}");

            $row++;
        }

        // ======================
        // 🎨 STYLING TABEL
        // ======================
        $this->applyAnalysisTableStyling($sheet, $row, $headers);

        // ======================
        // 💾 SIMPAN & UNDUH
        // ======================
        $filename = 'Analisis_Jawaban_' . $test['bank_code'] . '.xlsx';
        $exportPath = WRITEPATH . 'exports';

        if (!is_dir($exportPath)) {
            mkdir($exportPath, 0777, true);
        }

        $tempFile = $exportPath . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $this->response
            ->download($tempFile, null)
            ->setFileName($filename)
            ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function laporanJawaban($testId)
    {
        $db = $this->db;

        $test = $this->testModel
            ->select('cbt_test_status.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = cbt_test_status.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = cbt_test_status.exam_name_id', 'left')
            ->where('cbt_test_status.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Tes tidak ditemukan.');
        }

        // FIX: Handle duplicate sessions - only get latest session per student using MAX(id)
        $subquery = $db->table('cbt_sessions')
            ->select('student_id, MAX(id) as max_id')
            ->where('test_id', $testId)
            ->groupBy('student_id')
            ->getCompiledSelect();

        // ambil daftar siswa yang mengikuti tes: join langsung students -> classes
        $sessions = $this->sessionModel
            ->select('cbt_sessions.*, st.username AS exam_number, st.name AS student_name, st.nis, st.class_id, cl.name AS class_name')
            ->join('students st', 'st.id = cbt_sessions.student_id', 'left')
            ->join('classes cl', 'cl.id = st.class_id', 'left')
            ->join("($subquery) latest", 'cbt_sessions.id = latest.max_id', 'inner')
            ->where('cbt_sessions.test_id', $testId)
            ->orderBy('st.name', 'ASC')
            ->findAll();

        return view('admin/cbt/aktivitas/laporan_jawaban', [
            'test' => $test,
            'sessions' => $sessions
        ]);
    }

    public function simpanNilaiEsai()
    {
        $sessionId = $this->request->getPost('session_id');
        $nilaiEsai = $this->request->getPost('nilai_esai');

        $this->sessionModel->update($sessionId, ['essay_score' => $nilaiEsai]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Nilai esai berhasil disimpan'
        ]);
    }

    public function laporanJawabanPdf($testId, $studentId)
    {
        $db = db_connect();

        // 🔹 Ambil detail tes
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Data tes tidak ditemukan.');
        }

        // 🔹 Ambil data siswa
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // 🔹 Ambil semua soal dari bank
        $allQuestions = $db->table('cbt_questions')
            ->where('bank_id', $test['bank_id'])
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // 🔹 Ambil jawaban siswa
        $answers = $this->answerModel
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->get()
            ->getResultArray();

        // Map jawaban by question_id
        $answerMap = [];
        foreach ($answers as $ans) {
            $answerMap[$ans['question_id']] = $ans['answer'];
        }

        // 🔹 Hitung nilai PG dan PG Kompleks
        $pgCount = 0;
        $pgCorrect = 0;
        $esaiCount = 0;
        $esaiScore = 0;

        $questions = [];
        foreach ($allQuestions as $q) {
            $qType = strtolower($q['question_type'] ?? 'pg');
            $answer = $answerMap[$q['id']] ?? '';

            $item = [
                'type' => $qType,
                'text' => $q['question'] ?? $q['question_text'] ?? '',
                'options' => $this->parseOptions($q),
                'correct_option' => $q['correct_option'] ?? '',
                'answer' => $answer,
                'score' => null,
                'is_correct' => false
            ];

            // Hitung kebenaran untuk PG dan PG Kompleks
            if (in_array($qType, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $pgCount++;
                $item['is_correct'] = ($answer && $answer === strtoupper($q['correct_option']));
                if ($item['is_correct']) {
                    $pgCorrect++;
                }
            } elseif ($qType === 'pg_kompleks') {
                $pgCount++;

                // Hitung dengan soft penalty
                if ($answer && !empty($q['correct_option'])) {
                    // Normalisasi jawaban: uppercase & trim
                    $correctStr = strtoupper(trim($q['correct_option']));
                    $studentStr = strtoupper(trim($answer));

                    $correctArr = array_map('trim', explode(',', $correctStr));
                    $studentArr = array_map('trim', explode(',', $studentStr));

                    $correctSelected = count(array_intersect($studentArr, $correctArr));
                    $incorrectSelected = count(array_diff($studentArr, $correctArr));
                    $totalCorrectOptions = count($correctArr);

                    $rawScore = $correctSelected - (0.5 * $incorrectSelected);
                    $score = ($totalCorrectOptions > 0) ? (max(0, $rawScore) / $totalCorrectOptions) : 0;

                    // Threshold 70% untuk dihitung benar
                    $item['is_correct'] = ($score >= 0.7);
                    if ($item['is_correct']) {
                        $pgCorrect++;
                    }
                }
            } elseif (in_array($qType, ['esai', 'essay'])) {
                $esaiCount++;
                // Ambil nilai esai dari cbt_answers jika ada
                $esaiAnswer = array_filter($answers, function ($a) use ($q) {
                    return $a['question_id'] == $q['id'];
                });
                if (!empty($esaiAnswer)) {
                    $esaiAnswer = array_values($esaiAnswer)[0];
                    $item['score'] = $esaiAnswer['essay_score'] ?? 0;
                    $esaiScore += (float) $item['score'];
                }
            }

            $questions[] = $item;
        }

        // 🔹 Hitung nilai berdasarkan bobot
        $bobotPg = (float) ($test['pg_weight'] ?? 60);
        $bobotEsai = (float) ($test['essay_weight'] ?? 40);

        // Nilai PG (dari jumlah benar)
        $nilaiPg = ($pgCount > 0) ? round(($pgCorrect / $pgCount) * 100, 2) : 0;

        // Nilai Esai (rata-rata)
        $nilaiEsai = ($esaiCount > 0) ? round($esaiScore / $esaiCount, 2) : 0;

        // Total akhir (berbobot)
        $nilaiTotal = round(($nilaiPg * $bobotPg / 100) + ($nilaiEsai * $bobotEsai / 100), 2);

        // 🔹 Render HTML untuk PDF
        $html = view('admin/cbt/aktivitas/pdf_laporan_jawaban', [
            'test' => $test,
            'student' => $student,
            'questions' => $questions,
            'nilai_pg' => $nilaiPg,
            'nilai_esai' => $nilaiEsai,
            'bobot_pg' => $bobotPg,
            'bobot_esai' => $bobotEsai,
            'nilai_total' => $nilaiTotal
        ]);

        // 🔹 Pastikan tidak ada output lain yang bocor
        if (ob_get_length()) {
            ob_end_clean();
        }

        // 🔹 Inisialisasi Dompdf
        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 🔹 Ambil hasil PDF sebagai string
        $output = $dompdf->output();
        $filenameSafe = 'Laporan_Jawaban_' . preg_replace('/[^A-Za-z0-9]/', '_', $student['name'] ?? 'Siswa') . '.pdf';

        // 🔹 Return via response CI4 (aman di hosting)
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filenameSafe . '"')
            ->setBody($output);
    }

    // Helper method to parse options
    private function parseOptions($question)
    {
        $options = [];
        foreach (['A', 'B', 'C', 'D', 'E'] as $key) {
            $field = 'option_' . strtolower($key);
            if (isset($question[$field]) && !empty($question[$field])) {
                $options[$key] = $question[$field];
            }
        }
        return $options;
    }


    // ✅ Menampilkan modal isi soal esai dan jawaban siswa
    public function getSoalEsai($testId, $studentId)
    {
        $db = db_connect();

        // 🔹 Ambil data bobot esai dari cbt_test_status
        $test = $db->table('cbt_test_status')
            ->select('bank_id, bobot_esai')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return '<div class="alert alert-danger">Tes tidak ditemukan.</div>';
        }

        // 🔹 Ambil soal esai dan jawaban siswa
        $soal = $db->table('cbt_questions q')
            ->select('q.id, q.question_text, a.answer, a.score')
            ->join('cbt_answers a', 'a.question_id = q.id AND a.student_id = ' . $studentId, 'left')
            ->where('q.bank_id', $test['bank_id'])
            ->whereIn('q.question_type', ['esai', 'essay'])
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        if (!$soal) {
            return '<div class="alert alert-warning">Tidak ada soal esai pada tes ini.</div>';
        }

        // 🔹 Kirim data ke view modal
        return view('admin/cbt/aktivitas/modal_nilai_esai', [
            'test_id' => $testId,
            'student_id' => $studentId,
            'bobot_esai' => $test['bobot_esai'] ?? 0,
            'soal' => $soal
        ]);
    }


    // ✅ Simpan nilai esai
    public function simpanNilaiEsaiDetail()
    {
        $testId = $this->request->getPost('test_id');
        $studentId = $this->request->getPost('student_id');
        $nilaiSoal = $this->request->getPost('scores'); // array: [question_id => nilai_per_soal]

        $db = db_connect();

        // 🔹 Ambil bobot dari test
        $test = $db->table('cbt_test_status')
            ->select('bobot_pg, bobot_pg_kompleks, bobot_bs, bobot_esai, bank_id')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return $this->response->setJSON(['error' => 'Data ujian tidak ditemukan'])->setStatusCode(404);
        }

        // 🔹 Ambil session untuk mendapatkan question_order
        $session = $db->table('cbt_sessions')
            ->select('question_order')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->get()
            ->getRowArray();

        if (!$session) {
            return $this->response->setJSON(['error' => 'Sesi tidak ditemukan'])->setStatusCode(404);
        }

        // 🔒 FIX: Get question_order from session
        $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];

        if (empty($questionOrder)) {
            // Fallback to all questions if question_order is empty
            $questions = $db->table('cbt_questions')
                ->select('id, question_type, correct_option')
                ->where('bank_id', $test['bank_id'])
                ->get()
                ->getResultArray();
        } else {
            // 🔒 FIX: Fetch ONLY questions that were shown to student
            $questions = $db->table('cbt_questions')
                ->select('id, question_type, correct_option')
                ->whereIn('id', $questionOrder)
                ->get()
                ->getResultArray();
        }

        // 🔹 Ambil semua jawaban siswa
        $answers = $db->table('cbt_answers')
            ->select('question_id, answer')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->get()
            ->getResultArray();

        $answerMap = [];
        foreach ($answers as $a) {
            $answerMap[$a['question_id']] = $a['answer'];
        }

        // 🔹 Simpan nilai tiap soal esai di cbt_answers.score
        $totalNilaiEsai = 0;
        $jumlahSoalEsai = 0;

        foreach ($nilaiSoal as $qid => $nilai) {
            $totalNilaiEsai += floatval($nilai);
            $jumlahSoalEsai++;
            $db->table('cbt_answers')
                ->where('question_id', $qid)
                ->where('student_id', $studentId)
                ->update(['score' => $nilai]);
        }

        // 🔹 Recalculate ALL scores (PG, PGK, BS, Esai)
        $totalPg = $totalPgk = $totalBs = $totalEsai = 0;
        $earnedPg = $earnedPgk = $earnedBs = 0;

        foreach ($questions as $q) {
            $type = strtolower($q['question_type'] ?? 'pg');
            $qid = $q['id'];
            $correctStr = $q['correct_option'] ?? '';
            $studentAns = isset($answerMap[$qid]) ? ($answerMap[$qid] ?? null) : null;

            if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $totalPg++;
                if ($correctStr !== '' && $studentAns !== null && (string) $studentAns === (string) $correctStr) {
                    $earnedPg += 1;
                }
            } elseif ($type === 'pg_kompleks') {
                $totalPgk++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $correctArr = explode(',', $correctStr);
                    $studentArr = explode(',', $studentAns);
                    $cSel = count(array_intersect($studentArr, $correctArr));
                    $iSel = count(array_diff($studentArr, $correctArr));
                    $tCorr = count($correctArr);
                    $raw = $cSel - (0.5 * $iSel);
                    $earnedPgk += ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
                }
            } elseif ($type === 'benar_salah') {
                $totalBs++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $correctArr = explode(',', $correctStr);
                    $studentArr = explode(',', $studentAns);
                    $matches = 0;
                    $tItems = count($correctArr);
                    for ($i = 0; $i < $tItems; $i++) {
                        $stdAns = isset($studentArr[$i]) ? trim($studentArr[$i]) : '';
                        $keyAns = isset($correctArr[$i]) ? trim($correctArr[$i]) : '';

                        if ($stdAns !== '' && $stdAns === $keyAns) {
                            $matches++;
                        }
                    }
                    $earnedBs += ($tItems > 0) ? ($matches / $tItems) : 0;
                }
            } elseif (in_array($type, ['esai', 'essay'])) {
                $totalEsai++;
            }
        }

        // 🔹 Calculate scores per type
        $bobotPg = (float) ($test['bobot_pg'] ?? 0);
        $bobotPgk = (float) ($test['bobot_pg_kompleks'] ?? 0);
        $bobotBs = (float) ($test['bobot_bs'] ?? 0);
        $bobotEsai = (float) ($test['bobot_esai'] ?? 0);

        $nilaiPg = ($totalPg > 0) ? ($earnedPg / $totalPg) * 100 : 0;
        $nilaiPgk = ($totalPgk > 0) ? ($earnedPgk / $totalPgk) * 100 : 0;
        $nilaiBs = ($totalBs > 0) ? ($earnedBs / $totalBs) * 100 : 0;
        $essayScore = $jumlahSoalEsai ? round($totalNilaiEsai / $jumlahSoalEsai, 2) : 0;

        // 🔹 Calculate final score with weights
        $finalScore = round(
            ($nilaiPg * ($bobotPg / 100)) +
            ($nilaiPgk * ($bobotPgk / 100)) +
            ($nilaiBs * ($bobotBs / 100)) +
            ($essayScore * ($bobotEsai / 100)),
            2
        );

        // 🔹 Simpan ke cbt_sessions
        $db->table('cbt_sessions')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->update([
                'score' => $finalScore,
                'essay_score' => $essayScore,
                'total_score' => $finalScore
            ]);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Nilai esai berhasil disimpan',
            'nilai' => [
                'pg' => round($nilaiPg, 2),
                'pgk' => round($nilaiPgk, 2),
                'bs' => round($nilaiBs, 2),
                'esai' => $essayScore,
                'bobot_pg' => $bobotPg,
                'bobot_pgk' => $bobotPgk,
                'bobot_bs' => $bobotBs,
                'bobot_esai' => $bobotEsai,
                'total' => $finalScore,
            ],
        ]);
    }

    public function laporanJawabPdf($testId, $studentId)
    {
        $db = db_connect();

        // 🔹 Ambil informasi tes
        // Pastikan ambil semua kolom dari cbt_test_status ts.*
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Data tes tidak ditemukan.');
        }

        // 🔹 Ambil data siswa + kelas
        $student = $db->table('students st')
            ->select('st.id, st.name, st.nis, st.class_id, cl.name AS class_name')
            ->join('classes cl', 'cl.id = st.class_id', 'left')
            ->where('st.id', $studentId)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // 🔹 Ambil session (jika ada) untuk nilai esai yang sudah dinilai
        $session = $db->table('cbt_sessions cs')
            ->where('cs.test_id', $testId)
            ->where('cs.student_id', $studentId)
            ->get()
            ->getRowArray();

        // 🔹 Ambil semua jawaban siswa
        $answers = $db->table('cbt_answers a')
            ->select('a.*, q.question_text, q.question_type, q.correct_option, q.raw_text,
                  q.option_a, q.option_b, q.option_c, q.option_d, q.option_e')
            ->join('cbt_questions q', 'q.id = a.question_id', 'left')
            ->where('a.test_id', $testId)
            ->where('a.student_id', $studentId)
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        // 🔹 Inisialisasi Counter
        $totalPg = 0;
        $scorePg = 0;
        $totalPgk = 0;
        $scorePgk = 0;
        $totalBs = 0;
        $scoreBs = 0;

        // 🔹 Siapkan data tampilan
        $questionData = [];
        foreach ($answers as $a) {
            $type = strtolower($a['question_type'] ?? 'pg');
            $answer = $a['answer'] ?? ($a['selected_option'] ?? '');
            $isCorrect = false;

            // --- Logika Penilaian ---
            if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $totalPg++;
                if ($answer && strtoupper($answer) === strtoupper($a['correct_option'])) {
                    $isCorrect = true;
                    $scorePg++;
                }
            } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                $totalPgk++;
                if ($answer && !empty($a['correct_option'])) {
                    $correctArr = explode(',', strtoupper($a['correct_option']));
                    $studentArr = explode(',', strtoupper($answer));
                    $cSel = count(array_intersect($studentArr, $correctArr));
                    $iSel = count(array_diff($studentArr, $correctArr));
                    $tCorr = count($correctArr);
                    $raw = $cSel - (0.5 * $iSel);
                    $score = ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
                    if ($score >= 0.7) {
                        $isCorrect = true;
                        $scorePgk++;
                    }
                }
            } elseif ($type === 'benar_salah' || $type === 'bs') {
                $totalBs++;
                if ($answer && !empty($a['correct_option'])) {
                    $cArr = explode(',', strtoupper($a['correct_option']));
                    $sArr = explode(',', strtoupper($answer));
                    $m = 0;
                    $ti = count($cArr);
                    for ($k = 0; $k < $ti; $k++) {
                        if (isset($sArr[$k]) && $sArr[$k] === $cArr[$k])
                            $m++;
                    }
                    if ($ti > 0 && ($m / $ti) >= 0.7) {
                        $isCorrect = true;
                        $scoreBs++;
                    }
                }
            }

            $questionData[] = [
                'id' => $a['question_id'],
                'type' => $type,
                'text' => $a['question_text'], // Use question_text instead of raw_text to avoid duplicate options
                'options' => [
                    'A' => $a['option_a'] ?? null,
                    'B' => $a['option_b'] ?? null,
                    'C' => $a['option_c'] ?? null,
                    'D' => $a['option_d'] ?? null,
                    'E' => $a['option_e'] ?? null,
                ],
                'answer' => $answer,
                'correct_option' => $a['correct_option'] ?? '',
                'score' => $a['score'] ?? null,
                'is_correct' => $isCorrect
            ];
        }

        // 🔹 Urutkan: PG -> PGK -> BS -> Esai
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

        // 🔹 Hitung Nilai (Skala 100)
        $nilaiPgRaw = ($totalPg > 0) ? round(($scorePg / $totalPg) * 100, 2) : 0;
        $nilaiPgkRaw = ($totalPgk > 0) ? round(($scorePgk / $totalPgk) * 100, 2) : 0;
        $nilaiBsRaw = ($totalBs > 0) ? round(($scoreBs / $totalBs) * 100, 2) : 0;
        $nilaiEsaiRaw = $session['essay_score'] ?? 0;

        // 🔹 Ambil Bobot
        $bobotPg = (float) ($test['bobot_pg'] ?? 0);
        $bobotPgk = (float) ($test['bobot_pg_kompleks'] ?? 0);
        $bobotBs = (float) ($test['bobot_bs'] ?? 0);
        $bobotEsai = (float) ($test['bobot_esai'] ?? 0);

        // 🔹 Hitung Kontribusi Nilai
        $nilaiPgAkhir = ($nilaiPgRaw * $bobotPg) / 100;
        $nilaiPgkAkhir = ($nilaiPgkRaw * $bobotPgk) / 100;
        $nilaiBsAkhir = ($nilaiBsRaw * $bobotBs) / 100;
        $nilaiEsaiAkhir = ($nilaiEsaiRaw * $bobotEsai) / 100;

        $nilaiTotal = round($nilaiPgAkhir + $nilaiPgkAkhir + $nilaiBsAkhir + $nilaiEsaiAkhir, 2);

        // 🔹 Render view ke HTML
        $html = view('admin/cbt/aktivitas/pdf_laporan_jawaban', [
            'test' => $test,
            'student' => $student,
            'questions' => $questionData,

            'nilai_pg_raw' => $nilaiPgRaw,
            'bobot_pg' => $bobotPg,
            'nilai_pg_akhir' => $nilaiPgAkhir,

            'nilai_pgk_raw' => $nilaiPgkRaw,
            'bobot_pgk' => $bobotPgk,
            'nilai_pgk_akhir' => $nilaiPgkAkhir,

            'nilai_bs_raw' => $nilaiBsRaw,
            'bobot_bs' => $bobotBs,
            'nilai_bs_akhir' => $nilaiBsAkhir,

            'nilai_esai_raw' => $nilaiEsaiRaw,
            'bobot_esai' => $bobotEsai,
            'nilai_esai_akhir' => $nilaiEsaiAkhir,

            'nilai_total' => $nilaiTotal,
        ]);

        // 🔹 Bersihkan buffer (penting di hosting!)
        if (ob_get_length()) {
            ob_end_clean();
        }

        // 🔹 Generate PDF
        $dompdf = new Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 🔹 Ambil hasil PDF sebagai string
        $output = $dompdf->output();
        $filenameSafe = 'Laporan_Jawaban_' . preg_replace('/[^A-Za-z0-9]/', '_', $student['name'] ?? 'Siswa') . '.pdf';

        // 🔹 Return via response (aman untuk CI4 + hosting)
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filenameSafe . '"')
            ->setBody($output);
    }



    /**
     * ✅ Helper to calculate normalized score (0 to 1) for various question types
     */
    private function calculateNormalizedScore($type, $studentAns, $correctAns)
    {
        $type = strtolower(str_replace(' ', '_', $type ?? 'pg'));
        $studentAns = trim((string) ($studentAns ?? ''));
        $correctAns = trim((string) ($correctAns ?? ''));

        if (empty($studentAns) || empty($correctAns)) {
            return 0;
        }

        // Standard Single Choice (PG)
        if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
            return (strtoupper($studentAns) === strtoupper($correctAns)) ? 1 : 0;
        }

        // Complex Single Choice (PGK)
        if ($type === 'pg_kompleks' || $type === 'pgk') {
            $correctArr = explode(',', $correctAns);
            $studentArr = explode(',', $studentAns);
            $cSel = count(array_intersect($studentArr, $correctArr));
            $iSel = count(array_diff($studentArr, $correctArr));
            $tCorr = count($correctArr);
            $raw = $cSel - (0.5 * $iSel);
            return ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
        }

        // True/False (BS)
        if ($type === 'benar_salah' || $type === 'bs') {
            $cArr = explode(',', $correctAns);
            $sArr = explode(',', $studentAns);
            $matches = 0;
            $tItems = count($cArr);
            for ($i = 0; $i < $tItems; $i++) {
                $std = isset($sArr[$i]) ? strtoupper(trim($sArr[$i])) : '';
                $key = isset($cArr[$i]) ? strtoupper(trim($cArr[$i])) : '';
                if ($std !== '' && $std === $key) {
                    $matches++;
                }
            }
            return ($tItems > 0) ? ($matches / $tItems) : 0;
        }

        return 0;
    }

    /**
     * 🎨 Helper method to apply styling and conditional formatting for Analisis Jawaban
     * Evaluates 'too many types' warning in IDE and improves code readability.
     */
    private function applyAnalysisTableStyling(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $currentRow, array $headers)
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $currentRow - 1;

        // Header utama
        $sheet->getStyle("A7:{$lastCol}7")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4F81BD']],
        ]);

        // Border seluruh tabel
        $sheet->getStyle("A7:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'AAAAAA']
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ========================================
        // ✅ CONDITIONAL FORMATTING HIJAU / MERAH
        // ========================================
        $startColIndex = 7; // Kolom G
        foreach ($headers as $index => $h) {
            $colIndex = $startColIndex + $index;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $range = "{$colLetter}8:{$colLetter}{$lastRow}";
            $key = $h['key'];

            // Hijau untuk jawaban benar
            $conditionalGreen = new Conditional();
            $conditionalGreen->setConditionType(Conditional::CONDITION_CELLIS);
            $conditionalGreen->setOperatorType(Conditional::OPERATOR_EQUAL);
            $conditionalGreen->addCondition("\"{$key}\"");
            $conditionalGreen->getStyle()->getFill()->setFillType(
                Fill::FILL_SOLID
            )->getStartColor()->setARGB('92D050');

            // Merah untuk jawaban salah
            $conditionalRed = new Conditional();
            $conditionalRed->setConditionType(Conditional::CONDITION_EXPRESSION);
            $conditionalRed->addCondition("AND({$colLetter}8<>\"{$key}\",{$colLetter}8<>\"-\")");
            $conditionalRed->getStyle()->getFill()->setFillType(
                Fill::FILL_SOLID
            )->getStartColor()->setARGB('FF7C80');

            $sheet->getStyle($range)->setConditionalStyles([$conditionalGreen, $conditionalRed]);
        }
    }
}

