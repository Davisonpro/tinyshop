<?php

declare(strict_types=1);

namespace TinyShop\Services;

use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;

final class Payment
{
    // ── Stripe ──

    public function createStripeSession(
        string $secretKey,
        array $items,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        string $customerEmail,
        string $orderNumber
    ): string {
        Stripe::setApiKey($secretKey);

        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => $item['product_name'],
                        'images' => $item['product_image'] ? [$item['product_image']] : [],
                    ],
                    'unit_amount' => (int) round($item['unit_price'] * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'metadata' => ['order_number' => $orderNumber],
        ]);

        return $session->url;
    }

    public function verifyStripeSession(string $sessionId, string $secretKey): array
    {
        Stripe::setApiKey($secretKey);

        $session = StripeSession::retrieve($sessionId);
        if ($session->payment_status === 'paid') {
            return [
                'paid' => true,
                'payment_intent' => $session->payment_intent,
                'order_number' => $session->metadata['order_number'] ?? null,
                'amount' => $session->amount_total / 100,
            ];
        }
        return ['paid' => false];
    }

    public function verifyStripeWebhook(string $payload, string $sigHeader, string $secret): ?array
    {
        $event = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            return [
                'order_number' => $session->metadata['order_number'] ?? null,
                'payment_intent' => $session->payment_intent ?? null,
                'paid' => $session->payment_status === 'paid',
            ];
        }
        return null;
    }

    // ── PayPal ──

    public function createPayPalOrder(
        string $clientId,
        string $secret,
        float $total,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        bool $sandbox = true
    ): ?string {
        $baseUrl = $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $token = $this->getPayPalAccessToken($clientId, $secret, $baseUrl);

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($currency),
                    'value' => number_format($total, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
                'brand_name' => 'TinyShop',
            ],
        ];

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('PayPal API request failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \RuntimeException('PayPal order creation failed with HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }

        return null;
    }

    public function capturePayPalOrder(
        string $paypalOrderId,
        string $clientId,
        string $secret,
        bool $sandbox = true
    ): ?array {
        $baseUrl = $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        $token = $this->getPayPalAccessToken($clientId, $secret, $baseUrl);

        $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $paypalOrderId . '/capture');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('PayPal capture request failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \RuntimeException('PayPal capture failed with HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        if (($data['status'] ?? '') === 'COMPLETED') {
            return [
                'paid' => true,
                'id' => $data['id'],
                'amount' => $data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? null,
            ];
        }

        return null;
    }

    private function getPayPalAccessToken(string $clientId, string $secret, string $baseUrl): string
    {
        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $clientId . ':' . $secret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('PayPal auth request failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException('PayPal authentication failed with HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
}
