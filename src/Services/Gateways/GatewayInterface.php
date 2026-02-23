<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

interface GatewayInterface
{
    public function name(): string;

    public function createPayment(PaymentRequest $request): PaymentResult;

    /**
     * @param array<string, string> $params Query params from the return URL
     */
    public function verifyPayment(array $params): PaymentVerification;

    /**
     * @param array<string, string> $params  Query params (Pesapal IPN sends GET params)
     * @param array<string, string> $headers Request headers (Stripe uses Stripe-Signature)
     */
    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification;
}
