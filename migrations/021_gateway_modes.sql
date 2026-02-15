-- Per-gateway sandbox/live mode (replaces single payment_mode)
ALTER TABLE users
    ADD COLUMN stripe_mode ENUM('test','live') NOT NULL DEFAULT 'test' AFTER stripe_secret_key,
    ADD COLUMN paypal_mode ENUM('test','live') NOT NULL DEFAULT 'test' AFTER paypal_secret;

-- Copy existing payment_mode to both gateway modes
UPDATE users SET stripe_mode = payment_mode, paypal_mode = payment_mode WHERE payment_mode = 'live';
