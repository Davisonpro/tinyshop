<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class PaymentRequest
{
    /**
     * @param array<array{product_name: string, product_image: ?string, unit_price: float, quantity: int}> $lineItems
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
