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
use TinyShop\Models\Plan;
use TinyShop\Services\Auth;
use TinyShop\Services\Upload;
use TinyShop\Services\View;

/**
 * Admin seller management controller.
 *
 * @since 1.0.0
 */
final class AdminSellerController
{
    use JsonResponder;

    private const SELLERS_PER_PAGE = 25;

    public function __construct(
        private readonly View $view,
        private readonly Auth $auth,
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly Plan $planModel,
        private readonly Upload $upload,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * List all sellers.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function sellers(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $page   = max(1, (int) ($params['page'] ?? 1));
        $search = trim($params['q'] ?? '');
        $offset = ($page - 1) * self::SELLERS_PER_PAGE;

        $sellers = $this->userModel->findSellers(self::SELLERS_PER_PAGE, $offset, $search);
        $total   = $this->userModel->countSellers($search);

        return $this->view->render($response, 'pages/admin/sellers.tpl', [
            'page_title'   => 'Sellers',
            'active_page'  => 'sellers',
            'sellers'      => $sellers,
            'total'        => $total,
            'search'       => $search,
            'current_page' => $page,
            'total_pages'  => max(1, (int) ceil($total / self::SELLERS_PER_PAGE)),
        ]);
    }

    /**
     * Render the seller detail page.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
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

        return $this->view->render($response, 'pages/admin/seller_detail.tpl', [
            'page_title'   => $seller['store_name'] ?? '',
            'active_page'  => 'sellers',
            'seller'       => $seller,
            'products'     => $products,
            'plans'        => $plans,
            'plan_expired' => $planExpired,
        ]);
    }

    /**
     * Impersonate a seller.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function impersonate(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $seller = User::find($id);

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

    /**
     * Toggle a seller's active or showcased status.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function toggleSeller(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        $target = User::find($id);
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

    /**
     * Assign or remove a seller's plan.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function updateSellerPlan(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $data = (array) $request->getParsedBody();

        $target = User::find($id);
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
            $plan = Plan::find($planId);
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

    /**
     * Delete a seller account.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function deleteSeller(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $seller = User::find($id);

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
}
