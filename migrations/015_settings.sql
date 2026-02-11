CREATE TABLE IF NOT EXISTS `settings` (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed defaults
INSERT INTO `settings` (`key`, `value`) VALUES
    ('site_name', 'TinyShop'),
    ('base_domain', 'localhost'),
    ('support_email', ''),
    ('maintenance_mode', '0'),
    ('default_currency', 'KES'),
    ('max_products_per_seller', '100'),
    ('allow_registration', '1')
ON DUPLICATE KEY UPDATE `key` = `key`;
