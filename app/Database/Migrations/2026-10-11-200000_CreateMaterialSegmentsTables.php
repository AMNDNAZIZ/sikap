<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaterialSegmentsTables extends Migration
{
    public function up()
    {
        // 1. material_segments
        if (!$this->db->tableExists('material_segments')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'material_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'content_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '20',
                    'default'    => 'text',
                ],
                'content' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'content_html' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'video_url' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '500',
                    'null'       => true,
                ],
                'external_link' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '500',
                    'null'       => true,
                ],
                'file_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
                'estimated_minutes' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => 10,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
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
            $this->forge->addKey('material_id');
            $this->forge->addForeignKey('material_id', 'subject_materials', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('material_segments', true);
        }

        // 2. material_segment_slides
        if (!$this->db->tableExists('material_segment_slides')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'segment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'image_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                ],
                'caption' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('segment_id');
            $this->forge->addForeignKey('segment_id', 'material_segments', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('material_segment_slides', true);
        }

        // 3. material_segment_progress
        if (!$this->db->tableExists('material_segment_progress')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'student_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'material_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'segment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['in_progress', 'completed'],
                    'default'    => 'completed',
                ],
                'completed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['student_id', 'segment_id']);
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('material_id', 'subject_materials', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('segment_id', 'material_segments', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('material_segment_progress', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('material_segment_progress', true);
        $this->forge->dropTable('material_segment_slides', true);
        $this->forge->dropTable('material_segments', true);
    }
}
