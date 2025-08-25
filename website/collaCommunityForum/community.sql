-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2025 at 05:44 PM
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
-- Database: `forum`
--

-- --------------------------------------------------------

--
-- Table structure for table `community`
--

CREATE TABLE `community` (
  `community_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `picture_url` text DEFAULT NULL,
  `creator_id` varchar(20) DEFAULT NULL,
  `visibility` enum('Public','Private','Anonymous') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community`
--

INSERT INTO `community` (`community_id`, `name`, `description`, `picture_url`, `creator_id`, `visibility`, `created_at`, `updated_at`) VALUES
('COM2025030001', 'Tech Enthusiasts', 'A community for tech lovers.', NULL, 'USR2025030001', 'Public', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030002', 'Book Club', 'Discussing books and literature.', NULL, 'USR2025030002', 'Public', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030003', 'Gaming Hub', 'A place for gamers.', NULL, 'USR2025030003', 'Private', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030004', 'Music Fans', 'Share and discuss music.', NULL, 'USR2025030004', 'Public', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030005', 'Fitness Freaks', 'Health and fitness discussions.', NULL, 'USR2025030005', 'Private', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030006', 'Startup Founders', 'Networking for entrepreneurs.', NULL, 'USR2025030006', 'Anonymous', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030007', 'Movie Lovers', 'Movies, reviews, and discussions.', NULL, 'USR2025030007', 'Public', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030008', 'Photography', 'For photography enthusiasts.', NULL, 'USR2025030008', 'Private', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030009', 'Foodies', 'Exploring different cuisines.', NULL, 'USR2025030009', 'Public', '2025-03-10 16:27:40', '2025-03-10 16:27:40'),
('COM2025030010', 'Coding Challenges', 'A community for coding contests.', NULL, 'USR2025030010', 'Anonymous', '2025-03-10 16:27:40', '2025-03-10 16:27:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `community`
--
ALTER TABLE `community`
  ADD PRIMARY KEY (`community_id`),
  ADD KEY `creator_id` (`creator_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `community`
--
ALTER TABLE `community`
  ADD CONSTRAINT `community_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
