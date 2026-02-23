<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class PesapalGateway implements GatewayInterface
{
    private readonly string $baseUrl;

    public function __construct(
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
        bool $sandbox = true,
    ) {
        $this->baseUrl = $sandbox
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';
    }

    public function name(): string
    {
        return 'pesapal';
    }

    public function createPayment(PaymentRequest $request): PaymentResult
    {
        $token = $this->getToken();

        // Register IPN endpoint
        $ipnId = $this->registerIPN($token, $request->webhookUrl);

        // Build billing address
        $billingAddress = ['email_address' => $request->customerEmail];
        if ($request->customerPhone !== '') {
            $billingAddress['phone_number'] = $request->customerPhone;
        }
        if ($request->customerName !== '') {
            $billingAddress['first_name'] = $request->customerName;
        }

        $payload = json_encode([
            'id' => $request->reference,
            'currency' => strtoupper($request->currency),
            'amount' => round($request->amount, 2),
            'description' => substr($request->description, 0, 100),
            'callback_url' => $request->successUrl,
            'notification_id' => $ipnId,
            'billing_address' => $billingAddress,
        ]);

        $ch = curl_init($this->baseUrl . '/api/Transactions/SubmitOrderRequest');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Pesapal order submission failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException('Pesapal order submission failed (HTTP ' . $httpCode . '): ' . ($response ?: ''));
        }

        $data = json_decode($response, true);

        // Pesapal may return HTTP 200 with an error object in the body
        if (!empty($data['error'])) {
            $errMsg = is_array($data['error'])
                ? ($data['error']['message'] ?? json_encode($data['error']))
                : (string) $data['error'];
            throw new \RuntimeException('Pesapal error: ' . $errMsg);
        }

        $redirectUrl = $data['redirect_url'] ?? '';
        $orderTrackingId = $data['order_tracking_id'] ?? '';

        if ($redirectUrl === '' || $orderTrackingId === '') {
            throw new \RuntimeException(
                'Pesapal order response missing redirect_url or tracking ID. Response: '
                . substr($response ?: '(empty)', 0, 500)
            );
        }

        return new PaymentResult(
            redirectUrl: $redirectUrl,
            transactionId: $orderTrackingId,
        );
    }

    public function verifyPayment(array $params): PaymentVerification
    {
        $trackingId = $params['tracking_id'] ?? $params['OrderTrackingId'] ?? '';
        if ($trackingId === '') {
            return new PaymentVerification(paid: false);
        }

        return $this->getTransactionStatus($trackingId);
    }

    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        // Pesapal IPN sends GET params (OrderTrackingId, OrderNotificationType, OrderMerchantReference)
        $trackingId = $params['OrderTrackingId'] ?? '';
        if ($trackingId === '') {
            return new PaymentVerification(paid: false);
        }

        return $this->getTransactionStatus($trackingId);
    }

    private function getTransactionStatus(string $orderTrackingId): PaymentVerification
    {
        $token = $this->getToken();

        $ch = curl_init($this->baseUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($orderTrackingId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
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
        // status_code: 0=INVALID, 1=COMPLETED, 2=FAILED, 3=REVERSED
        $statusCode = (int) ($data['status_code'] ?? 0);

        return new PaymentVerification(
            paid: $statusCode === 1,
            reference: $data['merchant_reference'] ?? '',
            transactionId: $data['confirmation_code'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            raw: [
                'status_code' => $statusCode,
                'payment_method' => $data['payment_method'] ?? '',
                'order_tracking_id' => $orderTrackingId,
            ],
        );
    }

    private function registerIPN(string $token, string $ipnUrl): string
    {
        $payload = json_encode([
            'url' => $ipnUrl,
            'ipn_notification_type' => 'GET',
        ]);

        $ch = curl_init($this->baseUrl . '/api/URLSetup/RegisterIPN');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Pesapal IPN registration failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException('Pesapal IPN registration failed (HTTP ' . $httpCode . ')');
        }

        $data = json_decode($response, true);
        $ipnId = $data['ipn_id'] ?? '';
        if ($ipnId === '') {
            throw new \RuntimeException('Pesapal IPN registration returned no ipn_id');
        }
        return $ipnId;
    }

    private function getToken(): string
    {
        $payload = json_encode([
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ]);

        $ch = curl_init($this->baseUrl . '/api/Auth/RequestToken');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Pesapal auth failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException('Pesapal auth failed (HTTP ' . $httpCode . '): ' . ($response ?: 'empty'));
        }

        $data = json_decode($response, true);
        $token = $data['token'] ?? '';
        if ($token === '') {
            throw new \RuntimeException('Pesapal auth returned no token');
        }
        return $token;
    }
}
