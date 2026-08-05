<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeachingJournalSubjects extends Migration
{
    public function up()
    {
        // Tabel untuk menyimpan multiple mapel per jurnal (guru kelas)
        if (!$this->db->tableExists('teaching_journal_subjects')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'journal_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'subject_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'atp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('journal_id', 'teaching_journals', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('atp_id', 'alur_tujuan_pembelajaran', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('teaching_journal_subjects');
        }
    }

    public function down()
    {
        $this->forge->dropTable('teaching_journal_subjects', true);
    }
}
