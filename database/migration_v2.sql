USE gyankunja;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(60)  NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    program VARCHAR(100) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

ALTER TABLE files
    ADD COLUMN category VARCHAR(40) NOT NULL DEFAULT 'Theory Notes' AFTER subject,
    ADD COLUMN user_id INT NULL DEFAULT NULL AFTER uploader_login,
    ADD INDEX idx_subject_category (subject, category);
