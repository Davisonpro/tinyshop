-- Design settings: visibility toggles + announcement bar
ALTER TABLE users
    ADD COLUMN show_store_name TINYINT(1) NOT NULL DEFAULT 1 AFTER shop_theme,
    ADD COLUMN show_tagline TINYINT(1) NOT NULL DEFAULT 1 AFTER show_store_name,
    ADD COLUMN show_search TINYINT(1) NOT NULL DEFAULT 1 AFTER show_tagline,
    ADD COLUMN show_categories TINYINT(1) NOT NULL DEFAULT 1 AFTER show_search,
    ADD COLUMN show_sort_toolbar TINYINT(1) NOT NULL DEFAULT 1 AFTER show_categories,
    ADD COLUMN show_desktop_footer TINYINT(1) NOT NULL DEFAULT 1 AFTER show_sort_toolbar,
    ADD COLUMN announcement_text VARCHAR(500) NULL DEFAULT NULL AFTER show_desktop_footer;
