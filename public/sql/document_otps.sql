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
-- Table structure for table `document_otps`
--

CREATE TABLE `document_otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp` varchar(6) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_otps`
--

INSERT INTO `document_otps` (`id`, `document_id`, `email`, `otp`, `expires_at`, `verified_at`, `created_at`, `updated_at`) VALUES
(4, 2, 'warbase777@gmail.com', '949443', '2026-04-08 14:09:59', '2026-04-08 08:39:59', '2026-04-08 08:39:44', '2026-04-08 08:39:59'),
(6, 3, 'warbase777@gmail.com', '056377', '2026-04-08 14:18:49', '2026-04-08 08:48:49', '2026-04-08 08:48:01', '2026-04-08 08:48:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `document_otps`
--
ALTER TABLE `document_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_otps_document_id_foreign` (`document_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `document_otps`
--
ALTER TABLE `document_otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_otps`
--
ALTER TABLE `document_otps`
  ADD CONSTRAINT `document_otps_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
