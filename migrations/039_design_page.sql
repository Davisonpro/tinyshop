-- Design page: color palette, logo alignment, hero slides
ALTER TABLE users
    ADD COLUMN color_palette VARCHAR(20) NOT NULL DEFAULT 'default' AFTER shop_theme,
    ADD COLUMN logo_alignment VARCHAR(10) NOT NULL DEFAULT 'left' AFTER color_palette;

CREATE TABLE hero_slides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    heading VARCHAR(200) DEFAULT NULL,
    subheading VARCHAR(500) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    link_text VARCHAR(100) DEFAULT NULL,
    position SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_hero_user_pos (user_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
