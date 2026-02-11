-- Migration: Add variations JSON column to products
-- Run: mysql -u root -ppassword tinyshop < migrations/006_product_variations.sql

ALTER TABLE `products` ADD COLUMN `variations` JSON DEFAULT NULL AFTER `is_sold`;
