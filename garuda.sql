-- garuda.sql
-- Create database and users table, and seed an admin user

CREATE DATABASE IF NOT EXISTS `garudaeyes` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `garudaeyes`;

-- users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(191) NOT NULL UNIQUE,
  `email` VARCHAR(191) DEFAULT NULL,
  `password` TEXT NOT NULL,
  `current_session` VARCHAR(128) DEFAULT NULL,
  `locked_until` DATETIME DEFAULT NULL,
  `fullname` VARCHAR(191) DEFAULT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed admin user (username: admin, email: admin@example.local)
-- Password chosen for seed: Admin@Garuda2025!
-- NOTE: change this password after first login in production
INSERT INTO `users` (username, email, password, fullname, role) VALUES
('admin', 'admin@example.local', '$2y$10$e0Nw2zQ6bQHqQvOLQmF6Xu2gK5GqZ0hQm9Q5s1nGkT6YQ9cR7sG2W', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE username=VALUES(username), email=VALUES(email);

-- password reset tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
