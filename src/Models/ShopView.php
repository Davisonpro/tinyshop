<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;
use Throwable;

/**
 * Privacy-friendly analytics with session-based deduplication.
 *
 * - Visitor identified by a random cookie token (no PII stored).
 * - Same visitor + same page within 30 min = 1 view (GA-style sessions).
 * - Bots, crawlers, and seller self-views are excluded.
 * - All methods are fail-safe: errors never break the storefront or dashboard.
 */
final class ShopView
{
    private PDO $db;

    private const EMPTY_STATS = [
        'today'       => 0,
        'week'        => 0,
        'month'       => 0,
        'total'       => 0,
        'unique_week' => 0,
    ];

    private const BOT_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
        'facebookexternalhit', 'linkedinbot', 'twitterbot',
        'whatsapp', 'telegrambot', 'curl', 'wget', 'python',
        'go-http-client', 'headlesschrome', 'lighthouse',
        'pagespeed', 'prerender', 'phantom', 'selenium',
        'scrapy', 'httpclient', 'java/', 'libwww', 'apache-httpclient',
    ];

    /** Cookie name for visitor token. */
    public const COOKIE_NAME = '_tsv';

    /** Dedup window in minutes (GA uses 30). */
    private const DEDUP_MINUTES = 30;

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Get or create a visitor token from the cookie value.
     * Returns [token, isNew] so the controller can set the cookie if new.
     */
    public static function resolveVisitorToken(string $cookieValue = ''): array
    {
        if ($cookieValue !== '' && preg_match('/^[a-f0-9]{32}$/', $cookieValue)) {
            return [$cookieValue, false];
        }

        $token = bin2hex(random_bytes(16));
        return [$token, true];
    }

    /**
     * Log a page view. Silently fails — never breaks the storefront.
     */
    public function log(
        int $sellerId,
        ?int $productId,
        string $visitorToken,
        string $ip,
        string $userAgent = '',
        ?int $visitorUserId = null
    ): void {
        try {
            // Seller viewing their own shop — skip
            if ($visitorUserId !== null && $visitorUserId === $sellerId) {
                return;
            }

            // Bot / empty UA — skip
            if ($this->isBot($userAgent)) {
                return;
            }

            // Build a stable hash from token + IP (handles cookie-less browsers)
            $hash = hash('sha256', $visitorToken . '|' . $ip);

            // Dedup: same visitor + same page within session window → skip
            $dedup = $this->db->prepare(
                'SELECT 1 FROM shop_views
                 WHERE user_id = ? AND visitor_hash = ? AND product_id <=> ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ' . self::DEDUP_MINUTES . ' MINUTE)
                 LIMIT 1'
            );
            $dedup->execute([$sellerId, $hash, $productId]);

            if ($dedup->fetchColumn() !== false) {
                return;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO shop_views (user_id, product_id, visitor_hash, created_at)
                 VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([$sellerId, $productId, $hash]);
        } catch (Throwable) {
            // Never break the storefront for analytics
        }
    }

    /**
     * Stats: today, week, month, all time, unique visitors this week.
     */
    public function getStats(int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT
                    COALESCE(SUM(created_at >= CURDATE()), 0) AS today,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)), 0) AS week,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) AS month,
                    COUNT(*) AS total,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN visitor_hash END) AS unique_week
                FROM shop_views
                WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch();

            return $row ? [
                'today'       => (int) $row['today'],
                'week'        => (int) $row['week'],
                'month'       => (int) $row['month'],
                'total'       => (int) $row['total'],
                'unique_week' => (int) $row['unique_week'],
            ] : self::EMPTY_STATS;
        } catch (Throwable) {
            return self::EMPTY_STATS;
        }
    }

    /**
     * Daily view counts. Always returns exactly $days entries, zero-filled.
     */
    public function getDailyViews(int $userId, int $days = 14): array
    {
        $days = max(1, min($days, 90));

        try {
            $stmt = $this->db->prepare(
                "SELECT DATE(created_at) AS day, COUNT(*) AS views
                FROM shop_views
                WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY day ASC"
            );
            $stmt->execute([$userId, $days]);
            $map = array_column($stmt->fetchAll(), 'views', 'day');
        } catch (Throwable) {
            $map = [];
        }

        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'day'   => $date,
                'label' => date('M j', strtotime($date)),
                'views' => (int) ($map[$date] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Top viewed products in the last 30 days.
     */
    public function getTopProducts(int $userId, int $limit = 5): array
    {
        $limit = max(1, min($limit, 20));

        try {
            $stmt = $this->db->prepare(
                "SELECT sv.product_id, p.name, p.image_url, COUNT(*) AS views
                FROM shop_views sv
                JOIN products p ON p.id = sv.product_id
                WHERE sv.user_id = ?
                  AND sv.product_id IS NOT NULL
                  AND sv.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY sv.product_id, p.name, p.image_url
                ORDER BY views DESC
                LIMIT ?"
            );
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    private function isBot(string $ua): bool
    {
        if ($ua === '') {
            return true;
        }

        $lower = strtolower($ua);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
