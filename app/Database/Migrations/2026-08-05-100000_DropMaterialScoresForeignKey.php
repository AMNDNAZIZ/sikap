<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropMaterialScoresForeignKey extends Migration
{
    public function up()
    {
        // Hapus FK constraint yang salah — material_scores.material_id
        // seharusnya mereferensi alur_tujuan_pembelajaran, bukan subject_materials
        try {
            $db = \Config\Database::connect();
            $dbName = $db->database;

            $fkExists = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = 'material_scores'
                  AND CONSTRAINT_NAME = 'fk_ms_material'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ")->getRowArray();

            if ($fkExists) {
                $db->query('ALTER TABLE `material_scores` DROP FOREIGN KEY `fk_ms_material`');
                log_message('info', '[Migration] Dropped FK fk_ms_material from material_scores');
            }

            // Juga drop FK lain yang mungkin ada dengan nama berbeda ke subject_materials
            $otherFks = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = 'material_scores'
                  AND COLUMN_NAME = 'material_id'
                  AND REFERENCED_TABLE_NAME = 'subject_materials'
            ")->getResultArray();

            foreach ($otherFks as $fk) {
                $db->query("ALTER TABLE `material_scores` DROP FOREIGN KEY `{$fk['CONSTRAINT_NAME']}`");
                log_message('info', '[Migration] Dropped FK ' . $fk['CONSTRAINT_NAME'] . ' from material_scores');
            }

            // Ubah kolom type dari ENUM ke VARCHAR agar fleksibel (tidak terbatas tulis/lisan/projek/observasi)
            $colType = $db->query("
                SELECT DATA_TYPE FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = 'material_scores'
                  AND COLUMN_NAME = 'type'
            ")->getRowArray();

            if ($colType && strtolower($colType['DATA_TYPE']) === 'enum') {
                $db->query("ALTER TABLE `material_scores` MODIFY COLUMN `type` VARCHAR(50) NOT NULL");
                log_message('info', '[Migration] Changed material_scores.type from ENUM to VARCHAR(50)');
            }

        } catch (\Throwable $e) {
            log_message('warning', '[Migration] DropMaterialScoresForeignKey: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Tidak perlu restore FK ini karena memang tidak seharusnya ada
    }
}
