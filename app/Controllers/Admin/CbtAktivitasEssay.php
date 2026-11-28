<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtAnswerModel;
use App\Models\CbtQuestionModel;
use App\Models\StudentModel;

class CbtAktivitasEssay extends BaseController
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
     * 📝 Halaman laporan jawaban siswa dengan form input nilai esai
     */
    public function laporanJawaban($testId)
    {
        helper('cbt');
        if (!can_access_cbt_test_status((int) $testId)) {
            return redirect()->to(site_url('admin/cbt/aktivitas'))->with('error', 'Anda tidak memiliki akses ke ujian ini.');
        }
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

        // Ambil daftar siswa yang mengikuti tes
        $sessions = $this->sessionModel
            ->select('cbt_sessions.*, st.nis AS exam_number, st.name AS student_name, st.nis, 
                      sr.class_id, cl.name AS class_name')
            ->join('students st', 'st.id = cbt_sessions.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = st.id', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->where('cbt_sessions.test_id', $testId)
            ->orderBy('st.name', 'ASC')
            ->findAll();

        return view('admin/cbt/aktivitas/laporan_jawaban', [
            'test' => $test,
            'sessions' => $sessions
        ]);
    }

    /**
     * 📊 AJAX: Ambil soal esai dan jawaban siswa untuk modal penilaian
     */
    public function getSoalEsai($testId, $studentId)
    {
        helper('cbt');
        if (!can_access_cbt_test_status((int) $testId)) {
            return '<div class="alert alert-danger">Anda tidak memiliki akses ke ujian ini.</div>';
        }
        $db = $this->db;

        // Ambil data bobot esai dari cbt_test_status
        $test = $db->table('cbt_test_status')
            ->select('bank_id, bobot_esai')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        if (!$test) {
            return '<div class="alert alert-danger">Tes tidak ditemukan.</div>';
        }

        // Ambil data sesi siswa terbaru
        $session = $db->table('cbt_sessions')
            ->select('id, score, status')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        $sessionId = $session['id'] ?? null;

        // Ambil soal esai dan jawaban siswa (terikat test_id!)
        $soal = $db->table('cbt_questions q')
            ->select('q.id, q.question_text, a.id AS answer_id, a.answer, a.score')
            ->join('cbt_answers a', 'a.question_id = q.id AND a.student_id = ' . (int) $studentId . ' AND a.test_id = ' . (int) $testId, 'left')
            ->where('q.bank_id', $test['bank_id'])
            ->whereIn('q.question_type', ['esai', 'essay'])
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        if (!$soal) {
            return '<div class="alert alert-warning">Tidak ada soal esai pada tes ini.</div>';
        }

        // Ambil riwayat revisi penilaian esai (V3 Append-Only History)
        $hasV3Grades = $db->tableExists('cbt_essay_grades_v3');
        $pendingCount = 0;

        foreach ($soal as $k => $s) {
            $ansId = (int)($s['answer_id'] ?? 0);
            $revisions = [];
            if ($hasV3Grades && $ansId > 0) {
                $revisions = $db->table('cbt_essay_grades_v3 eg')
                    ->select('eg.*, u.fullname AS grader_name, u.username AS grader_username')
                    ->join('users u', 'u.id = eg.grader_id', 'left')
                    ->where('eg.answer_id', $ansId)
                    ->orderBy('eg.version_number', 'ASC')
                    ->get()
                    ->getResultArray();
            }
            $soal[$k]['revisions'] = $revisions;
            if ($s['score'] === null || $s['score'] === '') {
                $pendingCount++;
            }
        }

        // Kirim data ke view modal
        return view('admin/cbt/aktivitas/modal_nilai_esai', [
            'test_id' => $testId,
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'bobot_esai' => $test['bobot_esai'] ?? 0,
            'pending_count' => $pendingCount,
            'soal' => $soal
        ]);
    }

    /**
     * 💾 Simpan nilai esai per soal dan hitung total score dengan bobot
     */
    public function simpanNilaiEsaiDetail()
    {
        $testId = (int) $this->request->getPost('test_id');
        $studentId = (int) $this->request->getPost('student_id');
        $nilaiSoal = $this->request->getPost('scores'); // array: [question_id => nilai_per_soal]
        $feedbackSoal = $this->request->getPost('feedbacks') ?? []; // array: [question_id => catatan_guru]

        helper('cbt');
        if (!can_access_cbt_test_status($testId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk menilai ujian ini.'])->setStatusCode(403);
        }

        $db = $this->db;
        $userSession = session('user') ?? [];
        $graderId = (int)($userSession['id'] ?? session('user_id') ?? 1);

        $test = $db->table('cbt_test_status')
            ->select('bank_id, bobot_pg, bobot_pg_kompleks, bobot_bs, bobot_esai')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        $session = $db->table('cbt_sessions')
            ->select('id, score')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$test || !$session) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data ujian atau sesi siswa tidak ditemukan.'])->setStatusCode(404);
        }

        $sessionId = (int) $session['id'];
        $bobotEsai = (float) ($test['bobot_esai'] ?? 0);

        $hasV3Grades = $db->tableExists('cbt_essay_grades_v3');
        $hasV3Scores = $db->tableExists('cbt_session_scores_v3');
        $hasV3Audit  = $db->tableExists('cbt_audit_trail_v3');

        // Simpan nilai tiap soal esai (terikat test_id) dan catat revisi append-only
        $totalNilaiEsai = 0.0;
        $jumlahSoal = is_array($nilaiSoal) ? count($nilaiSoal) : 0;
        $pendingCount = 0;

        if (is_array($nilaiSoal)) {
            foreach ($nilaiSoal as $qid => $nilai) {
                $qid = (int) $qid;
                if ($nilai === '' || $nilai === null) {
                    $pendingCount++;
                    continue;
                }

                $scoreVal = floatval($nilai);
                $totalNilaiEsai += $scoreVal;

                // 1. Upsert legacy cbt_answers scoped strictly to (student_id, test_id, question_id)
                $ansRow = $db->table('cbt_answers')
                    ->where('question_id', $qid)
                    ->where('student_id', $studentId)
                    ->where('test_id', $testId)
                    ->get()
                    ->getRowArray();

                if ($ansRow) {
                    $db->table('cbt_answers')->where('id', $ansRow['id'])->update(['score' => $scoreVal]);
                    $ansId = (int) $ansRow['id'];
                } else {
                    $db->table('cbt_answers')->insert([
                        'student_id'  => $studentId,
                        'test_id'     => $testId,
                        'question_id' => $qid,
                        'answer'      => '',
                        'score'       => $scoreVal,
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                    $ansId = (int) $db->insertID();
                }

                // 2. Append-Only Revision in cbt_essay_grades_v3
                if ($hasV3Grades && $sessionId && $ansId > 0) {
                    $db->table('cbt_essay_grades_v3')
                        ->where('answer_id', $ansId)
                        ->where('is_current_confirmed', 1)
                        ->update(['is_current_confirmed' => 0]);

                    $latestVer = $db->table('cbt_essay_grades_v3')
                        ->where('answer_id', $ansId)
                        ->selectMax('version_number')
                        ->get()
                        ->getRowArray();
                    $nextVer = ((int)($latestVer['version_number'] ?? 0)) + 1;

                    $feedback = !empty($feedbackSoal[$qid]) ? trim($feedbackSoal[$qid]) : null;

                    $db->table('cbt_essay_grades_v3')->insert([
                        'answer_id'            => $ansId,
                        'session_id'           => $sessionId,
                        'grader_id'            => $graderId,
                        'version_number'       => $nextVer,
                        'score_awarded'        => $scoreVal,
                        'feedback'             => $feedback,
                        'is_current_confirmed' => 1,
                        'graded_at'            => date('Y-m-d H:i:s'),
                    ]);

                    if ($db->tableExists('cbt_answers_v3')) {
                        $db->table('cbt_answers_v3')
                            ->where('session_id', $sessionId)
                            ->where('student_id', $studentId)
                            ->where('item_version_id', $qid)
                            ->update(['allocated_score' => $scoreVal]);
                    }
                }
            }
        }

        // Rata-rata nilai esai dalam skala 100
        $gradedCount = max(0, $jumlahSoal - $pendingCount);
        $essayScore = $jumlahSoal > 0 ? round($totalNilaiEsai / $jumlahSoal, 2) : 0.0;

        // Simpan essay_score dulu lalu hitung ulang total skor dari NOL secara terpusat (anti double-counting!)
        $publicationEligible = ($pendingCount === 0);
        $db->table('cbt_sessions')
            ->where('id', $sessionId)
            ->update(['essay_score' => $gradedCount > 0 ? $essayScore : null]);

        $scoringService = new \App\Services\CbtScoringService();
        $breakdown = $scoringService->calculateScoreBreakdown($studentId, $testId);
        $stats = $breakdown['stats'] ?? [];
        $objScore = round(
            ($stats['pg']['contribution'] ?? 0)
            + ($stats['pgk']['contribution'] ?? 0)
            + ($stats['bs']['contribution'] ?? 0),
            2
        );
        $totalScore = (float) ($breakdown['provisional_score'] ?? $objScore);

        $db->table('cbt_sessions')
            ->where('id', $sessionId)
            ->update([
                'score'       => $totalScore,
                'essay_score' => $gradedCount > 0 ? $essayScore : null,
                'total_score' => $publicationEligible ? $totalScore : null,
            ]);

        // Enforce DEC-ESS-01 on cbt_session_scores_v3
        if ($hasV3Scores && $sessionId) {
            $existingScoreV3 = $db->table('cbt_session_scores_v3')->where('session_id', $sessionId)->get()->getRowArray();
            $payloadScore = [
                'session_id'              => $sessionId,
                'score_pg'                => round((float) ($stats['pg']['score'] ?? 0), 3),
                'score_pg_kompleks'       => round((float) ($stats['pgk']['score'] ?? 0), 3),
                'score_bs'                => round((float) ($stats['bs']['score'] ?? 0), 3),
                'score_esai'              => $publicationEligible ? $essayScore : null,
                'final_score'             => $publicationEligible ? $totalScore : null,
                'report_score'            => $publicationEligible ? $totalScore : null,
                'pending_essay_count'     => $pendingCount,
                'publication_eligibility' => $publicationEligible ? 1 : 0,
                'updated_at'              => date('Y-m-d H:i:s'),
            ];

            if ($existingScoreV3) {
                $db->table('cbt_session_scores_v3')->where('session_id', $sessionId)->update($payloadScore);
            } else {
                $payloadScore['calculated_at'] = date('Y-m-d H:i:s');
                $db->table('cbt_session_scores_v3')->insert($payloadScore);
            }
        }

        // Append HMAC Audit Trail log
        if ($hasV3Audit && $sessionId) {
            try {
                $auditModel = new \App\Models\CbtAuditTrailV3Model();
                $auditModel->appendLog(
                    'ESSAY_GRADED',
                    'cbt_essay_grades_v3',
                    (string)$sessionId,
                    (int)$graderId,
                    'TEACHER',
                    [
                        'test_id'                 => $testId,
                        'student_id'              => $studentId,
                        'session_id'              => $sessionId,
                        'pending_count'           => $pendingCount,
                        'publication_eligibility' => $publicationEligible ? 1 : 0,
                        'essay_score'             => $essayScore,
                        'total_score'             => $publicationEligible ? $totalScore : null,
                    ]
                );
            } catch (\Throwable $e) {
                log_message('error', '[CbtAktivitasEssay] Audit trail log failed: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Nilai esai dan riwayat revisi berhasil disimpan.',
            'nilai' => [
                'rata_esai'   => $essayScore,
                'bobot'       => $bobotEsai,
                'essay_score' => $essayScore,
                'pg'          => $objScore,
                'total'       => $publicationEligible ? $totalScore : null,
                'provisional_total' => $totalScore,
            ],
            'dec_ess_01' => [
                'pending_essay_count'  => $pendingCount,
                'publication_eligible' => $publicationEligible,
                'final_score'          => $publicationEligible ? $totalScore : null,
            ],
        ]);
    }

    /**
     * 📄 Generate PDF laporan jawaban siswa
     */
    public function laporanJawabPdf($testId, $studentId)
    {
        $db = $this->db;

        // Ambil informasi tes
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

        // Ambil data siswa + kelas
        $student = $db->table('students st')
            ->select('st.id, st.name, st.nis, sr.class_id, cl.name AS class_name')
            ->join('student_records sr', 'sr.student_id = st.id', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->where('st.id', $studentId)
            ->get()
            ->getRowArray();

        if (!$student) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        // Ambil session
        $session = $db->table('cbt_sessions cs')
            ->where('cs.test_id', $testId)
            ->where('cs.student_id', $studentId)
            ->get()
            ->getRowArray();

        // Ambil semua jawaban siswa
        $answers = $db->table('cbt_answers a')
            ->select('a.*, q.question_text, q.question_type, q.correct_option,
                      q.option_a, q.option_b, q.option_c, q.option_d, q.option_e')
            ->join('cbt_questions q', 'q.id = a.question_id', 'left')
            ->where('a.test_id', $testId)
            ->where('a.student_id', $studentId)
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        // Siapkan data tampilan
        $questionData = [];
        foreach ($answers as $a) {
            $type = strtolower($a['question_type'] ?? 'pg');
            $questionData[] = [
                'id' => $a['question_id'],
                'type' => $type,
                'text' => $a['question_text'],
                'options' => [
                    'A' => $a['option_a'] ?? null,
                    'B' => $a['option_b'] ?? null,
                    'C' => $a['option_c'] ?? null,
                    'D' => $a['option_d'] ?? null,
                    'E' => $a['option_e'] ?? null,
                ],
                'answer' => $a['answer'] ?? '',
                'correct_option' => $a['correct_option'] ?? '',
                'score' => $a['score'] ?? null,
            ];
        }

        // Urutkan: PG dulu baru esai
        usort($questionData, function ($a, $b) {
            $order = ['pg' => 1, 'pilihan_ganda' => 1, 'pgk' => 1, 'esai' => 2, 'essay' => 2];
            return ($order[$a['type']] ?? 99) <=> ($order[$b['type']] ?? 99);
        });

        // Nilai
        $nilaiPg = $session['score'] ?? 0;
        $nilaiEsai = $session['essay_score'] ?? 0;
        $nilaiTotal = $session['total_score'] ?? $nilaiPg;
        $bobotEsai = $test['bobot_esai'] ?? 0;
        $bobotPg = $test['bobot_pg'] ?? (100 - $bobotEsai);

        // Render view ke HTML
        $html = view('admin/cbt/aktivitas/pdf_laporan_jawaban', [
            'test' => $test,
            'student' => $student,
            'questions' => $questionData,
            'nilai_pg' => $nilaiPg,
            'nilai_esai' => $nilaiEsai,
            'nilai_total' => $nilaiTotal,
            'bobot_pg' => $bobotPg,
            'bobot_esai' => $bobotEsai,
        ]);

        // Bersihkan buffer
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Generate PDF
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Ambil hasil PDF sebagai string
        $output = $dompdf->output();
        $filenameSafe = 'Laporan_Jawaban_' . preg_replace('/[^A-Za-z0-9]/', '_', $student['name'] ?? 'Siswa') . '.pdf';

        // Return via response
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filenameSafe . '"')
            ->setBody($output);
    }
}
