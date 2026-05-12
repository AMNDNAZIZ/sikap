<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClassIdToKokurikulerDocuments extends Migration
{
    public function up()
    {
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
        $this->db->query('ALTER TABLE kokurikuler_documents ADD CONSTRAINT fk_kokurikuler_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE kokurikuler_documents DROP FOREIGN KEY fk_kokurikuler_class');
        
        // Drop column
        $this->forge->dropColumn('kokurikuler_documents', 'class_id');
    }
}
