<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\ShopView;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Services\Auth;
use TinyShop\Services\View;
use TinyShop\Services\Hooks;

final class ShopController
{
    use JsonResponder;

    private const DEFAULT_CURRENCY = 'KES';
    private const PRODUCTS_PER_PAGE = 24;

    private const CURRENCY_SYMBOLS = [
        'KES' => 'KES ', 'USD' => '$', 'NGN' => "\u{20A6}", 'TZS' => 'TZS ',
        'UGX' => 'UGX ', 'ZAR' => 'R', 'GHS' => "GH\u{20B5}", 'GBP' => "\u{00A3}",
        'EUR' => "\u{20AC}", 'RWF' => 'RWF ', 'ETB' => 'ETB ', 'XOF' => 'CFA ',
    ];

    private const VISITOR_COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // 1 year

    public function __construct(
        private readonly View $view,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly ShopView $shopViewModel,
        private readonly Auth $auth
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
        $products = $this->productModel->findActiveByUserPaginated($shopId, self::PRODUCTS_PER_PAGE, 0);
        $totalProducts = $this->productModel->countActiveByUser($shopId);

        foreach ($products as &$p) {
            $p['variations_data'] = self::filterVariations($p['variations'] ?? null);
        }
        unset($p);

        $products = Hooks::applyFilter('shop.products', $products, $shop);
        $categories = $this->categoryModel->findByUser($shopId);
        $categoryTree = $this->categoryModel->findByUserAsTree($shopId);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);
        $hasPayments = self::shopHasPayments($shop);

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
            'currency'         => $currency,
            'currency_symbol'  => $currencySymbol,
            'has_payments'     => $hasPayments,
            'shop_theme'       => $shop['shop_theme'] ?? 'classic',
            'og_title'         => $shopName,
            'og_description'   => $shop['shop_tagline'] ?: 'Browse products from ' . $shopName,
            'og_image'         => $shop['shop_logo'] ?? '',
            'og_url'           => '/',
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
        ]);
    }

    public function searchProducts(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $shopId = (int) $shop['id'];
        $params = $request->getQueryParams();

        $limit = min(max(1, (int) ($params['limit'] ?? self::PRODUCTS_PER_PAGE)), 48);
        $offset = max(0, (int) ($params['offset'] ?? 0));
        $search = isset($params['search']) ? trim($params['search']) : null;
        $categoryIds = isset($params['category']) ? trim($params['category']) : null;
        $sort = $params['sort'] ?? 'default';

        if ($search === '') {
            $search = null;
        }
        if ($categoryIds === '' || $categoryIds === 'all') {
            $categoryIds = null;
        }

        $products = $this->productModel->findActiveByUserPaginated(
            $shopId, $limit, $offset, $search, $categoryIds, $sort
        );
        $total = $this->productModel->countActiveByUser($shopId, $search, $categoryIds);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);

        return $this->json($response, [
            'products' => $products,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'has_more' => ($offset + count($products)) < $total,
            'currency_symbol' => $currencySymbol,
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

        $product['variations_data'] = self::filterVariations($product['variations'] ?? null);

        $productId = (int) $product['id'];
        $images = $this->productImageModel->findByProduct($productId);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);

        $response = $this->trackVisit($request, $response, $shopId, $productId);

        // Fetch related products: same category first, then others
        $categoryId = $product['category_id'] ? (int) $product['category_id'] : null;
        $moreProducts = $this->productModel->findRelated($shopId, $productId, $categoryId, 6);

        $pageTitle = ($product['meta_title'] ?: $product['name']) . ' — ' . ($shop['store_name'] ?: $shop['name']);
        $metaDesc  = $product['meta_description'] ?: ($product['description'] ? mb_substr(strip_tags($product['description']), 0, 160) : '');

        $hasPayments = self::shopHasPayments($shop);

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
            'has_payments'     => $hasPayments,
            'shop_theme'       => $shop['shop_theme'] ?? 'classic',
            'og_title'         => $product['meta_title'] ?: $product['name'],
            'og_description'   => $metaDesc,
            'og_image'         => $product['image_url'] ?? '',
            'og_url'           => '/' . ($product['slug'] ?: $productId),
            'og_type'          => 'product',
        ]);
    }

    public function orderTracking(Request $request, Response $response, array $args): Response
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

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);

        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        return $this->view->render($response, 'pages/order_tracking.tpl', [
            'page_title'      => 'Track Your Order',
            'shop'            => $shop,
            'currency'        => $currency,
            'currency_symbol' => $currencySymbol,
            'shop_theme'      => $shop['shop_theme'] ?? 'classic',
        ]);
    }

    public function orderLookup(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $body = $request->getParsedBody() ?? [];
        $email = trim((string) ($body['email'] ?? ''));
        $orderNumber = trim((string) ($body['order_number'] ?? ''));

        if (empty($email) || empty($orderNumber)) {
            return $this->json($response, ['error' => true, 'message' => 'Email and order number are required'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid email address'], 422);
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);

        if (!$order ||
            (int) $order['user_id'] !== (int) $shop['id'] ||
            strcasecmp($order['customer_email'] ?? '', $email) !== 0) {
            return $this->json($response, ['error' => true, 'message' => 'Order not found. Check your email and order number.'], 404);
        }

        $items = $this->orderItemModel->findByOrder((int) $order['id']);

        return $this->json($response, [
            'success' => true,
            'order' => [
                'order_number'   => $order['order_number'],
                'status'         => $order['status'],
                'amount'         => (float) $order['amount'],
                'customer_name'  => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'customer_phone' => $order['customer_phone'] ?? null,
                'notes'          => $order['notes'] ?? null,
                'created_at'     => $order['created_at'],
                'items'          => $items,
            ],
        ]);
    }

    private static function resolveCurrencySymbol(string $currency): string
    {
        return self::CURRENCY_SYMBOLS[$currency] ?? $currency . ' ';
    }

    private static function shopHasPayments(array $shop): bool
    {
        $hasStripe = !empty($shop['stripe_enabled']) && !empty($shop['stripe_public_key']) && !empty($shop['stripe_secret_key']);
        $hasPaypal = !empty($shop['paypal_enabled']) && !empty($shop['paypal_client_id']) && !empty($shop['paypal_secret']);
        return $hasStripe || $hasPaypal;
    }

    /**
     * Filter out variation groups that have no options or no name.
     */
    private static function filterVariations(?string $json): ?array
    {
        if ($json === null || $json === '') {
            return null;
        }

        $groups = json_decode($json, true);
        if (!is_array($groups)) {
            return null;
        }

        $filtered = array_values(array_filter($groups, static function (array $group): bool {
            return !empty($group['name'])
                && !empty($group['options'])
                && is_array($group['options']);
        }));

        return $filtered === [] ? null : $filtered;
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
