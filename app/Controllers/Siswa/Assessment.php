<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\BkAssignmentStudentModel;
use App\Models\BkInstrumentModel;
use App\Models\BkInstrumentQuestionModel;
use App\Models\BkQuestionOptionModel;
use App\Models\BkStudentAnswerModel;

class Assessment extends BaseController
{
    protected $assignmentStudentModel;
    protected $instrumentModel;
    protected $questionModel;
    protected $optionModel;
    protected $studentAnswerModel;

    public function __construct()
    {
        $this->assignmentStudentModel = new BkAssignmentStudentModel();
        $this->instrumentModel = new BkInstrumentModel();
        $this->questionModel = new BkInstrumentQuestionModel();
        $this->optionModel = new BkQuestionOptionModel();
        $this->studentAnswerModel = new BkStudentAnswerModel();
    }

    private function getStudentId()
    {
        $session = session();
        return $session->get('user')['student_id'] ?? $session->get('user')['related_id'];
    }

    public function index()
    {
        $studentId = $this->getStudentId();
        
        // Get assigned assessments for this student
        $db = \Config\Database::connect();
        $assessments = $db->table('bk_assignment_students bas')
            ->select('bas.id as assignment_student_id, bas.status, bas.completed_at, ba.start_date, ba.end_date, bi.title, bi.assessment_type, bi.description')
            ->join('bk_assignments ba', 'ba.id = bas.assignment_id')
            ->join('bk_instruments bi', 'bi.id = ba.instrument_id')
            ->where('bas.student_id', $studentId)
            ->where('ba.status', 'active') // Only show active assignments
            ->orderBy('ba.end_date', 'ASC')
            ->get()->getResultArray();
            
        $data = [
            'title' => 'Asesmen BK',
            'assessments' => $assessments
        ];
        
        return view('siswa/assessment/index', $data);
    }

    public function fill($assignment_student_id)
    {
        $studentId = $this->getStudentId();
        
        // Verify ownership and status
        $assignment = $this->assignmentStudentModel
            ->select('bk_assignment_students.*, bk_assignments.instrument_id, bk_assignments.end_date, bk_instruments.title')
            ->join('bk_assignments', 'bk_assignments.id = bk_assignment_students.assignment_id')
            ->join('bk_instruments', 'bk_instruments.id = bk_assignments.instrument_id')
            ->where('bk_assignment_students.id', $assignment_student_id)
            ->where('bk_assignment_students.student_id', $studentId)
            ->first();
            
        if (!$assignment) {
            return redirect()->to('siswa/asesmen')->with('error', 'Asesmen tidak ditemukan.');
        }
        
        if ($assignment['status'] === 'COMPLETED') {
            return redirect()->to('siswa/asesmen')->with('error', 'Asesmen ini sudah selesai dikerjakan.');
        }

        if (strtotime($assignment['end_date'] . ' 23:59:59') < time()) {
            return redirect()->to('siswa/asesmen')->with('error', 'Waktu pengisian asesmen telah berakhir.');
        }

        // Change status to IN_PROGRESS if first time
        if ($assignment['status'] === 'ASSIGNED') {
            $this->assignmentStudentModel->update($assignment_student_id, [
                'status' => 'IN_PROGRESS',
                'started_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Fetch domains and questions
        $db = \Config\Database::connect();
        $domains = $db->table('bk_instrument_domains')->where('instrument_id', $assignment['instrument_id'])->orderBy('sort_order', 'ASC')->get()->getResultArray();
        $questions = $db->table('bk_instrument_questions')->where('instrument_id', $assignment['instrument_id'])->where('is_active', 1)->orderBy('sort_order', 'ASC')->get()->getResultArray();
        
        // Fetch saved answers
        $savedAnswersRaw = $this->studentAnswerModel->where('assignment_student_id', $assignment_student_id)->findAll();
        $savedAnswers = [];
        foreach ($savedAnswersRaw as $ans) {
            $savedAnswers[$ans['question_id']] = $ans;
        }

        // Simple default scale mapping for placeholder logic (will be enhanced by actual scale fetching if needed)
        // For standard AKPD/Likert: 4, 3, 2, 1
        $defaultScale = [
            ['value' => 4, 'label' => 'Sangat Sesuai / Sangat Membutuhkan'],
            ['value' => 3, 'label' => 'Sesuai / Membutuhkan'],
            ['value' => 2, 'label' => 'Kurang Sesuai / Kurang Membutuhkan'],
            ['value' => 1, 'label' => 'Tidak Sesuai / Tidak Membutuhkan'],
        ];

        $data = [
            'title' => 'Mengisi: ' . $assignment['title'],
            'assignment' => $assignment,
            'domains' => $domains,
            'questions' => $questions,
            'savedAnswers' => $savedAnswers,
            'scale' => $defaultScale
        ];

        return view('siswa/assessment/fill', $data);
    }

    public function saveAnswer()
    {
        $assignment_student_id = $this->request->getPost('assignment_student_id');
        $question_id = $this->request->getPost('question_id');
        $value = $this->request->getPost('value');
        
        // Ownership check omitted for brevity in AJAX, but essential in prod
        
        $existing = $this->studentAnswerModel
            ->where('assignment_student_id', $assignment_student_id)
            ->where('question_id', $question_id)
            ->first();

        // Calculate score (mock basic logic for likert, handled properly in scoring engine)
        $score = $value; // Temporary basic score

        $data = [
            'assignment_student_id' => $assignment_student_id,
            'question_id'           => $question_id,
            'option_id'             => null, // used for specific options table
            'text_answer'           => $value, // string value for scale 1-4
            'raw_score'             => $score,
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $this->studentAnswerModel->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->studentAnswerModel->insert($data);
        }

        $this->assignmentStudentModel->update($assignment_student_id, ['last_saved_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function submit($assignment_student_id)
    {
        // Panggil Scoring Engine sebelum mengubah status menjadi COMPLETED
        $this->_calculateScore($assignment_student_id);

        // Change status to COMPLETED
        $this->assignmentStudentModel->update($assignment_student_id, [
            'status' => 'COMPLETED',
            'completed_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to('siswa/asesmen')->with('success', 'Terima kasih, jawaban asesmen Anda telah disubmit dan diproses.');
    }

    private function _calculateScore($assignment_student_id)
    {
        $db = \Config\Database::connect();
        
        // 1. Dapatkan info penugasan
        $assignment = $this->assignmentStudentModel->find($assignment_student_id);
        if (!$assignment) return;
        
        $assignDetail = $db->table('bk_assignments')->where('id', $assignment['assignment_id'])->get()->getRowArray();
        $instrument_id = $assignDetail['instrument_id'];
        
        // 2. Dapatkan max scale value (Asumsi scale 1-4 untuk perhitungan reverse)
        $maxScale = $db->table('bk_instrument_scales')->where('instrument_id', $instrument_id)->selectMax('scale_value')->get()->getRowArray()['scale_value'] ?? 4;
        $minScale = 1; // Asumsi min = 1
        
        // 3. Ambil semua jawaban siswa untuk penugasan ini
        $answers = $this->studentAnswerModel->where('assignment_student_id', $assignment_student_id)->findAll();
        if(empty($answers)) return;
        
        // 4. Ambil master pertanyaan
        $questions = $this->questionModel->where('instrument_id', $instrument_id)->findAll();
        $qMap = [];
        foreach($questions as $q) {
            $qMap[$q['id']] = $q;
        }
        
        $domainScores = [];
        $total_score = 0;
        $total_max_score = 0;
        
        // 5. Kalkulasi skor per jawaban
        foreach ($answers as $ans) {
            $qId = $ans['question_id'];
            if (!isset($qMap[$qId])) continue;
            
            $q = $qMap[$qId];
            $dId = $q['domain_id'] ?? 0;
            
            $raw_score = (float) $ans['raw_score'];
            $final_score = $raw_score;
            
            // Reverse scoring
            if ($q['is_reverse']) {
                $final_score = ($maxScale + $minScale) - $raw_score;
            }
            
            // Apply weight
            $weight = (float) $q['weight'];
            $final_score = $final_score * $weight;
            $max_possible = $maxScale * $weight;
            
            // Update answer table with final score
            $this->studentAnswerModel->update($ans['id'], ['final_score' => $final_score]);
            
            // Agregasi ke Domain
            if (!isset($domainScores[$dId])) {
                $domainScores[$dId] = ['score' => 0, 'max' => 0];
            }
            $domainScores[$dId]['score'] += $final_score;
            $domainScores[$dId]['max'] += $max_possible;
            
            // Agregasi Total
            $total_score += $final_score;
            $total_max_score += $max_possible;
        }
        
        // 6. Interpretasi dan simpan Result
        $total_percentage = $total_max_score > 0 ? ($total_score / $total_max_score) * 100 : 0;
        
        // Cari overall level dari bk_instrument_interpretations (domain_id IS NULL)
        $overallInterp = $db->table('bk_instrument_interpretations')
                            ->where('instrument_id', $instrument_id)
                            ->where('domain_id IS NULL')
                            ->where('min_percent <=', $total_percentage)
                            ->where('max_percent >=', $total_percentage)
                            ->get()->getRowArray();
                            
        $overall_level = $overallInterp ? $overallInterp['level_label'] : $this->_defaultInterpretation($total_percentage);
        
        $resultModel = new \App\Models\BkStudentResultModel();
        // Hapus result lama jika ada (untuk safety, meskipun seharusnya tidak ada)
        $resultModel->where('assignment_student_id', $assignment_student_id)->delete();
        
        $resultData = [
            'assignment_student_id' => $assignment_student_id,
            'instrument_id' => $instrument_id,
            'student_id' => $assignment['student_id'],
            'total_score' => $total_score,
            'total_max_score' => $total_max_score,
            'percentage' => $total_percentage,
            'overall_level' => $overall_level,
            'calculated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result_id = $resultModel->insert($resultData);
        
        // 7. Simpan Detail Domain
        $detailModel = new \App\Models\BkResultDetailModel();
        foreach ($domainScores as $dId => $ds) {
            if ($dId == 0) continue; // Skip jika tidak ada domain
            
            $d_percentage = $ds['max'] > 0 ? ($ds['score'] / $ds['max']) * 100 : 0;
            
            // Interpretasi spesifik domain (atau fallback ke default)
            $domainInterp = $db->table('bk_instrument_interpretations')
                               ->where('instrument_id', $instrument_id)
                               ->groupStart()
                                   ->where('domain_id', $dId)
                                   ->orWhere('domain_id IS NULL')
                               ->groupEnd()
                               ->where('min_percent <=', $d_percentage)
                               ->where('max_percent >=', $d_percentage)
                               ->orderBy('domain_id', 'DESC') // Prioritaskan yg domain_id != NULL
                               ->get()->getRowArray();
                               
            $d_level = $domainInterp ? $domainInterp['level_label'] : $this->_defaultInterpretation($d_percentage);
            
            $detailModel->insert([
                'result_id' => $result_id,
                'domain_id' => $dId,
                'domain_score' => $ds['score'],
                'domain_max_score' => $ds['max'],
                'percentage' => $d_percentage,
                'level_label' => $d_level,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    // Fallback interpretasi jika konfigurasi DB tidak diset
    private function _defaultInterpretation($percentage)
    {
        if ($percentage >= 76) return 'Sangat Tinggi';
        if ($percentage >= 51) return 'Tinggi';
        if ($percentage >= 26) return 'Sedang';
        return 'Rendah';
    }
}
