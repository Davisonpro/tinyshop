-- Pay on Delivery option for shops
ALTER TABLE users
    ADD COLUMN cod_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER paypal_enabled;
