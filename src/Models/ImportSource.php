<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

/**
 * Admin-managed whitelisted import sources.
 *
 * @since 1.0.0
 */
final class ImportSource
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Get all active sources ordered by priority.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findActive(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM import_sources WHERE is_active = 1 ORDER BY priority ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all sources (for admin).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM import_sources ORDER BY priority ASC, name ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Find by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM import_sources WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a new import source.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO import_sources (name, base_url, search_url_template, selectors, is_active, priority)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($data['name']),
            trim($data['base_url']),
            trim($data['search_url_template'] ?? ''),
            is_string($data['selectors']) ? $data['selectors'] : json_encode($data['selectors']),
            (int) ($data['is_active'] ?? 1),
            (int) ($data['priority'] ?? 10),
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an import source.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach (['name', 'base_url', 'search_url_template'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = trim($data[$field]);
            }
        }

        if (isset($data['selectors'])) {
            $fields[] = 'selectors = ?';
            $params[] = is_string($data['selectors']) ? $data['selectors'] : json_encode($data['selectors']);
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = (int) $data['is_active'];
        }

        if (isset($data['priority'])) {
            $fields[] = 'priority = ?';
            $params[] = (int) $data['priority'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE import_sources SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete an import source.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM import_sources WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
