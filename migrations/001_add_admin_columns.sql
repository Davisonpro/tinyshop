-- Migration: Add admin/security columns to users table
-- Run: mysql -u root tinyshop < migrations/001_add_admin_columns.sql

ALTER TABLE `users`
    ADD COLUMN `role` ENUM('admin','seller') NOT NULL DEFAULT 'seller' AFTER `password_hash`,
    ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `role`,
    ADD COLUMN `email_verified_at` DATETIME DEFAULT NULL AFTER `is_active`,
    ADD COLUMN `last_login_at` DATETIME DEFAULT NULL AFTER `email_verified_at`,
    ADD COLUMN `login_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_at`,
    ADD KEY `idx_role` (`role`),
    ADD KEY `idx_active` (`is_active`);
