<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

/**
 * Platform settings model.
 *
 * @since 1.0.0
 */
final class Setting
{
    private readonly PDO $db;
    private static ?array $cache = null;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Get a setting value by key.
     *
     * @since 1.0.0
     *
     * @param string  $key     Setting key.
     * @param ?string $default Fallback if key doesn't exist.
     * @return string|null
     */
    public function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }

    /**
     * Get all settings.
     *
     * @since 1.0.0
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return self::$cache;
    }

    /**
     * Save a single setting (upsert).
     *
     * @since 1.0.0
     *
     * @param string  $key   Setting key.
     * @param ?string $value Setting value.
     */
    public function set(string $key, ?string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        $stmt->execute([$key, $value]);
        self::$cache = null;
    }

    /**
     * Save multiple settings at once (upsert).
     *
     * @since 1.0.0
     *
     * @param array<string, string|null> $data Key => value pairs.
     */
    public function setMany(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        self::$cache = null;
    }

    /** Load all rows from the settings table. */
    private function loadAll(): array
    {
        $stmt = $this->db->query('SELECT `key`, `value` FROM `settings`');
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
