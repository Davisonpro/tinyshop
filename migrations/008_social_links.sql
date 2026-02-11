ALTER TABLE `users`
    ADD COLUMN `social_instagram` VARCHAR(100) DEFAULT NULL AFTER `map_link`,
    ADD COLUMN `social_tiktok` VARCHAR(100) DEFAULT NULL AFTER `social_instagram`,
    ADD COLUMN `social_facebook` VARCHAR(255) DEFAULT NULL AFTER `social_tiktok`;
