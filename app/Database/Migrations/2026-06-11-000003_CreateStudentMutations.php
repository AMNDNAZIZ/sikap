<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStudentMutations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['masuk', 'keluar', 'pindah_kelas'],
                'null' => false,
            ],
            'from_school' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => true,
            ],
            'to_school' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => true,
            ],
            'from_class_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'to_class_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'mutation_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'letter_number' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'attachment' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'null' => false,
                'default' => 'pending',
            ],
            'approved_by' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('student_id');
        $this->forge->addKey('type');
        $this->forge->addKey('status');
        $this->forge->addKey('mutation_date');

        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('from_class_id', 'classes', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('to_class_id', 'classes', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('approved_by', 'users', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('student_mutations');
    }

    public function down(): void
    {
        $this->forge->dropTable('student_mutations');
    }
}
