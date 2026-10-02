<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBkTables extends Migration
{
    public function up()
    {
        // 1. bk_journals: Jurnal Konseling Siswa
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'counselor_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // Guru BK
            'counseling_type'     => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'Pribadi, Sosial, Belajar, Karir'],
            'session_date'        => ['type' => 'DATE'],
            'problem_description' => ['type' => 'TEXT'],
            'diagnosis'           => ['type' => 'TEXT', 'null' => true],
            'treatment'           => ['type' => 'TEXT', 'null' => true],
            'follow_up'           => ['type' => 'TEXT', 'null' => true],
            'is_confidential'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'comment' => '1=Secret, 0=Shared'],
            'status'              => ['type' => 'ENUM', 'constraint' => ['Open', 'Closed'], 'default' => 'Open'],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        // counselor_id relates to teachers or users, we assume teachers table
        $this->forge->createTable('bk_journals', true);

        // 2. bk_appointments: Janji Temu Konseling
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'requested_date' => ['type' => 'DATE'],
            'requested_time' => ['type' => 'TIME', 'null' => true],
            'topic'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'description'    => ['type' => 'TEXT', 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Rejected', 'Completed'], 'default' => 'Pending'],
            'counselor_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        $this->forge->createTable('bk_appointments', true);

        // 3. bk_summons: Surat Panggilan Orang Tua
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'issue_date'  => ['type' => 'DATE'],
            'summon_date' => ['type' => 'DATE'],
            'reason'      => ['type' => 'TEXT'],
            'level'       => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'SP1, SP2, SP3'],
            'status'      => ['type' => 'ENUM', 'constraint' => ['Pending', 'Attended', 'Ignored'], 'default' => 'Pending'],
            'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        $this->forge->createTable('bk_summons', true);
    }

    public function down()
    {
        $this->forge->dropTable('bk_summons', true);
        $this->forge->dropTable('bk_appointments', true);
        $this->forge->dropTable('bk_journals', true);
    }
}
