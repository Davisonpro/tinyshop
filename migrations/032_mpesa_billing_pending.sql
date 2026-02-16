-- Tracks pending M-Pesa billing payments (webhook can't access user session)
CREATE TABLE IF NOT EXISTS billing_mpesa_pending (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    billing_cycle VARCHAR(10) NOT NULL DEFAULT 'monthly',
    checkout_request_id VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_checkout_req (checkout_request_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
