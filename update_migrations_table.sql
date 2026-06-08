-- ============================================================================
-- UPDATE MIGRATIONS TABLE
-- ============================================================================
-- File ini untuk update tabel migrations agar sistem tahu migrasi sudah dijalankan
-- Jalankan SETELAH import update_database_manual.sql
-- ============================================================================

-- Insert migration records (akan skip jika sudah ada)
INSERT INTO migrations (version, class, `group`, namespace, time, batch) VALUES
('2026-02-21-000001', 'App\\Database\\Migrations\\AddMustChangePasswordToUsers', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000002', 'App\\Database\\Migrations\\AddColumnsToStudents', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000003', 'App\\Database\\Migrations\\AddPhotoToTeachers', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000004', 'App\\Database\\Migrations\\CreateAnnouncementTargets', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000005', 'App\\Database\\Migrations\\CreateExamSchedules', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000006', 'App\\Database\\Migrations\\CreateAppLicense', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000007', 'App\\Database\\Migrations\\CreateSubjectScores', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-21-000008', 'App\\Database\\Migrations\\CreateCiSessions', 'default', 'App', UNIX_TIMESTAMP(), 1),
('2026-02-25-000001', 'App\\Database\\Migrations\\AddHardwareSignatureToLicense', 'default', 'App', UNIX_TIMESTAMP(), 1)
ON DUPLICATE KEY UPDATE time = UNIX_TIMESTAMP();

-- Verify
SELECT 'Migrations table updated successfully!' AS Status;
SELECT * FROM migrations ORDER BY time DESC;
