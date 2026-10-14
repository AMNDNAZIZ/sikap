<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnrichBkCasesTable extends Migration
{
    public function up()
    {
        // 1. Tambah kolom confidential_notes, related_parties, workflow_step pada bk_cases jika belum ada
        $existingColumns = $this->db->getFieldNames('bk_cases');
        $fieldsToAdd = [];

        if (!in_array('confidential_notes', $existingColumns)) {
            $fieldsToAdd['confidential_notes'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'description',
            ];
        }

        if (!in_array('related_parties', $existingColumns)) {
            $fieldsToAdd['related_parties'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'reporter_id',
            ];
        }

        if (!in_array('workflow_step', $existingColumns)) {
            $fieldsToAdd['workflow_step'] = [
                'type' => 'ENUM',
                'constraint' => [
                    'Laporan/Pengaduan',
                    'Verifikasi',
                    'Identifikasi',
                    'Asesmen',
                    'Rencana Penanganan',
                    'Intervensi',
                    'Kolaborasi',
                    'Monitoring',
                    'Evaluasi',
                    'Selesai / Rujukan'
                ],
                'default' => 'Laporan/Pengaduan',
                'after' => 'status',
            ];
        }

        if (!empty($fieldsToAdd)) {
            $this->forge->addColumn('bk_cases', $fieldsToAdd);
        }

        // 2. Modifikasi kolom status pada bk_cases — wrap try/catch (idempotent)
        try {
            $this->db->query("ALTER TABLE bk_cases MODIFY COLUMN status ENUM(
                'DRAFT',
                'REPORTED',
                'VERIFIED',
                'IN_ASSESSMENT',
                'IN_PROGRESS',
                'MONITORING',
                'REFERRED',
                'RESOLVED',
                'CLOSED'
            ) NOT NULL DEFAULT 'REPORTED'");
        } catch (\Exception $e) {
            log_message('warning', '[EnrichBkCasesTable] MODIFY status: ' . $e->getMessage());
        }

        // 3. Buat tabel bk_case_logs untuk Audit Log perubahan status & timeline
        if (!$this->db->tableExists('bk_case_logs')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'case_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'old_status' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'new_status' => ['type' => 'VARCHAR', 'constraint' => 50],
                'changed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'notes'      => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('case_id', 'bk_cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('bk_case_logs', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('bk_case_logs')) {
            $this->forge->dropTable('bk_case_logs', true);
        }

        $columns = ['confidential_notes', 'related_parties', 'workflow_step'];
        foreach ($columns as $col) {
            if ($this->db->fieldExists($col, 'bk_cases')) {
                $this->forge->dropColumn('bk_cases', $col);
            }
        }
    }
}
