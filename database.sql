-- BantayPurrPaws Database Schema (legacy entry point)
-- Prefer importing the full schema: sql/schema.sql
CREATE DATABASE IF NOT EXISTS bantaypurrpaws CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bantaypurrpaws;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'staff', 'admin') DEFAULT 'user',
    username VARCHAR(50) DEFAULT NULL UNIQUE,
    phone_number VARCHAR(20) DEFAULT NULL,
    profile_picture VARCHAR(512) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rescue reports table
CREATE TABLE IF NOT EXISTS rescue_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(20) NOT NULL UNIQUE,
    reporter_id INT NOT NULL,
    reporter_name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    animal_type VARCHAR(100),
    location TEXT NOT NULL,
    description TEXT,
    photo_path VARCHAR(255),
    status ENUM('pending', 'in_progress', 'rescued', 'failed') DEFAULT 'pending',
    assigned_to INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Report logs table
CREATE TABLE IF NOT EXISTS report_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES rescue_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed admin account (password: admin123)
INSERT INTO users (full_name, email, password, role) VALUES
('System Administrator', 'admin@bantaypurrpaws.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Staff Member', 'staff@bantaypurrpaws.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');
-- Note: Default password is 'password' — change in production

-- Pet adoption tables: import database/adoption_tables.sql separately
