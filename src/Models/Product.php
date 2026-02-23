<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;

class Product extends Model
{
    protected static array $definition = [
        'table'   => 'products',
        'primary' => 'id',
        'fields'  => [
            'user_id'          => ['type' => FieldType::Int, 'required' => true],
            'category_id'      => ['type' => FieldType::Int],
            'name'             => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'slug'             => ['type' => FieldType::String, 'maxLength' => 255],
            'description'      => ['type' => FieldType::Text],
            'full_description' => ['type' => FieldType::LongText],
            'price'            => ['type' => FieldType::Decimal, 'required' => true],
            'compare_price'    => ['type' => FieldType::Decimal],
            'image_url'        => ['type' => FieldType::String, 'maxLength' => 500],
            'sort_order'       => ['type' => FieldType::Int, 'default' => 0],
            'is_active'        => ['type' => FieldType::Bool, 'default' => 1],
            'is_sold'          => ['type' => FieldType::Bool, 'default' => 0],
            'stock_quantity'   => ['type' => FieldType::Int],
            'is_featured'      => ['type' => FieldType::Bool, 'default' => 0],
            'variations'       => ['type' => FieldType::Json],
            'meta_title'       => ['type' => FieldType::String, 'maxLength' => 255],
            'meta_description' => ['type' => FieldType::String, 'maxLength' => 500],
            'source_url'       => ['type' => FieldType::String, 'maxLength' => 500],
            'created_at'       => ['type' => FieldType::DateTime],
            'updated_at'       => ['type' => FieldType::DateTime],
        ],
    ];

    public function findByUser(int $userId, int $limit = 100, int $offset = 0): array
    {
        $db = static::db();
        $stmt = $db->prepare(
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        return static::rawQuery($sql, $params);
    }

    public function findById(int $id): ?array
    {
        $rows = static::rawQuery(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ?',
            [$id]
        );
        return $rows[0] ?? null;
    }

    public function findBySlug(int $userId, string $slug): ?array
    {
        $rows = static::rawQuery(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ? AND p.slug = ?',
            [$userId, $slug]
        );
        return $rows[0] ?? null;
    }

    public function findBySourceUrl(int $userId, string $sourceUrl): ?array
    {
        $rows = static::rawQuery(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ? AND p.source_url = ?',
            [$userId, $sourceUrl]
        );
        return $rows[0] ?? null;
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
            $count = (int) static::rawScalar($sql, $params);
            if ($count === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }

    public function create(array $data): int
    {
        $product = new static();
        $product->fill([
            'user_id'          => $data['user_id'],
            'category_id'      => $data['category_id'] ?? null,
            'name'             => $data['name'],
            'slug'             => $data['slug'] ?? null,
            'description'      => $data['description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'price'            => $data['price'],
            'compare_price'    => $data['compare_price'] ?? null,
            'image_url'        => $data['image_url'] ?? null,
            'sort_order'       => $data['sort_order'] ?? 0,
            'is_sold'          => $data['is_sold'] ?? 0,
            'stock_quantity'   => $data['stock_quantity'] ?? null,
            'is_featured'      => $data['is_featured'] ?? 0,
            'variations'       => $data['variations'] ?? null,
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'source_url'       => $data['source_url'] ?? null,
        ]);
        $product->save();
        return (int) $product->getId();
    }

    public function update(int $id, array $data): bool
    {
        $product = static::find($id);
        if (!$product) {
            return false;
        }

        $allowed = [
            'name', 'slug', 'description', 'full_description', 'price', 'compare_price',
            'image_url', 'category_id', 'sort_order', 'is_active', 'is_sold',
            'stock_quantity', 'is_featured', 'variations', 'meta_title', 'meta_description',
            'source_url',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $product->{$field} = $data[$field];
            }
        }

        return $product->save();
    }

    /**
     * Atomically decrement stock. Returns false if insufficient stock.
     * Auto-marks product as sold when stock reaches 0.
     */
    public function decrementStock(int $productId, int $qty): bool
    {
        return static::rawExecute(
            'UPDATE products SET stock_quantity = stock_quantity - ?, is_sold = CASE WHEN stock_quantity - ? = 0 THEN 1 ELSE is_sold END WHERE id = ? AND stock_quantity IS NOT NULL AND stock_quantity >= ?',
            [$qty, $qty, $productId, $qty]
        ) > 0;
    }

    public function countByUser(int $userId): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM products WHERE user_id = ?',
            [$userId]
        );
    }

    public function countAll(): int
    {
        return (int) static::rawScalar('SELECT COUNT(*) FROM products');
    }

    public function countActive(): int
    {
        return (int) static::rawScalar('SELECT COUNT(*) FROM products WHERE is_active = 1');
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

        $db = static::db();
        $stmt = $db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        return (int) static::rawScalar($sql, $params);
    }

    /**
     * Tokenize a search query into individual words (min 2 chars each, max 5 tokens).
     */
    private function tokenizeSearch(string $search): array
    {
        $tokens = array_filter(
            preg_split('/\s+/', trim($search)),
            fn(string $t): bool => mb_strlen($t) >= 2
        );

        if (empty($tokens)) {
            $tokens = [trim($search)];
        }

        return array_slice(array_values($tokens), 0, 5);
    }

    /**
     * Build multi-field, multi-word WHERE conditions.
     */
    private function buildSearchWhere(string $search, array &$binds): string
    {
        $tokens = $this->tokenizeSearch($search);
        $clauses = [];

        foreach ($tokens as $token) {
            $like = '%' . $token . '%';
            $clauses[] = '(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
            $binds[] = [$like, PDO::PARAM_STR];
            $binds[] = [$like, PDO::PARAM_STR];
            $binds[] = [$like, PDO::PARAM_STR];
        }

        return ' AND (' . implode(' AND ', $clauses) . ')';
    }

    /**
     * Build relevance scoring expression for search result ranking.
     */
    private function buildRelevanceScore(string $search, array &$binds): string
    {
        $trimmed = trim($search);
        $tokens = $this->tokenizeSearch($search);
        $parts = [];

        $parts[] = 'CASE WHEN p.name = ? THEN 200 ELSE 0 END';
        $binds[] = [$trimmed, PDO::PARAM_STR];

        $parts[] = 'CASE WHEN p.name LIKE ? THEN 80 ELSE 0 END';
        $binds[] = [$trimmed . '%', PDO::PARAM_STR];

        foreach ($tokens as $token) {
            $like = '%' . $token . '%';

            $parts[] = 'CASE WHEN p.name LIKE ? THEN 30 ELSE 0 END';
            $binds[] = [$like, PDO::PARAM_STR];

            $parts[] = 'CASE WHEN c.name LIKE ? THEN 10 ELSE 0 END';
            $binds[] = [$like, PDO::PARAM_STR];

            $parts[] = 'CASE WHEN p.description LIKE ? THEN 3 ELSE 0 END';
            $binds[] = [$like, PDO::PARAM_STR];
        }

        return '(' . implode(' + ', $parts) . ')';
    }

    public function findActiveByUserPaginated(
        int $userId,
        int $limit = 24,
        int $offset = 0,
        ?string $search = null,
        ?string $categoryIds = null,
        string $sort = 'default'
    ): array {
        $hasSearch = $search !== null && $search !== '';
        $binds = [];

        $selectRelevance = '';
        if ($hasSearch) {
            $selectRelevance = ', ' . $this->buildRelevanceScore($search, $binds) . ' AS search_relevance';
        }

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug{$selectRelevance}
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.user_id = ? AND p.is_active = 1";
        $binds[] = [$userId, PDO::PARAM_INT];

        if ($hasSearch) {
            $sql .= $this->buildSearchWhere($search, $binds);
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

        if ($hasSearch && $sort === 'default') {
            $sql .= ' ORDER BY search_relevance DESC, p.is_featured DESC, p.name ASC';
        } else {
            $sql .= match ($sort) {
                'price_asc' => ' ORDER BY p.price ASC',
                'price_desc' => ' ORDER BY p.price DESC',
                'newest' => ' ORDER BY p.created_at DESC',
                'name_asc' => ' ORDER BY p.name ASC',
                default => ' ORDER BY p.is_featured DESC, p.sort_order ASC, p.created_at DESC',
            };
        }

        $sql .= ' LIMIT ? OFFSET ?';
        $binds[] = [$limit, PDO::PARAM_INT];
        $binds[] = [$offset, PDO::PARAM_INT];

        $db = static::db();
        $stmt = $db->prepare($sql);
        foreach ($binds as $i => $bind) {
            $stmt->bindValue($i + 1, $bind[0], $bind[1]);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countActiveByUser(
        int $userId,
        ?string $search = null,
        ?string $categoryIds = null
    ): int {
        $sql = 'SELECT COUNT(*) FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.user_id = ? AND p.is_active = 1';
        $binds = [[$userId, PDO::PARAM_INT]];

        if ($search !== null && $search !== '') {
            $sql .= $this->buildSearchWhere($search, $binds);
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

        $db = static::db();
        $stmt = $db->prepare($sql);
        foreach ($binds as $i => $bind) {
            $stmt->bindValue($i + 1, $bind[0], $bind[1]);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function findRelated(int $userId, int $excludeId, ?int $categoryId, int $limit = 6): array
    {
        $db = static::db();
        $stmt = $db->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = ? AND p.is_active = 1 AND p.id != ?
             ORDER BY (p.category_id IS NOT NULL AND p.category_id = ?) DESC,
                      p.is_featured DESC, p.sort_order ASC, p.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $categoryId, $categoryId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(4, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function reorder(int $userId, array $orderedIds): bool
    {
        $db = static::db();
        $stmt = $db->prepare('UPDATE products SET sort_order = ? WHERE id = ? AND user_id = ?');
        foreach ($orderedIds as $index => $id) {
            $stmt->execute([$index, $id, $userId]);
        }
        return true;
    }

    public function findLowStock(int $userId, int $threshold = 5): array
    {
        return static::rawQuery(
            'SELECT id, name, stock_quantity, slug
             FROM products
             WHERE user_id = ? AND stock_quantity IS NOT NULL AND stock_quantity <= ? AND stock_quantity > 0 AND is_active = 1
             ORDER BY stock_quantity ASC, name ASC',
            [$userId, $threshold]
        );
    }
}
