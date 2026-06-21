-- Database Schema for TaskFlow Pro

CREATE DATABASE IF NOT EXISTS `taskflow_pro` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taskflow_pro`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    `profile_image` VARCHAR(255) DEFAULT 'default.png',
    `phone` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Teams Table
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Team Members
CREATE TABLE IF NOT EXISTS `team_members` (
    `team_id` INT,
    `user_id` INT,
    `role` ENUM('owner', 'member') DEFAULT 'member',
    PRIMARY KEY (`team_id`, `user_id`),
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 3. Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `color` VARCHAR(20) DEFAULT '#2563EB',
    `team_id` INT NULL,
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 4. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(20) DEFAULT '#0F172A',
    `user_id` INT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 5. Tasks Table
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `status` ENUM('todo', 'in_progress', 'completed') DEFAULT 'todo',
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `due_date` DATETIME NULL,
    `project_id` INT NULL,
    `category_id` INT NULL,
    `assigned_to` INT NULL,
    `created_by` INT,
    `phone` VARCHAR(20) DEFAULT NULL,
    `is_archived` BOOLEAN DEFAULT FALSE,
    `is_starred` BOOLEAN DEFAULT FALSE,
    `recurrence` ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 6. Task Comments Table
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT,
    `user_id` INT,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 7. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `message` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 8. Reminders Table
CREATE TABLE IF NOT EXISTS `reminders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT,
    `user_id` INT,
    `reminder_time` DATETIME NOT NULL,
    `type` ENUM('browser', 'email', 'system') DEFAULT 'system',
    `is_sent` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 9. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 10. Attachments Table
CREATE TABLE IF NOT EXISTS `attachments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `task_id` INT,
    `user_id` INT,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`task_id`) REFERENCES `tasks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 11. Notes Table
CREATE TABLE IF NOT EXISTS `notes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT,
    `color` VARCHAR(20) DEFAULT '#FFFFFF',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 12. Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT
);

-- 13. Contacts Directory Table
CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `company` VARCHAR(100) DEFAULT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Insert Default Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'TaskFlow Pro'),
('theme', 'light'),
('allow_registration', 'true');

-- Create a default admin user (password: Admin@123)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@taskflow.pro', '$2y$10$wCqVjBylqU34largEiee6u4jkibKUWx8CgCW6KT4eTVaK8HuI73kK', 'admin');


