-- Customer password resets (separate from seller password_resets)
CREATE TABLE IF NOT EXISTS `customer_password_resets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_cpr_customer` (`customer_id`),
    INDEX `idx_cpr_token` (`token`),
    CONSTRAINT `fk_cpr_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
