<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\HeroSlide;
use TinyShop\Models\Plan;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\Order;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Models\Subscription;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\Theme;
use TinyShop\Services\View;

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
        private readonly HeroSlide $heroSlideModel
    ) {}

    public function home(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $productCount = $this->productModel->countByUser($userId);
        $viewStats = $this->shopViewModel->getStats($userId);
        $orderStats = $this->orderModel->getStats($userId);
        $lowStockProducts = $this->productModel->findLowStock($userId, 5);

        $onboardingSteps = [
            ['key' => 'store_name', 'label' => 'Name your shop', 'done' => !empty($user['store_name']), 'link' => '/dashboard/shop'],
            ['key' => 'logo', 'label' => 'Upload a logo', 'done' => !empty($user['shop_logo']), 'link' => '/dashboard/shop'],
            ['key' => 'product', 'label' => 'Add your first product', 'done' => $productCount > 0, 'link' => '/dashboard/products/add'],
            ['key' => 'contact', 'label' => 'Add contact info', 'done' => !empty($user['contact_whatsapp']) || !empty($user['contact_email']) || !empty($user['contact_phone']), 'link' => '/dashboard/shop'],
            ['key' => 'payments', 'label' => 'Set up payments', 'done' => !empty($user['stripe_enabled']) || !empty($user['paypal_enabled']) || !empty($user['cod_enabled']) || !empty($user['mpesa_enabled']), 'link' => '/dashboard/shop'],
            ['key' => 'homescreen', 'label' => 'Add to homescreen', 'done' => false, 'link' => '#add-to-homescreen'],
        ];

        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dash_home.tpl', [
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

    public function products(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dash_products.tpl', [
            'page_title'  => 'Products',
            'active_page' => 'products',
            'user'        => $user,
            'usage'       => $usage,
        ]);
    }

    public function shop(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        // List available themes from themes/ directory
        $availableThemes = $this->themeService->listAvailable();

        return $this->view->render($response, 'pages/dash_shop.tpl', [
            'page_title'        => 'Shop Settings',
            'user'              => $user,
            'active_page'       => 'shop',
            'usage'             => $usage,
            'available_themes'  => $availableThemes,
        ]);
    }

    public function categories(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);

        return $this->view->render($response, 'pages/dash_categories.tpl', [
            'page_title'  => 'Categories',
            'active_page' => 'products',
            'user'        => $user,
        ]);
    }

    public function orders(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);

        return $this->view->render($response, 'pages/dash_orders.tpl', [
            'page_title'  => 'Orders',
            'active_page' => 'orders',
            'user'        => $user,
            'currency'    => $user['currency'] ?? 'KES',
        ]);
    }

    public function coupons(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $usage = $this->planGuard->getUsageSummary($userId);

        return $this->view->render($response, 'pages/dash_coupons.tpl', [
            'page_title'  => 'Coupons',
            'active_page' => 'orders',
            'user'        => $user,
            'currency'    => $user['currency'] ?? 'KES',
            'usage'       => $usage,
        ]);
    }

    public function analytics(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $viewStats = $this->shopViewModel->getStats($userId);

        $params = $request->getQueryParams();
        $allowed = [7, 14, 30];

        $viewDays = (int) ($params['view_days'] ?? $params['days'] ?? 14);
        $viewDays = in_array($viewDays, $allowed, true) ? $viewDays : 14;

        $salesDays = (int) ($params['sales_days'] ?? $params['days'] ?? 14);
        $salesDays = in_array($salesDays, $allowed, true) ? $salesDays : 14;

        $dailyViews = $this->shopViewModel->getDailyViews($userId, $viewDays);
        $topProducts = $this->shopViewModel->getTopProducts($userId, 5);

        $orderStats = $this->orderModel->getStats($userId);
        $dailySales = $this->orderModel->getDailySales($userId, $salesDays);
        $currency = $user['currency'] ?? 'KES';

        return $this->view->render($response, 'pages/dash_analytics.tpl', [
            'page_title'    => 'Analytics',
            'active_page'   => 'analytics',
            'user'          => $user,
            'view_stats'    => $viewStats,
            'daily_views'   => $dailyViews,
            'top_products'  => $topProducts,
            'order_stats'   => $orderStats,
            'daily_sales'   => $dailySales,
            'currency'      => $currency,
            'view_days'     => $viewDays,
            'sales_days'    => $salesDays,
            'subdomain'     => $user['subdomain'] ?? '',
        ]);
    }

    public function billing(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
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

        // Determine which payment gateways are configured by the admin
        $settings = $this->settingModel->all();
        $gateways = [];
        if (!empty($settings['platform_stripe_secret_key'])) {
            $gateways[] = 'stripe';
        }
        if (!empty($settings['platform_paypal_client_id']) && !empty($settings['platform_paypal_secret'])) {
            $gateways[] = 'paypal';
        }
        if (!empty($settings['platform_mpesa_shortcode']) && !empty($settings['platform_mpesa_consumer_key'])) {
            $gateways[] = 'mpesa';
        }

        return $this->view->render($response, 'pages/dash_billing.tpl', [
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

    public function productForm(Request $request, Response $response, array $args = []): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
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

        return $this->view->render($response, 'pages/dash_product_form.tpl', [
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

    public function design(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $heroSlides = $this->heroSlideModel->findByUser($userId);

        return $this->view->render($response, 'pages/dash_design.tpl', [
            'page_title'   => 'Design',
            'active_page'  => 'shop',
            'user'         => $user,
            'hero_slides'  => $heroSlides,
        ]);
    }
}
