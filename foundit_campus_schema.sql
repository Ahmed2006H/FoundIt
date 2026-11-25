-- FoundIt Campus database schema
-- Structure only: no sample users, password hashes, item reports, claims,
-- notifications, or AI match records are included.
-- Import with phpMyAdmin or the MySQL command line.

CREATE DATABASE IF NOT EXISTS `foundit_campus_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `foundit_campus_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `ai_matches`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `claims`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(100) NOT NULL,
  `type` enum('lost','found') NOT NULL,
  `location` varchar(255) NOT NULL,
  `item_date` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('open','claimed','resolved') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `claim_message` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`item_id`)
    REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lost_item_id` int(11) NOT NULL,
  `found_item_id` int(11) NOT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `match_explanation` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_match` (`lost_item_id`,`found_item_id`),
  KEY `found_item_id` (`found_item_id`),
  CONSTRAINT `ai_matches_ibfk_1` FOREIGN KEY (`lost_item_id`)
    REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_matches_ibfk_2` FOREIGN KEY (`found_item_id`)
    REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
