<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;

final class Plan
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findAllAdmin(): array
    {
        $stmt = $this->db->query('SELECT * FROM plans ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findDefault(): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE is_default = 1 LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        // If this plan is being set as default, unset any existing default
        if (!empty($data['is_default'])) {
            $this->db->exec('UPDATE plans SET is_default = 0');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO plans (name, slug, description, price_monthly, price_yearly, currency,
                max_products, allowed_themes, custom_domain_allowed, coupons_allowed,
                features, cta_text, badge_text, is_featured,
                is_default, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['price_monthly'] ?? 0,
            $data['price_yearly'] ?? 0,
            $data['currency'] ?? 'KES',
            $data['max_products'] ?? null,
            $data['allowed_themes'] ?? null,
            $data['custom_domain_allowed'] ?? 0,
            $data['coupons_allowed'] ?? 0,
            $data['features'] ?? null,
            $data['cta_text'] ?? null,
            $data['badge_text'] ?? null,
            $data['is_featured'] ?? 0,
            $data['is_default'] ?? 0,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // If this plan is being set as default, unset any existing default
        if (!empty($data['is_default'])) {
            $this->db->prepare('UPDATE plans SET is_default = 0 WHERE id != ?')->execute([$id]);
        }

        $fields = [];
        $values = [];
        $allowed = [
            'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'currency',
            'max_products', 'allowed_themes', 'custom_domain_allowed', 'coupons_allowed',
            'features', 'cta_text', 'badge_text', 'is_featured',
            'is_default', 'is_active', 'sort_order',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = 'UPDATE plans SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        // Only allow deletion if no active subscribers
        if ($this->countSubscribers($id) > 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM plans WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function countSubscribers(int $planId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE plan_id = ? AND plan_expires_at > NOW()'
        );
        $stmt->execute([$planId]);
        return (int) $stmt->fetchColumn();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM plans WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM plans WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }
}
