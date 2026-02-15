<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Setting
{
    private readonly PDO $db;
    private static ?array $cache = null;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }

    public function all(): array
    {
        if (self::$cache === null) {
            self::$cache = $this->loadAll();
        }
        return self::$cache;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        $stmt->execute([$key, $value]);
        self::$cache = null;
    }

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
