-- Help center tables

CREATE TABLE IF NOT EXISTS `help_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) NOT NULL DEFAULT 'fa-circle-question',
    `description` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_help_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `help_articles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `summary` VARCHAR(500) DEFAULT NULL,
    `content` LONGTEXT,
    `keywords` TEXT DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_help_articles_slug` (`slug`),
    KEY `idx_help_articles_category` (`category_id`),
    KEY `idx_help_articles_published` (`is_published`),
    CONSTRAINT `fk_help_articles_category` FOREIGN KEY (`category_id`) REFERENCES `help_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories
INSERT INTO `help_categories` (`name`, `slug`, `icon`, `description`, `sort_order`) VALUES
    ('Getting Started', 'getting-started', 'fa-rocket', 'New to the platform? Start here.', 1),
    ('Products', 'products', 'fa-box-open', 'Everything about managing your product listings.', 2),
    ('Categories', 'categories', 'fa-folder', 'Organize products into groups for easier browsing.', 3),
    ('Orders', 'orders', 'fa-receipt', 'Manage customer orders from start to finish.', 4),
    ('Payments', 'payments', 'fa-credit-card', 'Set up how you get paid by customers.', 5),
    ('Shop Design', 'shop-design', 'fa-palette', 'Customize how your shop looks and feels.', 6),
    ('Shop Settings', 'shop-settings', 'fa-gear', 'Configure your shop details and preferences.', 7),
    ('Your Shop Link', 'shop-link', 'fa-link', 'Share your shop and connect your own domain.', 8),
    ('Coupons & Discounts', 'coupons', 'fa-ticket', 'Create discount codes to boost sales.', 9),
    ('Analytics', 'analytics', 'fa-chart-line', 'Understand how your shop is performing.', 10),
    ('Billing & Plans', 'billing', 'fa-wallet', 'Manage your subscription and payments.', 11),
    ('Account & Security', 'account', 'fa-shield-halved', 'Keep your account safe and up to date.', 12),
    ('For Your Customers', 'customers', 'fa-users', 'How the buying experience works.', 13);
