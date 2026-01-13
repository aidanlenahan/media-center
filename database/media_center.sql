-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 13, 2026 at 04:20 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `media_center`
--

-- --------------------------------------------------------

--
-- Table structure for table `dev_settings`
--

DROP TABLE IF EXISTS `dev_settings`;
CREATE TABLE IF NOT EXISTS `dev_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `debug_mode` tinyint(1) DEFAULT '0',
  `show_sql_queries` tinyint(1) DEFAULT '0',
  `log_all_actions` tinyint(1) DEFAULT '0',
  `bypass_time_restrictions` tinyint(1) DEFAULT '0',
  `test_mode` tinyint(1) DEFAULT '0',
  `allow_duplicate_passes` tinyint(1) DEFAULT '0',
  `email_override_address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dev_settings`
--

INSERT INTO `dev_settings` (`id`, `debug_mode`, `show_sql_queries`, `log_all_actions`, `bypass_time_restrictions`, `test_mode`, `allow_duplicate_passes`, `email_override_address`, `created_at`, `updated_at`) VALUES
(1, 0, 0, 0, 0, 0, 0, NULL, '2026-01-13 16:05:26', '2026-01-13 16:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `librarians`
--

DROP TABLE IF EXISTS `librarians`;
CREATE TABLE IF NOT EXISTS `librarians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('librarian','root') COLLATE utf8mb4_unicode_ci DEFAULT 'librarian',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `librarians`
--

INSERT INTO `librarians` (`id`, `username`, `password_hash`, `email`, `role`, `created_at`, `updated_at`) VALUES
(1, 'abarry', '$2y$10$BA08dnUQjdBx8i9YW.DZTe9ztnVeUHZ95Pck1KFotDBtwqiAZK5ja', 'librarian@school.local', 'librarian', '2026-01-13 03:25:01', '2026-01-13 05:47:01'),
(5, 'root', '$2y$10$SVJQP7kC.15HU5q7K9hE7eHHclou/HjN8k6pV2JJi.n7ZKKPoGWla', 'developer@school.local', 'root', '2026-01-13 16:17:57', '2026-01-13 16:17:57'),
(4, 'admin', '$2y$10$iDZBQQhcVV39jAGDwbxJJODARM0PmO6IH4BaVS7wcLgK8a6Epng0K', 'librarian@school.local', 'librarian', '2026-01-13 16:17:57', '2026-01-13 16:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `passes_archive`
--

DROP TABLE IF EXISTS `passes_archive`;
CREATE TABLE IF NOT EXISTS `passes_archive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mod` int NOT NULL,
  `activities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `agreement_checked` tinyint(1) DEFAULT '0',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `pass_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `pass_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pass_code` (`pass_code`),
  KEY `pass_date` (`pass_date`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passes_current`
--

DROP TABLE IF EXISTS `passes_current`;
CREATE TABLE IF NOT EXISTS `passes_current` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mod` int NOT NULL,
  `activities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `agreement_checked` tinyint(1) DEFAULT '0',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `pass_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pass_code` (`pass_code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passes_current`
--

INSERT INTO `passes_current` (`id`, `first_name`, `last_name`, `email`, `teacher_name`, `mod`, `activities`, `agreement_checked`, `status`, `pass_code`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 'Aidan', 'Lenahan', 'aidanlenahan@outlook.com', 'Milonas', 1, '[\"Studying\"]', 1, 'rejected', '3393461008B2', NULL, '2026-01-13 04:03:13', '2026-01-13 05:20:02'),
(2, 'Aidan', 'Lenahan', 'aidanlenahan@outlook.com', 'Milonas', 1, '[\"Studying\"]', 1, 'approved', 'A6FA35F66833', '2026-01-13 05:20:53', '2026-01-13 05:20:29', '2026-01-13 05:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `form_auto_open` tinyint(1) DEFAULT '0',
  `form_open_time` time DEFAULT NULL,
  `form_close_time` time DEFAULT NULL,
  `auto_approval` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `form_status_override` tinyint(1) DEFAULT '0',
  `form_status_manual` enum('open','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `recent_entries_limit` int DEFAULT '10',
  `disable_weekends` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `form_auto_open`, `form_open_time`, `form_close_time`, `auto_approval`, `created_at`, `updated_at`, `form_status_override`, `form_status_manual`, `recent_entries_limit`, `disable_weekends`) VALUES
(1, 1, '07:30:00', '14:30:00', 0, '2026-01-13 03:25:01', '2026-01-13 14:02:50', 0, 'open', 10, 1),
(2, 1, '07:30:00', '14:30:00', 0, '2026-01-13 16:05:26', '2026-01-13 16:05:26', 0, 'open', 10, 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
