<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\Order;
use TinyShop\Models\ShopView;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\View;

final class DashboardController
{
    public function __construct(
        private View $view,
        private Auth $auth,
        private User $userModel,
        private Product $productModel,
        private ProductImage $productImageModel,
        private Category $categoryModel,
        private Order $orderModel,
        private ShopView $shopViewModel
    ) {}

    public function home(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $productCount = $this->productModel->countByUser($userId);
        $viewStats = $this->shopViewModel->getStats($userId);

        return $this->view->render($response, 'pages/dash_home.tpl', [
            'page_title'    => 'Dashboard',
            'user'          => $user,
            'product_count' => $productCount,
            'view_stats'    => $viewStats,
            'active_page'   => 'home',
        ]);
    }

    public function products(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);

        return $this->view->render($response, 'pages/dash_products.tpl', [
            'page_title'  => 'Products',
            'active_page' => 'products',
            'user'        => $user,
        ]);
    }

    public function shop(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);

        return $this->view->render($response, 'pages/dash_shop.tpl', [
            'page_title'  => 'Shop Settings',
            'user'        => $user,
            'active_page' => 'shop',
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

    public function analytics(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $user = $this->userModel->findById($userId);
        $viewStats = $this->shopViewModel->getStats($userId);

        $params = $request->getQueryParams();
        $days = (int) ($params['days'] ?? 14);
        $days = in_array($days, [7, 14, 30], true) ? $days : 14;

        $dailyViews = $this->shopViewModel->getDailyViews($userId, $days);
        $topProducts = $this->shopViewModel->getTopProducts($userId, 5);

        return $this->view->render($response, 'pages/dash_analytics.tpl', [
            'page_title'    => 'Analytics',
            'active_page'   => 'analytics',
            'user'          => $user,
            'view_stats'    => $viewStats,
            'daily_views'   => $dailyViews,
            'top_products'  => $topProducts,
            'selected_days' => $days,
            'subdomain'     => $user['subdomain'] ?? '',
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
        ]);
    }
}
