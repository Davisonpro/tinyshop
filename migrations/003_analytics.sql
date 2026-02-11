-- Migration 003: Shop Views Analytics
-- Run: mysql -u root -p tinyshop < migrations/003_analytics.sql

CREATE TABLE IF NOT EXISTS `shop_views` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED DEFAULT NULL,
    `visitor_hash` VARCHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_date` (`user_id`, `created_at`),
    INDEX `idx_user_product` (`user_id`, `product_id`),
    INDEX `idx_dedup` (`user_id`, `visitor_hash`, `product_id`, `created_at`),
    CONSTRAINT `fk_shop_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
