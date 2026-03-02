<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

use RuntimeException;

/**
 * OAuth provider factory.
 *
 * @since 1.0.0
 */
final class OAuthProviderFactory
{
    /**
     * @param array<string, array<string, mixed>> $config  Provider config array.
     * @param string                              $baseUrl Application base URL.
     */
    public function __construct(
        private readonly array $config,
        private readonly string $baseUrl,
    ) {}

    /** Check if a provider is enabled. */
    public function isEnabled(string $provider): bool
    {
        return !empty($this->config[$provider]['enabled']);
    }

    /**
     * Create a provider instance.
     *
     * @since 1.0.0
     *
     * @param  string $provider Provider name.
     * @return OAuthProviderInterface
     *
     * @throws RuntimeException If provider is unknown.
     */
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
