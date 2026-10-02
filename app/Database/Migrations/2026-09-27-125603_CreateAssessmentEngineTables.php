<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssessmentEngineTables extends Migration
{
    public function up()
    {
        // 1. bk_instruments
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'                => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'assessment_type'      => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'AKPD'],
            'academic_year_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'semester'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'target_level'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'All'],
            'purpose'              => ['type' => 'TEXT', 'null' => true],
            'default_scale_type'   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'likert'],
            'status'               => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'is_template'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'source_instrument_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'total_questions'      => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_by'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // FKs for academic_years and users could be added, but following existing patterns
        $this->forge->createTable('bk_instruments', true);

        // 2. bk_instrument_domains
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'instrument_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'domain_name'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'sub_domain'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'   => ['type' => 'TEXT', 'null' => true],
            'sort_order'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_instrument_domains', true);

        // 3. bk_instrument_scales
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'instrument_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'scale_group'   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'default'],
            'scale_value'   => ['type' => 'INT', 'constraint' => 5],
            'scale_label'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'sort_order'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_instrument_scales', true);

        // 4. bk_instrument_questions
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'instrument_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'domain_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'scale_group'   => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'default', 'null' => true],
            'question_text' => ['type' => 'TEXT'],
            'question_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'scale'],
            'is_required'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_reverse'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'weight'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 1.00],
            'sort_order'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'is_active'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('domain_id', 'bk_instrument_domains', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('bk_instrument_questions', true);

        // 5. bk_question_options
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'question_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'option_text' => ['type' => 'VARCHAR', 'constraint' => 255],
            'score_value' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'sort_order'  => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('question_id', 'bk_instrument_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_question_options', true);

        // 6. bk_instrument_interpretations
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'instrument_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'domain_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'min_percent'    => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'max_percent'    => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'level_label'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'level_color'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'recommendation' => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('domain_id', 'bk_instrument_domains', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('bk_instrument_interpretations', true);

        // 7. bk_assignments
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'instrument_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'target_type'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'class'],
            'target_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_date'       => ['type' => 'DATE'],
            'end_date'         => ['type' => 'DATE'],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'assigned_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_assignments', true);

        // 8. bk_assignment_students
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'student_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ASSIGNED'],
            'started_at'    => ['type' => 'DATETIME', 'null' => true],
            'completed_at'  => ['type' => 'DATETIME', 'null' => true],
            'last_saved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assignment_id', 'student_id']);
        $this->forge->addForeignKey('assignment_id', 'bk_assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_assignment_students', true);

        // 9. bk_student_answers
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'question_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'option_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'text_answer'           => ['type' => 'TEXT', 'null' => true],
            'selected_options'      => ['type' => 'TEXT', 'null' => true],
            'raw_score'             => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'final_score'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assignment_student_id', 'question_id']);
        $this->forge->addForeignKey('assignment_student_id', 'bk_assignment_students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'bk_instrument_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_student_answers', true);

        // 10. bk_student_results
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'instrument_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'student_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'total_score'           => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'total_max_score'       => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'percentage'            => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'overall_level'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'calculated_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['assignment_student_id']);
        $this->forge->addForeignKey('assignment_student_id', 'bk_assignment_students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('instrument_id', 'bk_instruments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_student_results', true);

        // 11. bk_result_details
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'result_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'domain_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'domain_score'     => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'domain_max_score' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'percentage'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'level_label'      => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['result_id', 'domain_id']);
        $this->forge->addForeignKey('result_id', 'bk_student_results', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('domain_id', 'bk_instrument_domains', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('bk_result_details', true);
    }

    public function down()
    {
        $this->forge->dropTable('bk_result_details', true);
        $this->forge->dropTable('bk_student_results', true);
        $this->forge->dropTable('bk_student_answers', true);
        $this->forge->dropTable('bk_assignment_students', true);
        $this->forge->dropTable('bk_assignments', true);
        $this->forge->dropTable('bk_instrument_interpretations', true);
        $this->forge->dropTable('bk_question_options', true);
        $this->forge->dropTable('bk_instrument_questions', true);
        $this->forge->dropTable('bk_instrument_scales', true);
        $this->forge->dropTable('bk_instrument_domains', true);
        $this->forge->dropTable('bk_instruments', true);
    }
}
