-- Migration: Add image_url to categories for icon/image support
-- Run: mysql -u root tinyshop < migrations/004_category_images.sql

ALTER TABLE `categories`
    ADD COLUMN `image_url` VARCHAR(500) DEFAULT NULL AFTER `name`;
