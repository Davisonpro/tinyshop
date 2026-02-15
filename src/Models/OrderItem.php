<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class OrderItem
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function createBatch(int $orderId, array $items): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, product_image, variation, quantity, unit_price, total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['product_image'] ?? null,
                $item['variation'] ?? null,
                $item['quantity'],
                $item['unit_price'],
                $item['total'],
            ]);
        }
    }

    public function findByOrder(int $orderId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function findByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM order_items WHERE order_id IN ({$placeholders}) ORDER BY order_id, id ASC"
        );
        $stmt->execute($orderIds);
        return $stmt->fetchAll();
    }
}
