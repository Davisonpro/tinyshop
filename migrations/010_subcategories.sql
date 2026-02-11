ALTER TABLE `categories`
    ADD COLUMN `parent_id` BIGINT UNSIGNED DEFAULT NULL AFTER `user_id`,
    ADD INDEX `idx_parent` (`parent_id`),
    ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
