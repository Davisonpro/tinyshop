<?php

declare(strict_types=1);

namespace TinyShop\Services\OAuth;

/**
 * OAuth provider contract.
 *
 * @since 1.0.0
 */
interface OAuthProviderInterface
{
    /** Provider identifier (e.g. "google", "tiktok"). */
    public function name(): string;

    /**
     * Build the authorization URL.
     *
     * @since 1.0.0
     *
     * @param  string $state CSRF state token.
     * @return string Authorization URL.
     */
    public function getAuthUrl(string $state): string;

    /**
     * Exchange an auth code for a token and fetch the user profile.
     *
     * @since 1.0.0
     *
     * @param  string $code Authorization code from callback.
     * @return OAuthUser|null User profile, or null on failure.
     */
    public function handleCallback(string $code): ?OAuthUser;
}
