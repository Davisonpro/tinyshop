<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;

class Subscription extends Model
{
    protected static array $definition = [
        'table'   => 'subscriptions',
        'primary' => 'id',
        'fields'  => [
            'user_id'           => ['type' => FieldType::Int, 'required' => true],
            'plan_id'           => ['type' => FieldType::Int, 'required' => true],
            'billing_cycle'     => ['type' => FieldType::Enum, 'values' => ['monthly', 'yearly'], 'required' => true],
            'status'            => ['type' => FieldType::Enum, 'values' => ['active', 'expired', 'cancelled'], 'default' => 'active'],
            'starts_at'         => ['type' => FieldType::DateTime, 'required' => true],
            'expires_at'        => ['type' => FieldType::DateTime, 'required' => true],
            'payment_gateway'   => ['type' => FieldType::String, 'maxLength' => 50],
            'payment_reference' => ['type' => FieldType::String, 'maxLength' => 255],
            'amount_paid'       => ['type' => FieldType::Decimal, 'default' => 0],
            'created_at'        => ['type' => FieldType::DateTime],
            'updated_at'        => ['type' => FieldType::DateTime],
        ],
    ];

    public function findActiveByUser(int $userId): ?array
    {
        $rows = static::rawQuery(
            'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = ? AND s.status = "active"
             ORDER BY s.created_at DESC LIMIT 1',
            [$userId]
        );
        return $rows[0] ?? null;
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        $db = static::db();
        $stmt = $db->prepare(
            'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sub = new static();
        $sub->fill([
            'user_id'           => $data['user_id'],
            'plan_id'           => $data['plan_id'],
            'billing_cycle'     => $data['billing_cycle'],
            'status'            => $data['status'] ?? 'active',
            'starts_at'         => $data['starts_at'],
            'expires_at'        => $data['expires_at'],
            'payment_gateway'   => $data['payment_gateway'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'amount_paid'       => $data['amount_paid'] ?? 0,
        ]);
        $sub->save();
        return (int) $sub->getId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        return static::rawExecute(
            'UPDATE subscriptions SET status = ? WHERE id = ?',
            [$status, $id]
        ) > 0;
    }

    public function expireOverdue(): int
    {
        // Mark expired subscriptions
        $count = static::rawExecute(
            'UPDATE subscriptions SET status = "expired"
             WHERE status = "active" AND expires_at < NOW()'
        );

        // Clear plan_id/plan_expires_at for users whose subscriptions just expired
        static::rawExecute(
            'UPDATE users u
             SET u.plan_id = NULL, u.plan_expires_at = NULL
             WHERE u.plan_expires_at IS NOT NULL AND u.plan_expires_at < NOW()
               AND NOT EXISTS (
                   SELECT 1 FROM subscriptions s
                   WHERE s.user_id = u.id AND s.status = "active" AND s.expires_at > NOW()
               )'
        );

        return $count;
    }
}
