<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Payment request value object.
 *
 * @since 1.0.0
 */
final class PaymentRequest
{
    /**
     * @param float  $amount     Total amount.
     * @param string $currency   ISO 4217 currency code.
     * @param string $reference  Order reference.
     * @param string $successUrl Success redirect URL.
     * @param string $cancelUrl  Cancel redirect URL.
     * @param string $webhookUrl Webhook URL.
     */
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $reference,
        public readonly string $successUrl,
        public readonly string $cancelUrl = '',
        public readonly string $webhookUrl = '',
        public readonly string $customerEmail = '',
        public readonly string $customerName = '',
        public readonly string $customerPhone = '',
        public readonly string $description = 'Payment',
        public readonly string $brandName = '',
        public readonly array $lineItems = [],
    ) {}
}
