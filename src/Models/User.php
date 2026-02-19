<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Enums\OrderStatus;
use TinyShop\Enums\UserRole;
use TinyShop\Services\DB;
use PDO;

final class User
{
    private readonly PDO $db;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    // ── Finders ──

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByOAuth(string $provider, string $oauthId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ?');
        $stmt->execute([$provider, $oauthId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySubdomain(string $subdomain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE subdomain = ? AND is_active = 1');
        $stmt->execute([$subdomain]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByCustomDomain(string $domain): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE custom_domain = ? AND is_active = 1');
        $stmt->execute([$domain]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function customDomainExists(string $domain, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE custom_domain = ? AND id != ?');
            $stmt->execute([$domain, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE custom_domain = ?');
            $stmt->execute([$domain]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Admin queries ──

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE is_active = 1 AND role = ?');
        $stmt->execute([UserRole::Seller->value]);
        return (int) $stmt->fetchColumn();
    }

    public function findSellers(int $limit = 50, int $offset = 0, string $search = ''): array
    {
        $sql = 'SELECT id, name, email, store_name, subdomain, custom_domain, is_active, created_at, updated_at, last_login_at, login_count, currency
                FROM users WHERE role = ?';
        $params = [UserRole::Seller->value];

        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ? OR store_name LIKE ? OR subdomain LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';

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

    public function countSellers(string $search = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role = ?';
        $params = [UserRole::Seller->value];

        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ? OR store_name LIKE ? OR subdomain LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getSellerWithStats(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? AND role = ?');
        $stmt->execute([$id, UserRole::Seller->value]);
        $seller = $stmt->fetch();
        if (!$seller) return null;

        // Product count
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE user_id = ?');
        $stmt->execute([$id]);
        $seller['product_count'] = (int) $stmt->fetchColumn();

        // Order stats
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as order_count,
                    COALESCE(SUM(amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid_revenue
             FROM orders WHERE user_id = ?'
        );
        $stmt->execute([OrderStatus::Paid->value, $id]);
        $stats = $stmt->fetch();
        $seller['order_count'] = (int) $stats['order_count'];
        $seller['total_revenue'] = (float) $stats['total_revenue'];
        $seller['paid_revenue'] = (float) $stats['paid_revenue'];

        // View count
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM shop_views WHERE user_id = ?');
        $stmt->execute([$id]);
        $seller['view_count'] = (int) $stmt->fetchColumn();

        return $seller;
    }

    public function recentSignups(int $days = 7): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE role = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmt->execute([UserRole::Seller->value, $days]);
        return (int) $stmt->fetchColumn();
    }

    // ── Mutations ──

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, oauth_provider, oauth_id, role, store_name, subdomain) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'] ?? $data['store_name'] ?? '',
            $data['email'],
            $data['password_hash'] ?? null,
            $data['oauth_provider'] ?? null,
            $data['oauth_id'] ?? null,
            $data['role'] ?? UserRole::Seller->value,
            $data['store_name'] ?? null,
            $data['subdomain'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        $allowed = [
            'name', 'email', 'store_name', 'subdomain', 'custom_domain',
            'shop_logo', 'shop_favicon', 'shop_tagline', 'contact_whatsapp', 'contact_email',
            'contact_phone', 'map_link', 'currency', 'is_active',
            'social_instagram', 'social_tiktok', 'social_facebook',
            'shop_theme', 'color_palette', 'logo_alignment', 'product_image_fit',
            'stripe_public_key', 'stripe_secret_key', 'stripe_mode', 'stripe_enabled',
            'paypal_client_id', 'paypal_secret', 'paypal_mode', 'paypal_enabled',
            'cod_enabled',
            'mpesa_shortcode', 'mpesa_consumer_key', 'mpesa_consumer_secret',
            'mpesa_passkey', 'mpesa_mode', 'mpesa_enabled',
            'plan_id', 'plan_expires_at',
            'payment_mode', 'is_showcased',
            'show_store_name', 'show_tagline', 'show_search',
            'show_categories', 'show_sort_toolbar', 'show_desktop_footer',
            'announcement_text',
            'google_verification', 'bing_verification',
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
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function recordLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET last_login_at = NOW(), login_count = login_count + 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public function isActive(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT is_active FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() === 1;
    }

    public function toggleActive(int $id, bool $active): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    public function subdomainExists(string $subdomain, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE subdomain = ? AND id != ?');
            $stmt->execute([$subdomain, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE subdomain = ?');
            $stmt->execute([$subdomain]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getPasswordHash(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();
        return $hash ?: null;
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$hash, $id]);
    }

    public function updateOAuth(int $id, string $provider, string $oauthId): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET oauth_provider = ?, oauth_id = ? WHERE id = ?');
        return $stmt->execute([$provider, $oauthId, $id]);
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET email = ? WHERE id = ?');
        return $stmt->execute([$email, $id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Permanently delete a user account and all associated data.
     * Returns an array of uploaded file URLs for cleanup, or false on failure.
     * @return string[]|false
     */
    public function findShowcased(int $limit = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, store_name, subdomain, shop_logo, shop_tagline
             FROM users
             WHERE is_showcased = 1 AND is_active = 1 AND subdomain IS NOT NULL
             ORDER BY RAND()
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function deleteAccount(int $id): array|false
    {
        // Collect file URLs before deleting rows
        $urls = [];

        $stmt = $this->db->prepare(
            'SELECT pi.image_url FROM product_images pi
             INNER JOIN products p ON pi.product_id = p.id
             WHERE p.user_id = ? AND pi.image_url IS NOT NULL'
        );
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) $urls[] = $url;
        }

        $stmt = $this->db->prepare('SELECT image_url FROM products WHERE user_id = ? AND image_url IS NOT NULL');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) $urls[] = $url;
        }

        $stmt = $this->db->prepare('SELECT image_url FROM categories WHERE user_id = ? AND image_url IS NOT NULL');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) $urls[] = $url;
        }

        $stmt = $this->db->prepare('SELECT image_url FROM hero_slides WHERE user_id = ? AND image_url IS NOT NULL');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) $urls[] = $url;
        }

        $stmt = $this->db->prepare('SELECT shop_logo FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $logo = $stmt->fetchColumn();
        if ($logo) $urls[] = $logo;

        $this->db->beginTransaction();
        try {
            // Delete product images
            $this->db->prepare(
                'DELETE pi FROM product_images pi
                 INNER JOIN products p ON pi.product_id = p.id
                 WHERE p.user_id = ?'
            )->execute([$id]);

            // Delete products
            $this->db->prepare('DELETE FROM products WHERE user_id = ?')->execute([$id]);

            // Delete categories
            $this->db->prepare('DELETE FROM categories WHERE user_id = ?')->execute([$id]);

            // Delete shop views
            $this->db->prepare('DELETE FROM shop_views WHERE user_id = ?')->execute([$id]);

            // Delete orders
            $this->db->prepare(
                'DELETE oi FROM order_items oi
                 INNER JOIN orders o ON oi.order_id = o.id
                 WHERE o.user_id = ?'
            )->execute([$id]);
            $this->db->prepare('DELETE FROM orders WHERE user_id = ?')->execute([$id]);

            // Delete billing pending records
            $this->db->prepare('DELETE FROM billing_mpesa_pending WHERE user_id = ?')->execute([$id]);

            // Delete hero slides
            $this->db->prepare('DELETE FROM hero_slides WHERE user_id = ?')->execute([$id]);

            // Delete coupons
            $this->db->prepare('DELETE FROM coupons WHERE user_id = ?')->execute([$id]);

            // Delete subscriptions
            $this->db->prepare('DELETE FROM subscriptions WHERE user_id = ?')->execute([$id]);

            // Delete the user
            $this->db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

            $this->db->commit();
            return $urls;
        } catch (\Throwable) {
            $this->db->rollBack();
            return false;
        }
    }
}
