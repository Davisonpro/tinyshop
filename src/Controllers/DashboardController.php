<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\Plan;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\Order;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Models\Subscription;
use TinyShop\Models\ThemeOption;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\Theme;
use TinyShop\Services\View;

/**
 * Seller dashboard controller.
 *
 * @since 1.0.0
 */
final class DashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly Order $orderModel,
        private readonly ShopView $shopViewModel,
        private readonly Plan $planModel,
        private readonly Subscription $subscriptionModel,
        private readonly PlanGuard $planGuard,
        private readonly Theme $themeService,
        private readonly Setting $settingModel,
        private readonly ThemeOption $themeOptionModel
    ) {}

    /**
     * Render the dashboard home page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function home(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $productCount = $this->productModel->countByUser($userId);
        $viewStats = $this->shopViewModel->getStats($userId);
        $orderStats = $this->orderModel->getStats($userId);
        $lowStockProducts = $this->productModel->findLowStock($userId, 5);

        $onboardingSteps = [
            ['key' => 'store_name', 'label' => 'Name your shop', 'done' => !empty($user['store_name']), 'link' => '/dashboard/shop'],
            ['key' => 'logo', 'label' => 'Upload a logo', 'done' => !empty($user['shop_logo']), 'link' => '/dashboard/shop'],
            ['key' => 'product', 'label' => 'Add your first product', 'done' => $productCount > 0, 'link' => '/dashboard/products/add'],
            ['key' => 'contact', 'label' => 'Add contact info', 'done' => !empty($user['contact_whatsapp']) || !empty($user['contact_email']) || !empty($user['contact_phone']), 'link' => '/dashboard/shop'],
            ['key' => 'payments', 'label' => 'Set up payments', 'done' => (!empty($user['stripe_enabled']) && !empty($user['stripe_public_key'])) || (!empty($user['paypal_enabled']) && !empty($user['paypal_client_id'])) || !empty($user['cod_enabled']) || (!empty($user['mpesa_enabled']) && !empty($user['mpesa_shortcode'])), 'link' => '/dashboard/shop'],
            ['key' => 'homescreen', 'label' => 'Add to homescreen', 'done' => false, 'link' => '#add-to-homescreen'],
        ];

        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dashboard/home.tpl', [
            'page_title'         => 'Dashboard',
            'user'               => $user,
            'product_count'      => $productCount,
            'view_stats'         => $viewStats,
            'order_stats'        => $orderStats,
            'currency'           => $user['currency'] ?? 'KES',
            'active_page'        => 'home',
            'low_stock_products' => $lowStockProducts,
            'onboarding_steps'   => $onboardingSteps,
            'usage'              => $usage,
        ]);
    }

    /**
     * Render the products listing page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function products(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dashboard/products.tpl', [
            'page_title'  => 'Products',
            'active_page' => 'products',
            'user'        => $user,
            'usage'       => $usage,
        ]);
    }

    /**
     * Render the shop settings page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function shop(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        // List available themes from themes/ directory
        $availableThemes = $this->themeService->listAvailable();

        return $this->view->render($response, 'pages/dashboard/shop.tpl', [
            'page_title'        => 'Shop Settings',
            'user'              => $user,
            'active_page'       => 'shop',
            'usage'             => $usage,
            'available_themes'  => $availableThemes,
        ]);
    }

    /**
     * Render the categories management page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function categories(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);

        return $this->view->render($response, 'pages/dashboard/categories.tpl', [
            'page_title'  => 'Categories',
            'active_page' => 'products',
            'user'        => $user,
        ]);
    }

    /**
     * Render the orders listing page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function orders(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);

        return $this->view->render($response, 'pages/dashboard/orders.tpl', [
            'page_title'  => 'Orders',
            'active_page' => 'orders',
            'user'        => $user,
            'currency'    => $user['currency'] ?? 'KES',
        ]);
    }

    /**
     * Render the customers listing page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function customers(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);

        return $this->view->render($response, 'pages/dashboard/customers.tpl', [
            'page_title'  => 'Customers',
            'active_page' => 'customers',
            'user'        => $user,
            'currency'    => $user['currency'] ?? 'KES',
        ]);
    }

    /**
     * Render the coupons management page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function coupons(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dashboard/coupons.tpl', [
            'page_title'  => 'Coupons',
            'active_page' => 'orders',
            'user'        => $user,
            'currency'    => $user['currency'] ?? 'KES',
            'usage'       => $usage,
        ]);
    }

    /**
     * Render the analytics page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function analytics(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $viewStats = $this->shopViewModel->getStats($userId);

        $params = $request->getQueryParams();
        $allowed = [7, 14, 30];

        $viewDays = (int) ($params['view_days'] ?? $params['days'] ?? 14);
        $viewDays = in_array($viewDays, $allowed, true) ? $viewDays : 14;

        $salesDays = (int) ($params['sales_days'] ?? $params['days'] ?? 14);
        $salesDays = in_array($salesDays, $allowed, true) ? $salesDays : 14;

        $dailyViews = $this->shopViewModel->getDailyViews($userId, $viewDays);
        $topProducts = $this->shopViewModel->getTopProducts($userId, 5);
        $trafficSources = $this->shopViewModel->getTrafficSources($userId);

        $orderStats = $this->orderModel->getStats($userId);
        $dailySales = $this->orderModel->getDailySales($userId, $salesDays);
        $currency = $user['currency'] ?? 'KES';

        return $this->view->render($response, 'pages/dashboard/analytics.tpl', [
            'page_title'       => 'Analytics',
            'active_page'      => 'analytics',
            'user'             => $user,
            'view_stats'       => $viewStats,
            'daily_views'      => $dailyViews,
            'top_products'     => $topProducts,
            'traffic_sources'  => $trafficSources,
            'order_stats'      => $orderStats,
            'daily_sales'      => $dailySales,
            'currency'         => $currency,
            'view_days'        => $viewDays,
            'sales_days'       => $salesDays,
            'subdomain'        => $user['subdomain'] ?? '',
        ]);
    }

    /**
     * Render the billing page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function billing(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $usage = $this->planGuard->getUsageSummary($userId);
        $plans = $this->planModel->findAll();
        $history = $this->subscriptionModel->findByUser($userId);
        $activeSub = $this->subscriptionModel->findActiveByUser($userId);

        // Add computed fields for template display
        $plans = array_map(function (array $plan): array {
            $plan['feature_list'] = $plan['features'] ? json_decode($plan['features'], true) : [];
            $plan['all_themes'] = $plan['allowed_themes'] === null;
            $plan['custom_domain'] = (bool) $plan['custom_domain_allowed'];
            $plan['coupons'] = (bool) $plan['coupons_allowed'];
            return $plan;
        }, $plans);

        // Determine which payment gateways are enabled and configured by the admin
        $settings = $this->settingModel->all();
        $gateways = [];
        if (!empty($settings['platform_stripe_enabled']) && !empty($settings['platform_stripe_secret_key'])) {
            $gateways[] = 'stripe';
        }
        if (!empty($settings['platform_paypal_enabled']) && !empty($settings['platform_paypal_client_id']) && !empty($settings['platform_paypal_secret'])) {
            $gateways[] = 'paypal';
        }
        if (!empty($settings['platform_mpesa_enabled']) && !empty($settings['platform_mpesa_shortcode']) && !empty($settings['platform_mpesa_consumer_key'])) {
            $gateways[] = 'mpesa';
        }
        if (!empty($settings['platform_pesapal_enabled']) && !empty($settings['platform_pesapal_consumer_key']) && !empty($settings['platform_pesapal_consumer_secret'])) {
            $gateways[] = 'pesapal';
        }

        return $this->view->render($response, 'pages/dashboard/billing.tpl', [
            'page_title'  => 'Billing',
            'active_page' => 'shop',
            'user'        => $user,
            'usage'        => $usage,
            'plans'        => $plans,
            'history'      => $history,
            'active_sub'   => $activeSub,
            'gateways'     => $gateways,
        ]);
    }

    /**
     * Render the add/edit product form.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function productForm(Request $request, Response $response, array $args = []): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $categories = $this->categoryModel->findByUser($userId);
        $categoryTree = $this->categoryModel->findByUserAsTree($userId);

        $product = null;
        $images = [];
        $isEdit = false;

        if (!empty($args['id'])) {
            $product = $this->productModel->findById((int) $args['id']);
            if (!$product || (int) $product['user_id'] !== $userId) {
                return $response->withHeader('Location', '/dashboard/products')->withStatus(302);
            }
            $images = $this->productImageModel->findByProduct((int) $product['id']);
            $isEdit = true;
        }

        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dashboard/product_form.tpl', [
            'page_title'  => $isEdit ? 'Edit Product' : 'Add Product',
            'active_page' => 'products',
            'user'        => $user,
            'product'     => $product,
            'images'      => $images,
            'categories'  => $categories,
            'category_tree' => $categoryTree,
            'currency'    => $user['currency'] ?? 'KES',
            'is_edit'     => $isEdit,
            'usage'       => $usage,
        ]);
    }

    /**
     * Render the design customizer.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function design(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = User::find($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        // Activate seller's theme to trigger customizer registration in functions.php
        $themeSlug = $user['shop_theme'] ?? 'classic';
        $this->themeService->activate($themeSlug, $this->view);

        $customizer = $this->themeService->getCustomizer();
        $schema = $customizer->getSchema();
        $savedOptions = $this->themeOptionModel->getAll($userId, $themeSlug);
        $resolvedOptions = $customizer->resolveOptions($savedOptions);

        return $this->view->render($response, 'pages/dashboard/design.tpl', [
            'page_title'                => 'Design',
            'active_page'               => 'shop',
            'user'                      => $user,
            'customizer_schema'         => $schema,
            'theme_option_values'       => $resolvedOptions,
            'usage'                     => $usage,
            'available_themes'          => $this->themeService->listAvailable(),
        ]);
    }

    /**
     * Render the product import page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function import(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/dashboard/import.tpl', [
            'page_title'  => 'Import Product',
            'active_page' => 'products',
        ]);
    }
}
