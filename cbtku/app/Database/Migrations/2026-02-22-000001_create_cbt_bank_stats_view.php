<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCbtBankStatsView extends Migration
{
    public function up()
    {
        // Create view for bank statistics
        // This replaces multiple COUNT queries with a single view
        $sql = "
            CREATE OR REPLACE VIEW vw_cbt_bank_stats AS
            SELECT 
                b.id,
                b.code,
                b.subject_id,
                b.teacher_id,
                b.level,
                b.is_active,
                b.option_count,
                b.created_at,
                b.updated_at,
                COUNT(q.id) as total_questions,
                SUM(CASE WHEN q.question_type = 'pg' THEN 1 ELSE 0 END) as total_pg,
                SUM(CASE WHEN q.question_type = 'pg_kompleks' THEN 1 ELSE 0 END) as total_pg_kompleks,
                SUM(CASE WHEN q.question_type = 'benar_salah' THEN 1 ELSE 0 END) as total_bs,
                SUM(CASE WHEN q.question_type = 'esai' THEN 1 ELSE 0 END) as total_esai,
                s.name as subject_name,
                t.name as teacher_name
            FROM cbt_question_banks b
            LEFT JOIN cbt_questions q ON q.bank_id = b.id
            LEFT JOIN subjects s ON s.id = b.subject_id
            LEFT JOIN teachers t ON t.id = b.teacher_id
            GROUP BY b.id, b.code, b.subject_id, b.teacher_id, 
                     b.level, b.is_active, b.option_count,
                     b.created_at, b.updated_at, s.name, t.name
        ";
        
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS vw_cbt_bank_stats");
    }
}
