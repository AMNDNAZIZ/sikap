<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateKokurikulerDocuments extends Migration
{
    public function up()
    {
        // Add semester field
        if (!$this->db->fieldExists('semester', 'kokurikuler_documents')) {
            $fields = [
                'semester' => [
                    'type' => 'ENUM',
                    'constraint' => ['1', '2'],
                    'default' => '1',
                    'after' => 'level_kelas'
                ]
            ];
            $this->forge->addColumn('kokurikuler_documents', $fields);
        }

        // Add is_template field (untuk rencana yang dibuat admin sebagai template)
        if (!$this->db->fieldExists('is_template', 'kokurikuler_documents')) {
            $fields = [
                'is_template' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'comment' => '1 = Template dari Admin, 0 = Dokumen biasa',
                    'after' => 'status'
                ]
            ];
            $this->forge->addColumn('kokurikuler_documents', $fields);
        }

        // Add parent_id field (untuk tracking dokumen yang di-copy dari template)
        if (!$this->db->fieldExists('parent_id', 'kokurikuler_documents')) {
            $fields = [
                'parent_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'ID dokumen template yang digunakan',
                    'after' => 'is_template'
                ]
            ];
            $this->forge->addColumn('kokurikuler_documents', $fields);
        }

        // Add used_by_teacher_id field (untuk tracking wali kelas yang menggunakan template)
        if (!$this->db->fieldExists('used_by_teacher_id', 'kokurikuler_documents')) {
            $fields = [
                'used_by_teacher_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'ID teacher yang menggunakan template ini',
                    'after' => 'parent_id'
                ]
            ];
            $this->forge->addColumn('kokurikuler_documents', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('semester', 'kokurikuler_documents')) {
            $this->forge->dropColumn('kokurikuler_documents', 'semester');
        }

        if ($this->db->fieldExists('is_template', 'kokurikuler_documents')) {
            $this->forge->dropColumn('kokurikuler_documents', 'is_template');
        }

        if ($this->db->fieldExists('parent_id', 'kokurikuler_documents')) {
            $this->forge->dropColumn('kokurikuler_documents', 'parent_id');
        }

        if ($this->db->fieldExists('used_by_teacher_id', 'kokurikuler_documents')) {
            $this->forge->dropColumn('kokurikuler_documents', 'used_by_teacher_id');
        }
    }
}
