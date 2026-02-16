<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Category
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categories WHERE user_id = ? ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUserAndSlug(int $userId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM categories WHERE user_id = ? AND slug = ?'
        );
        $stmt->execute([$userId, $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUserAsTree(int $userId): array
    {
        $all = $this->findByUser($userId);
        $byParent = [];
        foreach ($all as $cat) {
            $pid = $cat['parent_id'] ?? null;
            $byParent[$pid ?? 0][] = $cat;
        }

        $tree = [];
        foreach (($byParent[0] ?? []) as $parent) {
            $parent['children'] = $byParent[$parent['id']] ?? [];
            $tree[] = $parent;
        }
        return $tree;
    }

    public function create(array $data): int
    {
        $slug = $data['slug'] ?? self::generateSlug($data['name']);
        $slug = $this->ensureUniqueSlug((int) $data['user_id'], $slug);

        $stmt = $this->db->prepare(
            'INSERT INTO categories (user_id, parent_id, name, slug, image_url, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['parent_id'] ?? null,
            $data['name'],
            $slug,
            $data['image_url'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // Auto-generate slug when name changes
        if (isset($data['name']) && !isset($data['slug'])) {
            $existing = $this->findById($id);
            if ($existing) {
                $data['slug'] = self::generateSlug($data['name']);
                $data['slug'] = $this->ensureUniqueSlug((int) $existing['user_id'], $data['slug'], $id);
            }
        }

        $fields = [];
        $values = [];

        $allowed = ['name', 'slug', 'image_url', 'sort_order', 'parent_id'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = 'UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function generateSlug(string $name): string
    {
        $slug = mb_strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-') ?: 'category';
    }

    private function ensureUniqueSlug(int $userId, string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $counter = 1;

        while (true) {
            $sql = 'SELECT id FROM categories WHERE user_id = ? AND slug = ?';
            $params = [$userId, $slug];

            if ($excludeId !== null) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if (!$stmt->fetch()) {
                return $slug;
            }

            $slug = $base . '-' . (++$counter);
        }
    }
}
