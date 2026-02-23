<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Services\DB;

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

    public function get(int $userId, string $themeSlug, string $name, ?string $default = null): ?string
    {
        $all = $this->getAll($userId, $themeSlug);
        return array_key_exists($name, $all) ? $all[$name] : $default;
    }

    /**
     * @return array<string, string|null>
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
     * @param array<string, string|null> $data
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

    public function delete(int $userId, string $themeSlug, string $name): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM theme_options WHERE user_id = ? AND theme_slug = ? AND option_name = ?'
        );
        $stmt->execute([$userId, $themeSlug, $name]);
        self::$cache = null;
    }
}
