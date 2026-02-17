<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Enums\UserRole;
use TinyShop\Models\Category;
use TinyShop\Models\HelpArticle;
use TinyShop\Models\HelpCategory;
use TinyShop\Models\Page;
use TinyShop\Models\ProductImage;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\Order;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Models\Subscription;
use TinyShop\Services\Auth;
use TinyShop\Services\Importers\HttpClient;
use TinyShop\Services\Importers\ImporterFactory;
use TinyShop\Services\Mailer;
use TinyShop\Services\Upload;
use TinyShop\Services\Validation;
use TinyShop\Services\View;

final class AdminController
{
    use JsonResponder;

    private const SELLERS_PER_PAGE = 25;

    private const ALLOWED_SETTINGS = [
        'site_name', 'base_domain', 'support_email',
        'site_logo', 'site_favicon',
        'maintenance_mode', 'default_currency',
        'max_products_per_seller', 'allow_registration',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'smtp_encryption', 'mail_from_email', 'mail_from_name',
        'google_verification', 'bing_verification',
        'google_analytics_id', 'facebook_pixel_id',
        'robots_extra',
        'platform_stripe_public_key', 'platform_stripe_secret_key', 'platform_stripe_mode',
        'platform_paypal_client_id', 'platform_paypal_secret', 'platform_paypal_mode',
        'platform_mpesa_shortcode', 'platform_mpesa_consumer_key',
        'platform_mpesa_consumer_secret', 'platform_mpesa_passkey', 'platform_mpesa_mode',
        's3_bucket', 's3_region', 's3_access_key', 's3_secret_key', 's3_endpoint', 's3_cdn_url',
    ];

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly Order $orderModel,
        private readonly Plan $planModel,
        private readonly Setting $settingModel,
        private readonly ShopView $shopViewModel,
        private readonly Subscription $subscriptionModel,
        private readonly HelpCategory $helpCategoryModel,
        private readonly HelpArticle $helpArticleModel,
        private readonly Page $pageModel,
        private readonly Category $categoryModel,
        private readonly ProductImage $productImageModel,
        private readonly ImporterFactory $importerFactory,
        private readonly HttpClient $httpClient,
        private readonly Mailer $mailer,
        private readonly Upload $upload,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger
    ) {}

    // ── Pages ──

    public function dashboard(Request $request, Response $response): Response
    {
        $orderStats = $this->orderModel->getPlatformStats();

        return $this->view->render($response, 'pages/admin_dashboard.tpl', [
            'page_title'     => 'Platform Overview',
            'active_page'    => 'dashboard',
            'total_sellers'  => $this->userModel->countByRole(UserRole::Seller->value),
            'active_sellers' => $this->userModel->countActive(),
            'total_products' => $this->productModel->countAll(),
            'total_orders'   => $orderStats['total'],
            'new_signups'    => $this->userModel->recentSignups(7),
        ]);
    }

    private const ORDERS_PER_PAGE = 50;
    private const PRODUCTS_PER_PAGE = 50;

    public function orders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = trim($params['status'] ?? '');
        $offset = ($page - 1) * self::ORDERS_PER_PAGE;

        $orders = $this->orderModel->findAllAdmin(self::ORDERS_PER_PAGE, $offset, $status);
        $total  = $this->orderModel->countAllAdmin($status);

        return $this->view->render($response, 'pages/admin_orders.tpl', [
            'page_title'   => 'All Orders',
            'active_page'  => 'orders',
            'orders'       => $orders,
            'total'        => $total,
            'status'       => $status,
            'current_page' => $page,
            'total_pages'  => max(1, (int) ceil($total / self::ORDERS_PER_PAGE)),
        ]);
    }

    public function products(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $search = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::PRODUCTS_PER_PAGE;

        $products = $this->productModel->findAllAdmin(self::PRODUCTS_PER_PAGE, $offset, $search);
        $total    = $this->productModel->countAllAdmin($search);

        return $this->view->render($response, 'pages/admin_products.tpl', [
            'page_title'   => 'All Products',
            'active_page'  => 'products',
            'products'     => $products,
            'total'        => $total,
            'search'       => $search,
            'current_page' => $page,
            'total_pages'  => max(1, (int) ceil($total / self::PRODUCTS_PER_PAGE)),
        ]);
    }

    public function sellers(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $search = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::SELLERS_PER_PAGE;

        $sellers = $this->userModel->findSellers(self::SELLERS_PER_PAGE, $offset, $search);
        $total   = $this->userModel->countSellers($search);

        return $this->view->render($response, 'pages/admin_sellers.tpl', [
            'page_title'   => 'Sellers',
            'active_page'  => 'sellers',
            'sellers'      => $sellers,
            'total'        => $total,
            'search'       => $search,
            'current_page' => $page,
            'total_pages'  => max(1, (int) ceil($total / self::SELLERS_PER_PAGE)),
        ]);
    }

    public function sellerDetail(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $seller = $this->userModel->getSellerWithStats($id);

        if (!$seller) {
            return $response->withHeader('Location', '/admin/sellers')->withStatus(302);
        }

        $products = $this->productModel->findByUser($id);
        $plans = $this->planModel->findAll();

        $planExpired = false;
        if (!empty($seller['plan_expires_at'])) {
            $planExpired = strtotime($seller['plan_expires_at']) < time();
        }

        return $this->view->render($response, 'pages/admin_seller_detail.tpl', [
            'page_title'   => $seller['store_name'] ?? '',
            'active_page'  => 'sellers',
            'seller'       => $seller,
            'products'     => $products,
            'plans'        => $plans,
            'plan_expired' => $planExpired,
        ]);
    }

    public function settings(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admin_settings.tpl', [
            'page_title'  => 'Settings',
            'active_page' => 'settings',
            'settings'    => $this->settingModel->all(),
        ]);
    }

    // ── Impersonation ──

    public function impersonate(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $seller = $this->userModel->findById($id);

        if (!$seller || $seller['role'] === UserRole::Admin->value) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot impersonate this account'], 403);
        }

        $this->auth->impersonate((int) $seller['id'], $seller['store_name'] ?? '', UserRole::tryFrom($seller['role'] ?? '') ?? UserRole::Seller);

        $this->logger->info('admin.impersonate', [
            'admin_id'  => $this->auth->realAdminId(),
            'seller_id' => $seller['id'],
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true, 'redirect' => '/dashboard']);
    }

    public function stopImpersonate(Request $request, Response $response): Response
    {
        $this->auth->stopImpersonating();

        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    // ── API endpoints ──

    public function toggleSeller(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        // Prevent toggling admin accounts
        $target = $this->userModel->findById($id);
        if (!$target || ($target['role'] ?? '') === UserRole::Admin->value) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot modify admin accounts'], 403);
        }

        if (array_key_exists('is_active', $data)) {
            $active = !empty($data['is_active']);
            $this->userModel->toggleActive($id, $active);

            $this->logger->info('admin.seller_toggled', [
                'admin_id'  => $this->auth->userId(),
                'seller_id' => $id,
                'is_active' => $active,
                'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
            ]);
        }

        if (array_key_exists('is_showcased', $data)) {
            $this->userModel->update($id, ['is_showcased' => !empty($data['is_showcased']) ? 1 : 0]);
        }

        return $this->json($response, ['success' => true]);
    }

    public function updateSellerPlan(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        $target = $this->userModel->findById($id);
        if (!$target || ($target['role'] ?? '') === UserRole::Admin->value) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot modify admin accounts'], 403);
        }

        $planId = isset($data['plan_id']) ? (int) $data['plan_id'] : null;
        $planName = 'None';
        $updates = [];

        if ($planId === null || $planId === 0) {
            $updates['plan_id'] = null;
            $updates['plan_expires_at'] = null;
        } else {
            $plan = $this->planModel->findById($planId);
            if (!$plan) {
                return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
            }
            $updates['plan_id'] = $planId;
            $planName = $plan['name'];

            $expiresAt = trim($data['plan_expires_at'] ?? '');
            if ($expiresAt !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
                    return $this->json($response, ['error' => true, 'message' => 'Invalid date format'], 422);
                }
                $updates['plan_expires_at'] = $expiresAt . ' 23:59:59';
            } else {
                $updates['plan_expires_at'] = null;
            }
        }

        $this->userModel->update($id, $updates);

        $this->logger->info('admin.seller_plan_changed', [
            'admin_id'  => $this->auth->userId(),
            'seller_id' => $id,
            'plan_id'   => $updates['plan_id'],
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true, 'plan_name' => $planName]);
    }

    public function deleteSeller(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $seller = $this->userModel->findById($id);

        if (!$seller || $seller['role'] === UserRole::Admin->value) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot delete this account'], 403);
        }

        $result = $this->userModel->deleteAccount($id);

        if ($result === false) {
            return $this->json($response, ['error' => true, 'message' => 'Failed to delete seller'], 500);
        }

        // Clean up uploaded files (best-effort)
        foreach ($result as $url) {
            $this->upload->deleteFile($url);
        }

        $this->logger->info('admin.seller_deleted', [
            'admin_id'  => $this->auth->userId(),
            'seller_id' => $id,
            'email'     => $seller['email'],
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true]);
    }

    public function updateSettings(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();

        $updates = [];
        foreach (self::ALLOWED_SETTINGS as $key) {
            if (array_key_exists($key, $data)) {
                $updates[$key] = (string) $data[$key];
            }
        }

        if ($updates === []) {
            return $this->json($response, ['error' => true, 'message' => 'No valid settings provided'], 422);
        }

        $this->settingModel->setMany($updates);

        $this->logger->info('admin.settings_updated', [
            'admin_id' => $this->auth->userId(),
            'keys'     => array_keys($updates),
            'ip'       => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true]);
    }

    public function testEmail(Request $request, Response $response): Response
    {
        $adminEmail = $this->settingModel->get('support_email', '');
        if ($adminEmail === '') {
            $adminId = $this->auth->userId();
            $admin = $adminId !== null ? $this->userModel->findById($adminId) : null;
            $adminEmail = $admin['email'] ?? '';
        }

        if ($adminEmail === '') {
            return $this->json($response, ['error' => true, 'message' => 'No email address configured. Set a support email first.'], 422);
        }

        $siteName = $this->settingModel->get('site_name', 'TinyShop');
        $html = '<div style="font-family:-apple-system,BlinkMacSystemFont,sans-serif;max-width:480px;margin:0 auto;padding:32px 24px">'
            . '<h2 style="font-size:1.25rem;margin:0 0 12px">Test Email</h2>'
            . '<p style="color:#666;font-size:0.9375rem;line-height:1.6;margin:0 0 20px">Your SMTP settings are working correctly.</p>'
            . '<p style="color:#999;font-size:0.8125rem;margin:0">Sent at ' . date('Y-m-d H:i:s') . '</p>'
            . '</div>';

        $error = $this->mailer->send($adminEmail, $siteName . ' - Test Email', $html);

        if ($error !== null) {
            return $this->json($response, ['error' => true, 'message' => 'SMTP Error: ' . $error], 500);
        }

        return $this->json($response, ['success' => true, 'message' => 'Test email sent to ' . $adminEmail]);
    }

    public function testS3(Request $request, Response $response): Response
    {
        $bucket    = trim($this->settingModel->get('s3_bucket', '') ?? '');
        $region    = trim($this->settingModel->get('s3_region', '') ?? '') ?: 'us-east-1';
        $accessKey = trim($this->settingModel->get('s3_access_key', '') ?? '');
        $secretKey = trim($this->settingModel->get('s3_secret_key', '') ?? '');

        if ($bucket === '' || $accessKey === '' || $secretKey === '') {
            return $this->json($response, ['error' => true, 'message' => 'Please save your S3 settings first'], 422);
        }

        $config = [
            'version'     => 'latest',
            'region'      => $region,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ];

        $endpoint = trim($this->settingModel->get('s3_endpoint', '') ?? '');
        if ($endpoint !== '') {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        try {
            $client = new \Aws\S3\S3Client($config);

            $testKey = '_tinyshop_test_' . bin2hex(random_bytes(8)) . '.txt';
            $client->putObject([
                'Bucket'      => $bucket,
                'Key'         => $testKey,
                'Body'        => 'TinyShop S3 connection test',
                'ContentType' => 'text/plain',
                'ACL'         => 'public-read',
            ]);
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $testKey,
            ]);

            return $this->json($response, ['success' => true, 'message' => 'Connected to bucket "' . $bucket . '" successfully']);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'InvalidAccessKeyId')) {
                $msg = 'The access key is not valid. Please check and try again.';
            } elseif (str_contains($msg, 'SignatureDoesNotMatch')) {
                $msg = 'The secret key does not match. Please check and try again.';
            } elseif (str_contains($msg, 'NoSuchBucket')) {
                $msg = 'Bucket "' . $bucket . '" was not found. Please check the bucket name.';
            } elseif (str_contains($msg, 'AccessDenied')) {
                $msg = 'Access denied. Make sure your credentials have permission to upload to this bucket.';
            }

            return $this->json($response, ['error' => true, 'message' => 'S3 Error: ' . $msg], 500);
        }
    }

    public function pingSitemap(Request $request, Response $response): Response
    {
        $baseDomain = $this->settingModel->get('base_domain', '');
        if ($baseDomain === '') {
            return $this->json($response, ['error' => true, 'message' => 'Base domain not configured'], 422);
        }

        $sitemapUrl = 'https://' . $baseDomain . '/sitemap.xml';
        $results = [];

        // Ping Google
        $results['google'] = $this->httpPing(
            'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl)
        );

        // Ping Bing / IndexNow
        $results['bing'] = $this->httpPing(
            'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl)
        );

        $messages = [];
        $messages[] = $results['google'] ? 'Google notified' : 'Google ping failed';
        $messages[] = $results['bing'] ? 'Bing notified' : 'Bing ping failed';

        $this->logger->info('admin.sitemap_ping', [
            'admin_id' => $this->auth->userId(),
            'results'  => $results,
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => implode('. ', $messages),
        ]);
    }

    // ── Plan management ──

    public function plans(Request $request, Response $response): Response
    {
        $plans = $this->planModel->findAllAdmin();

        // Attach subscriber counts
        foreach ($plans as &$plan) {
            $plan['subscriber_count'] = $this->planModel->countSubscribers((int) $plan['id']);
        }
        unset($plan);

        // Expire overdue subscriptions on admin load
        $this->subscriptionModel->expireOverdue();

        return $this->view->render($response, 'pages/admin_plans.tpl', [
            'page_title'  => 'Plans',
            'active_page' => 'plans',
            'plans'       => $plans,
        ]);
    }

    public function listPlans(Request $request, Response $response): Response
    {
        $plans = $this->planModel->findAllAdmin();
        foreach ($plans as &$plan) {
            $plan['subscriber_count'] = $this->planModel->countSubscribers((int) $plan['id']);
        }
        unset($plan);

        return $this->json($response, ['success' => true, 'plans' => $plans]);
    }

    public function createPlan(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Plan name is required'], 422);
        }

        $slug = $this->validation->slug($name);
        if ($this->planModel->slugExists($slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A plan with a similar name already exists'], 422);
        }

        $allowedThemes = null;
        if (isset($data['allowed_themes']) && $data['allowed_themes'] !== 'all') {
            $themes = is_array($data['allowed_themes']) ? $data['allowed_themes'] : [$data['allowed_themes']];
            $allowedThemes = json_encode(array_values(array_filter($themes)));
        }

        // Parse features list
        $features = null;
        if (isset($data['features']) && is_array($data['features'])) {
            $features = json_encode(array_values(array_filter(
                array_map('trim', $data['features']),
                fn($f) => $f !== ''
            )));
        }

        $id = $this->planModel->create([
            'name'                   => $name,
            'slug'                   => $slug,
            'description'            => trim($data['description'] ?? ''),
            'price_monthly'          => (float) ($data['price_monthly'] ?? 0),
            'price_yearly'           => (float) ($data['price_yearly'] ?? 0),
            'currency'               => $data['currency'] ?? 'KES',
            'max_products'           => isset($data['max_products']) && $data['max_products'] !== '' ? (int) $data['max_products'] : null,
            'allowed_themes'         => $allowedThemes,
            'custom_domain_allowed'  => !empty($data['custom_domain_allowed']) ? 1 : 0,
            'coupons_allowed'        => !empty($data['coupons_allowed']) ? 1 : 0,
            'features'               => $features,
            'cta_text'               => trim($data['cta_text'] ?? '') ?: null,
            'badge_text'             => trim($data['badge_text'] ?? '') ?: null,
            'is_featured'            => !empty($data['is_featured']) ? 1 : 0,
            'is_default'             => !empty($data['is_default']) ? 1 : 0,
            'is_active'              => isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1,
            'sort_order'             => (int) ($data['sort_order'] ?? 0),
        ]);

        $plan = $this->planModel->findById($id);

        $this->logger->info('admin.plan_created', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
            'name'     => $name,
        ]);

        return $this->json($response, ['success' => true, 'plan' => $plan], 201);
    }

    public function updatePlan(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $plan = $this->planModel->findById($id);

        if (!$plan) {
            return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json($response, ['error' => true, 'message' => 'Plan name is required'], 422);
            }
            $updates['name'] = $name;
            $newSlug = $this->validation->slug($name);
            if ($this->planModel->slugExists($newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A plan with a similar name already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['description'])) $updates['description'] = trim($data['description']);
        if (isset($data['price_monthly'])) $updates['price_monthly'] = (float) $data['price_monthly'];
        if (isset($data['price_yearly'])) $updates['price_yearly'] = (float) $data['price_yearly'];
        if (isset($data['currency'])) $updates['currency'] = $data['currency'];

        if (array_key_exists('max_products', $data)) {
            $updates['max_products'] = ($data['max_products'] !== '' && $data['max_products'] !== null) ? (int) $data['max_products'] : null;
        }

        if (isset($data['allowed_themes'])) {
            if ($data['allowed_themes'] === 'all' || $data['allowed_themes'] === null) {
                $updates['allowed_themes'] = null;
            } else {
                $themes = is_array($data['allowed_themes']) ? $data['allowed_themes'] : [$data['allowed_themes']];
                $updates['allowed_themes'] = json_encode(array_values(array_filter($themes)));
            }
        }

        if (isset($data['custom_domain_allowed'])) $updates['custom_domain_allowed'] = !empty($data['custom_domain_allowed']) ? 1 : 0;
        if (isset($data['coupons_allowed'])) $updates['coupons_allowed'] = !empty($data['coupons_allowed']) ? 1 : 0;

        if (isset($data['features'])) {
            if (is_array($data['features'])) {
                $updates['features'] = json_encode(array_values(array_filter(
                    array_map('trim', $data['features']),
                    fn($f) => $f !== ''
                )));
            } else {
                $updates['features'] = null;
            }
        }
        if (isset($data['cta_text'])) $updates['cta_text'] = trim($data['cta_text']) ?: null;
        if (isset($data['badge_text'])) $updates['badge_text'] = trim($data['badge_text']) ?: null;
        if (isset($data['is_featured'])) $updates['is_featured'] = !empty($data['is_featured']) ? 1 : 0;

        if (isset($data['is_default'])) $updates['is_default'] = !empty($data['is_default']) ? 1 : 0;
        if (isset($data['is_active'])) $updates['is_active'] = (int) (bool) $data['is_active'];
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];

        if (!empty($updates)) {
            $this->planModel->update($id, $updates);
        }

        $plan = $this->planModel->findById($id);

        $this->logger->info('admin.plan_updated', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'plan' => $plan]);
    }

    public function deletePlan(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $plan = $this->planModel->findById($id);

        if (!$plan) {
            return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
        }

        if (!empty($plan['is_default'])) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot delete the default plan'], 422);
        }

        if (!$this->planModel->delete($id)) {
            return $this->json($response, ['error' => true, 'message' => 'Cannot delete a plan with active subscribers'], 422);
        }

        $this->logger->info('admin.plan_deleted', [
            'admin_id' => $this->auth->userId(),
            'plan_id'  => $id,
            'name'     => $plan['name'],
        ]);

        return $this->json($response, ['success' => true]);
    }

    // ── Help center management ──

    public function help(Request $request, Response $response): Response
    {
        $categories = $this->helpCategoryModel->findAllAdmin();
        $articles = $this->helpArticleModel->findAll();

        return $this->view->render($response, 'pages/admin_help.tpl', [
            'page_title'  => 'Help Center',
            'active_page' => 'help',
            'categories'  => $categories,
            'articles'    => $articles,
        ]);
    }

    public function helpArticleForm(Request $request, Response $response, array $args = []): Response
    {
        $categories = $this->helpCategoryModel->findAllAdmin();
        $article = null;
        $isEdit = false;

        if (!empty($args['id'])) {
            $article = $this->helpArticleModel->findById((int) $args['id']);
            if (!$article) {
                return $response->withHeader('Location', '/admin/help')->withStatus(302);
            }
            $isEdit = true;
        }

        return $this->view->render($response, 'pages/admin_help_article.tpl', [
            'page_title'  => $isEdit ? 'Edit Article' : 'New Article',
            'active_page' => 'help',
            'categories'  => $categories,
            'article'     => $article,
            'is_edit'     => $isEdit,
        ]);
    }

    public function listHelpCategories(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'categories' => $this->helpCategoryModel->findAllAdmin()]);
    }

    public function createHelpCategory(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            return $this->json($response, ['error' => true, 'message' => 'Category name is required'], 422);
        }

        $slug = $this->validation->slug($name);
        if ($this->helpCategoryModel->slugExists($slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A category with a similar name already exists'], 422);
        }

        $id = $this->helpCategoryModel->create([
            'name'        => $name,
            'slug'        => $slug,
            'icon'        => trim($data['icon'] ?? '') ?: 'fa-circle-question',
            'description' => trim($data['description'] ?? ''),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ]);

        $this->logger->info('admin.help_category_created', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'category' => $this->helpCategoryModel->findById($id)], 201);
    }

    public function updateHelpCategory(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = $this->helpCategoryModel->findById($id);

        if (!$category) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                return $this->json($response, ['error' => true, 'message' => 'Category name is required'], 422);
            }
            $updates['name'] = $name;
            $newSlug = $this->validation->slug($name);
            if ($this->helpCategoryModel->slugExists($newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A category with a similar name already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['icon'])) $updates['icon'] = trim($data['icon']) ?: 'fa-circle-question';
        if (isset($data['description'])) $updates['description'] = trim($data['description']);
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];

        if (!empty($updates)) {
            $this->helpCategoryModel->update($id, $updates);
        }

        $this->logger->info('admin.help_category_updated', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'category' => $this->helpCategoryModel->findById($id)]);
    }

    public function deleteHelpCategory(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $category = $this->helpCategoryModel->findById($id);

        if (!$category) {
            return $this->json($response, ['error' => true, 'message' => 'Category not found'], 404);
        }

        if (!$this->helpCategoryModel->delete($id)) {
            return $this->json($response, ['error' => true, 'message' => 'Remove all articles from this category first'], 422);
        }

        $this->logger->info('admin.help_category_deleted', [
            'admin_id'    => $this->auth->userId(),
            'category_id' => $id,
            'name'        => $category['name'],
        ]);

        return $this->json($response, ['success' => true]);
    }

    public function listHelpArticles(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'articles' => $this->helpArticleModel->findAll()]);
    }

    public function createHelpArticle(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $title = trim($data['title'] ?? '');
        $categoryId = (int) ($data['category_id'] ?? 0);

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Article title is required'], 422);
        }

        if (!$this->helpCategoryModel->findById($categoryId)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid category'], 422);
        }

        $slug = $this->validation->slug($title);
        if ($this->helpArticleModel->slugExists($slug)) {
            return $this->json($response, ['error' => true, 'message' => 'An article with a similar title already exists'], 422);
        }

        $id = $this->helpArticleModel->create([
            'category_id'  => $categoryId,
            'title'        => $title,
            'slug'         => $slug,
            'summary'      => trim($data['summary'] ?? ''),
            'content'      => $data['content'] ?? '',
            'keywords'     => trim($data['keywords'] ?? ''),
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
            'is_published' => isset($data['is_published']) ? (int) (bool) $data['is_published'] : 1,
        ]);

        $this->logger->info('admin.help_article_created', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'article' => $this->helpArticleModel->findById($id)], 201);
    }

    public function updateHelpArticle(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $article = $this->helpArticleModel->findById($id);

        if (!$article) {
            return $this->json($response, ['error' => true, 'message' => 'Article not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if ($title === '') {
                return $this->json($response, ['error' => true, 'message' => 'Article title is required'], 422);
            }
            $updates['title'] = $title;
            $newSlug = $this->validation->slug($title);
            if ($this->helpArticleModel->slugExists($newSlug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'An article with a similar title already exists'], 422);
            }
            $updates['slug'] = $newSlug;
        }

        if (isset($data['category_id'])) {
            $catId = (int) $data['category_id'];
            if (!$this->helpCategoryModel->findById($catId)) {
                return $this->json($response, ['error' => true, 'message' => 'Invalid category'], 422);
            }
            $updates['category_id'] = $catId;
        }

        if (isset($data['summary'])) $updates['summary'] = trim($data['summary']);
        if (isset($data['content'])) $updates['content'] = $data['content'];
        if (isset($data['keywords'])) $updates['keywords'] = trim($data['keywords']);
        if (isset($data['sort_order'])) $updates['sort_order'] = (int) $data['sort_order'];
        if (isset($data['is_published'])) $updates['is_published'] = (int) (bool) $data['is_published'];

        if (!empty($updates)) {
            $this->helpArticleModel->update($id, $updates);
        }

        $this->logger->info('admin.help_article_updated', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
        ]);

        return $this->json($response, ['success' => true, 'article' => $this->helpArticleModel->findById($id)]);
    }

    public function deleteHelpArticle(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $article = $this->helpArticleModel->findById($id);

        if (!$article) {
            return $this->json($response, ['error' => true, 'message' => 'Article not found'], 404);
        }

        $this->helpArticleModel->delete($id);

        $this->logger->info('admin.help_article_deleted', [
            'admin_id'   => $this->auth->userId(),
            'article_id' => $id,
            'title'      => $article['title'],
        ]);

        return $this->json($response, ['success' => true]);
    }

    // ── Pages (dynamic content pages) ──

    public function pages(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admin_pages.tpl', [
            'page_title'  => 'Pages',
            'active_page' => 'pages',
            'pages_list'  => $this->pageModel->findAll(),
        ]);
    }

    public function pageForm(Request $request, Response $response, array $args = []): Response
    {
        $page = null;
        $isEdit = false;

        if (!empty($args['id'])) {
            $page = $this->pageModel->findById((int) $args['id']);
            if (!$page) {
                return $response->withHeader('Location', '/admin/pages')->withStatus(302);
            }
            $isEdit = true;
        }

        return $this->view->render($response, 'pages/admin_page_form.tpl', [
            'page_title'  => $isEdit ? 'Edit Page' : 'New Page',
            'active_page' => 'pages',
            'page_data'   => $page,
            'is_edit'     => $isEdit,
        ]);
    }

    public function listPages(Request $request, Response $response): Response
    {
        return $this->json($response, ['success' => true, 'pages' => $this->pageModel->findAll()]);
    }

    public function createPage(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $title = trim($data['title'] ?? '');

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Page title is required'], 422);
        }

        $slug = trim($data['slug'] ?? '');
        if ($slug === '') {
            $slug = $this->validation->slug($title);
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->json($response, ['error' => true, 'message' => 'Permalink can only contain lowercase letters, numbers, and hyphens'], 422);
        }
        if ($this->pageModel->slugExists($slug)) {
            return $this->json($response, ['error' => true, 'message' => 'A page with this permalink already exists'], 422);
        }

        $id = $this->pageModel->create([
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $data['content'] ?? '',
            'meta_description' => trim($data['meta_description'] ?? ''),
            'is_published'     => isset($data['is_published']) ? (int) (bool) $data['is_published'] : 1,
        ]);

        $this->logger->info('admin.page_created', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'page' => $this->pageModel->findById($id)], 201);
    }

    public function updatePage(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $page = $this->pageModel->findById($id);

        if (!$page) {
            return $this->json($response, ['error' => true, 'message' => 'Page not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if ($title === '') {
                return $this->json($response, ['error' => true, 'message' => 'Page title is required'], 422);
            }
            $updates['title'] = $title;
        }

        if (isset($data['slug'])) {
            $slug = trim($data['slug']);
            if ($slug === '') {
                $slug = $this->validation->slug($updates['title'] ?? $page['title']);
            }
            if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                return $this->json($response, ['error' => true, 'message' => 'Permalink can only contain lowercase letters, numbers, and hyphens'], 422);
            }
            if ($this->pageModel->slugExists($slug, $id)) {
                return $this->json($response, ['error' => true, 'message' => 'A page with this permalink already exists'], 422);
            }
            $updates['slug'] = $slug;
        }

        if (isset($data['content'])) {
            $updates['content'] = $data['content'];
        }
        if (isset($data['meta_description'])) {
            $updates['meta_description'] = trim($data['meta_description']);
        }
        if (isset($data['is_published'])) {
            $updates['is_published'] = (int) (bool) $data['is_published'];
        }

        if (!empty($updates)) {
            $this->pageModel->update($id, $updates);
        }

        $this->logger->info('admin.page_updated', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
        ]);

        return $this->json($response, ['success' => true, 'page' => $this->pageModel->findById($id)]);
    }

    public function deletePage(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $page = $this->pageModel->findById($id);

        if (!$page) {
            return $this->json($response, ['error' => true, 'message' => 'Page not found'], 404);
        }

        $this->pageModel->delete($id);

        $this->logger->info('admin.page_deleted', [
            'admin_id' => $this->auth->userId(),
            'page_id'  => $id,
            'title'    => $page['title'],
        ]);

        return $this->json($response, ['success' => true]);
    }

    // ── Product Import ──

    public function import(Request $request, Response $response): Response
    {
        $sellers = $this->userModel->findSellers(500, 0);

        return $this->view->render($response, 'pages/admin_import.tpl', [
            'page_title'  => 'Import Product',
            'active_page' => 'import',
            'sellers'     => $sellers,
        ]);
    }

    public function fetchImport(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $url  = trim($data['url'] ?? '');

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json($response, ['error' => true, 'message' => 'Please enter a valid product URL'], 422);
        }

        try {
            $importer = $this->importerFactory->resolve($url);
            $result   = $importer->fetch($url);
            return $this->json($response, ['success' => true, 'product' => $result->toArray()]);
        } catch (\Throwable $e) {
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 422);
        }
    }

    public function saveImport(Request $request, Response $response): Response
    {
        $data     = (array) $request->getParsedBody();
        $sellerId = (int) ($data['seller_id'] ?? 0);
        $title    = trim($data['title'] ?? '');
        $price    = (float) ($data['price'] ?? 0);

        if ($sellerId === 0) {
            return $this->json($response, ['error' => true, 'message' => 'Please select a seller'], 422);
        }

        $seller = $this->userModel->findById($sellerId);
        if (!$seller) {
            return $this->json($response, ['error' => true, 'message' => 'Seller not found'], 404);
        }

        if ($title === '') {
            return $this->json($response, ['error' => true, 'message' => 'Product title is required'], 422);
        }

        // Resolve / create category hierarchy — match existing before creating
        $categoryId = null;
        $categories = $data['categories'] ?? [];
        if (!empty($categories) && is_array($categories)) {
            $parentId = null;
            foreach ($categories as $catName) {
                $catName = trim($catName);
                if ($catName === '') {
                    continue;
                }

                // Try matching by name (case-insensitive) + parent, then by slug, then globally
                $existing = $this->categoryModel->findByUserNameAndParent($sellerId, $catName, $parentId);
                if ($existing) {
                    $categoryId = (int) $existing['id'];
                } else {
                    $categoryId = $this->categoryModel->create([
                        'user_id'   => $sellerId,
                        'parent_id' => $parentId,
                        'name'      => $catName,
                    ]);
                }
                $parentId = $categoryId;
            }
        }

        // Download and store images
        $imageUrls = [];
        $remoteImages = $data['images'] ?? [];
        if (is_array($remoteImages)) {
            foreach ($remoteImages as $imgUrl) {
                $stored = $this->downloadAndStoreImage($imgUrl);
                if ($stored !== null) {
                    $imageUrls[] = $stored;
                }
            }
        }

        $comparePrice = isset($data['compare_price']) && $data['compare_price'] !== '' && $data['compare_price'] !== null
            ? (float) $data['compare_price'] : null;

        // Convert flat WooCommerce variations to TinyShop grouped format
        // From: [{name, price, attributes: {Group: Value}}]
        // To:   [{name: "Group", options: [{value: "Value", price: N}]}]
        $variations = null;
        if (!empty($data['variations']) && is_array($data['variations'])) {
            $grouped = $this->convertToGroupedVariations($data['variations']);
            $variations = $this->validation->sanitizeVariations($grouped);
        }

        $slug = $this->validation->slug($title);
        $slug = $this->productModel->ensureUniqueSlug($sellerId, $slug);

        $productId = $this->productModel->create([
            'user_id'        => $sellerId,
            'category_id'    => $categoryId,
            'name'           => $title,
            'slug'           => $slug,
            'description'    => trim($data['description'] ?? ''),
            'price'          => $price,
            'compare_price'  => $comparePrice,
            'image_url'      => $imageUrls[0] ?? null,
            'sort_order'     => 0,
            'is_sold'        => 0,
            'stock_quantity'  => null,
            'is_featured'    => 0,
            'variations'     => $variations,
        ]);

        // Sync product images
        if (!empty($imageUrls)) {
            $this->productImageModel->sync($productId, $imageUrls);
        }

        $this->logger->info('admin.product_imported', [
            'admin_id'   => $this->auth->userId(),
            'seller_id'  => $sellerId,
            'product_id' => $productId,
            'title'      => $title,
        ]);

        return $this->json($response, [
            'success'    => true,
            'product_id' => $productId,
            'message'    => 'Product imported successfully',
        ], 201);
    }

    /**
     * Convert flat WooCommerce-style variations into TinyShop grouped format.
     *
     * Input:  [{name: "A / B", price: 100, attributes: {"Size": "A", "Color": "B"}}]
     * Output: [{name: "Size", options: [{value: "A", price: 100}]}, {name: "Color", options: [{value: "B"}]}]
     *
     * @return array[]
     */
    private function convertToGroupedVariations(array $flatVariations): array
    {
        // Collect unique values per group and track min price for each value
        $groups = [];      // groupName => [value => minPrice]
        $groupOrder = [];   // preserve attribute order

        foreach ($flatVariations as $v) {
            $attributes = $v['attributes'] ?? [];
            $price = $v['price'] ?? null;

            foreach ($attributes as $groupName => $value) {
                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = [];
                    $groupOrder[] = $groupName;
                }

                // Track the minimum price for this option value
                if (!isset($groups[$groupName][$value])) {
                    $groups[$groupName][$value] = $price;
                } elseif ($price !== null && ($groups[$groupName][$value] === null || $price < $groups[$groupName][$value])) {
                    $groups[$groupName][$value] = $price;
                }
            }
        }

        $result = [];
        foreach ($groupOrder as $name) {
            $options = [];
            foreach ($groups[$name] as $value => $price) {
                $opt = ['value' => (string) $value];
                if ($price !== null) {
                    $opt['price'] = $price;
                }
                $options[] = $opt;
            }
            $result[] = ['name' => $name, 'options' => $options];
        }

        return $result;
    }

    private function downloadAndStoreImage(string $url): ?string
    {
        try {
            $tmpPath  = $this->httpClient->download($url);
            $filename = basename(parse_url($url, PHP_URL_PATH) ?? 'image.jpg');
            return $this->upload->storeFromPath($tmpPath, $filename);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function httpPing(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'TinyShop/1.0',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 400;
    }
}
