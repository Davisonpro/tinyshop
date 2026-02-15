<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Subscription
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findActiveByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, p.name AS plan_name, p.slug AS plan_slug
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = ? AND s.status = "active"
             ORDER BY s.created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
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
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO subscriptions (user_id, plan_id, billing_cycle, status, starts_at, expires_at,
                payment_gateway, payment_reference, amount_paid)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['plan_id'],
            $data['billing_cycle'],
            $data['status'] ?? 'active',
            $data['starts_at'],
            $data['expires_at'],
            $data['payment_gateway'] ?? null,
            $data['payment_reference'] ?? null,
            $data['amount_paid'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE subscriptions SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function expireOverdue(): int
    {
        // Mark expired subscriptions
        $stmt = $this->db->prepare(
            'UPDATE subscriptions SET status = "expired"
             WHERE status = "active" AND expires_at < NOW()'
        );
        $stmt->execute();
        $count = $stmt->rowCount();

        // Clear plan_id/plan_expires_at for users whose subscriptions just expired
        $this->db->exec(
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
