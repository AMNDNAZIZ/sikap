<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GradesModel;
use App\Models\MaterialScoresModel;
use App\Models\SubjectMaterialModel;
use App\Models\SubjectModel;
use App\Models\StudentModel;
use App\Models\SubjectWeightModel;
use App\Models\AcademicYearModel;

class Scores extends BaseController
{
    protected $gradeModel;
    protected $materialScoreModel;
    protected $materialModel;
    protected $subjectModel;
    protected $studentModel;
    protected $weightModel;
    protected $yearModel;
    protected $db;

    public function __construct()
    {
        $this->gradeModel         = new GradesModel();
        $this->materialScoreModel = new MaterialScoresModel();
        $this->materialModel      = new SubjectMaterialModel();
        $this->subjectModel       = new SubjectModel();
        $this->studentModel       = new StudentModel();
        $this->weightModel        = new SubjectWeightModel();
        $this->yearModel          = new AcademicYearModel();
        $this->db                 = db_connect();
    }

    /**
     * Daftar grades (rekap per mapel/semester)
     * filter: year_id, subject_id, semester
     */
    public function index()
    {
        $yearId    = $this->request->getGet('year_id') ?: null;
        $subjectId = $this->request->getGet('subject_id') ?: null;
        $semester  = $this->request->getGet('semester') ?: null;

        $builder = $this->gradeModel
            ->select('grades.*, students.name AS student_name, students.nisn, subjects.name AS subject_name, academic_years.year AS academic_year')
            ->join('students', 'students.id = grades.student_id', 'left')
            ->join('subjects', 'subjects.id = grades.subject_id', 'left')
            ->join('academic_years', 'academic_years.id = grades.year_id', 'left');

        if ($yearId) {
            $builder->where('grades.year_id', $yearId);
        }
        if ($subjectId) {
            $builder->where('grades.subject_id', $subjectId);
        }
        if ($semester) {
            $builder->where('grades.semester', $semester);
        }

        $grades = $builder->orderBy('students.name', 'ASC')->findAll();

        // Data untuk filter form
        $years    = $this->yearModel->orderBy('year', 'DESC')->findAll();
        $subjects = $this->subjectModel->orderBy('name')->findAll();

        return view('admin/scores/index', compact('grades','years','subjects','yearId','subjectId','semester'));
    }

    /**
     * Simpan atau update grade (rekap per siswa-per-mapel-semester)
     * Jika formative_score kosong, akan dihitung otomatis dari material_scores
     */
    public function storeGrade()
    {
        $post = $this->request->getPost();

        // Validasi minimal
        $studentId = (int) ($post['student_id'] ?? 0);
        $subjectId = (int) ($post['subject_id'] ?? 0);
        $yearId    = (int) ($post['year_id'] ?? 0);
        $semester  = $post['semester'] ?? null;

        if (!$studentId || !$subjectId || !$yearId || !$semester) {
            return redirect()->back()->with('error', 'Lengkapi field siswa, mapel, tahun, dan semester.');
        }

        // Hitung formative_score otomatis bila null
        $formative = $post['formative_score'] !== '' ? (float) $post['formative_score'] : $this->computeFormativeScore($studentId, $subjectId, $yearId, $semester);

        $summative = $post['summative_score'] !== '' ? (float) $post['summative_score'] : null;
        $finalExam = $post['final_exam'] !== '' ? (float) $post['final_exam'] : null;

        // Hitung report_score berdasarkan bobot (subject_weights) jika ada
        $report = $this->computeReportScore($subjectId, $yearId, $formative, $summative, $finalExam);

        // upsert grade
        $existing = $this->gradeModel->where([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'year_id'    => $yearId,
            'semester'   => $semester
        ])->first();

        $data = [
            'student_id'      => $studentId,
            'subject_id'      => $subjectId,
            'year_id'         => $yearId,
            'semester'        => $semester,
            'formative_score' => $formative,
            'summative_score' => $summative,
            'final_exam'      => $finalExam,
            'report_score'    => $report,
        ];

        if ($existing) {
            $this->gradeModel->update($existing['id'], $data);
        } else {
            $this->gradeModel->insert($data);
        }

        return redirect()->back()->with('success', 'Rekap nilai (grade) tersimpan.');
    }

    /**
     * Hapus grade
     */
    public function deleteGrade($id)
    {
        $this->gradeModel->delete($id);
        return redirect()->back()->with('success', 'Grade dihapus.');
    }

    /**
     * List nilai per materi (material_scores) — dengan filter material_id / subject_id / year / semester
     */
    public function materialIndex()
    {
        $materialId = $this->request->getGet('material_id') ?: null;
        $subjectId  = $this->request->getGet('subject_id') ?: null;
        $yearId     = $this->request->getGet('year_id') ?: null;
        $semester   = $this->request->getGet('semester') ?: null;

        $builder = $this->materialScoreModel
            ->select('material_scores.*, students.name as student_name, atp.lingkup_materi as material_title, atp.subject_id')
            ->join('students', 'students.id = material_scores.student_id', 'left')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = material_scores.material_id', 'left');

        if ($materialId) $builder->where('material_scores.material_id', $materialId);
        if ($subjectId)  $builder->where('atp.subject_id', $subjectId);
        // ATP as per ATP table doesnt have year_id, so we skip it or filter by student's current class year in another way
        if ($semester)   $builder->where('atp.semester', $semester);

        $scores    = $builder->orderBy('students.name')->findAll();
        
        // Custom query for materials from ATP
        $materials = $this->db->table('alur_tujuan_pembelajaran')->orderBy('lingkup_materi')->get()->getResultArray();
        $subjects  = $this->subjectModel->orderBy('name')->findAll();
        $years     = $this->yearModel->orderBy('year','DESC')->findAll();

        return view('admin/scores/materials', compact('scores','materials','subjects','years','materialId','subjectId','yearId','semester'));
    }

    /**
     * Store atau update material_score (upsert per student+material+type)
     */
    public function storeMaterialScore()
    {
        $studentId  = (int) $this->request->getPost('student_id');
        $materialId = (int) $this->request->getPost('material_id');
        $type       = $this->request->getPost('type');
        $score      = $this->request->getPost('score');

        if (!$studentId || !$materialId || !$type) {
            return redirect()->back()->with('error', 'Lengkapi siswa, materi, dan jenis penilaian.');
        }

        $existing = $this->materialScoreModel->where([
            'student_id' => $studentId,
            'material_id'=> $materialId,
            'type'       => $type
        ])->first();

        $data = [
            'student_id' => $studentId,
            'material_id'=> $materialId,
            'type'       => $type,
            'score'      => $score,
        ];

        if ($existing) {
            $this->materialScoreModel->update($existing['id'], $data);
        } else {
            $this->materialScoreModel->insert($data);
        }

        return redirect()->back()->with('success', 'Nilai materi tersimpan.');
    }

    public function editMaterialScore($id)
    {
        $score = $this->materialScoreModel->find($id);
        if (!$score) return redirect()->back()->with('error', 'Data tidak ditemukan.');

        $materials = $this->materialModel->orderBy('title')->findAll();
        $students  = $this->studentModel->orderBy('name')->findAll();

        return view('admin/scores/edit_material', compact('score','materials','students'));
    }

    public function updateMaterialScore($id)
    {
        $data = [
            'student_id' => (int) $this->request->getPost('student_id'),
            'material_id'=> (int) $this->request->getPost('material_id'),
            'type'       => $this->request->getPost('type'),
            'score'      => $this->request->getPost('score'),
        ];
        $this->materialScoreModel->update($id, $data);
        return redirect()->to(base_url('admin/scores/materials'))->with('success', 'Nilai materi diperbarui.');
    }

    public function deleteMaterialScore($id)
    {
        $this->materialScoreModel->delete($id);
        return redirect()->back()->with('success', 'Nilai materi dihapus.');
    }

    /* ------------------------
     * Helper: hitung formative_score
     * Rata-rata semua material_scores siswa untuk semua materi di mapel/year/semester
     * ------------------------ */
    protected function computeFormativeScore(int $studentId, int $subjectId, int $yearId, string $semester)
    {
        // Cari class_id siswa di tahun ajaran tersebut
        $record = $this->db->table('student_records')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId)
            ->get()->getRowArray();
        
        if (!$record) return null;

        // Ambil semua materi ATP untuk subject/class/semester
        $materials = $this->db->table('alur_tujuan_pembelajaran')->where([
            'subject_id' => $subjectId,
            'class_id'   => $record['class_id'],
            'semester'   => $semester == 'ganjil' ? 1 : ($semester == 'genap' ? 2 : $semester)
        ])->get()->getResultArray();

        if (empty($materials)) {
            return null; // tidak ada materi -> cannot compute
        }

        $materialIds = array_column($materials, 'id');

        // ambil semua material_scores untuk siswa & material terkait
        $scores = $this->materialScoreModel
            ->whereIn('material_id', $materialIds)
            ->where('student_id', $studentId)
            ->findAll();

        if (empty($scores)) return null;

        // compute average of scores (simple average). Bisa diganti weighting nanti.
        $sum = 0; $count = 0;
        foreach ($scores as $s) {
            $sum += (float) $s['score'];
            $count++;
        }

        return $count ? round($sum / $count, 2) : null;
    }

    /* ------------------------
     * Helper: hitung report_score berdasarkan subject_weights
     * - bobot diambil dari subject_weights (subject_id + year_id)
     * - jika bobot tidak ada, fallback ke 60/40 (formatif/sumatif)
     * - final_exam dimasukkan hanya bila final_exam_weight > 0 dan ada value
     * ------------------------ */
    protected function computeReportScore(int $subjectId, int $yearId, $formative, $summative, $finalExam)
    {
        $weights = $this->weightModel->where([
            'subject_id' => $subjectId,
            'year_id'    => $yearId
        ])->first();

        if ($weights) {
            $wForm = (int) $weights['formative_weight'];
            $wSum  = (int) $weights['summative_weight'];
            $wFinal= (int) $weights['final_exam_weight'];
        } else {
            $wForm = 60; $wSum = 40; $wFinal = 0;
        }

        $total = 0;
        $acc   = 0.0;

        if ($formative !== null && $wForm > 0) {
            $acc += ($formative * $wForm);
            $total += $wForm;
        }

        if ($summative !== null && $wSum > 0) {
            $acc += ($summative * $wSum);
            $total += $wSum;
        }

        if ($finalExam !== null && $wFinal > 0) {
            $acc += ($finalExam * $wFinal);
            $total += $wFinal;
        }

        if ($total === 0) return null;

        $result = $acc / $total;
        return round($result, 2);
    }
}
