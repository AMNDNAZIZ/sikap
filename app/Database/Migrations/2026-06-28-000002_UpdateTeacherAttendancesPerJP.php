<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTeacherAttendancesPerJP extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Cek apakah kolom jp_ke sudah ada
        $fields = $db->getFieldNames('teacher_attendances');
        if (in_array('jp_ke', $fields)) {
            return; // Sudah dijalankan sebelumnya
        }

        // Hapus UNIQUE KEY lama jika ada
        $indexes = $db->query("SHOW INDEX FROM teacher_attendances WHERE Key_name != 'PRIMARY'")->getResultArray();
        foreach ($indexes as $idx) {
            $db->query('ALTER TABLE teacher_attendances DROP INDEX `' . $idx['Key_name'] . '`');
            break; // hapus satu saja (ada duplikat key_name per kolom)
        }

        // Tambah kolom jp_ke
        $this->forge->addColumn('teacher_attendances', [
            'jp_ke' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'schedule_id',
                'comment'    => 'Urutan JP ke-berapa dalam slot (1, 2, atau 3)',
            ],
        ]);

        // Tambah UNIQUE KEY baru (schedule_id, date, jp_ke)
        $db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY unique_jp (schedule_id, date, jp_ke)');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->query('ALTER TABLE teacher_attendances DROP INDEX unique_jp');
        $this->forge->dropColumn('teacher_attendances', 'jp_ke');
        $db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY schedule_id (schedule_id, date)');
    }
}
