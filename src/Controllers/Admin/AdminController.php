<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Enums\UserRole;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\Order;
use TinyShop\Models\PageView;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Services\Auth;
use TinyShop\Services\Mailer;
use TinyShop\Services\Upload;
use TinyShop\Services\View;

final class AdminController
{
    use JsonResponder;

    private const ORDERS_PER_PAGE = 50;
    private const PRODUCTS_PER_PAGE = 50;

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
        'platform_stripe_public_key', 'platform_stripe_secret_key', 'platform_stripe_mode', 'platform_stripe_enabled',
        'platform_paypal_client_id', 'platform_paypal_secret', 'platform_paypal_mode', 'platform_paypal_enabled',
        'platform_mpesa_shortcode', 'platform_mpesa_consumer_key',
        'platform_mpesa_consumer_secret', 'platform_mpesa_passkey', 'platform_mpesa_mode', 'platform_mpesa_enabled',
        'platform_pesapal_consumer_key', 'platform_pesapal_consumer_secret', 'platform_pesapal_mode', 'platform_pesapal_enabled',
        's3_bucket', 's3_region', 's3_access_key', 's3_secret_key', 's3_endpoint', 's3_cdn_url',
    ];

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly Order $orderModel,
        private readonly Setting $settingModel,
        private readonly ShopView $shopViewModel,
        private readonly PageView $pageViewModel,
        private readonly Mailer $mailer,
        private readonly Upload $upload,
        private readonly LoggerInterface $logger,
    ) {}

    public function dashboard(Request $request, Response $response): Response
    {
        $orderStats = $this->orderModel->getPlatformStats();

        return $this->view->render($response, 'pages/admin/dashboard.tpl', [
            'page_title'     => 'Platform Overview',
            'active_page'    => 'dashboard',
            'total_sellers'  => $this->userModel->countByRole(UserRole::Seller->value),
            'active_sellers' => $this->userModel->countActive(),
            'total_products' => $this->productModel->countAll(),
            'total_orders'   => $orderStats['total'],
            'new_signups'    => $this->userModel->recentSignups(7),
            'order_stats'    => $orderStats,
            'view_stats'     => $this->shopViewModel->getPlatformStats(),
            'currency'       => $this->settingModel->get('default_currency', 'KES'),
        ]);
    }

    public function analytics(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $viewDays     = max(7, min(30, (int) ($params['view_days'] ?? 14)));
        $salesDays    = max(7, min(30, (int) ($params['sales_days'] ?? 14)));
        $siteViewDays = max(7, min(30, (int) ($params['site_view_days'] ?? 14)));

        $orderStats = $this->orderModel->getPlatformStats();

        return $this->view->render($response, 'pages/admin/analytics.tpl', [
            'page_title'           => 'Analytics',
            'active_page'          => 'analytics',
            // Shop analytics
            'view_stats'           => $this->shopViewModel->getPlatformStats(),
            'daily_views'          => $this->shopViewModel->getPlatformDailyViews($viewDays),
            'daily_sales'          => $this->orderModel->getPlatformDailySales($salesDays),
            'traffic_sources'      => $this->shopViewModel->getPlatformTrafficSources(),
            'top_shops'            => $this->shopViewModel->getTopShops(5),
            'view_days'            => $viewDays,
            'sales_days'           => $salesDays,
            'order_stats'          => $orderStats,
            'currency'             => $this->settingModel->get('default_currency', 'KES'),
            // Website analytics
            'site_stats'           => $this->pageViewModel->getStats(),
            'site_daily_views'     => $this->pageViewModel->getDailyViews($siteViewDays),
            'site_traffic_sources' => $this->pageViewModel->getTrafficSources(),
            'site_top_pages'       => $this->pageViewModel->getTopPages(10),
            'site_view_days'       => $siteViewDays,
        ]);
    }

    public function orders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = trim($params['status'] ?? '');
        $offset = ($page - 1) * self::ORDERS_PER_PAGE;

        $orders = $this->orderModel->findAllAdmin(self::ORDERS_PER_PAGE, $offset, $status);
        $total  = $this->orderModel->countAllAdmin($status);

        return $this->view->render($response, 'pages/admin/orders.tpl', [
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

        return $this->view->render($response, 'pages/admin/products.tpl', [
            'page_title'   => 'All Products',
            'active_page'  => 'products',
            'products'     => $products,
            'total'        => $total,
            'search'       => $search,
            'current_page' => $page,
            'total_pages'  => max(1, (int) ceil($total / self::PRODUCTS_PER_PAGE)),
        ]);
    }

    public function settings(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'pages/admin/settings.tpl', [
            'page_title'  => 'Settings',
            'active_page' => 'settings',
            'settings'    => $this->settingModel->all(),
        ]);
    }

    public function stopImpersonate(Request $request, Response $response): Response
    {
        $this->auth->stopImpersonating();

        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    // ── API endpoints ──

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
            $admin = $adminId !== null ? User::find($adminId) : null;
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

        $results['google'] = $this->httpPing(
            'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl)
        );

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
