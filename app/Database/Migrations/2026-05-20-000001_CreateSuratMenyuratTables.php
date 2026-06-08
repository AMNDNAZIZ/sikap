<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSuratMenyuratTables extends Migration
{
    public function up(): void
    {
        // 1. letter_number_sequences (Penomoran surat otomatis per tahun)
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'year'          => ['type' => 'SMALLINT', 'constraint' => 4, 'null' => false],
            'last_sequence' => ['type' => 'INT', 'null' => false, 'default' => 0],
            'updated_at'    => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('year');
        $this->forge->createTable('letter_number_sequences');

        // 2. outgoing_letters (Surat Keluar)
        $this->forge->addField([
            'id'                      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'qr_code_id'              => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false, 'unique' => true],
            'sequence_number'         => ['type' => 'INT', 'null' => false],
            'letter_number'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'unique' => true],
            'issued_at'               => ['type' => 'DATE', 'null' => false],
            'letter_type'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'subject'                 => ['type' => 'TEXT', 'null' => false],
            'sifat'                   => ['type' => 'ENUM', 'constraint' => ['Biasa', 'Penting', 'Rahasia', 'Segera'], 'default' => 'Biasa'],
            
            // Penerima Tunggal
            'recipient_type'          => ['type' => 'ENUM', 'constraint' => ['siswa', 'guru', 'eksternal', 'internal'], 'null' => false],
            'recipient_ref_id'        => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true], // UUID atau ID ke tabel lain
            'recipient_name'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'recipient_detail'        => ['type' => 'JSON', 'null' => true],
            
            // Penerima Multiple (Lomba)
            'is_multi_recipient'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'recipients'              => ['type' => 'JSON', 'null' => true],
            
            // Field Dinamis Per Jenis Surat
            'letter_data'             => ['type' => 'JSON', 'null' => true],
            
            // Sumber & Rujukan
            'is_external'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'external_notes'          => ['type' => 'TEXT', 'null' => true],
            'reference_incoming_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true], // Akan ditambahkan FK nanti
            
            // Storage
            'pdf_url'                 => ['type' => 'TEXT', 'null' => true],
            'pdf_path'                => ['type' => 'TEXT', 'null' => true],
            'file_size_bytes'         => ['type' => 'INT', 'null' => true],
            
            // Status
            'status'                  => ['type' => 'ENUM', 'constraint' => ['active', 'revoked', 'expired'], 'default' => 'active'],
            'revoked_at'              => ['type' => 'DATETIME', 'null' => true],
            'revoke_reason'           => ['type' => 'TEXT', 'null' => true],
            
            // Snapshot Kepala Sekolah
            'principal_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'principal_nip_snapshot'  => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            
            // Audit
            'created_by'              => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false], // Menyesuaikan ID users (UUID atau int?)
            'created_at'              => ['type' => 'DATETIME', 'null' => false],
            'updated_at'              => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('issued_at');
        $this->forge->addKey('letter_type');
        $this->forge->addKey('status');
        $this->forge->createTable('outgoing_letters');

        // 3. incoming_letters (Surat Masuk)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'letter_number'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sender_name'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'sender_agency'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'subject'          => ['type' => 'TEXT', 'null' => false],
            'received_at'      => ['type' => 'DATE', 'null' => false],
            'letter_date'      => ['type' => 'DATE', 'null' => true],
            
            // Klasifikasi
            'letter_category'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'disposition'      => ['type' => 'TEXT', 'null' => true],
            'disposition_to'   => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true], // User ID
            
            // Dokumen
            'scan_url'         => ['type' => 'TEXT', 'null' => true],
            'scan_path'        => ['type' => 'TEXT', 'null' => true],
            'file_type'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'file_size_bytes'  => ['type' => 'INT', 'null' => true],
            
            // OCR
            'ocr_processed'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ocr_confidence'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'ocr_raw_text'     => ['type' => 'TEXT', 'null' => true],
            
            // Audit
            'created_by'       => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false],
            'created_at'       => ['type' => 'DATETIME', 'null' => false],
            'updated_at'       => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('received_at');
        $this->forge->addKey('sender_name');
        $this->forge->createTable('incoming_letters');

        // Add FK to outgoing_letters -> reference_incoming_id
        // (Note: in MySQL, FK needs to reference the exact same type)
        // $this->forge->addForeignKey('reference_incoming_id', 'incoming_letters', 'id', 'SET NULL', 'SET NULL');
        // Actually, we'll let it be a loose relation or add index.

        // 4. qr_verifications (Log Verifikasi)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'qr_code_id'   => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false],
            'letter_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at'  => ['type' => 'DATETIME', 'null' => false],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'TEXT', 'null' => true],
            'result'       => ['type' => 'ENUM', 'constraint' => ['valid', 'revoked', 'not_found'], 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('qr_code_id');
        $this->forge->createTable('qr_verifications');

        // 5. audit_logs (Log Aktivitas Surat)
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => true],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'entity_type'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'entity_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'before_data'  => ['type' => 'JSON', 'null' => true],
            'after_data'   => ['type' => 'JSON', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('entity_id');
        $this->forge->createTable('letter_audit_logs'); // Ganti nama agar tidak bentrok dengan audit_logs sistem SIKAP jika ada
    }

    public function down(): void
    {
        $this->forge->dropTable('letter_audit_logs');
        $this->forge->dropTable('qr_verifications');
        $this->forge->dropTable('incoming_letters');
        $this->forge->dropTable('outgoing_letters');
        $this->forge->dropTable('letter_number_sequences');
    }
}
