<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLetterTemplatesAndEnhanceSuratKeluar extends Migration
{
    public function up(): void
    {
        // 1. Tabel letter_templates (Bank Template Surat Dinamis)
        if (!$this->db->tableExists('letter_templates')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'title'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'code'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'category'        => ['type' => 'ENUM', 'constraint' => ['siswa', 'guru', 'umum', 'undangan'], 'default' => 'siswa'],
                'header_style'    => ['type' => 'ENUM', 'constraint' => ['tengah', 'kiri_kanan'], 'default' => 'tengah'],
                'default_subject' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'body_html'       => ['type' => 'LONGTEXT', 'null' => false],
                'is_system'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_by'      => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => false],
                'updated_at'      => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('category');
            $this->forge->addKey('is_active');
            $this->forge->createTable('letter_templates', true);
        }

        // 2. Tambah kolom use_kop, classification_code, template_id pada outgoing_letters jika belum ada
        if ($this->db->tableExists('outgoing_letters')) {
            $fieldsToAdd = [];
            if (!$this->db->fieldExists('use_kop', 'outgoing_letters')) {
                $fieldsToAdd['use_kop'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'letter_data',
                ];
            }
            if (!$this->db->fieldExists('classification_code', 'outgoing_letters')) {
                $fieldsToAdd['classification_code'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'letter_type',
                ];
            }
            if (!$this->db->fieldExists('template_id', 'outgoing_letters')) {
                $fieldsToAdd['template_id'] = [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'use_kop',
                ];
            }

            if (!empty($fieldsToAdd)) {
                $this->forge->addColumn('outgoing_letters', $fieldsToAdd);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('letter_templates')) {
            $this->forge->dropTable('letter_templates', true);
        }

        if ($this->db->tableExists('outgoing_letters')) {
            $columnsToDrop = [];
            if ($this->db->fieldExists('use_kop', 'outgoing_letters')) {
                $columnsToDrop[] = 'use_kop';
            }
            if ($this->db->fieldExists('classification_code', 'outgoing_letters')) {
                $columnsToDrop[] = 'classification_code';
            }
            if ($this->db->fieldExists('template_id', 'outgoing_letters')) {
                $columnsToDrop[] = 'template_id';
            }

            if (!empty($columnsToDrop)) {
                $this->forge->dropColumn('outgoing_letters', $columnsToDrop);
            }
        }
    }
}

