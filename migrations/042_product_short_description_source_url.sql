-- Short description for SEO/quick display and source URL for import tracking
ALTER TABLE products
    ADD COLUMN short_description TEXT DEFAULT NULL AFTER description,
    ADD COLUMN source_url VARCHAR(500) DEFAULT NULL AFTER meta_description;

-- Index for re-import detection: find products previously imported from a URL
ALTER TABLE products
    ADD INDEX idx_source_url (source_url(191));
