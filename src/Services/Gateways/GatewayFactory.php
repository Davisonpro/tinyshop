<?php

declare(strict_types=1);

namespace TinyShop\Services\Gateways;

final class GatewayFactory
{
    /**
     * @param array<string, mixed> $config Gateway-specific credentials/settings
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
