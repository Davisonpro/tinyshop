<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\ProductImage;
use TinyShop\Models\Category;
use TinyShop\Models\HeroSlide;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\ShopView;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Services\Auth;
use TinyShop\Services\View;
use TinyShop\Services\Theme;
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

    private const VISITOR_COOKIE_MAX_AGE = 365 * 24 * 60 * 60;

    private const PALETTES = [
        'default'    => ['primary' => '#222222', 'bar' => '#222222', 'bar_text' => '#ffffff', 'accent' => '#222222'],
        'ocean'      => ['primary' => '#0F2B46', 'bar' => '#0F2B46', 'bar_text' => '#ffffff', 'accent' => '#E07A5F'],
        'forest'     => ['primary' => '#1B4332', 'bar' => '#1B4332', 'bar_text' => '#ffffff', 'accent' => '#D4A373'],
        'sunset'     => ['primary' => '#5C1A0A', 'bar' => '#5C1A0A', 'bar_text' => '#ffffff', 'accent' => '#457B9D'],
        'lavender'   => ['primary' => '#2D2457', 'bar' => '#2D2457', 'bar_text' => '#ffffff', 'accent' => '#F2CC8F'],
        'cherry'     => ['primary' => '#7B1E34', 'bar' => '#7B1E34', 'bar_text' => '#ffffff', 'accent' => '#C9534A'],
        'sage'       => ['primary' => '#5A7247', 'bar' => '#5A7247', 'bar_text' => '#ffffff', 'accent' => '#8B6F4E'],
        'midnight'   => ['primary' => '#151B2B', 'bar' => '#151B2B', 'bar_text' => '#ffffff', 'accent' => '#4A7CFF'],
        'mocha'      => ['primary' => '#3E2723', 'bar' => '#3E2723', 'bar_text' => '#ffffff', 'accent' => '#A1887F'],
        'blush'      => ['primary' => '#4A3040', 'bar' => '#4A3040', 'bar_text' => '#ffffff', 'accent' => '#D4889E'],
    ];

    public function __construct(
        private readonly View $view,
        private readonly Theme $theme,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly ProductImage $productImageModel,
        private readonly Category $categoryModel,
        private readonly HeroSlide $heroSlideModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly ShopView $shopViewModel,
        private readonly Auth $auth
    ) {}

    // ── Page Handlers ─────────────────────────────────────────

    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->renderShop($request, $response, $args);
    }

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
        $metaDesc  = $product['meta_description'] ?: ($product['description'] ? mb_substr(strip_tags($product['description']), 0, 160) : '');

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        $viewData = [
            'page_title'       => $pageTitle,
            'meta_description' => $metaDesc,
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

        return $this->view->render($response, 'pages/shop_product.tpl', $viewData);
    }

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
            'shop'             => $shop,
            'categories'       => $categories,
            ...$ctx,
        ];

        $viewData = Hooks::applyFilter('collections.view_data', $viewData, $shop);
        Hooks::doAction('collections.before_render', $shop);

        return $this->view->render($response, 'pages/collections.tpl', $viewData);
    }

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

        $allCategories = $this->categoryModel->findByUser($shopId);
        $subcategories = array_values(array_filter(
            $allCategories,
            static fn(array $c): bool => (int) ($c['parent_id'] ?? 0) === $catId
        ));

        $products = $this->productModel->findActiveByUserPaginated($shopId, self::PRODUCTS_PER_PAGE, 0, null, $categoryIds);
        $totalProducts = $this->productModel->countActiveByUser($shopId, null, $categoryIds);
        $products = $this->prepareProducts($products, $shop);

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);
        $shopName = $shop['store_name'] ?? '';

        $viewData = [
            'page_title'       => $category['name'] . ' — ' . $shopName,
            'meta_description' => 'Browse ' . $category['name'] . ' from ' . $shopName,
            'shop'             => $shop,
            'category'         => $category,
            'subcategories'    => $subcategories,
            'products'         => $products,
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
            ...$ctx,
        ];

        $viewData = Hooks::applyFilter('collection.view_data', $viewData, $shop, $category);
        Hooks::doAction('collection.before_render', $shop, $category);

        return $this->view->render($response, 'pages/collection.tpl', $viewData);
    }

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

        if ($query !== '') {
            $products = $this->productModel->findActiveByUserPaginated(
                $shopId, self::PRODUCTS_PER_PAGE, 0, $query
            );
            $totalProducts = $this->productModel->countActiveByUser($shopId, $query);
            $products = $this->prepareProducts($products, $shop);
        }

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);
        $shopName = $shop['store_name'] ?? '';

        $viewData = [
            'page_title'       => ($query !== '' ? 'Search: ' . $query : 'Search') . ' — ' . $shopName,
            'meta_description' => 'Search results for "' . $query . '" from ' . $shopName,
            'shop'             => $shop,
            'search_query'     => $query,
            'products'         => $products,
            'total_products'   => $totalProducts,
            'products_limit'   => self::PRODUCTS_PER_PAGE,
            ...$ctx,
        ];

        return $this->view->render($response, 'pages/search_results.tpl', $viewData);
    }

    // ── API Handlers ──────────────────────────────────────────

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

    public function orderTracking(Request $request, Response $response, array $args): Response
    {
        $shop = $this->resolveShop($args);
        if (!$shop) {
            return $this->render404($response, 'Shop Not Found');
        }

        $this->activateTheme($shop);
        $ctx = $this->buildShopContext($shop);

        return $this->view->render($response, 'pages/order_tracking.tpl', [
            'page_title' => 'Track Your Order',
            'shop'       => $shop,
            ...$ctx,
        ]);
    }

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

        $heroSlides = $this->heroSlideModel->findByUser($shopId);
        $heroSlides = array_values(array_filter($heroSlides, static fn(array $s): bool => (bool) $s['is_active']));
        $heroSlides = Hooks::applyFilter('shop.hero_slides', $heroSlides, $shop);

        $saleProducts = array_values(array_filter($products, static fn(array $p): bool =>
            !empty($p['compare_price']) && $p['compare_price'] > $p['price'] && !$p['is_sold']
        ));
        $featuredProducts = array_values(array_filter($products, static fn(array $p): bool =>
            !empty($p['is_featured']) && !$p['is_sold']
        ));

        $viewData = [
            'page_title'         => $pageTitle,
            'meta_description'   => $metaDesc,
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
            'hero_slides'        => $heroSlides,
            'sale_products'      => $saleProducts,
            'featured_products'  => $featuredProducts,
            ...$ctx,
        ];

        $this->applyVerificationCodes($viewData, $shop);
        $viewData = Hooks::applyFilter('shop.view_data', $viewData, $shop);
        Hooks::doAction('shop.before_render', $shop);

        return $this->view->render($response, 'pages/shop.tpl', $viewData);
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
            'pages/shop_404.tpl',
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

        return [
            'currency'          => $currency,
            'currency_symbol'   => $currencySymbol,
            'has_payments'      => $hasPayments,
            'shop_theme'        => $shop['shop_theme'] ?? 'classic',
            'palette_css'       => $paletteCss,
            'product_image_fit' => $shop['product_image_fit'] ?? 'cover',
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
            $cardHtml = $this->view->renderFragment('partials/product_card.tpl', [
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
