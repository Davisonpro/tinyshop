<?php

declare(strict_types=1);

namespace TinyShop\Controllers\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use TinyShop\Controllers\Traits\JsonResponder;
use TinyShop\Models\Coupon;
use TinyShop\Models\Order;
use TinyShop\Models\OrderItem;
use TinyShop\Models\Product;
use TinyShop\Models\User;
use TinyShop\Services\Mailer;
use TinyShop\Services\Payment;
use TinyShop\Services\Validation;

final class CheckoutController
{
    use JsonResponder;

    public function __construct(
        private readonly User $userModel,
        private readonly Product $productModel,
        private readonly Order $orderModel,
        private readonly OrderItem $orderItemModel,
        private readonly Coupon $couponModel,
        private readonly Payment $payment,
        private readonly Mailer $mailer,
        private readonly Validation $validation,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * POST /api/checkout/validate
     * Validate cart items: check stock, prices, availability.
     */
    public function validateCart(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);
        $items = $data['items'] ?? [];

        if ($shopId <= 0 || !is_array($items) || empty($items)) {
            return $this->json($response, ['error' => true, 'message' => 'Invalid cart data'], 422);
        }

        $shop = $this->userModel->findById($shopId);
        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $validated = [];
        $errors = [];

        foreach ($items as $item) {
            $productId = (int) ($item['productId'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            $product = $this->productModel->findById($productId);
            if (!$product || (int) $product['user_id'] !== $shopId || !(int) $product['is_active']) {
                $errors[] = ($item['name'] ?? 'Item') . ' is no longer available';
                continue;
            }

            if ((int) $product['is_sold']) {
                $errors[] = $product['name'] . ' is sold out';
                continue;
            }

            if ($product['stock_quantity'] !== null && (int) $product['stock_quantity'] < $qty) {
                $available = (int) $product['stock_quantity'];
                if ($available === 0) {
                    $errors[] = $product['name'] . ' is sold out';
                } else {
                    $errors[] = 'Only ' . $available . ' of ' . $product['name'] . ' available';
                }
                continue;
            }

            $validated[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_image' => $product['image_url'] ?? null,
                'variation' => $item['variation'] ?? '',
                'quantity' => $qty,
                'unit_price' => (float) $product['price'],
                'total' => round((float) $product['price'] * $qty, 2),
            ];
        }

        if (!empty($errors)) {
            return $this->json($response, [
                'error' => true,
                'message' => $errors[0],
                'errors' => $errors,
            ], 422);
        }

        $subtotal = 0;
        foreach ($validated as $v) {
            $subtotal += $v['total'];
        }

        return $this->json($response, [
            'success' => true,
            'items' => $validated,
            'subtotal' => round($subtotal, 2),
        ]);
    }

    /**
     * POST /api/checkout/create
     * Create order, decrement stock, initiate payment.
     */
    public function createOrder(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody();
        $shopId = (int) ($data['shop_id'] ?? 0);
        $items = $data['items'] ?? [];
        $gateway = $data['payment_method'] ?? '';
        $customerName = trim($data['customer_name'] ?? '');
        $customerPhone = trim($data['customer_phone'] ?? '');
        $customerEmail = trim($data['customer_email'] ?? '');
        $notes = trim($data['notes'] ?? '');

        // Validate required fields
        if (empty($customerName)) {
            return $this->json($response, ['error' => true, 'message' => 'Name is required'], 422);
        }
        if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['error' => true, 'message' => 'A valid email is required'], 422);
        }
        if (!in_array($gateway, ['stripe', 'paypal', 'cod', 'mpesa'], true)) {
            return $this->json($response, ['error' => true, 'message' => 'Please select a payment method'], 422);
        }

        // Validate M-Pesa phone number
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

        $shop = $this->userModel->findById($shopId);
        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        // Verify gateway credentials and enabled status
        if ($gateway === 'stripe' && (empty($shop['stripe_enabled']) || empty($shop['stripe_public_key']) || empty($shop['stripe_secret_key']))) {
            return $this->json($response, ['error' => true, 'message' => 'Stripe is not configured for this shop'], 422);
        }
        if ($gateway === 'paypal' && (empty($shop['paypal_enabled']) || empty($shop['paypal_client_id']) || empty($shop['paypal_secret']))) {
            return $this->json($response, ['error' => true, 'message' => 'PayPal is not configured for this shop'], 422);
        }
        if ($gateway === 'cod' && empty($shop['cod_enabled'])) {
            return $this->json($response, ['error' => true, 'message' => 'Pay on Delivery is not available'], 422);
        }
        if ($gateway === 'mpesa' && (empty($shop['mpesa_enabled']) || empty($shop['mpesa_shortcode'])
            || empty($shop['mpesa_consumer_key']) || empty($shop['mpesa_consumer_secret'])
            || empty($shop['mpesa_passkey']))) {
            return $this->json($response, ['error' => true, 'message' => 'M-Pesa is not configured for this shop'], 422);
        }

        // Validate and build order items
        $orderItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $productId = (int) ($item['productId'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            $product = $this->productModel->findById($productId);
            if (!$product || (int) $product['user_id'] !== $shopId || !(int) $product['is_active'] || (int) $product['is_sold']) {
                return $this->json($response, ['error' => true, 'message' => ($item['name'] ?? 'An item') . ' is no longer available'], 422);
            }

            // Check stock
            if ($product['stock_quantity'] !== null && (int) $product['stock_quantity'] < $qty) {
                return $this->json($response, ['error' => true, 'message' => 'Not enough stock for ' . $product['name']], 422);
            }

            $lineTotal = round((float) $product['price'] * $qty, 2);
            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'product_image' => $product['image_url'] ?? null,
                'variation' => $item['variation'] ?? null,
                'quantity' => $qty,
                'unit_price' => (float) $product['price'],
                'total' => $lineTotal,
            ];
            $subtotal += $lineTotal;
        }

        if (empty($orderItems)) {
            return $this->json($response, ['error' => true, 'message' => 'Cart is empty'], 422);
        }

        $subtotal = round($subtotal, 2);
        $orderNumber = Order::generateOrderNumber();
        $currency = $shop['currency'] ?? 'KES';

        // Apply coupon if provided
        $couponCode = strtoupper(trim($data['coupon_code'] ?? ''));
        $discountAmount = 0;
        $couponId = null;

        if ($couponCode !== '') {
            $coupon = $this->couponModel->findByUserAndCode($shopId, $couponCode);
            if ($coupon) {
                $result = $this->couponModel->validate($coupon, $subtotal);
                if ($result['valid']) {
                    $discountAmount = $result['discount'];
                    $couponId = (int) $coupon['id'];
                }
            }
        }

        $finalAmount = round(max(0, $subtotal - $discountAmount), 2);

        // Create the order
        $orderId = $this->orderModel->create([
            'user_id' => $shopId,
            'product_id' => $orderItems[0]['product_id'],
            'order_number' => $orderNumber,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone ?: null,
            'customer_email' => $customerEmail,
            'notes' => $notes ?: null,
            'amount' => $finalAmount,
            'subtotal' => $subtotal,
            'currency' => $currency,
            'status' => 'pending',
            'payment_method' => $gateway,
            'payment_gateway' => $gateway,
            'coupon_code' => $couponCode ?: null,
            'discount_amount' => $discountAmount,
        ]);

        // Create order items
        $this->orderItemModel->createBatch($orderId, $orderItems);

        // COD: confirm immediately (no payment redirect needed)
        if ($gateway === 'cod') {
            $this->decrementStockForOrder($orderId);
            $this->incrementCouponUsage([
                'user_id' => $shopId,
                'coupon_code' => $couponCode ?: null,
            ]);
            $this->sendOrderEmails(
                array_merge(['id' => $orderId], $this->orderModel->findById($orderId)),
                $shop
            );

            $this->logger->info('checkout.order_created', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'gateway' => 'cod',
                'amount' => $finalAmount,
                'discount' => $discountAmount,
            ]);

            return $this->json($response, [
                'success' => true,
                'order_url' => '/order/' . $orderNumber,
                'order_number' => $orderNumber,
            ]);
        }

        // M-Pesa: initiate STK push, return polling info (no redirect)
        if ($gateway === 'mpesa') {
            $host = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
            $port = $request->getUri()->getPort();
            if ($port && $port !== 80 && $port !== 443) {
                $host .= ':' . $port;
            }
            $callbackUrl = $host . '/webhook/mpesa';
            $mpesaSandbox = ($shop['mpesa_mode'] ?? 'test') === 'test';

            try {
                $stkResult = $this->payment->initiateStkPush(
                    $shop['mpesa_consumer_key'],
                    $shop['mpesa_consumer_secret'],
                    $shop['mpesa_shortcode'],
                    $shop['mpesa_passkey'],
                    $mpesaPhone,
                    $finalAmount,
                    $callbackUrl,
                    $orderNumber,
                    'Payment',
                    $mpesaSandbox
                );

                $this->orderModel->update($orderId, [
                    'payment_intent_id' => $stkResult['CheckoutRequestID'] ?? '',
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('checkout.mpesa_stk_error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
                return $this->json($response, ['error' => true, 'message' => $e->getMessage()], 500);
            }

            $this->logger->info('checkout.order_created', [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'gateway' => 'mpesa',
                'amount' => $finalAmount,
            ]);

            return $this->json($response, [
                'success' => true,
                'gateway' => 'mpesa',
                'order_number' => $orderNumber,
                'poll_url' => '/api/checkout/status?order=' . $orderNumber,
            ]);
        }

        // Online payment: stock is decremented after payment confirmation (handleReturn / webhooks)

        // Build return/cancel URLs
        $host = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
        $port = $request->getUri()->getPort();
        if ($port && $port !== 80 && $port !== 443) {
            $host .= ':' . $port;
        }
        $successUrl = $host . '/checkout/return/' . $gateway . '?order=' . $orderNumber;
        $cancelUrl = $host . '/checkout';

        try {
            if ($gateway === 'stripe') {
                $redirectUrl = $this->payment->createStripeSession(
                    $shop['stripe_secret_key'],
                    $orderItems,
                    $currency,
                    $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
                    $cancelUrl,
                    $customerEmail,
                    $orderNumber
                );
            } else {
                $paypalSandbox = ($shop['paypal_mode'] ?? 'test') === 'test';
                $redirectUrl = $this->payment->createPayPalOrder(
                    $shop['paypal_client_id'],
                    $shop['paypal_secret'],
                    $finalAmount,
                    $currency,
                    $successUrl,
                    $cancelUrl,
                    $paypalSandbox,
                    $shop['store_name'] ?? ''
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error('checkout.payment_error', [
                'order_id' => $orderId,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return $this->json($response, ['error' => true, 'message' => 'Payment setup failed. Please try again.'], 500);
        }

        if (!$redirectUrl) {
            return $this->json($response, ['error' => true, 'message' => 'Could not connect to payment provider. Please try again.'], 500);
        }

        $this->logger->info('checkout.order_created', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'gateway' => $gateway,
            'amount' => $finalAmount,
            'discount' => $discountAmount,
        ]);

        return $this->json($response, [
            'success' => true,
            'redirect_url' => $redirectUrl,
            'order_number' => $orderNumber,
        ]);
    }

    /**
     * GET /checkout/return/{gateway}
     * Payment return handler — verifies payment and redirects to confirmation.
     */
    public function handleReturn(Request $request, Response $response, array $args): Response
    {
        $gateway = $args['gateway'] ?? '';
        $params = $request->getQueryParams();
        $orderNumber = $params['order'] ?? '';

        if (empty($orderNumber)) {
            return $this->json($response, ['error' => true, 'message' => 'Missing order reference'], 400);
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order) {
            return $this->json($response, ['error' => true, 'message' => 'Order not found'], 404);
        }

        $shop = $this->userModel->findById((int) $order['user_id']);
        if (!$shop) {
            return $this->json($response, ['error' => true, 'message' => 'Shop not found'], 404);
        }

        $wasPending = $order['status'] === 'pending';

        try {
            if ($gateway === 'stripe') {
                $sessionId = $params['session_id'] ?? '';
                if ($sessionId) {
                    $result = $this->payment->verifyStripeSession($sessionId, $shop['stripe_secret_key']);
                    if ($result && ($result['paid'] ?? false)) {
                        $this->orderModel->update((int) $order['id'], [
                            'status' => 'paid',
                            'payment_intent_id' => $result['payment_intent'] ?? $sessionId,
                        ]);
                    }
                }
            } elseif ($gateway === 'paypal') {
                $paypalOrderId = $params['token'] ?? '';
                if ($paypalOrderId) {
                    $paypalSandbox = ($shop['paypal_mode'] ?? 'test') === 'test';
                    $result = $this->payment->capturePayPalOrder($paypalOrderId, $shop['paypal_client_id'], $shop['paypal_secret'], $paypalSandbox);
                    if ($result && ($result['paid'] ?? false)) {
                        $this->orderModel->update((int) $order['id'], [
                            'status' => 'paid',
                            'payment_intent_id' => $result['id'] ?? $paypalOrderId,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('checkout.return_error', [
                'order_number' => $orderNumber,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
        }

        // On successful payment: decrement stock, increment coupon usage, send emails
        if ($wasPending) {
            $freshOrder = $this->orderModel->findById((int) $order['id']);
            if ($freshOrder && $freshOrder['status'] === 'paid') {
                $this->decrementStockForOrder((int) $freshOrder['id']);
                $this->incrementCouponUsage($freshOrder);
                $this->sendOrderEmails($freshOrder, $shop);
            }
        }

        // Redirect to confirmation page
        $confirmUrl = '/order/' . $orderNumber;
        return $response->withHeader('Location', $confirmUrl)->withStatus(302);
    }

    /**
     * GET /api/checkout/status?order={orderNumber}
     * Returns current order status for polling (used by M-Pesa flow).
     */
    public function checkStatus(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $orderNumber = $params['order'] ?? '';

        if (empty($orderNumber)) {
            return $this->json($response, ['error' => true, 'message' => 'Missing order number'], 400);
        }

        $order = $this->orderModel->findByOrderNumber($orderNumber);
        if (!$order) {
            return $this->json($response, ['error' => true, 'message' => 'Order not found'], 404);
        }

        return $this->json($response, [
            'status' => $order['status'],
            'order_number' => $order['order_number'],
            'order_url' => '/order/' . $order['order_number'],
        ]);
    }

    private function incrementCouponUsage(array $order): void
    {
        $couponCode = $order['coupon_code'] ?? '';
        if ($couponCode === '' || $couponCode === null) {
            return;
        }

        $coupon = $this->couponModel->findByUserAndCode((int) $order['user_id'], $couponCode);
        if ($coupon) {
            $this->couponModel->incrementUsage((int) $coupon['id']);
        }
    }

    private function decrementStockForOrder(int $orderId): void
    {
        $items = $this->orderItemModel->findByOrder($orderId);
        foreach ($items as $item) {
            $this->productModel->decrementStock((int) $item['product_id'], (int) $item['quantity']);
        }
    }

    private function sendOrderEmails(array $order, array $shop): void
    {
        try {
            $items = $this->orderItemModel->findByOrder((int) $order['id']);

            // Email to customer
            $this->mailer->sendOrderConfirmation(
                $order['customer_email'] ?? '',
                $order['customer_name'] ?? '',
                $order,
                $items,
                $shop
            );

            // Email to seller
            $this->mailer->sendNewOrderNotification(
                $shop['email'] ?? '',
                $shop['store_name'] ?? '',
                $order,
                $items,
                $shop
            );
        } catch (\Throwable $e) {
            $this->logger->error('checkout.email_error', [
                'order_id' => $order['id'] ?? 0,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
