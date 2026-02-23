<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class PaymentVerification
{
    public function __construct(
        public readonly bool $paid,
        public readonly string $reference = '',
        public readonly string $transactionId = '',
        public readonly float $amount = 0,
        public readonly array $raw = [],
    ) {}

    public function toArray(): array
    {
        return [
            'paid'           => $this->paid,
            'reference'      => $this->reference,
            'transaction_id' => $this->transactionId,
            'amount'         => $this->amount,
        ];
    }
}
