-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 08, 2026 at 04:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `filemanager`
--

-- --------------------------------------------------------

--
-- Table structure for table `document_views`
--

CREATE TABLE `document_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_views`
--

INSERT INTO `document_views` (`id`, `document_id`, `email`, `verified_at`, `viewed_at`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES
(1, 2, 'warbase777@gmail.com', '2026-04-08 08:39:59', NULL, NULL, NULL, '2026-04-08 08:39:59', '2026-04-08 08:39:59'),
(2, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:39:59', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:39:59', '2026-04-08 08:39:59'),
(3, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:40:22', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:40:22', '2026-04-08 08:40:22'),
(4, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:40:24', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:40:24', '2026-04-08 08:40:24'),
(5, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:41:11', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:41:11', '2026-04-08 08:41:11'),
(6, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:41:11', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:41:11', '2026-04-08 08:41:11'),
(7, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:42:00', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:42:00', '2026-04-08 08:42:00'),
(8, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:42:50', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:42:50', '2026-04-08 08:42:50'),
(9, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:43:01', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:43:01', '2026-04-08 08:43:01'),
(10, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:44:33', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:44:33', '2026-04-08 08:44:33'),
(11, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:47:53', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:47:53', '2026-04-08 08:47:53'),
(12, 2, 'warbase777@gmail.com', NULL, '2026-04-08 08:47:56', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:47:56', '2026-04-08 08:47:56'),
(13, 3, 'warbase777@gmail.com', '2026-04-08 08:48:49', NULL, NULL, NULL, '2026-04-08 08:48:49', '2026-04-08 08:48:49'),
(14, 3, 'warbase777@gmail.com', NULL, '2026-04-08 08:48:49', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:48:49', '2026-04-08 08:48:49'),
(15, 3, 'warbase777@gmail.com', NULL, '2026-04-08 08:48:54', '127.0.0.1', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2026-04-08 08:48:54', '2026-04-08 08:48:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `document_views`
--
ALTER TABLE `document_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_views_document_id_foreign` (`document_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `document_views`
--
ALTER TABLE `document_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_views`
--
ALTER TABLE `document_views`
  ADD CONSTRAINT `document_views_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
