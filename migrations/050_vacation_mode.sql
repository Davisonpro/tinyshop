ALTER TABLE `users`
  ADD COLUMN `vacation_mode` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
  ADD COLUMN `vacation_message` VARCHAR(500) DEFAULT NULL AFTER `vacation_mode`;
