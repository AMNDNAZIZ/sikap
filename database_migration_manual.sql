-- ============================================
-- MIGRASI DATABASE SIAKAD
-- Dari struktur lama ke struktur terbaru
-- Tanggal: 21 Februari 2026
-- ============================================

-- PENTING: Backup database Anda sebelum menjalankan script ini!

-- ============================================
-- 1. TAMBAH KOLOM DI TABEL users
-- ============================================

-- Cek apakah kolom sudah ada sebelum menambahkan
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'must_change_password'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `users` 
     ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `related_type`,
     ADD COLUMN `password_changed_at` DATETIME NULL AFTER `must_change_password`',
    'SELECT "Kolom must_change_password sudah ada di tabel users" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 2. TAMBAH KOLOM DI TABEL students
-- ============================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'students' 
    AND COLUMN_NAME = 'class_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `students` 
     ADD COLUMN `class_id` INT(11) UNSIGNED NULL AFTER `user_id`,
     ADD COLUMN `room` VARCHAR(50) NULL AFTER `class_id`,
     ADD COLUMN `plain_password` VARCHAR(100) NULL AFTER `room`',
    'SELECT "Kolom class_id sudah ada di tabel students" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 3. TAMBAH KOLOM DI TABEL teachers
-- ============================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'teachers' 
    AND COLUMN_NAME = 'photo'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `teachers` 
     ADD COLUMN `photo` VARCHAR(255) NULL AFTER `certification_year`',
    'SELECT "Kolom photo sudah ada di tabel teachers" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- 4. BUAT TABEL announcement_targets
-- ============================================

CREATE TABLE IF NOT EXISTS `announcement_targets` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` INT(10) UNSIGNED NOT NULL,
  `target_type` ENUM('role','class','student') NOT NULL,
  `target_value` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_announcement_id` (`announcement_id`),
  CONSTRAINT `fk_announcement_targets_announcement` 
    FOREIGN KEY (`announcement_id`) 
    REFERENCES `announcements` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 5. BUAT TABEL exam_schedules
-- ============================================

CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `exam_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_class_id` (`class_id`),
  KEY `idx_exam_date` (`exam_date`),
  CONSTRAINT `fk_exam_schedules_subject` 
    FOREIGN KEY (`subject_id`) 
    REFERENCES `subjects` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_exam_schedules_class` 
    FOREIGN KEY (`class_id`) 
    REFERENCES `classes` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 6. BUAT TABEL app_license
-- ============================================

CREATE TABLE IF NOT EXISTS `app_license` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_key` VARCHAR(500) NULL,
  `domain` VARCHAR(255) NULL,
  `machine_id` VARCHAR(500) NULL,
  `status` ENUM('active','inactive','expired') NOT NULL DEFAULT 'inactive',
  `last_check` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `hash` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 7. BUAT TABEL subject_scores
-- ============================================

CREATE TABLE IF NOT EXISTS `subject_scores` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `year_id` INT(10) UNSIGNED NOT NULL,
  `semester` ENUM('ganjil','genap') NOT NULL,
  `formatif_score` DECIMAL(5,2) NULL,
  `sumatif_score` DECIMAL(5,2) NULL,
  `final_exam_score` DECIMAL(5,2) NULL,
  `report_score` DECIMAL(5,2) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_subject_id` (`subject_id`),
  KEY `idx_year_id` (`year_id`),
  CONSTRAINT `fk_subject_scores_student` 
    FOREIGN KEY (`student_id`) 
    REFERENCES `students` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_subject_scores_subject` 
    FOREIGN KEY (`subject_id`) 
    REFERENCES `subjects` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_subject_scores_year` 
    FOREIGN KEY (`year_id`) 
    REFERENCES `academic_years` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- 8. BUAT TABEL ci_sessions
-- ============================================

CREATE TABLE IF NOT EXISTS `ci_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` BLOB NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- SELESAI
-- ============================================

SELECT 'Migrasi database selesai!' AS status;
SELECT 'Silakan verifikasi struktur tabel yang telah diubah' AS message;
