<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtAnswerModel;
use App\Models\CbtQuestionModel;
use App\Models\StudentModel;
use App\Services\CbtScoringService;
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
     * Auto-close sesi ujian yang waktunya sudah habis tapi masih berstatus 'active'.
     *
     * Dipanggil setiap kali admin membuka halaman detail aktivitas, sehingga
     * tampilan selalu sinkron dengan kondisi sebenarnya tanpa menunggu siswa
     * membuka halaman mereka sendiri.
     *
     * Kondisi expired: started_at + (duration + extra_time) * 60 + 30 detik grace <= now
     * atau end_time ujian sudah terlewat
     *
     * @param int $testId
     * @param int $duration Durasi ujian dalam menit (dari cbt_test_status)
     * @param string $testEndTime Waktu berakhirnya jadwal ujian
     */
    protected function autoCloseExpiredSessions(int $testId, int $duration, string $testEndTime): void
    {
        $now = time();
        $testEndTs = strtotime($testEndTime);
        $gracePeriod = 30; // detik

        // Ambil semua sesi yang masih active untuk test ini
        $activeSessions = $this->sessionModel
            ->where('test_id', $testId)
            ->where('status', 'active')
            ->findAll();

        if (empty($activeSessions)) return;

        $scoringService = new CbtScoringService();

        foreach ($activeSessions as $session) {
            $startTs = (int) $session['started_at'];
            if ($startTs < 100000) continue; // data tidak valid

            $extraMin = (int) ($session['extra_time'] ?? 0);
            $totalDurationSec = ($duration + $extraMin) * 60;
            $sessionExpireTs = $startTs + $totalDurationSec + $gracePeriod;

            // Expired jika durasi sesi habis ATAU waktu akhir jadwal ujian terlewat
            $isExpired = ($now >= $sessionExpireTs) || ($testEndTs > 0 && $now > $testEndTs + $gracePeriod);

            if (!$isExpired) continue;

            $studentId = $session['student_id'];
            log_message('info', "[AutoClose] Closing expired session ID={$session['id']}, Student={$studentId}, Test={$testId}");

            try {
                // Hitung skor menggunakan scoring service yang sama dengan submit normal
                $score = $scoringService->calculateScoreWithBobot($studentId, $testId);

                $this->sessionModel->update($session['id'], [
                    'status'     => 'finished',
                    'finished_at' => date('Y-m-d H:i:s', $now),
                    'score'      => $score,
                    'total_score' => $score,
                ]);

                log_message('info', "[AutoClose] Session ID={$session['id']} closed with score={$score}");
            } catch (\Throwable $e) {
                log_message('error', "[AutoClose] Failed to close session ID={$session['id']}: " . $e->getMessage());
            }
        }
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
            ->select('ts.id AS test_id, qb.code AS test_code, s.name AS subject_name, en.name AS exam_name, ts.start_time, ts.end_time, ts.class_codes')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left');

        // KRITERIA OTOMATIS: 
        // 1. Ujian sudah masuk waktu mulai (start_time <= now)
        // 2. Minimal ada 1 siswa yang sudah/sedang mengerjakan (exists in cbt_sessions)
        $query->where('ts.start_time <=', $now);
        $query->where("EXISTS (SELECT 1 FROM cbt_sessions cs WHERE cs.test_id = ts.id)", null, false);

        // Filter untuk guru: tampilkan ujian yang:
        // 1. Dibuat oleh guru tersebut, ATAU
        // 2. Menggunakan bank soal milik guru tersebut, ATAU
        // 3. Melibatkan salah satu kelas yang diampu guru tersebut
        $teacherClassNamesForFilter = [];
        if ($context['is_teacher'] && $context['teacher_id'] && $context['user_id']) {
            $teacherClasses = get_teacher_classes($context['teacher_id']);
            $userId    = (int) $context['user_id'];
            $teacherId = (int) $context['teacher_id'];

            // Kondisi 1 & 2 di SQL
            $query->groupStart()
                ->where('ts.created_by', $userId)
                ->orWhere('qb.teacher_id', $teacherId)
                ->groupEnd();

            // Siapkan nama kelas untuk filter kondisi 3 di PHP
            $teacherClassNamesForFilter = array_map(
                fn($c) => strtolower(trim($c['name'])),
                $teacherClasses
            );
        }

        $sessions = $query
            ->orderBy('ts.start_time', 'DESC')
            ->get()->getResultArray();

        // Kondisi 3 (kelas yang diampu): tambahkan ujian yang belum tertangkap kondisi 1&2
        // Strategi: jika guru, ambil SEMUA ujian lalu gabung (union PHP) dengan yang
        // lolos kondisi kelas. Lebih simpel: jalankan query kedua khusus kelas.
        if (!empty($teacherClassNamesForFilter)) {
            // Ambil semua ujian (tanpa filter guru) untuk dicek kelas-nya di PHP
            $allQuery = $this->db->table('cbt_test_status ts')
                ->select('ts.id AS test_id, qb.code AS test_code, s.name AS subject_name, en.name AS exam_name, ts.start_time, ts.end_time, ts.class_codes')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
                ->where('ts.start_time <=', $now)
                ->where("EXISTS (SELECT 1 FROM cbt_sessions cs WHERE cs.test_id = ts.id)", null, false)
                ->get()->getResultArray();

            // Index hasil kondisi 1&2 agar tidak duplikat
            $existingIds = array_column($sessions, 'test_id');

            foreach ($allQuery as $row) {
                if (in_array($row['test_id'], $existingIds)) continue;
                $codes = json_decode($row['class_codes'] ?? '[]', true);
                foreach ((array)$codes as $code) {
                    if (in_array(strtolower(trim((string)$code)), $teacherClassNamesForFilter)) {
                        $sessions[] = $row;
                        $existingIds[] = $row['test_id'];
                        break;
                    }
                }
            }

            // Urutkan ulang berdasarkan start_time DESC
            usort($sessions, fn($a, $b) => strcmp($b['start_time'] ?? '', $a['start_time'] ?? ''));
        }

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

        // Auto-close sesi yang waktunya sudah habis sebelum menampilkan data
        // Ini memastikan tampilan admin selalu sinkron dengan kondisi sebenarnya
        $this->autoCloseExpiredSessions(
            (int) $testId,
            (int) ($test['duration'] ?? 0),
            (string) ($test['end_time'] ?? '')
        );

        // 🔹 Ambil tahun ajaran aktif
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        // 🔹 Resolve room column safely (kolom room ada di tabel students, bukan student_records)
        $roomField = "'' AS room";
        if ($db->fieldExists('room', 'students')) {
            $roomField = 'st.room';
        } elseif ($db->fieldExists('room', 'student_records')) {
            $roomField = 'sr.room';
        }

        // 🔹 FIX V3: Get all sessions, log them, then filter
        $allSessions = $db->table('cbt_sessions cs')
            ->select('cs.id, cs.test_id, cs.student_id, cs.question_order, cs.option_orders,
                      cs.status, cs.score, cs.essay_score, cs.total_score, cs.started_at, 
                      cs.finished_at, cs.last_activity, cs.extra_time, cs.updated_at, cs.created_at,
                      st.name AS student_name, st.nis, u.username, c.name AS class_name, ' . $roomField)
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('users u', 'u.id = st.user_id', 'left')
            ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . (int)$activeYearId, 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
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

        // -------------------------------------------------------
        // Filter untuk guru: hanya tampilkan sesi dari kelas yang diampu
        // Admin melihat semua kelas (lintas kelas)
        // -------------------------------------------------------
        $isTeacher = $context['is_teacher'] && $context['teacher_id'];
        if ($isTeacher) {
            $teacherClasses = get_teacher_classes($context['teacher_id']);
            // Normalisasi: lowercase trim nama kelas yang diampu guru
            $teacherClassNames = array_map(
                fn($c) => strtolower(trim($c['name'])),
                $teacherClasses
            );
            $sessions = array_values(array_filter($sessions, function ($s) use ($teacherClassNames) {
                return in_array(strtolower(trim($s['class_name'] ?? '')), $teacherClassNames);
            }));
            log_message('info', "[DetailAktivitas] After teacher class filter: " . count($sessions) . " sessions");
        }

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
            $order = json_decode((string)($s['question_order'] ?? '[]'), true) ?? [];
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

                $durationSec = ((int) ($test['duration'] ?? 0)) * 60;
                $extraSec = ((int) ($s['extra_time'] ?? 0)) * 60;
                $totalDurationSec = $durationSec + $extraSec;

                $testEndTs = !empty($test['end_time']) ? strtotime((string) $test['end_time']) : PHP_INT_MAX;
                $sessionEndTs = $startTs + $totalDurationSec;
                $effectiveEndTs = min($sessionEndTs, $testEndTs);

                if ($s['status'] === 'finished') {
                    // Jika siswa sudah menyelesaikan ujian, waktu berhenti
                    $s['remaining_seconds'] = 0;
                    $s['remaining_minutes'] = 0;
                } else {
                    $remainingSec = max(0, $effectiveEndTs - $now);
                    $s['remaining_seconds'] = $remainingSec;
                    $s['remaining_minutes'] = max(0, floor($remainingSec / 60));
                }
            } else {
                $s['started_at'] = 0;
                $s['remaining_seconds'] = 0;
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

        // 🔹 Extract available classes and rooms for filtering
        $availableClasses = [];
        $availableRooms = [];
        foreach ($sessions as $s) {
            if (!empty($s['class_name']) && !in_array($s['class_name'], $availableClasses)) {
                $availableClasses[] = $s['class_name'];
            }
            if (!empty($s['room']) && !in_array($s['room'], $availableRooms)) {
                $availableRooms[] = $s['room'];
            }
        }
        sort($availableClasses);
        sort($availableRooms);

        return view('admin/cbt/aktivitas/detail', [
            'test'             => $test,
            'sessions'         => $sessions,
            'noBankMap'        => $noBankMap,
            'count_belum'      => $count_belum,
            'is_teacher'       => $isTeacher,
            'availableClasses' => $availableClasses,
            'availableRooms'   => $availableRooms,
        ]);
    }

    /**
     * ⚡ AJAX Polling Ringan untuk Auto-Refresh Monitor Aktivitas Ujian
     * Mengembalikan JSON ringkas data status peserta tanpa me-render ulang seluruh halaman,
     * tanpa query bank soal, dan tanpa transfer data HTML berlebih.
     * Menghemat CPU server hingga 95%+ dibandingkan location.reload().
     */
    public function pollStatus($testId)
    {
        helper('cbt');
        $context = get_cbt_user_context();

        if (!can_access_cbt_test_status((int) $testId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $db = $this->db;
        $test = $db->table('cbt_test_status ts')
            ->select('ts.id, ts.duration, ts.end_time, ts.is_active')
            ->where('ts.id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Test not found'])->setStatusCode(404);
        }

        // Auto-close sesi expired jika ada
        $this->autoCloseExpiredSessions(
            (int) $testId,
            (int) ($test['duration'] ?? 0),
            (string) ($test['end_time'] ?? '')
        );

        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        // Ambil sesi ringkas saja
        $allSessions = $db->table('cbt_sessions cs')
            ->select('cs.id, cs.student_id, cs.status, cs.score, cs.total_score, cs.started_at, cs.finished_at, cs.extra_time, c.name AS class_name')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . (int)$activeYearId, 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->where('cs.test_id', $testId)
            ->orderBy('cs.id', 'DESC')
            ->get()->getResultArray();

        // Ambil sesi terbaru per siswa
        $seenStudents = [];
        $sessions = [];
        foreach ($allSessions as $session) {
            $studentId = $session['student_id'];
            if (!isset($seenStudents[$studentId])) {
                $seenStudents[$studentId] = true;
                $sessions[] = $session;
            }
        }

        // Filter kelas jika guru
        $isTeacher = $context['is_teacher'] && $context['teacher_id'];
        if ($isTeacher) {
            $teacherClasses = get_teacher_classes($context['teacher_id']);
            $teacherClassNames = array_map(
                fn($c) => strtolower(trim($c['name'])),
                $teacherClasses
            );
            $sessions = array_values(array_filter($sessions, function ($s) use ($teacherClassNames) {
                return in_array(strtolower(trim($s['class_name'] ?? '')), $teacherClassNames);
            }));
        }

        // Ambil total pelanggaran per siswa
        $violationCounts = $db->table('cbt_cheat_logs')
            ->select('student_id, COUNT(*) as violation_count')
            ->where('test_id', $testId)
            ->groupBy('student_id')
            ->get()->getResultArray();

        $violationMap = [];
        foreach ($violationCounts as $v) {
            $violationMap[$v['student_id']] = (int) $v['violation_count'];
        }

        $now = time();
        $durationSec = ((int) ($test['duration'] ?? 0)) * 60;
        $testEndTs = !empty($test['end_time']) ? strtotime((string) $test['end_time']) : PHP_INT_MAX;

        $studentsData = [];
        $kpi = [
            'total'          => count($sessions),
            'active'         => 0,
            'finished'       => 0,
            'timeout'        => 0,
            'violation_count'=> 0,
            'total_violations'=> 0
        ];

        foreach ($sessions as $s) {
            $status = $s['status'] ?? 'timeout';
            $violations = $violationMap[$s['student_id']] ?? 0;
            $score = $s['total_score'] ?? $s['score'] ?? 0;

            // Hitung sisa waktu
            $remainingSec = 0;
            if (!empty($s['started_at']) && $status !== 'finished') {
                $startTs = is_numeric($s['started_at']) ? (int) $s['started_at'] : strtotime((string) $s['started_at']);
                if ($startTs > 100000) {
                    $extraSec = ((int) ($s['extra_time'] ?? 0)) * 60;
                    $effectiveEndTs = min($startTs + $durationSec + $extraSec, $testEndTs);
                    $remainingSec = max(0, $effectiveEndTs - $now);
                }
            }

            if ($status === 'active') {
                $kpi['active']++;
            } elseif ($status === 'finished') {
                $kpi['finished']++;
            } else {
                $kpi['timeout']++;
            }

            if ($violations > 0) {
                $kpi['violation_count']++;
                $kpi['total_violations'] += $violations;
            }

            $studentsData[$s['id']] = [
                'session_id'        => (int) $s['id'],
                'student_id'        => (int) $s['student_id'],
                'status'            => $status,
                'score'             => $score,
                'remaining_seconds' => $remainingSec,
                'violations'        => $violations
            ];
        }

        return $this->response->setJSON([
            'status'      => 'success',
            'server_time' => date('H:i:s'),
            'kpi'         => $kpi,
            'students'    => $studentsData
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
     * Cek apakah jawaban siswa benar sesuai tipe soal.
     * - PG        : exact match, case-insensitive
     * - PGK       : order-independent (sort kedua array lalu compare)
     * - BS        : compare per-item (tiap pernyataan harus cocok)
     * - Esai      : selalu null (dinilai manual)
     *
     * @param string $type      Tipe soal (pg, pgk, pg_kompleks, bs, benar_salah, esai, essay)
     * @param string $student   Jawaban siswa  (sudah di-strtoupper trim)
     * @param string $correct   Kunci jawaban  (sudah di-strtoupper trim)
     * @return bool|null  true=benar, false=salah, null=tidak bisa dinilai otomatis (esai)
     */
    protected function isAnswerCorrect(string $type, string $student, string $correct): ?bool
    {
        if (in_array($type, ['esai', 'essay'])) return null;
        if ($correct === '' || $student === '') return false;

        if (in_array($type, ['pgk', 'pg_kompleks'])) {
            // Order-independent: A,B,C == C,B,A == B,A,C
            $sArr = array_filter(array_map('trim', explode(',', $student)));
            $cArr = array_filter(array_map('trim', explode(',', $correct)));
            sort($sArr);
            sort($cArr);
            return $sArr === $cArr;
        }

        if (in_array($type, ['bs', 'benar_salah'])) {
            // Per-item comparison — B,S,B == B,S,B tapi trailing koma diabaikan
            $sArr = array_map('trim', explode(',', $student));
            $cArr = array_map('trim', explode(',', $correct));
            // Samakan panjang dengan kunci
            $len = count($cArr);
            for ($i = 0; $i < $len; $i++) {
                $s = $sArr[$i] ?? '';
                $c = $cArr[$i] ?? '';
                if ($c === '') continue; // item kunci kosong, skip
                if ($s !== $c) return false;
            }
            return true;
        }

        // PG: exact match
        return $student === $correct;
    }

    /**
     * 📄 Modal detail jawaban siswa — real-time, tampilkan rincian lengkap soal, opsi, dan status jawaban sesuai tampilan siswa
     */
    public function detail_jawaban($sessionId)
    {
        // Paksa browser tidak cache response ini agar selalu menampilkan data terbaru
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        $db = db_connect();

        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        $roomField = "'' AS room";
        if ($db->fieldExists('room', 'students')) {
            $roomField = 'st.room';
        } elseif ($db->fieldExists('room', 'student_records')) {
            $roomField = 'sr.room';
        }

        // 1) Ambil session & test info (termasuk bank_id, bobot, option_orders)
        $session = $db->table('cbt_sessions s')
            ->select('s.id AS session_id, s.student_id, s.test_id, s.question_order, s.option_orders,
                      s.status, s.score, s.total_score, s.essay_score, s.started_at, s.finished_at, s.updated_at,
                      ts.bank_id, ts.duration,
                      ts.bobot_pg, ts.bobot_pg_kompleks, ts.bobot_bs, ts.bobot_esai,
                      ts.show_pg_count, ts.show_pg_kompleks_count, ts.show_bs_count, ts.show_esai_count,
                      st.name AS student_name, st.nis,
                      u.username,
                      c.name AS class_name, ' . $roomField)
            ->join('cbt_test_status ts',    'ts.id = s.test_id',                       'left')
            ->join('students st',           'st.id = s.student_id',                    'left')
            ->join('users u',               'u.id = st.user_id',                       'left')
            ->join('student_records sr',    'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . (int)$activeYearId, 'left')
            ->join('classes c',             'c.id = sr.class_id',                      'left')
            ->where('s.id', $sessionId)
            ->get()
            ->getRowArray();

        if (!$session) {
            return '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Sesi ujian tidak ditemukan.</div>';
        }

        $bankId    = $session['bank_id'];
        $studentId = $session['student_id'];
        $testId    = $session['test_id'];

        // 2) Ambil semua soal dari bank lengkap dengan teks, raw_text & opsi, real-time
        $bankQuestions = $db->table('cbt_questions')
            ->select('id, question_text, raw_text, question_type, correct_option, option_a, option_b, option_c, option_d, option_e, score')
            ->where('bank_id', $bankId)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        // Helper rewrite URL gambar
        $baseUrl = base_url();
        $rewriteImageUrls = function(string $html) use ($baseUrl): string {
            if (empty($html) || strpos($html, '<img') === false) return $html;
            $html = preg_replace('#src="https?://localhost(?::\d+)?/(uploads/)#i', 'src="' . $baseUrl . '$1', $html);
            $html = preg_replace('#src="https?://127\.0\.0\.1(?::\d+)?/(uploads/)#i', 'src="' . $baseUrl . '$1', $html);
            $html = preg_replace('#src="https?://[^"]+/public/(uploads/)#i', 'src="' . $baseUrl . '$1', $html);
            $html = preg_replace('#src="(?!https?://)(uploads/)#i', 'src="' . $baseUrl . '$1', $html);
            return $html;
        };

        // Helper membersihkan teks soal dari duplikasi baris opsi/kunci jika ada
        $cleanQuestionText = function(string $html): string {
            if (empty($html)) return '';
            $out = $html;
            $out = preg_replace('/<p[^>]*>\s*[\(]?[A-Ea-e]\s*[:.)\-]\s*[^<]*<\/p>/i', '', $out);
            $out = preg_replace('/^[\(]?[A-Ea-e]\s*[:.)\-]\s*[^\n]*$/im', '', $out);
            $out = preg_replace('/<p[^>]*>\s*Kunci\s*[\:\=\-]\s*[^<]*<\/p>/i', '', $out);
            return trim($out);
        };

        // Service parser soal
        $questionService = new \App\Services\CbtQuestionService();

        // Map question_id → detail soal & nomor urut di bank
        $questionMap = [];
        $noBankMap   = [];
        $no = 1;
        foreach ($bankQuestions as $q) {
            $parsed = null;
            if (!empty($q['raw_text']) && (empty($q['question_text']) || strlen(strip_tags($q['question_text'])) < 10)) {
                try {
                    $parsed = $questionService->parseRawQuestion($q['raw_text']);
                } catch (\Throwable $e) {
                    $parsed = null;
                }
            }

            // Normalisasi tipe soal
            $rawTypeFromDb = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
            if ($parsed && !empty($parsed['type'])) {
                $rawTypeFromDb = $parsed['type'];
            }
            $type = 'pg';
            if ($rawTypeFromDb === 'pgk' || strpos($rawTypeFromDb, 'kompleks') !== false) {
                $type = 'pg_kompleks';
            } elseif ($rawTypeFromDb === 'bs' || strpos($rawTypeFromDb, 'benar') !== false || strpos($rawTypeFromDb, 'salah') !== false) {
                $type = 'benar_salah';
            } elseif (in_array($rawTypeFromDb, ['esai', 'essay', 'uraian'])) {
                $type = 'esai';
            }

            // Ekstrak teks soal
            if ($parsed && !empty($parsed['question'])) {
                $qText = $parsed['question'];
            } elseif (!empty($q['question_text'])) {
                $qText = $cleanQuestionText($q['question_text']);
            } else {
                $qText = $cleanQuestionText($q['raw_text'] ?? '');
            }
            $qText = $rewriteImageUrls(trim($qText));

            // Ekstrak opsi
            $opts = [];
            foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
                $col = 'option_' . strtolower($opt);
                if (!empty($q[$col])) {
                    $opts[$opt] = $rewriteImageUrls(trim($q[$col]));
                }
            }
            if (empty($opts) && $parsed && !empty($parsed['options'])) {
                foreach ($parsed['options'] as $k => $v) {
                    $opts[$k] = $rewriteImageUrls(trim($v));
                }
            }

            $q['processed_type'] = $type;
            $q['processed_text'] = $qText;
            $q['processed_opts'] = $opts;

            $questionMap[$q['id']] = $q;
            $noBankMap[$q['id']]   = $no++;
        }

        // 3) Ambil question_order & option_orders dari session
        $questionOrder = json_decode($session['question_order'] ?? '[]', true);
        if (!is_array($questionOrder) || empty($questionOrder)) {
            $questionOrder = array_keys($questionMap);
        }
        $optionOrders = json_decode($session['option_orders'] ?? '{}', true);
        if (!is_array($optionOrders)) $optionOrders = [];

        // 4) Ambil jawaban siswa terbaru langsung dari DB
        $answerRows = $db->table('cbt_answers')
            ->where('student_id', $studentId)
            ->where('test_id',    $testId)
            ->get()
            ->getResultArray();

        $answerMap = [];
        $essayScoreMap = [];
        $latestAnswerTimestamp = !empty($session['updated_at']) ? strtotime($session['updated_at']) : 0;
        foreach ($answerRows as $a) {
            $answerMap[(int)$a['question_id']] = $a['answer'] ?? '';
            if (isset($a['essay_score'])) {
                $essayScoreMap[(int)$a['question_id']] = $a['essay_score'];
            }
            if (!empty($a['updated_at'])) {
                $t = strtotime($a['updated_at']);
                if ($t > $latestAnswerTimestamp) {
                    $latestAnswerTimestamp = $t;
                }
            }
        }

        // 5) Hitung statistik: total soal, terjawab, benar, salah, sebagian benar
        $totalSoal       = count($questionOrder);
        $totalJawab      = 0;
        $totalBenar      = 0;
        $totalSalah      = 0;
        $totalSebagian   = 0;
        $totalEsai       = 0;
        $resultsPerQid   = [];

        foreach ($questionOrder as $qid) {
            $qid = (int)$qid;
            $q   = $questionMap[$qid] ?? null;
            if (!$q) continue;

            $type     = $q['processed_type'];
            $answered = isset($answerMap[$qid]) && trim($answerMap[$qid]) !== '';
            $student  = $answered ? strtoupper(trim($answerMap[$qid])) : '';
            $correct  = strtoupper(trim($q['correct_option'] ?? ''));

            if ($answered) $totalJawab++;
            if ($type === 'esai') {
                $totalEsai++;
                $resultsPerQid[$qid] = 'esai';
                continue;
            }

            if (!$answered) {
                $resultsPerQid[$qid] = 'unanswered';
                continue;
            }

            // Hitung kebenaran
            if ($type === 'pg_kompleks') {
                $sArr = array_filter(array_map('trim', explode(',', $student)));
                $cArr = array_filter(array_map('trim', explode(',', $correct)));
                sort($sArr);
                sort($cArr);
                if ($sArr === $cArr && count($cArr) > 0) {
                    $totalBenar++;
                    $resultsPerQid[$qid] = 'correct';
                } else {
                    $matches = count(array_intersect($sArr, $cArr));
                    $incorrects = count(array_diff($sArr, $cArr));
                    $tCorr = count($cArr);
                    $raw = $matches - (0.5 * $incorrects);
                    $scoreRatio = $tCorr > 0 ? (max(0, $raw) / $tCorr) : 0;
                    if ($scoreRatio >= 0.5) {
                        $totalSebagian++;
                        $resultsPerQid[$qid] = 'partial';
                    } else {
                        $totalSalah++;
                        $resultsPerQid[$qid] = 'incorrect';
                    }
                }
            } elseif ($type === 'benar_salah') {
                $sArr = array_map('trim', explode(',', $student));
                $cArr = array_map('trim', explode(',', $correct));
                $matchedCount = 0;
                $itemCount = 0;
                foreach (['A', 'B', 'C', 'D', 'E'] as $rowIdx => $letter) {
                    if (empty($q['processed_opts'][$letter])) continue;
                    $itemCount++;
                    $sVal = $sArr[$rowIdx] ?? '';
                    $cVal = $cArr[$rowIdx] ?? '';
                    if ($sVal !== '' && $sVal === $cVal) $matchedCount++;
                }
                if ($itemCount > 0 && $matchedCount === $itemCount) {
                    $totalBenar++;
                    $resultsPerQid[$qid] = 'correct';
                } elseif ($itemCount > 0 && ($matchedCount / $itemCount) >= 0.5) {
                    $totalSebagian++;
                    $resultsPerQid[$qid] = 'partial';
                } else {
                    $totalSalah++;
                    $resultsPerQid[$qid] = 'incorrect';
                }
            } else {
                // PG biasa
                if ($student === $correct && $correct !== '') {
                    $totalBenar++;
                    $resultsPerQid[$qid] = 'correct';
                } else {
                    $totalSalah++;
                    $resultsPerQid[$qid] = 'incorrect';
                }
            }
        }

        $totalBelum   = $totalSoal - $totalJawab;
        $percentJawab = $totalSoal > 0 ? round(($totalJawab / $totalSoal) * 100) : 0;
        $objektifCount = $totalSoal - $totalEsai;
        $percentBenar = $objektifCount > 0 ? round(($totalBenar / $objektifCount) * 100) : 0;

        $statusBadge = match($session['status']) {
            'finished' => "<span class='badge bg-primary'><i class='fas fa-check-circle me-1'></i>Selesai</span>",
            'active'   => "<span class='badge bg-success'><i class='fas fa-play-circle me-1'></i>Sedang Ujian</span>",
            default    => "<span class='badge bg-secondary'>{$session['status']}</span>",
        };

        $nilaiDisplay = $session['total_score'] ?? $session['score'] ?? null;
        if ($nilaiDisplay === null || $nilaiDisplay === '') {
            $nilaiDisplay = ($session['status'] === 'active') ? "<span class='text-primary' style='font-size:16px;'>{$totalBenar} Benar</span>" : '-';
        }
        $updatedAt = $latestAnswerTimestamp > 0 ? date('d/m/Y H:i:s', $latestAnswerTimestamp) : (!empty($session['started_at']) ? date('d/m/Y H:i:s', strtotime($session['started_at'])) : '-');

        // 6) Render HTML Modal
        $html = "
        <div id='sessionStatusMarker' data-session-status='{$session['status']}' class='d-none'></div>";

        // Banner Status Live Sinkronisasi
        if ($session['status'] === 'active') {
            $html .= "
            <div class='alert alert-success bg-success-subtle border-success py-2 px-3 d-flex flex-wrap justify-content-between align-items-center mb-3 rounded-3 text-start' style='text-align:left;'>
                <div class='d-flex align-items-center gap-2'>
                    <span class='badge bg-success d-flex align-items-center gap-1'><i class='fas fa-broadcast-tower me-1'></i> LIVE AKTIF</span>
                    <span class='small fw-semibold text-success-emphasis'>Siswa sedang mengerjakan ujian. Jawaban dan progres diperbarui secara langsung.</span>
                </div>
                <div class='small text-muted'>
                    <span class='me-2'><i class='fas fa-clock me-1 text-success'></i>Aktivitas Terakhir: <strong>{$updatedAt}</strong></span>
                    <span class='badge bg-white text-secondary border'><i class='fas fa-sync-alt fa-spin me-1'></i>" . date('H:i:s') . "</span>
                </div>
            </div>";
        } elseif ($session['status'] === 'finished') {
            $html .= "
            <div class='alert alert-primary bg-primary-subtle border-primary py-2 px-3 d-flex flex-wrap justify-content-between align-items-center mb-3 rounded-3 text-start' style='text-align:left;'>
                <div class='d-flex align-items-center gap-2'>
                    <span class='badge bg-primary'><i class='fas fa-check-circle me-1'></i> SELESAI</span>
                    <span class='small fw-semibold text-primary-emphasis'>Siswa telah menyelesaikan ujian ini.</span>
                </div>
                <div class='small text-muted'>
                    <span>Waktu Selesai: <strong>" . (!empty($session['finished_at']) ? date('d/m/Y H:i:s', strtotime($session['finished_at'])) : $updatedAt) . "</strong></span>
                </div>
            </div>";
        }

        $html .= "
        <div class='modal-student-info mb-3 p-3 border rounded-3 bg-light shadow-sm text-start' style='text-align:left;'>
            <div class='d-flex flex-wrap justify-content-between align-items-center gap-2'>
                <div>
                    <h5 class='mb-1 fw-bold text-dark'>{$session['student_name']}</h5>
                    <div class='text-muted small'>
                        <span class='me-2'><i class='fas fa-id-card me-1 text-primary'></i>Username: <strong>{$session['username']}</strong></span>
                        <span class='me-2'><i class='fas fa-hashtag me-1 text-primary'></i>NIS: <strong>{$session['nis']}</strong></span>
                        <span class='me-2'><i class='fas fa-chalkboard-teacher me-1 text-primary'></i>Kelas: <strong>" . esc($session['class_name'] ?? '-') . "</strong></span>
                        <span class='me-2'><i class='fas fa-door-open me-1 text-primary'></i>Ruang: <strong>" . esc($session['room'] ?? '-') . "</strong></span>
                    </div>
                </div>
                <div class='d-flex align-items-center gap-2'>
                    <div>{$statusBadge}</div>
                    <div class='px-3 py-2 bg-white rounded-3 border text-center'>
                        <span class='small text-muted d-block' style='font-size:10px;'>SKOR / NILAI</span>
                        <h4 class='mb-0 fw-bold text-primary'>{$nilaiDisplay}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class='row g-2 mb-3 text-center'>
            <div class='col'>
                <div class='p-2 border rounded bg-white'>
                    <small class='text-muted d-block'>Total Soal</small>
                    <h5 class='mb-0 fw-bold'>{$totalSoal}</h5>
                </div>
            </div>
            <div class='col'>
                <div class='p-2 border rounded bg-success-subtle border-success'>
                    <small class='text-muted d-block'>Terjawab</small>
                    <h5 class='mb-0 text-success fw-bold'>{$totalJawab}</h5>
                </div>
            </div>
            <div class='col'>
                <div class='p-2 border rounded bg-success text-white'>
                    <small class='d-block opacity-75'>Benar</small>
                    <h5 class='mb-0 fw-bold'>{$totalBenar}</h5>
                </div>
            </div>
            <div class='col'>
                <div class='p-2 border rounded bg-danger text-white'>
                    <small class='d-block opacity-75'>Salah</small>
                    <h5 class='mb-0 fw-bold'>{$totalSalah}</h5>
                </div>
            </div>
            <div class='col'>
                <div class='p-2 border rounded bg-warning-subtle border-warning'>
                    <small class='text-muted d-block'>Belum Dijawab</small>
                    <h5 class='mb-0 text-warning-emphasis fw-bold'>{$totalBelum}</h5>
                </div>
            </div>
        </div>

        <div class='d-flex justify-content-between align-items-center mb-1 small text-muted text-start' style='text-align:left;'>
            <span>Progres Pengerjaan: <strong>{$percentJawab}%</strong> ({$totalJawab}/{$totalSoal} soal)</span>
            <span>Akurasi Soal Objektif: <strong>{$percentBenar}%</strong></span>
        </div>
        <div class='progress mb-3' style='height:8px;'>
            <div class='progress-bar bg-success' role='progressbar' style='width:{$percentJawab}%'></div>
        </div>

        <!-- Grid Navigasi Nomor Soal -->
        <div class='card mb-4 border shadow-sm text-start' style='text-align:left;'>
            <div class='card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center text-start'>
                <strong class='small text-secondary'><i class='fas fa-th me-1'></i>Navigasi Nomor Soal Siswa:</strong>
                <span class='text-muted' style='font-size:11px;'>(Klik nomor untuk melompat ke rincian soal)</span>
            </div>
            <div class='card-body p-2 text-start'>
                <div class='d-flex flex-wrap gap-1 justify-content-start'>";

        // Grid kotak nomor soal berwarna sesuai status
        foreach ($questionOrder as $idx => $qid) {
            $qid    = (int)$qid;
            $noUser = $idx + 1;
            $res    = $resultsPerQid[$qid] ?? 'unanswered';

            if ($res === 'unanswered') {
                $bgClass = 'btn-outline-secondary';
                $title   = "Soal No {$noUser}: Belum dijawab";
            } elseif ($res === 'esai') {
                $bgClass = 'btn-info text-dark';
                $title   = "Soal No {$noUser}: Esai / Uraian";
            } elseif ($res === 'correct') {
                $bgClass = 'btn-success text-white';
                $title   = "Soal No {$noUser}: Benar";
            } elseif ($res === 'partial') {
                $bgClass = 'btn-warning text-dark';
                $title   = "Soal No {$noUser}: Sebagian Benar";
            } else {
                $bgClass = 'btn-danger text-white';
                $title   = "Soal No {$noUser}: Salah";
            }

            $html .= "<button type='button' class='btn btn-sm {$bgClass} modal-nav-q-btn p-0 d-flex align-items-center justify-content-center'
                             style='width:34px;height:34px;font-size:12px;font-weight:700;'
                             data-target='soal-card-{$noUser}'
                             title='" . htmlspecialchars($title, ENT_QUOTES) . "'>{$noUser}</button>";
        }

        $html .= "
                </div>
                <div class='mt-2 pt-2 border-top d-flex flex-wrap gap-3 small' style='font-size:11px;'>
                    <span><span class='badge bg-success me-1'>&nbsp;</span> Benar</span>
                    <span><span class='badge bg-danger me-1'>&nbsp;</span> Salah</span>
                    <span><span class='badge bg-warning text-dark me-1'>&nbsp;</span> Sebagian Benar</span>
                    <span><span class='badge bg-info text-dark me-1'>&nbsp;</span> Esai</span>
                    <span><span class='badge bg-light text-secondary border me-1'>&nbsp;</span> Belum Dijawab</span>
                </div>
            </div>
        </div>

        <h6 class='fw-bold mb-3 text-dark text-start' style='text-align:left;'><i class='fas fa-list-check me-2 text-primary'></i>Rincian Soal dan Jawaban:</h6>
        <div class='soal-detail-container text-start' style='text-align:left !important;'>";

        // Rincian setiap soal
        foreach ($questionOrder as $idx => $qid) {
            $qid    = (int)$qid;
            $noUser = $idx + 1;
            $noBank = $noBankMap[$qid] ?? '-';
            $q      = $questionMap[$qid] ?? null;

            if (!$q) {
                $html .= "<div class='alert alert-secondary small mb-2 text-start' id='soal-card-{$noUser}'>Soal nomor {$noUser} (ID: {$qid}) tidak ditemukan di bank soal.</div>";
                continue;
            }

            $type           = $q['processed_type'];
            $qText          = $q['processed_text'];
            $opts           = $q['processed_opts'];
            $correct        = strtoupper(trim($q['correct_option'] ?? ''));
            $studentRaw     = $answerMap[$qid] ?? '';
            $answered       = trim($studentRaw) !== '';
            $studentUpper   = strtoupper(trim($studentRaw));
            $res            = $resultsPerQid[$qid] ?? 'unanswered';

            // Badge Tipe Soal
            $typeLabel = match($type) {
                'pg_kompleks' => "<span class='badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25'>PG Kompleks</span>",
                'benar_salah' => "<span class='badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25'>Benar / Salah</span>",
                'esai'        => "<span class='badge bg-info bg-opacity-10 text-info border border-info border-opacity-25'>Esai / Uraian</span>",
                default       => "<span class='badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25'>Pilihan Ganda</span>",
            };

            // Badge Status Hasil
            $statusBadge = match($res) {
                'correct'     => "<span class='badge bg-success px-2 py-1'><i class='fas fa-check-circle me-1'></i>Benar</span>",
                'partial'     => "<span class='badge bg-warning text-dark px-2 py-1'><i class='fas fa-exclamation-triangle me-1'></i>Sebagian Benar</span>",
                'incorrect'   => "<span class='badge bg-danger px-2 py-1'><i class='fas fa-times-circle me-1'></i>Salah</span>",
                'esai'        => "<span class='badge bg-info text-dark px-2 py-1'><i class='fas fa-pen me-1'></i>Esai</span>",
                default       => "<span class='badge bg-secondary px-2 py-1'><i class='fas fa-minus-circle me-1'></i>Belum Dijawab</span>",
            };

            // Shuffled option mapping: susun urutan opsi sesuai urutan yang dilihat siswa di layarnya
            $shuffledOrder = $optionOrders[$qid] ?? [];
            $displayOpts = [];

            if (!empty($shuffledOrder) && is_array($shuffledOrder)) {
                $screenIndex = 0;
                foreach ($shuffledOrder as $bankLetter) {
                    $bankLetter = strtoupper(trim($bankLetter));
                    if (isset($opts[$bankLetter])) {
                        $displayOpts[] = [
                            'bank_letter'   => $bankLetter,
                            'screen_letter' => chr(65 + $screenIndex),
                            'text'          => $opts[$bankLetter],
                            'is_shuffled'   => true
                        ];
                        $screenIndex++;
                    }
                }
            }

            if (empty($displayOpts)) {
                foreach (['A', 'B', 'C', 'D', 'E'] as $bankLetter) {
                    if (isset($opts[$bankLetter])) {
                        $displayOpts[] = [
                            'bank_letter'   => $bankLetter,
                            'screen_letter' => $bankLetter,
                            'text'          => $opts[$bankLetter],
                            'is_shuffled'   => false
                        ];
                    }
                }
            }

            $cardBorder = match($res) {
                'correct'   => 'border-success',
                'partial'   => 'border-warning',
                'incorrect' => 'border-danger',
                'esai'      => 'border-info',
                default     => 'border-secondary-subtle',
            };

            $html .= "
            <div class='card mb-3 shadow-sm {$cardBorder} text-start' id='soal-card-{$noUser}' style='text-align:left !important;'>
                <div class='card-header bg-light d-flex flex-wrap justify-content-between align-items-center py-2 px-3 text-start'>
                    <div class='d-flex align-items-center gap-2'>
                        <span class='badge bg-dark rounded-pill px-2 py-1'>Soal {$noUser}</span>
                        <span class='text-muted small'>(No. {$noBank} di Bank Soal)</span>
                        {$typeLabel}
                    </div>
                    <div>
                        {$statusBadge}
                    </div>
                </div>
                <div class='card-body p-3 text-start' style='text-align:left !important;'>
                    <!-- Teks Pertanyaan -->
                    <div class='question-content mb-3 text-dark text-start' style='line-height:1.6; text-align:left !important;'>
                        {$qText}
                    </div>";

            // Rincian Jawaban sesuai tipe
            if ($type === 'pg') {
                $html .= "<div class='options-review-list mt-2 text-start' style='text-align:left !important;'>";
                foreach ($displayOpts as $optItem) {
                    $bankLetter   = $optItem['bank_letter'];
                    $screenLetter = $optItem['screen_letter'];
                    $isShuffled   = $optItem['is_shuffled'];
                    $optText      = $optItem['text'];

                    $isStudentChoice = ($answered && $studentUpper === $bankLetter);
                    $isCorrectKey    = ($correct === $bankLetter);

                    $optClass = 'border-secondary-subtle bg-white';
                    $badgeChoice = '';

                    if ($isStudentChoice && $isCorrectKey) {
                        $optClass = 'border-success bg-success-subtle text-success-emphasis fw-bold';
                        $badgeChoice = "<span class='badge bg-success ms-auto'><i class='fas fa-check me-1'></i>Pilihan Siswa & Kunci</span>";
                    } elseif ($isStudentChoice && !$isCorrectKey) {
                        $optClass = 'border-danger bg-danger-subtle text-danger-emphasis fw-bold';
                        $badgeChoice = "<span class='badge bg-danger ms-auto'><i class='fas fa-times me-1'></i>Pilihan Siswa</span>";
                    } elseif ($isCorrectKey) {
                        $optClass = 'border-success bg-success bg-opacity-10 text-success-emphasis';
                        $badgeChoice = "<span class='badge bg-success bg-opacity-75 text-white ms-auto'><i class='fas fa-key me-1'></i>Kunci Jawaban</span>";
                    }

                    $letterBadge = $isShuffled
                        ? "<span class='badge bg-primary text-white border px-2 py-1' style='font-size:12px;' title='Huruf di layar siswa: {$screenLetter}'>{$screenLetter}</span><span class='badge bg-light text-secondary border px-1 py-1' style='font-size:10px;' title='Kunci bank soal: {$bankLetter}'>Bank: {$bankLetter}</span>"
                        : "<span class='badge bg-secondary-subtle text-dark border px-2 py-1' style='font-size:12px;'>{$bankLetter}</span>";

                    $html .= "
                    <div class='p-2 mb-2 border rounded-3 d-flex align-items-start gap-2 text-start {$optClass}' style='text-align:left !important;'>
                        <div class='d-flex align-items-center gap-1 flex-shrink-0 pt-1'>
                            {$letterBadge}
                        </div>
                        <div class='flex-grow-1 text-start' style='text-align:left !important;'>{$optText}</div>
                        <div class='flex-shrink-0'>{$badgeChoice}</div>
                    </div>";
                }
                $html .= "</div>";

            } elseif ($type === 'pg_kompleks') {
                $studentKeys = array_filter(array_map('trim', explode(',', $studentUpper)));
                $correctKeys = array_filter(array_map('trim', explode(',', $correct)));

                $html .= "<div class='options-review-list mt-2 text-start' style='text-align:left !important;'>";
                foreach ($displayOpts as $optItem) {
                    $bankLetter   = $optItem['bank_letter'];
                    $screenLetter = $optItem['screen_letter'];
                    $isShuffled   = $optItem['is_shuffled'];
                    $optText      = $optItem['text'];

                    $isStudentChoice = in_array($bankLetter, $studentKeys);
                    $isCorrectKey    = in_array($bankLetter, $correctKeys);

                    $optClass = 'border-secondary-subtle bg-white';
                    $badgeChoice = '';

                    if ($isStudentChoice && $isCorrectKey) {
                        $optClass = 'border-success bg-success-subtle text-success-emphasis fw-bold';
                        $badgeChoice = "<span class='badge bg-success ms-auto'><i class='fas fa-check me-1'></i>Dipilih (Benar)</span>";
                    } elseif ($isStudentChoice && !$isCorrectKey) {
                        $optClass = 'border-danger bg-danger-subtle text-danger-emphasis fw-bold';
                        $badgeChoice = "<span class='badge bg-danger ms-auto'><i class='fas fa-times me-1'></i>Dipilih (Salah)</span>";
                    } elseif ($isCorrectKey) {
                        $optClass = 'border-success bg-success bg-opacity-10 text-success-emphasis';
                        $badgeChoice = "<span class='badge bg-success bg-opacity-75 text-white ms-auto'><i class='fas fa-key me-1'></i>Kunci Jawaban</span>";
                    }

                    $letterBadge = $isShuffled
                        ? "<span class='badge bg-primary text-white border px-2 py-1' style='font-size:12px;' title='Huruf di layar siswa: {$screenLetter}'>{$screenLetter}</span><span class='badge bg-light text-secondary border px-1 py-1' style='font-size:10px;' title='Kunci bank soal: {$bankLetter}'>Bank: {$bankLetter}</span>"
                        : "<span class='badge bg-secondary-subtle text-dark border px-2 py-1' style='font-size:12px;'>{$bankLetter}</span>";

                    $html .= "
                    <div class='p-2 mb-2 border rounded-3 d-flex align-items-start gap-2 text-start {$optClass}' style='text-align:left !important;'>
                        <div class='d-flex align-items-center gap-1 flex-shrink-0 pt-1'>
                            {$letterBadge}
                        </div>
                        <div class='flex-grow-1 text-start' style='text-align:left !important;'>{$optText}</div>
                        <div class='flex-shrink-0'>{$badgeChoice}</div>
                    </div>";
                }
                $html .= "</div>";

            } elseif ($type === 'benar_salah') {
                $studentBS = explode(',', $studentUpper);
                $correctBS = explode(',', $correct);

                $html .= "
                <div class='table-responsive mt-2 text-start'>
                    <table class='table table-bordered table-sm align-middle mb-0 text-start' style='font-size:13px; text-align:left;'>
                        <thead class='table-light'>
                            <tr>
                                <th style='width:40px;' class='text-center'>No</th>
                                <th class='text-start'>Pernyataan</th>
                                <th class='text-center' style='width:120px;'>Pilihan Siswa</th>
                                <th class='text-center' style='width:120px;'>Kunci Jawaban</th>
                                <th class='text-center' style='width:80px;'>Status</th>
                            </tr>
                        </thead>
                        <tbody>";

                $bsIndex = 0;
                foreach (['A', 'B', 'C', 'D', 'E'] as $rowLetter) {
                    if (empty($opts[$rowLetter])) continue;

                    $sVal = $studentBS[$bsIndex] ?? '';
                    $cVal = $correctBS[$bsIndex] ?? '';
                    $isRowCorrect = ($sVal !== '' && $sVal === $cVal);

                    $sValDisplay = match($sVal) {
                        'B' => "<span class='badge bg-primary'>Benar</span>",
                        'S' => "<span class='badge bg-warning text-dark'>Salah</span>",
                        default => "<span class='text-muted fst-italic'>-</span>"
                    };
                    $cValDisplay = match($cVal) {
                        'B' => "<span class='badge bg-primary'>Benar</span>",
                        'S' => "<span class='badge bg-warning text-dark'>Salah</span>",
                        default => "<span class='text-muted fst-italic'>-</span>"
                    };

                    $rowStatus = $sVal === '' 
                        ? "<span class='badge bg-secondary'>-</span>" 
                        : ($isRowCorrect 
                            ? "<span class='badge bg-success'><i class='fas fa-check'></i></span>" 
                            : "<span class='badge bg-danger'><i class='fas fa-times'></i></span>");

                    $html .= "
                            <tr class='" . ($isRowCorrect ? 'table-success-subtle' : ($sVal !== '' ? 'table-danger-subtle' : '')) . "'>
                                <td class='text-center fw-bold'>{$rowLetter}</td>
                                <td class='text-start' style='text-align:left !important;'>{$opts[$rowLetter]}</td>
                                <td class='text-center'>{$sValDisplay}</td>
                                <td class='text-center'>{$cValDisplay}</td>
                                <td class='text-center'>{$rowStatus}</td>
                            </tr>";

                    $bsIndex++;
                }

                $html .= "
                        </tbody>
                    </table>
                </div>";

            } elseif ($type === 'esai') {
                $essayText = $answered ? nl2br(esc($studentRaw)) : "<em class='text-muted'>Siswa belum mengisi jawaban untuk soal esai ini.</em>";
                $essayScore = $essayScoreMap[$qid] ?? null;

                $scoreDisplay = $essayScore !== null 
                    ? "<span class='badge bg-success px-2 py-1'><i class='fas fa-star me-1'></i>Nilai: {$essayScore}</span>" 
                    : "<span class='badge bg-warning text-dark px-2 py-1'><i class='fas fa-hourglass me-1'></i>Belum Dikoreksi</span>";

                $html .= "
                <div class='mt-2 text-start'>
                    <div class='d-flex justify-content-between align-items-center mb-1'>
                        <span class='small fw-bold text-secondary'><i class='fas fa-file-alt me-1'></i>Jawaban Esai Siswa:</span>
                        {$scoreDisplay}
                    </div>
                    <div class='p-3 bg-light border rounded-3 text-dark text-start' style='font-size:13.5px;line-height:1.6; text-align:left !important;'>
                        {$essayText}
                    </div>
                </div>";
            }

            $html .= "
                </div>
            </div>";
        }

        $html .= "
        </div>
        <div class='text-muted small text-end mt-2 pt-2 border-top'>
            <i class='fas fa-sync-alt me-1'></i>Data sinkronisasi realtime pada: " . date('d/m/Y H:i:s') . "
        </div>";

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
        // Paksa browser tidak cache response ini agar selalu menampilkan data terbaru
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        try {
            helper('cbt');
            $db = $this->db;
            $testId = (int) $testId;

            $test = $db->table('cbt_test_status ts')
                ->select('ts.id, ts.class_codes, ts.bank_id, ts.duration, ts.end_time, ts.subject_type, ts.religion AS test_religion, s.name AS subject_name, s.religion AS subject_religion')
                ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
                ->join('subjects s', 's.id = qb.subject_id', 'left')
                ->where('ts.id', $testId)
                ->get()
                ->getRowArray();

            if (!$test) {
                return $this->response->setBody('<div class="alert alert-danger py-3"><i class="fas fa-exclamation-triangle me-2"></i> Ujian dengan ID ini tidak ditemukan di database.</div>');
            }

            // Sinkronisasi auto-close sesi yang expired jika method tersedia
            if (method_exists($this, 'autoCloseExpiredSessions')) {
                $this->autoCloseExpiredSessions(
                    $testId,
                    (int) ($test['duration'] ?? 0),
                    (string) ($test['end_time'] ?? '')
                );
            }

            // Parsing class_codes (fleksibel: json array atau pemisah koma)
            $rawCodes = $test['class_codes'] ?? '';
            $classList = [];
            if (is_string($rawCodes)) {
                $decoded = json_decode($rawCodes, true);
                if (is_array($decoded)) {
                    $classList = $decoded;
                } elseif (trim($rawCodes) !== '') {
                    $classList = array_map('trim', explode(',', $rawCodes));
                }
            } elseif (is_array($rawCodes)) {
                $classList = $rawCodes;
            }

            $classList = array_values(array_filter(array_map('trim', (array) $classList)));
            if (empty($classList)) {
                return $this->response->setBody('<div class="alert alert-warning py-3"><i class="fas fa-info-circle me-2"></i> Tidak ada kelas yang terdaftar untuk ujian ini.</div>');
            }

            // Deteksi apakah ujian ini khusus untuk mata pelajaran agama tertentu
            $targetReligion = function_exists('cbt_get_test_target_religion') ? cbt_get_test_target_religion($test) : '';

            // Ambil tahun ajaran aktif
            $activeYearId = 0;
            try {
                $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
                $activeYearId = (int) ($activeYear['id'] ?? 0);
            } catch (\Throwable $e) {
                $activeYearId = 0;
            }

            // Ambil siswa aktif: Gunakan LEFT JOIN tanpa groupBy SQL untuk kompatibilitas MariaDB/MySQL ONLY_FULL_GROUP_BY
            $studentQuery = $db->table('students st')
                ->select('st.id, st.name AS student_name, st.nis, st.religion, c.name AS class_name')
                ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif"' . ($activeYearId > 0 ? ' AND sr.academic_year_id = ' . $activeYearId : ''), 'left')
                ->join('classes c', 'c.id = sr.class_id', 'left')
                ->whereIn('c.name', $classList)
                ->orderBy('c.name', 'ASC')
                ->orderBy('st.name', 'ASC')
                ->get()
                ->getResultArray();

            // Deduplikasi siswa di PHP berdasarkan ID
            $seenStudentIds = [];
            $students = [];
            foreach ($studentQuery as $row) {
                $sid = (int) ($row['id'] ?? 0);
                if ($sid > 0 && !isset($seenStudentIds[$sid])) {
                    $seenStudentIds[$sid] = true;
                    $students[] = $row;
                }
            }

            // Khusus mapel agama: filter hanya siswa yang agamanya sesuai
            if ($targetReligion !== '' && function_exists('cbt_normalize_religion')) {
                $students = array_values(array_filter($students, function ($s) use ($targetReligion) {
                    return cbt_normalize_religion($s['religion'] ?? '') === $targetReligion;
                }));
            }

            // 1. Ambil student_id yang tercatat di cbt_sessions
            $doneSessions = $db->table('cbt_sessions')
                ->select('student_id')
                ->where('test_id', $testId)
                ->get()
                ->getResultArray();

            // 2. Ambil student_id yang tercatat di cbt_answers jika tabel ada
            $doneAnswers = [];
            if ($db->tableExists('cbt_answers')) {
                $doneAnswers = $db->table('cbt_answers')
                    ->select('student_id')
                    ->where('test_id', $testId)
                    ->get()
                    ->getResultArray();
            }

            // Normalisasi semua ID yang sudah pernah mengerjakan/mulai ke integer
            $doneSessionIds = array_map('intval', array_column($doneSessions, 'student_id'));
            $doneAnswerIds  = array_map('intval', array_column($doneAnswers, 'student_id'));
            $allDoneIds     = array_unique(array_filter(array_merge($doneSessionIds, $doneAnswerIds)));
            $doneMap        = array_fill_keys($allDoneIds, true);

            // Siswa yang benar-benar belum tes
            $notTested = array_values(array_filter($students, function ($s) use ($doneMap) {
                return !isset($doneMap[(int) $s['id']]);
            }));

            $totalStudents = count($students);
            $totalNotTested = count($notTested);
        $totalDone = max(0, $totalStudents - $totalNotTested);
        $pctDone = $totalStudents > 0 ? round(($totalDone / $totalStudents) * 100, 1) : 0;
        $syncTime = date('H:i:s');

        $religionNotice = '';
        if ($targetReligion !== '') {
            $relLabel = ucfirst($targetReligion);
            $religionNotice = "<div class='alert alert-info py-2 px-3 mb-3 small d-flex align-items-center gap-2'>
                <i class='fas fa-info-circle fa-lg text-primary'></i>
                <div>
                    Khusus Mata Pelajaran <strong>" . esc($test['subject_name'] ?? 'Agama') . "</strong>: 
                    Hanya menampilkan siswa beragama <strong>{$relLabel}</strong>. Siswa dengan agama berbeda tidak diwajibkan mengikuti ujian ini.
                </div>
            </div>";
        }

        if (empty($students)) {
            return $this->response->setBody($religionNotice . '<div class="alert alert-warning py-3"><i class="fas fa-exclamation-circle me-2"></i> Tidak ada siswa ' . ($targetReligion !== '' ? 'beragama ' . ucfirst($targetReligion) . ' ' : '') . 'pada kelas terkait ujian ini.</div>');
        }

        // Statistik Cards
        $statsHtml = "
        <div class='row g-2 mb-3'>
            <div class='col-sm-4'>
                <div class='card border-0 bg-light-subtle shadow-sm rounded-3 p-2 text-center'>
                    <small class='text-muted d-block'>Target Siswa</small>
                    <span class='fs-5 fw-bold text-dark'><i class='fas fa-users me-1 text-secondary'></i> {$totalStudents}</span>
                </div>
            </div>
            <div class='col-sm-4'>
                <div class='card border-0 bg-success-subtle shadow-sm rounded-3 p-2 text-center'>
                    <small class='text-success fw-semibold d-block'>Sudah Ikut Tes</small>
                    <span class='fs-5 fw-bold text-success'><i class='fas fa-user-check me-1'></i> {$totalDone} <small class='fs-6 fw-normal'>({$pctDone}%)</small></span>
                </div>
            </div>
            <div class='col-sm-4'>
                <div class='card border-0 " . ($totalNotTested > 0 ? "bg-warning-subtle" : "bg-light-subtle") . " shadow-sm rounded-3 p-2 text-center'>
                    <small class='" . ($totalNotTested > 0 ? "text-danger" : "text-muted") . " fw-semibold d-block'>Belum Tes</small>
                    <span class='fs-5 fw-bold " . ($totalNotTested > 0 ? "text-danger" : "text-muted") . "'><i class='fas fa-user-times me-1'></i> {$totalNotTested}</span>
                </div>
            </div>
        </div>
        <div class='progress mb-3' style='height: 6px;'>
            <div class='progress-bar bg-success progress-bar-striped progress-bar-animated' role='progressbar' style='width: {$pctDone}%' aria-valuenow='{$pctDone}' aria-valuemin='0' aria-valuemax='100'></div>
        </div>";

        if (empty($notTested)) {
            return $this->response->setBody(
                $religionNotice . $statsHtml .
                '<div class="alert alert-success d-flex align-items-center gap-3 p-3 shadow-sm rounded-3 mb-0">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Semua Siswa Telah Mengikuti Tes 🎉</h6>
                        <small class="text-muted">Seluruh <strong>' . $totalStudents . ' siswa</strong> ' . ($targetReligion !== '' ? 'beragama ' . ucfirst($targetReligion) . ' ' : '') . 'telah mulai atau menyelesaikan ujian ini.</small>
                    </div>
                </div>'
            );
        }

        // Toolbar pencarian dan sinkronisasi
        $toolbarHtml = "
        <div class='d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2'>
            <div class='d-flex align-items-center gap-2'>
                <span class='badge bg-light text-dark border py-1 px-2 small'>
                    <i class='fas fa-clock text-primary me-1'></i> Sinkron: {$syncTime} WIB
                </span>
                <span class='badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2 small'>
                    <i class='fas fa-user-times text-danger me-1'></i> {$totalNotTested} Siswa Belum Tes
                </span>
            </div>
            <div style='min-width: 200px; max-width: 280px;' class='flex-grow-1 flex-sm-grow-0'>
                <div class='input-group input-group-sm'>
                    <span class='input-group-text bg-light'><i class='fas fa-search text-muted'></i></span>
                    <input type='text' id='searchBelumTes' class='form-control form-control-sm' placeholder='Cari nama atau kelas...'>
                </div>
            </div>
        </div>";

        $tableHtml = $religionNotice . $statsHtml . $toolbarHtml . "
        <div class='table-responsive border rounded-3 shadow-sm'>
            <table class='table table-hover table-striped table-sm align-middle mb-0' id='tableBelumTes'>
                <thead class='table-light text-center border-bottom'>
                    <tr>
                        <th style='width: 45px;' class='py-2'>No</th>
                        <th class='text-start py-2'>Nama Siswa</th>
                        <th style='width: 120px;' class='py-2'>Kelas</th>" .
                        ($targetReligion !== '' ? "<th style='width: 110px;' class='py-2'>Agama</th>" : "") . "
                        <th style='width: 130px;' class='py-2'>Status</th>
                    </tr>
                </thead>
                <tbody>";

        $i = 1;
        foreach ($notTested as $row) {
            $nisInfo = !empty($row['nis']) ? "<span class='text-muted small ms-1'>(" . esc($row['nis']) . ")</span>" : "";
            $tableHtml .= "<tr class='belum-tes-row'>
                <td class='text-center text-muted fw-semibold'>{$i}</td>
                <td class='text-start'>
                    <div class='fw-semibold text-dark'>" . esc($row['student_name']) . "</div>
                    {$nisInfo}
                </td>
                <td class='text-center'>
                    <span class='badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1'>" . esc($row['class_name']) . "</span>
                </td>" .
                ($targetReligion !== '' ? "<td class='text-center'><span class='badge bg-light text-dark border px-2 py-1'>" . esc($row['religion'] ?: '-') . "</span></td>" : "") . "
                <td class='text-center'>
                    <span class='badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1'>
                        <i class='fas fa-times-circle me-1'></i> Belum Tes
                    </span>
                </td>
            </tr>";
            $i++;
        }

        $tableHtml .= "
                <tr id='noSearchResultRow' style='display: none;'>
                    <td colspan='" . ($targetReligion !== '' ? "5" : "4") . "' class='text-center text-muted py-4'>
                        <i class='fas fa-search me-1'></i> Tidak ditemukan siswa yang cocok dengan pencarian.
                    </td>
                </tr>
            </tbody>
        </table>
        </div>";

        return $this->response->setBody($tableHtml);
        } catch (\Throwable $e) {
            log_message('error', '[CbtAktivitas::belumTes] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $errorMsg = esc($e->getMessage());
            return $this->response->setStatusCode(200)->setBody("
                <div class='alert alert-danger py-3 shadow-sm'>
                    <div class='d-flex align-items-center gap-2 mb-2'>
                        <i class='fas fa-exclamation-triangle fa-lg text-danger'></i>
                        <strong class='fs-6'>Terjadi Kendala Memuat Data Siswa</strong>
                    </div>
                    <p class='small text-muted mb-2'>Pesan sistem: <code>{$errorMsg}</code></p>
                    <button type='button' class='btn btn-sm btn-outline-danger' onclick='if(typeof fetchBelumTes===\"function\"&&currentBelumTesTestId){fetchBelumTes(currentBelumTesTestId,false);}'>
                        <i class='fas fa-sync-alt me-1'></i> Coba Muat Ulang
                    </button>
                </div>
            ");
        }
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
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;
        
        $builder = $db->table('cbt_sessions cs');
        $subquery = '(
            SELECT student_id, MAX(id) as max_session_id
            FROM cbt_sessions
            WHERE test_id = ' . (int) $testId . '
            GROUP BY student_id
        ) latest';

        if ($classFilter && $classFilter !== 'all') {
            $subquery = '(
                SELECT student_id FROM student_records WHERE class_id = ' . (int) $classFilter . ' AND status = "aktif" AND academic_year_id = ' . $activeYearId . '
            )';
            $builder->where("cs.student_id IN $subquery", null, false);
        }

        $builder->select('cs.student_id, st.name AS student_name, st.nis, c.name AS class_name, 
                      COALESCE(cs.total_score, cs.score, 0) as final_score, 
                      cs.status, cs.finished_at')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . $activeYearId, 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->join('(SELECT student_id, MAX(id) as max_session_id FROM cbt_sessions WHERE test_id = ' . (int) $testId . ' GROUP BY student_id) latest', 'cs.id = latest.max_session_id', 'inner')
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
            
        $activeYear = class_exists('\App\Models\AcademicYearModel') 
            ? (new \App\Models\AcademicYearModel())->getActiveYear() 
            : null;
        $activeYearId = (int) ($activeYear['id'] ?? 0);

        // Cek keberadaan tabel student_records
        $hasStudentRecords = $this->db->tableExists('student_records');

        // Gunakan $this->db->table() agar tidak terjadi cartesian product (double FROM) pada Model
        $builder = $this->db->table('cbt_sessions cs');

        if ($hasStudentRecords) {
            $builder->select('cs.*, st.name AS student_name, COALESCE(cl.name, cl_direct.name) AS class_name, COALESCE(sr.class_id, st.class_id) AS class_id')
                ->join('students st', 'st.id = cs.student_id', 'left')
                ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif"' . ($activeYearId ? ' AND sr.academic_year_id = ' . $activeYearId : ''), 'left')
                ->join('classes cl', 'cl.id = sr.class_id', 'left')
                ->join('classes cl_direct', 'cl_direct.id = st.class_id', 'left');
        } else {
            $builder->select('cs.*, st.name AS student_name, cl.name AS class_name, st.class_id')
                ->join('students st', 'st.id = cs.student_id', 'left')
                ->join('classes cl', 'cl.id = st.class_id', 'left');
        }

        $builder->join("($subquery) latest", 'cs.id = latest.max_id', 'inner')
            ->where('cs.test_id', $testId);

        if ($mode === 'kelas' && !empty($classId)) {
            if ($hasStudentRecords) {
                if (is_numeric($classId)) {
                    $builder->groupStart()
                        ->where('sr.class_id', (int) $classId)
                        ->orWhere('st.class_id', (int) $classId)
                        ->orWhere('cl.id', (int) $classId)
                        ->orWhere('cl_direct.id', (int) $classId)
                    ->groupEnd();
                } else {
                    $builder->groupStart()
                        ->where('cl.name', $classId)
                        ->orWhere('cl_direct.name', $classId)
                    ->groupEnd();
                }
            } else {
                if (is_numeric($classId)) {
                    $builder->groupStart()
                        ->where('st.class_id', (int) $classId)
                        ->orWhere('cl.id', (int) $classId)
                    ->groupEnd();
                } else {
                    $builder->where('cl.name', $classId);
                }
            }
        }

        $sessions = $builder
            ->orderBy('st.name', 'ASC')
            ->get()
            ->getResultArray();

        if (!$sessions) {
            if ($mode === 'kelas' && !empty($classId)) {
                $targetClassName = '';
                if (is_numeric($classId)) {
                    $clsRow = $this->db->table('classes')->select('name')->where('id', $classId)->get()->getRowArray();
                    $targetClassName = $clsRow['name'] ?? "ID {$classId}";
                } else {
                    $targetClassName = $classId;
                }
                return redirect()->back()->with('error', 'Tidak ada data sesi ujian untuk kelas "' . $targetClassName . '". Pastikan siswa dari kelas tersebut sudah mengerjakan ujian.');
            }
            return redirect()->back()->with('error', 'Tidak ada data sesi ditemukan untuk ujian ini.');
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
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        $sessions = $this->sessionModel
            ->select('cbt_sessions.*, st.username AS exam_number, st.name AS student_name, st.nis, sr.class_id, cl.name AS class_name')
            ->join('students st', 'st.id = cbt_sessions.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . $activeYearId, 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
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
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;
        
        $student = $db->table('students st')
            ->select('st.id, st.name, st.nis, sr.class_id, cl.name AS class_name')
            ->join('student_records sr', 'sr.student_id = st.id AND sr.status = "aktif" AND sr.academic_year_id = ' . $activeYearId, 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
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

    // =========================================================
    // EXPORT NILAI — untuk diimpor ke ESPATA
    // Menghasilkan file JSON berisi nilai siswa dari satu ujian.
    // Kunci pencocokan siswa: NIS (bukan student_id lokal).
    // =========================================================
    public function exportNilai($testId)
    {
        $testId = (int) $testId;
        $db     = $this->db;

        // Info ujian
        $test = $db->table('cbt_test_status ts')
            ->select('ts.id, ts.bank_id, ts.class_codes, ts.semester,
                      qb.code AS bank_code,
                      s.id AS subject_id, s.name AS subject_name, s.code AS subject_code,
                      en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id',   'left')
            ->join('subjects s',             's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en',      'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()->getRowArray();

        if (!$test) {
            return $this->response->setStatusCode(404)
                ->setJSON(['error' => 'Ujian tidak ditemukan.']);
        }

        // Nilai siswa: ambil sesi terbaru per siswa (MAX id)
        $subquery = "(SELECT student_id, MAX(id) AS max_id
                      FROM cbt_sessions WHERE test_id = {$testId}
                      GROUP BY student_id) latest";

        $rows = $db->table('cbt_sessions cs')
            ->select('st.nis, st.name AS student_name,
                      c.name AS class_name,
                      COALESCE(cs.total_score, cs.score, 0) AS score,
                      cs.status, cs.finished_at')
            ->join('students st', 'st.id = cs.student_id', 'left')
            ->join('classes c',   'c.id = st.class_id',    'left')
            ->join($subquery,     'cs.id = latest.max_id', 'inner')
            ->where('cs.test_id', $testId)
            ->orderBy('c.name', 'ASC')
            ->orderBy('st.name', 'ASC')
            ->get()->getResultArray();

        $students = [];
        foreach ($rows as $r) {
            $students[] = [
                'nis'        => $r['nis'],
                'name'       => $r['student_name'],
                'class_name' => $r['class_name'],
                'score'      => round((float)$r['score'], 2),
                'ikut_ujian' => ($r['status'] === 'finished'),
                'finished_at'=> $r['finished_at'] ?? null,
            ];
        }

        $payload = [
            'format'        => 'espata_cbt_export_v1',
            'exported_at'   => date('Y-m-d H:i:s'),
            'test_id'       => $testId,
            'bank_code'     => $test['bank_code'],
            'exam_name'     => $test['exam_name'],
            'subject_id'    => $test['subject_id'],
            'subject_name'  => $test['subject_name'],
            'subject_code'  => $test['subject_code'],
            'class_codes'   => $test['class_codes'],
            'semester'      => $test['semester'],
            'total_students'=> count($students),
            'students'      => $students,
        ];

        $filename = 'nilai_cbt_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $test['bank_code'])
                    . '_' . date('Ymd_His') . '.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

