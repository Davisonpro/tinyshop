ALTER TABLE `users`
    ADD COLUMN `oauth_provider` VARCHAR(20) DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN `oauth_id` VARCHAR(255) DEFAULT NULL AFTER `oauth_provider`,
    MODIFY COLUMN `password_hash` VARCHAR(255) DEFAULT NULL;

ALTER TABLE `users` ADD INDEX `idx_oauth` (`oauth_provider`, `oauth_id`);
