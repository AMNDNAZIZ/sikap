<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCbtSessionStatsView extends Migration
{
    public function up()
    {
        // Create view for session statistics with answer counts
        // This optimizes dashboard queries
        $sql = "
            CREATE OR REPLACE VIEW vw_cbt_session_stats AS
            SELECT 
                s.id as session_id,
                s.student_id,
                s.test_id,
                s.status,
                s.started_at,
                s.finished_at,
                s.extra_time,
                s.question_order,
                s.score,
                s.essay_score,
                s.total_score,
                st.name as student_name,
                st.nis as student_nis,
                t.duration,
                t.exam_name_id,
                t.bank_id,
                e.name as exam_name,
                b.code as bank_code,
                sub.name as subject_name,
                COUNT(a.id) as answered_count,
                SUM(CASE WHEN a.is_doubtful = 1 THEN 1 ELSE 0 END) as doubtful_count
            FROM cbt_sessions s
            LEFT JOIN students st ON st.id = s.student_id
            LEFT JOIN cbt_test_status t ON t.id = s.test_id
            LEFT JOIN cbt_exam_names e ON e.id = t.exam_name_id
            LEFT JOIN cbt_question_banks b ON b.id = t.bank_id
            LEFT JOIN subjects sub ON sub.id = b.subject_id
            LEFT JOIN cbt_answers a ON a.student_id = s.student_id AND a.test_id = s.test_id
            GROUP BY s.id, s.student_id, s.test_id, s.status, s.started_at, 
                     s.finished_at, s.extra_time, s.question_order,
                     s.score, s.essay_score, s.total_score,
                     st.name, st.nis, t.duration, t.exam_name_id, t.bank_id,
                     e.name, b.code, sub.name
        ";
        
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS vw_cbt_session_stats");
    }
}
