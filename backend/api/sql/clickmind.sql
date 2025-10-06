-- --------------------------------------------------------
-- Host:                         juliophp.com
-- Server version:               10.6.22-MariaDB-cll-lve - MariaDB Server
-- Server OS:                    Linux
-- HeidiSQL Version:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for clickmind
CREATE DATABASE IF NOT EXISTS `clickmind` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci */;
USE `clickmind`;

-- Dumping structure for table clickmind.login_attempts
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table clickmind.login_attempts: ~3 rows (approximately)
INSERT INTO `login_attempts` (`id`, `email`, `phone`, `ip_address`, `attempt_time`) VALUES
	(1, NULL, '+18455417975', '71.167.57.75', '2025-10-06 11:19:44'),
	(2, 'juliophpx@gmail.com', NULL, '71.167.57.75', '2025-10-06 11:26:53'),
	(3, 'Juliophpx@gmail.com', NULL, '71.167.57.75', '2025-10-06 11:29:55');

-- Dumping structure for table clickmind.refresh_tokens
CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` text NOT NULL,
  `expiration` int(10) unsigned NOT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_unique` (`token`(255))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table clickmind.refresh_tokens: ~1 rows (approximately)
INSERT INTO `refresh_tokens` (`id`, `user_id`, `token`, `expiration`, `is_revoked`, `created_at`) VALUES
	(1, 21, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyMSwiaXNfcmVmcmVzaF90b2tlbiI6dHJ1ZSwiaWF0IjoxNzU5NzQ5NjE4LCJleHAiOjE3NjIzNDE2MTh9.rJm5tMczonFOCIc4DpuP9GjSSLDUALcxOlhONMwYRGU', 1762341618, 1, '2025-10-06 11:20:18'),
	(2, 22, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyMiwiaXNfcmVmcmVzaF90b2tlbiI6dHJ1ZSwiaWF0IjoxNzU5NzUwMDMxLCJleHAiOjE3NjIzNDIwMzF9.7THnd9aQ3NE_GVnd8xtZGCYjMPfSfccEhB9lpB7D6OQ', 1762342031, 0, '2025-10-06 11:27:11'),
	(3, 22, 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyMiwiaXNfcmVmcmVzaF90b2tlbiI6dHJ1ZSwiaWF0IjoxNzU5NzUwMjEzLCJleHAiOjE3NjIzNDIyMTN9.6-Mw0hXcCi31yEpcyJ6-pyE-Z6u6iHDnBm1fLvgLrzk', 1762342213, 1, '2025-10-06 11:30:13');

-- Dumping structure for table clickmind.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `level` varchar(100) DEFAULT 'user' COMMENT 'user, admin',
  `type` varchar(100) DEFAULT 'user' COMMENT 'user, customer',
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_unique` (`email`),
  UNIQUE KEY `phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table clickmind.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `level`, `type`, `password`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(21, 'Phone: 7975', NULL, '+18455417975', 'user', 'user', NULL, '2025-10-06 11:19:44', '2025-10-06 11:19:44', NULL),
	(22, 'juliophpx', 'juliophpx@gmail.com', NULL, 'user', 'user', NULL, '2025-10-06 11:26:53', '2025-10-06 11:26:53', NULL);

-- Dumping structure for table clickmind.user_button_clicks
CREATE TABLE IF NOT EXISTS `user_button_clicks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `button_id` varchar(255) NOT NULL,
  `click_count` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_button` (`user_id`,`button_id`),
  CONSTRAINT `user_button_clicks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table clickmind.user_button_clicks: ~7 rows (approximately)
INSERT INTO `user_button_clicks` (`id`, `user_id`, `button_id`, `click_count`, `created_at`, `updated_at`) VALUES
	(1, 21, 'button-feature-b', 2, '2025-10-06 11:25:46', '2025-10-06 11:25:56'),
	(2, 21, 'button-feature-a', 1, '2025-10-06 11:25:52', '2025-10-06 11:25:52'),
	(3, 21, 'button-cta', 3, '2025-10-06 11:25:52', '2025-10-06 11:25:56'),
	(4, 21, 'button-upgrade', 2, '2025-10-06 11:25:54', '2025-10-06 11:25:59'),
	(9, 22, 'button-feature-b', 2, '2025-10-06 11:27:14', '2025-10-06 11:27:57'),
	(10, 22, 'button-cta', 5, '2025-10-06 11:27:20', '2025-10-06 11:27:26'),
	(15, 22, 'button-feature-a', 2, '2025-10-06 11:27:29', '2025-10-06 11:30:18');

-- Dumping structure for table clickmind.user_token_invalidations
CREATE TABLE IF NOT EXISTS `user_token_invalidations` (
  `user_id` int(10) unsigned NOT NULL,
  `invalidated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table clickmind.user_token_invalidations: ~2 rows (approximately)
INSERT INTO `user_token_invalidations` (`user_id`, `invalidated_at`) VALUES
	(21, '2025-10-06 18:26:25'),
	(22, '2025-10-06 18:30:40');

-- Dumping structure for table clickmind.verification_codes
CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(250) DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `code` varchar(10) NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table clickmind.verification_codes: ~2 rows (approximately)
INSERT INTO `verification_codes` (`id`, `email`, `user_id`, `code`, `used`, `expires_at`, `created_at`) VALUES
	(1, 'juliophpx@gmail.com', NULL, '813815', 1, '2025-10-06 04:41:53', '2025-10-06 11:26:53'),
	(2, 'Juliophpx@gmail.com', NULL, '301344', 1, '2025-10-06 04:44:55', '2025-10-06 11:29:55');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
