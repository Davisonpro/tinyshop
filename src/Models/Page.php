<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Page
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM pages ORDER BY title ASC');
        return $stmt->fetchAll();
    }

    public function findPublished(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM pages WHERE is_published = 1 ORDER BY title ASC'
        );
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pages WHERE slug = ? AND is_published = 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pages (title, slug, content, meta_description, is_published)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['content'] ?? null,
            $data['meta_description'] ?? null,
            $data['is_published'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['title', 'slug', 'content', 'meta_description', 'is_published'];

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
        $sql = 'UPDATE pages SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pages WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM pages WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }
}
