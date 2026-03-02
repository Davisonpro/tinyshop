<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Cash on delivery gateway.
 *
 * @since 1.0.0
 */
final class CodGateway implements GatewayInterface
{
    public function name(): string
    {
        return 'cod';
    }

    /** {@inheritDoc} */
    public function createPayment(PaymentRequest $request): PaymentResult
    {
        return new PaymentResult(immediate: true);
    }

    /** {@inheritDoc} */
    public function verifyPayment(array $params): PaymentVerification
    {
        return new PaymentVerification(paid: true);
    }

    /** {@inheritDoc} */
    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification
    {
        return new PaymentVerification(paid: false);
    }
}
