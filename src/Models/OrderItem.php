<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

class OrderItem extends Model
{
    protected static array $definition = [
        'table'   => 'order_items',
        'primary' => 'id',
        'fields'  => [
            'order_id'      => ['type' => FieldType::Int, 'required' => true],
            'product_id'    => ['type' => FieldType::Int, 'required' => true],
            'product_name'  => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'product_image' => ['type' => FieldType::String, 'maxLength' => 500],
            'variation'     => ['type' => FieldType::String, 'maxLength' => 255],
            'quantity'      => ['type' => FieldType::Int, 'required' => true],
            'unit_price'    => ['type' => FieldType::Decimal, 'required' => true],
            'total'         => ['type' => FieldType::Decimal, 'required' => true],
            'created_at'    => ['type' => FieldType::DateTime],
        ],
    ];

    public function createBatch(int $orderId, array $items): void
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'order_id'      => $orderId,
                'product_id'    => $item['product_id'],
                'product_name'  => $item['product_name'],
                'product_image' => $item['product_image'] ?? null,
                'variation'     => $item['variation'] ?? null,
                'quantity'      => $item['quantity'],
                'unit_price'    => $item['unit_price'],
                'total'         => $item['total'],
            ];
        }

        static::batchInsert($rows);
    }

    public function findByOrder(int $orderId): array
    {
        return static::rawQuery(
            'SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC',
            [$orderId]
        );
    }

    public function findByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        return static::rawQuery(
            "SELECT * FROM order_items WHERE order_id IN ({$placeholders}) ORDER BY order_id, id ASC",
            $orderIds
        );
    }
}
