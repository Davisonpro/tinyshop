-- Pesapal credentials for sellers (storefront checkout)
ALTER TABLE users
    ADD COLUMN pesapal_consumer_key VARCHAR(255) NULL AFTER mpesa_enabled,
    ADD COLUMN pesapal_consumer_secret VARCHAR(255) NULL AFTER pesapal_consumer_key,
    ADD COLUMN pesapal_mode ENUM('test','live') NOT NULL DEFAULT 'test' AFTER pesapal_consumer_secret,
    ADD COLUMN pesapal_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER pesapal_mode;

-- Pending billing payments for IPN matching (mirrors billing_mpesa_pending)
CREATE TABLE IF NOT EXISTS billing_pesapal_pending (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    billing_cycle VARCHAR(10) NOT NULL DEFAULT 'monthly',
    order_tracking_id VARCHAR(100) NOT NULL,
    merchant_reference VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tracking_id (order_tracking_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
