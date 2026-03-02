<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\FieldType;

/**
 * Discount coupon model.
 *
 * @since 1.0.0
 */
final class Coupon extends Model
{
    protected static array $definition = [
        'table'   => 'coupons',
        'primary' => 'id',
        'fields'  => [
            'user_id'    => ['type' => FieldType::Int, 'required' => true],
            'code'       => ['type' => FieldType::String, 'required' => true, 'maxLength' => 50],
            'type'       => ['type' => FieldType::Enum, 'values' => ['percent', 'fixed'], 'default' => 'percent'],
            'value'      => ['type' => FieldType::Decimal, 'required' => true],
            'min_order'  => ['type' => FieldType::Decimal],
            'max_uses'   => ['type' => FieldType::Int],
            'used_count' => ['type' => FieldType::Int, 'default' => 0],
            'is_active'  => ['type' => FieldType::Bool, 'default' => 1],
            'expires_at' => ['type' => FieldType::DateTime],
            'created_at' => ['type' => FieldType::DateTime],
            'updated_at' => ['type' => FieldType::DateTime],
        ],
    ];

    /**
     * Get all coupons for a seller, newest first.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @return array[]
     */
    public function findByUser(int $userId): array
    {
        return static::rawQuery(
            'SELECT * FROM coupons WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    /**
     * Find a coupon by code in a seller's shop.
     *
     * @since 1.0.0
     *
     * @param  int    $userId Seller ID.
     * @param  string $code   Coupon code.
     * @return array|null
     */
    public function findByUserAndCode(int $userId, string $code): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM coupons WHERE user_id = ? AND code = ?',
            [$userId, strtoupper(trim($code))]
        );
        return $rows[0] ?? null;
    }

    /**
     * Create a new coupon.
     *
     * @since 1.0.0
     *
     * @param  array $data Coupon data.
     * @return int   New coupon ID.
     */
    public function create(array $data): int
    {
        $coupon = new static();
        $coupon->fill([
            'user_id'    => $data['user_id'],
            'code'       => strtoupper(trim($data['code'])),
            'type'       => $data['type'] ?? 'percent',
            'value'      => $data['value'],
            'min_order'  => $data['min_order'] ?? null,
            'max_uses'   => $data['max_uses'] ?? null,
            'is_active'  => $data['is_active'] ?? 1,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        $coupon->save();
        return (int) $coupon->getId();
    }

    /**
     * Update a coupon by ID.
     *
     * @since 1.0.0
     *
     * @param  int   $id   Coupon ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $coupon = static::find($id);
        if (!$coupon) {
            return false;
        }

        $allowed = ['code', 'type', 'value', 'min_order', 'max_uses', 'is_active', 'expires_at'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'code') {
                    $value = strtoupper(trim($value));
                }
                $coupon->{$field} = $value;
            }
        }

        return $coupon->save();
    }

    /** Increment used_count after a successful order. */
    public function incrementUsage(int $id): void
    {
        static::increment($id, 'used_count');
    }

    /** Decrement used_count (e.g. on cancellation). */
    public function decrementUsage(int $id): void
    {
        static::decrement($id, 'used_count');
    }

    /**
     * Validate a coupon and calculate the discount.
     *
     * @since 1.0.0
     *
     * @param  array $coupon     Coupon row.
     * @param  float $orderTotal Cart subtotal.
     * @return array{valid: bool, message: string, discount: float}
     */
    public function validateCoupon(array $coupon, float $orderTotal): array
    {
        if (!(int) $coupon['is_active']) {
            return ['valid' => false, 'message' => 'This coupon is no longer active', 'discount' => 0];
        }

        if ($coupon['expires_at'] !== null && strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This coupon has expired', 'discount' => 0];
        }

        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit', 'discount' => 0];
        }

        if ($coupon['min_order'] !== null && $orderTotal < (float) $coupon['min_order']) {
            $min = number_format((float) $coupon['min_order'], 2);
            return ['valid' => false, 'message' => "Minimum order of $min required", 'discount' => 0];
        }

        $discount = 0;
        if ($coupon['type'] === 'percent') {
            $discount = round($orderTotal * (float) $coupon['value'] / 100, 2);
        } else {
            $discount = min((float) $coupon['value'], $orderTotal);
        }

        $discount = round($discount, 2);

        return ['valid' => true, 'message' => 'Coupon applied!', 'discount' => $discount];
    }
}
