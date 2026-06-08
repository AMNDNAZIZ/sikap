<?php namespace App\Models;

use CodeIgniter\Model;

class GradesModel extends Model
{
    protected $table = 'grades';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'subject_id', 'year_id', 'semester',
        'formative_score', 'summative_score', 'final_exam_score', 'final_exam',
        'report_score', 'created_at', 'updated_at'
    ];

    public function calculateReport($studentId, $subjectId, $yearId, $semester)
    {
        $weightModel = new \App\Models\SubjectWeightModel();
        $weights = $weightModel->where([
            'subject_id' => $subjectId,
            'year_id'    => $yearId
        ])->first();

        $formative = $this->db->table('material_scores')
            ->selectAvg('score')
            ->join('subject_materials', 'subject_materials.id = material_scores.material_id')
            ->where('student_id', $studentId)
            ->where('subject_materials.subject_id', $subjectId)
            ->where('subject_materials.year_id', $yearId)
            ->where('subject_materials.semester', $semester)
            ->get()->getRow()->score ?? 0;

        $summative = $this->db->table('summative_scores')
            ->selectAvg('score')
            ->where([
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'year_id'    => $yearId,
                'semester'   => $semester
            ])->get()->getRow()->score ?? 0;

        $finalExam = $this->db->table('final_exam_scores')
            ->selectAvg('score')
            ->where([
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'year_id'    => $yearId
            ])->get()->getRow()->score ?? 0;

        $report = (
            ($formative * ($weights['formative_weight'] ?? 60) / 100) +
            ($summative * ($weights['summative_weight'] ?? 40) / 100) +
            ($finalExam * ($weights['final_exam_weight'] ?? 0) / 100)
        );

        return [
            'formative'  => $formative,
            'summative'  => $summative,
            'final_exam' => $finalExam,
            'report'     => round($report, 2)
        ];
    }
}
