-- Add slug column to categories for SEO-friendly URLs
ALTER TABLE categories ADD COLUMN slug VARCHAR(255) NULL AFTER name;

-- Generate slugs from existing category names
UPDATE categories SET slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(name), ' ', '-'), '&', 'and'), '''', ''), '"', ''), '--', '-'));

-- Add unique index per user (each seller's categories must have unique slugs)
ALTER TABLE categories ADD UNIQUE INDEX idx_categories_user_slug (user_id, slug);
