<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherAttendanceSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'date' => [
                'type'    => 'DATE',
                'comment' => 'Tanggal absensi yang sudah diselesaikan admin/staf',
            ],
            'academic_year_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'recorded_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('date');
        $this->forge->createTable('teacher_attendance_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('teacher_attendance_sessions', true);
    }
}
