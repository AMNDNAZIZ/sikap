<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdjustUniqueCpMaster extends Migration
{
    public function up()
    {
        // Add a regular index for mapel_master_id first, 
        // because unique_cp is used by the foreign key fk_cp_mapel
        $this->forge->addKey('mapel_master_id');
        $this->forge->processIndexes('cp_master');

        // Drop existing unique key
        $this->db->query('ALTER TABLE cp_master DROP INDEX unique_cp');
        
        // Add new unique key including elemen
        $this->forge->addUniqueKey(['mapel_master_id', 'fase', 'tahun', 'elemen'], 'unique_cp');
        $this->forge->processIndexes('cp_master');
    }

    public function down()
    {
        // Reverse changes
        $this->db->query('ALTER TABLE cp_master DROP INDEX unique_cp');
        $this->forge->addUniqueKey(['mapel_master_id', 'fase', 'tahun'], 'unique_cp');
        $this->forge->processIndexes('cp_master');
    }
}
