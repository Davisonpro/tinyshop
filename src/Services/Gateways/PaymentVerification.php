<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Payment verification result.
 *
 * @since 1.0.0
 */
final class PaymentVerification
{
    /**
     * @param bool   $paid          Whether the payment succeeded.
     * @param string $reference     Order reference.
     * @param string $transactionId Provider transaction ID.
     * @param float  $amount        Confirmed amount.
     * @param array  $raw           Raw provider response for logging.
     */
    public function __construct(
        public readonly bool $paid,
        public readonly string $reference = '',
        public readonly string $transactionId = '',
        public readonly float $amount = 0,
        public readonly array $raw = [],
    ) {}

    /** @return array{paid: bool, reference: string, transaction_id: string, amount: float} */
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
