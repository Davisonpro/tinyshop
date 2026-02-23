-- Add show_logo toggle column to users table
ALTER TABLE `users` ADD COLUMN `show_logo` TINYINT(1) NOT NULL DEFAULT 1 AFTER `shop_theme`;
