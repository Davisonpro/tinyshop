ALTER TABLE `products` ADD COLUMN `slug` VARCHAR(255) DEFAULT NULL AFTER `name`;
ALTER TABLE `products` ADD UNIQUE INDEX `idx_user_slug` (`user_id`, `slug`);

-- Generate slugs for existing products
UPDATE `products` SET `slug` = LOWER(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(TRIM(`name`), ' ', '-'),
                    '&', 'and'),
                "'", ''),
            '"', ''),
        '--', '-')
) WHERE `slug` IS NULL;
