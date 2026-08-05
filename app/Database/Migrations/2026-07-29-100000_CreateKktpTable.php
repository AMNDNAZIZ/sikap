<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKktpTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('kktp')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'class_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'subject_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'tp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'comment'    => 'Referensi ke tujuan_pembelajaran.id (opsional)',
                ],
                'tujuan_pembelajaran' => [
                    'type' => 'TEXT',
                    'comment' => 'Deskripsi Tujuan Pembelajaran',
                ],
                'kriteria_1_interval' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'default'    => '0-40%',
                ],
                'kriteria_1_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'default'    => 'Belum Tercapai',
                ],
                'kriteria_1_intervensi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'default'    => 'Remedial di seluruh bagian',
                ],
                'kriteria_2_interval' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'default'    => '41-65%',
                ],
                'kriteria_2_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'default'    => 'Mulai Tercapai',
                ],
                'kriteria_2_intervensi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'default'    => 'Remedial di bagian yang diperlukan',
                ],
                'kriteria_3_interval' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'default'    => '66-85%',
                ],
                'kriteria_3_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'default'    => 'Tercapai',
                ],
                'kriteria_3_intervensi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'default'    => 'Tidak perlu remedial',
                ],
                'kriteria_4_interval' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'default'    => '86-100%',
                ],
                'kriteria_4_label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'default'    => 'Melampaui',
                ],
                'kriteria_4_intervensi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'default'    => 'Diberikan pengayaan',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['class_id', 'subject_id']);
            $this->forge->createTable('kktp');
        }
    }

    public function down()
    {
        $this->forge->dropTable('kktp', true);
    }
}
