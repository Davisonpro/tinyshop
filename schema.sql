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
    `is_showcased` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
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
    `features` TEXT NULL DEFAULT NULL,
    `cta_text` VARCHAR(100) NULL DEFAULT NULL,
    `badge_text` VARCHAR(50) NULL DEFAULT NULL,
    `is_featured` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
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

-- ══════════════════════════════════════════════════════════════════════════════
-- Pages (admin-managed dynamic pages)
-- ══════════════════════════════════════════════════════════════════════════════

CREATE TABLE `pages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pages_slug` (`slug`),
    KEY `idx_pages_published` (`is_published`)
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
    `features`, `cta_text`, `badge_text`, `is_featured`,
    `is_default`, `sort_order`)
VALUES
    ('Free', 'free', 'Get started with the basics', 0, 0, 'KES', 10, '["classic"]', 0, 0,
     '["Up to 10 products","1 shop design","Order notifications","Basic analytics","Free forever"]',
     'Start Free', NULL, 0, 1, 0),
    ('Pro', 'pro', 'Everything you need to grow', 399, 3990, 'KES', NULL, NULL, 1, 1,
     '["Unlimited products","All shop designs","Your own web address","Discount codes","Priority support","Advanced analytics","M-Pesa payments"]',
     'Upgrade to Pro', 'Most popular', 1, 0, 1),
    ('Premium', 'premium', 'For power sellers who want it all', 699, 6990, 'KES', NULL, NULL, 1, 1,
     '["Everything in Pro","Dedicated support","Custom integrations","White-label branding","API access"]',
     'Go Premium', NULL, 0, 0, 2);

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

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_description`, `is_published`) VALUES
    ('Terms of Service', 'terms', '<h3>Terms of Service</h3><p>Please update this page with your terms of service.</p>', 'Terms of Service', 1),
    ('Privacy Policy', 'privacy', '<h3>Privacy Policy</h3><p>Please update this page with your privacy policy.</p>', 'Privacy Policy', 1);

INSERT INTO `help_articles` (`category_id`, `title`, `slug`, `summary`, `content`, `keywords`, `sort_order`, `is_published`) VALUES

-- Getting Started (cat 1)
(1, 'Creating your account', 'creating-your-account',
 'Learn how to sign up and set up your seller account in just a few minutes.',
 '<p>Getting started is easy. Visit the registration page and fill in your name, email address, and a password.</p><h3>Step 1: Sign up</h3><p>Click <strong>Get Started</strong> on the homepage or go directly to the registration page. Enter your full name, email, and choose a strong password.</p><h3>Step 2: Verify your email</h3><p>We''ll send you a confirmation email. Click the link inside to verify your account.</p><h3>Step 3: Set up your shop</h3><p>Once verified, you''ll be taken to your dashboard. Click <strong>Shop</strong> in the menu to give your store a name and choose a theme.</p><p>That''s it &mdash; you''re ready to start adding products!</p>',
 'sign up, register, create account, get started', 1, 1),

(1, 'Quick start guide', 'quick-start-guide',
 'A five-minute walkthrough to get your first product listed and your shop live.',
 '<p>This guide walks you through the essentials so you can start selling as fast as possible.</p><h3>1. Name your shop</h3><p>Go to <strong>Dashboard &rarr; Shop</strong> and enter a shop name. This also creates your unique shop link.</p><h3>2. Add your first product</h3><p>Go to <strong>Dashboard &rarr; Products</strong> and tap the <strong>+</strong> button. Add a title, price, description, and at least one photo.</p><h3>3. Choose a theme</h3><p>In <strong>Shop</strong> settings, pick a theme that matches your brand. You can change it anytime.</p><h3>4. Share your link</h3><p>Your shop is live! Copy the link from your dashboard and share it on social media, WhatsApp, or anywhere your customers are.</p>',
 'quick start, first product, tutorial, walkthrough', 2, 1),

(1, 'Understanding your dashboard', 'understanding-your-dashboard',
 'A tour of the main sections in your seller dashboard.',
 '<p>Your dashboard is where you manage everything about your shop. Here''s what each section does:</p><ul><li><strong>Home</strong> &mdash; See your recent orders, revenue, and quick stats at a glance.</li><li><strong>Products</strong> &mdash; Add, edit, or remove the items you sell.</li><li><strong>Orders</strong> &mdash; View and manage customer orders.</li><li><strong>Categories</strong> &mdash; Organize your products into groups.</li><li><strong>Shop</strong> &mdash; Change your shop name, theme, and other settings.</li><li><strong>Analytics</strong> &mdash; Track views, visitors, and sales over time.</li><li><strong>Coupons</strong> &mdash; Create discount codes for your customers.</li><li><strong>Billing</strong> &mdash; Manage your subscription plan.</li></ul><p>Use the tabs at the bottom of the screen to navigate between sections.</p>',
 'dashboard, navigation, menu, sections, overview', 3, 1),

-- Products (cat 2)
(2, 'Adding a new product', 'adding-a-new-product',
 'Step-by-step instructions for listing a product in your shop.',
 '<p>To add a product, go to <strong>Dashboard &rarr; Products</strong> and tap the <strong>+</strong> button.</p><h3>Fill in the details</h3><ul><li><strong>Title</strong> &mdash; Give your product a clear, descriptive name.</li><li><strong>Price</strong> &mdash; Set the selling price. You can also add a compare-at price to show a discount.</li><li><strong>Description</strong> &mdash; Describe what the product is, what it includes, and why customers should buy it.</li><li><strong>Photos</strong> &mdash; Add up to 5 images. The first image will be the main product photo.</li></ul><h3>Save and publish</h3><p>When you''re happy with everything, tap <strong>Save</strong>. The product will appear in your shop immediately.</p>',
 'add product, create listing, new item, photos', 1, 1),

(2, 'Editing and deleting products', 'editing-and-deleting-products',
 'How to update product details or remove a product from your shop.',
 '<p>Need to change a price, update a photo, or remove a product? Here''s how.</p><h3>Editing a product</h3><p>Go to <strong>Products</strong>, find the product you want to change, and tap on it. Make your edits and tap <strong>Save</strong>.</p><h3>Deleting a product</h3><p>Open the product you want to remove and scroll to the bottom. Tap <strong>Delete Product</strong> and confirm. This action cannot be undone.</p><p><strong>Tip:</strong> If you want to temporarily hide a product without deleting it, you can set it as a draft instead.</p>',
 'edit product, update, delete, remove, draft', 2, 1),

(2, 'Writing great product descriptions', 'writing-great-product-descriptions',
 'Tips for descriptions that help sell your products.',
 '<p>A good product description can make the difference between a sale and a bounce. Here are some tips:</p><h3>Be specific</h3><p>Instead of &ldquo;Nice shirt,&rdquo; try &ldquo;Soft cotton crew-neck t-shirt in navy blue, available in sizes S&ndash;XL.&rdquo;</p><h3>Highlight benefits</h3><p>Don''t just list features &mdash; explain why they matter. &ldquo;Wrinkle-free fabric so you always look sharp.&rdquo;</p><h3>Keep it scannable</h3><p>Use short paragraphs and bullet points. Most people skim rather than read every word.</p><h3>Include important details</h3><ul><li>Materials and dimensions</li><li>Care instructions</li><li>What''s included in the package</li><li>Shipping information</li></ul>',
 'description, copywriting, tips, selling, conversion', 3, 1),

-- Categories (cat 3)
(3, 'Creating and managing categories', 'creating-and-managing-categories',
 'How to organize your products into categories for easier browsing.',
 '<p>Categories help your customers find what they''re looking for quickly.</p><h3>Creating a category</h3><p>Go to <strong>Dashboard &rarr; Categories</strong> and tap <strong>Add Category</strong>. Give it a name and optionally add an image.</p><h3>Adding products to categories</h3><p>When you create or edit a product, you''ll see a category dropdown. Select the category that best fits.</p><h3>Reordering categories</h3><p>Categories appear in your shop in the order you set. Use the sort order field to control the display order.</p><h3>Deleting a category</h3><p>Deleting a category won''t delete the products in it &mdash; they''ll just become uncategorized.</p>',
 'categories, organize, group, sort', 1, 1),

-- Orders (cat 4)
(4, 'Viewing and managing orders', 'viewing-and-managing-orders',
 'How to see new orders and update their status.',
 '<p>When a customer places an order, you''ll see it in your <strong>Orders</strong> page.</p><h3>Order statuses</h3><ul><li><strong>Pending</strong> &mdash; The order has been placed but not yet processed.</li><li><strong>Processing</strong> &mdash; You''re preparing the order.</li><li><strong>Shipped</strong> &mdash; The order is on its way to the customer.</li><li><strong>Delivered</strong> &mdash; The customer has received their order.</li><li><strong>Cancelled</strong> &mdash; The order was cancelled.</li></ul><h3>Updating an order</h3><p>Tap on any order to see its details. Use the status dropdown to update it as you process and ship the order.</p>',
 'orders, manage, status, pending, shipped, delivered', 1, 1),

(4, 'Order notifications', 'order-notifications',
 'How you and your customers get notified about orders.',
 '<p>Both you and your customers receive email notifications at key points in the order process.</p><h3>For you (the seller)</h3><p>You''ll get an email every time a new order comes in. The email includes the order details, customer info, and items ordered.</p><h3>For your customers</h3><p>Customers receive an email when their order is confirmed. If you update the status to &ldquo;Shipped&rdquo; or &ldquo;Delivered,&rdquo; they''ll be notified about that too.</p><p><strong>Tip:</strong> Make sure your email settings are configured in <strong>Shop &rarr; Settings</strong> so notifications are sent from your shop''s email address.</p>',
 'notifications, email, alerts, new order', 2, 1),

(4, 'Handling refunds and cancellations', 'handling-refunds-and-cancellations',
 'What to do when a customer wants to cancel or return an order.',
 '<p>Sometimes orders need to be cancelled or refunded. Here''s how to handle it.</p><h3>Cancelling an order</h3><p>Open the order and change the status to <strong>Cancelled</strong>. The customer will be notified automatically.</p><h3>Processing a refund</h3><p>Refunds are handled through your payment provider (Stripe, PayPal, or M-Pesa). Log into your payment dashboard and issue the refund from there.</p><p>After refunding, update the order status so your records stay accurate.</p><h3>Setting a return policy</h3><p>We recommend adding your return policy to your shop description so customers know what to expect before buying.</p>',
 'refund, cancel, return, policy', 3, 1),

-- Payments (cat 5)
(5, 'Setting up payment methods', 'setting-up-payment-methods',
 'How to connect Stripe, PayPal, or M-Pesa to receive payments.',
 '<p>Before you can accept payments, you need to connect at least one payment method.</p><h3>Stripe</h3><p>Stripe lets you accept credit and debit cards. Go to <strong>Dashboard &rarr; Shop</strong>, scroll to Payments, and tap <strong>Connect Stripe</strong>. You''ll be redirected to Stripe to complete the setup.</p><h3>PayPal</h3><p>PayPal is great if your customers prefer paying with their PayPal balance. Enter your PayPal Client ID and Secret in the payment settings.</p><h3>M-Pesa</h3><p>Perfect for customers in East Africa. Enter your M-Pesa API credentials to start accepting mobile money payments.</p><h3>Cash on Delivery</h3><p>You can also enable cash on delivery if you deliver products yourself.</p>',
 'payment, stripe, paypal, mpesa, setup, connect', 1, 1),

(5, 'Understanding transaction fees', 'understanding-transaction-fees',
 'What fees apply when customers pay you.',
 '<p>Each payment provider charges a small fee per transaction. Here''s a quick overview:</p><ul><li><strong>Stripe</strong> &mdash; Typically 2.9% + $0.30 per transaction (varies by country).</li><li><strong>PayPal</strong> &mdash; Usually around 2.9% + $0.30 (varies by country and account type).</li><li><strong>M-Pesa</strong> &mdash; Fees depend on your Safaricom business account terms.</li><li><strong>Cash on Delivery</strong> &mdash; No transaction fees.</li></ul><p>These fees are charged by the payment providers, not by us. The exact rates depend on your location and agreement with the provider.</p>',
 'fees, transaction, charges, stripe, paypal', 2, 1),

-- Shop Design (cat 6)
(6, 'Choosing a shop theme', 'choosing-a-shop-theme',
 'Browse available themes and pick the one that fits your brand.',
 '<p>Your shop theme controls the overall look and feel of your storefront.</p><h3>How to change your theme</h3><p>Go to <strong>Dashboard &rarr; Shop</strong> and scroll to the <strong>Theme</strong> section. Browse the available themes and tap the one you like to preview it.</p><h3>Available themes</h3><p>We offer several professionally designed themes, each with its own style. Some are bold and colorful, while others are minimal and clean.</p><h3>Tips for choosing</h3><ul><li>Pick a theme that matches your product type &mdash; a clean theme works great for tech products, while a warm theme suits handmade goods.</li><li>Consider your brand colors when choosing.</li><li>You can switch themes anytime without losing your products or settings.</li></ul>',
 'theme, design, customize, appearance, style', 1, 1),

(6, 'Customizing your shop appearance', 'customizing-your-shop-appearance',
 'How to add a logo, banner, and other visual elements.',
 '<p>Beyond choosing a theme, you can personalize your shop with your own branding.</p><h3>Shop logo</h3><p>Upload a logo in <strong>Shop</strong> settings. Use a square image (at least 200&times;200 pixels) for best results.</p><h3>Shop banner</h3><p>Some themes support a banner image at the top. Upload a wide image (at least 1200&times;400 pixels).</p><h3>Shop description</h3><p>Write a short description that tells customers what your shop is about. This appears at the top of your storefront.</p><p><strong>Tip:</strong> Keep your branding consistent. Use the same logo and colors across your shop and social media.</p>',
 'logo, banner, customize, branding, appearance', 2, 1),

-- Shop Settings (cat 7)
(7, 'Configuring shop details', 'configuring-shop-details',
 'Set your shop name, currency, and contact information.',
 '<p>Your shop settings control the basic details that customers see.</p><h3>Shop name</h3><p>Choose a name that''s memorable and tells people what you sell. This appears at the top of your shop and in search results.</p><h3>Currency</h3><p>Pick the currency you sell in. All product prices will be displayed in this currency.</p><h3>Contact email</h3><p>Add an email address where customers can reach you. This appears in order confirmation emails.</p><h3>Social links</h3><p>Add links to your Instagram, Facebook, Twitter, or other social profiles. These appear in your shop footer.</p>',
 'shop name, currency, settings, contact, social', 1, 1),

(7, 'Enabling cash on delivery', 'enabling-cash-on-delivery',
 'How to let customers pay when they receive their order.',
 '<p>Cash on delivery (COD) lets customers pay in cash when they receive their order. It''s great for local deliveries.</p><h3>How to enable it</h3><p>Go to <strong>Dashboard &rarr; Shop</strong> and scroll to the payment section. Toggle <strong>Cash on Delivery</strong> on.</p><h3>When to use COD</h3><ul><li>You deliver products yourself or use a local courier.</li><li>Your customers prefer paying in cash.</li><li>You''re in an area where card payments aren''t common.</li></ul><h3>Things to keep in mind</h3><p>COD orders carry a risk of no-shows. Consider requiring a deposit or only offering COD for repeat customers.</p>',
 'cash on delivery, COD, payment, local', 2, 1),

-- Shop Link (cat 8)
(8, 'Sharing your shop link', 'sharing-your-shop-link',
 'How to find and share your unique shop URL.',
 '<p>Every shop gets a unique link that you can share with customers.</p><h3>Finding your link</h3><p>Go to <strong>Dashboard &rarr; Shop</strong>. Your shop link is displayed near the top of the page. Tap the copy button to copy it to your clipboard.</p><h3>Where to share it</h3><ul><li>Social media bios (Instagram, Twitter, TikTok)</li><li>WhatsApp messages and status</li><li>Facebook posts and stories</li><li>Email signatures</li><li>Business cards</li><li>Printed flyers or packaging</li></ul><p><strong>Tip:</strong> The more places you share your link, the more traffic your shop will get.</p>',
 'link, URL, share, social media, whatsapp', 1, 1),

(8, 'Connecting a custom domain', 'connecting-a-custom-domain',
 'Use your own domain name for a professional shop address.',
 '<p>Instead of using the default shop link, you can connect your own domain (like <strong>www.myshop.com</strong>).</p><h3>Requirements</h3><ul><li>A registered domain name from any provider (GoDaddy, Namecheap, Cloudflare, etc.)</li><li>A paid plan that includes custom domain support</li></ul><h3>How to connect</h3><p>1. Go to <strong>Dashboard &rarr; Shop</strong> and find the custom domain section.</p><p>2. Enter your domain name.</p><p>3. Update your domain''s DNS settings to point to our servers. We''ll show you the exact records to add.</p><p>4. Wait for DNS to propagate (usually takes a few hours, sometimes up to 48 hours).</p><p>Once connected, customers can visit your shop using your own domain name.</p>',
 'domain, custom domain, DNS, professional, URL', 2, 1),

-- Coupons (cat 9)
(9, 'Creating a coupon code', 'creating-a-coupon-code',
 'How to create discount codes to attract more customers.',
 '<p>Coupons are a great way to boost sales and reward loyal customers.</p><h3>Creating a coupon</h3><p>Go to <strong>Dashboard &rarr; Coupons</strong> and tap the <strong>+</strong> button.</p><ul><li><strong>Code</strong> &mdash; The code customers will enter at checkout (e.g., SAVE10).</li><li><strong>Discount type</strong> &mdash; Choose between a percentage discount or a fixed amount.</li><li><strong>Discount value</strong> &mdash; How much the discount is worth.</li><li><strong>Minimum order</strong> &mdash; Optionally set a minimum order amount.</li><li><strong>Usage limit</strong> &mdash; Limit how many times the coupon can be used.</li><li><strong>Expiry date</strong> &mdash; Set when the coupon expires.</li></ul><h3>Sharing your coupon</h3><p>Share the code on social media, in emails, or on printed materials. Make the code easy to remember and type.</p>',
 'coupon, discount, promo code, sale', 1, 1),

(9, 'Coupon best practices', 'coupon-best-practices',
 'Tips for using coupons effectively without hurting your margins.',
 '<p>Coupons can drive sales, but use them wisely to protect your profit margins.</p><h3>Do</h3><ul><li>Use coupons for special occasions (holidays, product launches, milestones).</li><li>Set expiry dates to create urgency.</li><li>Track which coupons perform best.</li><li>Use minimum order amounts to increase average order value.</li></ul><h3>Don''t</h3><ul><li>Offer discounts so steep you lose money on every sale.</li><li>Run coupons constantly &mdash; they lose their appeal.</li><li>Make codes too complicated to type on mobile.</li></ul><p><strong>Pro tip:</strong> A 10&ndash;15% discount is usually enough to nudge someone who''s on the fence about buying.</p>',
 'coupon, tips, strategy, marketing, discount', 2, 1),

-- Analytics (cat 10)
(10, 'Understanding your analytics', 'understanding-your-analytics',
 'A guide to the stats and charts in your analytics dashboard.',
 '<p>The Analytics page helps you understand how your shop is performing.</p><h3>Key metrics</h3><ul><li><strong>Views</strong> &mdash; How many times your shop was visited.</li><li><strong>Unique visitors</strong> &mdash; How many different people visited.</li><li><strong>Orders</strong> &mdash; Total number of orders placed.</li><li><strong>Revenue</strong> &mdash; Total amount of money earned from orders.</li></ul><h3>Time periods</h3><p>You can view your analytics for different time periods: today, this week, this month, or all time.</p><h3>Using analytics to improve</h3><p>If you''re getting lots of views but few orders, consider improving your product photos or descriptions. If traffic is low, focus on sharing your shop link more.</p>',
 'analytics, stats, views, revenue, performance', 1, 1),

-- Billing (cat 11)
(11, 'Choosing the right plan', 'choosing-the-right-plan',
 'Compare plans and pick the one that fits your needs.',
 '<p>We offer different plans to match your business size and needs.</p><h3>Free plan</h3><p>Great for getting started. List a limited number of products and access basic features.</p><h3>Paid plans</h3><p>Unlock more products, premium themes, custom domains, coupons, and priority support. Check the <strong>Pricing</strong> page for the latest plan details and prices.</p><h3>How to upgrade</h3><p>Go to <strong>Dashboard &rarr; Billing</strong> and select the plan you want. You can pay monthly or yearly (yearly saves you money).</p><h3>Changing plans</h3><p>You can upgrade or downgrade anytime. When you upgrade, you''ll get access to new features immediately. When you downgrade, changes take effect at the end of your billing period.</p>',
 'plan, pricing, subscription, upgrade, billing', 1, 1),

(11, 'Managing your subscription', 'managing-your-subscription',
 'How to upgrade, downgrade, or cancel your plan.',
 '<p>You''re always in control of your subscription.</p><h3>Viewing your current plan</h3><p>Go to <strong>Dashboard &rarr; Billing</strong> to see your current plan, billing date, and payment method.</p><h3>Upgrading</h3><p>Choose a higher plan to unlock more features. The price difference is prorated for the rest of your billing period.</p><h3>Downgrading</h3><p>Switch to a lower plan. Your current features stay active until the end of your billing period, then the new plan kicks in.</p><h3>Cancelling</h3><p>If you cancel, your shop stays active until the end of your paid period. After that, you''ll be moved to the free plan. Your products and data are preserved.</p>',
 'subscription, upgrade, downgrade, cancel, billing', 2, 1),

-- Account & Security (cat 12)
(12, 'Changing your password', 'changing-your-password',
 'How to update your account password.',
 '<p>It''s a good idea to change your password regularly to keep your account secure.</p><h3>How to change your password</h3><p>1. Go to <strong>Dashboard &rarr; Shop</strong>.</p><p>2. Scroll down to the <strong>Security</strong> section.</p><p>3. Enter your current password and your new password.</p><p>4. Tap <strong>Change Password</strong>.</p><h3>Password tips</h3><ul><li>Use at least 8 characters.</li><li>Mix uppercase letters, lowercase letters, numbers, and symbols.</li><li>Don''t reuse passwords from other websites.</li><li>Consider using a password manager.</li></ul>',
 'password, security, change password, account', 1, 1),

(12, 'Updating your email address', 'updating-your-email-address',
 'How to change the email associated with your account.',
 '<p>Your email address is used for logging in and receiving notifications.</p><h3>How to change it</h3><p>1. Go to <strong>Dashboard &rarr; Shop</strong>.</p><p>2. Scroll down to the <strong>Account</strong> section.</p><p>3. Enter your new email address and your current password to confirm.</p><p>4. Tap <strong>Change Email</strong>.</p><p>Your login email will be updated immediately. All future notifications and order emails will go to your new address.</p>',
 'email, account, change email, update', 2, 1),

(12, 'Deleting your account', 'deleting-your-account',
 'What happens when you delete your account and how to do it.',
 '<p>If you want to permanently remove your account, you can do so from your settings.</p><h3>Before you delete</h3><p>Please note that deleting your account is <strong>permanent and cannot be undone</strong>. This will:</p><ul><li>Delete your shop and all its products</li><li>Remove all order history</li><li>Cancel any active subscription</li><li>Remove your account from our system</li></ul><h3>How to delete</h3><p>Go to <strong>Dashboard &rarr; Shop</strong>, scroll to the bottom, and tap <strong>Delete Account</strong>. You''ll be asked to confirm by entering your password.</p><p>If you''re having issues, consider reaching out to support first &mdash; we might be able to help.</p>',
 'delete account, close, remove, permanent', 3, 1),

-- For Your Customers (cat 13)
(13, 'How checkout works', 'how-checkout-works',
 'An overview of the buying experience for your customers.',
 '<p>Here''s what happens when a customer places an order in your shop.</p><h3>1. Browse and add to cart</h3><p>Customers browse your products and add items to their cart. The cart shows a running total.</p><h3>2. Checkout</h3><p>At checkout, customers enter their name, email, phone number, and delivery address. They choose a payment method and complete the purchase.</p><h3>3. Confirmation</h3><p>After paying, customers see a confirmation page with their order number. They also receive a confirmation email.</p><h3>4. Tracking</h3><p>Customers can track their order status using their order number and email address on your shop''s order tracking page.</p>',
 'checkout, buying, cart, customer experience', 1, 1),

(13, 'Order tracking for customers', 'order-tracking-for-customers',
 'How your customers can check the status of their orders.',
 '<p>Customers can track their orders without needing to create an account.</p><h3>How it works</h3><p>Your shop has an order tracking page. Customers enter their order number and email address to see the current status of their order.</p><h3>Where to find the tracking page</h3><p>The tracking link is included in the order confirmation email. Customers can also find it in your shop''s navigation.</p><h3>What customers see</h3><p>The tracking page shows the current order status (pending, processing, shipped, or delivered), along with the order details and items purchased.</p>',
 'tracking, order status, customer, lookup', 2, 1),

(13, 'Supported payment methods for buyers', 'supported-payment-methods-for-buyers',
 'What payment options your customers can use at checkout.',
 '<p>The payment methods available at checkout depend on what you''ve enabled in your shop settings.</p><h3>Available methods</h3><ul><li><strong>Credit/Debit cards</strong> &mdash; Via Stripe. Supports Visa, Mastercard, American Express, and more.</li><li><strong>PayPal</strong> &mdash; Customers can pay with their PayPal balance or linked bank account.</li><li><strong>M-Pesa</strong> &mdash; Mobile money payments for customers in supported regions.</li><li><strong>Cash on Delivery</strong> &mdash; Pay in cash when the order arrives.</li></ul><p>Customers will only see the payment methods that you have enabled and configured. We recommend enabling at least two options to give customers flexibility.</p>',
 'payment methods, credit card, paypal, mpesa, customer', 3, 1);
