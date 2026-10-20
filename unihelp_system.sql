-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2026 at 07:09 PM
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
-- Database: `unihelp_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `availability`
--

CREATE TABLE `availability` (
  `availability_id` int(11) NOT NULL,
  `volunteer_id` varchar(10) NOT NULL,
  `day` date NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `comments` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_match`
--

CREATE TABLE `request_match` (
  `match_id` int(11) NOT NULL,
  `volunteer_id` varchar(10) NOT NULL,
  `request_id` int(11) NOT NULL,
  `matchStatus` enum('Pending','Accepted','Rejected') NOT NULL,
  `createAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill`
--

CREATE TABLE `skill` (
  `skill_id` int(11) NOT NULL,
  `volunteer_id` varchar(10) NOT NULL,
  `skillName` varchar(100) NOT NULL,
  `description` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contactNo` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_request`
--

CREATE TABLE `support_request` (
  `request_id` int(11) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `category` enum('Academic','Technology','New Student','General') NOT NULL,
  `description` varchar(500) NOT NULL,
  `preferredDate` date NOT NULL,
  `preferredTime` time NOT NULL,
  `supportMode` enum('Virtual','Face-to-Face') NOT NULL,
  `status` enum('Pending','Accepted','Rejected') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_session`
--

CREATE TABLE `support_session` (
  `session_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `volunteer_id` varchar(10) NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  `supportMode` enum('Virtual','Face-to-Face') NOT NULL,
  `status` enum('Scheduled','Rescheduled','Completed','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer`
--

CREATE TABLE `volunteer` (
  `volunteer_id` varchar(10) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `skill` enum('Virtual','Face to Face') NOT NULL,
  `availability` enum('Active','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_skill`
--

CREATE TABLE `volunteer_skill` (
  `volunteer_skill_id` int(11) NOT NULL,
  `volunteer_id` varchar(10) NOT NULL,
  `skil_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `availability`
--
ALTER TABLE `availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `request_match`
--
ALTER TABLE `request_match`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `volunteer_id` (`volunteer_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `skill`
--
ALTER TABLE `skill`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `support_request`
--
ALTER TABLE `support_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `support_session`
--
ALTER TABLE `support_session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- Indexes for table `volunteer`
--
ALTER TABLE `volunteer`
  ADD PRIMARY KEY (`volunteer_id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `volunteer_skill`
--
ALTER TABLE `volunteer_skill`
  ADD PRIMARY KEY (`volunteer_skill_id`),
  ADD KEY `skil_id` (`skil_id`),
  ADD KEY `volunteer_id` (`volunteer_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `availability`
--
ALTER TABLE `availability`
  ADD CONSTRAINT `availability_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `support_session` (`session_id`),
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `request_match`
--
ALTER TABLE `request_match`
  ADD CONSTRAINT `request_match_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`),
  ADD CONSTRAINT `request_match_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `support_request` (`request_id`);

--
-- Constraints for table `skill`
--
ALTER TABLE `skill`
  ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`);

--
-- Constraints for table `support_request`
--
ALTER TABLE `support_request`
  ADD CONSTRAINT `support_request_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `support_session`
--
ALTER TABLE `support_session`
  ADD CONSTRAINT `support_session_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `support_request` (`request_id`),
  ADD CONSTRAINT `support_session_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`);

--
-- Constraints for table `volunteer`
--
ALTER TABLE `volunteer`
  ADD CONSTRAINT `volunteer_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `volunteer_skill`
--
ALTER TABLE `volunteer_skill`
  ADD CONSTRAINT `volunteer_skill_ibfk_1` FOREIGN KEY (`skil_id`) REFERENCES `skill` (`skill_id`),
  ADD CONSTRAINT `volunteer_skill_ibfk_2` FOREIGN KEY (`volunteer_id`) REFERENCES `volunteer` (`volunteer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
