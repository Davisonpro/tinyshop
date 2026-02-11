<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\ShopView;
use TinyShop\Services\Auth;
use TinyShop\Services\View;
use TinyShop\Services\Hooks;

final class ShopController
{
    private const DEFAULT_CURRENCY = 'KES';

    private const CURRENCY_SYMBOLS = [
        'KES' => 'KES ', 'USD' => '$', 'NGN' => "\u{20A6}", 'TZS' => 'TZS ',
        'UGX' => 'UGX ', 'ZAR' => 'R', 'GHS' => "GH\u{20B5}", 'GBP' => "\u{00A3}",
        'EUR' => "\u{20AC}", 'RWF' => 'RWF ', 'ETB' => 'ETB ', 'XOF' => 'CFA ',
    ];

    private const VISITOR_COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // 1 year

    public function __construct(
        private View $view,
        private User $userModel,
        private Product $productModel,
        private ProductImage $productImageModel,
        private Category $categoryModel,
        private ShopView $shopViewModel,
        private Auth $auth
    ) {}

    public function show(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $shopId = (int) $shop['id'];
        $products = $this->productModel->findActiveByUser($shopId);

        foreach ($products as &$p) {
            $p['variations_data'] = !empty($p['variations']) ? json_decode($p['variations'], true) : null;
        }
        unset($p);

        $products = Hooks::applyFilter('shop.products', $products, $shop);
        $categories = $this->categoryModel->findByUser($shopId);
        $categoryTree = $this->categoryModel->findByUserAsTree($shopId);

        $currencySymbol = self::resolveCurrencySymbol($shop['currency'] ?? self::DEFAULT_CURRENCY);

        $response = $this->trackVisit($request, $response, $shopId, null);

        $shopName = $shop['store_name'] ?: $shop['name'] . "'s Shop";

        // Activate theme-specific template overrides
        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        return $this->view->render($response, 'pages/shop.tpl', [
            'page_title'       => $shopName,
            'meta_description' => $shop['shop_tagline'] ?: 'Browse products from ' . $shopName,
            'shop'             => $shop,
            'products'         => $products,
            'categories'       => $categories,
            'category_tree'    => $categoryTree,
            'currency_symbol'  => $currencySymbol,
            'shop_theme'       => $shop['shop_theme'] ?? 'classic',
            'og_title'         => $shopName,
            'og_description'   => $shop['shop_tagline'] ?: 'Browse products from ' . $shopName,
            'og_image'         => $shop['shop_logo'] ?? '',
            'og_url'           => '/',
        ]);
    }

    public function showProduct(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $productSlug = $args['slug'] ?? '';

        $shop = $this->userModel->findBySubdomain($subdomain);
        if (!$shop) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Shop Not Found']
            );
        }

        $shopId = (int) $shop['id'];

        $product = $this->productModel->findBySlug($shopId, $productSlug);
        if (!$product && ctype_digit($productSlug)) {
            $product = $this->productModel->findById((int) $productSlug);
        }

        if (!$product || (int) $product['user_id'] !== $shopId || !(int) $product['is_active']) {
            return $this->view->render(
                $response->withStatus(404),
                'pages/shop_404.tpl',
                ['page_title' => 'Product Not Found']
            );
        }

        // Redirect numeric IDs to slug URL for SEO
        if (ctype_digit($productSlug) && !empty($product['slug'])) {
            return $response->withHeader('Location', '/' . $product['slug'])->withStatus(301);
        }

        $product['variations_data'] = !empty($product['variations'])
            ? json_decode($product['variations'], true) : null;

        $productId = (int) $product['id'];
        $images = $this->productImageModel->findByProduct($productId);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);

        $response = $this->trackVisit($request, $response, $shopId, $productId);

        // Fetch more products from same shop (exclude current, limit 6)
        $allProducts = $this->productModel->findActiveByUser($shopId);
        $moreProducts = [];
        foreach ($allProducts as $p) {
            if ((int) $p['id'] !== $productId) {
                $moreProducts[] = $p;
                if (count($moreProducts) >= 6) break;
            }
        }

        $pageTitle = ($product['meta_title'] ?: $product['name']) . ' — ' . ($shop['store_name'] ?: $shop['name']);
        $metaDesc  = $product['meta_description'] ?: ($product['description'] ? mb_substr(strip_tags($product['description']), 0, 160) : '');

        // Activate theme-specific template overrides
        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        return $this->view->render($response, 'pages/shop_product.tpl', [
            'page_title'       => $pageTitle,
            'meta_description' => $metaDesc,
            'shop'             => $shop,
            'product'          => $product,
            'images'           => $images,
            'more_products'    => $moreProducts,
            'currency'         => $currency,
            'currency_symbol'  => $currencySymbol,
            'shop_theme'       => $shop['shop_theme'] ?? 'classic',
            'og_title'         => $product['meta_title'] ?: $product['name'],
            'og_description'   => $metaDesc,
            'og_image'         => $product['image_url'] ?? '',
            'og_url'           => '/' . ($product['slug'] ?: $productId),
            'og_type'          => 'product',
        ]);
    }

    private static function resolveCurrencySymbol(string $currency): string
    {
        return self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';
    }

    private function trackVisit(Request $request, Response $response, int $shopId, ?int $productId): Response
    {
        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $serverParams['HTTP_USER_AGENT'] ?? '';

        $cookies = $request->getCookieParams();
        [$visitorToken, $isNewToken] = ShopView::resolveVisitorToken($cookies[ShopView::COOKIE_NAME] ?? '');
        $visitorUserId = $this->auth->userId();
        $this->shopViewModel->log($shopId, $productId, $visitorToken, $ip, $ua, $visitorUserId);

        if ($isNewToken) {
            $response = $response->withHeader(
                'Set-Cookie',
                ShopView::COOKIE_NAME . '=' . $visitorToken
                . '; Path=/; Max-Age=' . self::VISITOR_COOKIE_MAX_AGE . '; HttpOnly; SameSite=Lax'
            );
        }

        return $response;
    }
}
