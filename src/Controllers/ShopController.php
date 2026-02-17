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
        return $this->renderShop($request, $response, $args);
    }

    public function showCategory(Request $request, Response $response, array $args): Response
    {
        return $this->renderShop($request, $response, $args, $args['categorySlug'] ?? '');
    }

    private function renderShop(Request $request, Response $response, array $args, string $categorySlug = ''): Response
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

        // Fetch categories early — needed for resolving active category children
        $categories = $this->categoryModel->findByUser($shopId);
        $categoryTree = $this->categoryModel->findByUserAsTree($shopId);

        // Resolve category from slug
        $activeCategory = null;
        $activeCategoryIds = null;
        if ($categorySlug !== '') {
            $activeCategory = $this->categoryModel->findByUserAndSlug($shopId, $categorySlug);
            if (!$activeCategory) {
                return $this->view->render(
                    $response->withStatus(404),
                    'pages/shop_404.tpl',
                    ['page_title' => 'Category Not Found']
                );
            }
            // Include parent + all children so subcategory products appear
            $catId = (int) $activeCategory['id'];
            $ids = [$catId];
            foreach ($categories as $cat) {
                if ((int) ($cat['parent_id'] ?? 0) === $catId) {
                    $ids[] = (int) $cat['id'];
                }
            }
            $activeCategoryIds = implode(',', $ids);
        }

        $products = $this->productModel->findActiveByUserPaginated($shopId, self::PRODUCTS_PER_PAGE, 0, null, $activeCategoryIds);
        $totalProducts = $this->productModel->countActiveByUser($shopId, null, $activeCategoryIds);

        foreach ($products as &$p) {
            $p['variations_data'] = self::filterVariations($p['variations'] ?? null);
        }
        unset($p);

        $products = Hooks::applyFilter('shop.products', $products, $shop);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = self::resolveCurrencySymbol($currency);
        $hasPayments = self::shopHasPayments($shop);

        $response = $this->trackVisit($request, $response, $shopId, null);

        $shopName = $shop['store_name'] ?? '';
        $pageTitle = $shopName;
        $metaDesc = $shop['shop_tagline'] ?: 'Browse products from ' . $shopName;
        if ($activeCategory) {
            $pageTitle = $activeCategory['name'] . ' — ' . $shopName;
            $metaDesc = 'Browse ' . $activeCategory['name'] . ' from ' . $shopName;
        }

        // Activate theme-specific template overrides
        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        $viewData = [
            'page_title'       => $pageTitle,
            'meta_description' => $metaDesc,
            'shop'             => $shop,
            'products'         => $products,
            'categories'       => $categories,
            'category_tree'    => $categoryTree,
            'currency'         => $currency,
            'currency_symbol'  => $currencySymbol,
            'has_payments'     => $hasPayments,
            'shop_theme'       => $shop['shop_theme'] ?? 'classic',
            'og_title'         => $pageTitle,
            'og_description'   => $metaDesc,
            'og_image'         => $shop['shop_logo'] ?? '',
            'og_url'           => $activeCategory ? '/category/' . $activeCategory['slug'] : '/',
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
            'active_category'  => $activeCategory,
        ];

        // Per-shop verification codes override platform-level ones
        if (!empty($shop['google_verification'])) {
            $viewData['google_verification'] = $shop['google_verification'];
        }
        if (!empty($shop['bing_verification'])) {
            $viewData['bing_verification'] = $shop['bing_verification'];
        }

        return $this->view->render($response, 'pages/shop.tpl', $viewData);
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

        $pageTitle = ($product['meta_title'] ?: $product['name']) . ' — ' . ($shop['store_name'] ?? '');
        $metaDesc  = $product['meta_description'] ?: ($product['description'] ? mb_substr(strip_tags($product['description']), 0, 160) : '');

        $hasPayments = self::shopHasPayments($shop);

        // Activate theme-specific template overrides
        $this->view->setTheme($shop['shop_theme'] ?? 'classic');

        $viewData = [
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
        ];

        // Per-shop verification codes override platform-level ones
        if (!empty($shop['google_verification'])) {
            $viewData['google_verification'] = $shop['google_verification'];
        }
        if (!empty($shop['bing_verification'])) {
            $viewData['bing_verification'] = $shop['bing_verification'];
        }

        return $this->view->render($response, 'pages/shop_product.tpl', $viewData);
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
        $hasMpesa  = !empty($shop['mpesa_enabled']) && !empty($shop['mpesa_shortcode']) && !empty($shop['mpesa_consumer_key']) && !empty($shop['mpesa_consumer_secret']) && !empty($shop['mpesa_passkey']);
        $hasCod = !empty($shop['cod_enabled']);
        return $hasStripe || $hasPaypal || $hasMpesa || $hasCod;
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

    public function manifest(Request $request, Response $response, array $args): Response
    {
        $subdomain = $args['subdomain'] ?? '';
        $shop = $this->userModel->findBySubdomain($subdomain);

        if (!$shop) {
            return $response->withStatus(404);
        }

        $shopName = $shop['store_name'] ?? '';
        $icon = $shop['shop_favicon'] ?? $shop['shop_logo'] ?? null;

        $manifest = [
            'name'             => $shopName,
            'short_name'       => mb_substr($shopName, 0, 12),
            'description'      => $shop['shop_tagline'] ?: 'Browse products from ' . $shopName,
            'id'               => '/',
            'start_url'        => '/',
            'display'          => 'standalone',
            'background_color' => '#F5F5F7',
            'theme_color'      => '#111111',
            'orientation'      => 'portrait',
        ];

        if ($icon) {
            $manifest['icons'] = [
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ];
        } else {
            $manifest['icons'] = [
                ['src' => '/public/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/public/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/public/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => '/public/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ];
        }

        $response->getBody()->write(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/manifest+json')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }
}
