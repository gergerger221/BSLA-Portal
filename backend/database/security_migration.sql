-- ============================================================================
-- Security Migration: Rate Limiting + Token Expiry
-- Run this SQL in phpMyAdmin or MySQL CLI against sia_highschool_db
-- ============================================================================

-- VULN-06 fix: Create login_attempts table for rate limiting
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    `username_attempted` VARCHAR(255) NOT NULL COMMENT 'Username/email tried',
    `was_successful` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=success, 0=failed',
    `user_agent` VARCHAR(512) DEFAULT NULL,
    `attempted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip_time` (`ip_address`, `attempted_at`),
    INDEX `idx_cleanup` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='VULN-06: Tracks login attempts for IP-based rate limiting';

-- VULN-07 fix: Add token_expires_at column to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME DEFAULT NULL 
COMMENT 'VULN-07: Token expiry timestamp — NULL means no expiry (legacy tokens)' 
AFTER `remember_token`;

SELECT 'Security migration completed successfully!' AS status;
