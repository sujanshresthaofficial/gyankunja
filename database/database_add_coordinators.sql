USE gyankunja;

CREATE TABLE IF NOT EXISTS coordinators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,      
    role ENUM('coordinator', 'admin') NOT NULL DEFAULT 'coordinator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);