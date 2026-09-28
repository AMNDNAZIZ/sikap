<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateComprehensiveBkTables extends Migration
{
    public function up()
    {
        // 1. bk_assessments: Instrumen Angket Asesmen (AKPD, DCM, AUM, dll)
        if (!$this->db->tableExists('bk_assessments')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'type'             => ['type' => 'ENUM', 'constraint' => ['AKPD', 'DCM', 'AUM', 'Custom'], 'default' => 'AKPD'],
                'target_grade'     => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'All'],
                'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'description'      => ['type' => 'TEXT', 'null' => true],
                'is_active'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('bk_assessments', true);
        }

        // 2. bk_assessment_questions: Pertanyaan Asesmen per Bidang
        if (!$this->db->tableExists('bk_assessment_questions')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'assessment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category'      => ['type' => 'ENUM', 'constraint' => ['Pribadi', 'Sosial', 'Belajar', 'Karir'], 'default' => 'Pribadi'],
                'question_text' => ['type' => 'TEXT'],
                'sort_order'    => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('assessment_id', 'bk_assessments', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_assessment_questions', true);
        }

        // 3. bk_assessment_responses: Jawaban Siswa per Asesmen
        if (!$this->db->tableExists('bk_assessment_responses')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'assessment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'student_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'answers_json'  => ['type' => 'LONGTEXT', 'null' => true],
                'score_pribadi' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                'score_sosial'  => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                'score_belajar' => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                'score_karir'   => ['type' => 'INT', 'constraint' => 5, 'default' => 0],
                'submitted_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('assessment_id', 'bk_assessments', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_assessment_responses', true);
        }

        // 4. bk_programs: Program BK (Prota & Prosem BK)
        if (!$this->db->tableExists('bk_programs')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'counselor_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'program_type'     => ['type' => 'ENUM', 'constraint' => ['Prota', 'Prosem'], 'default' => 'Prota'],
                'period_month'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'field'            => ['type' => 'ENUM', 'constraint' => ['Pribadi', 'Sosial', 'Belajar', 'Karir', 'Campuran'], 'default' => 'Pribadi'],
                'target_class_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'description'      => ['type' => 'TEXT', 'null' => true],
                'status'           => ['type' => 'ENUM', 'constraint' => ['Draft', 'Approved', 'Active', 'Completed'], 'default' => 'Active'],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('bk_programs', true);
        }

        // 5. bk_rpl: Rencana Pelaksanaan Layanan (RPL BK)
        if (!$this->db->tableExists('bk_rpl')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'program_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'title'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'service_type'     => ['type' => 'ENUM', 'constraint' => ['Bimbingan Klasikal', 'Bimbingan Kelompok', 'Konseling Individual', 'Konseling Kelompok', 'Konsultasi', 'Orientasi', 'Informasi', 'Penempatan'], 'default' => 'Bimbingan Klasikal'],
                'field'            => ['type' => 'ENUM', 'constraint' => ['Pribadi', 'Sosial', 'Belajar', 'Karir'], 'default' => 'Pribadi'],
                'target_class_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'duration_minutes' => ['type' => 'INT', 'constraint' => 5, 'default' => 45],
                'purpose'          => ['type' => 'TEXT', 'null' => true],
                'media_tools'      => ['type' => 'TEXT', 'null' => true],
                'methods'          => ['type' => 'TEXT', 'null' => true],
                'evaluation_steps' => ['type' => 'TEXT', 'null' => true],
                'created_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('bk_rpl', true);
        }

        // 6. bk_services: Realisasi Layanan BK
        if (!$this->db->tableExists('bk_services')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'rpl_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'service_type'     => ['type' => 'ENUM', 'constraint' => ['Bimbingan Klasikal', 'Bimbingan Kelompok', 'Konseling Individual', 'Konseling Kelompok', 'Konsultasi', 'Home Visit', 'Lainnya'], 'default' => 'Bimbingan Klasikal'],
                'field'            => ['type' => 'ENUM', 'constraint' => ['Pribadi', 'Sosial', 'Belajar', 'Karir'], 'default' => 'Pribadi'],
                'counselor_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'class_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'service_date'     => ['type' => 'DATE'],
                'start_time'       => ['type' => 'TIME', 'null' => true],
                'end_time'         => ['type' => 'TIME', 'null' => true],
                'topic'            => ['type' => 'VARCHAR', 'constraint' => 255],
                'activity_summary' => ['type' => 'TEXT', 'null' => true],
                'evaluation_notes' => ['type' => 'TEXT', 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('bk_services', true);
        }

        // 7. bk_service_participants: Peserta Layanan BK
        if (!$this->db->tableExists('bk_service_participants')) {
            $this->forge->addField([
                'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'service_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'student_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'attendance_status' => ['type' => 'ENUM', 'constraint' => ['Hadir', 'Izin', 'Alpa'], 'default' => 'Hadir'],
                'notes'             => ['type' => 'TEXT', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('service_id', 'bk_services', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_service_participants', true);
        }

        // 8. bk_cases: Penanganan Kasus (Pengaduan, Rujukan Internal, Kasus, Asesmen Kasus)
        if (!$this->db->tableExists('bk_cases')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'case_code'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'student_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'reporter_type'   => ['type' => 'ENUM', 'constraint' => ['Siswa', 'Guru', 'Wali Kelas', 'Orang Tua', 'Piket', 'Sistem'], 'default' => 'Wali Kelas'],
                'reporter_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'source'          => ['type' => 'ENUM', 'constraint' => ['Pengaduan', 'Rujukan Internal', 'Observasi BK', 'Laporan Orang Tua'], 'default' => 'Rujukan Internal'],
                'category'        => ['type' => 'ENUM', 'constraint' => ['Kedisiplinan', 'Bullying', 'Akademik/Belajar', 'Gadget/Game', 'Sosialisasi', 'Keluarga', 'Perilaku/Emosi', 'Lainnya'], 'default' => 'Kedisiplinan'],
                'severity'        => ['type' => 'ENUM', 'constraint' => ['Ringan', 'Sedang', 'Berat'], 'default' => 'Ringan'],
                'incident_date'   => ['type' => 'DATE', 'null' => true],
                'description'     => ['type' => 'TEXT'],
                'status'          => ['type' => 'ENUM', 'constraint' => ['Pengaduan', 'Rujukan', 'Asesmen', 'Intervensi', 'Monitoring', 'Selesai', 'Rujukan Eksternal'], 'default' => 'Rujukan'],
                'is_confidential' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'closed_at'       => ['type' => 'DATETIME', 'null' => true],
                'closed_notes'    => ['type' => 'TEXT', 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_cases', true);
        }

        // 9. bk_case_actions: Intervensi, Tindakan, Monitoring & Penutupan Kasus
        if (!$this->db->tableExists('bk_case_actions')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'case_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'action_date'  => ['type' => 'DATE'],
                'action_type'  => ['type' => 'ENUM', 'constraint' => ['Konseling Individual', 'Konseling Kelompok', 'Panggilan OT', 'Home Visit', 'Surat Peringatan', 'Pendampingan', 'Monitoring', 'Penutupan Kasus'], 'default' => 'Konseling Individual'],
                'description'  => ['type' => 'TEXT'],
                'result_notes' => ['type' => 'TEXT', 'null' => true],
                'next_step'    => ['type' => 'TEXT', 'null' => true],
                'performed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('case_id', 'bk_cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_case_actions', true);
        }

        // 10. bk_case_referrals: Rujukan Eksternal
        if (!$this->db->tableExists('bk_case_referrals')) {
            $this->forge->addField([
                'id'                     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'case_id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'student_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'referral_target'        => ['type' => 'ENUM', 'constraint' => ['Psikolog', 'Psikiater', 'Kepolisian', 'Rumah Sakit', 'Dinas Sosial', 'Pihak Profesional Lain'], 'default' => 'Psikolog'],
                'target_name'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'referral_date'          => ['type' => 'DATE'],
                'reason'                 => ['type' => 'TEXT'],
                'recommendation_received' => ['type' => 'TEXT', 'null' => true],
                'status'                 => ['type' => 'ENUM', 'constraint' => ['Proses', 'Aktif', 'Selesai'], 'default' => 'Proses'],
                'created_at'             => ['type' => 'DATETIME', 'null' => true],
                'updated_at'             => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('case_id', 'bk_cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_case_referrals', true);
        }

        // 11. bk_individual_plans: Perencanaan Individual Siswa
        if (!$this->db->tableExists('bk_individual_plans')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'student_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'strengths'        => ['type' => 'TEXT', 'null' => true],
                'growth_areas'     => ['type' => 'TEXT', 'null' => true],
                'interests'        => ['type' => 'TEXT', 'null' => true],
                'learning_style'   => ['type' => 'ENUM', 'constraint' => ['Visual', 'Auditori', 'Kinestetik', 'Campuran'], 'default' => 'Visual'],
                'career_target'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'academic_target'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'habit_target'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'progress_percent' => ['type' => 'INT', 'constraint' => 3, 'default' => 0],
                'counselor_notes'   => ['type' => 'TEXT', 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_individual_plans', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('bk_individual_plans', true);
        $this->forge->dropTable('bk_case_referrals', true);
        $this->forge->dropTable('bk_case_actions', true);
        $this->forge->dropTable('bk_cases', true);
        $this->forge->dropTable('bk_service_participants', true);
        $this->forge->dropTable('bk_services', true);
        $this->forge->dropTable('bk_rpl', true);
        $this->forge->dropTable('bk_programs', true);
        $this->forge->dropTable('bk_assessment_responses', true);
        $this->forge->dropTable('bk_assessment_questions', true);
        $this->forge->dropTable('bk_assessments', true);
    }
}
