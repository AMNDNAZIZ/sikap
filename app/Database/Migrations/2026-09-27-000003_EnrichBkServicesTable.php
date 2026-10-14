<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnrichBkServicesTable extends Migration
{
    public function up()
    {
        $fields = [
            'academic_year_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'rpl_id',
            ],
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'class_id',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Rencana', 'Penjadwalan', 'Pelaksanaan', 'Evaluasi', 'Tindak Lanjut', 'Selesai', 'Batal'],
                'default'    => 'Pelaksanaan',
                'after'      => 'service_date',
            ],
            'purpose' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'topic',
            ],
            'material' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'purpose',
            ],
            'needs_complaint' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'material',
            ],
            'assessment_result' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'needs_complaint',
            ],
            'session_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'activity_summary',
            ],
            'agreement' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'session_notes',
            ],
            'follow_up' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'evaluation_notes',
            ],
        ];

        // Filter out fields that already exist
        $existingColumns = $this->db->getFieldNames('bk_services');
        $fieldsToAdd = [];

        foreach ($fields as $fieldName => $fieldConfig) {
            if (!in_array($fieldName, $existingColumns)) {
                $fieldsToAdd[$fieldName] = $fieldConfig;
            }
        }

        if (!empty($fieldsToAdd)) {
            $this->forge->addColumn('bk_services', $fieldsToAdd);
        }

        // Modify service_type column — wrap try/catch (idempotent)
        try {
            $this->db->query("ALTER TABLE bk_services MODIFY COLUMN service_type ENUM(
                'Bimbingan Klasikal',
                'Bimbingan Kelompok',
                'Konseling Individual',
                'Konseling Kelompok',
                'Konsultasi',
                'Rujukan',
                'Home Visit',
                'Orientasi',
                'Informasi',
                'Penempatan',
                'Lainnya'
            ) NOT NULL DEFAULT 'Bimbingan Klasikal'");
        } catch (\Exception $e) {
            log_message('warning', '[EnrichBkServicesTable] MODIFY service_type: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Drop added columns if needed
        $columns = [
            'academic_year_id',
            'student_id',
            'status',
            'purpose',
            'material',
            'needs_complaint',
            'assessment_result',
            'session_notes',
            'agreement',
            'follow_up'
        ];
        
        foreach ($columns as $col) {
            if ($this->db->fieldExists($col, 'bk_services')) {
                $this->forge->dropColumn('bk_services', $col);
            }
        }
    }
}
