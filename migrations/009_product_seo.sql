ALTER TABLE `products`
    ADD COLUMN `meta_title` VARCHAR(200) DEFAULT NULL AFTER `variations`,
    ADD COLUMN `meta_description` VARCHAR(500) DEFAULT NULL AFTER `meta_title`;
