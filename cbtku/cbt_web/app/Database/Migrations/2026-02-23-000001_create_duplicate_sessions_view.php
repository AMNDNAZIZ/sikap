<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDuplicateSessionsView extends Migration
{
    public function up()
    {
        // Create view to monitor duplicate sessions
        $sql = "
            CREATE OR REPLACE VIEW v_duplicate_sessions AS
            SELECT 
                cs.student_id,
                st.name AS student_name,
                cs.test_id,
                ts.code AS test_code,
                COUNT(*) as session_count,
                GROUP_CONCAT(cs.id ORDER BY cs.id) as session_ids,
                GROUP_CONCAT(cs.score ORDER BY cs.id) as scores,
                GROUP_CONCAT(cs.status ORDER BY cs.id) as statuses,
                MAX(cs.id) as latest_session_id,
                MIN(cs.id) as oldest_session_id
            FROM cbt_sessions cs
            LEFT JOIN students st ON st.id = cs.student_id
            LEFT JOIN cbt_test_status ts ON ts.id = cs.test_id
            GROUP BY cs.student_id, cs.test_id
            HAVING COUNT(*) > 1
        ";
        
        $this->db->query($sql);
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS v_duplicate_sessions");
    }
}
