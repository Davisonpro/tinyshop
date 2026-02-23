<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class CodGateway implements GatewayInterface
{
    public function name(): string
    {
        return 'cod';
    }

    public function createPayment(PaymentRequest $request): PaymentResult
    {
        return new PaymentResult(immediate: true);
    }

    public function verifyPayment(array $params): PaymentVerification
    {
        return new PaymentVerification(paid: true);
    }

    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        return new PaymentVerification(paid: false);
    }
}
