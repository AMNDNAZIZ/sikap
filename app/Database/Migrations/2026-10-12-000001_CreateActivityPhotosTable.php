<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityPhotosTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('landing_activity_photos')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'activity_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'image' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'caption' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'is_cover' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
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
            $this->forge->addKey('activity_id');
            $this->forge->createTable('landing_activity_photos', true);

            // Migrasikan foto eksisting dari landing_activities jika ada
            if ($this->db->tableExists('landing_activities')) {
                $activities = $this->db->table('landing_activities')->get()->getResultArray();
                foreach ($activities as $act) {
                    if (!empty($act['image'])) {
                        $this->db->table('landing_activity_photos')->insert([
                            'activity_id' => $act['id'],
                            'image'       => $act['image'],
                            'caption'     => $act['description'] ?? '',
                            'is_cover'    => 1,
                            'sort_order'  => 0,
                            'created_at'  => $act['created_at'] ?? date('Y-m-d H:i:s'),
                            'updated_at'  => $act['updated_at'] ?? date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('landing_activity_photos', true);
    }
}
