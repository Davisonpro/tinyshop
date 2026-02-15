<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\OrderStatus;
use TinyShop\Services\DB;
use PDO;

final class Order
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, COALESCE(COUNT(oi.id), 0) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getStats(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as revenue
             FROM orders WHERE user_id = ?'
        );
        $stmt->execute([OrderStatus::Pending->value, OrderStatus::Paid->value, OrderStatus::Paid->value, $userId]);
        return $stmt->fetch() ?: ['total' => 0, 'pending' => 0, 'completed' => 0, 'revenue' => 0];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orders (user_id, order_number, product_id, customer_name, customer_phone, customer_email, shipping_address, amount, subtotal, status, payment_method, payment_gateway, payment_intent_id, notes, reference_id, coupon_code, discount_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['order_number'] ?? null,
            $data['product_id'] ?? null,
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['customer_email'] ?? null,
            isset($data['shipping_address']) ? json_encode($data['shipping_address']) : null,
            $data['amount'] ?? 0,
            $data['subtotal'] ?? $data['amount'] ?? 0,
            $data['status'] ?? OrderStatus::Pending->value,
            $data['payment_method'] ?? 'manual',
            $data['payment_gateway'] ?? null,
            $data['payment_intent_id'] ?? null,
            $data['notes'] ?? null,
            $data['reference_id'] ?? null,
            $data['coupon_code'] ?? null,
            $data['discount_amount'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_number = ?');
        $stmt->execute([$orderNumber]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPaymentIntent(string $intentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE payment_intent_id = ?');
        $stmt->execute([$intentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function generateOrderNumber(): string
    {
        return 'TS-' . strtoupper(bin2hex(random_bytes(4)));
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['status', 'payment_intent_id', 'payment_gateway', 'notes', 'customer_email', 'customer_phone'];
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orders WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Daily sales totals for a seller over N days.
     */
    public function getDailySales(int $userId, int $days = 14): array
    {
        $days = max(1, min($days, 90));
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(amount), 0) AS revenue
             FROM orders
             WHERE user_id = ? AND status = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC"
        );
        $stmt->execute([$userId, OrderStatus::Paid->value, $days]);
        $rows = $stmt->fetchAll();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['day']] = ['orders' => (int) $r['orders'], 'revenue' => (float) $r['revenue']];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'day'     => $date,
                'label'   => date('M j', strtotime($date)),
                'orders'  => $map[$date]['orders'] ?? 0,
                'revenue' => $map[$date]['revenue'] ?? 0,
            ];
        }

        return $result;
    }

    // ── Admin queries ──

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM orders');
        return (int) $stmt->fetchColumn();
    }

    public function getPlatformStats(): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                COALESCE(SUM(amount), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid_volume
             FROM orders'
        );
        $stmt->execute([OrderStatus::Pending->value, OrderStatus::Paid->value, OrderStatus::Paid->value]);
        return $stmt->fetch() ?: ['total' => 0, 'pending' => 0, 'completed' => 0, 'total_volume' => 0, 'paid_volume' => 0];
    }

    public function findAllAdmin(int $limit = 50, int $offset = 0, string $status = ''): array
    {
        $sql = 'SELECT o.*, u.name AS seller_name, u.store_name, u.subdomain
                FROM orders o
                JOIN users u ON u.id = o.user_id';
        $params = [];

        if ($status !== '') {
            $sql .= ' WHERE o.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?';

        $stmt = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAllAdmin(string $status = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM orders';
        $params = [];

        if ($status !== '') {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function recentOrders(int $days = 7): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([$days]);
        return (int) $stmt->fetchColumn();
    }
}
