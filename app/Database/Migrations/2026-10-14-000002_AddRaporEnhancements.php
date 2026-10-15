<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRaporEnhancements extends Migration
{
    public function up()
    {
        // 1. Tambah kolom principal_signature ke school_profile
        if (!$this->db->fieldExists('principal_signature', 'school_profile')) {
            $this->forge->addColumn('school_profile', [
                'principal_signature' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'principal_nip',
                ],
            ]);
        }

        // 2. Tambah kolom signature ke teachers
        if (!$this->db->fieldExists('signature', 'teachers')) {
            $this->forge->addColumn('teachers', [
                'signature' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'photo',
                ],
            ]);
        }

        // 3. Tambah kolom pengaturan cetak ke rapor_configs
        $fieldsConfig = [];
        if (!$this->db->fieldExists('ttd_layout', 'rapor_configs')) {
            $fieldsConfig['ttd_layout'] = [
                'type'       => 'ENUM',
                'constraint' => ['1_baris', '2_baris'],
                'default'    => '1_baris',
                'after'      => 'place_city',
            ];
        }
        if (!$this->db->fieldExists('show_signature', 'rapor_configs')) {
            $fieldsConfig['show_signature'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'ttd_layout',
            ];
        }
        if (!$this->db->fieldExists('show_watermark', 'rapor_configs')) {
            $fieldsConfig['show_watermark'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'show_signature',
            ];
        }

        if (!empty($fieldsConfig)) {
            $this->forge->addColumn('rapor_configs', $fieldsConfig);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('principal_signature', 'school_profile')) {
            $this->forge->dropColumn('school_profile', 'principal_signature');
        }
        if ($this->db->fieldExists('signature', 'teachers')) {
            $this->forge->dropColumn('teachers', 'signature');
        }
        if ($this->db->fieldExists('show_watermark', 'rapor_configs')) {
            $this->forge->dropColumn('rapor_configs', 'show_watermark');
        }
        if ($this->db->fieldExists('show_signature', 'rapor_configs')) {
            $this->forge->dropColumn('rapor_configs', 'show_signature');
        }
        if ($this->db->fieldExists('ttd_layout', 'rapor_configs')) {
            $this->forge->dropColumn('rapor_configs', 'ttd_layout');
        }
    }
}
