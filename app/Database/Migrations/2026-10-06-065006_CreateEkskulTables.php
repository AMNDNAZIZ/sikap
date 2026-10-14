<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEkskulTables extends Migration
{
    public function up()
    {
        // 1. ekskul_master
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kode' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'TEXT', 'null' => true],
            'category' => ['type' => 'ENUM', 'constraint' => ['wajib', 'pilihan'], 'default' => 'pilihan'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ekskul_master');

        // 2. ekskul_pembina
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ekskul_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_external' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ekskul_id', 'ekskul_master', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ekskul_pembina');

        // 3. ekskul_members
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ekskul_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'aktif', 'keluar'], 'default' => 'aktif'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ekskul_id', 'ekskul_master', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ekskul_members');

        // 4. ekskul_schedules
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ekskul_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'day_of_week' => ['type' => 'VARCHAR', 'constraint' => 20],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ekskul_id', 'ekskul_master', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ekskul_schedules');

        // 5. ekskul_jurnal
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ekskul_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'date' => ['type' => 'DATE'],
            'materi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'pembina_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'verification_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected'], 'default' => 'pending'],
            'verified_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'verified_at' => ['type' => 'DATETIME', 'null' => true],
            'verification_notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ekskul_id', 'ekskul_master', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pembina_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ekskul_jurnal');

        // 6. ekskul_attendances (Hanya menyimpan ketidakhadiran)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'jurnal_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_type' => ['type' => 'ENUM', 'constraint' => ['siswa', 'pembina']],
            'status' => ['type' => 'ENUM', 'constraint' => ['sakit', 'izin', 'alfa']],
            'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('jurnal_id', 'ekskul_jurnal', 'id', 'CASCADE', 'CASCADE');
        // $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE'); // Removed because it's polymorphic
        $this->forge->createTable('ekskul_attendances');

        // 7. ekskul_scores
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'ekskul_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'semester' => ['type' => 'VARCHAR', 'constraint' => 10],
            'predicate' => ['type' => 'ENUM', 'constraint' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang']],
            'description' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ekskul_id', 'ekskul_master', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('academic_year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ekskul_scores');
    }

    public function down()
    {
        $this->forge->dropTable('ekskul_scores', true);
        $this->forge->dropTable('ekskul_attendances', true);
        $this->forge->dropTable('ekskul_jurnal', true);
        $this->forge->dropTable('ekskul_schedules', true);
        $this->forge->dropTable('ekskul_members', true);
        $this->forge->dropTable('ekskul_pembina', true);
        $this->forge->dropTable('ekskul_master', true);
    }
}
