<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class PaymentResult
{
    public function __construct(
        public readonly bool $immediate = false,
        public readonly string $redirectUrl = '',
        public readonly string $transactionId = '',
    ) {}

    public function toArray(): array
    {
        return [
            'immediate'      => $this->immediate,
            'redirect_url'   => $this->redirectUrl,
            'transaction_id' => $this->transactionId,
        ];
    }
}
