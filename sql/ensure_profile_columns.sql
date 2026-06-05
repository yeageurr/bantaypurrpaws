-- Run once if profile updates fail (missing columns on older databases)
USE bantaypurrpaws;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS username VARCHAR(50) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS phone_number VARCHAR(20) DEFAULT NULL AFTER username,
    ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(512) DEFAULT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uk_users_username ON users (username);
