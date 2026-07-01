<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdjustUniqueCpMaster extends Migration
{
    public function up()
    {
        // 1. Cek index mapel_master_id terlebih dahulu
        $indexMapel = $this->db->query("
            SELECT 1 FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'cp_master' 
            AND index_name = 'mapel_master_id' 
            LIMIT 1
        ")->getRow();

        if (!$indexMapel) {
            $this->forge->addKey('mapel_master_id');
            $this->forge->processIndexes('cp_master');
        }

        // 2. Cek index unique_cp
        $indexUnique = $this->db->query("
            SELECT 1 FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'cp_master' 
            AND index_name = 'unique_cp' 
            LIMIT 1
        ")->getRow();

        if ($indexUnique) {
            $this->db->query('ALTER TABLE cp_master DROP INDEX unique_cp');
        }
        
        // 3. Tambahkan unique key baru termasuk elemen
        $this->forge->addUniqueKey(['mapel_master_id', 'fase', 'tahun', 'elemen'], 'unique_cp');
        $this->forge->processIndexes('cp_master');
    }

    public function down()
    {
        // Cek index unique_cp
        $indexUnique = $this->db->query("
            SELECT 1 FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = 'cp_master' 
            AND index_name = 'unique_cp' 
            LIMIT 1
        ")->getRow();

        if ($indexUnique) {
            $this->db->query('ALTER TABLE cp_master DROP INDEX unique_cp');
        }

        $this->forge->addUniqueKey(['mapel_master_id', 'fase', 'tahun'], 'unique_cp');
        $this->forge->processIndexes('cp_master');
    }
}
