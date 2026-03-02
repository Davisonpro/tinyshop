<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Payment gateway contract.
 *
 * @since 1.0.0
 */
interface GatewayInterface
{
    /** Gateway identifier (e.g. "stripe", "paypal"). */
    public function name(): string;

    /**
     * Create a payment with the provider.
     *
     * @since 1.0.0
     *
     * @param  PaymentRequest $request Payment details.
     * @return PaymentResult
     */
    public function createPayment(PaymentRequest $request): PaymentResult;

    /**
     * Verify payment on return from the provider.
     *
     * @since 1.0.0
     *
     * @param  array<string, string> $params Return URL query params.
     * @return PaymentVerification
     */
    public function verifyPayment(array $params): PaymentVerification;

    /**
     * Parse a webhook notification from the provider.
     *
     * @since 1.0.0
     *
     * @param  string               $payload Raw request body.
     * @param  array<string, string> $params  Query params.
     * @param  array<string, string> $headers Request headers.
     * @return PaymentVerification
     */
    public function parseWebhook(string $payload, array $params = [], array $headers = []): PaymentVerification;
}
