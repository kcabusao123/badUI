-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2026 at 03:45 PM
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
-- Database: `grand_inn`
--

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `room_number` tinyint(3) UNSIGNED NOT NULL,
  `room_type` enum('Standard','Deluxe','Suite','Family') NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `guest_name` varchar(120) NOT NULL,
  `guest_phone` varchar(30) DEFAULT NULL,
  `checkin_date` date NOT NULL,
  `checkout_date` date NOT NULL,
  `status` enum('reserved','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'reserved',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `room_number`, `room_type`, `price_per_night`, `guest_name`, `guest_phone`, `checkin_date`, `checkout_date`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'Standard', 800.00, 'Santos, Maria', '09171234567', '2026-02-20', '2026-02-24', 'checked_out', NULL, '2026-02-20 14:00:00', '2026-02-24 20:22:11'),
(2, 5, 'Deluxe', 1500.00, 'Reyes, Juan', '09281234567', '2026-02-22', '2026-02-25', 'cancelled', NULL, '2026-02-21 10:00:00', '2026-02-24 20:15:47'),
(3, 7, 'Suite', 3000.00, 'Cruz, Ana', '09351234567', '2026-02-18', '2026-02-22', 'checked_out', NULL, '2026-02-18 09:00:00', '2026-02-24 18:31:49'),
(4, 9, 'Family', 2000.00, 'Dela Cruz, Robert', '09461234567', '2026-02-23', '2026-02-26', 'reserved', NULL, '2026-02-22 08:00:00', '2026-02-24 18:31:49'),
(5, 1, 'Standard', 800.00, 'III', '50000008000', '2026-02-25', '2026-02-27', 'reserved', 1, '2026-02-24 22:00:11', '2026-02-24 22:00:11');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(10) UNSIGNED NOT NULL,
  `room_number` tinyint(3) UNSIGNED NOT NULL,
  `type` enum('Standard','Deluxe','Suite','Family') NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `type`, `price_per_night`, `description`, `is_active`) VALUES
(1, 1, 'Standard', 800.00, 'Standard room, ground floor, garden view', 1),
(2, 2, 'Standard', 800.00, 'Standard room, ground floor, street view', 1),
(3, 3, 'Standard', 800.00, 'Standard room, ground floor, courtyard view', 1),
(4, 4, 'Deluxe', 1500.00, 'Deluxe room, upper floor, city view', 1),
(5, 5, 'Deluxe', 1500.00, 'Deluxe room, upper floor, pool view', 1),
(6, 6, 'Deluxe', 1500.00, 'Deluxe room, upper floor, mountain view', 1),
(7, 7, 'Suite', 3000.00, 'Suite, top floor, panoramic view, king bed', 1),
(8, 8, 'Suite', 3000.00, 'Suite, top floor, panoramic view, twin beds', 1),
(9, 9, 'Family', 2000.00, 'Family room, ground floor, 2 rooms connected', 1),
(10, 10, 'Family', 2000.00, 'Family room, upper floor, 2 rooms connected', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$12$YGbji4CelN8hBGmpEhTlTOnatqmCmIBeKGm2WQMYlkC1k0q2WcNTG', 'Admin Manager', 'admin', '2026-02-24 20:05:01', '2026-02-24 21:56:41'),
(2, 'staff', '$2y$12$Cy4/eLe.FSbw0Ld4qspwDeD9kUe35RLPC3QEc6fn0qGnrz2nQ5lCq', 'Front Desk', 'staff', '2026-02-24 20:05:02', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_room` (`room_number`),
  ADD KEY `idx_dates` (`checkin_date`,`checkout_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
