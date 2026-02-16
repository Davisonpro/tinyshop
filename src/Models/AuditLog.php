<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class AuditLog
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Record an audit log entry.
     * Wrapped in try/catch so failures never break the main flow.
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $details = null
    ): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            ]);
        } catch (\Throwable) {
            // Audit logging must never break the main application flow
        }
    }

    /**
     * Get recent audit log entries with user name joined.
     */
    public function getRecent(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT al.*, u.name AS user_name, u.email AS user_email
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit log entries for a specific user.
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT al.*, u.name AS user_name, u.email AS user_email
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.user_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete audit log entries older than N days.
     * Returns the number of rows deleted.
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->bindValue(1, $daysToKeep, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
