-- Create Media Center Pass System Database

CREATE DATABASE IF NOT EXISTS media_center;
USE media_center;

-- Settings table (librarian controls)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    form_auto_open BOOLEAN DEFAULT 0,
    form_open_time TIME,
    form_close_time TIME,
    auto_approval BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Librarian login credentials
CREATE TABLE IF NOT EXISTS librarians (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Student passes (current day)
CREATE TABLE IF NOT EXISTS passes_current (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    `mod` INT NOT NULL,
    activities TEXT NOT NULL,
    agreement_checked BOOLEAN DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    pass_code VARCHAR(20) UNIQUE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Archive table for historical passes (we'll create dated tables as needed)
CREATE TABLE IF NOT EXISTS passes_archive (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    `mod` INT NOT NULL,
    activities TEXT NOT NULL,
    agreement_checked BOOLEAN DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    pass_code VARCHAR(20) UNIQUE,
    sent_at TIMESTAMP NULL,
    pass_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (pass_date),
    INDEX (first_name),
    INDEX (last_name)
);

-- Insert default settings
INSERT INTO settings (form_auto_open, form_open_time, form_close_time, auto_approval) 
VALUES (1, '07:30:00', '14:30:00', 0);

-- Create default admin (password: admin123)
INSERT INTO librarians (username, email, password_hash) 
VALUES ('admin', 'librarian@school.local', '$2y$10$YQv8zF1N8Z8zF1N8Z8zF1eQzF1N8Z8zF1N8Z8zF1N8Z8zF1N8Z8zF');
