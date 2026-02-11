ALTER TABLE `users`
    ADD COLUMN `custom_domain` VARCHAR(255) DEFAULT NULL AFTER `subdomain`,
    ADD UNIQUE KEY `uniq_custom_domain` (`custom_domain`);
