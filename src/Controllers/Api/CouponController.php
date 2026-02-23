<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Coupon;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\PlanGuard;

final class CouponController
{
    use JsonResponder;

    public function __construct(
        private readonly Auth $auth,
        private readonly Coupon $couponModel,
        private readonly User $userModel,
        private readonly PlanGuard $planGuard
    ) {}

    /**
     * GET /api/coupons — List seller's coupons
     */
    public function list(Request $request, Response $response): Response
    {
        $coupons = $this->couponModel->findByUser($this->auth->userId());
        return $this->json($response, ['success' => true, 'coupons' => $coupons]);
    }

    /**
     * POST /api/coupons — Create coupon
     */
    public function create(Request $request, Response $response): Response
    {
        // Plan check: coupons feature
        if (!$this->planGuard->canUseCoupons($this->auth->userId())) {
            return $this->json($response, ['error' => true, 'message' => 'Coupons are available on paid plans.'], 403);
        }

        $data = (array) $request->getParsedBody();
        $code = strtoupper(trim($data['code'] ?? ''));
        $type = $data['type'] ?? 'percent';
        $value = (float) ($data['value'] ?? 0);

        if (empty($code) || !preg_match('/^[A-Z0-9_-]{2,50}$/', $code)) {
            return $this->json($response, ['error' => true, 'message' => 'Code must be 2-50 characters (letters, numbers, hyphens, underscores)'], 422);
        }

        if (!in_array($type, ['percent', 'fixed'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Type must be percent or fixed'], 422);
        }

        if ($value <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Value must be greater than 0'], 422);
        }

        if ($type === 'percent' && $value > 100) {
            return $this->json($response, ['error' => true, 'message' => 'Percent discount cannot exceed 100%'], 422);
        }

        $userId = $this->auth->userId();

        // Check for duplicate code
        $existing = $this->couponModel->findByUserAndCode($userId, $code);
        if ($existing) {
            return $this->json($response, ['error' => true, 'message' => 'A coupon with this code already exists'], 422);
        }

        $minOrder = !empty($data['min_order']) ? (float) $data['min_order'] : null;
        $maxUses = !empty($data['max_uses']) ? (int) $data['max_uses'] : null;
        $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;

        $id = $this->couponModel->create([
            'user_id' => $userId,
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order' => $minOrder,
            'max_uses' => $maxUses,
            'is_active' => 1,
            'expires_at' => $expiresAt,
        ]);

        $coupon = Coupon::find($id);
        return $this->json($response, ['success' => true, 'coupon' => $coupon], 201);
    }

    /**
     * PUT /api/coupons/{id} — Update coupon
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $coupon = Coupon::find($id);

        if (!$coupon || (int) $coupon['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Coupon not found'], 404);
        }

        $data = (array) $request->getParsedBody();
        $updates = [];

        if (isset($data['code'])) {
            $code = strtoupper(trim($data['code']));
            if (!preg_match('/^[A-Z0-9_-]{2,50}$/', $code)) {
                return $this->json($response, ['error' => true, 'message' => 'Invalid code format'], 422);
            }
            // Check duplicate (exclude self)
            $existing = $this->couponModel->findByUserAndCode($this->auth->userId(), $code);
            if ($existing && (int) $existing['id'] !== $id) {
                return $this->json($response, ['error' => true, 'message' => 'A coupon with this code already exists'], 422);
            }
            $updates['code'] = $code;
        }

        if (isset($data['type'])) {
            if (!in_array($data['type'], ['percent', 'fixed'], true)) {
                return $this->json($response, ['error' => true, 'message' => 'Type must be percent or fixed'], 422);
            }
            $updates['type'] = $data['type'];
        }

        if (isset($data['value'])) {
            $value = (float) $data['value'];
            if ($value <= 0) {
                return $this->json($response, ['error' => true, 'message' => 'Value must be greater than 0'], 422);
            }
            $checkType = $updates['type'] ?? $coupon['type'];
            if ($checkType === 'percent' && $value > 100) {
                return $this->json($response, ['error' => true, 'message' => 'Percent discount cannot exceed 100%'], 422);
            }
            $updates['value'] = $value;
        }

        if (array_key_exists('min_order', $data)) {
            $updates['min_order'] = !empty($data['min_order']) ? (float) $data['min_order'] : null;
        }

        if (array_key_exists('max_uses', $data)) {
            $updates['max_uses'] = !empty($data['max_uses']) ? (int) $data['max_uses'] : null;
        }

        if (isset($data['is_active'])) {
            $updates['is_active'] = (int) (bool) $data['is_active'];
        }

        if (array_key_exists('expires_at', $data)) {
            $updates['expires_at'] = !empty($data['expires_at']) ? $data['expires_at'] : null;
        }

        if (!empty($updates)) {
            $this->couponModel->update($id, $updates);
        }

        $coupon = Coupon::find($id);
        return $this->json($response, ['success' => true, 'coupon' => $coupon]);
    }

    /**
     * DELETE /api/coupons/{id} — Delete coupon
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $coupon = Coupon::find($id);

        if (!$coupon || (int) $coupon['user_id'] !== $this->auth->userId()) {
            return $this->json($response, ['error' => true, 'message' => 'Coupon not found'], 404);
        }

        $this->couponModel->delete($id);
        return $this->json($response, ['success' => true]);
    }

    /**
     * POST /api/checkout/apply-coupon — Validate coupon for cart (public)
     */
    public function applyCoupon(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);
        $code = strtoupper(trim($data['code'] ?? ''));
        $subtotal = (float) ($data['subtotal'] ?? 0);

        if ($shopId <= 0 || empty($code) || $subtotal <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid request'], 422);
        }

        $shop = User::find($shopId);
        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $coupon = $this->couponModel->findByUserAndCode($shopId, $code);
        if (!$coupon) {
            return $this->json($response, ['error' => true, 'message' => 'Coupon not found'], 404);
        }

        $result = $this->couponModel->validateCoupon($coupon, $subtotal);

        if (!$result['valid']) {
            return $this->json($response, ['error' => true, 'message' => $result['message']], 422);
        }

        return $this->json($response, [
            'success' => true,
            'message' => $result['message'],
            'discount' => $result['discount'],
            'type' => $coupon['type'],
            'value' => (float) $coupon['value'],
            'code' => $coupon['code'],
        ]);
    }
}
