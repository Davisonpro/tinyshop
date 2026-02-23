-- Customer accounts (per-shop)
CREATE TABLE IF NOT EXISTS `customers` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `name`           VARCHAR(100) NOT NULL DEFAULT '',
    `email`          VARCHAR(255) NOT NULL,
    `phone`          VARCHAR(20) DEFAULT NULL,
    `password_hash`  VARCHAR(255) NOT NULL,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at`  DATETIME DEFAULT NULL,
    `login_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_shop_email` (`user_id`, `email`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link orders to customer accounts
ALTER TABLE `orders`
    ADD COLUMN `customer_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `user_id`,
    ADD KEY `idx_customer_id` (`customer_id`),
    ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;
