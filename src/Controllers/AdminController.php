<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Enums\UserRole;
use TinyShop\Models\User;
use TinyShop\Models\Product;
use TinyShop\Models\Order;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Models\Subscription;
use TinyShop\Services\Auth;
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

        return $this->view->render($response, 'pages/admin_seller_detail.tpl', [
            'page_title'  => $seller['store_name'] ?: $seller['name'],
            'active_page' => 'sellers',
            'seller'      => $seller,
            'products'    => $products,
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
            return $response->withHeader('Location', '/admin/sellers')->withStatus(302);
        }

        $this->auth->impersonate((int) $seller['id'], $seller['name'], UserRole::tryFrom($seller['role'] ?? '') ?? UserRole::Seller);

        $this->logger->info('admin.impersonate', [
            'admin_id'  => $this->auth->realAdminId(),
            'seller_id' => $seller['id'],
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $response->withHeader('Location', '/dashboard')->withStatus(302);
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
        $active = !empty($data['is_active']);

        $this->userModel->toggleActive($id, $active);

        $this->logger->info('admin.seller_toggled', [
            'admin_id'  => $this->auth->userId(),
            'seller_id' => $id,
            'is_active' => $active,
            'ip'        => $request->getServerParams()['REMOTE_ADDR'] ?? '',
        ]);

        return $this->json($response, ['success' => true]);
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
