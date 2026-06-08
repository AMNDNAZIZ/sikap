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

        // Ambil soal esai dan jawaban siswa
        $soal = $db->table('cbt_questions q')
            ->select('q.id, q.question_text, a.answer, a.score')
            ->join('cbt_answers a', 'a.question_id = q.id AND a.student_id = ' . (int) $studentId, 'left')
            ->where('q.bank_id', $test['bank_id'])
            ->whereIn('q.question_type', ['esai', 'essay'])
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        if (!$soal) {
            return '<div class="alert alert-warning">Tidak ada soal esai pada tes ini.</div>';
        }

        // Kirim data ke view modal
        return view('admin/cbt/aktivitas/modal_nilai_esai', [
            'test_id' => $testId,
            'student_id' => $studentId,
            'bobot_esai' => $test['bobot_esai'] ?? 0,
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

        $db = $this->db;

        // Ambil bobot esai & nilai PG siswa
        $test = $db->table('cbt_test_status')
            ->select('bobot_esai')
            ->where('id', $testId)
            ->get()
            ->getRowArray();

        $session = $db->table('cbt_sessions')
            ->select('score')
            ->where('test_id', $testId)
            ->where('student_id', $studentId)
            ->get()
            ->getRowArray();

        $bobotEsai = $test['bobot_esai'] ?? 0;
        $nilaiPg = $session['score'] ?? 0;

        // Simpan nilai tiap soal esai di cbt_answers.score
        $totalNilaiEsai = 0;
        $jumlahSoal = count($nilaiSoal);

        foreach ($nilaiSoal as $qid => $nilai) {
            $totalNilaiEsai += floatval($nilai);
            $db->table('cbt_answers')
                ->where('question_id', $qid)
                ->where('student_id', $studentId)
                ->update(['score' => $nilai]);
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

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Nilai esai berhasil disimpan',
            'nilai' => [
                'rata_esai' => $essayScore,
                'bobot' => $bobotEsai,
                'essay_score' => $essayScore,
                'pg' => $nilaiPg,
                'total' => $totalScore,
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
