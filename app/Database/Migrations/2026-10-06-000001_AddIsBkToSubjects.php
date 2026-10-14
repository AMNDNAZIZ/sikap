<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom `is_bk` pada tabel `subjects`.
 *
 * Kolom ini digunakan untuk menandai mata pelajaran BP/BK
 * agar dikecualikan dari modul: Materi, Penilaian, Administrasi Guru,
 * Jurnal Mengajar, Tugas, dan Nilai — tetapi tetap muncul di Penjadwalan.
 */
class AddIsBkToSubjects extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('is_bk', 'subjects')) {
            $this->forge->addColumn('subjects', [
                'is_bk' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 0,
                    'comment'    => 'Tandai jika mapel adalah BP/BK (dikecualikan dari administrasi dan penilaian)',
                    'after'      => 'religion',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_bk', 'subjects')) {
            $this->forge->dropColumn('subjects', 'is_bk');
        }
    }
}
