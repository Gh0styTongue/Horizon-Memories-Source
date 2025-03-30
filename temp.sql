-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 30, 2025 at 12:47 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u448261159_hor`
--

-- --------------------------------------------------------

--
-- Table structure for table `capsules`
--

CREATE TABLE `capsules` (
  `capsule_id` varchar(22) NOT NULL,
  `user_id` int(11) NOT NULL,
  `storage_size` bigint(20) NOT NULL,
  `file_types` varchar(50) NOT NULL,
  `unlock_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `capsules`
--

INSERT INTO `capsules` (`capsule_id`, `user_id`, `storage_size`, `file_types`, `unlock_date`, `created_at`) VALUES
('gmzlvo-9vtlnu-ec31kxhz', 1, 262144000, 'photos,text', '2025-05-28 17:16:00', '2025-03-30 00:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `files_capsule`
--

CREATE TABLE `files_capsule` (
  `id` int(11) NOT NULL,
  `capsule_id` varchar(22) NOT NULL,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_date` datetime DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT current_timestamp(),
  `file_blob` mediumblob NOT NULL,
  `byte_size` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `family_last_name` varchar(100) NOT NULL,
  `owner_first_name` varchar(100) NOT NULL,
  `plan_type` enum('free','premium') NOT NULL,
  `card_number` varchar(20) DEFAULT NULL,
  `card_expiry` varchar(5) DEFAULT NULL,
  `card_cvv` varchar(4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `phone`, `password`, `family_last_name`, `owner_first_name`, `plan_type`, `card_number`, `card_expiry`, `card_cvv`, `created_at`) VALUES
(1, 'demo@demo.com', '0000000000', '', 'Demo', 'Demo', 'free', NULL, NULL, NULL, '2025-03-30 00:04:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `capsules`
--
ALTER TABLE `capsules`
  ADD PRIMARY KEY (`capsule_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `files_capsule`
--
ALTER TABLE `files_capsule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `capsule_id` (`capsule_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `files_capsule`
--
ALTER TABLE `files_capsule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `capsules`
--
ALTER TABLE `capsules`
  ADD CONSTRAINT `capsules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files_capsule`
--
ALTER TABLE `files_capsule`
  ADD CONSTRAINT `files_capsule_ibfk_1` FOREIGN KEY (`capsule_id`) REFERENCES `capsules` (`capsule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_capsule_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
