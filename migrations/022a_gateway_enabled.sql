-- Enable/disable toggles for payment gateways (keeps credentials intact)
ALTER TABLE users
    ADD COLUMN stripe_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER stripe_mode,
    ADD COLUMN paypal_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER paypal_mode;
