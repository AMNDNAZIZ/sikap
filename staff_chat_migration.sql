-- ============================================================
-- Migration: Staff Chat Room
-- Tabel untuk menyimpan room chat internal staff
-- (Admin, Kepala Sekolah, Guru, Staf — role_id: 1,2,3,7)
--
-- Pesan disimpan di tabel chat_messages yang sudah ada,
-- dengan room_id yang merujuk ke staff_chat_rooms.id
-- ============================================================

CREATE TABLE IF NOT EXISTS `staff_chat_rooms` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT 'Ruang Diskusi Staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert satu room default (hanya dijalankan sekali)
INSERT IGNORE INTO `staff_chat_rooms` (`id`, `name`) VALUES (1, 'Ruang Diskusi Staff');
