CREATE TABLE `page_views` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_path` VARCHAR(255) NOT NULL,
    `visitor_hash` VARCHAR(64) NOT NULL,
    `referer_domain` VARCHAR(100) DEFAULT NULL,
    `utm_source` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_page_date` (`page_path`, `created_at`),
    INDEX `idx_date` (`created_at`),
    INDEX `idx_dedup` (`visitor_hash`, `page_path`, `created_at`),
    INDEX `idx_referer_date` (`referer_domain`, `created_at`),
    INDEX `idx_utm_date` (`utm_source`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
