-- SEO & Marketing settings
INSERT INTO `settings` (`key`, `value`) VALUES
    ('google_verification', ''),
    ('bing_verification', ''),
    ('google_analytics_id', ''),
    ('facebook_pixel_id', ''),
    ('robots_extra', '')
ON DUPLICATE KEY UPDATE `key` = `key`;
