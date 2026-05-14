-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 02:38 PM
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
-- Database: `barangay_helpdesk`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `actor_type` enum('admin','user') NOT NULL,
  `actor_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `actor_type`, `actor_id`, `action`, `target_type`, `target_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'user', 1, 'Registered account', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:22:33'),
(2, 'user', 1, 'Logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:22:44'),
(3, 'user', 1, 'Submitted concern', 'concern', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:23:49'),
(4, 'admin', 1, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:25:38'),
(5, 'admin', 1, 'Replied to concern', 'concern', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:26:36'),
(6, 'user', 1, 'Added reply to concern', 'concern', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:26:56'),
(7, 'admin', 1, 'Created announcement', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 11:28:20'),
(8, 'user', 1, 'Logged out', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 12:16:28'),
(9, 'admin', 1, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:53:23'),
(10, 'admin', 1, 'Toggled resident status', 'user', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:53:49'),
(11, 'admin', 1, 'Toggled resident status', 'user', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:53:55'),
(12, 'admin', 1, 'Created official account', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:55:32'),
(13, 'admin', 1, 'Admin logged out', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:56:39'),
(14, 'admin', 2, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 15:56:51'),
(15, 'admin', 2, 'Admin logged out', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:03:29'),
(16, 'admin', 1, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:03:41'),
(17, 'user', 2, 'Registered account', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:21:08'),
(18, 'user', 2, 'Logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:21:19'),
(19, 'admin', 1, 'Updated concern status', 'concern', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:21:50'),
(20, 'admin', 1, 'Admin logged out', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:23:01'),
(21, 'admin', 2, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:23:11'),
(22, 'user', 2, 'Logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:29:23'),
(23, 'user', 2, 'Submitted concern', 'concern', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:30:15'),
(24, 'admin', 2, 'Updated concern status', 'concern', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 16:30:45'),
(25, 'admin', 1, 'Admin logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 20:35:41'),
(26, 'user', 2, 'Logged in', '', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-12 20:37:08');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','staff','viewer') DEFAULT 'staff',
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `password`, `role`, `position`, `phone`, `profile_photo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Barangay Captain', 'admin@barangayhelpdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Barangay Captain', NULL, NULL, 1, '2026-05-05 13:44:33', '2026-05-05 16:45:59'),
(2, 'Charie Fe Estanel', 'chariefeestanel@gmail.com', '$2y$12$VdxZIbeizcVKnG4pVx7ozeCQz5m4hRHYj.ntbK0xKwRzuF14tj90K', 'staff', 'Secretary', '0948 902 3883', NULL, 1, '2026-05-12 15:55:32', '2026-05-12 15:55:32');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `admin_id`, `title`, `content`, `image`, `is_pinned`, `is_published`, `published_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cash Assistance', 'No requirements!', NULL, 0, 1, '2026-05-08 11:28:20', '2026-05-23 08:00:00', '2026-05-08 11:28:20', '2026-05-08 11:28:20');

-- --------------------------------------------------------

--
-- Table structure for table `concerns`
--

CREATE TABLE `concerns` (
  `id` int(11) NOT NULL,
  `tracking_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','resolved','rejected') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concerns`
--

INSERT INTO `concerns` (`id`, `tracking_code`, `user_id`, `category_id`, `title`, `description`, `location`, `priority`, `status`, `assigned_to`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 'BHD-D43E12B2', 1, 2, 'Ambot', 'Makaperwisyo na kaayo ang baho sa kanal', 'Near Aling Nena house', 'medium', 'resolved', NULL, '2026-05-12 16:21:50', '2026-05-08 11:23:49', '2026-05-12 16:21:50'),
(2, 'BHD-14863C25', 2, 5, 'Makalagot', 'Guba ang dalan diri dapit sa Purok 1', 'Purok 1', 'high', 'in_progress', 2, NULL, '2026-05-12 16:30:15', '2026-05-12 16:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `concern_attachments`
--

CREATE TABLE `concern_attachments` (
  `id` int(11) NOT NULL,
  `concern_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `concern_categories`
--

CREATE TABLE `concern_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bi-question-circle',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_categories`
--

INSERT INTO `concern_categories` (`id`, `name`, `description`, `icon`, `is_active`, `created_at`) VALUES
(1, 'Streetlight Issue', 'Broken or malfunctioning streetlights', 'bi-lightbulb', 1, '2026-05-05 13:44:33'),
(2, 'Drainage Problem', 'Clogged or damaged drainage systems', 'bi-water', 1, '2026-05-05 13:44:33'),
(3, 'Garbage Collection', 'Missed or delayed garbage collection', 'bi-trash', 1, '2026-05-05 13:44:33'),
(4, 'Noise Complaint', 'Disturbances from noise sources', 'bi-volume-up', 1, '2026-05-05 13:44:33'),
(5, 'Road Damage', 'Potholes, cracks, or damaged roads', 'bi-sign-stop', 1, '2026-05-05 13:44:33'),
(6, 'Public Safety', 'Safety hazards in public areas', 'bi-shield-exclamation', 1, '2026-05-05 13:44:33'),
(7, 'Other', 'Other concerns not listed above', 'bi-three-dots', 1, '2026-05-05 13:44:33');

-- --------------------------------------------------------

--
-- Table structure for table `concern_replies`
--

CREATE TABLE `concern_replies` (
  `id` int(11) NOT NULL,
  `concern_id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_replies`
--

INSERT INTO `concern_replies` (`id`, `concern_id`, `sender_type`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 'admin', 1, 'Your concern is in progress.', 1, '2026-05-08 11:26:36'),
(2, 1, 'user', 1, 'Omkieee', 1, '2026-05-08 11:26:56');

-- --------------------------------------------------------

--
-- Table structure for table `concern_status_history`
--

CREATE TABLE `concern_status_history` (
  `id` int(11) NOT NULL,
  `concern_id` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changer_type` enum('admin','user') DEFAULT 'admin',
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `concern_status_history`
--

INSERT INTO `concern_status_history` (`id`, `concern_id`, `changed_by`, `changer_type`, `old_status`, `new_status`, `note`, `changed_at`) VALUES
(1, 1, 1, 'user', NULL, 'pending', 'Concern submitted', '2026-05-08 11:23:49'),
(2, 1, 1, 'admin', 'pending', 'resolved', '', '2026-05-12 16:21:50'),
(3, 2, 2, 'user', NULL, 'pending', 'Concern submitted', '2026-05-12 16:30:15'),
(4, 2, 2, 'admin', 'pending', 'in_progress', '', '2026-05-12 16:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `purok` varchar(100) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `purok`, `profile_photo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bert Nichole Estrella', 'bertestrella277@gmail.com', '$2y$12$KbcJJY0uSfBOXpT1LGzRseRNqy9o.r.jUZDHzX2Sq6tAe2R8RDVhW', '0948 902 3883', 'Bayawan City, Negros Oriental', 'Purok 2', NULL, 1, '2026-05-08 11:22:33', '2026-05-12 15:53:55'),
(2, 'Geraldine Hepolito', 'emeeme@gmail.com', '$2y$12$9gBPajFnBGPd3iuAijzKEOGAq0IBFLfKNwGWMuX0wBIWhJq70dxNa', '09123123122', 'Negros Oriental', 'Purok 1', NULL, 1, '2026-05-12 16:21:08', '2026-05-12 16:21:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_actor` (`actor_type`,`actor_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `concerns`
--
ALTER TABLE `concerns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_code` (`tracking_code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `idx_concerns_user` (`user_id`),
  ADD KEY `idx_concerns_status` (`status`),
  ADD KEY `idx_concerns_priority` (`priority`),
  ADD KEY `idx_concerns_tracking` (`tracking_code`);

--
-- Indexes for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `concern_id` (`concern_id`);

--
-- Indexes for table `concern_categories`
--
ALTER TABLE `concern_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `concern_replies`
--
ALTER TABLE `concern_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_replies_concern` (`concern_id`);

--
-- Indexes for table `concern_status_history`
--
ALTER TABLE `concern_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `concern_id` (`concern_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `concerns`
--
ALTER TABLE `concerns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `concern_categories`
--
ALTER TABLE `concern_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `concern_replies`
--
ALTER TABLE `concern_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `concern_status_history`
--
ALTER TABLE `concern_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `concerns`
--
ALTER TABLE `concerns`
  ADD CONSTRAINT `concerns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `concerns_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `concern_categories` (`id`),
  ADD CONSTRAINT `concerns_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `concern_attachments`
--
ALTER TABLE `concern_attachments`
  ADD CONSTRAINT `concern_attachments_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `concern_replies`
--
ALTER TABLE `concern_replies`
  ADD CONSTRAINT `concern_replies_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `concern_status_history`
--
ALTER TABLE `concern_status_history`
  ADD CONSTRAINT `concern_status_history_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
