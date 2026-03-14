<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;
use TinyShop\Enums\OrderStatus;

/**
 * Order model.
 *
 * @since 1.0.0
 */
final class Order extends Model
{
    protected static array $definition = [
        'table'   => 'orders',
        'primary' => 'id',
        'fields'  => [
            'user_id'            => ['type' => FieldType::Int, 'required' => true],
            'customer_id'        => ['type' => FieldType::Int],
            'order_number'       => ['type' => FieldType::String, 'maxLength' => 50],
            'product_id'         => ['type' => FieldType::Int],
            'customer_name'      => ['type' => FieldType::String, 'maxLength' => 255],
            'customer_phone'     => ['type' => FieldType::String, 'maxLength' => 50],
            'customer_email'     => ['type' => FieldType::String, 'maxLength' => 255],
            'shipping_address'   => ['type' => FieldType::Json],
            'amount'             => ['type' => FieldType::Decimal, 'default' => 0],
            'subtotal'           => ['type' => FieldType::Decimal, 'default' => 0],
            'status'             => ['type' => FieldType::Enum, 'values' => ['pending', 'paid', 'cancelled', 'refunded'], 'default' => 'pending'],
            'payment_method'     => ['type' => FieldType::String, 'maxLength' => 50, 'default' => 'manual'],
            'payment_gateway'    => ['type' => FieldType::String, 'maxLength' => 50],
            'payment_intent_id'  => ['type' => FieldType::String, 'maxLength' => 255],
            'notes'              => ['type' => FieldType::Text],
            'reference_id'       => ['type' => FieldType::String, 'maxLength' => 255],
            'coupon_code'        => ['type' => FieldType::String, 'maxLength' => 50],
            'discount_amount'    => ['type' => FieldType::Decimal, 'default' => 0],
            'created_at'         => ['type' => FieldType::DateTime],
            'updated_at'         => ['type' => FieldType::DateTime],
        ],
    ];

    /**
     * Get orders for a seller with item counts.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @param  int $limit  Max rows.
     * @param  int $offset Pagination offset.
     * @return array[]
     */
    public function findByUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        $db = static::db();
        $stmt = $db->prepare(
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count all orders for a seller.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @return int
     */
    public function countByUser(int $userId): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM orders WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Get order stats for a seller's dashboard.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @return array{total: int, pending: int, completed: int, revenue: float}
     */
    public function getStats(int $userId): array
    {
        $rows = static::rawQuery(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as revenue
             FROM orders WHERE user_id = ?',
            [OrderStatus::Pending->value, OrderStatus::Paid->value, OrderStatus::Paid->value, $userId]
        );
        return $rows[0] ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'revenue' => 0];
    }

    /**
     * Create a new order.
     *
     * @since 1.0.0
     *
     * @param  array $data Order data.
     * @return int   New order ID.
     */
    public function create(array $data): int
    {
        $order = new static();
        $order->fill([
            'user_id'           => $data['user_id'],
            'customer_id'       => $data['customer_id'] ?? null,
            'order_number'      => $data['order_number'] ?? null,
            'product_id'        => $data['product_id'] ?? null,
            'customer_name'     => $data['customer_name'] ?? null,
            'customer_phone'    => $data['customer_phone'] ?? null,
            'customer_email'    => $data['customer_email'] ?? null,
            'shipping_address'  => $data['shipping_address'] ?? null,
            'amount'            => $data['amount'] ?? 0,
            'subtotal'          => $data['subtotal'] ?? $data['amount'] ?? 0,
            'status'            => $data['status'] ?? OrderStatus::Pending->value,
            'payment_method'    => $data['payment_method'] ?? 'manual',
            'payment_gateway'   => $data['payment_gateway'] ?? null,
            'payment_intent_id' => $data['payment_intent_id'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'reference_id'      => $data['reference_id'] ?? null,
            'coupon_code'       => $data['coupon_code'] ?? null,
            'discount_amount'   => $data['discount_amount'] ?? 0,
        ]);
        $order->save();
        return (int) $order->getId();
    }

    /**
     * Find an order by its order number.
     *
     * @since 1.0.0
     *
     * @param  string $orderNumber Order number (e.g. "TS-A1B2C3D4").
     * @return array|null
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM orders WHERE order_number = ?',
            [$orderNumber]
        );
        return $rows[0] ?? null;
    }

    /**
     * Find an order by payment intent ID.
     *
     * @since 1.0.0
     *
     * @param  string $intentId Payment gateway intent ID.
     * @return array|null
     */
    public function findByPaymentIntent(string $intentId): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM orders WHERE payment_intent_id = ?',
            [$intentId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Generate a unique order number.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        return 'TS-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Update an order by ID.
     *
     * @since 1.0.0
     *
     * @param  int   $id   Order ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $order = static::find($id);
        if (!$order) {
            return false;
        }

        $allowed = ['status', 'payment_intent_id', 'payment_gateway', 'notes', 'customer_email', 'customer_phone', 'customer_id'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $order->{$field} = $data[$field];
            }
        }

        return $order->save();
    }

    /**
     * Get orders for a customer account.
     *
     * @since 1.0.0
     *
     * @param  int $customerId Customer ID.
     * @param  int $limit      Max rows.
     * @param  int $offset     Pagination offset.
     * @return array[]
     */
    public function findByCustomer(int $customerId, int $limit = 50, int $offset = 0): array
    {
        $db = static::db();
        $stmt = $db->prepare(
            'SELECT o.*, COALESCE(COUNT(oi.id), 0) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.customer_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Link unlinked orders to a customer account by email.
     *
     * @since 1.0.0
     *
     * @param  int    $shopId     Shop (seller) ID.
     * @param  string $email      Customer email.
     * @param  int    $customerId Customer ID to link.
     * @return int    Orders linked.
     */
    public function linkToCustomer(int $shopId, string $email, int $customerId): int
    {
        return static::rawExecute(
            'UPDATE orders SET customer_id = ? WHERE user_id = ? AND customer_email = ? AND customer_id IS NULL',
            [$customerId, $shopId, $email]
        );
    }

    /**
     * Set an order's status.
     *
     * @since 1.0.0
     *
     * @param  int    $id     Order ID.
     * @param  string $status New status.
     * @return bool
     */
    public function updateStatus(int $id, string $status): bool
    {
        return static::rawExecute(
            'UPDATE orders SET status = ? WHERE id = ?',
            [$status, $id]
        ) > 0;
    }

    /**
     * Atomically mark an order as paid (only if pending).
     *
     * @since 1.0.0
     *
     * @param  int     $id               Order ID.
     * @param  ?string $paymentIntentId   Payment intent to store.
     * @return bool    False if already processed.
     */
    public function markPaid(int $id, ?string $paymentIntentId = null): bool
    {
        $sql = 'UPDATE orders SET status = "paid"';
        $params = [];

        if ($paymentIntentId !== null) {
            $sql .= ', payment_intent_id = ?';
            $params[] = $paymentIntentId;
        }

        $sql .= ' WHERE id = ? AND status = "pending"';
        $params[] = $id;

        $db = static::db();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Daily paid-order totals for a seller, zero-filled.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @param  int $days   Number of days (1-90).
     * @return list<array{day: string, label: string, orders: int, revenue: float}>
     */
    public function getDailySales(int $userId, int $days = 14): array
    {
        $days = max(1, min($days, 90));
        $rows = static::rawQuery(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(amount), 0) AS revenue
             FROM orders
             WHERE user_id = ? AND status = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$userId, OrderStatus::Paid->value, $days]
        );

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

    /**
     * Daily paid-order totals for a seller within a date range, zero-filled.
     *
     * @since 1.0.0
     *
     * @param  int    $userId    Seller ID.
     * @param  string $startDate Start date (Y-m-d).
     * @param  string $endDate   End date (Y-m-d).
     * @return list<array{day: string, label: string, orders: int, revenue: float}>
     */
    public function getDailySalesRange(int $userId, string $startDate, string $endDate): array
    {
        $rows = static::rawQuery(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(amount), 0) AS revenue
             FROM orders
             WHERE user_id = ? AND status = ? AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$userId, OrderStatus::Paid->value, $startDate, $endDate]
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r['day']] = ['orders' => (int) $r['orders'], 'revenue' => (float) $r['revenue']];
        }

        $result = [];
        $current = new \DateTimeImmutable($startDate);
        $end = new \DateTimeImmutable($endDate);
        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $result[] = [
                'day'     => $date,
                'label'   => $current->format('M j'),
                'orders'  => $map[$date]['orders'] ?? 0,
                'revenue' => $map[$date]['revenue'] ?? 0,
            ];
            $current = $current->modify('+1 day');
        }

        return $result;
    }

    // ── Admin queries ──

    /**
     * Count all orders platform-wide.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int) static::rawScalar('SELECT COUNT(*) FROM orders');
    }

    /**
     * Get platform-wide order stats (admin).
     *
     * @since 1.0.0
     *
     * @return array{total: int, pending: int, completed: int, total_volume: float, paid_volume: float}
     */
    public function getPlatformStats(): array
    {
        $rows = static::rawQuery(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                COALESCE(SUM(amount), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid_volume
             FROM orders',
            [OrderStatus::Pending->value, OrderStatus::Paid->value, OrderStatus::Paid->value]
        );
        return $rows[0] ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'total_volume' => 0, 'paid_volume' => 0];
    }

    /**
     * Get orders for admin listing with seller info.
     *
     * @since 1.0.0
     *
     * @param  int    $limit  Max rows.
     * @param  int    $offset Pagination offset.
     * @param  string $status Optional status filter.
     * @return array[]
     */
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

        $db = static::db();
        $stmt = $db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count orders for admin listing.
     *
     * @since 1.0.0
     *
     * @param  string $status Optional status filter.
     * @return int
     */
    public function countAllAdmin(string $status = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM orders';
        $params = [];

        if ($status !== '') {
            $sql .= ' WHERE status = ?';
            $params[] = $status;
        }

        return (int) static::rawScalar($sql, $params);
    }

    /**
     * Platform-wide daily paid-order totals, zero-filled.
     *
     * @since 1.0.0
     *
     * @param  int $days Number of days (1-90).
     * @return list<array{day: string, label: string, orders: int, revenue: float}>
     */
    public function getPlatformDailySales(int $days = 14): array
    {
        $days = max(1, min($days, 90));
        $rows = static::rawQuery(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(amount), 0) AS revenue
             FROM orders
             WHERE status = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [OrderStatus::Paid->value, $days]
        );

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

    /**
     * Count recent orders platform-wide.
     *
     * @since 1.0.0
     *
     * @param  int $days Lookback period.
     * @return int
     */
    public function recentOrders(int $days = 7): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$days]
        );
    }

    /**
     * Get unique customers for a seller, aggregated from orders.
     *
     * @since 1.0.0
     *
     * @param  int    $userId Seller ID.
     * @param  int    $limit  Max rows.
     * @param  int    $offset Pagination offset.
     * @param  string $search Optional search term.
     * @return array[]
     */
    public function getUniqueCustomers(int $userId, int $limit = 50, int $offset = 0, string $search = ''): array
    {
        $where = 'WHERE user_id = ? AND customer_email IS NOT NULL AND customer_email != ?';
        $params = [$userId, ''];

        if ($search !== '') {
            $where .= ' AND (customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT
                    customer_email,
                    MAX(customer_name) AS customer_name,
                    MAX(customer_phone) AS customer_phone,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) AS total_spent,
                    MAX(created_at) AS last_order_at
                FROM orders
                {$where}
                GROUP BY customer_email
                ORDER BY last_order_at DESC
                LIMIT ? OFFSET ?";

        $params = array_merge([OrderStatus::Paid->value], $params);
        $params[] = $limit;
        $params[] = $offset;

        return static::rawQuery($sql, $params);
    }

    /**
     * Count unique customers for a seller.
     *
     * @since 1.0.0
     *
     * @param  int    $userId Seller ID.
     * @param  string $search Optional search term.
     * @return int
     */
    public function countUniqueCustomers(int $userId, string $search = ''): int
    {
        $where = 'WHERE user_id = ? AND customer_email IS NOT NULL AND customer_email != ?';
        $params = [$userId, ''];

        if ($search !== '') {
            $where .= ' AND (customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return (int) static::rawScalar(
            "SELECT COUNT(DISTINCT customer_email) FROM orders {$where}",
            $params
        );
    }

    /**
     * Count stale pending orders (likely abandoned).
     *
     * @since 1.0.0
     *
     * @param  int $userId   Seller ID.
     * @param  int $hoursOld Minimum age in hours.
     * @return int
     */
    public function countStalePending(int $userId, int $hoursOld = 1): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM orders
             WHERE user_id = ? AND status = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)',
            [$userId, OrderStatus::Pending->value, $hoursOld]
        );
    }
}
