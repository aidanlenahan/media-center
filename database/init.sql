-- Create Media Center Pass System Database

CREATE DATABASE IF NOT EXISTS media_center;
USE media_center;

-- Settings table (librarian controls)
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    form_auto_open BOOLEAN DEFAULT 0,
    disable_weekends BOOLEAN DEFAULT 0,
    form_open_time TIME,
    form_close_time TIME,
    auto_approval BOOLEAN DEFAULT 0,
    form_status_override BOOLEAN DEFAULT 0,
    form_status_manual ENUM('open', 'closed') DEFAULT 'open',
    recent_entries_limit INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Librarian login credentials
CREATE TABLE IF NOT EXISTS librarians (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('librarian', 'root') DEFAULT 'librarian',
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

-- Developer settings table (only accessible to root user)
CREATE TABLE IF NOT EXISTS dev_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    debug_mode BOOLEAN DEFAULT 0,
    show_sql_queries BOOLEAN DEFAULT 0,
    log_all_actions BOOLEAN DEFAULT 0,
    bypass_time_restrictions BOOLEAN DEFAULT 0,
    test_mode BOOLEAN DEFAULT 0,
    allow_duplicate_passes BOOLEAN DEFAULT 0,
    email_override_address VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (form_auto_open, form_open_time, form_close_time, auto_approval) 
VALUES (1, '07:30:00', '14:30:00', 0);

-- Insert default dev settings
INSERT INTO dev_settings (debug_mode, test_mode) VALUES (0, 0);

-- Create default admin (password: admin123)
INSERT INTO librarians (username, email, password_hash, role) 
VALUES ('admin', 'librarian@school.local', '$2y$10$YQv8zF1N8Z8zF1N8Z8zF1eQzF1N8Z8zF1N8Z8zF1N8Z8zF1N8Z8zF', 'librarian');

-- Create root user for developer access (password: root123) - CHANGE THIS AFTER FIRST LOGIN!
INSERT INTO librarians (username, email, password_hash, role) 
VALUES ('root', 'developer@school.local', '$2y$10$xBx5vqXK7bZ9mKxdH4kV0.8J3nQh3F7eQVb7Ux5YnKqX8mN2dWvJG', 'root');
