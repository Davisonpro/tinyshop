<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Services\DB;

/**
 * Theme customization options model.
 *
 * @since 1.0.0
 */
final class ThemeOption
{
    private readonly PDO $db;

    private static ?array $cache = null;
    private static ?int $cacheUserId = null;
    private static ?string $cacheTheme = null;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Get a single theme option value.
     *
     * @since 1.0.0
     *
     * @param  int     $userId    Seller ID.
     * @param  string  $themeSlug Theme slug.
     * @param  string  $name      Option name.
     * @param  ?string $default   Fallback value.
     * @return ?string
     */
    public function get(int $userId, string $themeSlug, string $name, ?string $default = null): ?string
    {
        $all = $this->getAll($userId, $themeSlug);
        return array_key_exists($name, $all) ? $all[$name] : $default;
    }

    /**
     * Get all theme options for a seller's theme.
     *
     * @since 1.0.0
     *
     * @param  int    $userId    Seller ID.
     * @param  string $themeSlug Theme slug.
     * @return array<string, ?string>
     */
    public function getAll(int $userId, string $themeSlug): array
    {
        if (
            self::$cache !== null
            && self::$cacheUserId === $userId
            && self::$cacheTheme === $themeSlug
        ) {
            return self::$cache;
        }

        $stmt = $this->db->prepare(
            'SELECT option_name, option_value FROM theme_options WHERE user_id = ? AND theme_slug = ?'
        );
        $stmt->execute([$userId, $themeSlug]);

        $options = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $options[$row['option_name']] = $row['option_value'];
        }

        self::$cache = $options;
        self::$cacheUserId = $userId;
        self::$cacheTheme = $themeSlug;

        return $options;
    }

    /**
     * Set a single theme option (upsert).
     *
     * @since 1.0.0
     *
     * @param int     $userId    Seller ID.
     * @param string  $themeSlug Theme slug.
     * @param string  $name      Option name.
     * @param ?string $value     Option value.
     */
    public function set(int $userId, string $themeSlug, string $name, ?string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO theme_options (user_id, theme_slug, option_name, option_value)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
        );
        $stmt->execute([$userId, $themeSlug, $name, $value]);
        self::$cache = null;
    }

    /**
     * Set multiple theme options at once (upsert).
     *
     * @since 1.0.0
     *
     * @param int    $userId    Seller ID.
     * @param string $themeSlug Theme slug.
     * @param array  $data      Option name => value pairs.
     */
    public function setMany(int $userId, string $themeSlug, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO theme_options (user_id, theme_slug, option_name, option_value)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)'
        );
        foreach ($data as $name => $value) {
            $stmt->execute([$userId, $themeSlug, $name, $value]);
        }
        self::$cache = null;
    }

    /**
     * Delete a single theme option.
     *
     * @since 1.0.0
     *
     * @param int    $userId    Seller ID.
     * @param string $themeSlug Theme slug.
     * @param string $name      Option name.
     */
    public function delete(int $userId, string $themeSlug, string $name): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM theme_options WHERE user_id = ? AND theme_slug = ? AND option_name = ?'
        );
        $stmt->execute([$userId, $themeSlug, $name]);
        self::$cache = null;
    }
}
