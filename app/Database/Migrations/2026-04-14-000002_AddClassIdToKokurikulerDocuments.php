<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClassIdToKokurikulerDocuments extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('class_id', 'kokurikuler_documents')) {
            $this->forge->addColumn('kokurikuler_documents', [
                'class_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'level_kelas',
                    'comment' => 'ID kelas spesifik (6A, 6B, dll) - untuk wali kelas',
                ],
            ]);

            // Add foreign key
            $this->forge->processIndexes('kokurikuler_documents');

            $existingFk = $this->db->query("
                SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND CONSTRAINT_NAME = 'fk_kokurikuler_class'
                LIMIT 1
            ")->getRow();

            if (!$existingFk) {
                $this->db->query('ALTER TABLE kokurikuler_documents ADD CONSTRAINT fk_kokurikuler_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('class_id', 'kokurikuler_documents')) {
            // Drop foreign key first
            try {
                $this->db->query('ALTER TABLE kokurikuler_documents DROP FOREIGN KEY fk_kokurikuler_class');
            } catch (\Exception $e) {
                // FK may not exist
            }
            
            // Drop column
            $this->forge->dropColumn('kokurikuler_documents', 'class_id');
        }
    }
}
