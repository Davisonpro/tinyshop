<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

/**
 * Shared product knowledge base model.
 *
 * Caches product info (brand, model, specs, images) so lookups benefit all sellers.
 *
 * @since 1.0.0
 */
final class ProductCatalog
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Find a catalog entry by exact brand + model.
     *
     * @return array<string, mixed>|null
     */
    public function findByBrandModel(string $brand, string $model): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_catalog WHERE brand = ? AND model = ? LIMIT 1'
        );
        $stmt->execute([mb_strtoupper(trim($brand)), mb_strtoupper(trim($model))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * FULLTEXT search by canonical name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchByName(string $query, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT *, MATCH(canonical_name, brand, model) AGAINST(? IN NATURAL LANGUAGE MODE) AS relevance
             FROM product_catalog
             WHERE MATCH(canonical_name, brand, model) AGAINST(? IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC
             LIMIT ?'
        );
        $stmt->execute([trim($query), trim($query), $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Insert or update a catalog entry (keyed on brand + model).
     */
    public function upsert(array $data): int
    {
        $brand = mb_strtoupper(trim($data['brand'] ?? ''));
        $model = mb_strtoupper(trim($data['model'] ?? ''));
        $canonical = trim($data['canonical_name'] ?? "{$brand} {$model}");

        $price = isset($data['price']) && $data['price'] > 0 ? (float) $data['price'] : null;
        $comparePrice = isset($data['compare_price']) && $data['compare_price'] > 0 ? (float) $data['compare_price'] : null;

        $stmt = $this->db->prepare(
            'INSERT INTO product_catalog (brand, model, canonical_name, description, full_description, specs, images, category_hint, price, compare_price, variations, source_url, source_site, quality_score)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                canonical_name = VALUES(canonical_name),
                description = COALESCE(VALUES(description), description),
                full_description = COALESCE(VALUES(full_description), full_description),
                specs = COALESCE(VALUES(specs), specs),
                images = COALESCE(VALUES(images), images),
                category_hint = COALESCE(VALUES(category_hint), category_hint),
                price = COALESCE(VALUES(price), price),
                compare_price = COALESCE(VALUES(compare_price), compare_price),
                variations = COALESCE(VALUES(variations), variations),
                source_url = COALESCE(VALUES(source_url), source_url),
                source_site = COALESCE(VALUES(source_site), source_site),
                quality_score = GREATEST(quality_score, VALUES(quality_score))'
        );

        $stmt->execute([
            $brand,
            $model,
            $canonical,
            $data['description'] ?? null,
            $data['full_description'] ?? null,
            isset($data['specs']) ? json_encode($data['specs']) : null,
            isset($data['images']) ? json_encode($data['images']) : null,
            $data['category_hint'] ?? null,
            $price,
            $comparePrice,
            !empty($data['variations']) ? json_encode($data['variations']) : null,
            $data['source_url'] ?? null,
            $data['source_site'] ?? null,
            (int) ($data['quality_score'] ?? 0),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Increment the lookup counter for a catalog entry.
     */
    public function incrementLookup(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE product_catalog SET lookup_count = lookup_count + 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    /**
     * Find by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_catalog WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * List catalog entries with pagination.
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginate(int $offset = 0, int $limit = 50, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $stmt = $this->db->prepare(
                'SELECT * FROM product_catalog
                 WHERE brand LIKE ? OR model LIKE ? OR canonical_name LIKE ?
                 ORDER BY lookup_count DESC, updated_at DESC
                 LIMIT ? OFFSET ?'
            );
            $like = '%' . $search . '%';
            $stmt->execute([$like, $like, $like, $limit, $offset]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT * FROM product_catalog ORDER BY lookup_count DESC, updated_at DESC LIMIT ? OFFSET ?'
            );
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    /**
     * Delete a catalog entry.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM product_catalog WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Bulk lookup by brand+model pairs. Returns keyed by "BRAND|MODEL".
     *
     * @param array<int, array{brand: string, model: string}> $items
     * @return array<string, array<string, mixed>>
     */
    public function findMany(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $conditions = [];
        $params = [];
        foreach ($items as $item) {
            $conditions[] = '(brand = ? AND model = ?)';
            $params[] = mb_strtoupper(trim($item['brand']));
            $params[] = mb_strtoupper(trim($item['model']));
        }

        $sql = 'SELECT * FROM product_catalog WHERE ' . implode(' OR ', $conditions);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $key = $row['brand'] . '|' . $row['model'];
            $results[$key] = $row;
        }
        return $results;
    }
}
