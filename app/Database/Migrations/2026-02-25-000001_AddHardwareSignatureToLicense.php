<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHardwareSignatureToLicense extends Migration
{
    public function up()
    {
        // Check if table exists
        if (!$this->db->tableExists('app_license')) {
            log_message('warning', 'Table app_license does not exist, skipping migration');
            return;
        }
        
        // Check if column already exists
        if ($this->db->fieldExists('hardware_signature', 'app_license')) {
            log_message('info', 'Column hardware_signature already exists in app_license');
            return;
        }
        
        $fields = [
            'hardware_signature' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
                'after' => 'machine_id'
            ]
        ];
        
        $this->forge->addColumn('app_license', $fields);
        
        log_message('info', 'Column hardware_signature added to app_license');
    }

    public function down()
    {
        // Check if table exists
        if (!$this->db->tableExists('app_license')) {
            return;
        }
        
        // Check if column exists before dropping
        if ($this->db->fieldExists('hardware_signature', 'app_license')) {
            $this->forge->dropColumn('app_license', 'hardware_signature');
            log_message('info', 'Column hardware_signature dropped from app_license');
        }
    }
}
