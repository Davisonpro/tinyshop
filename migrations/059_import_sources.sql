-- 059: Create import_sources table (admin-managed whitelisted sites)
-- Stores CSS selectors and search URL templates for web scraping product data.

CREATE TABLE IF NOT EXISTS import_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    base_url VARCHAR(500) NOT NULL,
    search_url_template VARCHAR(500),
    selectors JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    priority TINYINT UNSIGNED DEFAULT 10,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_source_url (base_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add AI settings to the settings table
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('ai_api_key', '');
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('ai_enabled', '0');
