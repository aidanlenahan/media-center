-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 14, 2026 at 03:33 PM
-- Server version: 8.0.44-0ubuntu0.24.04.2
-- PHP Version: 8.3.6

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

CREATE TABLE `dev_settings` (
  `id` int NOT NULL,
  `debug_mode` tinyint(1) DEFAULT '0',
  `show_sql_queries` tinyint(1) DEFAULT '0',
  `log_all_actions` tinyint(1) DEFAULT '0',
  `bypass_time_restrictions` tinyint(1) DEFAULT '0',
  `test_mode` tinyint(1) DEFAULT '0',
  `allow_duplicate_passes` tinyint(1) DEFAULT '0',
  `email_override_address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `require_school_email` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dev_settings`
--

INSERT INTO `dev_settings` (`id`, `debug_mode`, `show_sql_queries`, `log_all_actions`, `bypass_time_restrictions`, `test_mode`, `allow_duplicate_passes`, `email_override_address`, `created_at`, `updated_at`, `require_school_email`) VALUES
(1, 0, 0, 0, 0, 0, 0, '', '2026-01-13 16:05:26', '2026-01-14 04:01:37', 1);

-- --------------------------------------------------------

--
-- Table structure for table `librarians`
--

CREATE TABLE `librarians` (
  `id` int NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('librarian','root') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'librarian',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `passes_archive` (
  `id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passes_current`
--

CREATE TABLE `passes_current` (
  `id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passes_current`
--

INSERT INTO `passes_current` (`id`, `first_name`, `last_name`, `email`, `teacher_name`, `mod`, `activities`, `agreement_checked`, `status`, `pass_code`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 'Aidan', 'Lenahan', 'aidanlenahan@outlook.com', 'Milonas', 1, '[\"Studying\"]', 1, 'rejected', '3393461008B2', NULL, '2026-01-13 04:03:13', '2026-01-13 05:20:02'),
(2, 'Aidan', 'Lenahan', 'aidanlenahan@outlook.com', 'Milonas', 1, '[\"Studying\"]', 1, 'approved', 'A6FA35F66833', '2026-01-13 05:20:53', '2026-01-13 05:20:29', '2026-01-13 05:20:53'),
(3, 'Aidan', 'Lenahan', 'aml.lenahan@gmail.com', 'Milonas', 2, '[\"Working on a project\",\"Reading\",\"Meeting with tutor\\/teacher\"]', 1, 'approved', '607918B05FD9', '2026-01-14 03:47:13', '2026-01-14 03:46:52', '2026-01-14 03:47:13'),
(4, 'll', 'll', 'jared896869686869684655457@students.rbrhs.org', 'M', 1, '[\"Meeting with tutor\\/teacher\"]', 1, 'rejected', '325BF04AEC3A', NULL, '2026-01-14 04:02:34', '2026-01-14 13:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `form_auto_open` tinyint(1) DEFAULT '0',
  `form_open_time` time DEFAULT NULL,
  `form_close_time` time DEFAULT NULL,
  `auto_approval` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `form_status_override` tinyint(1) DEFAULT '0',
  `form_status_manual` enum('open','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `recent_entries_limit` int DEFAULT '10',
  `disable_weekends` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `form_auto_open`, `form_open_time`, `form_close_time`, `auto_approval`, `created_at`, `updated_at`, `form_status_override`, `form_status_manual`, `recent_entries_limit`, `disable_weekends`) VALUES
(1, 1, '07:30:00', '14:30:00', 0, '2026-01-13 03:25:01', '2026-01-14 13:34:02', 0, 'open', 10, 1),
(2, 1, '07:30:00', '14:30:00', 0, '2026-01-13 16:05:26', '2026-01-13 16:05:26', 0, 'open', 10, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dev_settings`
--
ALTER TABLE `dev_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `librarians`
--
ALTER TABLE `librarians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `passes_archive`
--
ALTER TABLE `passes_archive`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pass_code` (`pass_code`),
  ADD KEY `pass_date` (`pass_date`),
  ADD KEY `first_name` (`first_name`),
  ADD KEY `last_name` (`last_name`);

--
-- Indexes for table `passes_current`
--
ALTER TABLE `passes_current`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pass_code` (`pass_code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dev_settings`
--
ALTER TABLE `dev_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `librarians`
--
ALTER TABLE `librarians`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `passes_archive`
--
ALTER TABLE `passes_archive`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passes_current`
--
ALTER TABLE `passes_current`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
