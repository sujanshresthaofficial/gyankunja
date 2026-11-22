-- ================================================================
-- GYANKUNJA - Consolidated MySQL Schema (current php/ backend)
-- File: gyankunj.sql
-- Database name: gyankunja (as configured in php/config.php)
-- ================================================================
-- PURPOSE: One-step schema installation for a FRESH database.
-- Merges the current requirements of:
--   database/gyankunja.sql       (users fields used by php/)
--   database/schema.sql          (files)
--   database/migration_v2.sql    (files.category, files.user_id)
--   database/database_add_coordinators.sql (coordinators)
--   database/dashboard_exams.sql (exam_schedule)
--
-- Excludes legacy api/ notes and faculty schema, which the current
-- dashboard.html, admin.html and semester pages do not use.
--
-- IMPORTANT:
--  * Does NOT delete tables or records, or create demo accounts.
--  * CREATE TABLE IF NOT EXISTS does NOT upgrade older existing tables.
--  * Back up your existing database before attempting any migrations.
--  * Uploads are saved to the server uploads/ folder, not in MySQL.
-- ================================================================

CREATE DATABASE IF NOT EXISTS `gyankunja`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gyankunja`;

-- ---------------------------------------------------------------
-- 1. USERS: student accounts and login / profile information
-- Used by: php/auth_signup.php, php/auth_login.php,
-- php/auth_me.php, php/profile_update.php, php/dashboard_data.php
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(60) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `program` VARCHAR(100) NOT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_student_id` (`student_id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 2. COORDINATORS: staff and admin accounts / role permissions
-- Used by: php/auth_login.php, php/admin_setup.php,
-- php/admin_coordinators.php, php/admin_files.php,
-- php/admin_review.php, php/exams.php
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coordinators` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('coordinator', 'admin') NOT NULL DEFAULT 'coordinator',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coordinators_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 3. FILES: uploaded learning-resource metadata and moderation
-- Used by: php/upload.php, php/list_files.php, php/download.php,
-- php/dashboard_data.php, php/admin_files.php, php/admin_review.php
-- Actual files live in gyankunja/uploads/ on the server.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `uploader_name` VARCHAR(150) DEFAULT NULL,
  `uploader_login` VARCHAR(100) DEFAULT NULL,
  `user_id` INT NULL DEFAULT NULL,
  `semester` VARCHAR(20) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `category` VARCHAR(40) NOT NULL DEFAULT 'Theory Notes',
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` VARCHAR(150) DEFAULT NULL,
  `review_remarks` TEXT DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_files_status` (`status`),
  KEY `idx_files_semester` (`semester`),
  KEY `idx_files_subject_category` (`subject`, `category`),
  KEY `idx_files_user_uploaded` (`user_id`, `uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- 4. EXAM_SCHEDULE: verified exams entered by coordinators/admins
-- Used by: php/exams.php and the current student/admin dashboards
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_schedule` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `semester` VARCHAR(20) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `exam_type` VARCHAR(100) NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `venue` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exam_semester_starts` (`semester`, `starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- INSTALLATION
-- 1. Start Apache and MySQL in WAMP.
-- 2. Open http://localhost/phpmyadmin/
-- 3. Click Import, choose gyankunj.sql, then click Go.
-- 4. The gyankunja database will contain 4 empty tables.
-- 5. Create the FIRST admin locally at:
--      http://localhost/gyankunja/php/admin_setup.php
-- 6. Register students using signup.html, then test login/upload.
-- 7. Back up the database before any future schema modifications.
-- ================================================================
