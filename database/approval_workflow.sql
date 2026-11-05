USE gyankunja;

-- Run this migration once after importing the original gyankunja.sql file.
ALTER TABLE notes
    ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER approval_status,
    ADD COLUMN reviewed_at DATETIME NULL AFTER reviewed_by,
    ADD COLUMN review_comment VARCHAR(500) NULL AFTER reviewed_at,
    ADD INDEX idx_notes_reviewed_by (reviewed_by);

-- Change this email to the real faculty account after the account is registered.
-- Example:
-- UPDATE users
-- SET role = 'faculty'
-- WHERE email = 'faculty@gandakiuniversity.edu.np';

-- You can also make a system administrator:
-- UPDATE users
-- SET role = 'admin'
-- WHERE email = 'admin@gandakiuniversity.edu.np';
