<?php

declare(strict_types=1);

namespace TinyShop\Models;

use TinyShop\Services\DB;
use PDO;
use Throwable;

/**
 * Marketing page view analytics.
 *
 * @since 1.0.0
 */
final class PageView
{
    private readonly PDO $db;

    private const DEDUP_MINUTES = 30;

    private const EMPTY_STATS = [
        'today'       => 0,
        'week'        => 0,
        'month'       => 0,
        'total'       => 0,
        'unique_week' => 0,
    ];

    /** Human-readable labels for known paths. */
    private const PAGE_LABELS = [
        '/'        => 'Home',
        '/pricing' => 'Pricing',
        '/help'    => 'Help Center',
    ];

    public function __construct(DB $database)
    {
        $this->db = $database->pdo();
    }

    /**
     * Record a page view with deduplication.
     *
     * @since 1.0.0
     *
     * @param string  $pagePath      URL path (e.g. "/pricing").
     * @param string  $visitorToken  Cookie token.
     * @param string  $ip            Visitor IP.
     * @param string  $userAgent     User-agent string.
     * @param ?string $refererDomain Parsed referer domain.
     * @param ?string $utmSource     UTM source parameter.
     */
    public function log(
        string $pagePath,
        string $visitorToken,
        string $ip,
        string $userAgent = '',
        ?string $refererDomain = null,
        ?string $utmSource = null
    ): void {
        try {
            if (ShopView::resolveVisitorToken('')[0] === '' || $this->isBot($userAgent)) {
                return;
            }

            $hash = hash('sha256', $visitorToken . '|' . $ip);

            // Dedup: same visitor + same page within session window
            $dedup = $this->db->prepare(
                'SELECT 1 FROM page_views
                 WHERE visitor_hash = ? AND page_path = ?
                   AND created_at >= DATE_SUB(NOW(), INTERVAL ' . self::DEDUP_MINUTES . ' MINUTE)
                 LIMIT 1'
            );
            $dedup->execute([$hash, $pagePath]);

            if ($dedup->fetchColumn() !== false) {
                return;
            }

            $stmt = $this->db->prepare(
                'INSERT INTO page_views (page_path, visitor_hash, referer_domain, utm_source, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$pagePath, $hash, $refererDomain, $utmSource]);
        } catch (Throwable) {
            // Never break the site for analytics
        }
    }

    /**
     * Get aggregate view stats.
     *
     * @since 1.0.0
     *
     * @return array{today: int, week: int, month: int, total: int, unique_week: int}
     */
    public function getStats(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                    COALESCE(SUM(created_at >= CURDATE()), 0) AS today,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)), 0) AS week,
                    COALESCE(SUM(created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) AS month,
                    COUNT(*) AS total,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN visitor_hash END) AS unique_week
                FROM page_views"
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
     * Daily view counts, zero-filled for charts.
     *
     * @since 1.0.0
     *
     * @param  int $days Number of days (1-90).
     * @return list<array{day: string, label: string, views: int}>
     */
    public function getDailyViews(int $days = 14): array
    {
        $days = max(1, min($days, 90));

        try {
            $stmt = $this->db->prepare(
                "SELECT DATE(created_at) AS day, COUNT(*) AS views
                FROM page_views
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
     * Traffic sources for the last 30 days.
     *
     * @since 1.0.0
     *
     * @return list<array{key: string, label: string, views: int, unique: int, percent: float}>
     */
    public function getTrafficSources(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT referer_domain, utm_source,
                        COUNT(*) AS views,
                        COUNT(DISTINCT visitor_hash) AS unique_visitors
                 FROM page_views
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
                    ? ShopView::categorizeSource($row['utm_source'])
                    : ShopView::categorizeSource($row['referer_domain']);
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
     * Top pages by views (last 30 days).
     *
     * @since 1.0.0
     *
     * @param  int $limit Max results (1-20).
     * @return list<array{page_path: string, views: int, unique_visitors: int, label: string}>
     */
    public function getTopPages(int $limit = 10): array
    {
        $limit = max(1, min($limit, 20));

        try {
            $stmt = $this->db->prepare(
                "SELECT page_path, COUNT(*) AS views,
                        COUNT(DISTINCT visitor_hash) AS unique_visitors
                FROM page_views
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY page_path
                ORDER BY views DESC
                LIMIT ?"
            );
            $stmt->execute([$limit]);
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $row['label'] = self::PAGE_LABELS[$row['page_path']] ?? ucfirst(ltrim($row['page_path'], '/'));
            }
            unset($row);

            return $rows;
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
        $patterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'facebookexternalhit', 'linkedinbot', 'twitterbot',
            'whatsapp', 'telegrambot', 'curl', 'wget', 'python',
            'go-http-client', 'headlesschrome', 'lighthouse',
            'pagespeed', 'prerender', 'phantom', 'selenium',
            'scrapy', 'httpclient', 'java/', 'libwww', 'apache-httpclient',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
