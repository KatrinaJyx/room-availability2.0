-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 11:10 AM
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
-- Database: `room_availability`
--

-- --------------------------------------------------------

--
-- Table structure for table `authorized_students`
--

CREATE TABLE `authorized_students` (
  `id` int(11) NOT NULL,
  `student_id_number` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authorized_students`
--

INSERT INTO `authorized_students` (`id`, `student_id_number`, `full_name`, `section`, `purpose`, `room_id`, `valid_from`, `valid_until`, `status`, `requested_at`, `approved_at`) VALUES
(2, '105', 'mark benedict selda', 'bsis3b', 'Practice', 10, '2026-10-03 22:20:00', '2026-10-03 23:00:00', 'approved', '2026-10-03 14:17:44', '2026-10-03 14:18:11'),
(3, '107', 'Selda, Mark Benedict', 'BSIS3B', 'Practice', 14, '2026-10-03 22:55:00', '2026-10-03 23:59:00', 'approved', '2026-10-03 14:54:13', '2026-10-03 14:54:29'),
(4, '24-06343', 'Ignacio, Katrina Joyce', 'BSIS 3B', 'Class', 17, '2026-10-07 15:00:00', '2026-10-07 17:00:00', 'approved', '2026-10-07 06:08:46', '2026-10-07 06:09:43'),
(5, '24-06342', 'Sj Mabansag', 'BSIS3b', 'Class', 11, '2026-10-12 14:00:00', '2026-10-12 15:00:00', 'approved', '2026-10-12 06:19:27', '2026-10-12 06:20:22'),
(6, '123', 'student1', 'BSIS3B', 'Defence', 15, '2026-10-13 05:00:00', '2026-10-13 07:00:00', 'approved', '2026-10-12 18:53:43', '2026-10-12 18:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `building` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_name`, `building`, `capacity`) VALUES
(1, 'Room 101', 'Main Building', 40),
(2, 'Room 105', 'Main Building', 35),
(4, 'Room 101', 'Kalap Building', 40),
(5, 'Room 102', 'Kalap Building', 40),
(6, 'Room 103', 'Kalap Building', 40),
(7, 'Room 104', 'Kalap Building', 40),
(8, 'Room 105', 'Kalap Building', 40),
(9, 'Room 201', 'Kalap Building', 40),
(10, 'Room 202', 'Kalap Building', 40),
(11, 'Room 203', 'Kalap Building', 40),
(12, 'Room 204', 'Kalap Building', 40),
(13, 'Room 205', 'Kalap Building', 40),
(14, 'ComLab', 'Main Building', 40),
(15, 'iLab', 'Main Building', 40),
(16, 'Multimedia', 'Main Building', 40),
(17, 'Math Room', 'Main Building', 40),
(18, 'BPA Room', 'Main Building', 40),
(19, 'Sned Room', 'Main Building', 40),
(20, 'Science Laboratory', 'Main Building', 40),
(21, 'Kalap Hall', 'Kalap Building', 200);

-- --------------------------------------------------------

--
-- Table structure for table `room_usage`
--

CREATE TABLE `room_usage` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `user_type` enum('teacher','student') NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `student_id_number` varchar(30) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_usage`
--

INSERT INTO `room_usage` (`id`, `schedule_id`, `room_id`, `user_type`, `teacher_id`, `student_id_number`, `user_name`, `section`, `purpose`, `start_time`, `end_time`, `created_at`) VALUES
(1, NULL, 1, 'teacher', 1, NULL, 'Elma Jane L. Mitante', 'BSIS 2A', 'Class', '2026-09-29 09:25:57', '2026-09-29 10:25:57', '2026-09-29 01:35:57'),
(2, NULL, 2, 'teacher', 2, NULL, 'Mark Benedict Selda', 'BSIS 2B', 'Class', '2026-09-29 09:55:57', '2026-09-29 10:55:57', '2026-09-29 01:35:57'),
(4, NULL, 18, 'teacher', 1, NULL, 'Elma Jane L. Mitante', 'BSIS3B', 'Class', '2026-10-03 20:40:00', '2026-10-03 20:50:00', '2026-10-03 12:40:08'),
(5, NULL, 14, 'teacher', 1, NULL, 'Elma Jane L. Mitante', 'BSIS 3B', 'Class', '2026-10-03 21:00:00', '2026-10-03 22:00:00', '2026-10-03 12:40:47'),
(6, NULL, 10, 'student', NULL, '105', 'mark benedict selda', 'bsis3b', 'Practice', '2026-10-03 22:20:00', '2026-10-03 23:00:00', '2026-10-03 14:29:11'),
(7, NULL, 18, 'teacher', 2, NULL, 'Mark Benedict Selda', 'BSIS3B', 'Class', '2026-10-03 23:00:00', '2026-10-03 23:59:00', '2026-10-03 14:53:07'),
(8, NULL, 14, 'student', NULL, '107', 'Selda, Mark Benedict', 'BSIS3B', 'Practice', '2026-10-03 22:55:00', '2026-10-03 23:59:00', '2026-10-03 14:54:46'),
(9, NULL, 15, 'teacher', 3, NULL, 'Robin Almarez', 'BSIS 3B', 'Bootcamp', '2026-10-07 13:00:00', '2026-10-07 17:00:00', '2026-10-07 06:05:38'),
(10, NULL, 17, 'student', NULL, '24-06343', 'Ignacio, Katrina Joyce', 'BSIS 3B', 'Class', '2026-10-07 15:00:00', '2026-10-07 17:00:00', '2026-10-07 06:11:34'),
(11, NULL, 11, 'student', NULL, '24-06342', 'Sj Mabansag', 'BSIS3b', 'Class', '2026-10-12 14:00:00', '2026-10-12 15:00:00', '2026-10-12 06:20:55'),
(12, 1, 14, 'teacher', 2, NULL, 'Mark Benedict Selda', 'BSIS3B', 'Class', '2026-10-13 03:00:00', '2026-10-13 16:00:00', '2026-10-12 18:46:17'),
(13, 1, 14, 'teacher', 2, NULL, 'Mark Benedict Selda', 'BSIS3B', 'Class', '2026-10-20 03:00:00', '2026-10-20 16:00:00', '2026-10-12 18:46:17'),
(14, 1, 14, 'teacher', 2, NULL, 'Mark Benedict Selda', 'BSIS3B', 'Class', '2026-10-27 03:00:00', '2026-10-27 16:00:00', '2026-10-12 18:46:17'),
(15, 5, 15, 'teacher', 4, NULL, 'Katrina Joyce Ignacio', 'BSIS 3B', 'Class', '2026-10-13 05:00:00', '2026-10-13 07:00:00', '2026-10-12 18:52:00'),
(16, 5, 15, 'teacher', 4, NULL, 'Katrina Joyce Ignacio', 'BSIS 3B', 'Class', '2026-10-20 05:00:00', '2026-10-20 07:00:00', '2026-10-12 18:52:00'),
(17, 5, 15, 'teacher', 4, NULL, 'Katrina Joyce Ignacio', 'BSIS 3B', 'Class', '2026-10-27 05:00:00', '2026-10-27 07:00:00', '2026-10-12 18:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `employee_id`, `full_name`, `department`) VALUES
(1, 'T-001', 'Elma Jane L. Mitante', 'IT Department'),
(2, 'T-002', 'Mark Benedict Selda', 'IT Department'),
(3, 'T-003', 'Robin Almarez', 'BSIS'),
(4, 'T-004', 'Katrina Joyce Ignacio', 'BSIS');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedules`
--

CREATE TABLE `teacher_schedules` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_schedules`
--

INSERT INTO `teacher_schedules` (`id`, `teacher_id`, `room_id`, `day_of_week`, `start_time`, `end_time`, `section`, `purpose`, `valid_from`, `valid_until`, `created_at`) VALUES
(1, 2, 14, 2, '03:00:00', '16:00:00', 'BSIS3B', 'Class', '2026-10-12', '2026-10-30', '2026-10-12 18:46:17'),
(3, 3, 14, 2, '03:00:00', '05:00:00', '', 'Class', '2026-10-12', '2026-10-20', '2026-10-12 18:49:09'),
(5, 4, 15, 2, '05:00:00', '07:00:00', 'BSIS 3B', 'Class', '2026-10-12', '2026-10-30', '2026-10-12 18:52:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authorized_students`
--
ALTER TABLE `authorized_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_usage`
--
ALTER TABLE `room_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `fk_usage_schedule` (`schedule_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `room_id` (`room_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authorized_students`
--
ALTER TABLE `authorized_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `room_usage`
--
ALTER TABLE `room_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `authorized_students`
--
ALTER TABLE `authorized_students`
  ADD CONSTRAINT `authorized_students_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_usage`
--
ALTER TABLE `room_usage`
  ADD CONSTRAINT `fk_usage_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `teacher_schedules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `room_usage_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_usage_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  ADD CONSTRAINT `teacher_schedules_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_schedules_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
