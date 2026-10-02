<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBkCareerTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            // Rencana setelah lulus
            'post_graduate_plan'  => ['type' => 'VARCHAR', 'constraint' => 50,  'comment' => 'Kuliah, Kerja, Wirausaha, Lainnya'],
            'university_target'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'Target Perguruan Tinggi jika kuliah'],
            'major_interest'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'Jurusan/bidang yang diminati'],
            // Minat & Bakat
            'hobby'               => ['type' => 'TEXT', 'null' => true, 'comment' => 'Hobi/aktivitas yang disukai'],
            'talent'              => ['type' => 'TEXT', 'null' => true, 'comment' => 'Kemampuan/bakat yang dimiliki'],
            'favorite_subject'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'Pelajaran favorit di sekolah'],
            // Aspirasi & Kondisi
            'dream_job'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'comment' => 'Cita-cita pekerjaan'],
            'family_income'       => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true, 'comment' => '<1jt, 1-3jt, 3-5jt, >5jt'],
            'family_support'      => ['type' => 'TINYINT', 'constraint' => 1,   'default' => 1,    'comment' => '1=Mendukung, 0=Tidak'],
            'motivation'          => ['type' => 'TEXT', 'null' => true, 'comment' => 'Motivasi dan harapan siswa'],
            // Status
            'filled_at'           => ['type' => 'DATETIME', 'null' => true],
            'counselor_note'      => ['type' => 'TEXT', 'null' => true, 'comment' => 'Catatan dari Guru BK'],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('student_id');
        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        $this->forge->createTable('bk_career_profiles', true);
    }

    public function down()
    {
        $this->forge->dropTable('bk_career_profiles', true);
    }
}
