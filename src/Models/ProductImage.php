<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

/**
 * Product image model.
 *
 * @since 1.0.0
 */
final class ProductImage extends Model
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
     * Batch-load images for multiple products.
     *
     * @since 1.0.0
     *
     * @param  int[] $productIds Product IDs.
     * @return array<int, array>  Keyed by product ID.
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

    /**
     * Get all images for a product.
     *
     * @since 1.0.0
     *
     * @param  int $productId Product ID.
     * @return array[]
     */
    public function findByProduct(int $productId): array
    {
        return static::rawQuery(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC',
            [$productId]
        );
    }

    /**
     * Create a product image record.
     *
     * @since 1.0.0
     *
     * @param  int    $productId Product ID.
     * @param  string $imageUrl  Image URL.
     * @param  int    $sortOrder Display order.
     * @return int    New image ID.
     */
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

    /**
     * Delete all images for a product.
     *
     * @since 1.0.0
     *
     * @param  int  $productId Product ID.
     * @return bool
     */
    public function deleteByProduct(int $productId): bool
    {
        return static::rawExecute(
            'DELETE FROM product_images WHERE product_id = ?',
            [$productId]
        ) >= 0;
    }

    /**
     * Replace a product's entire image gallery.
     *
     * @since 1.0.0
     *
     * @param int      $productId Product ID.
     * @param string[] $imageUrls Ordered image URLs.
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
     * Sync the product's primary image to the first gallery image.
     *
     * @since 1.0.0
     *
     * @param int $productId Product ID.
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
