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
use TinyShop\Services\DB;
use TinyShop\Services\Mailer;
use TinyShop\Services\Payment;

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
        private readonly Payment $payment,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger,
        DB $database
    ) {
        $this->db = $database->pdo();
    }

    /**
     * POST /webhook/stripe
     * Handles Stripe checkout.session.completed events.
     * Re-verifies with Stripe API — never trusts the webhook payload alone.
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

        $seller = $this->userModel->findById((int) $order['user_id']);
        if (!$seller || empty($seller['stripe_secret_key'])) {
            $this->logger->warning('webhook.stripe.no_credentials', ['order_number' => $orderNumber]);
            return $this->json($response, ['received' => true]);
        }

        // Re-verify payment with Stripe API
        try {
            $verified = $this->payment->verifyStripeSession($sessionId, $seller['stripe_secret_key']);
        } catch (\Throwable $e) {
            $this->logger->error('webhook.stripe.verify_failed', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['received' => true]);
        }

        if (!empty($verified['paid'])) {
            $this->orderModel->update((int) $order['id'], [
                'status' => 'paid',
                'payment_intent_id' => $verified['payment_intent'] ?? $sessionId,
            ]);
            $this->decrementStockForOrder((int) $order['id']);
            $this->logger->info('webhook.stripe.paid', [
                'order_id' => $order['id'],
                'order_number' => $orderNumber,
            ]);
            $this->sendOrderEmails($order);
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * POST /webhook/paypal
     * Handles PayPal CHECKOUT.ORDER.APPROVED / PAYMENT.CAPTURE.COMPLETED events.
     * Re-verifies with PayPal API — never trusts the webhook payload alone.
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

        $seller = $this->userModel->findById((int) $order['user_id']);
        if (!$seller || empty($seller['paypal_client_id']) || empty($seller['paypal_secret'])) {
            $this->logger->warning('webhook.paypal.no_credentials', ['order_id' => $order['id']]);
            return $this->json($response, ['received' => true]);
        }

        // Re-verify payment status with PayPal API
        $sandbox = ($seller['paypal_mode'] ?? 'test') === 'test';
        try {
            $verified = $this->payment->getPayPalOrderStatus(
                $paypalOrderId,
                $seller['paypal_client_id'],
                $seller['paypal_secret'],
                $sandbox
            );
        } catch (\Throwable $e) {
            $this->logger->error('webhook.paypal.verify_failed', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['received' => true]);
        }

        if ($verified && !empty($verified['paid'])) {
            $this->orderModel->update((int) $order['id'], ['status' => 'paid']);
            $this->decrementStockForOrder((int) $order['id']);
            $this->logger->info('webhook.paypal.paid', [
                'order_id' => $order['id'],
                'paypal_order_id' => $paypalOrderId,
            ]);
            $this->sendOrderEmails($order);
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * POST /webhook/mpesa
     * Handles M-Pesa STK Push callback for storefront orders.
     */
    public function mpesaWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $sourceIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $this->logger->info('webhook.mpesa.received', ['source_ip' => $sourceIp]);

        if (empty($payload)) {
            return $this->json($response, ['ResultCode' => 1, 'ResultDesc' => 'Empty payload']);
        }

        $result = $this->payment->parseMpesaCallback($payload);
        if (!$result) {
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if ($result['paid'] && !empty($result['checkout_request_id'])) {
            $order = $this->orderModel->findByPaymentIntent($result['checkout_request_id']);
            if ($order && $order['status'] === 'pending') {
                $this->orderModel->update((int) $order['id'], [
                    'status' => 'paid',
                    'payment_intent_id' => $result['receipt_number'] ?? $result['checkout_request_id'],
                ]);
                $this->decrementStockForOrder((int) $order['id']);
                $this->logger->info('webhook.mpesa.paid', [
                    'order_id' => $order['id'],
                    'receipt' => $result['receipt_number'] ?? '',
                ]);
                $this->sendOrderEmails($order);
            }
        }

        return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * POST /webhook/mpesa/billing
     * Handles M-Pesa STK Push callback for platform billing payments.
     */
    public function mpesaBillingWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $sourceIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $this->logger->info('webhook.mpesa.billing_received', ['source_ip' => $sourceIp]);

        if (empty($payload)) {
            return $this->json($response, ['ResultCode' => 1, 'ResultDesc' => 'Empty payload']);
        }

        $result = $this->payment->parseMpesaCallback($payload);
        if (!$result || !$result['paid']) {
            // Mark as failed if we can identify the request
            if ($result && !$result['paid'] && !empty($result['checkout_request_id'])) {
                $stmt = $this->db->prepare(
                    'UPDATE billing_mpesa_pending SET status = ? WHERE checkout_request_id = ? AND status = ?'
                );
                $stmt->execute(['failed', $result['checkout_request_id'], 'pending']);
            }
            return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $checkoutRequestId = $result['checkout_request_id'] ?? '';
        if (empty($checkoutRequestId)) {
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
            $result['receipt_number'] ?? $checkoutRequestId,
            (float) $pending['amount']
        );

        $this->logger->info('webhook.mpesa.billing_paid', [
            'user_id' => $pending['user_id'],
            'plan_id' => $pending['plan_id'],
            'receipt' => $result['receipt_number'] ?? '',
        ]);

        return $this->json($response, ['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
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
            $shop = $this->userModel->findById((int) $order['user_id']);
            if (!$shop) {
                return;
            }

            $items = $this->orderItemModel->findByOrder((int) $order['id']);

            // Re-fetch order to get updated status
            $freshOrder = $this->orderModel->findById((int) $order['id']);
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
