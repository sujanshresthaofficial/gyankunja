-- Run once in phpMyAdmin, database: gyankunja.
-- This is additive: it does not delete or change existing notes or users.
USE gyankunja;

CREATE TABLE IF NOT EXISTS exam_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    semester VARCHAR(20) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    exam_type VARCHAR(100) NOT NULL,
    starts_at DATETIME NOT NULL,
    venue VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_exam_semester_starts (semester, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Do not add sample exam rows: schedules must come from verified university information.
