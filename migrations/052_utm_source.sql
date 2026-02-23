ALTER TABLE `shop_views`
  ADD COLUMN `utm_source` VARCHAR(50) DEFAULT NULL AFTER `referer_domain`;

CREATE INDEX `idx_user_utm_date` ON `shop_views` (`user_id`, `utm_source`, `created_at`);
