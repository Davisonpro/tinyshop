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
use TinyShop\Models\User;
use TinyShop\Services\Mailer;
use TinyShop\Services\Payment;

final class WebhookController
{
    use JsonResponder;

    public function __construct(
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly Product $productModel,
        private readonly User $userModel,
        private readonly Payment $payment,
        private readonly Mailer $mailer,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * POST /webhook/stripe
     * Handles Stripe checkout.session.completed events.
     */
    public function stripeWebhook(Request $request, Response $response): Response
    {
        $payload = (string) $request->getBody();
        $sigHeader = $request->getHeaderLine('Stripe-Signature');

        if (empty($payload)) {
            return $this->json($response, ['error' => 'Empty payload'], 400);
        }

        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json($response, ['error' => 'Invalid JSON'], 400);
        }

        $type = $event['type'] ?? '';

        if ($type === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $paymentIntent = $session['payment_intent'] ?? $session['id'] ?? '';
            $paymentStatus = $session['payment_status'] ?? '';

            if ($paymentStatus === 'paid' && $paymentIntent) {
                $order = $this->orderModel->findByPaymentIntent($paymentIntent);
                if ($order && $order['status'] === 'pending') {
                    $this->orderModel->update((int) $order['id'], ['status' => 'paid']);
                    $this->decrementStockForOrder((int) $order['id']);
                    $this->logger->info('webhook.stripe.paid', [
                        'order_id' => $order['id'],
                        'payment_intent' => $paymentIntent,
                    ]);
                    $this->sendOrderEmails($order);
                }
            }
        }

        return $this->json($response, ['received' => true]);
    }

    /**
     * POST /webhook/paypal
     * Handles PayPal CHECKOUT.ORDER.APPROVED / PAYMENT.CAPTURE.COMPLETED events.
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

        if (in_array($eventType, ['CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
            $paypalOrderId = $resource['id'] ?? $resource['supplementary_data']['related_ids']['order_id'] ?? '';

            if ($paypalOrderId) {
                $order = $this->orderModel->findByPaymentIntent($paypalOrderId);
                if ($order && $order['status'] === 'pending') {
                    $this->orderModel->update((int) $order['id'], ['status' => 'paid']);
                    $this->decrementStockForOrder((int) $order['id']);
                    $this->logger->info('webhook.paypal.paid', [
                        'order_id' => $order['id'],
                        'paypal_order_id' => $paypalOrderId,
                    ]);
                    $this->sendOrderEmails($order);
                }
            }
        }

        return $this->json($response, ['received' => true]);
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
                $shop['store_name'] ?? $shop['name'] ?? '',
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
