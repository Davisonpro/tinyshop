<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Payment creation result.
 *
 * @since 1.0.0
 */
final class PaymentResult
{
    /**
     * @param bool   $immediate     True if no redirect needed (e.g. COD).
     * @param string $redirectUrl   Checkout redirect URL.
     * @param string $transactionId Provider transaction ID.
     */
    public function __construct(
        public readonly bool $immediate = false,
        public readonly string $redirectUrl = '',
        public readonly string $transactionId = '',
    ) {}

    /** @return array{immediate: bool, redirect_url: string, transaction_id: string} */
    public function toArray(): array
    {
        return [
            'immediate'      => $this->immediate,
            'redirect_url'   => $this->redirectUrl,
            'transaction_id' => $this->transactionId,
        ];
    }
}
