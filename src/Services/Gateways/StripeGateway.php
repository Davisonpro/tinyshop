<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

final class StripeGateway implements GatewayInterface
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret = '',
    ) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function createPayment(PaymentRequest $request): PaymentResult
    {
        Stripe::setApiKey($this->secretKey);

        $lineItems = [];
        foreach ($request->lineItems as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($request->currency),
                    'product_data' => [
                        'name' => $item['product_name'],
                        'images' => $item['product_image'] ? [$item['product_image']] : [],
                    ],
                    'unit_amount' => (int) round($item['unit_price'] * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $params = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $request->successUrl,
            'cancel_url' => $request->cancelUrl,
            'metadata' => ['order_number' => $request->reference],
        ];

        if ($request->customerEmail !== '') {
            $params['customer_email'] = $request->customerEmail;
        }

        $session = StripeSession::create($params);

        return new PaymentResult(redirectUrl: $session->url);
    }

    public function verifyPayment(array $params): PaymentVerification
    {
        $sessionId = $params['session_id'] ?? '';
        if ($sessionId === '') {
            return new PaymentVerification(paid: false);
        }

        Stripe::setApiKey($this->secretKey);

        $session = StripeSession::retrieve($sessionId);
        if ($session->payment_status === 'paid') {
            return new PaymentVerification(
                paid: true,
                reference: $session->metadata['order_number'] ?? '',
                transactionId: $session->payment_intent ?? '',
                amount: $session->amount_total / 100,
                raw: ['session_id' => $sessionId],
            );
        }

        return new PaymentVerification(paid: false);
    }

    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        $sigHeader = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';
        if ($sigHeader === '' || $this->webhookSecret === '') {
            return new PaymentVerification(paid: false);
        }

        $event = StripeWebhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            return new PaymentVerification(
                paid: $session->payment_status === 'paid',
                reference: $session->metadata['order_number'] ?? '',
                transactionId: $session->payment_intent ?? '',
                raw: ['event_type' => $event->type],
            );
        }

        return new PaymentVerification(paid: false);
    }
}
