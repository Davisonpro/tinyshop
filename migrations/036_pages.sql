-- Migration 036: Admin-managed dynamic pages (Terms, Privacy, etc.)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `pages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `content` LONGTEXT,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pages_slug` (`slug`),
    KEY `idx_pages_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pages` (`title`, `slug`, `content`, `meta_description`, `is_published`) VALUES
('Terms of Service', 'terms', '<h3>Terms of Service</h3><p>Please update this page with your terms of service.</p>', 'Terms of Service', 1),
('Privacy Policy', 'privacy', '<h3>Privacy Policy</h3><p>Please update this page with your privacy policy.</p>', 'Privacy Policy', 1);
