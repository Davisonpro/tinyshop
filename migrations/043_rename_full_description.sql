-- Rename short_description to full_description
-- The existing `description` column is the short description.
-- The new `full_description` column houses the detailed/long description.
ALTER TABLE products CHANGE COLUMN short_description full_description TEXT DEFAULT NULL;
