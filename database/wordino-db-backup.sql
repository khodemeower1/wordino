-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: wordino-db:3306
-- Generation Time: Mar 30, 2023 at 06:11 PM
-- Server version: 10.6.8-MariaDB-1:10.6.8+maria~focal
-- PHP Version: 8.0.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wordino`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  `password` varchar(200) COLLATE utf8mb3_bin NOT NULL,
  `access_level` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(5) NOT NULL,
  `group_name` varchar(50) COLLATE utf8mb3_bin NOT NULL,
  `description` varchar(1000) COLLATE utf8mb3_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;

-- --------------------------------------------------------

--
-- Table structure for table `wordlists`
--

CREATE TABLE `wordlists` (
  `id` int(11) NOT NULL,
  `wordlist_name` varchar(50) COLLATE utf8mb3_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- --------------------------------------------------------

--
-- Table structure for table `wordlist_group`
--

CREATE TABLE `wordlist_group` (
  `id` int(11) NOT NULL,
  `wordlist_id` int(5) NOT NULL,
  `group_id` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- --------------------------------------------------------

--
-- Table structure for table `words`
--

CREATE TABLE `words` (
  `id` int(10) NOT NULL,
  `word` varchar(100) COLLATE utf8mb3_bin NOT NULL,
  `points` float NOT NULL,
  `vuln` tinyint(1) NOT NULL DEFAULT 0,
  `reference` varchar(500) COLLATE utf8mb3_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- --------------------------------------------------------

--
-- Table structure for table `word_author`
--

CREATE TABLE `word_author` (
  `id` int(11) NOT NULL,
  `word_id` int(10) NOT NULL,
  `author_id` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


-- --------------------------------------------------------

--
-- Table structure for table `word_wordlist`
--

CREATE TABLE `word_wordlist` (
  `id` int(11) NOT NULL,
  `word_id` int(10) NOT NULL,
  `wordlist_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wordlists`
--
ALTER TABLE `wordlists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wordlist_group`
--
ALTER TABLE `wordlist_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `words`
--
ALTER TABLE `words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `word_author`
--
ALTER TABLE `word_author`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `word_wordlist`
--
ALTER TABLE `word_wordlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wordlists`
--
ALTER TABLE `wordlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `wordlist_group`
--
ALTER TABLE `wordlist_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `words`
--
ALTER TABLE `words`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `word_author`
--
ALTER TABLE `word_author`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `word_wordlist`
--
ALTER TABLE `word_wordlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
