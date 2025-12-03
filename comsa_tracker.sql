-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 10, 2025 at 10:16 AM
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
-- Database: `comsa_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('executive','representative','committee_head','committee_member') NOT NULL DEFAULT 'committee_member',
  `type` varchar(50) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `student_number`, `password`, `role`, `type`, `is_admin`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '12345', '$2y$10$nu/1hLaEYWBovoz.YcpoFemctQFWwvK51uZEGb5Vr7RCWPOgTt0LK', '', 'CSIT', 1, '2025-11-10 07:56:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- Create Events Table
CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(100) NOT NULL,
  title VARCHAR(255),
  sas_f6 BOOLEAN DEFAULT 0,
  transmittal BOOLEAN DEFAULT 0,
  invitation BOOLEAN DEFAULT 0,
  endorsement BOOLEAN DEFAULT 0,
  due_date DATE,
  status ENUM('Pending', 'Ongoing', 'Completed') DEFAULT 'Pending',
  printed BOOLEAN DEFAULT 0,
  signed BOOLEAN DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add enum type to the 'type' column in 'events' table
ALTER TABLE `events`
  MODIFY `type` enum('Off-Campus', 'On-Campus') NOT NULL;

-- Create Participants Table

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    student_number VARCHAR(20) UNIQUE, -- If participants are students with unique IDs
    section VARCHAR(50) NOT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant_event (event_id, student_number) -- Prevents duplicate entries for the same event
);

CREATE TABLE `participant_checklist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `participant_id` INT UNIQUE NOT NULL, 
    
    `p_studid` TINYINT(1) DEFAULT 0, 
    `p_parentid` TINYINT(1) DEFAULT 0, 
    `p_waiver` TINYINT(1) DEFAULT 0, 
    `p_cor` TINYINT(1) DEFAULT 0, 
    FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE
);

-- Modify users.id to be UNSIGNED
ALTER TABLE users
MODIFY id INT(11) UNSIGNED AUTO_INCREMENT;


-- Create Tasks Table
CREATE TABLE tasks (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assigned_to_id INT(11) UNSIGNED NOT NULL, 
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    notes TEXT,
    due_date DATE,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    link VARCHAR(255), 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
