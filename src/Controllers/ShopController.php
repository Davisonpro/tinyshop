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
use TinyShop\Models\ThemeOption;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Customer;
use TinyShop\Services\Auth;
use TinyShop\Services\CustomerAuth;
use TinyShop\Services\PlanGuard;
use TinyShop\Services\View;
use TinyShop\Services\Theme;
use TinyShop\Services\Hooks;

/**
 * Public storefront controller.
 *
 * @since 1.0.0
 */
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

    private const VISITOR_COOKIE_MAX_AGE = 365 * 24 * 60 * 60;

    private const PALETTES = [
        'default'  => ['anchor' => '#222222', 'depth' => '#555555', 'conversion' => '#888888', 'substrate' => '#E0E0E0', 'canvas' => '#FFFFFF'],
        'ocean'    => ['anchor' => '#0F2B46', 'depth' => '#1B4D7A', 'conversion' => '#E07A5F', 'substrate' => '#E8A393', 'canvas' => '#FDF0EC'],
        'forest'   => ['anchor' => '#1B4332', 'depth' => '#2D6A4F', 'conversion' => '#D4A373', 'substrate' => '#E2C4A0', 'canvas' => '#F5EEDF'],
        'sunset'   => ['anchor' => '#5C1A0A', 'depth' => '#8B3A2A', 'conversion' => '#457B9D', 'substrate' => '#7BABC5', 'canvas' => '#E0EDF5'],
        'lavender' => ['anchor' => '#2D2457', 'depth' => '#5B4D8A', 'conversion' => '#F2CC8F', 'substrate' => '#F6DDB4', 'canvas' => '#FDF5E8'],
        'cherry'   => ['anchor' => '#7B1E34', 'depth' => '#A63D50', 'conversion' => '#C9534A', 'substrate' => '#E0918B', 'canvas' => '#FBE9E7'],
        'sage'     => ['anchor' => '#5A7247', 'depth' => '#7D9B6A', 'conversion' => '#8B6F4E', 'substrate' => '#C4AD8F', 'canvas' => '#F3EDE4'],
        'midnight' => ['anchor' => '#151B2B', 'depth' => '#2A3450', 'conversion' => '#4A7CFF', 'substrate' => '#8DABFF', 'canvas' => '#E8EEFF'],
        'mocha'    => ['anchor' => '#3E2723', 'depth' => '#5D4037', 'conversion' => '#A1887F', 'substrate' => '#C8B7AF', 'canvas' => '#F5F0ED'],
        'blush'    => ['anchor' => '#4A3040', 'depth' => '#6E4D60', 'conversion' => '#D4889E', 'substrate' => '#E8B4C4', 'canvas' => '#FDF0F4'],
    ];

    public function __construct(
        private readonly View $view,
        private readonly Theme $theme,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly ShopView $shopViewModel,
        private readonly ThemeOption $themeOptionModel,
        private readonly PlanGuard $planGuard,
        private readonly Auth $auth,
        private readonly CustomerAuth $customerAuth,
        private readonly Customer $customerModel
    ) {}

    // ── Page Handlers ─────────────────────────────────────────

    /**
     * Render the shop homepage.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->renderShop($request, $response, $args);
    }

    /**
     * Render a product detail page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showProduct(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];
        $productSlug = $args['slug'] ?? '';

        $product = $this->productModel->findBySlug($shopId, $productSlug);
        if (!$product && ctype_digit($productSlug)) {
            $product = $this->productModel->findById((int) $productSlug);
        }

        if (!$product || (int) $product['user_id'] !== $shopId || !(int) $product['is_active']) {
            return $this->render404($response, 'Product Not Found');
        }

        if (ctype_digit($productSlug) && !empty($product['slug'])) {
            return $response->withHeader('Location', '/' . $product['slug'])->withStatus(301);
        }

        $product['variations_data'] = self::filterVariations($product['variations'] ?? null);
        $product = Hooks::applyFilter('product.data', $product, $shop);

        $productId = (int) $product['id'];
        $images = $this->productImageModel->findByProduct($productId);
        $images = Hooks::applyFilter('product.images', $images, $product);

        $response = $this->trackVisit($request, $response, $shopId, $productId);

        $categoryId = $product['category_id'] ? (int) $product['category_id'] : null;
        $moreProducts = $this->productModel->findRelated($shopId, $productId, $categoryId, 6);
        $moreProducts = Hooks::applyFilter('product.related', $moreProducts, $product, $shop);

        $pageTitle = ($product['meta_title'] ?: $product['name']) . ' — ' . ($shop['store_name'] ?? '');
        $metaDesc  = $product['meta_description']
            ?: ($product['description'] ? mb_substr(strip_tags($product['description']), 0, 160) : '');

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        $viewData = [
            'page_title'       => $pageTitle,
            'meta_description' => $metaDesc,
            'current_page'     => 'product',
            'shop'             => $shop,
            'product'          => $product,
            'images'           => $images,
            'more_products'    => $moreProducts,
            'og_title'         => $product['meta_title'] ?: $product['name'],
            'og_description'   => $metaDesc,
            'og_image'         => $product['image_url'] ?? '',
            'og_url'           => '/' . ($product['slug'] ?: $productId),
            'og_type'          => 'product',
            ...$ctx,
        ];

        $this->applyVerificationCodes($viewData, $shop);
        $viewData = Hooks::applyFilter('product.view_data', $viewData, $shop, $product);
        Hooks::doAction('product.before_render', $shop, $product);

        return $this->view->render($response, 'pages/shop/product.tpl', $viewData);
    }

    /**
     * Render the collections index page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showCollections(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];
        $categories = $this->categoryModel->findByUserAsTree($shopId);

        foreach ($categories as &$cat) {
            $ids = [(int) $cat['id']];
            foreach ($cat['children'] ?? [] as $child) {
                $ids[] = (int) $child['id'];
            }
            $cat['product_count'] = $this->productModel->countActiveByUser($shopId, null, implode(',', $ids));
        }
        unset($cat);

        $categories = Hooks::applyFilter('collections.categories', $categories, $shop);

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);
        $shopName = $shop['store_name'] ?? '';

        $viewData = [
            'page_title'       => 'Collections — ' . $shopName,
            'meta_description' => 'Browse all collections from ' . $shopName,
            'current_page'     => 'collections',
            'shop'             => $shop,
            'categories'       => $categories,
            ...$ctx,
        ];

        $viewData = Hooks::applyFilter('collections.view_data', $viewData, $shop);
        Hooks::doAction('collections.before_render', $shop);

        return $this->view->render($response, 'pages/shop/collections.tpl', $viewData);
    }

    /**
     * Render a single collection page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showCollection(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];
        $slug = $args['slug'] ?? '';
        $category = $this->categoryModel->findByUserAndSlug($shopId, $slug);

        if (!$category) {
            return $this->render404($response, 'Collection Not Found');
        }

        $catId = (int) $category['id'];
        $categoryIds = $this->resolveCategoryWithChildren($shopId, $catId);
        $sort = $request->getQueryParams()['sort'] ?? 'default';

        $allCategories = $this->categoryModel->findByUser($shopId);
        $subcategories = array_values(array_filter(
            $allCategories,
            static fn(array $c): bool => (int) ($c['parent_id'] ?? 0) === $catId
        ));

        $products = $this->productModel->findActiveByUserPaginated($shopId, self::PRODUCTS_PER_PAGE, 0, null, $categoryIds, $sort);
        $totalProducts = $this->productModel->countActiveByUser($shopId, null, $categoryIds);
        $products = $this->prepareProducts($products, $shop);

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);
        $shopName = $shop['store_name'] ?? '';

        $viewData = [
            'page_title'       => $category['name'] . ' — ' . $shopName,
            'meta_description' => 'Browse ' . $category['name'] . ' from ' . $shopName,
            'current_page'     => 'collection',
            'shop'             => $shop,
            'category'         => $category,
            'subcategories'    => $subcategories,
            'products'         => $products,
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
            'sort'             => $sort,
            ...$ctx,
        ];

        $viewData = Hooks::applyFilter('collection.view_data', $viewData, $shop, $category);
        Hooks::doAction('collection.before_render', $shop, $category);

        return $this->view->render($response, 'pages/shop/collection.tpl', $viewData);
    }

    /**
     * Render the search results page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function showSearchPage(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];
        $params = $request->getQueryParams();
        $query = isset($params['q']) ? trim($params['q']) : '';

        $products = [];
        $totalProducts = 0;
        $categories = [];

        if ($query !== '') {
            $products = $this->productModel->findActiveByUserPaginated(
                $shopId, self::PRODUCTS_PER_PAGE, 0, $query
            );
            $totalProducts = $this->productModel->countActiveByUser($shopId, $query);
            $products = $this->prepareProducts($products, $shop);
        } else {
            $categories = $this->categoryModel->findByUserAsTree($shopId);
            foreach ($categories as &$cat) {
                $ids = [(int) $cat['id']];
                foreach ($cat['children'] ?? [] as $child) {
                    $ids[] = (int) $child['id'];
                }
                $cat['product_count'] = $this->productModel->countActiveByUser($shopId, null, implode(',', $ids));
            }
            unset($cat);
        }

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);
        $shopName = $shop['store_name'] ?? '';

        $viewData = [
            'page_title'       => ($query !== '' ? 'Search: ' . $query : 'Search') . ' — ' . $shopName,
            'meta_description' => 'Search results for "' . $query . '" from ' . $shopName,
            'current_page'     => 'search',
            'shop'             => $shop,
            'search_query'     => $query,
            'products'         => $products,
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
            'categories'       => $categories,
            ...$ctx,
        ];

        return $this->view->render($response, 'pages/shop/search_results.tpl', $viewData);
    }

    // ── API Handlers ──────────────────────────────────────────

    /**
     * Search and filter products with pagination.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function searchProducts(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $shopId = (int) $shop['id'];
        $params = $request->getQueryParams();

        $queryArgs = [
            'limit'    => min(max(1, (int) ($params['limit'] ?? self::PRODUCTS_PER_PAGE)), 48),
            'offset'   => max(0, (int) ($params['offset'] ?? 0)),
            'search'   => isset($params['search']) ? trim($params['search']) : null,
            'category' => isset($params['category']) ? trim($params['category']) : null,
            'sort'     => $params['sort'] ?? 'default',
        ];

        if ($queryArgs['search'] === '') {
            $queryArgs['search'] = null;
        }
        if ($queryArgs['category'] === '' || $queryArgs['category'] === 'all') {
            $queryArgs['category'] = null;
        }

        $queryArgs = Hooks::applyFilter('shop.search_query', $queryArgs, $shop);

        $products = $this->productModel->findActiveByUserPaginated(
            $shopId, $queryArgs['limit'], $queryArgs['offset'],
            $queryArgs['search'], $queryArgs['category'], $queryArgs['sort']
        );
        $total = $this->productModel->countActiveByUser($shopId, $queryArgs['search'], $queryArgs['category']);

        $products = $this->prepareProducts($products, $shop);

        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = $this->resolveCurrencySymbol($currency);
        $format = $params['format'] ?? 'json';

        if ($format === 'html') {
            $this->activateTheme($shop);
            $html = $this->renderProductCardsHtml($products, $currencySymbol);

            return $this->json($response, [
                'html'     => $html,
                'total'    => $total,
                'offset'   => $queryArgs['offset'],
                'limit'    => $queryArgs['limit'],
                'has_more' => ($queryArgs['offset'] + count($products)) < $total,
            ]);
        }

        $result = [
            'products'        => $products,
            'total'           => $total,
            'offset'          => $queryArgs['offset'],
            'limit'           => $queryArgs['limit'],
            'has_more'        => ($queryArgs['offset'] + count($products)) < $total,
            'currency_symbol' => $currencySymbol,
        ];

        return $this->json($response, Hooks::applyFilter('shop.search_results', $result, $shop));
    }

    /**
     * Render the order tracking page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function orderTracking(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        return $this->view->render($response, 'pages/shop/order_tracking.tpl', [
            'page_title'   => 'Track Your Order',
            'current_page' => 'orders',
            'shop'         => $shop,
            ...$ctx,
        ]);
    }

    /**
     * Look up an order by email and order number.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function orderLookup(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
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

        $orderData = [
            'order_number'   => $order['order_number'],
            'status'         => $order['status'],
            'amount'         => (float) $order['amount'],
            'customer_name'  => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'] ?? null,
            'notes'          => $order['notes'] ?? null,
            'created_at'     => $order['created_at'],
            'items'          => $items,
        ];

        $orderData = Hooks::applyFilter('order.tracking_data', $orderData, $shop);

        return $this->json($response, ['success' => true, 'order' => $orderData]);
    }

    /**
     * Render the customer account page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function account(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];
        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        // If the shop owner is already logged in, redirect straight to dashboard
        if ($this->auth->check() && $this->auth->userId() === $shopId) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        $isLoggedIn = $this->customerAuth->check($shopId);
        $customer = null;
        $orders = [];

        if ($isLoggedIn) {
            $customerId = $this->customerAuth->customerId();
            $customer = Customer::find($customerId);
            $orders = $this->orderModel->findByCustomer($customerId, 20);

            $orderIds = array_column($orders, 'id');
            if (!empty($orderIds)) {
                $allItems = $this->orderItemModel->findByOrderIds($orderIds);
                $itemsByOrder = [];
                foreach ($allItems as $item) {
                    $itemsByOrder[(int) $item['order_id']][] = $item;
                }
                foreach ($orders as &$order) {
                    $order['items'] = $itemsByOrder[(int) $order['id']] ?? [];
                }
                unset($order);
            }
        }

        $shopName = $shop['store_name'] ?? '';

        $queryParams = $request->getQueryParams();
        $resetToken = $queryParams['reset_token'] ?? '';

        return $this->view->render($response, 'pages/shop/account.tpl', [
            'page_title'         => 'My Account — ' . $shopName,
            'current_page'       => 'account',
            'shop'               => $shop,
            'customer'           => $customer,
            'orders'             => $orders,
            'reset_token'        => $resetToken,
            ...$ctx,
        ]);
    }

    /**
     * Serve the shop's PWA manifest.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function manifest(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
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

        $defaultIcon = '/public/img/icon-192.png';
        $defaultIcon512 = '/public/img/icon-512.png';
        $src = $icon ?: $defaultIcon;
        $src512 = $icon ?: $defaultIcon512;

        $manifest['icons'] = [
            ['src' => $src,    'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $src512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $src,    'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => $src512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];

        $manifest = Hooks::applyFilter('shop.manifest', $manifest, $shop);

        $response->getBody()->write(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/manifest+json')
            ->withHeader('Cache-Control', 'public, max-age=3600');
    }

    // ── Private Helpers ───────────────────────────────────────

    private function renderShop(Request $request, Response $response, array $args, string $categorySlug = ''): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $shopId = (int) $shop['id'];

        $categories = $this->categoryModel->findByUser($shopId);
        $categoryTree = $this->categoryModel->findByUserAsTree($shopId);

        $activeCategory = null;
        $activeCategoryIds = null;
        if ($categorySlug !== '') {
            $activeCategory = $this->categoryModel->findByUserAndSlug($shopId, $categorySlug);
            if (!$activeCategory) {
                return $this->render404($response, 'Category Not Found');
            }
            $activeCategoryIds = $this->resolveCategoryWithChildren($shopId, (int) $activeCategory['id']);
        }

        $products = $this->productModel->findActiveByUserPaginated($shopId, self::PRODUCTS_PER_PAGE, 0, null, $activeCategoryIds);
        $totalProducts = $this->productModel->countActiveByUser($shopId, null, $activeCategoryIds);
        $products = $this->prepareProducts($products, $shop);

        $response = $this->trackVisit($request, $response, $shopId, null);

        $shopName = $shop['store_name'] ?? '';
        $pageTitle = $shopName;
        $metaDesc = $shop['shop_tagline'] ?: 'Browse products from ' . $shopName;
        if ($activeCategory) {
            $pageTitle = $activeCategory['name'] . ' — ' . $shopName;
            $metaDesc = 'Browse ' . $activeCategory['name'] . ' from ' . $shopName;
        }

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        $saleProducts = array_values(array_filter($products, static fn(array $p): bool =>
            !empty($p['compare_price']) && $p['compare_price'] > $p['price'] && !$p['is_sold']
        ));
        $featuredProducts = array_values(array_filter($products, static fn(array $p): bool =>
            !empty($p['is_featured']) && !$p['is_sold']
        ));

        $viewData = [
            'page_title'         => $pageTitle,
            'meta_description'   => $metaDesc,
            'current_page'       => 'home',
            'shop'               => $shop,
            'products'           => $products,
            'categories'         => $categories,
            'category_tree'      => $categoryTree,
            'og_title'           => $pageTitle,
            'og_description'     => $metaDesc,
            'og_image'           => $shop['shop_logo'] ?? '',
            'og_url'             => $activeCategory ? '/collections/' . $activeCategory['slug'] : '/',
            'total_products'     => $totalProducts,
            'products_limit'     => self::PRODUCTS_PER_PAGE,
            'active_category'    => $activeCategory,
            'sale_products'      => $saleProducts,
            'featured_products'  => $featuredProducts,
            ...$ctx,
        ];

        $this->applyVerificationCodes($viewData, $shop);
        $viewData = Hooks::applyFilter('shop.view_data', $viewData, $shop);
        Hooks::doAction('shop.before_render', $shop);

        return $this->view->render($response, 'pages/shop/home.tpl', $viewData);
    }

    private function resolveShop(array $args): ?array
    {
        $subdomain = $args['subdomain'] ?? '';
        return $this->userModel->findBySubdomain($subdomain);
    }

    private function render404(Response $response, string $title): Response
    {
        return $this->view->render(
            $response->withStatus(404),
            'pages/shop/404.tpl',
            ['page_title' => $title]
        );
    }

    private function buildShopContext(array $shop): array
    {
        $currency = $shop['currency'] ?? self::DEFAULT_CURRENCY;
        $currencySymbol = $this->resolveCurrencySymbol($currency);
        $hasPayments = $this->resolveHasPayments($shop);

        $paletteKey = $shop['color_palette'] ?? 'default';
        $palettes = Hooks::applyFilter('shop.palettes', self::PALETTES, $shop);
        $paletteCss = $palettes[$paletteKey] ?? $palettes['default'];

        $shopId = (int) $shop['id'];
        Auth::ensureSession();
        $customerLoggedIn = $this->customerAuth->check($shopId);

        return [
            'currency'           => $currency,
            'currency_symbol'    => $currencySymbol,
            'has_payments'       => $hasPayments,
            'shop_theme'         => $shop['shop_theme'] ?? 'classic',
            'palette_css'        => $paletteCss,
            'product_image_fit'  => $shop['product_image_fit'] ?? 'cover',
            'customer_logged_in' => $customerLoggedIn,
            'customer_name'      => $customerLoggedIn ? $this->customerAuth->customerName() : null,
        ];
    }

    private function prepareProducts(array $products, array $shop): array
    {
        foreach ($products as &$p) {
            $p['variations_data'] = self::filterVariations($p['variations'] ?? null);
            $p = Hooks::applyFilter('shop.product_data', $p, $shop);
        }
        unset($p);

        return Hooks::applyFilter('shop.products', $products, $shop);
    }

    private function renderProductCardsHtml(array $products, string $currencySymbol): string
    {
        $html = '';
        foreach ($products as $product) {
            $cardHtml = $this->view->renderFragment('partials/shop/product_card.tpl', [
                'product'         => $product,
                'currency_symbol' => $currencySymbol,
            ]);
            $html .= Hooks::applyFilter('shop.product_card_html', $cardHtml, $product);
        }
        return $html;
    }

    private function resolveCategoryWithChildren(int $shopId, int $categoryId): string
    {
        $allCategories = $this->categoryModel->findByUser($shopId);
        $ids = [$categoryId];
        foreach ($allCategories as $cat) {
            if ((int) ($cat['parent_id'] ?? 0) === $categoryId) {
                $ids[] = (int) $cat['id'];
            }
        }
        return implode(',', $ids);
    }

    private function activateTheme(array $shop): void
    {
        $themeSlug = $shop['shop_theme'] ?? 'classic';
        $this->theme->activate($themeSlug, $this->view);
        $this->view->assignThemeVars(
            $this->theme->getStyleUrls(),
            $this->theme->getScriptUrls(),
            $this->theme->getFontLink()
        );

        // Resolve theme options (merge saved values with theme defaults)
        $shopId = (int) $shop['id'];
        $themeOptions = $this->theme->resolveOptions($shopId, $this->themeOptionModel);

        // Free plan users always show "Powered by TinyShop"
        $plan = $this->planGuard->getUserPlan($shopId);
        if (((float) ($plan['price_monthly'] ?? 0)) === 0.0) {
            $themeOptions['show_powered_by'] = true;
        }

        $this->view->assign('theme_options', $themeOptions);
    }

    private function resolveCurrencySymbol(string $currency): string
    {
        $symbols = Hooks::applyFilter('shop.currency_symbols', self::CURRENCY_SYMBOLS);
        return $symbols[$currency] ?? $currency . ' ';
    }

    private function resolveHasPayments(array $shop): bool
    {
        $hasStripe = !empty($shop['stripe_enabled']) && !empty($shop['stripe_public_key']) && !empty($shop['stripe_secret_key']);
        $hasPaypal = !empty($shop['paypal_enabled']) && !empty($shop['paypal_client_id']) && !empty($shop['paypal_secret']);
        $hasMpesa  = !empty($shop['mpesa_enabled']) && !empty($shop['mpesa_shortcode']) && !empty($shop['mpesa_consumer_key']) && !empty($shop['mpesa_consumer_secret']) && !empty($shop['mpesa_passkey']);
        $hasCod = !empty($shop['cod_enabled']);
        $has = $hasStripe || $hasPaypal || $hasMpesa || $hasCod;

        return (bool) Hooks::applyFilter('shop.has_payments', $has, $shop);
    }

    private function applyVerificationCodes(array &$viewData, array $shop): void
    {
        if (!empty($shop['google_verification'])) {
            $viewData['google_verification'] = $shop['google_verification'];
        }
        if (!empty($shop['bing_verification'])) {
            $viewData['bing_verification'] = $shop['bing_verification'];
        }
    }

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

        // Extract and clean referrer domain (exclude self-referrals)
        $refererDomain = ShopView::extractRefererDomain($serverParams['HTTP_REFERER'] ?? '');
        $requestHost = strtolower($serverParams['HTTP_HOST'] ?? '');
        if ($requestHost !== '' && str_starts_with($requestHost, 'www.')) {
            $requestHost = substr($requestHost, 4);
        }
        if ($refererDomain !== null && $refererDomain === $requestHost) {
            $refererDomain = null;
        }

        // Extract UTM source from query params
        $queryParams = $request->getQueryParams();
        $utmSource = null;
        if (!empty($queryParams['utm_source'])) {
            $raw = strtolower(trim($queryParams['utm_source']));
            $raw = preg_replace('/[^a-z0-9_\-]/', '', $raw);
            $utmSource = $raw !== '' ? mb_substr($raw, 0, 50) : null;
        }

        $cookies = $request->getCookieParams();
        [$visitorToken, $isNewToken] = ShopView::resolveVisitorToken($cookies[ShopView::COOKIE_NAME] ?? '');
        $visitorUserId = $this->auth->userId();
        $this->shopViewModel->log($shopId, $productId, $visitorToken, $ip, $ua, $visitorUserId, $refererDomain, $utmSource);

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
