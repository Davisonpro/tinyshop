<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

use RuntimeException;

final class OAuthProviderFactory
{
    public function __construct(
        private readonly array $config,
        private readonly string $baseUrl,
    ) {}

    public function isEnabled(string $provider): bool
    {
        return !empty($this->config[$provider]['enabled']);
    }

    public function create(string $provider): OAuthProviderInterface
    {
        $cfg = $this->config[$provider] ?? null;
        if (!$cfg) {
            throw new RuntimeException("Unknown OAuth provider: {$provider}");
        }

        $redirectUri = $this->baseUrl . ($cfg['redirect_uri'] ?? '');

        return match ($provider) {
            'google' => new GoogleProvider(
                clientId: $cfg['client_id'] ?? '',
                clientSecret: $cfg['client_secret'] ?? '',
                redirectUri: $redirectUri,
            ),
            'instagram' => new InstagramProvider(
                clientId: $cfg['client_id'] ?? '',
                clientSecret: $cfg['client_secret'] ?? '',
                redirectUri: $redirectUri,
            ),
            'tiktok' => new TiktokProvider(
                clientKey: $cfg['client_key'] ?? '',
                clientSecret: $cfg['client_secret'] ?? '',
                redirectUri: $redirectUri,
            ),
            default => throw new RuntimeException("Unsupported OAuth provider: {$provider}"),
        };
    }
}
