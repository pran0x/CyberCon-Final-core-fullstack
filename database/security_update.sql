-- ============================================
-- CyberCon Security Update Script
-- Run this to add security logging tables
-- to your existing database
-- ============================================

USE cybercon_db;

-- Create failed_login_attempts table for security logging
CREATE TABLE IF NOT EXISTS failed_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username_attempted VARCHAR(255) NOT NULL,
    password_attempted VARCHAR(255) NOT NULL,
    user_agent TEXT,
    country VARCHAR(100),
    city VARCHAR(100),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_blocked BOOLEAN DEFAULT FALSE,
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_is_blocked (is_blocked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create blocked_ips table
CREATE TABLE IF NOT EXISTS blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE NOT NULL,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) DEFAULT 'Too many failed login attempts',
    unblock_at TIMESTAMP NULL,
    INDEX idx_ip_address (ip_address),
    INDEX idx_blocked_at (blocked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update admins table to add 'viewer' role if not exists
ALTER TABLE admins 
MODIFY COLUMN role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin';

-- Display success message
SELECT 'Security tables created successfully!' as Status;
SELECT COUNT(*) as failed_login_attempts_table FROM information_schema.tables 
WHERE table_schema = 'cybercon_db' AND table_name = 'failed_login_attempts';
SELECT COUNT(*) as blocked_ips_table FROM information_schema.tables 
WHERE table_schema = 'cybercon_db' AND table_name = 'blocked_ips';
