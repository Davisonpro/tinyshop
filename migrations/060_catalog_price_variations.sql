-- Add price, compare_price, and variations to product_catalog
-- These were missing, causing AI-enriched data to lose price/variation info on cache

ALTER TABLE product_catalog
    ADD COLUMN price DECIMAL(12,2) UNSIGNED NULL DEFAULT NULL AFTER category_hint,
    ADD COLUMN compare_price DECIMAL(12,2) UNSIGNED NULL DEFAULT NULL AFTER price,
    ADD COLUMN variations JSON NULL DEFAULT NULL AFTER compare_price;
