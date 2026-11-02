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

        // Ambil data sesi siswa
        $session = $db->table('cbt_sessions')
            ->select('id, score, status')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->get()
            ->getRowArray();
        $sessionId = $session['id'] ?? null;

        // Ambil soal esai dan jawaban siswa
        $soal = $db->table('cbt_questions q')
            ->select('q.id, q.question_text, a.id AS answer_id, a.answer, a.score')
            ->join('cbt_answers a', 'a.question_id = q.id AND a.student_id = ' . (int) $studentId, 'left')
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
                    ->select('eg.*, u.nama_lengkap AS grader_name, u.username AS grader_username')
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
        $testId = $this->request->getPost('test_id');
        $studentId = $this->request->getPost('student_id');
        $nilaiSoal = $this->request->getPost('scores'); // array: [question_id => nilai_per_soal]
        $feedbackSoal = $this->request->getPost('feedbacks') ?? []; // array: [question_id => catatan_guru]

        $db = $this->db;
        $userSession = session('user') ?? [];
        $graderId = (int)($userSession['id'] ?? session('user_id') ?? 1);

        // Ambil bobot esai & nilai PG siswa
        $test = $db->table('cbt_test_status')
            ->select('bank_id, bobot_esai')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        $session = $db->table('cbt_sessions')
            ->select('id, score')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->get()
            ->getRowArray();

        $sessionId = $session['id'] ?? null;
        $bobotEsai = $test['bobot_esai'] ?? 0;
        $nilaiPg = $session['score'] ?? 0;

        $hasV3Grades = $db->tableExists('cbt_essay_grades_v3');
        $hasV3Scores = $db->tableExists('cbt_session_scores_v3');
        $hasV3Audit  = $db->tableExists('cbt_audit_trail_v3');

        // Simpan nilai tiap soal esai dan catat revisi append-only
        $totalNilaiEsai = 0;
        $jumlahSoal = is_array($nilaiSoal) ? count($nilaiSoal) : 0;
        $pendingCount = 0;

        if (is_array($nilaiSoal)) {
            foreach ($nilaiSoal as $qid => $nilai) {
                if ($nilai === '' || $nilai === null) {
                    $pendingCount++;
                    continue;
                }

                $scoreVal = floatval($nilai);
                $totalNilaiEsai += $scoreVal;

                // 1. Update legacy cbt_answers
                $db->table('cbt_answers')
                    ->where('question_id', $qid)
                    ->where('student_id', $studentId)
                    ->update(['score' => $scoreVal]);

                // 2. Append-Only Revision in cbt_essay_grades_v3
                if ($hasV3Grades && $sessionId) {
                    $ansRow = $db->table('cbt_answers')
                        ->where('question_id', $qid)
                        ->where('student_id', $studentId)
                        ->get()
                        ->getRowArray();
                    $ansId = (int)($ansRow['id'] ?? $qid);

                    // Demote prior active versions
                    $db->table('cbt_essay_grades_v3')
                        ->where('answer_id', $ansId)
                        ->where('is_current_confirmed', 1)
                        ->update(['is_current_confirmed' => 0]);

                    // Get next version number
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

                    // Update cbt_answers_v3 if exists
                    if ($db->tableExists('cbt_answers_v3')) {
                        $db->table('cbt_answers_v3')
                            ->where('session_id', $sessionId)
                            ->where('student_id', $studentId)
                            ->update(['allocated_score' => $scoreVal]);
                    }
                }
            }
        }

        // Rata-rata nilai esai dalam skala 100
        $essayScore = $jumlahSoal ? round($totalNilaiEsai / $jumlahSoal, 2) : 0;

        // Hitung total_score dengan bobot
        $totalScore = round(
            $nilaiPg + ($essayScore * $bobotEsai / 100),
            2
        );

        // Simpan ke cbt_sessions
        $db->table('cbt_sessions')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->update([
                'essay_score' => $essayScore,
                'total_score' => $totalScore
            ]);

        // Enforce DEC-ESS-01 on cbt_session_scores_v3
        $publicationEligible = ($pendingCount === 0);
        if ($hasV3Scores && $sessionId) {
            $existingScoreV3 = $db->table('cbt_session_scores_v3')->where('session_id', $sessionId)->get()->getRowArray();
            $payloadScore = [
                'session_id'              => $sessionId,
                'score_pg'                => $nilaiPg,
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
                'pg'          => $nilaiPg,
                'total'       => $totalScore,
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
