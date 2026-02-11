CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(255)    NOT NULL,
    `token`      VARCHAR(64)     NOT NULL COMMENT 'SHA-256 hash of the plain token',
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at`    DATETIME        DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_password_resets_email` (`email`),
    INDEX `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up expired / used tokens older than 24 hours (run via cron or manually)
-- DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
