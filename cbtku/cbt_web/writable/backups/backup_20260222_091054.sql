-- Database Backup
-- Generated: 2026-02-22 09:10:54
-- Database: cbt

SET FOREIGN_KEY_CHECKS=0;

-- Table: academic_years
DROP TABLE IF EXISTS `academic_years`;
CREATE TABLE `academic_years` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `year` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `year` (`year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: academic_years
INSERT INTO `academic_years` (`id`, `year`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES ('1', '2025/2026', '2025-07-01', '2026-06-30', '1', '2026-02-12 07:37:54', '2026-02-12 07:37:54');

-- Table: app_license
DROP TABLE IF EXISTS `app_license`;
CREATE TABLE `app_license` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `machine_id` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','expired') NOT NULL DEFAULT 'active',
  `last_check` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: app_license
INSERT INTO `app_license` (`id`, `license_key`, `domain`, `machine_id`, `status`, `last_check`, `expires_at`, `hash`, `created_at`, `updated_at`) VALUES ('1', 'U62A-CK90-FYU7-MEL1', 'localhost', '9268d201d94470c9fe55da243498114986ab0622b9c5d75a01f799f3c1a1096a', 'active', '2026-02-22 06:21:25', '2026-02-23 11:42:00', '9c8810dfbf52e4655852d9751687b00ee99523461817343dbfd666f8fcf45ad2', '2026-02-20 15:13:28', '2026-02-22 06:21:25');

-- Table: cbt_answers
DROP TABLE IF EXISTS `cbt_answers`;
CREATE TABLE `cbt_answers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `test_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `answer` varchar(500) DEFAULT NULL,
  `is_doubtful` tinyint(1) DEFAULT 0,
  `score` float DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_answer` (`student_id`,`test_id`,`question_id`),
  UNIQUE KEY `uq_student_test_question` (`student_id`,`test_id`,`question_id`),
  KEY `idx_student_test_question` (`student_id`,`test_id`,`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=352 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_answers
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('4', '1', '1', '1', 'A', '0', NULL, '2026-02-12 09:22:50');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('5', '1', '1', '2', 'A,B,D', '0', NULL, '2026-02-12 09:22:50');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('18', '1', '2', '7', 'A', '0', NULL, '2026-02-12 16:17:29');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('19', '1', '2', '4', 'D', '0', NULL, '2026-02-12 16:17:44');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('20', '1', '2', '6', 'C', '0', NULL, '2026-02-12 16:17:44');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('21', '1', '2', '3', 'C', '0', NULL, '2026-02-12 16:17:59');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('22', '1', '2', '5', 'B', '0', NULL, '2026-02-12 16:17:59');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('23', '1', '2', '8', 'A,B,D', '0', NULL, '2026-02-12 16:18:11');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('24', '1', '2', '9', 'B,C', '0', NULL, '2026-02-12 16:18:11');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('25', '1', '2', '10', 'Besar pasak daripada tiang', '0', '100', '2026-02-12 19:57:33');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('28', '2', '2', '3', 'C', '0', NULL, '2026-02-12 19:52:02');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('29', '2', '2', '4', 'D', '0', NULL, '2026-02-12 19:52:02');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('30', '2', '2', '6', 'C', '0', NULL, '2026-02-12 19:54:07');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('31', '2', '2', '5', 'B', '0', NULL, '2026-02-12 19:54:32');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('32', '2', '2', '7', 'D', '0', NULL, '2026-02-12 19:54:32');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('33', '2', '2', '8', 'A,C,D', '0', NULL, '2026-02-12 19:55:01');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('34', '2', '2', '9', 'B,C,D', '0', NULL, '2026-02-12 19:54:50');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('36', '2', '2', '10', 'Besar pasak daripada tiang', '0', '100', '2026-02-12 19:57:48');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('37', '2', '3', '11', 'D', '0', NULL, '2026-02-12 21:28:15');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('38', '2', '3', '12', 'C', '0', NULL, '2026-02-12 21:28:15');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('39', '3', '3', '11', 'A', '0', NULL, '2026-02-12 21:44:54');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('40', '3', '3', '12', 'C', '0', NULL, '2026-02-12 21:44:54');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('43', '4', '3', '11', 'A', '0', NULL, '2026-02-12 22:14:11');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('44', '4', '3', '12', 'C', '0', NULL, '2026-02-12 22:14:11');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('50', '5', '3', '11', 'A', '0', NULL, '2026-02-12 22:55:32');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('51', '5', '3', '12', 'C', '0', NULL, '2026-02-12 22:55:32');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('52', '1', '4', '14', 'B,S,B', '0', NULL, '2026-02-13 08:19:55');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('53', '1', '4', '13', 'B,B,S,S', '0', NULL, '2026-02-13 08:20:18');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('54', '1', '5', '19', 'B', '0', NULL, '2026-02-13 13:25:05');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('55', '1', '5', '21', 'A,B,D', '0', NULL, '2026-02-13 13:25:39');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('57', '1', '5', '20', 'B,S,B', '0', NULL, '2026-02-13 13:26:19');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('58', '2', '5', '19', 'B', '0', NULL, '2026-02-13 13:43:11');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('59', '2', '5', '21', 'B,C', '0', NULL, '2026-02-13 13:44:08');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('60', '2', '5', '20', 'B,S,S', '0', NULL, '2026-02-13 13:44:43');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('62', '3', '5', '19', 'B', '0', NULL, '2026-02-13 13:49:29');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('63', '3', '5', '21', 'B,D', '0', NULL, '2026-02-13 13:49:46');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('65', '3', '5', '20', 'B,B,S', '0', NULL, '2026-02-13 13:50:04');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('66', '5', '5', '19', 'B', '0', NULL, '2026-02-13 14:02:47');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('67', '5', '5', '21', 'B,D', '0', NULL, '2026-02-13 14:03:00');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('68', '5', '5', '20', 'B,B,S', '0', NULL, '2026-02-13 14:03:10');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('71', '7', '5', '19', 'B', '0', NULL, '2026-02-13 14:06:25');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('72', '7', '5', '20', 'B,B,S', '0', NULL, '2026-02-13 14:06:37');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('73', '7', '5', '21', 'C,D', '0', NULL, '2026-02-13 14:06:37');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('74', '4', '5', '19', 'B', '0', NULL, '2026-02-13 14:18:14');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('75', '4', '5', '21', 'B,D', '0', NULL, '2026-02-13 14:18:25');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('76', '4', '5', '20', 'B,B,S', '0', NULL, '2026-02-13 14:18:25');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('78', '6', '5', '19', 'B', '0', NULL, '2026-02-13 14:24:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('79', '6', '5', '20', 'B,B,S', '0', NULL, '2026-02-13 14:24:44');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('80', '6', '5', '21', 'B,D', '0', NULL, '2026-02-13 14:24:33');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('82', '2', '6', '22', 'B', '0', NULL, '2026-02-13 15:03:13');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('83', '2', '6', '25', 'B', '0', NULL, '2026-02-13 15:03:13');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('84', '2', '6', '24', 'C', '0', NULL, '2026-02-13 15:03:26');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('85', '2', '6', '23', 'A', '0', NULL, '2026-02-13 15:03:38');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('86', '2', '6', '26', 'D', '0', NULL, '2026-02-13 15:03:38');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('94', '1', '7', '32', 'A', '0', NULL, '2026-02-14 08:07:23');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('95', '1', '7', '33', 'D', '0', NULL, '2026-02-14 08:07:23');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('96', '1', '7', '34', 'D', '0', NULL, '2026-02-14 08:07:23');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('97', '1', '7', '35', 'A,B,C', '0', NULL, '2026-02-14 08:07:36');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('98', '1', '7', '36', 'B,S,B,S', '0', NULL, '2026-02-14 08:07:49');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('103', '3', '7', '32', 'A', '0', NULL, '2026-02-14 15:03:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('104', '3', '7', '33', 'C', '0', NULL, '2026-02-14 15:03:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('105', '3', '7', '34', 'D', '0', NULL, '2026-02-14 15:03:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('106', '3', '7', '35', 'B,C', '0', NULL, '2026-02-14 15:03:31');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('107', '3', '7', '36', 'B,S,B,S', '0', NULL, '2026-02-14 15:03:31');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('108', '2', '7', '32', 'A', '0', NULL, '2026-02-14 15:40:24');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('109', '2', '7', '34', 'D', '0', NULL, '2026-02-14 15:40:24');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('110', '2', '7', '33', 'B', '0', NULL, '2026-02-14 15:40:37');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('111', '2', '7', '35', 'A,B,D', '0', NULL, '2026-02-14 15:40:37');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('112', '2', '7', '36', 'S,S,B,S', '0', NULL, '2026-02-14 15:40:47');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('115', '6', '7', '32', 'A', '0', NULL, '2026-02-14 18:14:30');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('116', '6', '7', '33', 'C', '0', NULL, '2026-02-14 18:14:30');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('117', '6', '7', '34', 'D', '0', NULL, '2026-02-14 18:14:41');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('118', '6', '7', '35', 'A,B,C', '0', NULL, '2026-02-14 18:14:41');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('119', '6', '7', '36', 'B,B,S,S', '0', NULL, '2026-02-14 18:14:52');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('120', '7', '7', '32', 'B', '0', NULL, '2026-02-14 18:28:20');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('121', '7', '7', '33', 'D', '0', NULL, '2026-02-14 18:28:20');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('122', '7', '7', '34', 'D', '0', NULL, '2026-02-14 18:28:20');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('123', '7', '7', '35', 'A,C,D', '0', NULL, '2026-02-14 18:28:32');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('124', '7', '7', '36', 'B,S,S,S', '0', NULL, '2026-02-14 18:28:42');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('126', '4', '7', '33', 'D', '0', NULL, '2026-02-14 18:47:27');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('127', '4', '7', '34', 'D', '0', NULL, '2026-02-14 18:47:27');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('128', '4', '7', '32', 'A', '0', NULL, '2026-02-14 18:47:38');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('129', '4', '7', '35', 'A,B,C', '0', NULL, '2026-02-14 18:47:58');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('130', '4', '7', '36', 'B,S,B,S', '0', NULL, '2026-02-14 18:47:58');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('132', '8', '7', '32', 'A', '0', NULL, '2026-02-14 19:10:27');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('133', '8', '7', '33', 'D', '0', NULL, '2026-02-14 19:10:27');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('134', '8', '7', '34', 'D', '0', NULL, '2026-02-14 19:10:38');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('135', '8', '7', '35', 'A,B,C', '0', NULL, '2026-02-14 19:10:38');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('136', '8', '7', '36', 'B,S,B,S', '0', NULL, '2026-02-14 19:10:48');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('137', '5', '7', '32', 'A', '0', NULL, '2026-02-14 19:24:14');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('138', '5', '7', '33', 'D', '0', NULL, '2026-02-14 19:24:14');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('139', '5', '7', '34', 'D', '0', NULL, '2026-02-14 19:24:14');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('140', '5', '7', '35', 'A,B,C', '0', NULL, '2026-02-14 19:24:24');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('141', '5', '7', '36', 'B,S,B,S', '0', NULL, '2026-02-14 19:24:24');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('285', '1', '9', '42', 'C', '0', NULL, '2026-02-15 16:54:15');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('286', '1', '9', '44', 'D', '0', NULL, '2026-02-15 16:54:22');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('287', '1', '9', '43', 'B', '0', NULL, '2026-02-15 16:54:25');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('288', '1', '9', '46', 'A,B,C', '0', NULL, '2026-02-15 16:54:28');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('291', '1', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 16:54:37');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('307', '2', '9', '42', 'C', '0', NULL, '2026-02-15 17:17:48');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('308', '2', '9', '43', 'B', '0', NULL, '2026-02-15 17:17:52');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('309', '2', '9', '44', 'D', '0', NULL, '2026-02-15 17:18:00');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('310', '2', '9', '46', 'C,B,A', '0', NULL, '2026-02-15 17:18:03');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('313', '2', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 17:18:10');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('316', '3', '9', '44', 'D', '0', NULL, '2026-02-15 17:19:55');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('317', '3', '9', '42', 'C', '0', NULL, '2026-02-15 17:20:03');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('318', '3', '9', '43', 'B', '0', NULL, '2026-02-15 17:20:08');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('319', '3', '9', '46', 'A,C,B', '0', NULL, '2026-02-15 17:20:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('322', '3', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 17:20:34');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('325', '4', '9', '42', 'C', '0', NULL, '2026-02-15 17:41:54');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('326', '4', '9', '43', 'B', '0', NULL, '2026-02-15 17:41:58');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('327', '4', '9', '44', 'D', '0', NULL, '2026-02-15 17:42:01');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('328', '4', '9', '46', 'C,A,B', '0', NULL, '2026-02-15 17:42:04');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('331', '4', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 17:42:17');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('334', '5', '9', '43', 'B', '0', NULL, '2026-02-15 18:25:16');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('335', '5', '9', '42', 'C', '0', NULL, '2026-02-15 18:25:19');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('336', '5', '9', '44', 'D', '0', NULL, '2026-02-15 18:25:22');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('337', '5', '9', '46', 'A,B,C', '0', NULL, '2026-02-15 18:25:26');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('340', '5', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 18:25:30');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('343', '6', '9', '42', 'C', '0', NULL, '2026-02-15 18:33:58');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('344', '6', '9', '43', 'B', '0', NULL, '2026-02-15 18:34:01');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('345', '6', '9', '44', 'D', '0', NULL, '2026-02-15 18:34:05');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('346', '6', '9', '46', 'C,B,A', '0', NULL, '2026-02-15 18:34:09');
INSERT INTO `cbt_answers` (`id`, `student_id`, `test_id`, `question_id`, `answer`, `is_doubtful`, `score`, `updated_at`) VALUES ('349', '6', '9', '45', 'S,B,B,,', '0', NULL, '2026-02-15 18:34:14');

-- Table: cbt_cheat_logs
DROP TABLE IF EXISTS `cbt_cheat_logs`;
CREATE TABLE `cbt_cheat_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `event` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_test_student` (`test_id`,`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_cheat_logs
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('1', '7', '7', 'cheat_fullscreen_exit: 1/3', '2026-02-14 18:28:35');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('2', '7', '7', 'cheat_blur: 2/3', '2026-02-14 18:28:44');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('3', '7', '7', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 18:29:00');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('4', '7', '7', 'cheat_fullscreen_exit: 4/3 [LIMIT EXCEEDED]', '2026-02-14 18:31:03');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('5', '4', '7', 'cheat_blur: 1/3', '2026-02-14 18:46:40');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('6', '4', '7', 'cheat_blur: 2/3', '2026-02-14 18:48:10');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('7', '4', '7', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 18:48:17');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('8', '8', '7', 'cheat_blur: 1/3', '2026-02-14 19:10:43');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('9', '8', '7', 'cheat_blur: 2/3', '2026-02-14 19:11:07');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('10', '8', '7', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 19:11:27');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('11', '5', '7', 'cheat_blur: 1/3', '2026-02-14 19:24:24');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('12', '5', '7', 'cheat_blur: 2/3', '2026-02-14 19:24:51');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('13', '5', '7', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 19:25:12');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('14', '5', '8', 'cheat_blur: 1/3', '2026-02-14 20:36:30');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('15', '5', '8', 'cheat_hidden: 2/3', '2026-02-14 20:36:42');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('16', '1', '8', 'cheat_fullscreen_exit: 1/3', '2026-02-14 21:34:23');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('17', '1', '8', 'cheat_fullscreen_exit: 2/3', '2026-02-14 21:36:54');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('18', '1', '8', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 21:37:01');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('19', '1', '8', 'cheat_fullscreen_exit: 1/3', '2026-02-14 21:50:54');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('20', '1', '8', 'cheat_blur: 2/3', '2026-02-14 21:51:16');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('21', '1', '8', 'cheat_fullscreen_exit: 3/3 [LIMIT EXCEEDED]', '2026-02-14 21:51:55');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('22', '1', '8', 'cheat_hidden: 1/3', '2026-02-14 21:57:36');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('23', '1', '8', 'cheat_fullscreen_exit: 2/3', '2026-02-14 21:57:44');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('24', '1', '8', 'cheat_blur: 3/3 [LIMIT EXCEEDED]', '2026-02-14 21:57:47');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('25', '1', '9', '[VIOLATION] Exit Fullscreen (1/10)', '2026-02-15 16:19:19');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('26', '1', '9', '[VIOLATION] Exit Fullscreen (2/10)', '2026-02-15 16:20:24');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('27', '1', '9', '[VIOLATION] Exit Fullscreen (3/10)', '2026-02-15 16:20:30');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('28', '1', '9', '[VIOLATION] Exit Fullscreen (4/10)', '2026-02-15 16:20:34');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('29', '1', '9', '[VIOLATION] Exit Fullscreen (5/10)', '2026-02-15 16:23:33');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('30', '1', '9', '[VIOLATION] Exit Fullscreen (6/10)', '2026-02-15 16:23:41');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('31', '1', '9', '[VIOLATION] Tab/Window Switched (7/10)', '2026-02-15 16:23:47');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('32', '1', '9', '[VIOLATION] Tab/Window Switched (8/10)', '2026-02-15 16:33:11');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('33', '1', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 16:33:32');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('34', '1', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 16:34:12');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('35', '1', '9', '[VIOLATION] Tab/Window Switched (3/4)', '2026-02-15 16:34:15');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('36', '1', '9', '[VIOLATION] Tab/Window Switched (4/4) [LIMIT EXCEEDED]', '2026-02-15 16:34:42');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('37', '1', '9', '[VIOLATION] Exit Fullscreen (5/4) [LIMIT EXCEEDED]', '2026-02-15 16:34:53');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('38', '1', '9', '[VIOLATION] Tab/Window Switched (6/4) [LIMIT EXCEEDED]', '2026-02-15 16:34:55');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('39', '1', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 16:43:51');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('40', '1', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 16:44:02');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('41', '1', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 16:44:04');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('42', '1', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 16:44:05');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('43', '1', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 16:44:06');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('44', '1', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 16:52:51');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('45', '1', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 16:52:53');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('46', '1', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 16:52:58');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('47', '1', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 16:53:09');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('48', '1', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 16:53:12');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('49', '2', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 16:58:12');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('50', '2', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 16:58:33');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('51', '2', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 16:58:44');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('52', '2', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 16:58:51');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('53', '2', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 16:58:55');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('54', '2', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:15:05');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('55', '2', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:15:09');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('56', '2', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:15:13');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('57', '2', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 17:15:16');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('58', '2', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:15:18');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('59', '2', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:16:15');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('60', '2', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:16:18');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('61', '2', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:16:22');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('62', '2', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:16:29');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('63', '2', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 17:16:32');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('64', '2', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:17:55');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('65', '2', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:18:04');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('66', '2', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:18:11');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('67', '2', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:18:20');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('68', '2', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 17:18:24');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('69', '3', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:20:10');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('70', '3', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:20:18');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('71', '3', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:20:24');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('72', '3', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:20:35');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('73', '3', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 17:20:39');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('74', '3', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:24:40');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('75', '3', '9', '[VIOLATION] Tab/Window Switched (2/4)', '2026-02-15 17:24:40');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('76', '3', '9', '[VIOLATION] Tab/Window Switched (3/4)', '2026-02-15 17:26:20');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('77', '3', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:26:28');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('78', '3', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:26:33');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('79', '3', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:26:38');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('80', '3', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:26:41');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('81', '3', '9', '[VIOLATION] Tab/Window Switched (5/4) [LIMIT EXCEEDED]', '2026-02-15 17:26:43');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('82', '4', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 17:42:07');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('83', '4', '9', '[VIOLATION] Exit Fullscreen (2/4)', '2026-02-15 17:42:12');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('84', '4', '9', '[VIOLATION] Exit Fullscreen (3/4)', '2026-02-15 17:42:18');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('85', '4', '9', '[VIOLATION] Exit Fullscreen (4/4) [LIMIT EXCEEDED]', '2026-02-15 17:42:24');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('86', '5', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 18:25:48');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('87', '5', '9', '[VIOLATION] Tab/Window Switched (2/4)', '2026-02-15 18:25:49');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('88', '5', '9', '[VIOLATION] Tab/Window Switched (3/4)', '2026-02-15 18:32:35');
INSERT INTO `cbt_cheat_logs` (`id`, `student_id`, `test_id`, `event`, `created_at`) VALUES ('89', '6', '9', '[VIOLATION] Exit Fullscreen (1/4)', '2026-02-15 18:34:46');

-- Table: cbt_exam_names
DROP TABLE IF EXISTS `cbt_exam_names`;
CREATE TABLE `cbt_exam_names` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_exam_names
INSERT INTO `cbt_exam_names` (`id`, `name`, `created_at`, `updated_at`) VALUES ('1', 'Penilaian Formatif', '2026-02-12 07:45:27', '2026-02-12 07:45:27');
INSERT INTO `cbt_exam_names` (`id`, `name`, `created_at`, `updated_at`) VALUES ('2', 'TKA', '2026-02-14 07:33:34', '2026-02-14 07:33:34');

-- Table: cbt_question_banks
DROP TABLE IF EXISTS `cbt_question_banks`;
CREATE TABLE `cbt_question_banks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `subject_id` int(10) unsigned NOT NULL,
  `level` varchar(10) NOT NULL,
  `teacher_id` int(10) unsigned DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `total_pg` int(11) DEFAULT 0,
  `total_pg_kompleks` int(11) DEFAULT 0,
  `total_esai` int(11) DEFAULT 0,
  `total_bs` int(11) DEFAULT 0,
  `option_count` tinyint(4) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `raw_text` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`) USING BTREE,
  CONSTRAINT `cbt_question_banks_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cbt_question_banks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cbt_bank_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_question_banks
INSERT INTO `cbt_question_banks` (`id`, `code`, `subject_id`, `level`, `teacher_id`, `total_questions`, `total_pg`, `total_pg_kompleks`, `total_esai`, `total_bs`, `option_count`, `is_active`, `created_at`, `updated_at`, `raw_text`) VALUES ('11', 'TKA-BINA-1', '3', 'SMP', NULL, '5', '3', '1', '0', '1', '4', '1', '2026-02-15 13:40:58', '2026-02-15 13:42:09', '<p class=\"MsoNormal\">Soal:1) \"Hewan pemakan daun disebut folivora. Contohnya adalah koala. Koala menghabiskan sebagian besar waktunya di atas pohon eukaliptus untuk makan dan tidur. Mereka tidak perlu sering minum karena kebutuhan cairan terpenuhi dari daun yang mereka makan.\"</p>\r\n<p class=\"MsoNormal\">Apa gagasan utama yang disampaikan pada teks tersebut?</p>\r\n<p class=\"MsoNormal\">A:Koala adalah hewan pemalas.</p>\r\n<p class=\"MsoNormal\">B:Pohon eukaliptus adalah tempat tinggal koala.</p>\r\n<p class=\"MsoNormal\">C:Pengertian dan contoh hewan folivora.</p>\r\n<p class=\"MsoNormal\">D:Cara koala bertahan hidup tanpa minum.</p>\r\n<p class=\"MsoNormal\">Kunci:C</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">Soal:2) \"Suasana di dalam ruang perpustakaan saat jam istirahat sangat&nbsp;menegangkan karena anak-anak berebut buku cerita baru.\"</p>\r\n<p class=\"MsoNormal\">Makna kata&nbsp;menegangkan&nbsp;dalam kalimat tersebut adalah...</p>\r\n<p class=\"MsoNormal\">A:Sangat tenang dan nyaman.</p>\r\n<p class=\"MsoNormal\">B:Membuat suasana menjadi kaku atau tegang.</p>\r\n<p class=\"MsoNormal\">C:Membuat suasana menjadi ramai dan ceria.</p>\r\n<p class=\"MsoNormal\">D:Membuat suasana menjadi berisik dan menakutkan.</p>\r\n<p class=\"MsoNormal\">Kunci:B</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">Soal:3)Bacalah kutipan cerita berikut!</p>\r\n<p class=\"MsoNormal\">\"Rino terdiam mendengar penjelasan Ani. Ia sadar bahwa selama ini ia terlalu angkuh. \'Maafkan aku, Ani. Aku janji tidak akan mengejek pekerjaan ayahmu lagi,\' ucap Rino tertunduk.\"</p>\r\n<p class=\"MsoNormal\">Pesan yang terkandung dalam cerita tersebut adalah...</p>\r\n<p class=\"MsoNormal\">A:Jangan meremehkan pekerjaan orang lain.</p>\r\n<p class=\"MsoNormal\">B:Pekerjaan yang baik adalah pekerjaan kantoran.</p>\r\n<p class=\"MsoNormal\">C:Kita harus menuruti semua kata teman.</p>\r\n<p class=\"MsoNormal\">D:Segera meminta maaf saat melakukan kesalahan.</p>\r\n<p class=\"MsoNormal\">Kunci:D</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">Soal:4) Bacalah teks cerita!</p>\r\n<p class=\"MsoNormal\">&nbsp;\"Amel terharu membaca surat dari Kenthus. Kenthus berjanji akan merawat anak lembu yang ia selamatkan.\"</p>\r\n<p class=\"MsoNormal\">Pernyataan yang sesuai dengan isi cerita (Klik Benar atau Salah)</p>\r\n<p class=\"MsoNormal\">Tipe:BS</p>\r\n<p class=\"MsoNormal\">A:Amel merasa sedih</p>\r\n<p class=\"MsoNormal\">B:Kenthus mengakui kesalahannya</p>\r\n<p class=\"MsoNormal\">C:Kenthus berjanji merawat lembu</p>\r\n<p class=\"MsoNormal\">Kunci:S, B, B</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">Soal:5) Bacalah teks berikut!</p>\r\n<p class=\"MsoNormal\">\"Ada tiga jenis hewan: herbivora (pemakan tumbuhan), karnivora (pemakan daging), dan omnivora (pemakan segala).\"</p>\r\n<p class=\"MsoNormal\">Manakah pasangan hewan dan jenis makanannya yang benar?</p>\r\n<p class=\"MsoNormal\">A:Sapi-Herbivora</p>\r\n<p class=\"MsoNormal\">B:Singa-Karnivora</p>\r\n<p class=\"MsoNormal\">C:Ayam-Omnivora</p>\r\n<p class=\"MsoNormal\">D:Kelinci-Karnivora</p>\r\n<p class=\"MsoNormal\">Kunci:A, B, C</p>');

-- Table: cbt_questions
DROP TABLE IF EXISTS `cbt_questions`;
CREATE TABLE `cbt_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` int(10) unsigned NOT NULL,
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
  `audio_e` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_id` (`bank_id`),
  CONSTRAINT `cbt_questions_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `cbt_question_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_questions
INSERT INTO `cbt_questions` (`id`, `bank_id`, `question_text`, `raw_text`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_option`, `question_type`, `essay_answer`, `score`, `media_image`, `media_audio`, `media_video`, `created_at`, `updated_at`, `has_image`, `has_audio`, `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e`) VALUES ('42', '11', '\"Hewan pemakan daun disebut folivora. Contohnya adalah koala. Koala menghabiskan sebagian besar waktunya di atas pohon eukaliptus untuk makan dan tidur. Mereka tidak perlu sering minum karena kebutuhan cairan terpenuhi dari daun yang mereka makan.\" Apa gagasan utama yang disampaikan pada teks tersebut?', '\"Hewan pemakan daun disebut folivora. Contohnya adalah koala. Koala menghabiskan sebagian besar waktunya di atas pohon eukaliptus untuk makan dan tidur. Mereka tidak perlu sering minum karena kebutuhan cairan terpenuhi dari daun yang mereka makan.\"</p>\r\n<p class=\"MsoNormal\">Apa gagasan utama yang disampaikan pada teks tersebut?</p>\r\n<p class=\"MsoNormal\">A:Koala adalah hewan pemalas.</p>\r\n<p class=\"MsoNormal\">B:Pohon eukaliptus adalah tempat tinggal koala.</p>\r\n<p class=\"MsoNormal\">C:Pengertian dan contoh hewan folivora.</p>\r\n<p class=\"MsoNormal\">D:Cara koala bertahan hidup tanpa minum.</p>\r\n<p class=\"MsoNormal\">Kunci:C</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">', 'Koala adalah hewan pemalas.', 'Pohon eukaliptus adalah tempat tinggal koala.', 'Pengertian dan contoh hewan folivora.', 'Cara koala bertahan hidup tanpa minum.', NULL, 'C', 'pg', '', '1.00', '[]', NULL, NULL, '2026-02-15 13:41:38', '2026-02-15 13:41:38', '0', '0', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cbt_questions` (`id`, `bank_id`, `question_text`, `raw_text`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_option`, `question_type`, `essay_answer`, `score`, `media_image`, `media_audio`, `media_video`, `created_at`, `updated_at`, `has_image`, `has_audio`, `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e`) VALUES ('43', '11', '\"Suasana di dalam ruang perpustakaan saat jam istirahat sangat menegangkan karena anak-anak berebut buku cerita baru.\" Makna kata menegangkan dalam kalimat tersebut adalah...', '\"Suasana di dalam ruang perpustakaan saat jam istirahat sangat&nbsp;menegangkan karena anak-anak berebut buku cerita baru.\"</p>\r\n<p class=\"MsoNormal\">Makna kata&nbsp;menegangkan&nbsp;dalam kalimat tersebut adalah...</p>\r\n<p class=\"MsoNormal\">A:Sangat tenang dan nyaman.</p>\r\n<p class=\"MsoNormal\">B:Membuat suasana menjadi kaku atau tegang.</p>\r\n<p class=\"MsoNormal\">C:Membuat suasana menjadi ramai dan ceria.</p>\r\n<p class=\"MsoNormal\">D:Membuat suasana menjadi berisik dan menakutkan.</p>\r\n<p class=\"MsoNormal\">Kunci:B</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">', 'Sangat tenang dan nyaman.', 'Membuat suasana menjadi kaku atau tegang.', 'Membuat suasana menjadi ramai dan ceria.', 'Membuat suasana menjadi berisik dan menakutkan.', NULL, 'B', 'pg', '', '1.00', '[]', NULL, NULL, '2026-02-15 13:41:38', '2026-02-15 13:41:38', '0', '0', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cbt_questions` (`id`, `bank_id`, `question_text`, `raw_text`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_option`, `question_type`, `essay_answer`, `score`, `media_image`, `media_audio`, `media_video`, `created_at`, `updated_at`, `has_image`, `has_audio`, `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e`) VALUES ('44', '11', 'Bacalah kutipan cerita berikut! \"Rino terdiam mendengar penjelasan Ani. Ia sadar bahwa selama ini ia terlalu angkuh. \'Maafkan aku, Ani. Aku janji tidak akan mengejek pekerjaan ayahmu lagi,\' ucap Rino tertunduk.\" Pesan yang terkandung dalam cerita tersebut adalah...', 'Bacalah kutipan cerita berikut!</p>\r\n<p class=\"MsoNormal\">\"Rino terdiam mendengar penjelasan Ani. Ia sadar bahwa selama ini ia terlalu angkuh. \'Maafkan aku, Ani. Aku janji tidak akan mengejek pekerjaan ayahmu lagi,\' ucap Rino tertunduk.\"</p>\r\n<p class=\"MsoNormal\">Pesan yang terkandung dalam cerita tersebut adalah...</p>\r\n<p class=\"MsoNormal\">A:Jangan meremehkan pekerjaan orang lain.</p>\r\n<p class=\"MsoNormal\">B:Pekerjaan yang baik adalah pekerjaan kantoran.</p>\r\n<p class=\"MsoNormal\">C:Kita harus menuruti semua kata teman.</p>\r\n<p class=\"MsoNormal\">D:Segera meminta maaf saat melakukan kesalahan.</p>\r\n<p class=\"MsoNormal\">Kunci:D</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">', 'Jangan meremehkan pekerjaan orang lain.', 'Pekerjaan yang baik adalah pekerjaan kantoran.', 'Kita harus menuruti semua kata teman.', 'Segera meminta maaf saat melakukan kesalahan.', NULL, 'D', 'pg', '', '1.00', '[]', NULL, NULL, '2026-02-15 13:41:38', '2026-02-15 13:41:38', '0', '0', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cbt_questions` (`id`, `bank_id`, `question_text`, `raw_text`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_option`, `question_type`, `essay_answer`, `score`, `media_image`, `media_audio`, `media_video`, `created_at`, `updated_at`, `has_image`, `has_audio`, `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e`) VALUES ('45', '11', 'Bacalah teks cerita! \"Amel terharu membaca surat dari Kenthus. Kenthus berjanji akan merawat anak lembu yang ia selamatkan.\" Pernyataan yang sesuai dengan isi cerita (Klik Benar atau Salah)', 'Bacalah teks cerita!</p>\r\n<p class=\"MsoNormal\">&nbsp;\"Amel terharu membaca surat dari Kenthus. Kenthus berjanji akan merawat anak lembu yang ia selamatkan.\"</p>\r\n<p class=\"MsoNormal\">Pernyataan yang sesuai dengan isi cerita (Klik Benar atau Salah)</p>\r\n<p class=\"MsoNormal\">Tipe:BS</p>\r\n<p class=\"MsoNormal\">A:Amel merasa sedih</p>\r\n<p class=\"MsoNormal\">B:Kenthus mengakui kesalahannya</p>\r\n<p class=\"MsoNormal\">C:Kenthus berjanji merawat lembu</p>\r\n<p class=\"MsoNormal\">Kunci:S, B, B</p>\r\n<p class=\"MsoNormal\">&nbsp;</p>\r\n<p class=\"MsoNormal\">', 'Amel merasa sedih', 'Kenthus mengakui kesalahannya', 'Kenthus berjanji merawat lembu', NULL, NULL, 'S,B,B', 'benar_salah', '', '1.00', '[]', NULL, NULL, '2026-02-15 13:41:38', '2026-02-15 13:41:38', '0', '0', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `cbt_questions` (`id`, `bank_id`, `question_text`, `raw_text`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `correct_option`, `question_type`, `essay_answer`, `score`, `media_image`, `media_audio`, `media_video`, `created_at`, `updated_at`, `has_image`, `has_audio`, `audio_a`, `audio_b`, `audio_c`, `audio_d`, `audio_e`) VALUES ('46', '11', 'Bacalah teks berikut! \"Ada tiga jenis hewan: herbivora (pemakan tumbuhan), karnivora (pemakan daging), dan omnivora (pemakan segala).\" Manakah pasangan hewan dan jenis makanannya yang benar?', 'Bacalah teks berikut!</p>\r\n<p class=\"MsoNormal\">\"Ada tiga jenis hewan: herbivora (pemakan tumbuhan), karnivora (pemakan daging), dan omnivora (pemakan segala).\"</p>\r\n<p class=\"MsoNormal\">Manakah pasangan hewan dan jenis makanannya yang benar?</p>\r\n<p class=\"MsoNormal\">A:Sapi-Herbivora</p>\r\n<p class=\"MsoNormal\">B:Singa-Karnivora</p>\r\n<p class=\"MsoNormal\">C:Ayam-Omnivora</p>\r\n<p class=\"MsoNormal\">D:Kelinci-Karnivora</p>\r\n<p class=\"MsoNormal\">Kunci:A, B, C</p>', 'Sapi-Herbivora', 'Singa-Karnivora', 'Ayam-Omnivora', 'Kelinci-Karnivora', NULL, 'A,B,C', 'pg_kompleks', '', '1.00', '[]', NULL, NULL, '2026-02-15 13:41:38', '2026-02-15 13:41:38', '0', '0', NULL, NULL, NULL, NULL, NULL);

-- Table: cbt_sessions
DROP TABLE IF EXISTS `cbt_sessions`;
CREATE TABLE `cbt_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `test_id` int(10) unsigned NOT NULL,
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
  `cheat_locked` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_test_idx` (`student_id`,`test_id`),
  KEY `idx_student_test` (`student_id`,`test_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: cbt_sessions
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('2', '1', '1', '1770862959', '[\"1\",\"2\"]', '{\"1\":[\"B\",\"A\",\"D\",\"C\"],\"2\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-12 09:22:39', '2026-02-12 09:22:50', '50.00', NULL, '50.00', '2026', '0', '157b757105a796ae', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('4', '1', '2', '1770887827', '[\"7\",\"4\",\"6\",\"3\",\"5\",\"8\",\"9\",\"10\"]', '{\"7\":[\"D\",\"B\",\"A\",\"C\"],\"4\":[\"D\",\"C\",\"B\",\"A\"],\"6\":[\"B\",\"C\",\"D\",\"A\"],\"3\":[\"D\",\"A\",\"B\",\"C\"],\"5\":[\"D\",\"A\",\"C\",\"B\"],\"8\":[\"D\",\"B\",\"C\",\"A\"],\"9\":[\"C\",\"A\",\"B\",\"D\"],\"10\":[]}', '2026', 'finished', '2026-02-12 16:17:07', '2026-02-12 19:57:33', '78.00', '100.00', '88.00', '2026', '0', '9bb6024eae5e9f14', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('5', '2', '2', '1770900691', '[\"3\",\"4\",\"6\",\"7\",\"5\",\"9\",\"8\",\"10\"]', '{\"3\":[\"D\",\"A\",\"B\",\"C\"],\"4\":[\"A\",\"D\",\"C\",\"B\"],\"6\":[\"B\",\"A\",\"D\",\"C\"],\"7\":[\"C\",\"D\",\"A\",\"B\"],\"5\":[\"A\",\"B\",\"C\",\"D\"],\"9\":[\"B\",\"C\",\"D\",\"A\"],\"8\":[\"B\",\"A\",\"C\",\"D\"],\"10\":[]}', '2026', 'finished', '2026-02-12 19:51:31', '2026-02-12 19:57:48', '78.75', '100.00', '88.75', '2026', '0', '215299375a938830', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('6', '2', '3', '1770905999', '[\"11\",\"12\"]', '{\"11\":[\"A\",\"C\",\"D\",\"B\"],\"12\":[\"D\",\"B\",\"C\",\"A\"]}', '2026', 'finished', '2026-02-12 21:19:59', '2026-02-12 21:28:16', '50.00', NULL, '50.00', '2026', '0', 'aa15a563b2d53434', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('7', '1', '3', '1770906541', '[\"11\",\"12\"]', '{\"11\":[\"D\",\"C\",\"B\",\"A\"],\"12\":[\"D\",\"B\",\"A\",\"C\"]}', '2026', 'finished', '2026-02-12 21:29:01', '2026-02-13 08:19:11', '0.00', NULL, '0.00', '2026', '0', '1bffb6a6d220720d', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('8', '3', '3', '1770907120', '[\"12\",\"11\"]', '{\"12\":[\"C\",\"B\",\"D\",\"A\"],\"11\":[\"C\",\"B\",\"D\",\"A\"]}', '2026', 'finished', '2026-02-12 21:38:40', '2026-02-12 21:44:55', '100.00', NULL, '100.00', '2026', '0', '624c59b67e846fe9', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('9', '4', '3', '1770909233', '[\"12\",\"11\"]', '{\"12\":[\"C\",\"D\",\"A\",\"B\"],\"11\":[\"C\",\"A\",\"D\",\"B\"]}', '2026', 'finished', '2026-02-12 21:57:06', '2026-02-12 22:14:11', '100.00', NULL, '100.00', '2026', '0', 'e58fedc0ca9f5274', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('11', '5', '3', '1770911696', '[\"11\",\"12\"]', '{\"11\":[\"A\",\"B\",\"D\",\"C\"],\"12\":[\"A\",\"D\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-12 22:27:17', '2026-02-12 22:59:27', '100.00', NULL, '100.00', '2026', '0', '0a0cb1e52e095722', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('12', '6', '3', '1770912950', '[\"12\",\"11\"]', '{\"12\":[\"C\",\"D\",\"B\",\"A\"],\"11\":[\"D\",\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-12 23:15:50', '2026-02-13 14:23:51', '0.00', NULL, '0.00', '2026', '0', 'b1c02b003e05a4d4', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('13', '1', '4', '1770945563', '[\"14\",\"13\"]', '{\"14\":[\"A\",\"B\",\"C\"],\"13\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-13 08:19:23', '2026-02-13 08:48:27', '87.50', NULL, '87.50', '2026', '0', 'fe595a3688de0d9f', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('14', '1', '5', '1770963434', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"A\",\"C\",\"B\",\"D\"],\"21\":[\"A\",\"B\",\"D\",\"C\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 13:17:14', '2026-02-13 13:26:23', '33.33', NULL, '33.33', '2026', '0', '5fb73c40e7c0110b', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('15', '2', '5', '1770964225', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"C\",\"D\",\"A\",\"B\"],\"21\":[\"C\",\"D\",\"B\",\"A\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 13:30:25', '2026-02-13 13:44:43', '56.67', NULL, '56.67', '2026', '0', '47dd41952cfb356f', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('16', '3', '5', '1770965327', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"D\",\"B\",\"C\",\"A\"],\"21\":[\"D\",\"C\",\"A\",\"B\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 13:48:47', '2026-02-13 13:51:36', '60.00', NULL, '60.00', '2026', '0', '6444ec334b65bc3f', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('17', '5', '5', '1770966147', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"A\",\"C\",\"B\",\"D\"],\"21\":[\"A\",\"C\",\"D\",\"B\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 14:02:27', '2026-02-13 14:05:02', '60.00', NULL, '60.00', '2026', '0', 'de0e43170c7a7bdc', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('18', '7', '5', '1770966370', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"B\",\"D\",\"C\",\"A\"],\"21\":[\"C\",\"A\",\"D\",\"B\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 14:06:10', '2026-02-13 14:09:17', '70.00', NULL, '70.00', '2026', '0', 'f5347892302069f5', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('19', '4', '5', '1770967078', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"B\",\"A\",\"D\",\"C\"],\"21\":[\"A\",\"D\",\"C\",\"B\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 14:17:58', '2026-02-13 14:20:33', '60.00', NULL, '60.00', '2026', '0', '4cb69acbb6cf600d', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('20', '6', '5', '1770967442', '[\"19\",\"21\",\"20\"]', '{\"19\":[\"B\",\"D\",\"C\",\"A\"],\"21\":[\"D\",\"C\",\"B\",\"A\"],\"20\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-13 14:24:02', '2026-02-13 14:26:36', '60.00', NULL, '60.00', '2026', '0', '588cdc6779ba6872', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('21', '2', '6', '1770969768', '[\"25\",\"22\",\"24\",\"26\",\"23\"]', '{\"25\":[\"B\",\"D\",\"A\",\"C\"],\"22\":[\"C\",\"D\",\"A\",\"B\"],\"24\":[\"A\",\"D\",\"C\",\"B\"],\"26\":[\"D\",\"A\",\"B\",\"C\"],\"23\":[\"D\",\"B\",\"A\",\"C\"]}', '2026', 'finished', '2026-02-13 15:02:48', '2026-02-13 15:03:44', '80.00', NULL, '80.00', '2026', '0', '8b6a87fa9d8106a8', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('22', '1', '7', '1771031216', '[\"33\",\"32\",\"34\",\"35\",\"36\"]', '{\"33\":[\"A\",\"D\",\"B\",\"C\"],\"32\":[\"C\",\"A\",\"D\",\"B\"],\"34\":[\"B\",\"D\",\"C\",\"A\"],\"35\":[\"B\",\"A\",\"C\",\"D\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 07:39:52', '2026-02-14 08:15:46', '100.00', NULL, '100.00', '2026', '0', '8cca889efd2d58ac', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('23', '2', '7', '1771058229', '[\"34\",\"32\",\"33\",\"35\",\"36\"]', '{\"34\":[\"B\",\"A\",\"D\",\"C\"],\"32\":[\"A\",\"C\",\"B\",\"D\"],\"33\":[\"D\",\"B\",\"A\",\"C\"],\"35\":[\"C\",\"B\",\"A\",\"D\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 14:25:54', '2026-02-14 15:43:16', '65.00', NULL, '65.00', '2026', '0', 'a476733de3b140f7', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('24', '3', '7', '1771056146', '[\"33\",\"32\",\"34\",\"35\",\"36\"]', '{\"33\":[\"D\",\"C\",\"A\",\"B\"],\"32\":[\"A\",\"D\",\"B\",\"C\"],\"34\":[\"C\",\"B\",\"D\",\"A\"],\"35\":[\"D\",\"C\",\"B\",\"A\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 15:02:26', '2026-02-14 15:12:26', '73.33', NULL, '73.33', '2026', '0', '948c96f1e16bc8e3', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('26', '6', '7', '1771067651', '[\"32\",\"33\",\"34\",\"35\",\"36\"]', '{\"32\":[\"D\",\"C\",\"A\",\"B\"],\"33\":[\"D\",\"B\",\"C\",\"A\"],\"34\":[\"A\",\"C\",\"B\",\"D\"],\"35\":[\"A\",\"C\",\"D\",\"B\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 18:14:11', '2026-02-14 18:16:46', '70.00', NULL, '70.00', '2026', '0', 'e129df6e66a62917', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('27', '7', '7', '1771068482', '[\"34\",\"32\",\"33\",\"35\",\"36\"]', '{\"34\":[\"B\",\"D\",\"A\",\"C\"],\"32\":[\"B\",\"C\",\"A\",\"D\"],\"33\":[\"B\",\"A\",\"C\",\"D\"],\"35\":[\"B\",\"A\",\"C\",\"D\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 18:28:02', '2026-02-14 18:45:40', '65.00', NULL, '65.00', '2026', '0', 'ad89f31f7d1df667', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('28', '4', '7', '1771069592', '[\"34\",\"33\",\"32\",\"35\",\"36\"]', '{\"34\":[\"A\",\"D\",\"C\",\"B\"],\"33\":[\"B\",\"D\",\"C\",\"A\"],\"32\":[\"C\",\"B\",\"A\",\"D\"],\"35\":[\"C\",\"D\",\"B\",\"A\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 18:46:32', '2026-02-14 18:48:20', '100.00', NULL, '100.00', '2026', '0', 'ab52d076438ed1a7', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('29', '8', '7', '1771071011', '[\"33\",\"32\",\"34\",\"35\",\"36\"]', '{\"33\":[\"C\",\"B\",\"D\",\"A\"],\"32\":[\"A\",\"D\",\"C\",\"B\"],\"34\":[\"A\",\"B\",\"D\",\"C\"],\"35\":[\"A\",\"C\",\"D\",\"B\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 19:10:11', '2026-02-14 19:11:47', '100.00', NULL, '100.00', '2026', '0', 'fcd6639599680454', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('30', '5', '7', '1771071835', '[\"34\",\"33\",\"32\",\"35\",\"36\"]', '{\"34\":[\"D\",\"B\",\"A\",\"C\"],\"33\":[\"A\",\"B\",\"C\",\"D\"],\"32\":[\"A\",\"B\",\"C\",\"D\"],\"35\":[\"A\",\"C\",\"B\",\"D\"],\"36\":[\"A\",\"B\",\"C\",\"D\"]}', '2026', 'finished', '2026-02-14 19:23:55', '2026-02-14 19:25:14', '100.00', NULL, '100.00', '2026', '0', '54f01b8a8ce5a790', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('60', '1', '9', '1771149248', '[\"42\",\"44\",\"43\",\"46\",\"45\"]', '{\"42\":[\"D\",\"C\",\"A\",\"B\"],\"44\":[\"A\",\"B\",\"C\",\"D\"],\"43\":[\"D\",\"B\",\"A\",\"C\"],\"46\":[\"D\",\"A\",\"B\",\"C\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 16:54:08', '2026-02-15 16:54:41', '100.00', NULL, '100.00', '1771149281', '0', '36b2616d038144f7', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('61', '2', '9', '1771150638', '[\"42\",\"43\",\"44\",\"46\",\"45\"]', '{\"42\":[\"D\",\"C\",\"B\",\"A\"],\"43\":[\"C\",\"A\",\"B\",\"D\"],\"44\":[\"A\",\"C\",\"D\",\"B\"],\"46\":[\"C\",\"D\",\"B\",\"A\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 16:57:51', '2026-02-15 17:18:51', '100.00', NULL, '100.00', '1771150731', '0', '306b8f5d90381e54', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('62', '3', '9', '1771150786', '[\"44\",\"42\",\"43\",\"46\",\"45\"]', '{\"44\":[\"B\",\"A\",\"C\",\"D\"],\"42\":[\"B\",\"C\",\"D\",\"A\"],\"43\":[\"C\",\"A\",\"D\",\"B\"],\"46\":[\"A\",\"C\",\"D\",\"B\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 17:19:46', '2026-02-15 17:35:27', '100.00', NULL, '100.00', '1771151727', '0', '87b4d395c4f89d47', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('63', '4', '9', '1771152090', '[\"42\",\"43\",\"44\",\"46\",\"45\"]', '{\"42\":[\"D\",\"B\",\"A\",\"C\"],\"43\":[\"C\",\"A\",\"D\",\"B\"],\"44\":[\"C\",\"A\",\"B\",\"D\"],\"46\":[\"C\",\"D\",\"A\",\"B\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 17:41:30', '2026-02-15 17:42:27', '100.00', NULL, '100.00', '1771152147', '0', '461494952584613d', 'violation_limit');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('64', '5', '9', '1771154668', '[\"43\",\"42\",\"44\",\"46\",\"45\"]', '{\"43\":[\"A\",\"C\",\"B\",\"D\"],\"42\":[\"A\",\"C\",\"B\",\"D\"],\"44\":[\"D\",\"C\",\"A\",\"B\"],\"46\":[\"A\",\"B\",\"D\",\"C\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 18:24:28', '2026-02-15 18:32:56', '100.00', NULL, '100.00', '1771155176', '0', '04932a611402906d', '0');
INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `question_order`, `option_orders`, `last_activity`, `status`, `created_at`, `updated_at`, `score`, `essay_score`, `total_score`, `finished_at`, `extra_time`, `reset_token`, `cheat_locked`) VALUES ('65', '6', '9', '1771155232', '[\"42\",\"43\",\"44\",\"46\",\"45\"]', '{\"42\":[\"B\",\"C\",\"D\",\"A\"],\"43\":[\"A\",\"C\",\"D\",\"B\"],\"44\":[\"A\",\"C\",\"B\",\"D\"],\"46\":[\"C\",\"B\",\"D\",\"A\"],\"45\":[\"A\",\"B\",\"C\"]}', '2026', 'finished', '2026-02-15 18:33:52', '2026-02-15 18:38:07', '100.00', NULL, '100.00', '1771155487', '0', 'c88f5915d98c482d', '0');

-- Table: cbt_test_status
DROP TABLE IF EXISTS `cbt_test_status`;
CREATE TABLE `cbt_test_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_id` int(10) unsigned NOT NULL,
  `exam_name_id` int(10) unsigned NOT NULL,
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
  `created_by` int(11) unsigned DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bank_id` (`bank_id`),
  KEY `exam_name_id` (`exam_name_id`),
  CONSTRAINT `cbt_test_status_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `cbt_question_banks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cbt_test_status_ibfk_2` FOREIGN KEY (`exam_name_id`) REFERENCES `cbt_exam_names` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: ci_sessions
DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: ci_sessions
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:0325bd097cef81aa5c35c94950136006', '::1', '4294967295', '__ci_last_regenerate|i:1771726250;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:46:\"http://localhost/cbtku/index.php/admin/updater\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:10299476401735c52a21130608828e9f', '::1', '4294967295', '__ci_last_regenerate|i:1771716450;error|s:49:\"Data lisensi tidak valid. Silakan aktivasi ulang.\";__ci_vars|a:2:{s:5:\"error\";s:3:\"old\";s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:1afa3da6134c26c730ad7572e6772102', '::1', '4294967295', '__ci_last_regenerate|i:1771722554;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:40:\"http://localhost/cbtku/index.php/profile\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:43f3f64c6c2d234f477c89e5eb7b9f9d', '::1', '4294967295', '__ci_last_regenerate|i:1771718214;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:33:\"http://localhost/cbtku/index.php/\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:61a6cedbb4ac813d1fe39e05cf0b71fd', '::1', '4294967295', '__ci_last_regenerate|i:1771725087;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:46:\"http://localhost/cbtku/index.php/admin/updater\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:67c34b253e78e829f9f12a9816a2e4b5', '::1', '4294967295', '__ci_last_regenerate|i:1771721931;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:40:\"http://localhost/cbtku/index.php/profile\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:6a65a6d5fd942a20f1fa4ed6b50bb39e', '::1', '4294967295', '__ci_last_regenerate|i:1771715974;error|s:49:\"Data lisensi tidak valid. Silakan aktivasi ulang.\";__ci_vars|a:2:{s:5:\"error\";s:3:\"old\";s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:6e9f44d805ae7315fc41f64449a61284', '::1', '4294967295', '__ci_last_regenerate|i:1771717616;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:7102546f119b1c979e45dded1db9bbae', '::1', '4294967295', '__ci_last_regenerate|i:1771726250;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:46:\"http://localhost/cbtku/index.php/admin/updater\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:74a3721297b3a1d77f50eef4f9518a5e', '::1', '4294967295', '__ci_last_regenerate|i:1771724454;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:40:\"http://localhost/cbtku/index.php/profile\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:778354c5c5f75c0593a1a4a2c04bd8af', '::1', '4294967295', '__ci_last_regenerate|i:1771723209;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";_ci_previous_url|s:40:\"http://localhost/cbtku/index.php/profile\";user|a:6:{s:2:\"id\";s:1:\"1\";s:8:\"username\";s:5:\"admin\";s:8:\"fullname\";s:13:\"Administrator\";s:7:\"role_id\";s:1:\"1\";s:10:\"related_id\";s:1:\"0\";s:12:\"related_type\";s:0:\"\";}logged_in|b:1;');
INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`) VALUES ('ci_session:c8f2e1157f0315c7a6f51d5bf9ae384b', '::1', '4294967295', '__ci_last_regenerate|i:1771717274;__ci_vars|a:1:{s:15:\"license_warning\";s:3:\"new\";}license_warning|s:81:\"Lisensi akan berakhir dalam 2 hari. Segera hubungi pengembang untuk perpanjangan.\";');

-- Table: classes
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `level` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: classes
INSERT INTO `classes` (`id`, `name`, `level`, `teacher_id`, `description`, `created_at`, `updated_at`) VALUES ('1', 'Kelas 7A', '7', NULL, NULL, '2026-02-12 07:38:41', '2026-02-12 07:38:41');

-- Table: exam_schedules
DROP TABLE IF EXISTS `exam_schedules`;
CREATE TABLE `exam_schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_exam_schedules_subject` (`subject_id`),
  KEY `fk_exam_schedules_class` (`class_id`),
  CONSTRAINT `fk_exam_schedules_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_exam_schedules_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: license_audit_log
DROP TABLE IF EXISTS `license_audit_log`;
CREATE TABLE `license_audit_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL,
  `license_key` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: migrations
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: migrations
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('1', '2026-02-16-051752', 'App\\Database\\Migrations\\CreateLicenseTable', 'default', 'App', '1771219091', '1');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('2', '2026-02-16-053010', 'App\\Database\\Migrations\\AddExpiresAtToLicenseTable', 'default', 'App', '1771219838', '2');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('3', '2026-02-16-075253', 'App\\Database\\Migrations\\AddHashToLicenseTable', 'default', 'App', '1771228411', '3');
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES ('4', '2026-02-21-215058', 'App\\Database\\Migrations\\RemoveTinymceApiKeyFromSchool', 'default', 'App', '1771710733', '4');

-- Table: roles
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: roles
INSERT INTO `roles` (`id`, `name`) VALUES ('1', 'admin');
INSERT INTO `roles` (`id`, `name`) VALUES ('2', 'guru');
INSERT INTO `roles` (`id`, `name`) VALUES ('3', 'siswa');

-- Table: school_profile
DROP TABLE IF EXISTS `school_profile`;
CREATE TABLE `school_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `headmaster` varchar(100) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: school_profile
INSERT INTO `school_profile` (`id`, `name`, `address`, `phone`, `email`, `logo`, `headmaster`, `level`, `created_at`, `updated_at`) VALUES ('1', 'SEKOLAH SAKASALIKA', 'Jl. Cinta Penuh Kasih NO.9', '081298732440', 'salmawijaya85@gmail.com', '1771012741_097d478157848c962371.png', 'Saka Salika', '2', '2026-02-12 07:37:28', '2026-02-15 21:37:29');

-- Table: students
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) DEFAULT NULL,
  `username` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` enum('L','P') NOT NULL,
  `class_id` int(11) unsigned NOT NULL,
  `religion` varchar(10) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plain_password` text NOT NULL,
  `room` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: students
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('1', '1213', '1213', 'Bambang Kusuma', 'L', '1', 'Kristen', '35', '12345*', 'Ruang 1', '2026-02-12 07:44:11', '2026-02-12 07:44:11');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('2', '1214', '1214', 'Budi Utomo', 'L', '1', 'Islam', '36', '12345*', 'Ruang 1', '2026-02-12 07:44:11', '2026-02-12 07:44:11');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('3', '1215', '1215', 'Cantika Maharani', 'P', '1', 'Islam', '37', '12345*', 'Ruang 1', '2026-02-12 07:44:11', '2026-02-12 07:44:12');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('4', '1216', '1216', 'Dewi Puspita', 'P', '1', 'Islam', '38', '12345*', 'Ruang 1', '2026-02-12 07:44:12', '2026-02-12 07:44:12');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('5', '1217', '1217', 'Eman Ruhiman', 'L', '1', 'Islam', '39', '12345*', 'Ruang 1', '2026-02-12 07:44:12', '2026-02-12 07:44:12');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('6', '1218', '1218', 'Momon Suherman', 'L', '1', 'Islam', '40', '12345*', 'Ruang 1', '2026-02-12 07:44:12', '2026-02-12 07:44:12');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('7', '1219', '1219', 'Nengsih', 'P', '1', 'Islam', '41', '12345*', 'Ruang 1', '2026-02-12 07:44:12', '2026-02-12 07:44:12');
INSERT INTO `students` (`id`, `nis`, `username`, `name`, `gender`, `class_id`, `religion`, `user_id`, `plain_password`, `room`, `created_at`, `updated_at`) VALUES ('8', '1220', '1220', 'Siti Maemunah', 'P', '1', 'Islam', '42', '12345*', 'Ruang 1', '2026-02-12 07:44:12', '2026-02-12 07:44:12');

-- Table: subjects
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: subjects
INSERT INTO `subjects` (`id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES ('1', 'PADB', 'Pendidikan Agama dan Budipekerti', '1', '2026-02-12 07:44:42', '2026-02-12 07:44:42');
INSERT INTO `subjects` (`id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES ('2', 'MTK', 'Matematika', '1', '2026-02-12 07:44:56', '2026-02-12 07:44:56');
INSERT INTO `subjects` (`id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES ('3', 'BINA', 'Bahasa Indonesia', '1', '2026-02-12 07:45:06', '2026-02-12 07:45:06');
INSERT INTO `subjects` (`id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES ('4', 'BING', 'Bahasa Inggris', '1', '2026-02-12 07:45:14', '2026-02-12 07:45:14');

-- Table: teachers
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_teachers_user` (`user_id`),
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: teachers
INSERT INTO `teachers` (`id`, `username`, `name`, `created_at`, `updated_at`, `user_id`) VALUES ('1', 'salmawijaya85@gmail.com', 'SALMA WIJAYA', '2026-02-12 07:38:22', '2026-02-12 07:38:22', '34');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active_session_id` varchar(255) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `role_id` int(5) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` enum('teacher','student','admin') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: users
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('1', 'admin', '$2y$10$GVL80yaFiyO643RikPYMz.pAFwZNJEc/pPNfDgeNPQIFuK2wu/1UK', '67c34b253e78e829f9f12a9816a2e4b5', '2026-02-22 09:10:54', 'Administrator', '1', '0000-00-00 00:00:00', '2026-02-22 09:10:54', '0', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('34', 'salmawijaya85@gmail.com', '$2y$10$2iwfi39okUxAUY3L5EvCMegm2Bul5yYstN2OySR8oT2Xt1M.43u6C', '3cb12ca9cc4d859c2fc8681150ed6eed', '2026-02-16 20:36:34', 'SALMA WIJAYA', '2', '2026-02-12 07:38:22', '2026-02-16 20:36:34', '1', 'teacher');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('35', '1213', '$2y$10$DsQJXwhTs1ExzmknFmciIu/V5f/wIoYEt1Bu3QeZus5O04I0Nvl3u', NULL, NULL, 'Bambang Kusuma', '3', '2026-02-12 07:44:11', '2026-02-16 18:52:35', '1', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('36', '1214', '$2y$10$0MHSYJnnU4b5BokjPdZTFO3RLbMRT1fWVWI17MH2SaCu5NnG0YF6i', NULL, NULL, 'Budi Utomo', '3', '2026-02-12 07:44:11', '2026-02-15 17:19:21', '2', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('37', '1215', '$2y$10$/B99WIVOJKHZmIk5Y7FLkevoiAIogG/BZb5OUMVNI./GP36L4C37u', NULL, NULL, 'Cantika Maharani', '3', '2026-02-12 07:44:12', '2026-02-15 17:36:58', '3', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('38', '1216', '$2y$10$gmZwA1XlyLTUi./DqicX8u/web3LngJr8E7p2F3CsS0wgcqy.Dvc6', NULL, NULL, 'Dewi Puspita', '3', '2026-02-12 07:44:12', '2026-02-15 18:23:06', '4', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('39', '1217', '$2y$10$aPgMdX4.KL86ysiFzPxlFe4/.fy9lH0RPdHnpgEOjehEXbgR0qSEe', NULL, NULL, 'Eman Ruhiman', '3', '2026-02-12 07:44:12', '2026-02-15 18:33:23', '5', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('40', '1218', '$2y$10$pQMOUER0VUGoDX5HX2loB.OEyt4TVE5RFf6mAizmWkXLJ2cIw2d7S', '2250a142826aa1f447df633b9a53a5a6', '2026-02-15 18:52:53', 'Momon Suherman', '3', '2026-02-12 07:44:12', '2026-02-15 18:52:53', '6', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('41', '1219', '$2y$10$846R78BKHGnxMJ9fAJpTsua2Bsvj19gwxNMmSIStjJmjmpAoefhyq', '734cdbadd5662bb67a8b1b47e387d677', '2026-02-14 18:45:43', 'Nengsih', '3', '2026-02-12 07:44:12', '2026-02-14 18:45:43', '7', '');
INSERT INTO `users` (`id`, `username`, `password`, `active_session_id`, `last_activity`, `fullname`, `role_id`, `created_at`, `updated_at`, `related_id`, `related_type`) VALUES ('42', '1220', '$2y$10$/pJrd8n4WwOoODWQH2NE5.WOoSMf1Ct0e2CJKGg0DAmpZTd8Sp4Ey', NULL, NULL, 'Siti Maemunah', '3', '2026-02-12 07:44:12', '2026-02-14 19:23:14', '8', '');

SET FOREIGN_KEY_CHECKS=1;
