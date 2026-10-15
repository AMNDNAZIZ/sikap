<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRaporTypeToRaporConfigs extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('rapor_configs')) {
            if (!$this->db->fieldExists('rapor_type', 'rapor_configs')) {
                $this->forge->addColumn('rapor_configs', [
                    'rapor_type' => [
                        'type'       => 'ENUM',
                        'constraint' => ['semester', 'pts'],
                        'default'    => 'semester',
                        'after'      => 'semester',
                    ],
                ]);
            }

            // Perbarui unique key agar mencakup academic_year_id, semester, rapor_type
            try {
                $this->db->query("ALTER TABLE `rapor_configs` DROP INDEX `academic_year_id_semester`");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `rapor_configs` DROP INDEX `academic_year_id`");
            } catch (\Throwable $e) {}

            try {
                $this->db->query("ALTER TABLE `rapor_configs` ADD UNIQUE KEY `unique_year_sem_type` (`academic_year_id`, `semester`, `rapor_type`)");
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        if ($this->db->tableExists('rapor_configs')) {
            if ($this->db->fieldExists('rapor_type', 'rapor_configs')) {
                try {
                    $this->db->query("ALTER TABLE `rapor_configs` DROP INDEX `unique_year_sem_type`");
                } catch (\Throwable $e) {}

                $this->forge->dropColumn('rapor_configs', 'rapor_type');

                try {
                    $this->db->query("ALTER TABLE `rapor_configs` ADD UNIQUE KEY `academic_year_id_semester` (`academic_year_id`, `semester`)");
                } catch (\Throwable $e) {}
            }
        }
    }
}
