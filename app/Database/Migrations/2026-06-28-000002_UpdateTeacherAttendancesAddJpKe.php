<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTeacherAttendancesAddJpKe extends Migration
{
    public function up()
    {
        // Drop FK dulu sebelum drop index
        $this->db->query('ALTER TABLE teacher_attendances DROP FOREIGN KEY teacher_attendances_schedule_id_foreign');

        // Hapus UNIQUE KEY lama (schedule_id, date)
        $this->db->query('ALTER TABLE teacher_attendances DROP INDEX schedule_id_date');

        // Tambah kolom jp_ke
        $this->forge->addColumn('teacher_attendances', [
            'jp_ke' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'default'    => 1,
                'after'      => 'date',
            ],
        ]);

        // Tambah UNIQUE KEY baru: (schedule_id, date, jp_ke)
        $this->db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY schedule_date_jp (schedule_id, date, jp_ke)');

        // Tambahkan kembali FK
        $this->db->query('ALTER TABLE teacher_attendances ADD CONSTRAINT teacher_attendances_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE teacher_attendances DROP FOREIGN KEY teacher_attendances_schedule_id_foreign');
        $this->db->query('ALTER TABLE teacher_attendances DROP INDEX schedule_date_jp');
        $this->forge->dropColumn('teacher_attendances', 'jp_ke');
        $this->db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY schedule_id_date (schedule_id, date)');
        $this->db->query('ALTER TABLE teacher_attendances ADD CONSTRAINT teacher_attendances_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE ON UPDATE CASCADE');
    }
}
