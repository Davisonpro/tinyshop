-- Per-shop search engine verification codes (for custom domain shops)
ALTER TABLE users
    ADD COLUMN google_verification VARCHAR(100) DEFAULT NULL AFTER announcement_text,
    ADD COLUMN bing_verification VARCHAR(100) DEFAULT NULL AFTER google_verification;
