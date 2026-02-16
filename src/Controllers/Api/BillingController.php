<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Plan;
use TinyShop\Models\Setting;
use TinyShop\Models\Subscription;
use TinyShop\Models\User;
use TinyShop\Services\Auth;
use TinyShop\Services\Config;
use TinyShop\Services\DB;
use TinyShop\Services\Payment;

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
        private readonly Payment $payment,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    /**
     * POST /api/billing/subscribe — Start subscription payment
     */
    public function subscribe(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $planId = (int) ($data['plan_id'] ?? 0);
        $cycle = $data['cycle'] ?? 'monthly';

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid billing cycle'], 422);
        }

        $plan = $this->planModel->findById($planId);
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
        $user = $this->userModel->findById($userId);
        $customerEmail = $user['email'] ?? '';

        if ($gateway === 'stripe') {
            $secretKey = $settings['platform_stripe_secret_key'] ?? '';
            if ($secretKey === '') {
                return $this->json($response, ['error' => true, 'message' => 'Stripe is not configured. Contact support.'], 422);
            }

            $mode = $settings['platform_stripe_mode'] ?? 'test';
            if ($mode !== 'live' && str_starts_with($secretKey, 'sk_live_')) {
                $secretKey = ''; // Mismatch safeguard
            }

            $currency = $plan['currency'] ?? 'KES';

            $redirectUrl = $this->payment->createStripeSession(
                $secretKey,
                [[
                    'product_name' => $plan['name'] . ' Plan (' . ucfirst($cycle) . ')',
                    'product_image' => null,
                    'unit_price' => $price,
                    'quantity' => 1,
                ]],
                $currency,
                $successUrl,
                $cancelUrl,
                $customerEmail,
                'SUB-' . $userId . '-' . $planId . '-' . $cycle
            );

            return $this->json($response, ['success' => true, 'redirect_url' => $redirectUrl]);

        } elseif ($gateway === 'paypal') {
            $clientId = $settings['platform_paypal_client_id'] ?? '';
            $secret = $settings['platform_paypal_secret'] ?? '';

            if ($clientId === '' || $secret === '') {
                return $this->json($response, ['error' => true, 'message' => 'PayPal is not configured. Contact support.'], 422);
            }

            $sandbox = ($settings['platform_paypal_mode'] ?? 'test') !== 'live';
            $currency = $plan['currency'] ?? 'KES';

            $ppSuccessUrl = $baseUrl . '/billing/return/paypal';

            $redirectUrl = $this->payment->createPayPalOrder(
                $clientId,
                $secret,
                $price,
                $currency,
                $ppSuccessUrl,
                $cancelUrl,
                $sandbox
            );

            if (!$redirectUrl) {
                return $this->json($response, ['error' => true, 'message' => 'Could not create PayPal payment'], 500);
            }

            // Store plan info in session for return
            $_SESSION['billing_paypal'] = [
                'plan_id' => $planId,
                'cycle' => $cycle,
                'amount' => $price,
            ];

            return $this->json($response, ['success' => true, 'redirect_url' => $redirectUrl]);

        } elseif ($gateway === 'mpesa') {
            $shortcode = $settings['platform_mpesa_shortcode'] ?? '';
            $consumerKey = $settings['platform_mpesa_consumer_key'] ?? '';
            $consumerSecret = $settings['platform_mpesa_consumer_secret'] ?? '';
            $passkey = $settings['platform_mpesa_passkey'] ?? '';

            if ($shortcode === '' || $consumerKey === '' || $consumerSecret === '' || $passkey === '') {
                return $this->json($response, ['error' => true, 'message' => 'M-Pesa is not configured. Contact support.'], 422);
            }

            $mpesaPhone = trim($data['mpesa_phone'] ?? '');
            if (empty($mpesaPhone)) {
                return $this->json($response, ['error' => true, 'message' => 'Phone number is required for M-Pesa'], 422);
            }

            // Normalize phone
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

            $sandbox = ($settings['platform_mpesa_mode'] ?? 'test') !== 'live';
            $ref = 'SUB-' . $userId . '-' . $planId . '-' . $cycle;
            $callbackUrl = $baseUrl . '/webhook/mpesa/billing';

            try {
                $stkResult = $this->payment->initiateStkPush(
                    $consumerKey,
                    $consumerSecret,
                    $shortcode,
                    $passkey,
                    $mpesaPhone,
                    $price,
                    $callbackUrl,
                    $ref,
                    'Subscription',
                    $sandbox
                );
            } catch (\Throwable $e) {
                $this->logger->error('billing.mpesa_stk_error', ['error' => $e->getMessage()]);
                return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 500);
            }

            // Store pending billing in DB for webhook matching
            $checkoutRequestId = $stkResult['CheckoutRequestID'] ?? '';
            $stmt = $this->db->prepare(
                'INSERT INTO billing_mpesa_pending (user_id, plan_id, billing_cycle, checkout_request_id, amount) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $planId, $cycle, $checkoutRequestId, $price]);

            return $this->json($response, [
                'success' => true,
                'gateway' => 'mpesa',
                'poll_url' => '/api/billing/status',
            ]);
        }

        return $this->json($response, ['error' => true, 'message' => 'Unsupported payment gateway'], 422);
    }

    /**
     * GET /billing/return/{gateway} — Handle payment return
     */
    public function handleReturn(Request $request, Response $response, array $args): Response
    {
        $gateway = $args['gateway'] ?? '';
        $params = $request->getQueryParams();

        if ($gateway === 'stripe') {
            return $this->handleStripeReturn($request, $response, $params);
        } elseif ($gateway === 'paypal') {
            return $this->handlePayPalReturn($request, $response, $params);
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
        $secretKey = $settings['platform_stripe_secret_key'] ?? '';
        if ($secretKey === '') {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        try {
            $result = $this->payment->verifyStripeSession($sessionId, $secretKey);
        } catch (\Throwable $e) {
            $this->logger->error('billing.stripe_verify_error', ['error' => $e->getMessage()]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        if (empty($result['paid'])) {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        // Parse the order reference: SUB-{userId}-{planId}-{cycle}
        $ref = $result['order_number'] ?? '';
        $parts = explode('-', $ref);
        if (count($parts) < 4 || $parts[0] !== 'SUB') {
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $userId = (int) $parts[1];
        $planId = (int) $parts[2];
        $cycle = $parts[3];

        // Verify the userId matches the authenticated user to prevent subscription hijacking
        $currentUserId = $this->auth->userId();
        if (!$currentUserId || $userId !== $currentUserId) {
            $this->logger->warning('billing.stripe_user_mismatch', ['ref_user' => $userId, 'auth_user' => $currentUserId]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        $this->activateSubscription($userId, $planId, $cycle, 'stripe', $result['payment_intent'] ?? $sessionId, $result['amount'] ?? 0);

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
        $clientId = $settings['platform_paypal_client_id'] ?? '';
        $secret = $settings['platform_paypal_secret'] ?? '';
        $sandbox = ($settings['platform_paypal_mode'] ?? 'test') !== 'live';

        try {
            $result = $this->payment->capturePayPalOrder($paypalOrderId, $clientId, $secret, $sandbox);
        } catch (\Throwable $e) {
            $this->logger->error('billing.paypal_capture_error', ['error' => $e->getMessage()]);
            return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
        }

        if (empty($result['paid'])) {
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
            $result['id'] ?? $paypalOrderId,
            (float) ($result['amount'] ?? $billingInfo['amount'])
        );

        unset($_SESSION['billing_paypal']);

        $_SESSION['toast'] = 'Your plan has been upgraded!';
        return $response->withHeader('Location', '/dashboard/billing')->withStatus(302);
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
     * POST /api/billing/cancel — Cancel subscription
     */
    public function cancel(Request $request, Response $response): Response
    {
        $userId = $this->auth->userId();
        $active = $this->subscriptionModel->findActiveByUser($userId);

        if (!$active) {
            return $this->json($response, ['error' => true, 'message' => 'No active subscription found'], 404);
        }

        $this->subscriptionModel->updateStatus((int) $active['id'], 'cancelled');

        $this->logger->info('billing.subscription_cancelled', [
            'user_id' => $userId,
            'subscription_id' => $active['id'],
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Your plan will remain active until ' . date('M j, Y', strtotime($active['expires_at'])),
        ]);
    }

    /**
     * GET /api/billing/status — Check M-Pesa billing payment status (polling)
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

        return $this->json($response, ['status' => 'pending']);
    }
}
