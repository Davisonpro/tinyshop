<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Product
{
    private PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ?
             ORDER BY p.sort_order ASC, p.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findActiveByUser(int $userId, ?int $categoryId = null): array
    {
        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.user_id = ? AND p.is_active = 1';
        $params = [$userId];

        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $categoryId;
        }

        $sql .= ' ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(int $userId, string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE user_id = ? AND slug = ?');
        $stmt->execute([$userId, $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function ensureUniqueSlug(int $userId, string $slug, ?int $excludeId = null): string
    {
        $base = $slug;
        $i = 1;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM products WHERE user_id = ? AND slug = ?';
            $params = [$userId, $slug];
            if ($excludeId) {
                $sql .= ' AND id != ?';
                $params[] = $excludeId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (user_id, category_id, name, slug, description, price, compare_price, image_url, sort_order, is_sold, is_featured, variations, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['category_id'] ?? null,
            $data['name'],
            $data['slug'] ?? null,
            $data['description'] ?? null,
            $data['price'],
            $data['compare_price'] ?? null,
            $data['image_url'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_sold'] ?? 0,
            $data['is_featured'] ?? 0,
            $data['variations'] ?? null,
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowed = ['name', 'slug', 'description', 'price', 'compare_price', 'image_url', 'category_id', 'sort_order', 'is_active', 'is_sold', 'is_featured', 'variations', 'meta_title', 'meta_description'];

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
        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM products');
        return (int) $stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM products WHERE is_active = 1');
        return (int) $stmt->fetchColumn();
    }

    public function findAllAdmin(int $limit = 50, int $offset = 0, string $search = ''): array
    {
        $sql = 'SELECT p.*, u.name AS seller_name, u.subdomain, u.store_name
                FROM products p
                JOIN users u ON u.id = p.user_id';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE (p.name LIKE ? OR u.store_name LIKE ? OR u.name LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';

        $stmt = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllAdmin(string $search = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM products p JOIN users u ON u.id = p.user_id';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE (p.name LIKE ? OR u.store_name LIKE ? OR u.name LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function reorder(int $userId, array $orderedIds): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET sort_order = ? WHERE id = ? AND user_id = ?');
        foreach ($orderedIds as $index => $id) {
            $stmt->execute([$index, $id, $userId]);
        }
        return true;
    }
}
