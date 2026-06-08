-- ============================================================
-- Migrasi: Pisahkan pesan staff chat ke tabel tersendiri
-- Ini menghindari konflik room_id antara chat_rooms dan staff_chat_rooms
-- ============================================================

-- 1. Buat tabel pesan khusus staff chat
CREATE TABLE IF NOT EXISTS `staff_chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) NOT NULL DEFAULT '',
  `reply_to` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_room_id` (`room_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Buat tabel mention khusus staff chat
CREATE TABLE IF NOT EXISTS `staff_chat_mentions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `mentioned_user_id` int(10) UNSIGNED NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_mentioned_user` (`mentioned_user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Pindahkan pesan staff yang sudah ada (room_id=1 di chat_messages)
-- HANYA jika staff_chat_rooms.id=1 ada dan chat_rooms.id=1 juga ada (konflik)
-- Jalankan ini hanya jika diperlukan:
-- INSERT INTO staff_chat_messages SELECT * FROM chat_messages WHERE room_id = 1;
-- DELETE FROM chat_messages WHERE room_id = 1 
--   AND room_id NOT IN (SELECT id FROM chat_rooms);
