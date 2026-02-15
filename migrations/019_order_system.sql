-- Expand orders table for multi-item checkout system
ALTER TABLE orders
    ADD COLUMN order_number VARCHAR(20) NULL AFTER id,
    ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_phone,
    ADD COLUMN shipping_address JSON NULL AFTER customer_email,
    ADD COLUMN subtotal DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER amount,
    ADD COLUMN payment_gateway VARCHAR(20) NULL AFTER payment_method,
    ADD COLUMN payment_intent_id VARCHAR(255) NULL AFTER payment_gateway,
    ADD COLUMN notes TEXT NULL AFTER payment_intent_id,
    MODIFY COLUMN product_id BIGINT UNSIGNED NULL,
    ADD UNIQUE KEY idx_order_number (order_number),
    ADD INDEX idx_payment_intent (payment_intent_id);

-- Order line items table
CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_image VARCHAR(500) NULL,
    variation VARCHAR(200) NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
