-- Migration: Add sold status and compare-at price to products
-- Run: mysql -u root tinyshop < migrations/005_sold_and_compare_price.sql

ALTER TABLE `products`
    ADD COLUMN `is_sold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
    ADD COLUMN `compare_price` DECIMAL(10,2) DEFAULT NULL AFTER `price`;
