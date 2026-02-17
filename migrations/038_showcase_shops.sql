-- Showcase shops on landing page
ALTER TABLE users
    ADD COLUMN is_showcased TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER is_active;
