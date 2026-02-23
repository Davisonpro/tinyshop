<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

class ProductImage extends Model
{
    protected static array $definition = [
        'table'   => 'product_images',
        'primary' => 'id',
        'fields'  => [
            'product_id' => ['type' => FieldType::Int, 'required' => true],
            'image_url'  => ['type' => FieldType::String, 'required' => true, 'maxLength' => 500],
            'sort_order' => ['type' => FieldType::Int, 'default' => 0],
            'created_at' => ['type' => FieldType::DateTime],
        ],
    ];

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
        $rows = static::rawQuery(
            "SELECT * FROM product_images WHERE product_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC",
            $productIds
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['product_id']][] = $row;
        }

        return $grouped;
    }

    public function findByProduct(int $productId): array
    {
        return static::rawQuery(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
            [$productId]
        );
    }

    public function create(int $productId, string $imageUrl, int $sortOrder = 0): int
    {
        $img = new static();
        $img->fill([
            'product_id' => $productId,
            'image_url'  => $imageUrl,
            'sort_order' => $sortOrder,
        ]);
        $img->save();
        return (int) $img->getId();
    }

    public function deleteByProduct(int $productId): bool
    {
        return static::rawExecute(
            'DELETE FROM product_images WHERE product_id = ?',
            [$productId]
        ) >= 0;
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
        $url = static::rawScalar(
            'SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1',
            [$productId]
        );

        static::rawExecute(
            'UPDATE products SET image_url = ? WHERE id = ?',
            [$url ?: null, $productId]
        );
    }
}
