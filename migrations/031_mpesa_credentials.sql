-- M-Pesa credentials for sellers (storefront checkout)
ALTER TABLE users
    ADD COLUMN mpesa_shortcode VARCHAR(20) NULL AFTER cod_enabled,
    ADD COLUMN mpesa_consumer_key VARCHAR(255) NULL AFTER mpesa_shortcode,
    ADD COLUMN mpesa_consumer_secret VARCHAR(255) NULL AFTER mpesa_consumer_key,
    ADD COLUMN mpesa_passkey VARCHAR(255) NULL AFTER mpesa_consumer_secret,
    ADD COLUMN mpesa_mode ENUM('test','live') NOT NULL DEFAULT 'test' AFTER mpesa_passkey,
    ADD COLUMN mpesa_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER mpesa_mode;
