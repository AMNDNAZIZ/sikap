<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk update dari database lama ke versi terbaru
 * 
 * Perubahan yang dilakukan:
 * 1. Tambah tabel license
 * 2. Tambah kolom version di cbt_answers
 * 3. Hapus kolom tinymce_api_key dari school_profile
 * 4. Tambah kolom is_active di users
 * 5. Tambah performance indexes
 * 6. Update struktur cbt_sessions (last_activity type)
 */
class UpdateFromOldDatabase extends Migration
{
    public function up()
    {
        helper('migration');
        
        log_message('info', '[Migration] Starting update from old database...');
        
        // ========================================
        // 1. CREATE LICENSE TABLE
        // ========================================
        if (!table_exists('license')) {
            log_message('info', '[Migration] Creating license table...');
            
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'license_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                ],
                'school_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'max_students' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'hash' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                ],
            ]);
            
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('license_key');
            $this->forge->createTable('license', true);
            
            log_message('info', '[Migration] License table created successfully');
        } else {
            log_message('info', '[Migration] License table already exists, skipping');
        }
        
        // ========================================
        // 2. ADD VERSION COLUMN TO CBT_ANSWERS
        // ========================================
        if (!column_exists('cbt_answers', 'version')) {
            log_message('info', '[Migration] Adding version column to cbt_answers...');
            
            safe_add_column('cbt_answers', [
                'version' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                    'null' => false,
                    'after' => 'score'
                ]
            ]);
            
            log_message('info', '[Migration] Version column added successfully');
        } else {
            log_message('info', '[Migration] Version column already exists, skipping');
        }
        
        // ========================================
        // 3. REMOVE TINYMCE_API_KEY FROM SCHOOL_PROFILE
        // ========================================
        if (column_exists('school_profile', 'tinymce_api_key')) {
            log_message('info', '[Migration] Removing tinymce_api_key from school_profile...');
            
            safe_drop_column('school_profile', 'tinymce_api_key');
            
            log_message('info', '[Migration] tinymce_api_key removed successfully');
        } else {
            log_message('info', '[Migration] tinymce_api_key already removed, skipping');
        }
        
        // ========================================
        // 4. ADD IS_ACTIVE COLUMN TO USERS
        // ========================================
        if (!column_exists('users', 'is_active')) {
            log_message('info', '[Migration] Adding is_active column to users...');
            
            safe_add_column('users', [
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                    'null' => false,
                    'after' => 'role_id'
                ]
            ]);
            
            log_message('info', '[Migration] is_active column added successfully');
        } else {
            log_message('info', '[Migration] is_active column already exists, skipping');
        }
        
        // ========================================
        // 5. UPDATE CBT_SESSIONS STRUCTURE
        // ========================================
        
        // Check if last_activity is INT type (old structure)
        $db = \Config\Database::connect();
        $fields = $db->getFieldData('cbt_sessions');
        $lastActivityField = null;
        
        foreach ($fields as $field) {
            if ($field->name === 'last_activity') {
                $lastActivityField = $field;
                break;
            }
        }
        
        if ($lastActivityField && strtolower($lastActivityField->type) === 'int') {
            log_message('info', '[Migration] Converting last_activity from INT to VARCHAR in cbt_sessions...');
            
            // Backup data first
            $sessions = $db->table('cbt_sessions')->get()->getResultArray();
            
            // Modify column type
            safe_modify_column('cbt_sessions', [
                'last_activity' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ]
            ]);
            
            // Convert existing INT timestamps to datetime strings
            foreach ($sessions as $session) {
                if (!empty($session['last_activity']) && is_numeric($session['last_activity'])) {
                    $datetime = date('Y-m-d H:i:s', $session['last_activity']);
                    $db->table('cbt_sessions')
                        ->where('id', $session['id'])
                        ->update(['last_activity' => $datetime]);
                }
            }
            
            log_message('info', '[Migration] last_activity converted successfully');
        } else {
            log_message('info', '[Migration] last_activity already correct type, skipping');
        }
        
        // ========================================
        // 6. ADD PERFORMANCE INDEXES
        // ========================================
        log_message('info', '[Migration] Adding performance indexes...');
        
        // cbt_sessions indexes
        safe_add_key($this->forge, 'cbt_sessions', ['student_id', 'test_id'], false, false, 'idx_session_student_test');
        safe_add_key($this->forge, 'cbt_sessions', ['test_id', 'status'], false, false, 'idx_session_test_status');
        safe_add_key($this->forge, 'cbt_sessions', ['student_id', 'status'], false, false, 'idx_session_student_status');
        safe_add_key($this->forge, 'cbt_sessions', ['status', 'started_at'], false, false, 'idx_session_status_time');
        
        // cbt_answers indexes
        safe_add_key($this->forge, 'cbt_answers', ['student_id', 'test_id', 'question_id'], false, true, 'idx_answer_lookup');
        safe_add_key($this->forge, 'cbt_answers', ['test_id', 'question_id'], false, false, 'idx_answer_test_question');
        safe_add_key($this->forge, 'cbt_answers', ['student_id', 'test_id'], false, false, 'idx_answer_student_test');
        
        // cbt_cheat_logs indexes
        safe_add_key($this->forge, 'cbt_cheat_logs', ['student_id', 'test_id'], false, false, 'idx_cheat_student_test');
        safe_add_key($this->forge, 'cbt_cheat_logs', ['test_id', 'created_at'], false, false, 'idx_cheat_test_time');
        
        // cbt_questions indexes
        safe_add_key($this->forge, 'cbt_questions', ['bank_id', 'question_type'], false, false, 'idx_question_bank_type');
        
        // cbt_test_status indexes
        safe_add_key($this->forge, 'cbt_test_status', ['is_visible', 'start_time', 'end_time'], false, false, 'idx_test_visibility');
        
        // students indexes
        safe_add_key($this->forge, 'students', ['username'], false, true, 'idx_student_username');
        safe_add_key($this->forge, 'students', ['class_id', 'name'], false, false, 'idx_student_class_name');
        
        // users indexes
        safe_add_key($this->forge, 'users', ['username'], false, true, 'idx_user_username');
        safe_add_key($this->forge, 'users', ['role_id', 'is_active'], false, false, 'idx_user_role_active');
        
        log_message('info', '[Migration] Performance indexes added successfully');
        
        // ========================================
        // 7. VERIFY DATA INTEGRITY
        // ========================================
        log_message('info', '[Migration] Verifying data integrity...');
        
        // Check for orphaned records
        $orphanedAnswers = $db->query("
            SELECT COUNT(*) as count 
            FROM cbt_answers a 
            LEFT JOIN students s ON a.student_id = s.id 
            WHERE s.id IS NULL
        ")->getRow()->count;
        
        if ($orphanedAnswers > 0) {
            log_message('warning', "[Migration] Found {$orphanedAnswers} orphaned answer records");
        }
        
        // Check for invalid session statuses
        $invalidSessions = $db->query("
            SELECT COUNT(*) as count 
            FROM cbt_sessions 
            WHERE status NOT IN ('active', 'finished')
        ")->getRow()->count;
        
        if ($invalidSessions > 0) {
            log_message('warning', "[Migration] Found {$invalidSessions} sessions with invalid status");
            // Fix invalid statuses
            $db->query("UPDATE cbt_sessions SET status = 'finished' WHERE status NOT IN ('active', 'finished')");
        }
        
        log_message('info', '[Migration] Data integrity check completed');
        
        // ========================================
        // MIGRATION COMPLETE
        // ========================================
        log_message('info', '[Migration] Update from old database completed successfully!');
        
        return true;
    }

    public function down()
    {
        helper('migration');
        
        log_message('info', '[Migration] Rolling back update from old database...');
        
        // Drop performance indexes
        safe_execute_query('DROP INDEX IF EXISTS idx_user_role_active ON users');
        safe_execute_query('DROP INDEX IF EXISTS idx_user_username ON users');
        safe_execute_query('DROP INDEX IF EXISTS idx_student_class_name ON students');
        safe_execute_query('DROP INDEX IF EXISTS idx_student_username ON students');
        safe_execute_query('DROP INDEX IF EXISTS idx_test_visibility ON cbt_test_status');
        safe_execute_query('DROP INDEX IF EXISTS idx_question_bank_type ON cbt_questions');
        safe_execute_query('DROP INDEX IF EXISTS idx_cheat_test_time ON cbt_cheat_logs');
        safe_execute_query('DROP INDEX IF EXISTS idx_cheat_student_test ON cbt_cheat_logs');
        safe_execute_query('DROP INDEX IF EXISTS idx_answer_student_test ON cbt_answers');
        safe_execute_query('DROP INDEX IF EXISTS idx_answer_test_question ON cbt_answers');
        safe_execute_query('DROP INDEX IF EXISTS idx_answer_lookup ON cbt_answers');
        safe_execute_query('DROP INDEX IF EXISTS idx_session_status_time ON cbt_sessions');
        safe_execute_query('DROP INDEX IF EXISTS idx_session_student_status ON cbt_sessions');
        safe_execute_query('DROP INDEX IF EXISTS idx_session_test_status ON cbt_sessions');
        safe_execute_query('DROP INDEX IF EXISTS idx_session_student_test ON cbt_sessions');
        
        // Remove is_active from users
        safe_drop_column('users', 'is_active');
        
        // Add back tinymce_api_key to school_profile
        safe_add_column('school_profile', [
            'tinymce_api_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ]
        ]);
        
        // Remove version from cbt_answers
        safe_drop_column('cbt_answers', 'version');
        
        // Drop license table
        if (table_exists('license')) {
            $this->forge->dropTable('license', true);
        }
        
        log_message('info', '[Migration] Rollback completed');
    }
}
