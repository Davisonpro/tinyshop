<?php

declare(strict_types=1);

namespace TinyShop\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\Product;
use TinyShop\Models\Subscription;
use TinyShop\Models\User;
use TinyShop\Models\Setting;
use TinyShop\Services\DB;
use TinyShop\Services\Gateways\GatewayFactory;
use TinyShop\Services\Mailer;

/**
 * Payment webhook controller.
 *
 * @since 1.0.0
 */
final class WebhookController
{
    use JsonResponder;

    private readonly \PDO $db;

    public function __construct(
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly Product $productModel,
        private readonly User $userModel,
        private readonly Subscription $subscriptionModel,
        private readonly Setting $settingModel,
        private readonly GatewayFactory $gatewayFactory,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    /**
     * Handle a Stripe webhook event.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function stripeWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();

        if (empty($payload)) {
            return $this->json($response, ['error' => 'Empty payload'], 400);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json($response, ['error' => 'Invalid JSON'], 400);
        }

        if (($event['type'] ?? '') !== 'checkout.session.completed') {
            return $this->json($response, ['received' => true]);
        }

        $session = $event['data']['object'] ?? [];
        $sessionId = $session['id'] ?? '';
        $orderNumber = $session['metadata']['order_number'] ?? '';

        if (empty($sessionId) || empty($orderNumber)) {
            return $this->json($response, ['received' => true]);
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order || $order['status'] !== 'pending') {
            return $this->json($response, ['received' => true]);
        }

        $seller = User::find((int) $order['user_id']);
        if (!$seller || empty($seller['stripe_secret_key'])) {
            $this->logger->warning('webhook.stripe.no_credentials', ['order_number' => $orderNumber]);
            return $this->json($response, ['received' => true]);
        }

        // Re-verify payment with Stripe API
        try {
            $gw = $this->gatewayFactory->create('stripe', $this->buildSellerGatewayConfig('stripe', $seller));
            $verification = $gw->verifyPayment(['session_id' => $sessionId]);
        } catch (\Throwable $e) {
            $this->logger->error('webhook.stripe.verify_failed', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['received' => true]);
        }

        if ($verification->paid) {
            $intentId = $verification->transactionId ?: $sessionId;
            if ($this->orderModel->markPaid((int) $order['id'], $intentId)) {
                $this->decrementStockForOrder((int) $order['id']);
                $this->logger->info('webhook.stripe.paid', [
                    'order_id' => $order['id'],
                    'order_number' => $orderNumber,
                ]);
                $this->sendOrderEmails($order);
            }
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * Handle a PayPal webhook event.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function paypalWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();

        if (empty($payload)) {
            return $this->json($response, ['error' => 'Empty payload'], 400);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json($response, ['error' => 'Invalid JSON'], 400);
        }

        $eventType = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        if (!in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
            return $this->json($response, ['received' => true]);
        }

        $paypalOrderId = $resource['id'] ?? $resource['supplementary_data']['related_ids']['order_id'] ?? '';
        if (empty($paypalOrderId)) {
            return $this->json($response, ['received' => true]);
        }

        $order = $this->orderModel->findByPaymentIntent($paypalOrderId);
        if (!$order || $order['status'] !== 'pending') {
            return $this->json($response, ['received' => true]);
        }

        $seller = User::find((int) $order['user_id']);
        if (!$seller || empty($seller['paypal_client_id']) || empty($seller['paypal_secret'])) {
            $this->logger->warning('webhook.paypal.no_credentials', ['order_id' => $order['id']]);
            return $this->json($response, ['received' => true]);
        }

        // Re-verify payment status with PayPal API
        try {
            /** @var \TinyShop\Services\Gateways\PayPalGateway $gw */
            $gw = $this->gatewayFactory->create('paypal', $this->buildSellerGatewayConfig('paypal', $seller));
            $verification = $gw->getOrderStatus($paypalOrderId);
        } catch (\Throwable $e) {
            $this->logger->error('webhook.paypal.verify_failed', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['received' => true]);
        }

        if ($verification->paid) {
            if ($this->orderModel->markPaid((int) $order['id'])) {
                $this->decrementStockForOrder((int) $order['id']);
                $this->logger->info('webhook.paypal.paid', [
                    'order_id' => $order['id'],
                    'paypal_order_id' => $paypalOrderId,
                ]);
                $this->sendOrderEmails($order);
            }
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * Handle an M-Pesa storefront webhook callback.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function mpesaWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $sourceIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $this->logger->info('webhook.mpesa.received', ['source_ip' => $sourceIp]);

        if (empty($payload)) {
            return $this->json($response, ['ResultCode' => 1, 'ResultDesc' => 'Empty payload']);
        }

        // M-Pesa credentials not needed for callback parsing — use empty config
        $gw = $this->gatewayFactory->create('mpesa', [
            'consumer_key' => '', 'consumer_secret' => '',
            'shortcode' => '', 'passkey' => '',
        ]);
        $verification = $gw->parseWebhook($payload);

        if (!$verification->paid && $verification->reference === '') {
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if ($verification->paid && $verification->reference !== '') {
            $order = $this->orderModel->findByPaymentIntent($verification->reference);
            if ($order) {
                $intentId = $verification->transactionId ?: $verification->reference;
                if ($this->orderModel->markPaid((int) $order['id'], $intentId)) {
                    $this->decrementStockForOrder((int) $order['id']);
                    $this->logger->info('webhook.mpesa.paid', [
                        'order_id' => $order['id'],
                        'receipt' => $verification->transactionId,
                    ]);
                    $this->sendOrderEmails($order);
                }
            }
        }

        return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Handle an M-Pesa billing webhook callback.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function mpesaBillingWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $sourceIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $this->logger->info('webhook.mpesa.billing_received', ['source_ip' => $sourceIp]);

        if (empty($payload)) {
            return $this->json($response, ['ResultCode' => 1, 'ResultDesc' => 'Empty payload']);
        }

        $gw = $this->gatewayFactory->create('mpesa', [
            'consumer_key' => '', 'consumer_secret' => '',
            'shortcode' => '', 'passkey' => '',
        ]);
        $verification = $gw->parseWebhook($payload);

        if (!$verification->paid) {
            if ($verification->reference !== '') {
                $stmt = $this->db->prepare(
                    'UPDATE billing_mpesa_pending SET status = ? WHERE checkout_request_id = ? AND status = ?'
                );
                $stmt->execute(['failed', $verification->reference, 'pending']);
            }
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutRequestId = $verification->reference;
        if ($checkoutRequestId === '') {
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // Find pending billing row
        $stmt = $this->db->prepare(
            'SELECT * FROM billing_mpesa_pending WHERE checkout_request_id = ? AND status = ? LIMIT 1'
        );
        $stmt->execute([$checkoutRequestId, 'pending']);
        $pending = $stmt->fetch();

        if (!$pending) {
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        // Mark as paid
        $stmt = $this->db->prepare('UPDATE billing_mpesa_pending SET status = ? WHERE id = ?');
        $stmt->execute(['paid', $pending['id']]);

        // Activate subscription
        $this->activateBillingSubscription(
            (int) $pending['user_id'],
            (int) $pending['plan_id'],
            $pending['billing_cycle'],
            'mpesa',
            $verification->transactionId ?: $checkoutRequestId,
            (float) $pending['amount']
        );

        $this->logger->info('webhook.mpesa.billing_paid', [
            'user_id' => $pending['user_id'],
            'plan_id' => $pending['plan_id'],
            'receipt' => $verification->transactionId,
        ]);

        return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Handle a Pesapal storefront webhook.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function pesapalWebhook(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $orderTrackingId = $params['OrderTrackingId'] ?? '';
        $merchantRef = $params['OrderMerchantReference'] ?? '';

        $this->logger->info('webhook.pesapal.received', [
            'tracking_id' => $orderTrackingId,
            'merchant_ref' => $merchantRef,
        ]);

        if (empty($orderTrackingId)) {
            return $this->json($response, ['received' => true]);
        }

        // Find order by tracking ID (stored in payment_intent_id)
        $order = $this->orderModel->findByPaymentIntent($orderTrackingId);
        if (!$order || $order['status'] !== 'pending') {
            return $this->json($response, ['received' => true]);
        }

        $seller = User::find((int) $order['user_id']);
        if (!$seller || empty($seller['pesapal_consumer_key']) || empty($seller['pesapal_consumer_secret'])) {
            $this->logger->warning('webhook.pesapal.no_credentials', ['order_id' => $order['id']]);
            return $this->json($response, ['received' => true]);
        }

        try {
            $gw = $this->gatewayFactory->create('pesapal', $this->buildSellerGatewayConfig('pesapal', $seller));
            $verification = $gw->parseWebhook('', $params);
        } catch (\Throwable $e) {
            $this->logger->error('webhook.pesapal.verify_failed', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['received' => true]);
        }

        if ($verification->paid) {
            $intentId = $verification->transactionId ?: $orderTrackingId;
            if ($this->orderModel->markPaid((int) $order['id'], $intentId)) {
                $this->decrementStockForOrder((int) $order['id']);
                $this->logger->info('webhook.pesapal.paid', [
                    'order_id' => $order['id'],
                    'confirmation' => $verification->transactionId,
                ]);
                $this->sendOrderEmails($order);
            }
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * Handle a Pesapal billing webhook.
     *
     * @since 1.0.0
     *
     * @param Request  $request  PSR-7 request.
     * @param Response $response PSR-7 response.
     * @return Response
     */
    public function pesapalBillingWebhook(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $orderTrackingId = $params['OrderTrackingId'] ?? '';
        $merchantRef = $params['OrderMerchantReference'] ?? '';

        $this->logger->info('webhook.pesapal.billing_received', [
            'tracking_id' => $orderTrackingId,
            'merchant_ref' => $merchantRef,
        ]);

        if (empty($orderTrackingId)) {
            return $this->json($response, ['received' => true]);
        }

        // Find pending billing row by tracking ID
        $stmt = $this->db->prepare(
            'SELECT * FROM billing_pesapal_pending WHERE order_tracking_id = ? AND status = ? LIMIT 1'
        );
        $stmt->execute([$orderTrackingId, 'pending']);
        $pending = $stmt->fetch();

        if (!$pending) {
            return $this->json($response, ['received' => true]);
        }

        $settings = $this->settingModel->all();
        $config = $this->buildBillingGatewayConfig('pesapal', $settings);

        if (($config['consumer_key'] ?? '') === '' || ($config['consumer_secret'] ?? '') === '') {
            $this->logger->warning('webhook.pesapal.billing_no_credentials');
            return $this->json($response, ['received' => true]);
        }

        try {
            $gw = $this->gatewayFactory->create('pesapal', $config);
            $verification = $gw->parseWebhook('', $params);
        } catch (\Throwable $e) {
            $this->logger->error('webhook.pesapal.billing_verify_failed', ['error' => $e->getMessage()]);
            return $this->json($response, ['received' => true]);
        }

        if (!$verification->paid) {
            $stmt = $this->db->prepare('UPDATE billing_pesapal_pending SET status = ? WHERE id = ?');
            $stmt->execute(['failed', $pending['id']]);
            return $this->json($response, ['received' => true]);
        }

        // Mark as paid
        $stmt = $this->db->prepare('UPDATE billing_pesapal_pending SET status = ? WHERE id = ?');
        $stmt->execute(['paid', $pending['id']]);

        $this->activateBillingSubscription(
            (int) $pending['user_id'],
            (int) $pending['plan_id'],
            $pending['billing_cycle'],
            'pesapal',
            $verification->transactionId ?: $orderTrackingId,
            (float) $pending['amount']
        );

        $this->logger->info('webhook.pesapal.billing_paid', [
            'user_id' => $pending['user_id'],
            'plan_id' => $pending['plan_id'],
            'confirmation' => $verification->transactionId,
        ]);

        return $this->json($response, ['received' => true]);
    }

    private function buildSellerGatewayConfig(string $gateway, array $seller): array
    {
        return match ($gateway) {
            'stripe' => [
                'secret_key' => $seller['stripe_secret_key'] ?? '',
            ],
            'paypal' => [
                'client_id' => $seller['paypal_client_id'] ?? '',
                'secret' => $seller['paypal_secret'] ?? '',
                'sandbox' => ($seller['paypal_mode'] ?? 'test') === 'test',
            ],
            'pesapal' => [
                'consumer_key' => $seller['pesapal_consumer_key'] ?? '',
                'consumer_secret' => $seller['pesapal_consumer_secret'] ?? '',
                'sandbox' => ($seller['pesapal_mode'] ?? 'test') === 'test',
            ],
            default => [],
        };
    }

    private function buildBillingGatewayConfig(string $gateway, array $settings): array
    {
        return match ($gateway) {
            'pesapal' => [
                'consumer_key' => $settings['platform_pesapal_consumer_key'] ?? '',
                'consumer_secret' => $settings['platform_pesapal_consumer_secret'] ?? '',
                'sandbox' => ($settings['platform_pesapal_mode'] ?? 'test') !== 'live',
            ],
            default => [],
        };
    }

    private function activateBillingSubscription(int $userId, int $planId, string $cycle, string $gateway, string $reference, float $amount): void
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

        $stmt = $this->db->prepare('UPDATE users SET plan_id = ?, plan_expires_at = ? WHERE id = ?');
        $stmt->execute([$planId, $expiresAt->format('Y-m-d H:i:s'), $userId]);
    }

    private function decrementStockForOrder(int $orderId): void
    {
        $items = $this->orderItemModel->findByOrder($orderId);
        foreach ($items as $item) {
            $this->productModel->decrementStock((int) $item['product_id'], (int) $item['quantity']);
        }
    }

    private function sendOrderEmails(array $order): void
    {
        try {
            $shop = User::find((int) $order['user_id']);
            if (!$shop) {
                return;
            }

            $items = $this->orderItemModel->findByOrder((int) $order['id']);

            // Re-fetch order to get updated status
            $freshOrder = Order::find((int) $order['id']);
            if (!$freshOrder) {
                return;
            }

            // Email to customer
            $this->mailer->sendOrderConfirmation(
                $freshOrder['customer_email'] ?? '',
                $freshOrder['customer_name'] ?? '',
                $freshOrder,
                $items,
                $shop
            );

            // Email to seller
            $this->mailer->sendNewOrderNotification(
                $shop['email'] ?? '',
                $shop['store_name'] ?? '',
                $freshOrder,
                $items,
                $shop
            );
        } catch (\Throwable $e) {
            $this->logger->error('webhook.email_error', [
                'order_id' => $order['id'] ?? 0,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
