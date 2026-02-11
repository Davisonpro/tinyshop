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
use TinyShop\Models\Setting;
use TinyShop\Models\ShopView;
use TinyShop\Services\Auth;
use TinyShop\Services\Mailer;
use TinyShop\Services\Upload;
use TinyShop\Services\View;

final class AdminController
{
    use JsonResponder;

    private const SELLERS_PER_PAGE = 25;

    private const ALLOWED_SETTINGS = [
        'site_name', 'base_domain', 'support_email',
        'maintenance_mode', 'default_currency',
        'max_products_per_seller', 'allow_registration',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'smtp_encryption', 'mail_from_email', 'mail_from_name',
    ];

    public function __construct(
        private View $view,
        private Auth $auth,
        private User $userModel,
        private Product $productModel,
        private Order $orderModel,
        private Setting $settingModel,
        private ShopView $shopViewModel,
        private Mailer $mailer,
        private Upload $upload,
        private LoggerInterface $logger
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
}
