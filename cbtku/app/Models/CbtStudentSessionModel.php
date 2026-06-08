<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtStudentSessionModel extends Model
{
    protected $table = 'cbt_student_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'test_id', 'student_id', 'class_name', 'token_input',
        'started_at', 'finished_at', 'duration_used', 'status',
        'score_pg', 'score_esai', 'final_score',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;

    public function getSummary()
    {
        return $this->select("
                cbt_test_status.id AS test_id,
                cbt_question_banks.code AS test_code,
                subjects.name AS subject_name,
                cbt_exam_names.name AS exam_name,
                COUNT(cbt_student_sessions.id) AS total_sessions,
                SUM(CASE WHEN cbt_student_sessions.status='selesai' THEN 1 ELSE 0 END) AS finished_count
            ")
            ->join('cbt_test_status', 'cbt_test_status.id = cbt_student_sessions.test_id', 'left')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
            ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
            ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
            ->groupBy('cbt_test_status.id')
            ->orderBy('cbt_test_status.start_time', 'DESC')
            ->findAll();
    }
}
