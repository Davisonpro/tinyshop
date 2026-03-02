<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * PayPal gateway (Orders v2 API).
 *
 * @since 1.0.0
 */
final class PayPalGateway implements GatewayInterface
{
    private readonly string $baseUrl;

    public function __construct(
        private readonly string $clientId,
        private readonly string $secret,
        bool $sandbox = true,
    ) {
        $this->baseUrl = $sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    public function name(): string
    {
        return 'paypal';
    }

    /** {@inheritDoc} */
    public function createPayment(PaymentRequest $request): PaymentResult
    {
        $token = $this->getAccessToken();

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper($request->currency),
                    'value' => number_format($request->amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $request->successUrl,
                'cancel_url' => $request->cancelUrl,
                'user_action' => 'PAY_NOW',
                'brand_name' => $request->brandName ?: 'TinyShop',
            ],
        ];

        $ch = curl_init($this->baseUrl . '/v2/checkout/orders');
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
        $redirectUrl = '';
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $redirectUrl = $link['href'];
                break;
            }
        }

        return new PaymentResult(
            redirectUrl: $redirectUrl,
            transactionId: $data['id'] ?? '',
        );
    }

    /** {@inheritDoc} */
    public function verifyPayment(array $params): PaymentVerification
    {
        $paypalOrderId = $params['token'] ?? '';
        if ($paypalOrderId === '') {
            return new PaymentVerification(paid: false);
        }

        $token = $this->getAccessToken();

        $ch = curl_init($this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId . '/capture');
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
            return new PaymentVerification(
                paid: true,
                reference: $data['id'] ?? '',
                transactionId: $data['id'] ?? '',
                amount: (float) ($data['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0),
                raw: $data,
            );
        }

        return new PaymentVerification(paid: false, raw: $data);
    }

    /** {@inheritDoc} */
    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        $data = json_decode($payload, true);
        if (!$data) {
            return new PaymentVerification(paid: false);
        }

        $eventType = $data['event_type'] ?? '';
        if ($eventType === 'CHECKOUT.ORDER.APPROVED' || $eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $data['resource'] ?? [];
            return new PaymentVerification(
                paid: true,
                reference: $resource['id'] ?? '',
                transactionId: $resource['id'] ?? '',
                raw: $data,
            );
        }

        return new PaymentVerification(paid: false, raw: $data);
    }

    /** Check order status without capturing. */
    public function getOrderStatus(string $paypalOrderId): PaymentVerification
    {
        $token = $this->getAccessToken();

        $ch = curl_init($this->baseUrl . '/v2/checkout/orders/' . urlencode($paypalOrderId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return new PaymentVerification(paid: false);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return new PaymentVerification(paid: false);
        }

        $data = json_decode($response, true);
        return new PaymentVerification(
            paid: ($data['status'] ?? '') === 'COMPLETED',
            reference: $data['id'] ?? '',
            transactionId: $data['id'] ?? '',
            raw: $data,
        );
    }

    /** Get a PayPal OAuth2 access token. */
    private function getAccessToken(): string
    {
        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->clientId . ':' . $this->secret,
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
        $token = $data['access_token'] ?? '';
        if ($token === '') {
            throw new \RuntimeException('PayPal authentication failed: no access token in response');
        }
        return $token;
    }
}
