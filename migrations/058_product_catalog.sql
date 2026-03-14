-- 058: Create product_catalog table (shared Product Knowledge Base)
-- Caches product info fetched from external sources so lookups benefit all sellers.

CREATE TABLE IF NOT EXISTS product_catalog (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(255) NOT NULL,
    canonical_name VARCHAR(255) NOT NULL,
    description TEXT,
    full_description LONGTEXT,
    specs JSON,
    images JSON,
    category_hint VARCHAR(255),
    source_url VARCHAR(500),
    source_site VARCHAR(255),
    quality_score TINYINT UNSIGNED DEFAULT 0,
    lookup_count INT UNSIGNED DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_brand_model (brand, model),
    INDEX idx_brand (brand),
    FULLTEXT INDEX ft_catalog_search (canonical_name, brand, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
