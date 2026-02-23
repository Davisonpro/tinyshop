ALTER TABLE `users`
  ADD COLUMN `notify_email` TINYINT(1) NOT NULL DEFAULT 1 AFTER `vacation_message`;
