<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

/**
 * Payment gateway factory.
 *
 * @since 1.0.0
 */
final class GatewayFactory
{
    /**
     * Create a gateway instance.
     *
     * @since 1.0.0
     *
     * @param  string               $gateway Gateway name.
     * @param  array<string, mixed> $config  Gateway credentials.
     * @return GatewayInterface
     *
     * @throws \RuntimeException If gateway is not supported.
     */
    public function create(string $gateway, array $config = []): GatewayInterface
    {
        return match ($gateway) {
            'stripe' => new StripeGateway(
                secretKey: $config['secret_key'] ?? '',
                webhookSecret: $config['webhook_secret'] ?? '',
            ),
            'paypal' => new PayPalGateway(
                clientId: $config['client_id'] ?? '',
                secret: $config['secret'] ?? '',
                sandbox: $config['sandbox'] ?? true,
            ),
            'mpesa' => new MpesaGateway(
                consumerKey: $config['consumer_key'] ?? '',
                consumerSecret: $config['consumer_secret'] ?? '',
                shortcode: $config['shortcode'] ?? '',
                passkey: $config['passkey'] ?? '',
                sandbox: $config['sandbox'] ?? true,
            ),
            'pesapal' => new PesapalGateway(
                consumerKey: $config['consumer_key'] ?? '',
                consumerSecret: $config['consumer_secret'] ?? '',
                sandbox: $config['sandbox'] ?? true,
            ),
            'cod' => new CodGateway(),
            default => throw new \RuntimeException('Unsupported gateway: ' . $gateway),
        };
    }
}
