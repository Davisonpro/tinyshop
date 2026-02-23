-- Theme options table (WordPress-style wp_options per seller per theme)
CREATE TABLE IF NOT EXISTS theme_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    theme_slug VARCHAR(50) NOT NULL,
    option_name VARCHAR(191) NOT NULL,
    option_value LONGTEXT DEFAULT NULL,
    UNIQUE KEY uq_theme_option (user_id, theme_slug, option_name),
    KEY idx_user_theme (user_id, theme_slug),
    CONSTRAINT fk_theme_options_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
