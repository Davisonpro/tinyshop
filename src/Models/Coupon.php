<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Coupon
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM coupons WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM coupons WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUserAndCode(int $userId, string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM coupons WHERE user_id = ? AND code = ?'
        );
        $stmt->execute([$userId, strtoupper(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO coupons (user_id, code, type, value, min_order, max_uses, is_active, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            strtoupper(trim($data['code'])),
            $data['type'] ?? 'percent',
            $data['value'],
            $data['min_order'] ?? null,
            $data['max_uses'] ?? null,
            $data['is_active'] ?? 1,
            $data['expires_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['code', 'type', 'value', 'min_order', 'max_uses', 'is_active', 'expires_at'];
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                if ($key === 'code') {
                    $value = strtoupper(trim($value));
                }
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = 'UPDATE coupons SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM coupons WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function incrementUsage(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function decrementUsage(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Validate a coupon and calculate discount.
     *
     * @return array{valid: bool, message: string, discount: float}
     */
    public function validate(array $coupon, float $orderTotal): array
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
