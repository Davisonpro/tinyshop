<?php

declare(strict_types=1);

namespace TinyShop\Models;

use PDO;
use TinyShop\Enums\FieldType;
use TinyShop\Enums\OrderStatus;
use TinyShop\Enums\UserRole;

/**
 * User model.
 *
 * @since 1.0.0
 */
final class User extends Model
{
    protected static array $definition = [
        'table'   => 'users',
        'primary' => 'id',
        'fields'  => [
            'name'                   => ['type' => FieldType::String, 'maxLength' => 255],
            'email'                  => ['type' => FieldType::String, 'required' => true, 'maxLength' => 255],
            'password_hash'          => ['type' => FieldType::String, 'maxLength' => 255],
            'oauth_provider'         => ['type' => FieldType::String, 'maxLength' => 50],
            'oauth_id'               => ['type' => FieldType::String, 'maxLength' => 255],
            'role'                   => ['type' => FieldType::Enum, 'values' => ['admin', 'seller'], 'default' => 'seller'],
            'store_name'             => ['type' => FieldType::String, 'maxLength' => 255],
            'subdomain'              => ['type' => FieldType::String, 'maxLength' => 100],
            'custom_domain'          => ['type' => FieldType::String, 'maxLength' => 255],
            'shop_logo'              => ['type' => FieldType::String, 'maxLength' => 500],
            'shop_favicon'           => ['type' => FieldType::String, 'maxLength' => 500],
            'shop_tagline'           => ['type' => FieldType::String, 'maxLength' => 255],
            'contact_whatsapp'       => ['type' => FieldType::String, 'maxLength' => 50],
            'contact_email'          => ['type' => FieldType::String, 'maxLength' => 255],
            'contact_phone'          => ['type' => FieldType::String, 'maxLength' => 50],
            'map_link'               => ['type' => FieldType::String, 'maxLength' => 500],
            'currency'               => ['type' => FieldType::String, 'maxLength' => 10],
            'is_active'              => ['type' => FieldType::Bool, 'default' => 1],
            'social_instagram'       => ['type' => FieldType::String, 'maxLength' => 255],
            'social_tiktok'          => ['type' => FieldType::String, 'maxLength' => 255],
            'social_facebook'        => ['type' => FieldType::String, 'maxLength' => 255],
            'shop_theme'             => ['type' => FieldType::String, 'maxLength' => 50],
            'color_palette'          => ['type' => FieldType::String, 'maxLength' => 50],
            'logo_alignment'         => ['type' => FieldType::String, 'maxLength' => 20],
            'product_image_fit'      => ['type' => FieldType::String, 'maxLength' => 20],
            'stripe_public_key'      => ['type' => FieldType::String, 'maxLength' => 255],
            'stripe_secret_key'      => ['type' => FieldType::String, 'maxLength' => 255],
            'stripe_mode'            => ['type' => FieldType::String, 'maxLength' => 10],
            'stripe_enabled'         => ['type' => FieldType::Bool, 'default' => 0],
            'paypal_client_id'       => ['type' => FieldType::String, 'maxLength' => 255],
            'paypal_secret'          => ['type' => FieldType::String, 'maxLength' => 255],
            'paypal_mode'            => ['type' => FieldType::String, 'maxLength' => 10],
            'paypal_enabled'         => ['type' => FieldType::Bool, 'default' => 0],
            'cod_enabled'            => ['type' => FieldType::Bool, 'default' => 0],
            'mpesa_shortcode'        => ['type' => FieldType::String, 'maxLength' => 50],
            'mpesa_consumer_key'     => ['type' => FieldType::String, 'maxLength' => 255],
            'mpesa_consumer_secret'  => ['type' => FieldType::String, 'maxLength' => 255],
            'mpesa_passkey'          => ['type' => FieldType::String, 'maxLength' => 255],
            'mpesa_mode'             => ['type' => FieldType::String, 'maxLength' => 10],
            'mpesa_enabled'          => ['type' => FieldType::Bool, 'default' => 0],
            'pesapal_consumer_key'   => ['type' => FieldType::String, 'maxLength' => 255],
            'pesapal_consumer_secret' => ['type' => FieldType::String, 'maxLength' => 255],
            'pesapal_mode'           => ['type' => FieldType::String, 'maxLength' => 10],
            'pesapal_enabled'        => ['type' => FieldType::Bool, 'default' => 0],
            'plan_id'                => ['type' => FieldType::Int],
            'plan_expires_at'        => ['type' => FieldType::DateTime],
            'payment_mode'           => ['type' => FieldType::String, 'maxLength' => 20],
            'is_showcased'           => ['type' => FieldType::Bool, 'default' => 0],
            'show_logo'              => ['type' => FieldType::Bool, 'default' => 1],
            'show_store_name'        => ['type' => FieldType::Bool, 'default' => 1],
            'show_tagline'           => ['type' => FieldType::Bool, 'default' => 1],
            'show_search'            => ['type' => FieldType::Bool, 'default' => 1],
            'show_categories'        => ['type' => FieldType::Bool, 'default' => 1],
            'show_sort_toolbar'      => ['type' => FieldType::Bool, 'default' => 1],
            'show_desktop_footer'    => ['type' => FieldType::Bool, 'default' => 1],
            'announcement_text'      => ['type' => FieldType::String, 'maxLength' => 500],
            'google_verification'    => ['type' => FieldType::String, 'maxLength' => 255],
            'bing_verification'      => ['type' => FieldType::String, 'maxLength' => 255],
            'last_login_at'          => ['type' => FieldType::DateTime],
            'login_count'            => ['type' => FieldType::Int, 'default' => 0],
            'created_at'             => ['type' => FieldType::DateTime],
            'updated_at'             => ['type' => FieldType::DateTime],
        ],
    ];

    // ── Finders ──

    /**
     * Find a user by OAuth provider and ID.
     *
     * @since 1.0.0
     *
     * @param  string $provider OAuth provider name.
     * @param  string $oauthId  Provider user ID.
     * @return array|null
     */
    public function findByOAuth(string $provider, string $oauthId): ?array
    {
        $result = static::findWhere(['oauth_provider' => $provider, 'oauth_id' => $oauthId]);
        return $result?->toArray();
    }

    /**
     * Find an active seller by subdomain.
     *
     * @since 1.0.0
     *
     * @param  string $subdomain Shop subdomain.
     * @return array|null
     */
    public function findBySubdomain(string $subdomain): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM users WHERE subdomain = ? AND is_active = 1',
            [$subdomain]
        );
        return $rows[0] ?? null;
    }

    /**
     * Find an active seller by custom domain.
     *
     * @since 1.0.0
     *
     * @param  string $domain Custom domain.
     * @return array|null
     */
    public function findByCustomDomain(string $domain): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM users WHERE custom_domain = ? AND is_active = 1',
            [$domain]
        );
        return $rows[0] ?? null;
    }

    // ── Admin queries ──

    /** Count users by role. */
    public function countByRole(string $role): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM users WHERE role = ?',
            [$role]
        );
    }

    /** Count active sellers. */
    public function countActive(): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM users WHERE is_active = 1 AND role = ?',
            [UserRole::Seller->value]
        );
    }

    /**
     * Paginated seller list for admin.
     *
     * @since 1.0.0
     *
     * @param  int    $limit  Max rows.
     * @param  int    $offset Starting offset.
     * @param  string $search Optional search query.
     * @return array[]
     */
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

    /** Count sellers, optionally filtered by search. */
    public function countSellers(string $search = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE role = ?';
        $params = [UserRole::Seller->value];

        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR email LIKE ? OR store_name LIKE ? OR subdomain LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        return (int) static::rawScalar($sql, $params);
    }

    /**
     * Get a seller with aggregated stats.
     *
     * @since 1.0.0
     *
     * @param  int $id Seller ID.
     * @return array|null
     */
    public function getSellerWithStats(int $id): ?array
    {
        $rows = static::rawQuery(
            'SELECT * FROM users WHERE id = ? AND role = ?',
            [$id, UserRole::Seller->value]
        );
        $seller = $rows[0] ?? null;
        if (!$seller) {
            return null;
        }

        $seller['product_count'] = (int) static::rawScalar(
            'SELECT COUNT(*) FROM products WHERE user_id = ?',
            [$id]
        );

        $stats = static::rawQuery(
            'SELECT COUNT(*) as order_count,
                    COALESCE(SUM(amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid_revenue
             FROM orders WHERE user_id = ?',
            [OrderStatus::Paid->value, $id]
        );
        $s = $stats[0] ?? ['order_count' => 0, 'total_revenue' => 0, 'paid_revenue' => 0];
        $seller['order_count'] = (int) $s['order_count'];
        $seller['total_revenue'] = (float) $s['total_revenue'];
        $seller['paid_revenue'] = (float) $s['paid_revenue'];

        $seller['view_count'] = (int) static::rawScalar(
            'SELECT COUNT(*) FROM shop_views WHERE user_id = ?',
            [$id]
        );

        return $seller;
    }

    /** Count recent seller signups. */
    public function recentSignups(int $days = 7): int
    {
        return (int) static::rawScalar(
            'SELECT COUNT(*) FROM users WHERE role = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)',
            [UserRole::Seller->value, $days]
        );
    }

    // ── Mutations ──

    /**
     * Create a new user.
     *
     * @since 1.0.0
     *
     * @param  array $data User data.
     * @return int   New user ID.
     */
    public function create(array $data): int
    {
        $user = new static();
        $user->fill([
            'name'           => $data['name'] ?? $data['store_name'] ?? '',
            'email'          => $data['email'],
            'password_hash'  => $data['password_hash'] ?? null,
            'oauth_provider' => $data['oauth_provider'] ?? null,
            'oauth_id'       => $data['oauth_id'] ?? null,
            'role'           => $data['role'] ?? UserRole::Seller->value,
            'store_name'     => $data['store_name'] ?? null,
            'subdomain'      => $data['subdomain'] ?? null,
        ]);
        $user->save();
        return (int) $user->getId();
    }

    /**
     * Update a user by ID.
     *
     * @since 1.0.0
     *
     * @param  int   $id   User ID.
     * @param  array $data Fields to update.
     * @return bool  False if not found.
     */
    public function update(int $id, array $data): bool
    {
        $user = static::find($id);
        if (!$user) {
            return false;
        }

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
            'pesapal_consumer_key', 'pesapal_consumer_secret',
            'pesapal_mode', 'pesapal_enabled',
            'plan_id', 'plan_expires_at',
            'payment_mode', 'is_showcased',
            'show_logo', 'show_store_name', 'show_tagline', 'show_search',
            'show_categories', 'show_sort_toolbar', 'show_desktop_footer',
            'announcement_text',
            'google_verification', 'bing_verification',
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }

        return $user->save();
    }

    /** Record a login (bump count and timestamp). */
    public function recordLogin(int $id): void
    {
        static::increment($id, 'login_count');
        static::rawExecute(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    /** Check if a user is active. */
    public function isActive(int $id): bool
    {
        return (int) static::rawScalar(
            'SELECT is_active FROM users WHERE id = ?',
            [$id]
        ) === 1;
    }

    /** Toggle a user's active status. */
    public function toggleActive(int $id, bool $active): bool
    {
        return static::rawExecute(
            'UPDATE users SET is_active = ? WHERE id = ?',
            [$active ? 1 : 0, $id]
        ) > 0;
    }

    /** Get the password hash for a user. */
    public function getPasswordHash(int $id): ?string
    {
        $hash = static::rawScalar(
            'SELECT password_hash FROM users WHERE id = ?',
            [$id]
        );
        return $hash ?: null;
    }

    /** Update a user's password hash. */
    public function updatePassword(int $id, string $hash): bool
    {
        return static::rawExecute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [$hash, $id]
        ) > 0;
    }

    /** Link an OAuth provider to a user. */
    public function updateOAuth(int $id, string $provider, string $oauthId): bool
    {
        return static::rawExecute(
            'UPDATE users SET oauth_provider = ?, oauth_id = ? WHERE id = ?',
            [$provider, $oauthId, $id]
        ) > 0;
    }

    /** Update a user's email. */
    public function updateEmail(int $id, string $email): bool
    {
        return static::rawExecute(
            'UPDATE users SET email = ? WHERE id = ?',
            [$email, $id]
        ) > 0;
    }

    /**
     * Get showcased shops for the homepage.
     *
     * @since 1.0.0
     *
     * @param  int $limit Max shops.
     * @return array[]
     */
    public function findShowcased(int $limit = 12): array
    {
        $db = static::db();
        $stmt = $db->prepare(
            'SELECT id, store_name, subdomain, shop_logo, shop_tagline
             FROM users
             WHERE is_showcased = 1 AND is_active = 1 AND subdomain IS NOT NULL
             ORDER BY RAND()
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a user account and all associated data.
     *
     * @since 1.0.0
     *
     * @param  int $id User ID.
     * @return string[]|false File URLs to clean up, or false on failure.
     */
    public function deleteAccount(int $id): array|false
    {
        $db = static::db();

        // Collect file URLs before deleting rows
        $urls = [];

        $stmt = $db->prepare(
            'SELECT pi.image_url FROM product_images pi
             INNER JOIN products p ON pi.product_id = p.id
             WHERE p.user_id = ? AND pi.image_url IS NOT NULL'
        );
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) {
                $urls[] = $url;
            }
        }

        $stmt = $db->prepare('SELECT image_url FROM products WHERE user_id = ? AND image_url IS NOT NULL');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) {
                $urls[] = $url;
            }
        }

        $stmt = $db->prepare('SELECT image_url FROM categories WHERE user_id = ? AND image_url IS NOT NULL');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $url) {
            if ($url) {
                $urls[] = $url;
            }
        }

        $stmt = $db->prepare('SELECT shop_logo FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $logo = $stmt->fetchColumn();
        if ($logo) {
            $urls[] = $logo;
        }

        try {
            static::transaction(function (\PDO $db) use ($id): void {
                $db->prepare(
                    'DELETE pi FROM product_images pi
                     INNER JOIN products p ON pi.product_id = p.id
                     WHERE p.user_id = ?'
                )->execute([$id]);

                $db->prepare('DELETE FROM products WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM categories WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM shop_views WHERE user_id = ?')->execute([$id]);

                $db->prepare(
                    'DELETE oi FROM order_items oi
                     INNER JOIN orders o ON oi.order_id = o.id
                     WHERE o.user_id = ?'
                )->execute([$id]);
                $db->prepare('DELETE FROM orders WHERE user_id = ?')->execute([$id]);

                $db->prepare('DELETE FROM billing_mpesa_pending WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM theme_options WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM coupons WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM subscriptions WHERE user_id = ?')->execute([$id]);
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            });
            return $urls;
        } catch (\Throwable) {
            return false;
        }
    }
}
