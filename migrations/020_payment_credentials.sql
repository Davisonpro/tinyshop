-- Add payment gateway credentials to users (sellers)
ALTER TABLE users
    ADD COLUMN stripe_public_key VARCHAR(255) NULL AFTER shop_theme,
    ADD COLUMN stripe_secret_key VARCHAR(255) NULL AFTER stripe_public_key,
    ADD COLUMN paypal_client_id VARCHAR(255) NULL AFTER stripe_secret_key,
    ADD COLUMN paypal_secret VARCHAR(255) NULL AFTER paypal_client_id,
    ADD COLUMN payment_mode ENUM('test','live') NOT NULL DEFAULT 'test' AFTER paypal_secret;
