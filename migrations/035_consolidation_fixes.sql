-- Migration 035: Consolidation fixes for production readiness
-- Fixes FK cascades, adds currency to orders, drops dead columns, adds missing FKs

-- 1. Fix FK cascade on orders.product_id (SET NULL instead of CASCADE)
ALTER TABLE orders DROP FOREIGN KEY fk_orders_product;
ALTER TABLE orders ADD CONSTRAINT fk_orders_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- 2. Fix FK cascade on order_items.product_id (SET NULL instead of CASCADE)
ALTER TABLE order_items DROP FOREIGN KEY fk_order_items_product;
ALTER TABLE order_items MODIFY product_id BIGINT UNSIGNED NULL;
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- 3. Add currency column to orders (snapshot at time of purchase)
ALTER TABLE orders ADD COLUMN currency VARCHAR(3) NULL AFTER amount;

-- 4. Drop dead payment_mode column from users (replaced by per-gateway modes)
ALTER TABLE users DROP COLUMN payment_mode;

-- 5. Add FK on users.plan_id
ALTER TABLE users ADD CONSTRAINT fk_users_plan
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL;

-- 6. Add FKs on billing_mpesa_pending
ALTER TABLE billing_mpesa_pending ADD CONSTRAINT fk_bmp_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
ALTER TABLE billing_mpesa_pending ADD CONSTRAINT fk_bmp_plan
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE;
