-- =============================================================
--  Attendance & Overtime Management System - Schema (SHARED HOSTING)
--  For InfinityFree / cPanel / Hostinger etc. where the database is
--  already created for you. In phpMyAdmin, SELECT your existing DB on
--  the left first, then Import THIS file (it does NOT create a DB).
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------
-- Users (administrators)
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `username`       VARCHAR(60) NOT NULL UNIQUE,
    `password`       VARCHAR(255) NOT NULL,
    `name`           VARCHAR(120) NOT NULL DEFAULT 'Administrator',
    `email`          VARCHAR(150) DEFAULT NULL,
    `role`           ENUM('admin','manager','staff') NOT NULL DEFAULT 'admin',
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Departments
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(120) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Employees
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `employee_code` VARCHAR(30) NOT NULL UNIQUE,
    `name`          VARCHAR(150) NOT NULL,
    `department`    VARCHAR(120) DEFAULT NULL,
    `designation`   VARCHAR(120) DEFAULT NULL,
    `mobile`        VARCHAR(20) DEFAULT NULL,
    `email`         VARCHAR(150) DEFAULT NULL,
    `joining_date`  DATE DEFAULT NULL,
    `photo`         VARCHAR(255) DEFAULT NULL,
    `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_department` (`department`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Attendance
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id`     INT NOT NULL,
    `attendance_date` DATE NOT NULL,
    `status`          ENUM('Present','Absent','Half Day','Leave','Holiday','Weekend') NOT NULL DEFAULT 'Present',
    `in_time`         TIME DEFAULT NULL,
    `out_time`        TIME DEFAULT NULL,
    `working_hours`   VARCHAR(8) DEFAULT '00:00',
    `overtime`        VARCHAR(8) DEFAULT '00:00',
    `remarks`         VARCHAR(255) DEFAULT NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_employee_date` (`employee_id`, `attendance_date`),
    INDEX `idx_date` (`attendance_date`),
    CONSTRAINT `fk_attendance_employee`
        FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Settings (single row)
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `office_start`  TIME NOT NULL DEFAULT '08:00:00',
    `office_end`    TIME NOT NULL DEFAULT '17:00:00',
    `break_time`    INT NOT NULL DEFAULT 30,          -- minutes
    `duty_hours`    VARCHAR(8) NOT NULL DEFAULT '08:30',
    `company_name`  VARCHAR(150) NOT NULL DEFAULT 'Acme Corporation',
    `company_logo`  VARCHAR(255) DEFAULT NULL,
    `address`       VARCHAR(255) DEFAULT NULL,
    `phone`         VARCHAR(40) DEFAULT NULL,
    `email`         VARCHAR(150) DEFAULT NULL,
    `theme`         ENUM('light','dark') NOT NULL DEFAULT 'light',
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Holidays
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `holidays`;
CREATE TABLE `holidays` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `title`        VARCHAR(150) NOT NULL,
    `holiday_date` DATE NOT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Activity / audit logs
-- ---------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT DEFAULT NULL,
    `action`      VARCHAR(80) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `ip_address`  VARCHAR(45) DEFAULT NULL,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- Seed: default admin user  (username: admin / password: admin123)
-- The hash below is a bcrypt hash of "admin123".
-- ---------------------------------------------------------------
INSERT INTO `users` (`username`, `password`, `name`, `email`, `role`) VALUES
('admin', '$2y$10$QaqV6tiFG2iFNFTiLJGGM.1u3GxFp.1eUU54PR2hajw5cKzRqxWFK', 'System Administrator', 'admin@example.com', 'admin');

-- Default settings row
INSERT INTO `settings`
    (`office_start`, `office_end`, `break_time`, `duty_hours`, `company_name`, `address`, `phone`, `email`, `theme`)
VALUES
    ('08:00:00', '17:00:00', 30, '08:30', 'Acme Corporation', '123 Business Park, Metropolis', '+1 555 010 2020', 'hr@acme.example', 'light');

-- Default departments
INSERT INTO `departments` (`name`) VALUES
('Administration'), ('Human Resources'), ('Finance'), ('Engineering'),
('Sales'), ('Marketing'), ('Operations'), ('IT Support');
