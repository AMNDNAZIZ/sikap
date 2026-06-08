<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAtpToSupportMultipleTps extends Migration
{
    public function up()
    {
        // 1. Update alur_tujuan_pembelajaran table
        // Add cp_master_id and lingkup_materi to alur_tujuan_pembelajaran
        $this->forge->addColumn('alur_tujuan_pembelajaran', [
            'cp_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'subject_id'
            ],
            'lingkup_materi' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'cp_master_id'
            ],
        ]);

        // 2. Update tujuan_pembelajaran table
        // Add atp_id to tujuan_pembelajaran
        $this->forge->addColumn('tujuan_pembelajaran', [
            'atp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'cp_master_id'
            ],
        ]);
        
        // 3. Migrate existing data
        $db = \Config\Database::connect();
        $atps = $db->table('alur_tujuan_pembelajaran')->get()->getResultArray();
        foreach ($atps as $atp) {
            $tp = $db->table('tujuan_pembelajaran')->where('id', $atp['tp_id'])->get()->getRowArray();
            if ($tp) {
                // Update ATP with TP's CP and Lingkup Materi
                $db->table('alur_tujuan_pembelajaran')
                   ->where('id', $atp['id'])
                   ->update([
                       'cp_master_id'   => $tp['cp_master_id'],
                       'lingkup_materi' => $tp['lingkup_materi']
                   ]);
                
                // Update TP with ATP ID
                $db->table('tujuan_pembelajaran')
                   ->where('id', $tp['id'])
                   ->update(['atp_id' => $atp['id']]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('tujuan_pembelajaran', 'atp_id');
        $this->forge->dropColumn('alur_tujuan_pembelajaran', ['cp_master_id', 'lingkup_materi']);
    }
}
