<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ploting Guru BK per kelas.
 * Digunakan untuk SMP/SMA di mana Guru BK adalah guru tersendiri
 * (bukan guru kelas seperti di SD).
 *
 * Satu guru BK bisa menangani banyak kelas.
 * Satu kelas bisa punya lebih dari satu guru BK (team BK besar).
 */
class CreateBkCounselorAssignments extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('bk_counselor_assignments')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned'  => true,
                'comment'   => 'FK → teachers.id (guru BK)',
            ],
            'class_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned'  => true,
                'comment'   => 'FK → classes.id',
            ],
            'year_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned'  => true,
                'comment'   => 'FK → academic_years.id',
            ],
            'created_by' => [
                'type'     => 'INT',
                'constraint' => 11,
                'null'      => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        // Satu guru hanya bisa di-assign sekali per kelas per tahun
        $this->forge->addUniqueKey(['teacher_id', 'class_id', 'year_id']);
        $this->forge->addKey('teacher_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('year_id');

        if ($this->db->tableExists('teachers')) {
            $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        }
        if ($this->db->tableExists('classes')) {
            $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE');
        }
        if ($this->db->tableExists('academic_years')) {
            $this->forge->addForeignKey('year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        }

        $this->forge->createTable('bk_counselor_assignments');
    }

    public function down()
    {
        if ($this->db->tableExists('bk_counselor_assignments')) {
            $this->forge->dropTable('bk_counselor_assignments');
        }
    }
}
