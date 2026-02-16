CREATE DATABASE IF NOT EXISTS `tinyshop`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `tinyshop`;

-- ══════════════════════════════════════════════════════════════════════════════
-- Users
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL DEFAULT '',
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `oauth_provider` VARCHAR(20) DEFAULT NULL,
    `oauth_id` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin','seller') NOT NULL DEFAULT 'seller',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `email_verified_at` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `login_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `store_name` VARCHAR(150) DEFAULT NULL,
    `subdomain` VARCHAR(63) DEFAULT NULL,
    `custom_domain` VARCHAR(255) DEFAULT NULL,
    `shop_logo` VARCHAR(500) DEFAULT NULL,
    `shop_favicon` VARCHAR(500) DEFAULT NULL,
    `shop_tagline` VARCHAR(300) DEFAULT NULL,
    `contact_whatsapp` VARCHAR(20) DEFAULT NULL,
    `contact_email` VARCHAR(255) DEFAULT NULL,
    `contact_phone` VARCHAR(20) DEFAULT NULL,
    `map_link` VARCHAR(500) DEFAULT NULL,
    `social_instagram` VARCHAR(100) DEFAULT NULL,
    `social_tiktok` VARCHAR(100) DEFAULT NULL,
    `social_facebook` VARCHAR(255) DEFAULT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `plan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `plan_expires_at` DATETIME NULL DEFAULT NULL,
    `shop_theme` VARCHAR(20) NOT NULL DEFAULT 'classic',
    `show_store_name` TINYINT(1) NOT NULL DEFAULT 1,
    `show_tagline` TINYINT(1) NOT NULL DEFAULT 1,
    `show_search` TINYINT(1) NOT NULL DEFAULT 1,
    `show_categories` TINYINT(1) NOT NULL DEFAULT 1,
    `show_sort_toolbar` TINYINT(1) NOT NULL DEFAULT 1,
    `show_desktop_footer` TINYINT(1) NOT NULL DEFAULT 1,
    `announcement_text` VARCHAR(500) NULL DEFAULT NULL,
    `google_verification` VARCHAR(100) DEFAULT NULL,
    `bing_verification` VARCHAR(100) DEFAULT NULL,
    `stripe_public_key` VARCHAR(255) NULL,
    `stripe_secret_key` VARCHAR(255) NULL,
    `stripe_mode` ENUM('test','live') NOT NULL DEFAULT 'test',
    `stripe_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `paypal_client_id` VARCHAR(255) NULL,
    `paypal_secret` VARCHAR(255) NULL,
    `paypal_mode` ENUM('test','live') NOT NULL DEFAULT 'test',
    `paypal_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `cod_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `mpesa_shortcode` VARCHAR(20) NULL,
    `mpesa_consumer_key` VARCHAR(255) NULL,
    `mpesa_consumer_secret` VARCHAR(255) NULL,
    `mpesa_passkey` VARCHAR(255) NULL,
    `mpesa_mode` ENUM('test','live') NOT NULL DEFAULT 'test',
    `mpesa_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_email` (`email`),
    UNIQUE KEY `uniq_subdomain` (`subdomain`),
    UNIQUE KEY `uniq_custom_domain` (`custom_domain`),
    KEY `idx_role` (`role`),
    KEY `idx_active` (`is_active`),
    KEY `idx_oauth` (`oauth_provider`, `oauth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Categories
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `parent_id` BIGINT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(255) NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_sort` (`user_id`, `sort_order`),
    INDEX `idx_parent` (`parent_id`),
    UNIQUE INDEX `idx_categories_user_slug` (`user_id`, `slug`),
    CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Products
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `category_id` BIGINT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(255) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `compare_price` DECIMAL(10,2) DEFAULT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_sold` TINYINT(1) NOT NULL DEFAULT 0,
    `stock_quantity` INT UNSIGNED NULL DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `variations` JSON DEFAULT NULL,
    `meta_title` VARCHAR(200) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_sort` (`user_id`, `sort_order`),
    KEY `idx_category` (`category_id`),
    KEY `idx_user_active` (`user_id`, `is_active`),
    UNIQUE KEY `idx_user_slug` (`user_id`, `slug`),
    CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Product Images
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `product_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_product_sort` (`product_id`, `sort_order`),
    CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Orders
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `order_number` VARCHAR(20) NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `customer_name` VARCHAR(100) DEFAULT NULL,
    `customer_phone` VARCHAR(20) DEFAULT NULL,
    `customer_email` VARCHAR(255) NULL,
    `shipping_address` JSON NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `status` ENUM('pending','paid','cancelled','refunded') NOT NULL DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payment_gateway` VARCHAR(20) NULL,
    `payment_intent_id` VARCHAR(255) NULL,
    `reference_id` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT NULL,
    `coupon_code` VARCHAR(50) NULL DEFAULT NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_user` (`user_id`),
    KEY `idx_order_product` (`product_id`),
    KEY `idx_user_created` (`user_id`, `created_at`),
    UNIQUE KEY `idx_order_number` (`order_number`),
    KEY `idx_payment_intent` (`payment_intent_id`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Order Items
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `order_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `product_image` VARCHAR(500) NULL,
    `variation` VARCHAR(200) NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Shop Views (analytics)
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `shop_views` (
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

-- ══════════════════════════════════════════════════════════════════════════════
-- Password Resets
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `password_resets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_password_resets_email` (`email`),
    INDEX `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Settings (key-value)
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `settings` (
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Coupons
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `coupons` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `value` DECIMAL(10,2) NOT NULL,
    `min_order` DECIMAL(10,2) NULL DEFAULT NULL,
    `max_uses` INT UNSIGNED NULL DEFAULT NULL,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `idx_user_code` (`user_id`, `code`),
    CONSTRAINT `fk_coupons_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Plans
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `plans` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(300) NULL,
    `price_monthly` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `price_yearly` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
    `max_products` INT UNSIGNED NULL DEFAULT NULL,
    `allowed_themes` TEXT NULL DEFAULT NULL,
    `custom_domain_allowed` TINYINT(1) NOT NULL DEFAULT 0,
    `coupons_allowed` TINYINT(1) NOT NULL DEFAULT 0,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Subscriptions
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `subscriptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NOT NULL,
    `billing_cycle` ENUM('monthly','yearly') NOT NULL,
    `status` ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    `starts_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `payment_gateway` VARCHAR(20) NULL,
    `payment_reference` VARCHAR(255) NULL,
    `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Audit Log
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `audit_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `details` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════════════
-- Billing — M-Pesa Pending
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `billing_mpesa_pending` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NOT NULL,
    `billing_cycle` VARCHAR(10) NOT NULL DEFAULT 'monthly',
    `checkout_request_id` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `status` ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_checkout_req` (`checkout_request_id`),
    INDEX `idx_user_status` (`user_id`, `status`),
    CONSTRAINT `fk_bmp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bmp_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deferred FK: users.plan_id → plans (plans table must exist first)
ALTER TABLE `users` ADD CONSTRAINT `fk_users_plan`
    FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;

-- ══════════════════════════════════════════════════════════════════════════════
-- Help Center
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `help_categories` (
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

CREATE TABLE `help_articles` (
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

-- ══════════════════════════════════════════════════════════════════════════════
-- Seed Data
-- ══════════════════════════════════════════════════════════════════════════════

INSERT INTO `settings` (`key`, `value`) VALUES
    ('site_name', 'TinyShop'),
    ('base_domain', 'localhost'),
    ('support_email', ''),
    ('maintenance_mode', '0'),
    ('default_currency', 'KES'),
    ('max_products_per_seller', '100'),
    ('allow_registration', '1'),
    ('smtp_host', ''),
    ('smtp_port', '587'),
    ('smtp_username', ''),
    ('smtp_password', ''),
    ('smtp_encryption', 'tls'),
    ('mail_from_email', ''),
    ('mail_from_name', 'TinyShop'),
    ('google_verification', ''),
    ('bing_verification', ''),
    ('google_analytics_id', ''),
    ('facebook_pixel_id', ''),
    ('robots_extra', '');

INSERT INTO `plans` (`name`, `slug`, `description`, `price_monthly`, `price_yearly`, `currency`,
    `max_products`, `allowed_themes`, `custom_domain_allowed`, `coupons_allowed`,
    `is_default`, `sort_order`)
VALUES
    ('Free', 'free', 'Get started with the basics', 0, 0, 'KES', 10, '["classic"]', 0, 0, 1, 0),
    ('Pro', 'pro', 'Everything you need to grow your business', 1000, 10000, 'KES', NULL, NULL, 1, 1, 0, 1);

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
