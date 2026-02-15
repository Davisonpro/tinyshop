<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class ProductImage
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Batch-load images for multiple products in a single query.
     * @return array<int, array> Map of productId => images[]
     */
    public function findByProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM product_images WHERE product_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute($productIds);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['product_id']][] = $row;
        }

        return $grouped;
    }

    public function findByProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function create(int $productId, string $imageUrl, int $sortOrder = 0): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)'
        );
        $stmt->execute([$productId, $imageUrl, $sortOrder]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM product_images WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function deleteByProduct(int $productId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM product_images WHERE product_id = ?');
        return $stmt->execute([$productId]);
    }

    /**
     * Sync images for a product: delete old, insert new, update products.image_url.
     */
    public function sync(int $productId, array $imageUrls): void
    {
        $this->deleteByProduct($productId);

        foreach ($imageUrls as $i => $url) {
            if (!empty($url)) {
                $this->create($productId, $url, $i);
            }
        }

        $this->syncPrimary($productId);
    }

    /**
     * Set products.image_url to the first image, or null if none.
     */
    public function syncPrimary(int $productId): void
    {
        $stmt = $this->db->prepare(
            'SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1'
        );
        $stmt->execute([$productId]);
        $url = $stmt->fetchColumn() ?: null;

        $stmt = $this->db->prepare('UPDATE products SET image_url = ? WHERE id = ?');
        $stmt->execute([$url, $productId]);
    }
}
