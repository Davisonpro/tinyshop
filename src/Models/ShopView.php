<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;
use Throwable;

/**
 * Shop view analytics.
 *
 * @since 1.0.0
 */
final class ShopView
{
    private readonly PDO $db;

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

    /** Domain-to-source mapping. */
    private const SOURCE_DOMAINS = [
        'google'    => ['google.com', 'google.co'],
        'facebook'  => ['facebook.com', 'fb.com', 'fb.me', 'l.facebook.com', 'm.facebook.com'],
        'instagram' => ['instagram.com', 'l.instagram.com'],
        'tiktok'    => ['tiktok.com', 'vm.tiktok.com'],
        'twitter'   => ['twitter.com', 'x.com', 't.co'],
        'whatsapp'  => ['wa.me', 'api.whatsapp.com', 'web.whatsapp.com'],
        'youtube'   => ['youtube.com', 'youtu.be', 'm.youtube.com'],
        'pinterest' => ['pinterest.com', 'pin.it'],
    ];

    private const SOURCE_LABELS = [
        'google'    => 'Google',
        'facebook'  => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'twitter'   => 'Twitter / X',
        'whatsapp'  => 'WhatsApp',
        'youtube'   => 'YouTube',
        'pinterest' => 'Pinterest',
        'direct'    => 'Direct',
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
     * Get or create a visitor token from the cookie.
     *
     * @since 1.0.0
     *
     * @param  string $cookieValue Existing cookie value.
     * @return array{0: string, 1: bool} [token, isNew].
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
     * Extract the bare domain from a Referer URL.
     *
     * @since 1.0.0
     *
     * @param  string $referer Raw Referer header value.
     * @return string|null     Lowercase domain, or null if unparseable.
     */
    public static function extractRefererDomain(string $referer): ?string
    {
        if ($referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if ($host === null || $host === false || $host === '') {
            return null;
        }

        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return mb_substr($host, 0, 100);
    }

    /**
     * Categorize a domain or UTM value into a traffic source.
     *
     * @since 1.0.0
     *
     * @param  ?string $domain Domain or UTM value.
     * @return array{key: string, label: string}
     */
    public static function categorizeSource(?string $domain): array
    {
        if ($domain === null || $domain === '') {
            return ['key' => 'direct', 'label' => self::SOURCE_LABELS['direct']];
        }

        // Direct match on source key (for UTM values like 'whatsapp', 'facebook', 'x')
        $lower = strtolower($domain);
        if (isset(self::SOURCE_LABELS[$lower])) {
            return ['key' => $lower, 'label' => self::SOURCE_LABELS[$lower]];
        }
        // Map 'x' UTM value to twitter key
        if ($lower === 'x') {
            return ['key' => 'twitter', 'label' => self::SOURCE_LABELS['twitter']];
        }

        foreach (self::SOURCE_DOMAINS as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if ($domain === $pattern || str_ends_with($domain, '.' . $pattern)) {
                    return ['key' => $key, 'label' => self::SOURCE_LABELS[$key]];
                }
            }
        }

        return ['key' => 'other', 'label' => $domain];
    }

    /**
     * Record a page view with deduplication.
     *
     * @since 1.0.0
     *
     * @param int     $sellerId       Shop owner ID.
     * @param ?int    $productId      Product ID, or null for shop home.
     * @param string  $visitorToken   Cookie token.
     * @param string  $ip             Visitor IP.
     * @param string  $userAgent      User-agent string.
     * @param ?int    $visitorUserId  Logged-in user ID (to exclude self-views).
     * @param ?string $refererDomain  Parsed referer domain.
     * @param ?string $utmSource      UTM source parameter.
     */
    public function log(
        int $sellerId,
        ?int $productId,
        string $visitorToken,
        string $ip,
        string $userAgent = '',
        ?int $visitorUserId = null,
        ?string $refererDomain = null,
        ?string $utmSource = null
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
                'INSERT INTO shop_views (user_id, product_id, visitor_hash, referer_domain, utm_source, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$sellerId, $productId, $hash, $refererDomain, $utmSource]);
        } catch (Throwable) {
            // Never break the storefront for analytics
        }
    }

    /**
     * Get traffic sources for a seller (last 30 days).
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @return list<array{key: string, label: string, views: int, unique: int, percent: float}>
     */
    public function getTrafficSources(int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT referer_domain, utm_source,
                        COUNT(*) AS views,
                        COUNT(DISTINCT visitor_hash) AS unique_visitors
                 FROM shop_views
                 WHERE user_id = ?
                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY referer_domain, utm_source
                 ORDER BY views DESC"
            );
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();

            $sources = [];
            $totalViews = 0;

            foreach ($rows as $row) {
                $views = (int) $row['views'];
                $unique = (int) $row['unique_visitors'];
                $totalViews += $views;

                // UTM source takes priority over referer domain
                $cat = $row['utm_source']
                    ? self::categorizeSource($row['utm_source'])
                    : self::categorizeSource($row['referer_domain']);
                $key = $cat['key'];

                if (!isset($sources[$key])) {
                    $sources[$key] = [
                        'key'     => $key,
                        'label'   => $cat['label'],
                        'views'   => 0,
                        'unique'  => 0,
                    ];
                }
                $sources[$key]['views'] += $views;
                $sources[$key]['unique'] += $unique;
            }

            usort($sources, static fn(array $a, array $b): int => $b['views'] <=> $a['views']);

            foreach ($sources as &$source) {
                $source['percent'] = $totalViews > 0
                    ? round($source['views'] / $totalViews * 100, 1)
                    : 0;
            }
            unset($source);

            return $sources;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Get aggregate view stats for a seller.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @return array{today: int, week: int, month: int, total: int, unique_week: int}
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
     * Daily view counts for a seller, zero-filled for charts.
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @param  int $days   Number of days (1-90).
     * @return list<array{day: string, label: string, views: int}>
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
     * Top products by views for a seller (last 30 days).
     *
     * @since 1.0.0
     *
     * @param  int $userId Seller ID.
     * @param  int $limit  Max results (1-20).
     * @return list<array{product_id: int, name: string, image_url: ?string, views: int}>
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

    // ── Platform-wide (admin) queries ──

    /**
     * Platform-wide aggregate view stats (admin).
     *
     * @since 1.0.0
     *
     * @return array{today: int, week: int, month: int, total: int, unique_week: int}
     */
    public function getPlatformStats(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                    COALESCE(SUM(created_at >= CURDATE()), 0) AS today,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)), 0) AS week,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) AS month,
                    COUNT(*) AS total,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN visitor_hash END) AS unique_week
                FROM shop_views"
            );
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
     * Platform-wide daily views, zero-filled for charts.
     *
     * @since 1.0.0
     *
     * @param  int $days Number of days (1-90).
     * @return list<array{day: string, label: string, views: int}>
     */
    public function getPlatformDailyViews(int $days = 14): array
    {
        $days = max(1, min($days, 90));

        try {
            $stmt = $this->db->prepare(
                "SELECT DATE(created_at) AS day, COUNT(*) AS views
                FROM shop_views
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY day ASC"
            );
            $stmt->execute([$days]);
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
     * Platform-wide traffic sources (last 30 days).
     *
     * @since 1.0.0
     *
     * @return list<array{key: string, label: string, views: int, unique: int, percent: float}>
     */
    public function getPlatformTrafficSources(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT referer_domain, utm_source,
                        COUNT(*) AS views,
                        COUNT(DISTINCT visitor_hash) AS unique_visitors
                 FROM shop_views
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY referer_domain, utm_source
                 ORDER BY views DESC"
            );
            $rows = $stmt->fetchAll();

            $sources = [];
            $totalViews = 0;

            foreach ($rows as $row) {
                $views = (int) $row['views'];
                $unique = (int) $row['unique_visitors'];
                $totalViews += $views;

                $cat = $row['utm_source']
                    ? self::categorizeSource($row['utm_source'])
                    : self::categorizeSource($row['referer_domain']);
                $key = $cat['key'];

                if (!isset($sources[$key])) {
                    $sources[$key] = [
                        'key'    => $key,
                        'label'  => $cat['label'],
                        'views'  => 0,
                        'unique' => 0,
                    ];
                }
                $sources[$key]['views'] += $views;
                $sources[$key]['unique'] += $unique;
            }

            usort($sources, static fn(array $a, array $b): int => $b['views'] <=> $a['views']);

            foreach ($sources as &$source) {
                $source['percent'] = $totalViews > 0
                    ? round($source['views'] / $totalViews * 100, 1)
                    : 0;
            }
            unset($source);

            return $sources;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Top products platform-wide (last 30 days).
     *
     * @since 1.0.0
     *
     * @param  int $limit Max results (1-20).
     * @return list<array{product_id: int, name: string, image_url: ?string, store_name: string, views: int}>
     */
    public function getPlatformTopProducts(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        try {
            $stmt = $this->db->prepare(
                "SELECT sv.product_id, p.name, p.image_url, u.store_name,
                        COUNT(*) AS views
                FROM shop_views sv
                JOIN products p ON p.id = sv.product_id
                JOIN users u ON u.id = sv.user_id
                WHERE sv.product_id IS NOT NULL
                  AND sv.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY sv.product_id, p.name, p.image_url, u.store_name
                ORDER BY views DESC
                LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Top shops by traffic (last 30 days, admin).
     *
     * @since 1.0.0
     *
     * @param  int $limit Max results (1-20).
     * @return list<array{user_id: int, store_name: string, subdomain: string, views: int, unique_visitors: int}>
     */
    public function getTopShops(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        try {
            $stmt = $this->db->prepare(
                "SELECT sv.user_id, u.store_name, u.subdomain,
                        COUNT(*) AS views,
                        COUNT(DISTINCT sv.visitor_hash) AS unique_visitors
                FROM shop_views sv
                JOIN users u ON u.id = sv.user_id
                WHERE sv.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY sv.user_id, u.store_name, u.subdomain
                ORDER BY views DESC
                LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /** Check if a user-agent looks like a bot. */
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
