-- Plans table — admin-defined subscription tiers
CREATE TABLE IF NOT EXISTS plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(300) NULL,
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency VARCHAR(3) NOT NULL DEFAULT 'KES',
    max_products INT UNSIGNED NULL DEFAULT NULL,
    allowed_themes TEXT NULL DEFAULT NULL,
    custom_domain_allowed TINYINT(1) NOT NULL DEFAULT 0,
    coupons_allowed TINYINT(1) NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscriptions table — seller subscription records
CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    billing_cycle ENUM('monthly','yearly') NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    payment_gateway VARCHAR(20) NULL,
    payment_reference VARCHAR(255) NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add plan columns to users
ALTER TABLE users
    ADD COLUMN plan_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER currency,
    ADD COLUMN plan_expires_at DATETIME NULL DEFAULT NULL AFTER plan_id;

-- Seed default plans
INSERT INTO plans (name, slug, description, price_monthly, price_yearly, currency,
    max_products, allowed_themes, custom_domain_allowed, coupons_allowed,
    is_default, sort_order)
VALUES
('Free', 'free', 'Get started with the basics', 0, 0, 'KES',
    10, '["classic"]', 0, 0, 1, 0),
('Pro', 'pro', 'Everything you need to grow your business', 1000, 10000, 'KES',
    NULL, NULL, 1, 1, 0, 1);
