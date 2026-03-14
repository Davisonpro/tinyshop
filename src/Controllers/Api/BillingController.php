<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\App;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\Subscription;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\Config;
use TinyShop\Services\DB;
use TinyShop\Services\Gateways\GatewayFactory;
use TinyShop\Services\Gateways\PaymentRequest;

/**
 * Billing API controller.
 *
 * @since 1.0.0
 */
final class BillingController
{
    use JsonResponder;

    private readonly \PDO $db;

    public function __construct(
        private readonly Auth $auth,
        private readonly Plan $planModel,
        private readonly Subscription $subscriptionModel,
        private readonly User $userModel,
        private readonly Setting $settingModel,
        private readonly GatewayFactory $gatewayFactory,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    /**
     * Get available plans, current subscription, history, and gateways.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function getPlans(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $settings = $this->settingModel->all();

        // Get all active plans
        $plans = $this->planModel->findAll();
        foreach ($plans as &$plan) {
            // Decode JSON features
            if (is_string($plan['features'] ?? null)) {
                $plan['features'] = json_decode($plan['features'], true) ?: [];
            }
            if (is_string($plan['allowed_themes'] ?? null)) {
                $plan['allowed_themes'] = json_decode($plan['allowed_themes'], true) ?: [];
            }
            // Map field names for mobile compatibility
            $plan['feature_list'] = $plan['features'] ?? [];
            $plan['max_products'] = (int) ($plan['max_products'] ?? 0);
            $plan['custom_domain'] = (bool) ($plan['custom_domain_allowed'] ?? false);
            $plan['coupons'] = (bool) ($plan['coupons_allowed'] ?? false);
            $plan['all_themes'] = !empty($plan['allowed_themes']) && $plan['allowed_themes'] === ['*'];
        }
        unset($plan);

        // Get subscription history
        $history = $this->subscriptionModel->findByUser($userId, 20);

        // Determine available payment gateways
        $gateways = [];
        if (!empty($settings['platform_stripe_enabled']) && !empty($settings['platform_stripe_secret_key'])) {
            $gateways[] = 'stripe';
        }
        if (!empty($settings['platform_paypal_enabled']) && !empty($settings['platform_paypal_client_id'])) {
            $gateways[] = 'paypal';
        }
        if (!empty($settings['platform_mpesa_enabled']) && !empty($settings['platform_mpesa_consumer_key'])) {
            $gateways[] = 'mpesa';
        }
        if (!empty($settings['platform_pesapal_enabled']) && !empty($settings['platform_pesapal_consumer_key'])) {
            $gateways[] = 'pesapal';
        }

        return $this->json($response, [
            'plans' => $plans,
            'history' => array_map(fn(array $h) => [
                'plan_name' => $h['plan_name'] ?? '',
                'starts_at' => $h['starts_at'] ?? null,
                'expires_at' => $h['expires_at'] ?? null,
                'amount_paid' => $h['amount_paid'] ?? 0,
                'status' => $h['status'] ?? '',
            ], $history),
            'gateways' => $gateways,
        ]);
    }

    /**
     * Initiate a subscription payment.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function subscribe(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $planId = (int) ($data['plan_id'] ?? 0);
        $cycle = $data['cycle'] ?? 'monthly';

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid billing cycle'], 422);
        }

        $plan = Plan::find($planId);
        if (!$plan || !$plan['is_active']) {
            return $this->json($response, ['error' => true, 'message' => 'Plan not found'], 404);
        }

        $price = $cycle === 'yearly' ? (float) $plan['price_yearly'] : (float) $plan['price_monthly'];
        if ($price <= 0) {
            return $this->json($response, ['error' => true, 'message' => 'This plan is free — no payment needed'], 422);
        }

        $gateway = $data['gateway'] ?? 'stripe';
        $settings = $this->settingModel->all();

        $baseUrl = $this->config->url();
        $successUrl = $baseUrl . '/billing/return/' . $gateway . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $baseUrl . '/dashboard/billing';

        $userId = $this->auth->userId();
        $user = User::find($userId);
        $customerEmail = $user['email'] ?? '';

        $config = $this->buildBillingGatewayConfig($gateway, $settings);
        $currency = $plan['currency'] ?? App::DEFAULT_CURRENCY;
        $ref = 'SUB-' . $userId . '-' . $planId . '-' . $cycle . '-' . time();
        $description = $plan['name'] . ' Plan (' . ucfirst($cycle) . ')';

        // Validate gateway is enabled
        $enabledKey = 'platform_' . $gateway . '_enabled';
        if (empty($settings[$enabledKey])) {
            return $this->json($response, ['error' => true, 'message' => ucfirst($gateway) . ' payments are not enabled. Contact support.'], 422);
        }

        // Validate credentials
        if ($gateway === 'stripe' && ($config['secret_key'] ?? '') === '') {
            return $this->json($response, ['error' => true, 'message' => 'Stripe is not configured. Contact support.'], 422);
        }
        if ($gateway === 'paypal' && (($config['client_id'] ?? '') === '' || ($config['secret'] ?? '') === '')) {
            return $this->json($response, ['error' => true, 'message' => 'PayPal is not configured. Contact support.'], 422);
        }
        if ($gateway === 'mpesa') {
            if (($config['consumer_key'] ?? '') === '' || ($config['shortcode'] ?? '') === '') {
                return $this->json($response, ['error' => true, 'message' => 'M-Pesa is not configured. Contact support.'], 422);
            }
        }
        if ($gateway === 'pesapal' && (($config['consumer_key'] ?? '') === '' || ($config['consumer_secret'] ?? '') === '')) {
            return $this->json($response, ['error' => true, 'message' => 'Pesapal is not configured. Contact support.'], 422);
        }

        // M-Pesa phone validation
        $mpesaPhone = '';
        if ($gateway === 'mpesa') {
            $mpesaPhone = trim($data['mpesa_phone'] ?? '');
            if (empty($mpesaPhone)) {
                return $this->json($response, ['error' => true, 'message' => 'Phone number is required for M-Pesa'], 422);
            }
            $mpesaPhone = preg_replace('/[\s\-]/', '', $mpesaPhone);
            if (str_starts_with($mpesaPhone, '0')) {
                $mpesaPhone = '254' . substr($mpesaPhone, 1);
            }
            if (str_starts_with($mpesaPhone, '+')) {
                $mpesaPhone = substr($mpesaPhone, 1);
            }
            if (!preg_match('/^254[0-9]{9}$/', $mpesaPhone)) {
                return $this->json($response, ['error' => true, 'message' => 'Enter a valid Kenyan phone number'], 422);
            }
        }

        // Stripe mode mismatch safeguard
        if ($gateway === 'stripe') {
            $mode = $settings['platform_stripe_mode'] ?? 'test';
            if ($mode !== 'live' && str_starts_with($config['secret_key'] ?? '', 'sk_live_')) {
                $config['secret_key'] = '';
            }
        }

        // Build gateway-specific URLs
        $gwSuccessUrl = match ($gateway) {
            'stripe' => $successUrl,
            'paypal' => $baseUrl . '/billing/return/paypal',
            'pesapal' => $baseUrl . '/billing/return/pesapal',
            default => $successUrl,
        };
        $webhookUrl = match ($gateway) {
            'mpesa' => $baseUrl . '/webhook/mpesa/billing',
            'pesapal' => $baseUrl . '/webhook/pesapal/billing',
            default => '',
        };

        try {
            $gw = $this->gatewayFactory->create($gateway, $config);
            $result = $gw->createPayment(new PaymentRequest(
                amount: $price,
                currency: $currency,
                reference: $ref,
                successUrl: $gwSuccessUrl,
                cancelUrl: $cancelUrl,
                webhookUrl: $webhookUrl,
                customerEmail: $customerEmail,
                customerName: $user['name'] ?? '',
                customerPhone: $mpesaPhone,
                description: $description,
                brandName: $this->settingModel->get('app_name', ''),
                lineItems: $gateway === 'stripe' ? [[
                    'product_name' => $description,
                    'product_image' => null,
                    'unit_price' => $price,
                    'quantity' => 1,
                ]] : [],
            ));
        } catch (\Throwable $e) {
            $this->logger->error('billing.' . $gateway . '_error', ['error' => $e->getMessage()]);
            return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 500);
        }

        // Gateway-specific post-processing
        if ($gateway === 'paypal') {
            if ($result->redirectUrl === '') {
                return $this->json($response, ['error' => true, 'message' => 'Could not create PayPal payment'], 500);
            }
            $_SESSION['billing_paypal'] = [
                'plan_id' => $planId,
                'cycle' => $cycle,
                'amount' => $price,
            ];
        }

        if ($gateway === 'mpesa') {
            $stmt = $this->db->prepare(
                'INSERT INTO billing_mpesa_pending (user_id, plan_id, billing_cycle, checkout_request_id, amount) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $planId, $cycle, $result->transactionId, $price]);

            return $this->json($response, [
                'success' => true,
                'gateway' => 'mpesa',
                'poll_url' => '/api/billing/status',
            ]);
        }

        if ($gateway === 'pesapal') {
            $stmt = $this->db->prepare(
                'INSERT INTO billing_pesapal_pending (user_id, plan_id, billing_cycle, order_tracking_id, merchant_reference, amount) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $planId, $cycle, $result->transactionId, $ref, $price]);
        }

        return $this->json($response, ['success' => true, 'redirect_url' => $result->redirectUrl]);
    }

    /**
     * Handle billing payment return redirect.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @param array    $args     Route arguments.
     * @return Response
     */
    public function handleReturn(Request $request, Response $response, array $args): Response
    {
        $gateway = $args['gateway'] ?? '';
        $params = $request->getQueryParams();

        if ($gateway === 'stripe') {
            return $this->handleStripeReturn($request, $response, $params);
        } elseif ($gateway === 'paypal') {
            return $this->handlePayPalReturn($request, $response, $params);
        } elseif ($gateway === 'pesapal') {
            return $this->handlePesapalReturn($request, $response, $params);
        }

        return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
    }

    private function handleStripeReturn(Request $request, Response $response, array $params): Response
    {
        $sessionId = $params['session_id'] ?? '';
        if ($sessionId === '') {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $settings = $this->settingModel->all();
        $config = $this->buildBillingGatewayConfig('stripe', $settings);

        try {
            $gw = $this->gatewayFactory->create('stripe', $config);
            $verification = $gw->verifyPayment($params);
        } catch (\Throwable $e) {
            $this->logger->error('billing.stripe_verify_error', ['error' => $e->getMessage()]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        if (!$verification->paid) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $parsed = $this->parseSubscriptionRef($verification->reference);
        if (!$parsed) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $currentUserId = $this->auth->userId();
        if (!$currentUserId || $parsed['user_id'] !== $currentUserId) {
            $this->logger->warning('billing.stripe_user_mismatch', ['ref_user' => $parsed['user_id'], 'auth_user' => $currentUserId]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $this->activateSubscription($parsed['user_id'], $parsed['plan_id'], $parsed['cycle'], 'stripe', $verification->transactionId ?: $sessionId, $verification->amount);

        $_SESSION['toast'] = 'Your plan has been upgraded!';
        return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
    }

    private function handlePayPalReturn(Request $request, Response $response, array $params): Response
    {
        $paypalOrderId = $params['token'] ?? '';
        if ($paypalOrderId === '') {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $billingInfo = $_SESSION['billing_paypal'] ?? null;
        if (!$billingInfo) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $settings = $this->settingModel->all();
        $config = $this->buildBillingGatewayConfig('paypal', $settings);

        try {
            $gw = $this->gatewayFactory->create('paypal', $config);
            $verification = $gw->verifyPayment($params);
        } catch (\Throwable $e) {
            $this->logger->error('billing.paypal_capture_error', ['error' => $e->getMessage()]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        if (!$verification->paid) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $userId = $this->auth->userId();
        if (!$userId) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $this->activateSubscription(
            $userId,
            (int) $billingInfo['plan_id'],
            $billingInfo['cycle'],
            'paypal',
            $verification->transactionId ?: $paypalOrderId,
            $verification->amount ?: (float) $billingInfo['amount']
        );

        unset($_SESSION['billing_paypal']);

        $_SESSION['toast'] = 'Your plan has been upgraded!';
        return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
    }

    private function handlePesapalReturn(Request $request, Response $response, array $params): Response
    {
        $orderTrackingId = $params['OrderTrackingId'] ?? '';
        if ($orderTrackingId === '') {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $settings = $this->settingModel->all();
        $config = $this->buildBillingGatewayConfig('pesapal', $settings);

        try {
            $gw = $this->gatewayFactory->create('pesapal', $config);
            $verification = $gw->verifyPayment($params);
        } catch (\Throwable $e) {
            $this->logger->error('billing.pesapal_verify_error', ['error' => $e->getMessage()]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        if (!$verification->paid) {
            $_SESSION['toast'] = 'Payment is being processed. You\'ll be notified once confirmed.';
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $merchantRef = $params['OrderMerchantReference'] ?? '';
        $ref = $merchantRef ?: $verification->reference;
        $parsed = $this->parseSubscriptionRef($ref);
        if (!$parsed) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $currentUserId = $this->auth->userId();
        if (!$currentUserId || $parsed['user_id'] !== $currentUserId) {
            $this->logger->warning('billing.pesapal_user_mismatch', ['ref_user' => $parsed['user_id'], 'auth_user' => $currentUserId]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $stmt = $this->db->prepare('UPDATE billing_pesapal_pending SET status = ? WHERE order_tracking_id = ?');
        $stmt->execute(['paid', $orderTrackingId]);

        $this->activateSubscription($parsed['user_id'], $parsed['plan_id'], $parsed['cycle'], 'pesapal', $verification->transactionId ?: $orderTrackingId, $verification->amount);

        $_SESSION['toast'] = 'Your plan has been upgraded!';
        return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
    }

    private function buildBillingGatewayConfig(string $gateway, array $settings): array
    {
        return match ($gateway) {
            'stripe' => [
                'secret_key' => $settings['platform_stripe_secret_key'] ?? '',
            ],
            'paypal' => [
                'client_id' => $settings['platform_paypal_client_id'] ?? '',
                'secret' => $settings['platform_paypal_secret'] ?? '',
                'sandbox' => ($settings['platform_paypal_mode'] ?? 'test') !== 'live',
            ],
            'mpesa' => [
                'consumer_key' => $settings['platform_mpesa_consumer_key'] ?? '',
                'consumer_secret' => $settings['platform_mpesa_consumer_secret'] ?? '',
                'shortcode' => $settings['platform_mpesa_shortcode'] ?? '',
                'passkey' => $settings['platform_mpesa_passkey'] ?? '',
                'sandbox' => ($settings['platform_mpesa_mode'] ?? 'test') !== 'live',
            ],
            'pesapal' => [
                'consumer_key' => $settings['platform_pesapal_consumer_key'] ?? '',
                'consumer_secret' => $settings['platform_pesapal_consumer_secret'] ?? '',
                'sandbox' => ($settings['platform_pesapal_mode'] ?? 'test') !== 'live',
            ],
            default => [],
        };
    }

    /**
     * Parse a subscription reference string.
     *
     * @since 1.0.0
     *
     * @param string $ref Reference string.
     * @return array|null
     */
    private function parseSubscriptionRef(string $ref): ?array
    {
        $parts = explode('-', $ref);
        if (count($parts) < 4 || $parts[0] !== 'SUB') {
            return null;
        }
        return [
            'user_id' => (int) $parts[1],
            'plan_id' => (int) $parts[2],
            'cycle' => $parts[3],
        ];
    }

    private function activateSubscription(int $userId, int $planId, string $cycle, string $gateway, string $reference, float $amount): void
    {
        $now = new \DateTimeImmutable();
        $expiresAt = $cycle === 'yearly'
            ? $now->modify('+1 year')
            : $now->modify('+1 month');

        $this->subscriptionModel->create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'billing_cycle' => $cycle,
            'status' => 'active',
            'starts_at' => $now->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'amount_paid' => $amount,
        ]);

        // Update user's plan
        $stmt = $this->db->prepare('UPDATE users SET plan_id = ?, plan_expires_at = ? WHERE id = ?');
        $stmt->execute([$planId, $expiresAt->format('Y-m-d H:i:s'), $userId]);

        $this->logger->info('billing.subscription_activated', [
            'user_id' => $userId,
            'plan_id' => $planId,
            'cycle' => $cycle,
            'gateway' => $gateway,
            'amount' => $amount,
        ]);
    }

    /**
     * Cancel the active subscription.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function cancel(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $active = $this->subscriptionModel->findActiveByUser($userId);

        if ($active) {
            // Mark as cancelled — user keeps the plan until expires_at,
            // then expireOverdue() will clean up when the period ends.
            $this->subscriptionModel->updateStatus((int) $active['id'], 'cancelled');

            $this->logger->info('billing.subscription_cancelled', [
                'user_id' => $userId,
                'subscription_id' => $active['id'],
            ]);

            $expiryMsg = 'Your plan will remain active until ' . date('M j, Y', strtotime($active['expires_at']));
        } else {
            // No subscription row but user may have a plan assigned directly
            $user = \TinyShop\Models\User::find($userId)?->toArray();
            if (!$user || empty($user['plan_id'])) {
                return $this->json($response, ['error' => true, 'message' => 'No active subscription found'], 404);
            }

            // No subscription row — clear immediately
            $stmt = $this->db->prepare('UPDATE users SET plan_id = NULL, plan_expires_at = NULL WHERE id = ?');
            $stmt->execute([$userId]);

            $expiryMsg = 'Your plan has been cancelled';
        }

        return $this->json($response, [
            'success' => true,
            'message' => $expiryMsg,
        ]);
    }

    /**
     * Check billing payment status (polling endpoint).
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function checkBillingStatus(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        if (!$userId) {
            return $this->json($response, ['status' => 'unknown']);
        }

        // Check for recently paid M-Pesa billing
        $stmt = $this->db->prepare(
            'SELECT * FROM billing_mpesa_pending
             WHERE user_id = ? AND status = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId, 'paid']);
        $row = $stmt->fetch();

        if ($row) {
            return $this->json($response, ['status' => 'paid', 'redirect' => '/dashboard/billing']);
        }

        // Also check Pesapal pending payments
        $stmt = $this->db->prepare(
            'SELECT * FROM billing_pesapal_pending
             WHERE user_id = ? AND status = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$userId, 'paid']);
        $row = $stmt->fetch();

        if ($row) {
            return $this->json($response, ['status' => 'paid', 'redirect' => '/dashboard/billing']);
        }

        return $this->json($response, ['status' => 'pending']);
    }
}
