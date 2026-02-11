<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use TinyShop\Controllers\PageController;
use TinyShop\Controllers\ShopController;
use TinyShop\Controllers\DashboardController;
use TinyShop\Controllers\AdminController;
use TinyShop\Controllers\Api\AuthController as ApiAuthController;
use TinyShop\Controllers\Api\ProductController as ApiProductController;
use TinyShop\Controllers\Api\ShopController as ApiShopController;
use TinyShop\Controllers\Api\UploadController as ApiUploadController;
use TinyShop\Controllers\Api\OrderController as ApiOrderController;
use TinyShop\Controllers\Api\CategoryController as ApiCategoryController;
use TinyShop\Middleware\AuthGuard;
use TinyShop\Middleware\AdminGuard;
use TinyShop\Middleware\GuestOnly;
use TinyShop\Middleware\CsrfGuard;
use TinyShop\Middleware\JsonResponse;
use TinyShop\Middleware\RateLimit;

return function (App $app): void {

    // ── Health check ──

    $app->get('/health', function ($request, $response) {
        $container = $this->getContainer();
        try {
            $container->get(\TinyShop\Services\DB::class)->pdo()->query('SELECT 1');
            $status = ['status' => 'ok', 'db' => 'ok', 'timestamp' => date('c')];
            $code = 200;
        } catch (\Throwable $e) {
            $status = ['status' => 'degraded', 'db' => 'error', 'timestamp' => date('c')];
            $code = 503;
        }
        $response->getBody()->write(json_encode($status));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    });

    // ── Public pages ──

    $app->get('/', [PageController::class, 'landing']);

    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/login', [PageController::class, 'login']);
        $group->get('/register', [PageController::class, 'register']);
        $group->get('/forgot-password', [PageController::class, 'forgotPassword']);
        $group->get('/reset-password', [PageController::class, 'resetPassword']);
    })->add(GuestOnly::class);

    $app->get('/auth/{provider}', [PageController::class, 'oauthRedirect']);
    $app->get('/auth/callback/{provider}', [PageController::class, 'oauthCallback']);

    $app->get('/logout', [PageController::class, 'logout']);

    // Shop pages — accessed via subdomain/custom domain (rewritten by CustomDomain middleware)
    $app->get('/~shop/{subdomain}', [ShopController::class, 'show']);
    $app->get('/~shop/{subdomain}/{slug}', [ShopController::class, 'showProduct']);

    // ── Seller dashboard (auth required) ──

    $app->group('/dashboard', function (RouteCollectorProxy $group) {
        $group->get('', [DashboardController::class, 'home']);
        $group->get('/products', [DashboardController::class, 'products']);
        $group->get('/products/add', [DashboardController::class, 'productForm']);
        $group->get('/products/{id}/edit', [DashboardController::class, 'productForm']);
        $group->get('/shop', [DashboardController::class, 'shop']);
        $group->get('/categories', [DashboardController::class, 'categories']);
        $group->get('/analytics', [DashboardController::class, 'analytics']);
        $group->get('/orders', [DashboardController::class, 'orders']);
    })->add(AuthGuard::class);

    // ── Admin panel (admin only) ──

    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('', [AdminController::class, 'dashboard']);
        $group->get('/sellers', [AdminController::class, 'sellers']);
        $group->get('/sellers/{id}', [AdminController::class, 'sellerDetail']);
        $group->get('/settings', [AdminController::class, 'settings']);
        $group->get('/impersonate/{id}', [AdminController::class, 'impersonate']);
    })->add(AdminGuard::class);

    // Stop impersonation (needs AuthGuard, not AdminGuard — user is currently a seller)
    $app->get('/admin/stop-impersonate', [AdminController::class, 'stopImpersonate'])->add(AuthGuard::class);

    // ── API routes ──

    $app->group('/api', function (RouteCollectorProxy $api) {

        // Auth (public, rate-limited)
        $api->group('/auth', function (RouteCollectorProxy $auth) {
            $auth->post('/register', [ApiAuthController::class, 'register']);
            $auth->post('/login', [ApiAuthController::class, 'login']);
            $auth->post('/logout', [ApiAuthController::class, 'logout']);
            $auth->post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
            $auth->post('/reset-password', [ApiAuthController::class, 'resetPassword']);
        })->add(new RateLimit(maxAttempts: 10, windowSeconds: 60));

        // Seller endpoints (auth required)
        $api->group('', function (RouteCollectorProxy $protected) {
            $protected->get('/products', [ApiProductController::class, 'list']);
            $protected->post('/products', [ApiProductController::class, 'create']);
            $protected->put('/products/{id}', [ApiProductController::class, 'update']);
            $protected->delete('/products/{id}', [ApiProductController::class, 'delete']);

            $protected->get('/shop', [ApiShopController::class, 'get']);
            $protected->put('/shop', [ApiShopController::class, 'update']);
            $protected->put('/shop/password', [ApiShopController::class, 'changePassword']);
            $protected->put('/shop/email', [ApiShopController::class, 'changeEmail']);
            $protected->delete('/shop', [ApiShopController::class, 'deleteAccount']);

            $protected->get('/categories', [ApiCategoryController::class, 'list']);
            $protected->post('/categories', [ApiCategoryController::class, 'create']);
            $protected->put('/categories/{id}', [ApiCategoryController::class, 'update']);
            $protected->delete('/categories/{id}', [ApiCategoryController::class, 'delete']);

            $protected->post('/upload', [ApiUploadController::class, 'store']);

            $protected->get('/orders', [ApiOrderController::class, 'list']);
            $protected->post('/orders', [ApiOrderController::class, 'create']);
            $protected->put('/orders/{id}/status', [ApiOrderController::class, 'updateStatus']);
            $protected->delete('/orders/{id}', [ApiOrderController::class, 'delete']);
        })->add(AuthGuard::class);

        // Admin API endpoints (admin only)
        $api->group('/admin', function (RouteCollectorProxy $admin) {
            $admin->put('/sellers/{id}/toggle', [AdminController::class, 'toggleSeller']);
            $admin->delete('/sellers/{id}', [AdminController::class, 'deleteSeller']);
            $admin->put('/settings', [AdminController::class, 'updateSettings']);
            $admin->post('/test-email', [AdminController::class, 'testEmail']);
        })->add(AdminGuard::class);

    })->add(CsrfGuard::class)->add(JsonResponse::class);
};
