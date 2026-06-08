-- ============================================================
-- Migration: Nilai Erapor
-- Menambahkan kolom erapor_score ke tabel grades
-- Kolom ini diisi oleh guru sebagai nilai rapor final (prerogratif guru)
-- Berbeda dari report_score yang dihitung otomatis sistem
-- ============================================================

-- Pastikan tabel grades punya kolom erapor_score
ALTER TABLE `grades`
  ADD COLUMN IF NOT EXISTS `erapor_score` decimal(5,2) DEFAULT NULL
    COMMENT 'Nilai erapor final yang diinput guru (prerogratif guru)'
  AFTER `report_score`;

-- Jika ADD COLUMN IF NOT EXISTS tidak didukung MariaDB versi lama, gunakan ini:
-- ALTER TABLE `grades` ADD COLUMN `erapor_score` decimal(5,2) DEFAULT NULL AFTER `report_score`;

-- Pastikan ada unique constraint agar tidak duplikat per siswa/mapel/tahun/semester
-- (jalankan hanya jika belum ada)
-- ALTER TABLE `grades` ADD UNIQUE KEY `uq_grades` (`student_id`, `subject_id`, `year_id`, `semester`);
