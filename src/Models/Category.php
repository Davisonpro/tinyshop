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
        $stmt = $this->db->prepare(
            'INSERT INTO categories (user_id, parent_id, name, image_url, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['parent_id'] ?? null,
            $data['name'],
            $data['image_url'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowed = ['name', 'image_url', 'sort_order', 'parent_id'];

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
}
