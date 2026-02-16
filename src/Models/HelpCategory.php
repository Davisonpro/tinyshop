<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class HelpCategory
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT c.*, COUNT(a.id) AS article_count
             FROM help_categories c
             LEFT JOIN help_articles a ON a.category_id = c.id AND a.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findAllAdmin(): array
    {
        $stmt = $this->db->query(
            'SELECT c.*, COUNT(a.id) AS article_count
             FROM help_categories c
             LEFT JOIN help_articles a ON a.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM help_categories WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO help_categories (name, slug, icon, description, sort_order)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['icon'] ?? 'fa-circle-question',
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['name', 'slug', 'icon', 'description', 'sort_order'];

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
        $sql = 'UPDATE help_categories SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM help_articles WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM help_categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM help_categories WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM help_categories WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }
}
