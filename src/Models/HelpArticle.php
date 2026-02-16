<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class HelpArticle
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findPublished(): array
    {
        $stmt = $this->db->query(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.is_published = 1
             ORDER BY c.sort_order ASC, a.sort_order ASC, a.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.slug = ? AND a.is_published = 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM help_articles
             WHERE category_id = ? AND is_published = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             ORDER BY c.sort_order ASC, a.sort_order ASC, a.id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
             FROM help_articles a
             JOIN help_categories c ON c.id = a.category_id
             WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO help_articles (category_id, title, slug, summary, content, keywords, sort_order, is_published)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['category_id'],
            $data['title'],
            $data['slug'],
            $data['summary'] ?? null,
            $data['content'] ?? null,
            $data['keywords'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_published'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['category_id', 'title', 'slug', 'summary', 'content', 'keywords', 'sort_order', 'is_published'];

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
        $sql = 'UPDATE help_articles SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM help_articles WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM help_articles WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM help_articles WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Published articles grouped by category slug — for frontend display.
     */
    public function grouped(): array
    {
        $articles = $this->findPublished();
        $grouped = [];
        foreach ($articles as $article) {
            $catSlug = $article['category_slug'];
            $grouped[$catSlug][] = $article;
        }
        return $grouped;
    }
}
