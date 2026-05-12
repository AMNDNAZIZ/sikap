<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateKokurikulerStructure extends Migration
{
    public function up()
    {
        // 1. Rename bentuk_kegiatan to jenis_kokurikuler
        $this->forge->modifyColumn('kokurikuler_documents', [
            'bentuk_kegiatan' => [
                'name' => 'jenis_kokurikuler',
                'type' => 'ENUM',
                'constraint' => ['lintas_disiplin', '7kaih', 'lainnya'],
                'comment' => 'Jenis Kokurikuler: Lintas Disiplin Ilmu, 7 KAIH, atau Kegiatan Lainnya',
            ],
        ]);

        // 2. Add bentuk_kegiatan_konkret field (kegiatan nyata yang dilakukan)
        $this->forge->addColumn('kokurikuler_documents', [
            'bentuk_kegiatan_konkret' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'jenis_kokurikuler',
                'comment' => 'Bentuk kegiatan konkret, contoh: Membuat poster dan video edukasi digital',
            ],
        ]);

        // 3. Add sub_dimensi field to kokurikuler_rubrik
        if ($this->db->tableExists('kokurikuler_rubrik')) {
            $this->forge->addColumn('kokurikuler_rubrik', [
                'sub_dimensi' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'dimensi_profil',
                    'comment' => 'Sub dimensi profil lulusan, contoh: hubungan dengan sesama manusia',
                ],
            ]);
        }
    }

    public function down()
    {
        // Revert changes
        $this->forge->modifyColumn('kokurikuler_documents', [
            'jenis_kokurikuler' => [
                'name' => 'bentuk_kegiatan',
                'type' => 'ENUM',
                'constraint' => ['lintas_disiplin', '7kaih', 'lainnya'],
            ],
        ]);

        $this->forge->dropColumn('kokurikuler_documents', 'bentuk_kegiatan_konkret');
        
        if ($this->db->tableExists('kokurikuler_rubrik')) {
            $this->forge->dropColumn('kokurikuler_rubrik', 'sub_dimensi');
        }
    }
}
