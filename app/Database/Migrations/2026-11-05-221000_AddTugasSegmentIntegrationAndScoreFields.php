<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTugasSegmentIntegrationAndScoreFields extends Migration
{
    public function up()
    {
        // 1. Tambah material_id dan segment_id pada tabel tugas
        if ($this->db->tableExists('tugas')) {
            $fieldsToAddTugas = [];

            if (!$this->db->fieldExists('material_id', 'tugas')) {
                $fieldsToAddTugas['material_id'] = [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'subject_id',
                    'comment'    => 'ID sub materi / pertemuan (opsional untuk tugas in-segment)',
                ];
            }

            if (!$this->db->fieldExists('segment_id', 'tugas')) {
                $fieldsToAddTugas['segment_id'] = [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'material_id',
                    'comment'    => 'ID segmen pertemuan (opsional untuk tugas in-segment)',
                ];
            }

            if (!empty($fieldsToAddTugas)) {
                $this->forge->addColumn('tugas', $fieldsToAddTugas);
            }

            // Tambahkan index jika belum ada
            try {
                $this->db->query("CREATE INDEX `idx_tugas_material_segment` ON `tugas` (`material_id`, `segment_id`)");
            } catch (\Throwable $e) {
                // Index mungkin sudah ada
            }
        }

        // 2. Tambah nilai_angka pada tabel tugas_submissions
        if ($this->db->tableExists('tugas_submissions')) {
            if (!$this->db->fieldExists('nilai_angka', 'tugas_submissions')) {
                $this->forge->addColumn('tugas_submissions', [
                    'nilai_angka' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '5,2',
                        'null'       => true,
                        'after'      => 'nilai',
                        'comment'    => 'Nilai kuantitatif angka 0 - 100',
                    ],
                ]);
            }
        }

        // 3. Tambah has_task pada tabel material_segments
        if ($this->db->tableExists('material_segments')) {
            if (!$this->db->fieldExists('has_task', 'material_segments')) {
                $this->forge->addColumn('material_segments', [
                    'has_task' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 0,
                        'after'      => 'estimated_minutes',
                        'comment'    => '1 jika langkah ini mewajibkan pengumpulan tugas sebagai prasyarat lanjut',
                    ],
                ]);
            }
        }

        // 4. Pastikan material_scores.type mendukung 'penugasan'
        if ($this->db->tableExists('material_scores')) {
            try {
                $this->db->query("ALTER TABLE `material_scores` MODIFY COLUMN `type` VARCHAR(50) NOT NULL");
            } catch (\Throwable $e) {
                // Abaikan jika sudah VARCHAR(50)
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('material_segments')) {
            if ($this->db->fieldExists('has_task', 'material_segments')) {
                $this->forge->dropColumn('material_segments', 'has_task');
            }
        }

        if ($this->db->tableExists('tugas_submissions')) {
            if ($this->db->fieldExists('nilai_angka', 'tugas_submissions')) {
                $this->forge->dropColumn('tugas_submissions', 'nilai_angka');
            }
        }

        if ($this->db->tableExists('tugas')) {
            $dropFields = [];
            if ($this->db->fieldExists('segment_id', 'tugas')) {
                $dropFields[] = 'segment_id';
            }
            if ($this->db->fieldExists('material_id', 'tugas')) {
                $dropFields[] = 'material_id';
            }
            if (!empty($dropFields)) {
                $this->forge->dropColumn('tugas', $dropFields);
            }
        }
    }
}

