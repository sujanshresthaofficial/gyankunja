-- =====================================================
-- Database: gyankunja
-- Run this once in phpMyAdmin (Import tab) or via the
-- mysql command line.
-- =====================================================
CREATE DATABASE IF NOT EXISTS gyankunja
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gyankunja;

-- ---------------------------------------------------
-- Files / Notes table
-- (student ownership + coordinator review columns are
--  already here so we don't need to change the schema
--  later — they just aren't enforced yet)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Uploader info (plain text for now until real login/session exists)
    uploader_name VARCHAR(150) DEFAULT NULL,
    uploader_login VARCHAR(100) DEFAULT NULL,   -- e.g. BIT-SEM3-001

    -- Note details
    semester VARCHAR(20) NOT NULL,              -- e.g. "Semester I"
    subject VARCHAR(150) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,

    -- File storage
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,

    -- Approval workflow (used starting next round)
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by VARCHAR(150) DEFAULT NULL,
    review_remarks TEXT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,

    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_semester (semester)
);