<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * M-Pesa STK Push gateway.
 *
 * @since 1.0.0
 */
final class MpesaGateway implements GatewayInterface
{
    private readonly string $baseUrl;

    public function __construct(
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
        private readonly string $shortcode,
        private readonly string $passkey,
        bool $sandbox = true,
    ) {
        $this->baseUrl = $sandbox
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';
    }

    public function name(): string
    {
        return 'mpesa';
    }

    /** {@inheritDoc} */
    public function createPayment(PaymentRequest $request): PaymentResult
    {
        $token = $this->getToken();
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) ceil($request->amount),
            'PartyA' => $request->customerPhone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $request->customerPhone,
            'CallBackURL' => $request->webhookUrl,
            'AccountReference' => substr($request->reference, 0, 12),
            'TransactionDesc' => substr($request->description, 0, 13),
        ];

        $ch = curl_init($this->baseUrl . '/mpesa/stkpush/v1/processrequest');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('M-Pesa STK push failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 200 || ($data['ResponseCode'] ?? '') !== '0') {
            $desc = $data['errorMessage'] ?? $data['ResponseDescription'] ?? ($response ?: 'Unknown error');
            throw new \RuntimeException('M-Pesa STK push failed (HTTP ' . $httpCode . '): ' . $desc);
        }

        return new PaymentResult(
            transactionId: $data['CheckoutRequestID'] ?? '',
        );
    }

    /** {@inheritDoc} M-Pesa uses callback only, no return-URL verification. */
    public function verifyPayment(array $params): PaymentVerification
    {
        // M-Pesa uses callback only — no return-URL verification
        return new PaymentVerification(paid: false);
    }

    /** {@inheritDoc} */
    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        $data = json_decode($payload, true);
        if (!$data) {
            return new PaymentVerification(paid: false);
        }

        $callback = $data['Body']['stkCallback'] ?? null;
        if (!$callback) {
            return new PaymentVerification(paid: false);
        }

        $resultCode = (int) ($callback['ResultCode'] ?? -1);
        $checkoutRequestId = $callback['CheckoutRequestID'] ?? '';

        if ($resultCode !== 0) {
            return new PaymentVerification(
                paid: false,
                reference: $checkoutRequestId,
                raw: ['result_desc' => $callback['ResultDesc'] ?? 'Payment failed'],
            );
        }

        // Parse CallbackMetadata items into key-value pairs
        $meta = [];
        foreach ($callback['CallbackMetadata']['Item'] ?? [] as $item) {
            $meta[$item['Name']] = $item['Value'] ?? null;
        }

        return new PaymentVerification(
            paid: true,
            reference: $checkoutRequestId,
            transactionId: $meta['MpesaReceiptNumber'] ?? '',
            amount: (float) ($meta['Amount'] ?? 0),
            raw: [
                'merchant_request_id' => $callback['MerchantRequestID'] ?? '',
                'phone' => $meta['PhoneNumber'] ?? '',
                'transaction_date' => $meta['TransactionDate'] ?? '',
            ],
        );
    }

    /** Get an M-Pesa OAuth token. */
    private function getToken(): string
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $ch = curl_init($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('M-Pesa auth failed: ' . $error);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $body = is_string($response) ? $response : '';
            throw new \RuntimeException('M-Pesa auth failed (HTTP ' . $httpCode . '): ' . $body);
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            throw new \RuntimeException('M-Pesa auth returned no token: ' . ($response ?: 'empty response'));
        }

        return $data['access_token'];
    }
}
