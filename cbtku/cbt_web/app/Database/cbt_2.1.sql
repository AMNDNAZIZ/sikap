-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 23 Feb 2026 pada 08.30
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cbt`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) UNSIGNED NOT NULL,
  `year` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `app_license`
--

CREATE TABLE `app_license` (
  `id` int(11) UNSIGNED NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `machine_id` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','expired') NOT NULL DEFAULT 'active',
  `last_check` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `app_license`
--
DELIMITER $$
CREATE TRIGGER `prevent_license_delete` BEFORE DELETE ON `app_license` FOR EACH ROW BEGIN
                        -- Log attempt
                        INSERT INTO license_audit_log (action, license_key, timestamp)
                        VALUES ('DELETE_ATTEMPT', OLD.license_key, NOW());
                        
                        -- Prevent deletion by signaling error
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'License deletion is not allowed. Contact support.';
                    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_answers`
--

CREATE TABLE `cbt_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `test_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `answer` varchar(500) DEFAULT NULL,
  `updated_at_micro` decimal(16,6) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `is_doubtful` tinyint(1) DEFAULT 0,
  `score` float DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_cheat_logs`
--

CREATE TABLE `cbt_cheat_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_exam_names`
--

CREATE TABLE `cbt_exam_names` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_questions`
--

CREATE TABLE `cbt_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `bank_id` int(10) UNSIGNED NOT NULL,
  `question_text` longtext NOT NULL,
  `raw_text` longtext DEFAULT NULL,
  `option_a` longtext DEFAULT NULL,
  `option_b` longtext DEFAULT NULL,
  `option_c` longtext DEFAULT NULL,
  `option_d` longtext DEFAULT NULL,
  `option_e` longtext DEFAULT NULL,
  `correct_option` varchar(255) DEFAULT NULL,
  `question_type` enum('pg','esai','pg_kompleks','benar_salah') DEFAULT 'pg',
  `essay_answer` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 1.00,
  `media_image` varchar(255) DEFAULT NULL,
  `media_audio` varchar(255) DEFAULT NULL,
  `media_video` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `has_image` tinyint(1) DEFAULT 0,
  `has_audio` tinyint(1) DEFAULT 0,
  `audio_a` varchar(255) DEFAULT NULL,
  `audio_b` varchar(255) DEFAULT NULL,
  `audio_c` varchar(255) DEFAULT NULL,
  `audio_d` varchar(255) DEFAULT NULL,
  `audio_e` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_question_banks`
--

CREATE TABLE `cbt_question_banks` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `level` varchar(10) NOT NULL,
  `teacher_id` int(10) UNSIGNED DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `total_pg` int(11) DEFAULT 0,
  `total_pg_kompleks` int(11) DEFAULT 0,
  `total_esai` int(11) DEFAULT 0,
  `total_bs` int(11) DEFAULT 0,
  `option_count` tinyint(4) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `raw_text` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_sessions`
--

CREATE TABLE `cbt_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `test_id` int(10) UNSIGNED NOT NULL,
  `started_at` int(11) NOT NULL,
  `question_order` text DEFAULT NULL,
  `option_orders` longtext DEFAULT NULL,
  `last_activity` int(11) DEFAULT NULL,
  `status` enum('active','finished') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `score` decimal(5,2) DEFAULT NULL,
  `essay_score` decimal(6,2) DEFAULT 0.00,
  `total_score` decimal(6,2) DEFAULT 0.00,
  `finished_at` int(11) DEFAULT NULL,
  `extra_time` int(11) DEFAULT 0,
  `reset_token` varchar(32) NOT NULL,
  `cheat_locked` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cbt_test_status`
--

CREATE TABLE `cbt_test_status` (
  `id` int(10) UNSIGNED NOT NULL,
  `bank_id` int(10) UNSIGNED NOT NULL,
  `exam_name_id` int(10) UNSIGNED NOT NULL,
  `class_codes` varchar(255) NOT NULL COMMENT 'array/json kelas yg bisa ikut',
  `semester` enum('ganjil','genap') DEFAULT 'ganjil',
  `subject_type` enum('umum','agama') DEFAULT 'umum',
  `religion` varchar(20) DEFAULT NULL COMMENT 'agama jika subject_type = agama',
  `show_pg_count` int(11) DEFAULT 0,
  `show_pg_kompleks_count` int(11) DEFAULT 0,
  `show_bs_count` int(11) DEFAULT 0,
  `show_esai_count` int(11) DEFAULT 0,
  `bobot_pg` int(11) DEFAULT 50,
  `bobot_pg_kompleks` int(11) DEFAULT 0,
  `bobot_bs` int(11) DEFAULT 0,
  `bobot_esai` int(11) DEFAULT 50,
  `shuffle_question` enum('ya','tidak') DEFAULT 'tidak',
  `shuffle_option` enum('ya','tidak') DEFAULT 'tidak',
  `finish_button_lock` enum('0','0.25','0.5','0.75') DEFAULT '0' COMMENT 'proporsi waktu tombol selesai aktif',
  `start_time` datetime NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'dalam menit',
  `end_time` datetime NOT NULL,
  `show_token` enum('ya','tidak') DEFAULT 'ya',
  `show_score` enum('ya','tidak') DEFAULT 'tidak',
  `token` varchar(6) DEFAULT NULL,
  `anti_cheat` enum('tidak','kuat','sangat_kuat') DEFAULT 'tidak',
  `audio_limit` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'apakah ujian aktif',
  `is_paused` tinyint(1) DEFAULT 0 COMMENT 'apakah ujian sedang dijeda',
  `is_visible` tinyint(1) DEFAULT 1 COMMENT 'apakah tampil di halaman siswa',
  `created_by` int(11) UNSIGNED DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `classes`
--

CREATE TABLE `classes` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `license`
--

CREATE TABLE `license` (
  `id` int(11) UNSIGNED NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `license_audit_log`
--

CREATE TABLE `license_audit_log` (
  `id` int(11) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` int(5) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(2, 'guru'),
(3, 'siswa');

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_profile`
--

CREATE TABLE `school_profile` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `headmaster` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `students`
--

CREATE TABLE `students` (
  `id` int(11) UNSIGNED NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `username` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` enum('L','P') NOT NULL,
  `class_id` int(11) UNSIGNED NOT NULL,
  `religion` varchar(10) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plain_password` text NOT NULL,
  `room` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active_session_id` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `role_id` int(5) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` enum('teacher','student','admin') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES
(1, 'admin', '$2y$10$GVL80yaFiyO643RikPYMz.pAFwZNJEc/pPNfDgeNPQIFuK2wu/1UK', '39142a2620e0a498302114f91e7ec489', '2026-02-23 14:22:38', 'Administrator', 1, '0000-00-00 00:00:00', '2026-02-23 14:22:38', 0, ''),
(34, 'salmawijaya85@gmail.com', '$2y$10$2iwfi39okUxAUY3L5EvCMegm2Bul5yYstN2OySR8oT2Xt1M.43u6C', '3cb12ca9cc4d859c2fc8681150ed6eed', '2026-02-16 20:36:34', 'SALMA WIJAYA', 2, '2026-02-12 07:38:22', '2026-02-16 20:36:34', 1, 'teacher'),
(35, '1213', '$2y$10$DsQJXwhTs1ExzmknFmciIu/V5f/wIoYEt1Bu3QeZus5O04I0Nvl3u', NULL, NULL, 'Bambang Kusuma', 3, '2026-02-12 07:44:11', '2026-02-23 10:52:37', 1, ''),
(36, '1214', '$2y$10$0MHSYJnnU4b5BokjPdZTFO3RLbMRT1fWVWI17MH2SaCu5NnG0YF6i', NULL, NULL, 'Budi Utomo', 3, '2026-02-12 07:44:11', '2026-02-23 11:15:19', 2, ''),
(37, '1215', '$2y$10$/B99WIVOJKHZmIk5Y7FLkevoiAIogG/BZb5OUMVNI./GP36L4C37u', NULL, NULL, 'Cantika Maharani', 3, '2026-02-12 07:44:12', '2026-02-23 11:42:28', 3, ''),
(38, '1216', '$2y$10$gmZwA1XlyLTUi./DqicX8u/web3LngJr8E7p2F3CsS0wgcqy.Dvc6', NULL, NULL, 'Dewi Puspita', 3, '2026-02-12 07:44:12', '2026-02-23 11:59:47', 4, ''),
(39, '1217', '$2y$10$aPgMdX4.KL86ysiFzPxlFe4/.fy9lH0RPdHnpgEOjehEXbgR0qSEe', NULL, NULL, 'Eman Ruhiman', 3, '2026-02-12 07:44:12', '2026-02-23 12:04:14', 5, ''),
(40, '1218', '$2y$10$pQMOUER0VUGoDX5HX2loB.OEyt4TVE5RFf6mAizmWkXLJ2cIw2d7S', NULL, NULL, 'Momon Suherman', 3, '2026-02-12 07:44:12', '2026-02-23 12:14:40', 6, ''),
(41, '1219', '$2y$10$846R78BKHGnxMJ9fAJpTsua2Bsvj19gwxNMmSIStjJmjmpAoefhyq', 'a415ddd93760659745962261c0a7dc74', '2026-02-23 12:50:33', 'Nengsih', 3, '2026-02-12 07:44:12', '2026-02-23 12:50:33', 7, ''),
(42, '1220', '$2y$10$/pJrd8n4WwOoODWQH2NE5.WOoSMf1Ct0e2CJKGg0DAmpZTd8Sp4Ey', NULL, NULL, 'Siti Maemunah', 3, '2026-02-12 07:44:12', '2026-02-23 12:41:12', 8, ''),
(43, 'guru1', '$2y$10$53ey/KUDTbCehca3dpeZKOsoWt1tCWnjENyhuahU3xJHeUjf4sGCO', NULL, NULL, 'Mas Agus', 2, '2026-02-22 20:37:45', '2026-02-22 20:37:45', 2, 'teacher'),
(44, '1221', '$2y$10$U2uz73MyOcRyEAPXAT3stebIMa9hnWMkJMRTViaYPAxXvUxPUJ8Se', NULL, NULL, 'MUHAMMAD APRIANSYAH', 3, '2026-02-23 11:31:10', '2026-02-23 12:48:34', 9, 'student'),
(45, '1223', '$2y$10$QaPP/Pa.qwFj7riCuJu48.gqPJm9Wl1YpeYOjUWGXCGmk6kSjNLsy', NULL, NULL, 'Salika Fatimah Khairani', 3, '2026-02-23 11:39:15', '2026-02-23 12:49:38', 10, ''),
(46, '1224', '$2y$10$/2VQRTYWtEXBWnKrikEydONFCtYRKHUgOs7AUN2mXVIke7IvRE3hy', NULL, NULL, 'Al Zaidan Hafidz', 3, '2026-02-23 11:39:15', '2026-02-23 11:39:15', 11, ''),
(47, '1225', '$2y$10$wCVmZrfoOXAqhyUBCh/bj.QLkRxCTaOdOh3ioYsjRwL7ecfsNT5SS', NULL, NULL, 'Al Mahira', 3, '2026-02-23 11:39:15', '2026-02-23 11:39:15', 12, ''),
(48, 'iman', '$2y$10$giGjEzeDgMzASeC9wcQiLO3y3Ur5rWsaZuqweMX2JMB3hky8QpqAO', NULL, NULL, 'IMAN NUR SYUHADA', 2, '2026-02-23 11:41:54', '2026-02-23 11:58:19', 3, 'teacher'),
(49, 'admin2', '$2y$10$/4tkZOpUp7raemZr7qJbV.4ryPLiABNmJ7GymidwUaKFo2J48RhGi', NULL, NULL, 'Solehudin, S.Pd.I, MM', 1, '2026-02-23 11:45:08', '2026-02-23 11:45:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `vw_cbt_bank_stats`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_cbt_bank_stats` (
`id` int(10) unsigned
,`code` varchar(50)
,`subject_id` int(10) unsigned
,`teacher_id` int(10) unsigned
,`level` varchar(10)
,`is_active` tinyint(1)
,`option_count` tinyint(4)
,`created_at` datetime
,`updated_at` datetime
,`total_questions` bigint(21)
,`total_pg` decimal(22,0)
,`total_pg_kompleks` decimal(22,0)
,`total_bs` decimal(22,0)
,`total_esai` decimal(22,0)
,`subject_name` varchar(100)
,`teacher_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `vw_cbt_session_stats`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `vw_cbt_session_stats` (
`session_id` int(10) unsigned
,`student_id` int(10) unsigned
,`test_id` int(10) unsigned
,`status` enum('active','finished')
,`started_at` int(11)
,`finished_at` int(11)
,`extra_time` int(11)
,`question_order` text
,`score` decimal(5,2)
,`essay_score` decimal(6,2)
,`total_score` decimal(6,2)
,`student_name` varchar(100)
,`student_nis` varchar(20)
,`duration` int(11)
,`exam_name_id` int(10) unsigned
,`bank_id` int(10) unsigned
,`exam_name` varchar(100)
,`bank_code` varchar(50)
,`subject_name` varchar(100)
,`answered_count` bigint(21)
,`doubtful_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_cbt_bank_stats`
--
DROP TABLE IF EXISTS `vw_cbt_bank_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cbt_bank_stats`  AS SELECT `b`.`id` AS `id`, `b`.`code` AS `code`, `b`.`subject_id` AS `subject_id`, `b`.`teacher_id` AS `teacher_id`, `b`.`level` AS `level`, `b`.`is_active` AS `is_active`, `b`.`option_count` AS `option_count`, `b`.`created_at` AS `created_at`, `b`.`updated_at` AS `updated_at`, count(`q`.`id`) AS `total_questions`, sum(case when `q`.`question_type` = 'pg' then 1 else 0 end) AS `total_pg`, sum(case when `q`.`question_type` = 'pg_kompleks' then 1 else 0 end) AS `total_pg_kompleks`, sum(case when `q`.`question_type` = 'benar_salah' then 1 else 0 end) AS `total_bs`, sum(case when `q`.`question_type` = 'esai' then 1 else 0 end) AS `total_esai`, `s`.`name` AS `subject_name`, `t`.`name` AS `teacher_name` FROM (((`cbt_question_banks` `b` left join `cbt_questions` `q` on(`q`.`bank_id` = `b`.`id`)) left join `subjects` `s` on(`s`.`id` = `b`.`subject_id`)) left join `teachers` `t` on(`t`.`id` = `b`.`teacher_id`)) GROUP BY `b`.`id`, `b`.`code`, `b`.`subject_id`, `b`.`teacher_id`, `b`.`level`, `b`.`is_active`, `b`.`option_count`, `b`.`created_at`, `b`.`updated_at`, `s`.`name`, `t`.`name` ;

-- --------------------------------------------------------

--
-- Struktur untuk view `vw_cbt_session_stats`
--
DROP TABLE IF EXISTS `vw_cbt_session_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cbt_session_stats`  AS SELECT `s`.`id` AS `session_id`, `s`.`student_id` AS `student_id`, `s`.`test_id` AS `test_id`, `s`.`status` AS `status`, `s`.`started_at` AS `started_at`, `s`.`finished_at` AS `finished_at`, `s`.`extra_time` AS `extra_time`, `s`.`question_order` AS `question_order`, `s`.`score` AS `score`, `s`.`essay_score` AS `essay_score`, `s`.`total_score` AS `total_score`, `st`.`name` AS `student_name`, `st`.`nis` AS `student_nis`, `t`.`duration` AS `duration`, `t`.`exam_name_id` AS `exam_name_id`, `t`.`bank_id` AS `bank_id`, `e`.`name` AS `exam_name`, `b`.`code` AS `bank_code`, `sub`.`name` AS `subject_name`, count(`a`.`id`) AS `answered_count`, sum(case when `a`.`is_doubtful` = 1 then 1 else 0 end) AS `doubtful_count` FROM ((((((`cbt_sessions` `s` left join `students` `st` on(`st`.`id` = `s`.`student_id`)) left join `cbt_test_status` `t` on(`t`.`id` = `s`.`test_id`)) left join `cbt_exam_names` `e` on(`e`.`id` = `t`.`exam_name_id`)) left join `cbt_question_banks` `b` on(`b`.`id` = `t`.`bank_id`)) left join `subjects` `sub` on(`sub`.`id` = `b`.`subject_id`)) left join `cbt_answers` `a` on(`a`.`student_id` = `s`.`student_id` and `a`.`test_id` = `s`.`test_id`)) GROUP BY `s`.`id`, `s`.`student_id`, `s`.`test_id`, `s`.`status`, `s`.`started_at`, `s`.`finished_at`, `s`.`extra_time`, `s`.`question_order`, `s`.`score`, `s`.`essay_score`, `s`.`total_score`, `st`.`name`, `st`.`nis`, `t`.`duration`, `t`.`exam_name_id`, `t`.`bank_id`, `e`.`name`, `b`.`code`, `sub`.`name` ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- Indeks untuk tabel `app_license`
--
ALTER TABLE `app_license`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cbt_answers`
--
ALTER TABLE `cbt_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_answer` (`student_id`,`test_id`,`question_id`),
  ADD UNIQUE KEY `uq_student_test_question` (`student_id`,`test_id`,`question_id`),
  ADD UNIQUE KEY `idx_answer_lookup` (`student_id`,`test_id`,`question_id`),
  ADD KEY `idx_student_test_question` (`student_id`,`test_id`,`question_id`),
  ADD KEY `idx_answers_version` (`student_id`,`test_id`,`question_id`,`version`),
  ADD KEY `idx_answers_doubtful` (`student_id`,`test_id`,`is_doubtful`),
  ADD KEY `idx_answer_test_question` (`test_id`,`question_id`),
  ADD KEY `idx_answer_student_test` (`student_id`,`test_id`);

--
-- Indeks untuk tabel `cbt_cheat_logs`
--
ALTER TABLE `cbt_cheat_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_student` (`test_id`,`student_id`),
  ADD KEY `idx_cheat_student_test` (`student_id`,`test_id`),
  ADD KEY `idx_cheat_test_time` (`test_id`,`created_at`);

--
-- Indeks untuk tabel `cbt_exam_names`
--
ALTER TABLE `cbt_exam_names`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `cbt_questions`
--
ALTER TABLE `cbt_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_id` (`bank_id`),
  ADD KEY `idx_question_bank_type` (`bank_id`,`question_type`);

--
-- Indeks untuk tabel `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`) USING BTREE;

--
-- Indeks untuk tabel `cbt_sessions`
--
ALTER TABLE `cbt_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_test_idx` (`student_id`,`test_id`),
  ADD KEY `idx_student_test` (`student_id`,`test_id`),
  ADD KEY `idx_session_student_test` (`student_id`,`test_id`),
  ADD KEY `idx_session_test_status` (`test_id`,`status`),
  ADD KEY `idx_session_student_status` (`student_id`,`status`),
  ADD KEY `idx_session_status_time` (`status`,`started_at`);

--
-- Indeks untuk tabel `cbt_test_status`
--
ALTER TABLE `cbt_test_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_id` (`bank_id`),
  ADD KEY `exam_name_id` (`exam_name_id`),
  ADD KEY `idx_test_visibility` (`is_visible`,`start_time`,`end_time`);

--
-- Indeks untuk tabel `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indeks untuk tabel `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_exam_schedules_subject` (`subject_id`),
  ADD KEY `fk_exam_schedules_class` (`class_id`);

--
-- Indeks untuk tabel `license`
--
ALTER TABLE `license`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_key` (`license_key`);

--
-- Indeks untuk tabel `license_audit_log`
--
ALTER TABLE `license_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `school_profile`
--
ALTER TABLE `school_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_student_username` (`username`),
  ADD KEY `idx_student_class_name` (`class_id`,`name`);

--
-- Indeks untuk tabel `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indeks untuk tabel `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_teachers_user` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `app_license`
--
ALTER TABLE `app_license`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_answers`
--
ALTER TABLE `cbt_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_cheat_logs`
--
ALTER TABLE `cbt_cheat_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_exam_names`
--
ALTER TABLE `cbt_exam_names`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_questions`
--
ALTER TABLE `cbt_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_sessions`
--
ALTER TABLE `cbt_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `cbt_test_status`
--
ALTER TABLE `cbt_test_status`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `license`
--
ALTER TABLE `license`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `license_audit_log`
--
ALTER TABLE `license_audit_log`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `school_profile`
--
ALTER TABLE `school_profile`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cbt_questions`
--
ALTER TABLE `cbt_questions`
  ADD CONSTRAINT `cbt_questions_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `cbt_question_banks` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cbt_question_banks`
--
ALTER TABLE `cbt_question_banks`
  ADD CONSTRAINT `cbt_question_banks_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cbt_question_banks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cbt_bank_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cbt_test_status`
--
ALTER TABLE `cbt_test_status`
  ADD CONSTRAINT `cbt_test_status_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `cbt_question_banks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cbt_test_status_ibfk_2` FOREIGN KEY (`exam_name_id`) REFERENCES `cbt_exam_names` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `fk_exam_schedules_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_exam_schedules_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
