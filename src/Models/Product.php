<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Product
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
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
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
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
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(int $userId, string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ? AND p.slug = ?'
        );
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
            'INSERT INTO products (user_id, category_id, name, slug, description, price, compare_price, image_url, sort_order, is_sold, stock_quantity, is_featured, variations, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $data['stock_quantity'] ?? null,
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

        $allowed = ['name', 'slug', 'description', 'price', 'compare_price', 'image_url', 'category_id', 'sort_order', 'is_active', 'is_sold', 'stock_quantity', 'is_featured', 'variations', 'meta_title', 'meta_description'];

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

    /**
     * Atomically decrement stock. Returns false if insufficient stock.
     * Auto-marks product as sold when stock reaches 0.
     */
    public function decrementStock(int $productId, int $qty): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE products SET stock_quantity = stock_quantity - ?, is_sold = CASE WHEN stock_quantity - ? = 0 THEN 1 ELSE is_sold END WHERE id = ? AND stock_quantity IS NOT NULL AND stock_quantity >= ?'
        );
        $stmt->execute([$qty, $qty, $productId, $qty]);
        return $stmt->rowCount() > 0;
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

    public function findActiveByUserPaginated(
        int $userId,
        int $limit = 24,
        int $offset = 0,
        ?string $search = null,
        ?string $categoryIds = null,
        string $sort = 'default'
    ): array {
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.user_id = ? AND p.is_active = 1';
        $binds = [[$userId, PDO::PARAM_INT]];

        if ($search !== null && $search !== '') {
            $sql .= ' AND p.name LIKE ?';
            $binds[] = ['%' . $search . '%', PDO::PARAM_STR];
        }

        if ($categoryIds !== null && $categoryIds !== '') {
            $ids = array_filter(array_map('intval', explode(',', $categoryIds)));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " AND p.category_id IN ({$placeholders})";
                foreach ($ids as $id) {
                    $binds[] = [$id, PDO::PARAM_INT];
                }
            }
        }

        $sql .= match ($sort) {
            'price_asc' => ' ORDER BY p.price ASC',
            'price_desc' => ' ORDER BY p.price DESC',
            'newest' => ' ORDER BY p.created_at DESC',
            'name_asc' => ' ORDER BY p.name ASC',
            default => ' ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC',
        };

        $sql .= ' LIMIT ? OFFSET ?';
        $binds[] = [$limit, PDO::PARAM_INT];
        $binds[] = [$offset, PDO::PARAM_INT];

        $stmt = $this->db->prepare($sql);
        foreach ($binds as $i => $bind) {
            $stmt->bindValue($i + 1, $bind[0], $bind[1]);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countActiveByUser(
        int $userId,
        ?string $search = null,
        ?string $categoryIds = null
    ): int {
        $sql = 'SELECT COUNT(*) FROM products p WHERE p.user_id = ? AND p.is_active = 1';
        $binds = [[$userId, PDO::PARAM_INT]];

        if ($search !== null && $search !== '') {
            $sql .= ' AND p.name LIKE ?';
            $binds[] = ['%' . $search . '%', PDO::PARAM_STR];
        }

        if ($categoryIds !== null && $categoryIds !== '') {
            $ids = array_filter(array_map('intval', explode(',', $categoryIds)));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " AND p.category_id IN ({$placeholders})";
                foreach ($ids as $id) {
                    $binds[] = [$id, PDO::PARAM_INT];
                }
            }
        }

        $stmt = $this->db->prepare($sql);
        foreach ($binds as $i => $bind) {
            $stmt->bindValue($i + 1, $bind[0], $bind[1]);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findRelated(int $userId, int $excludeId, ?int $categoryId, int $limit = 6): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.user_id = ? AND p.is_active = 1 AND p.id != ?
                ORDER BY (p.category_id IS NOT NULL AND p.category_id = ?) DESC,
                         p.is_featured DESC, p.sort_order ASC, p.created_at DESC
                LIMIT ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $categoryId, $categoryId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function reorder(int $userId, array $orderedIds): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET sort_order = ? WHERE id = ? AND user_id = ?');
        foreach ($orderedIds as $index => $id) {
            $stmt->execute([$index, $id, $userId]);
        }
        return true;
    }

    public function findLowStock(int $userId, int $threshold = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, stock_quantity, slug
             FROM products
             WHERE user_id = ? AND stock_quantity IS NOT NULL AND stock_quantity <= ? AND stock_quantity > 0 AND is_active = 1
             ORDER BY stock_quantity ASC, name ASC'
        );
        $stmt->execute([$userId, $threshold]);
        return $stmt->fetchAll();
    }
}
