-- Add referrer domain tracking to shop_views
ALTER TABLE `shop_views`
  ADD COLUMN `referer_domain` VARCHAR(100) DEFAULT NULL AFTER `visitor_hash`;

ALTER TABLE `shop_views`
  ADD INDEX `idx_user_referer_date` (`user_id`, `referer_domain`, `created_at`);
