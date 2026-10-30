<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddModulReferenceToTeachingJournals extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('modul_id', 'teaching_journals')) {
            $fields['modul_id'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'atp_id',
            ];
        }

        if (!$this->db->fieldExists('pertemuan_ke', 'teaching_journals')) {
            $fields['pertemuan_ke'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'modul_id',
            ];
        }

        if (!$this->db->fieldExists('rencana_snapshot', 'teaching_journals')) {
            $fields['rencana_snapshot'] = [
                'type'  => 'LONGTEXT',
                'null'  => true,
                'after' => 'pertemuan_ke',
            ];
        }

        if (!$this->db->fieldExists('status_realisasi', 'teaching_journals')) {
            $fields['status_realisasi'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'terlaksana',
                'after'      => 'rencana_snapshot',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('teaching_journals', $fields);
        }

        // Add foreign key with ON DELETE SET NULL agar penghapusan Modul Ajar tidak menghapus riwayat jurnal
        try {
            $this->db->query("ALTER TABLE `teaching_journals` ADD CONSTRAINT `fk_teaching_journals_modul_id` FOREIGN KEY (`modul_id`) REFERENCES `modul_ajar`(`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        } catch (\Throwable $e) {
            log_message('warning', 'Foreign key fk_teaching_journals_modul_id could not be created: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Drop foreign key first
        try {
            $this->db->query("ALTER TABLE `teaching_journals` DROP FOREIGN KEY `fk_teaching_journals_modul_id`");
        } catch (\Throwable $e) {
            // Ignore if foreign key doesn't exist
        }

        $fieldsToDrop = [];
        $candidates = ['status_realisasi', 'rencana_snapshot', 'pertemuan_ke', 'modul_id'];
        foreach ($candidates as $col) {
            if ($this->db->fieldExists($col, 'teaching_journals')) {
                $fieldsToDrop[] = $col;
            }
        }

        if (!empty($fieldsToDrop)) {
            $this->forge->dropColumn('teaching_journals', $fieldsToDrop);
        }
    }
}

