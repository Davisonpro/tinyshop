<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\App;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Order;
use TinyShop\Models\ShopView;
use TinyShop\Models\User;
use TinyShop\Services\Auth;

/**
 * Analytics API controller.
 *
 * Returns view stats, daily views, top products, traffic sources,
 * order stats, and daily sales for the authenticated seller.
 *
 * @since 1.0.0
 */
final class AnalyticsController
{
    use JsonResponder;

    public function __construct(
        private readonly Auth $auth,
        private readonly ShopView $shopViewModel,
        private readonly Order $orderModel,
    ) {}

    /**
     * Get analytics data for the authenticated seller.
     *
     * @since 1.0.0
     */
    public function get(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);

        $params = $request->getQueryParams();
        $period = $params['period'] ?? null;

        // Resolve date range based on period
        [$startDate, $endDate, $days] = $this->resolveDateRange($period, $params);

        // Common queries (not period-dependent)
        $viewStats      = $this->shopViewModel->getStats($userId);
        $topProducts    = $this->shopViewModel->getTopProducts($userId, 5);
        $trafficSources = $this->shopViewModel->getTrafficSources($userId);
        $orderStats     = $this->orderModel->getStats($userId);

        // Period-dependent: daily views & sales
        if ($startDate !== null) {
            $dailyViews = $this->shopViewModel->getDailyViewsRange($userId, $startDate, $endDate);
            $dailySales = $this->orderModel->getDailySalesRange($userId, $startDate, $endDate);
        } else {
            $dailyViews = $this->shopViewModel->getDailyViews($userId, $days);
            $dailySales = $this->orderModel->getDailySales($userId, $days);
        }

        return $this->json($response, [
            'view_stats'      => $viewStats,
            'daily_views'     => $dailyViews,
            'top_products'    => $topProducts,
            'traffic_sources' => $trafficSources,
            'order_stats'     => $orderStats,
            'daily_sales'     => $dailySales,
            'currency'        => $user['currency'] ?? App::DEFAULT_CURRENCY,
            'period'          => $period ?? 'week',
            'days'            => $days,
        ]);
    }

    /**
     * Resolve date range from period or legacy day params.
     *
     * @return array{0: ?string, 1: string, 2: int} [startDate|null, endDate, days]
     */
    private function resolveDateRange(?string $period, array $params): array
    {
        if ($period === 'last_month') {
            $start = new \DateTimeImmutable('first day of last month');
            $end   = new \DateTimeImmutable('last day of last month');
            $days  = (int) $start->diff($end)->days + 1;
            return [$start->format('Y-m-d'), $end->format('Y-m-d'), $days];
        }

        if ($period === 'month') {
            $start = new \DateTimeImmutable('first day of this month');
            $end   = new \DateTimeImmutable('today');
            $days  = (int) $start->diff($end)->days + 1;
            return [$start->format('Y-m-d'), $end->format('Y-m-d'), $days];
        }

        if ($period === 'week') {
            return [null, date('Y-m-d'), 7];
        }

        // Legacy: view_days / sales_days params
        $allowed = [7, 14, 30];
        $days = (int) ($params['view_days'] ?? $params['sales_days'] ?? 7);
        $days = in_array($days, $allowed, true) ? $days : 7;
        return [null, date('Y-m-d'), $days];
    }
}
