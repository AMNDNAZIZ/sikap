<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTeacherAttendancesAddJpKe extends Migration
{
    public function up()
    {
        // Cek apakah kolom jp_ke sudah ada (sudah pernah dijalankan)
        if ($this->db->fieldExists('jp_ke', 'teacher_attendances')) {
            return;
        }

        // Drop FK dulu sebelum drop index
        try {
            $this->db->query('ALTER TABLE teacher_attendances DROP FOREIGN KEY teacher_attendances_schedule_id_foreign');
        } catch (\Exception $e) {
            // FK may not exist
        }

        // Hapus UNIQUE KEY lama (schedule_id, date)
        try {
            $this->db->query('ALTER TABLE teacher_attendances DROP INDEX schedule_id_date');
        } catch (\Exception $e) {
            // Index may not exist
        }

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
        try {
            $this->db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY schedule_date_jp (schedule_id, date, jp_ke)');
        } catch (\Exception $e) {
            // Index may already exist
        }

        // Tambahkan kembali FK
        try {
            $this->db->query('ALTER TABLE teacher_attendances ADD CONSTRAINT teacher_attendances_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE ON UPDATE CASCADE');
        } catch (\Exception $e) {
            // FK may already exist
        }
    }

    public function down()
    {
        if (!$this->db->fieldExists('jp_ke', 'teacher_attendances')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE teacher_attendances DROP FOREIGN KEY teacher_attendances_schedule_id_foreign');
        } catch (\Exception $e) {
            // FK may not exist
        }
        try {
            $this->db->query('ALTER TABLE teacher_attendances DROP INDEX schedule_date_jp');
        } catch (\Exception $e) {
            // Index may not exist
        }
        $this->forge->dropColumn('teacher_attendances', 'jp_ke');
        try {
            $this->db->query('ALTER TABLE teacher_attendances ADD UNIQUE KEY schedule_id_date (schedule_id, date)');
        } catch (\Exception $e) {
            // Index may already exist
        }
        try {
            $this->db->query('ALTER TABLE teacher_attendances ADD CONSTRAINT teacher_attendances_schedule_id_foreign FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE ON UPDATE CASCADE');
        } catch (\Exception $e) {
            // FK may already exist
        }
    }
}
