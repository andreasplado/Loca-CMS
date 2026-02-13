-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: hoggar.elkdata.ee
-- Generation Time: Feb 13, 2026 at 07:28 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vhost137745s3`
--

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `filepath` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `filename`, `filepath`, `uploaded_at`) VALUES
(1, '698f3e790398b.png', 'uploads/698f3e790398b.png', '2026-02-13 15:08:41'),
(2, '698f45bd05ba4.png', 'uploads/698f45bd05ba4.png', '2026-02-13 15:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`) VALUES
(1, 'Home', NULL, '[{\"id\":1770997801453,\"type\":\"html\",\"content\":\"<li>Ma tahan sind muss</li>\\n<img src=\\\"lol.png\\\"/><div>Lol</div>\",\"css\":\"\"},{\"id\":1770997206319,\"type\":\"text\",\"content\":\"Thanks to you i can see muss\",\"css\":\"\"},{\"id\":1770998593429,\"type\":\"grid\",\"content\":\"vdvvvvvdvvvv\",\"css\":\"\",\"columns\":[{\"width\":\"50%\",\"content\":\"FGGFGFGFGF\"},{\"width\":\"50%\",\"content\":\"REREREER\"},{\"width\":\"50%\",\"content\":\"GFGF\"}]},{\"id\":1770999067268,\"type\":\"grid\",\"content\":\"\",\"columns\":[]},{\"id\":1770999070204,\"type\":\"image\",\"content\":\"\",\"columns\":[]},{\"id\":1770999197244,\"type\":\"grid\",\"content\":\"\",\"columns\":[{\"width\":\"50%\",\"blocks\":[]},{\"width\":\"50%\",\"blocks\":[]}]},{\"id\":1770999198882,\"type\":\"grid\",\"content\":\"\",\"columns\":[{\"width\":\"50%\",\"blocks\":[]},{\"width\":\"50%\",\"blocks\":[]}]},{\"id\":1770999201210,\"type\":\"image\",\"content\":\"\",\"columns\":[]},{\"id\":1770999201778,\"type\":\"grid\",\"content\":\"\",\"columns\":[{\"width\":\"50%\",\"blocks\":[]},{\"width\":\"50%\",\"blocks\":[]}]},{\"id\":1770999202531,\"type\":\"text\",\"content\":\"\",\"columns\":[]},{\"id\":1770999208382,\"type\":\"image\",\"content\":\"\",\"columns\":[]}]'),
(7, 'Test', 'test', '[]');

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

CREATE TABLE `plugins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `version` varchar(50) DEFAULT '1.0.0',
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `plugins`
--

INSERT INTO `plugins` (`id`, `name`, `slug`, `is_active`, `version`, `description`) VALUES
(2, 'Hello-world', 'hello-world', 1, '1.0.0', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('home_page_id', '1'),
('site_title', 'Locawork site');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','viewer') DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '$2y$10$lJUrtk8oykMPrKMGh7kLr.ntOtuQ8BpAVkKwhVE.xDk8knWp763bK', 'admin'),
(2, 'test', '$2y$10$Ui5fs.4XkOe7NunH0YMkC.f4hBZ996yoqCSgB7JJl4CbIFqr7sIc6', 'admin'),
(5, 'Jou', '$2y$10$/jgN4X/pmIMbSiR2vLatJOfgCSvooK3jVrq1qq8CTiRW6wRkS4raa', 'viewer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `plugins`
--
ALTER TABLE `plugins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
